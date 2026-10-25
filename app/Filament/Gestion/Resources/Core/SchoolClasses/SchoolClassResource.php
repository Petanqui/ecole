<?php

namespace App\Filament\Gestion\Resources\Core\SchoolClasses;

use App\Filament\Gestion\Concerns\RestrictedToStaffMembers;
use App\Filament\Gestion\Resources\Core\SchoolClasses\Pages\CreateSchoolClass;
use App\Filament\Gestion\Resources\Core\SchoolClasses\Pages\EditSchoolClass;
use App\Filament\Gestion\Resources\Core\SchoolClasses\Pages\ListSchoolClasses;
use App\Filament\Gestion\Resources\Core\SchoolClasses\RelationManagers\ClassSubjectsRelationManager;
use App\Filament\Gestion\Resources\Core\SchoolClasses\Schemas\SchoolClassForm;
use App\Filament\Gestion\Resources\Core\SchoolClasses\Tables\SchoolClassesTable;
use App\Models\Core\SchoolClass;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SchoolClassResource extends Resource
{
    use RestrictedToStaffMembers;

    protected static ?string $model = SchoolClass::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Scolarité';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'classe';

    protected static ?string $pluralModelLabel = 'classes';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return SchoolClassForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SchoolClassesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ClassSubjectsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSchoolClasses::route('/'),
            'create' => CreateSchoolClass::route('/create'),
            'edit' => EditSchoolClass::route('/{record}/edit'),
        ];
    }
}
