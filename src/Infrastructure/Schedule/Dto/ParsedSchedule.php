<?php

namespace App\Infrastructure\Schedule\Dto;

final readonly class ParsedSchedule
{
    /**
     * @param list<string>              $venueNames
     * @param list<ParsedScheduleEvent> $events
     */
    public function __construct(
        public array $venueNames,
        public array $events,
    ) {
    }
}
