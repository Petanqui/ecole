<?php

namespace App\Filament\Gestion\Resources\Core\SchoolClasses\Tables;

use App\Enums\Core\GradeLevel;
use App\Models\Core\SchoolClass;
use App\Models\Core\SchoolYear;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SchoolClassesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Classe')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('level')
                    ->label('Niveau')
                    ->badge()
                    ->sortable(),
                TextColumn::make('schoolYear.name')
                    ->label('Année scolaire')
                    ->sortable(),
                TextColumn::make('enrollments_count')
                    ->label('Effectif')
                    ->counts('enrollments')
                    ->formatStateUsing(fn (int $state, SchoolClass $record): string => $record->capacity ? "{$state} / {$record->capacity}" : (string) $state)
                    ->color(fn (int $state, SchoolClass $record): ?string => $record->capacity && $state >= $record->capacity ? 'danger' : null),
            ])
            ->filters([
                SelectFilter::make('school_year_id')
                    ->label('Année scolaire')
                    ->relationship('schoolYear', 'name')
                    ->default(fn (): ?int => SchoolYear::current()->value('id')),
                SelectFilter::make('level')
                    ->label('Niveau')
                    ->options(GradeLevel::groupedOptions()),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
