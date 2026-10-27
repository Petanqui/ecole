<?php

namespace App\Filament\Gestion\Resources\Grades\Assessments;

use App\Filament\Gestion\Resources\Grades\Assessments\Pages\CreateAssessment;
use App\Filament\Gestion\Resources\Grades\Assessments\Pages\EditAssessment;
use App\Filament\Gestion\Resources\Grades\Assessments\Pages\ListAssessments;
use App\Filament\Gestion\Resources\Grades\Assessments\RelationManagers\MarksRelationManager;
use App\Filament\Gestion\Resources\Grades\Assessments\Schemas\AssessmentForm;
use App\Filament\Gestion\Resources\Grades\Assessments\Tables\AssessmentsTable;
use App\Models\Grades\Assessment;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class AssessmentResource extends Resource
{
    protected static ?string $model = Assessment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;

    protected static string|UnitEnum|null $navigationGroup = 'Notes';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'évaluation';

    protected static ?string $pluralModelLabel = 'évaluations';

    /**
     * Teachers only ever see the assessments of the subjects they teach.
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var User $user */
        $user = auth()->user();

        return parent::getEloquentQuery()->visibleTo($user);
    }

    public static function form(Schema $schema): Schema
    {
        return AssessmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AssessmentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            MarksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAssessments::route('/'),
            'create' => CreateAssessment::route('/create'),
            'edit' => EditAssessment::route('/{record}/edit'),
        ];
    }
}
