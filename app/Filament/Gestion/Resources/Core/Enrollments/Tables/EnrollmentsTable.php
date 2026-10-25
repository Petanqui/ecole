<?php

namespace App\Filament\Gestion\Resources\Core\Enrollments\Tables;

use App\Filament\Gestion\Resources\Core\Students\RelationManagers\EnrollmentsRelationManager;
use App\Models\Core\SchoolYear;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EnrollmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('enrolled_on', 'desc')
            ->columns([
                TextColumn::make('student.code')
                    ->label('Code')
                    ->searchable()
                    ->hiddenOn(EnrollmentsRelationManager::class),
                TextColumn::make('student.last_name')
                    ->label('Nom')
                    ->searchable()
                    ->hiddenOn(EnrollmentsRelationManager::class),
                TextColumn::make('student.first_name')
                    ->label('Prénoms')
                    ->searchable()
                    ->hiddenOn(EnrollmentsRelationManager::class),
                TextColumn::make('schoolYear.name')
                    ->label('Année scolaire'),
                TextColumn::make('schoolClass.name')
                    ->label('Classe')
                    ->sortable(),
                TextColumn::make('enrolled_on')
                    ->label('Inscrit(e) le')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('school_year_id')
                    ->label('Année scolaire')
                    ->relationship('schoolYear', 'name')
                    ->default(fn (): ?int => SchoolYear::current()->value('id'))
                    ->hiddenOn(EnrollmentsRelationManager::class),
                SelectFilter::make('school_class_id')
                    ->label('Classe')
                    ->relationship('schoolClass', 'name')
                    ->hiddenOn(EnrollmentsRelationManager::class),
            ])
            ->headerActions([
                CreateAction::make()
                    ->visible(fn ($livewire): bool => $livewire instanceof EnrollmentsRelationManager),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
