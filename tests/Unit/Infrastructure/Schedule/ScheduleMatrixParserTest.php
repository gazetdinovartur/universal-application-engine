<?php

namespace App\Tests\Unit\Infrastructure\Schedule;

use App\Enum\ScheduleEventType;
use App\Infrastructure\Schedule\ScheduleMatrixParser;
use PHPUnit\Framework\TestCase;

final class ScheduleMatrixParserTest extends TestCase
{
    private ScheduleMatrixParser $parser;

    protected function setUp(): void
    {
        $this->parser = new ScheduleMatrixParser();
    }

    public function testParsesMatrixAndMergesConsecutiveSlots(): void
    {
        $csv = file_get_contents(__DIR__.'/../../../fixtures/schedule-matrix-sample.csv');
        self::assertNotFalse($csv);

        $schedule = $this->parser->parse($csv);

        self::assertSame(['Кухня', 'Костровая сцена', 'Большая Сцена и Поляна'], $schedule->venueNames);
        self::assertGreaterThanOrEqual(6, count($schedule->events));

        $yogaEvent = $this->findEvent($schedule->events, 'Универсальная йога с Андреем Плетнёвым');
        self::assertNotNull($yogaEvent);
        self::assertSame('2026-06-26', $yogaEvent->startsAt->format('Y-m-d'));
        self::assertSame('16:00', $yogaEvent->startsAt->format('H:i'));
        self::assertSame('Europe/Moscow', $yogaEvent->startsAt->getTimezone()->getName());
        self::assertSame('+03:00', $yogaEvent->startsAt->format('P'));
        self::assertSame('16:30', $yogaEvent->endsAt->format('H:i'));
        self::assertSame('Костровая сцена', $yogaEvent->venueName);
        self::assertSame(ScheduleEventType::Program, $yogaEvent->eventType);

        $meal = $this->findEvent($schedule->events, 'Ужин');
        self::assertNotNull($meal);
        self::assertSame(ScheduleEventType::Meal, $meal->eventType);

        $hidden = $this->findEvent($schedule->events, 'чек. занято');
        self::assertNull($hidden);

        $merged = $this->findEvent($schedule->events, 'Парная йога с Андреем Плетневым');
        self::assertNotNull($merged);
        self::assertSame('14:30', $merged->startsAt->format('H:i'));
        self::assertSame('15:00', $merged->endsAt->format('H:i'));
    }

    /**
     * @param list<\App\Infrastructure\Schedule\Dto\ParsedScheduleEvent> $events
     */
    private function findEvent(array $events, string $title): ?\App\Infrastructure\Schedule\Dto\ParsedScheduleEvent
    {
        foreach ($events as $event) {
            if ($event->title === $title) {
                return $event;
            }
        }

        return null;
    }
}
