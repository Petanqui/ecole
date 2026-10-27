<?php

namespace App\Enums\Attendance;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Where a student was during a lesson. Only absences and late arrivals are stored:
 * a student without an attendance record for a roll call was present.
 */
enum AttendanceStatus: string implements HasColor, HasLabel
{
    case Present = 'present';
    case Absent = 'absent';
    case Late = 'late';

    public function getLabel(): string
    {
        return match ($this) {
            self::Present => 'Présent(e)',
            self::Absent => 'Absent(e)',
            self::Late => 'En retard',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Present => 'success',
            self::Absent => 'danger',
            self::Late => 'warning',
        };
    }
}
