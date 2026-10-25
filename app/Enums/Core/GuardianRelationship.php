<?php

namespace App\Enums\Core;

use Filament\Support\Contracts\HasLabel;

enum GuardianRelationship: string implements HasLabel
{
    case Father = 'father';
    case Mother = 'mother';
    case LegalGuardian = 'legal_guardian';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Father => 'Père',
            self::Mother => 'Mère',
            self::LegalGuardian => 'Tuteur / tutrice',
            self::Other => 'Autre',
        };
    }
}
