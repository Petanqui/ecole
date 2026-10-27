<?php

namespace App\Filament\Gestion\Resources\Billing\Fees;

use App\Filament\Gestion\Concerns\RestrictedToStaffMembers;
use App\Filament\Gestion\Resources\Billing\Fees\Pages\CreateFee;
use App\Filament\Gestion\Resources\Billing\Fees\Pages\EditFee;
use App\Filament\Gestion\Resources\Billing\Fees\Pages\ListFees;
use App\Filament\Gestion\Resources\Billing\Fees\Schemas\FeeForm;
use App\Filament\Gestion\Resources\Billing\Fees\Tables\FeesTable;
use App\Models\Billing\Fee;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class FeeResource extends Resource
{
    use RestrictedToStaffMembers;

    protected static ?string $model = Fee::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Facturation';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Tarifs';

    protected static ?string $modelLabel = 'frais';

    protected static ?string $pluralModelLabel = 'frais';

    public static function form(Schema $schema): Schema
    {
        return FeeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FeesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFees::route('/'),
            'create' => CreateFee::route('/create'),
            'edit' => EditFee::route('/{record}/edit'),
        ];
    }
}
