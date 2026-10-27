<?php

namespace App\Enums\Grades;

use App\Enums\Core\GradeLevel;
use Filament\Support\Contracts\HasLabel;

enum AssessmentType: string implements HasLabel
{
    case Test = 'test';
    case Homework = 'homework';
    case Composition = 'composition';

    public function getLabel(): string
    {
        return match ($this) {
            self::Test => 'Interrogation',
            self::Homework => 'Devoir',
            self::Composition => 'Composition',
        };
    }

    /**
     * The usual maximum score: out of 10 in primary (compositions out of 50), out of 20 in secondary.
     */
    public function defaultMaxScore(GradeLevel $level): int
    {
        if ($level->isPrimary()) {
            return $this === self::Composition ? 50 : 10;
        }

        return 20;
    }
}
