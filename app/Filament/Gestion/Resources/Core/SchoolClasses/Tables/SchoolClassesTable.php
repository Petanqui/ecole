<?php

namespace App\Filament\Gestion\Resources\Core\SchoolClasses\Tables;

use App\Enums\Core\GradeLevel;
use App\Models\Core\SchoolYear;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
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
                TextColumn::make('capacity')
                    ->label('Effectif max.')
                    ->numeric(),
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
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
