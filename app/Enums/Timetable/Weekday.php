<?php

namespace App\Enums\Timetable;

use Carbon\CarbonInterface;
use Filament\Support\Contracts\HasLabel;

/**
 * School days, numbered like ISO-8601 (Monday = 1).
 */
enum Weekday: int implements HasLabel
{
    case Monday = 1;
    case Tuesday = 2;
    case Wednesday = 3;
    case Thursday = 4;
    case Friday = 5;
    case Saturday = 6;

    public function getLabel(): string
    {
        return match ($this) {
            self::Monday => 'Lundi',
            self::Tuesday => 'Mardi',
            self::Wednesday => 'Mercredi',
            self::Thursday => 'Jeudi',
            self::Friday => 'Vendredi',
            self::Saturday => 'Samedi',
        };
    }

    /**
     * The school day of a date; null on Sundays.
     */
    public static function of(CarbonInterface $date): ?self
    {
        return self::tryFrom($date->dayOfWeekIso);
    }
}
