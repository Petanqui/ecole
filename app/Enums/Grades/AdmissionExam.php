<?php

namespace App\Enums\Grades;

use Filament\Support\Contracts\HasLabel;

/**
 * The four CM2 exams whose mean (MAP, out of 20) decides admission to 6e.
 */
enum AdmissionExam: string implements HasLabel
{
    case RegionalComposition1 = 'regional_1';
    case RegionalComposition2 = 'regional_2';
    case MockExam1 = 'mock_1';
    case MockExam2 = 'mock_2';

    public function getLabel(): string
    {
        return match ($this) {
            self::RegionalComposition1 => 'Compo régionale 1',
            self::RegionalComposition2 => 'Compo régionale 2',
            self::MockExam1 => 'Examen blanc 1',
            self::MockExam2 => 'Examen blanc 2',
        };
    }
}
