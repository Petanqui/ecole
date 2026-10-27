<?php

namespace App\Filament\Gestion\Resources\Grades\Assessments\RelationManagers;

use App\Models\Grades\Assessment;
use App\Models\Grades\Mark;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\CheckboxColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Grade entry: one row per student, scores typed straight into the table.
 */
class MarksRelationManager extends RelationManager
{
    protected static string $relationship = 'marks';

    protected static ?string $title = 'Notes';

    protected static ?string $modelLabel = 'note';

    protected static ?string $pluralModelLabel = 'notes';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        /** @var Assessment $assessment */
        $assessment = $this->getOwnerRecord();

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with('student')
                ->join('students', 'students.id', '=', 'marks.student_id')
                ->select('marks.*')
                ->orderBy('students.last_name')
                ->orderBy('students.first_name'))
            ->paginated(false)
            ->columns([
                TextColumn::make('student.full_name')
                    ->label('Élève'),
                TextColumn::make('student.code')
                    ->label('Code')
                    ->color('gray'),
                TextInputColumn::make('score')
                    ->label("Note / {$assessment->max_score}")
                    ->type('number')
                    ->step(0.25)
                    ->rules(['nullable', 'numeric', 'min:0', "max:{$assessment->max_score}"])
                    ->disabled(fn (Mark $record): bool => $record->is_absent),
                CheckboxColumn::make('is_absent')
                    ->label('Absent(e)'),
            ]);
    }
}
