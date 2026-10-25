<?php

namespace App\Filament\Gestion\Resources\Core\Students;

use App\Filament\Gestion\Concerns\RestrictedToStaffMembers;
use App\Filament\Gestion\Resources\Core\Students\Pages\CreateStudent;
use App\Filament\Gestion\Resources\Core\Students\Pages\EditStudent;
use App\Filament\Gestion\Resources\Core\Students\Pages\ListStudents;
use App\Filament\Gestion\Resources\Core\Students\RelationManagers\EnrollmentsRelationManager;
use App\Filament\Gestion\Resources\Core\Students\Schemas\StudentForm;
use App\Filament\Gestion\Resources\Core\Students\Tables\StudentsTable;
use App\Models\Core\Student;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class StudentResource extends Resource
{
    use RestrictedToStaffMembers;

    protected static ?string $model = Student::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|UnitEnum|null $navigationGroup = 'Élèves et familles';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'élève';

    protected static ?string $pluralModelLabel = 'élèves';

    protected static ?string $recordTitleAttribute = 'full_name';

    public static function form(Schema $schema): Schema
    {
        return StudentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StudentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            EnrollmentsRelationManager::class,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['code', 'mena_number', 'last_name', 'first_name'];
    }

    /**
     * @param  Student  $record
     */
    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return "{$record->full_name} ({$record->code})";
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStudents::route('/'),
            'create' => CreateStudent::route('/create'),
            'edit' => EditStudent::route('/{record}/edit'),
        ];
    }
}
