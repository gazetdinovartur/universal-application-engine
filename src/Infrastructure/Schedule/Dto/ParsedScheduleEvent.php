<?php

namespace App\Infrastructure\Schedule\Dto;

use App\Enum\ScheduleEventType;

final readonly class ParsedScheduleEvent
{
    public function __construct(
        public \DateTimeImmutable $startsAt,
        public \DateTimeImmutable $endsAt,
        public string $venueName,
        public int $venueSortOrder,
        public string $title,
        public ScheduleEventType $eventType,
    ) {
    }
}
