<?php

namespace App\Filament\Gestion\Resources\Core\Families\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FamiliesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount(['guardians', 'students']))
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Famille')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('guardians.phone')
                    ->label('Téléphones')
                    ->listWithLineBreaks()
                    ->searchable(),
                TextColumn::make('guardians_count')
                    ->label('Parents'),
                TextColumn::make('students_count')
                    ->label('Enfants'),
                TextColumn::make('address')
                    ->label('Adresse')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
