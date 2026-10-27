<?php

namespace App\Enums\Messaging;

use Filament\Support\Contracts\HasLabel;

/**
 * Whose parents an announcement is for, among the students enrolled in its school year.
 */
enum AnnouncementAudience: string implements HasLabel
{
    case School = 'school';
    case Level = 'level';
    case SchoolClass = 'class';

    public function getLabel(): string
    {
        return match ($this) {
            self::School => 'Toute l\'école',
            self::Level => 'Un niveau',
            self::SchoolClass => 'Une classe',
        };
    }
}
