<?php

namespace App\Filament\Gestion\Resources\Billing\Payments;

use App\Filament\Gestion\Concerns\RestrictedToStaffMembers;
use App\Filament\Gestion\Resources\Billing\Payments\Pages\ManagePayments;
use App\Filament\Gestion\Resources\Billing\Payments\Schemas\PaymentForm;
use App\Filament\Gestion\Resources\Billing\Payments\Tables\PaymentsTable;
use App\Models\Billing\Payment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Payments are never edited nor deleted: a mistake is cancelled (administrators only) and recorded again.
 */
class PaymentResource extends Resource
{
    use RestrictedToStaffMembers;

    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string|UnitEnum|null $navigationGroup = 'Facturation';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'paiement';

    protected static ?string $pluralModelLabel = 'paiements';

    public static function form(Schema $schema): Schema
    {
        return PaymentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PaymentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePayments::route('/'),
        ];
    }
}
