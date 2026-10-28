<?php

namespace App\Enums\Billing;

use Filament\Support\Contracts\HasLabel;

enum DiscountKind: string implements HasLabel
{
    case Percentage = 'percentage';
    case Amount = 'amount';

    public function getLabel(): string
    {
        return match ($this) {
            self::Percentage => 'Pourcentage',
            self::Amount => 'Montant fixe',
        };
    }
}
