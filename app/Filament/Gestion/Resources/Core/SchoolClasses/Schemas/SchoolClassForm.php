<?php

namespace App\Filament\Gestion\Resources\Core\SchoolClasses\Schemas;

use App\Enums\Core\GradeLevel;
use App\Models\Core\SchoolYear;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class SchoolClassForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('school_year_id')
                    ->label('Année scolaire')
                    ->relationship('schoolYear', 'name')
                    ->default(fn (): ?int => SchoolYear::current()->value('id'))
                    ->required(),
                Select::make('level')
                    ->label('Niveau')
                    ->options(GradeLevel::groupedOptions())
                    ->required()
                    ->live(),
                TextInput::make('section')
                    ->label('Section')
                    ->placeholder('1, 2, A, C1…')
                    ->helperText(fn (Get $get): string => ($level = GradeLevel::tryFrom((string) $get('level'))) && filled($get('section'))
                        ? 'Nom de la classe : '.$level->className($get('section'))
                        : 'Le nom est composé du niveau et de la section, ex. 6e + 1 = 6e1.')
                    ->required()
                    ->maxLength(20)
                    ->live(onBlur: true)
                    ->unique(modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule
                        ->where('school_year_id', $get('school_year_id'))
                        ->where('level', $get('level')))
                    ->validationMessages([
                        'unique' => 'Cette classe existe déjà pour cette année scolaire.',
                    ]),
                TextInput::make('capacity')
                    ->label('Effectif maximum')
                    ->integer()
                    ->minValue(1)
                    ->maxValue(200),
            ]);
    }
}
