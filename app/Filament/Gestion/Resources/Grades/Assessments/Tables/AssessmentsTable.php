<?php

namespace App\Filament\Gestion\Resources\Grades\Assessments\Tables;

use App\Enums\Grades\AssessmentType;
use App\Models\Grades\Assessment;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AssessmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['classSubject.schoolClass', 'classSubject.subject', 'term'])
                ->withCount([
                    'marks',
                    'marks as entered_marks_count' => fn (Builder $query) => $query->where(fn (Builder $query) => $query->whereNotNull('score')->orWhere('is_absent', true)),
                ]))
            ->defaultSort('held_on', 'desc')
            ->columns([
                TextColumn::make('held_on')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('classSubject.schoolClass.name')
                    ->label('Classe'),
                TextColumn::make('classSubject.subject.name')
                    ->label('Matière'),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge(),
                TextColumn::make('term.name')
                    ->label('Trimestre'),
                TextColumn::make('max_score')
                    ->label('Sur'),
                TextColumn::make('entered_marks_count')
                    ->label('Notes saisies')
                    ->formatStateUsing(fn (int $state, Assessment $record): string => "{$state} / {$record->marks_count}")
                    ->color(fn (int $state, Assessment $record): string => $state < $record->marks_count ? 'warning' : 'success'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Type')
                    ->options(AssessmentType::class),
                SelectFilter::make('term')
                    ->label('Trimestre')
                    ->options([1 => '1er trimestre', 2 => '2e trimestre', 3 => '3e trimestre'])
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'],
                        fn (Builder $query, $number) => $query->whereRelation('term', 'number', $number),
                    )),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Saisir les notes'),
                DeleteAction::make(),
            ]);
    }
}
