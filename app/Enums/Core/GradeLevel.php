<?php

namespace App\Enums\Core;

use Filament\Support\Contracts\HasLabel;

enum GradeLevel: string implements HasLabel
{
    case PetiteSection = 'ps';
    case MoyenneSection = 'ms';
    case GrandeSection = 'gs';
    case Cp1 = 'cp1';
    case Cp2 = 'cp2';
    case Ce1 = 'ce1';
    case Ce2 = 'ce2';
    case Cm1 = 'cm1';
    case Cm2 = 'cm2';

    public function getLabel(): string
    {
        return match ($this) {
            self::PetiteSection => 'Petite section',
            self::MoyenneSection => 'Moyenne section',
            self::GrandeSection => 'Grande section',
            default => strtoupper($this->value),
        };
    }

    public function isPreschool(): bool
    {
        return in_array($this, [self::PetiteSection, self::MoyenneSection, self::GrandeSection], true);
    }
}
