<?php

namespace App\Filament\Gestion\Resources\Core\Enrollments;

use App\Filament\Gestion\Concerns\RestrictedToStaffMembers;
use App\Filament\Gestion\Resources\Core\Enrollments\Pages\CreateEnrollment;
use App\Filament\Gestion\Resources\Core\Enrollments\Pages\EditEnrollment;
use App\Filament\Gestion\Resources\Core\Enrollments\Pages\ListEnrollments;
use App\Filament\Gestion\Resources\Core\Enrollments\Schemas\EnrollmentForm;
use App\Filament\Gestion\Resources\Core\Enrollments\Tables\EnrollmentsTable;
use App\Models\Core\Enrollment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class EnrollmentResource extends Resource
{
    use RestrictedToStaffMembers;

    protected static ?string $model = Enrollment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Scolarité';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'inscription';

    protected static ?string $pluralModelLabel = 'inscriptions';

    public static function form(Schema $schema): Schema
    {
        return EnrollmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EnrollmentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEnrollments::route('/'),
            'create' => CreateEnrollment::route('/create'),
            'edit' => EditEnrollment::route('/{record}/edit'),
        ];
    }
}
