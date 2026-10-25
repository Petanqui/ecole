<?php

namespace App\Filament\Gestion\Resources\Core\Families;

use App\Filament\Gestion\Concerns\RestrictedToStaffMembers;
use App\Filament\Gestion\Resources\Core\Families\Pages\CreateFamily;
use App\Filament\Gestion\Resources\Core\Families\Pages\EditFamily;
use App\Filament\Gestion\Resources\Core\Families\Pages\ListFamilies;
use App\Filament\Gestion\Resources\Core\Families\RelationManagers\GuardiansRelationManager;
use App\Filament\Gestion\Resources\Core\Families\RelationManagers\StudentsRelationManager;
use App\Filament\Gestion\Resources\Core\Families\Schemas\FamilyForm;
use App\Filament\Gestion\Resources\Core\Families\Tables\FamiliesTable;
use App\Models\Core\Family;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class FamilyResource extends Resource
{
    use RestrictedToStaffMembers;

    protected static ?string $model = Family::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Élèves et familles';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'famille';

    protected static ?string $pluralModelLabel = 'familles';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return FamilyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FamiliesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            GuardiansRelationManager::class,
            StudentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFamilies::route('/'),
            'create' => CreateFamily::route('/create'),
            'edit' => EditFamily::route('/{record}/edit'),
        ];
    }
}
