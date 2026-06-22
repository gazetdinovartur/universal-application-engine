<?php

namespace App\Infrastructure\Schedule;

use App\Enum\ScheduleEventType;
use App\Infrastructure\Schedule\Dto\ParsedSchedule;
use App\Infrastructure\Schedule\Dto\ParsedScheduleEvent;

final class ScheduleMatrixParser
{
    private const int VENUE_COLUMN_START = 3;

    private const array DAY_NAMES = ['Пятница', 'Суббота', 'Воскресенье', 'Четверг', 'Понедельник', 'Вторник', 'Среда'];

    /**
     * @throws \InvalidArgumentException
     */
    public function parse(string $csvContent): ParsedSchedule
    {
        $rows = $this->readCsvRows($csvContent);
        if ($rows === []) {
            throw new \InvalidArgumentException('CSV is empty.');
        }

        $venueRowIndex = $this->findVenueHeaderRowIndex($rows);
        $venueNames = $this->extractVenueNames($rows[$venueRowIndex]);
        if ($venueNames === []) {
            throw new \InvalidArgumentException('Venue header row not found in schedule matrix.');
        }

        /** @var list<array{venueIndex: int, startsAt: \DateTimeImmutable, title: string, type: ScheduleEventType}> $slots */
        $slots = [];

        $currentDate = null;
        $currentHour = null;

        for ($rowIndex = $venueRowIndex + 1, $rowCount = count($rows); $rowIndex < $rowCount; ++$rowIndex) {
            $row = $rows[$rowIndex];
            $colA = $this->cell($row, 0);
            $colB = $this->cell($row, 1);
            $colC = $this->cell($row, 2);

            if ($colA === 'дата' && $colB === 'час') {
                continue;
            }

            if ($this->isDayLabelRow($colA)) {
                continue;
            }

            $slotTime = $this->resolveSlotTime($colA, $colB, $colC, $currentDate, $currentHour);
            if ($slotTime === null) {
                continue;
            }

            [$currentDate, $currentHour, $startsAt] = $slotTime;

            foreach ($venueNames as $venueOffset => $venueName) {
                $columnIndex = self::VENUE_COLUMN_START + $venueOffset;
                $title = $this->cell($row, $columnIndex);
                if ($title === '') {
                    continue;
                }

                $eventType = $this->detectEventType($title);
                if ($eventType === ScheduleEventType::Hidden) {
                    continue;
                }

                $slots[] = [
                    'venueIndex' => $venueOffset,
                    'startsAt' => $startsAt,
                    'title' => $title,
                    'type' => $eventType,
                ];
            }
        }

        $events = $this->mergeConsecutiveSlots($slots, $venueNames);

        return new ParsedSchedule($venueNames, $events);
    }

    /**
     * @return list<list<string>>
     */
    private function readCsvRows(string $csvContent): array
    {
        $handle = fopen('php://memory', 'r+');
        if ($handle === false) {
            throw new \RuntimeException('Unable to open memory stream.');
        }

        fwrite($handle, $csvContent);
        rewind($handle);

        $rows = [];
        while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            $rows[] = array_map(static fn ($value) => trim((string) ($value ?? '')), $row);
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @param list<list<string>> $rows
     */
    private function findVenueHeaderRowIndex(array $rows): int
    {
        foreach ($rows as $index => $row) {
            $candidate = $this->extractVenueNames($row);
            if (count($candidate) >= 2) {
                return $index;
            }
        }

        throw new \InvalidArgumentException('Venue header row not found in schedule matrix.');
    }

    /**
     * @param list<string> $row
     *
     * @return list<string>
     */
    private function extractVenueNames(array $row): array
    {
        $venues = [];
        for ($i = self::VENUE_COLUMN_START, $count = count($row); $i < $count; ++$i) {
            $name = $this->cell($row, $i);
            if ($name !== '') {
                $venues[] = $name;
            }
        }

        return $venues;
    }

    private function isDayLabelRow(string $colA): bool
    {
        if ($colA === '') {
            return false;
        }

        foreach (self::DAY_NAMES as $dayName) {
            if (str_starts_with($colA, $dayName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{0: string, 1: int, 2: \DateTimeImmutable}|null
     */
    private function resolveSlotTime(
        string $colA,
        string $colB,
        string $colC,
        ?string $currentDate,
        ?int $currentHour,
    ): ?array {
        if ($colA !== '' && preg_match('/^(\d{2})\.(\d{2})\.(\d{2})$/', $colA, $matches) === 1) {
            $year = 2000 + (int) $matches[3];
            $date = sprintf('%04d-%02d-%02d', $year, (int) $matches[2], (int) $matches[1]);
            $hour = (int) $colB;
            $minute = $colC === '30' ? 30 : 0;

            return [$date, $hour, $this->createDateTime($date, $hour, $minute)];
        }

        if ($colA === '' && $colB === '' && $colC === '30' && $currentDate !== null && $currentHour !== null) {
            return [$currentDate, $currentHour, $this->createDateTime($currentDate, $currentHour, 30)];
        }

        return null;
    }

    private function createDateTime(string $date, int $hour, int $minute): \DateTimeImmutable
    {
        return new \DateTimeImmutable(
            sprintf('%s %02d:%02d:00', $date, $hour, $minute),
            new \DateTimeZone('Europe/Moscow'),
        );
    }

    private function detectEventType(string $title): ScheduleEventType
    {
        $normalized = mb_strtolower(trim($title));

        if (preg_match('/чек\.?\s*занято/u', $normalized) === 1
            || str_starts_with($normalized, 'резерв')
            || str_starts_with($normalized, 'подготовка')
        ) {
            return ScheduleEventType::Hidden;
        }

        if (in_array($normalized, ['завтрак', 'обед', 'ужин'], true)) {
            return ScheduleEventType::Meal;
        }

        if (str_contains($normalized, 'начало фестиваля') || str_contains($normalized, 'завершение фестиваля')) {
            return ScheduleEventType::Service;
        }

        return ScheduleEventType::Program;
    }

    /**
     * @param list<array{venueIndex: int, startsAt: \DateTimeImmutable, title: string, type: ScheduleEventType}> $slots
     * @param list<string>                                                                                        $venueNames
     *
     * @return list<ParsedScheduleEvent>
     */
    private function mergeConsecutiveSlots(array $slots, array $venueNames): array
    {
        $events = [];
        $slotCount = count($slots);

        for ($i = 0; $i < $slotCount; ++$i) {
            $slot = $slots[$i];
            $endsAt = $slot['startsAt']->modify('+30 minutes');

            while ($i + 1 < $slotCount) {
                $next = $slots[$i + 1];
                if ($next['venueIndex'] !== $slot['venueIndex']
                    || $next['title'] !== $slot['title']
                    || $next['startsAt'] != $endsAt
                ) {
                    break;
                }

                $endsAt = $next['startsAt']->modify('+30 minutes');
                ++$i;
            }

            $events[] = new ParsedScheduleEvent(
                startsAt: $slot['startsAt'],
                endsAt: $endsAt,
                venueName: $venueNames[$slot['venueIndex']],
                venueSortOrder: $slot['venueIndex'],
                title: $slot['title'],
                eventType: $slot['type'],
            );
        }

        return $events;
    }

    /**
     * @param list<string> $row
     */
    private function cell(array $row, int $index): string
    {
        return $row[$index] ?? '';
    }
}
