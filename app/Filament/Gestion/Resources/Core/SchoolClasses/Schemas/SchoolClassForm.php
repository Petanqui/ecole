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
                    ->required(),
                TextInput::make('name')
                    ->label('Nom de la classe')
                    ->placeholder('CP1 A, 6e 2, Tle D…')
                    ->required()
                    ->maxLength(50)
                    ->unique(modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule->where('school_year_id', $get('school_year_id')))
                    ->validationMessages([
                        'unique' => 'Une classe porte déjà ce nom pour cette année scolaire.',
                    ]),
                TextInput::make('capacity')
                    ->label('Effectif maximum')
                    ->integer()
                    ->minValue(1)
                    ->maxValue(200),
            ]);
    }
}
