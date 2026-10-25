<?php

namespace App\Filament\Gestion\Resources\Core\Students\Tables;

use App\Enums\Core\Gender;
use App\Filament\Gestion\Resources\Core\Families\RelationManagers\StudentsRelationManager;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StudentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('last_name')
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('last_name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('first_name')
                    ->label('Prénoms')
                    ->searchable(),
                TextColumn::make('gender')
                    ->label('Sexe'),
                TextColumn::make('birth_date')
                    ->label('Né(e) le')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('family.name')
                    ->label('Famille')
                    ->hiddenOn(StudentsRelationManager::class),
                TextColumn::make('mena_number')
                    ->label('Matricule MENA')
                    ->searchable()
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('gender')
                    ->label('Sexe')
                    ->options(Gender::class),
            ])
            ->headerActions([
                CreateAction::make()
                    ->visible(fn ($livewire): bool => $livewire instanceof StudentsRelationManager),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
