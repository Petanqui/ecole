<?php

namespace App\Enums\Core;

use Filament\Support\Contracts\HasLabel;

enum Gender: string implements HasLabel
{
    case Male = 'm';
    case Female = 'f';

    public function getLabel(): string
    {
        return match ($this) {
            self::Male => 'Garçon',
            self::Female => 'Fille',
        };
    }
}
