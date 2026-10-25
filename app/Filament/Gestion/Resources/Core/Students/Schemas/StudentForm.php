<?php

namespace App\Filament\Gestion\Resources\Core\Students\Schemas;

use App\Enums\Core\Gender;
use App\Models\Core\Student;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Livewire\Component;

class StudentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('family_id')
                    ->label('Famille')
                    ->relationship('family', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->hidden(fn (Component $livewire): bool => $livewire instanceof RelationManager),
                TextInput::make('last_name')
                    ->label('Nom')
                    ->required()
                    ->maxLength(255),
                TextInput::make('first_name')
                    ->label('Prénoms')
                    ->required()
                    ->maxLength(255),
                ToggleButtons::make('gender')
                    ->label('Sexe')
                    ->options(Gender::class)
                    ->inline()
                    ->required(),
                DatePicker::make('birth_date')
                    ->label('Date de naissance')
                    ->required()
                    ->beforeOrEqual('today'),
                TextInput::make('birth_place')
                    ->label('Lieu de naissance')
                    ->maxLength(255),
                TextInput::make('mena_number')
                    ->label('Matricule MENA')
                    ->helperText('Facultatif. Deux élèves ne peuvent pas avoir le même matricule.')
                    ->maxLength(20)
                    ->mutateStateForValidationUsing(fn (?string $state): ?string => Student::normalizeMenaNumber($state))
                    ->unique()
                    ->validationMessages([
                        'unique' => 'Ce matricule MENA est déjà attribué à un autre élève.',
                    ]),
            ]);
    }
}
