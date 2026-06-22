<?php

namespace App\Enum;

enum ScheduleEventType: string
{
    case Program = 'program';
    case Meal = 'meal';
    case Service = 'service';
    case Hidden = 'hidden';
}
