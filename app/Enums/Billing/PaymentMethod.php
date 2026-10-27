<?php

namespace App\Enums\Billing;

use Filament\Support\Contracts\HasLabel;

enum PaymentMethod: string implements HasLabel
{
    case Cash = 'cash';
    case OrangeMoney = 'orange_money';
    case MtnMomo = 'mtn_momo';
    case MoovMoney = 'moov_money';
    case Wave = 'wave';
    case BankTransfer = 'bank_transfer';
    case Cheque = 'cheque';

    public function getLabel(): string
    {
        return match ($this) {
            self::Cash => 'Espèces',
            self::OrangeMoney => 'Orange Money',
            self::MtnMomo => 'MTN MoMo',
            self::MoovMoney => 'Moov Money',
            self::Wave => 'Wave',
            self::BankTransfer => 'Virement',
            self::Cheque => 'Chèque',
        };
    }

    /**
     * Every method but cash leaves a trace to write down: transaction id, transfer or cheque number.
     */
    public function requiresReference(): bool
    {
        return $this !== self::Cash;
    }

    public function referenceLabel(): string
    {
        return match ($this) {
            self::Cheque => 'N° du chèque',
            self::BankTransfer => 'Référence du virement',
            default => 'ID de la transaction',
        };
    }
}
