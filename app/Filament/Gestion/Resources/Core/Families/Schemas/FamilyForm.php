<?php

namespace App\Filament\Gestion\Resources\Core\Families\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class FamilyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nom de la famille')
                    ->placeholder('Famille Koné')
                    ->required()
                    ->maxLength(255),
                TextInput::make('address')
                    ->label('Adresse / quartier')
                    ->maxLength(255),
                Textarea::make('notes')
                    ->label('Notes')
                    ->columnSpanFull(),
            ]);
    }
}
