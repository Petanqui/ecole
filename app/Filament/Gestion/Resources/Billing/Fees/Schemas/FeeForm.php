<?php

namespace App\Filament\Gestion\Resources\Billing\Fees\Schemas;

use App\Enums\Core\GradeLevel;
use App\Models\Core\SchoolYear;
use App\Services\Billing\StudentAccount;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class FeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        Select::make('school_year_id')
                            ->label('Année scolaire')
                            ->relationship('schoolYear', 'name')
                            ->default(fn (): ?int => SchoolYear::current()->value('id'))
                            ->required(),
                        TextInput::make('name')
                            ->label('Libellé')
                            ->placeholder('Frais de scolarité, Inscription, Cantine…')
                            ->required()
                            ->maxLength(100),
                        Select::make('level')
                            ->label('Niveau')
                            ->options(GradeLevel::groupedOptions())
                            ->placeholder('Tous les niveaux')
                            ->helperText('Laissez vide pour un frais identique à tous les niveaux (ex. inscription).'),
                        Toggle::make('is_optional')
                            ->label('Frais optionnel')
                            ->helperText('Cantine, transport… : seuls les élèves inscrits à ce frais le doivent.')
                            ->inline(false),
                    ]),
                Section::make('Échéancier')
                    ->description(fn (Get $get): string => 'Total : '.StudentAccount::formatAmount(
                        (int) collect($get('installments'))->sum(fn (array $installment): int => (int) ($installment['amount'] ?? 0)),
                    ))
                    ->schema([
                        Repeater::make('installments')
                            ->hiddenLabel()
                            ->relationship()
                            ->columns(2)
                            ->minItems(1)
                            ->defaultItems(1)
                            ->addActionLabel('Ajouter une tranche')
                            ->live()
                            ->schema([
                                DatePicker::make('due_on')
                                    ->label('À payer avant le')
                                    ->required(),
                                TextInput::make('amount')
                                    ->label('Montant')
                                    ->integer()
                                    ->minValue(1)
                                    ->suffix('FCFA')
                                    ->required()
                                    ->live(onBlur: true),
                            ]),
                    ]),
            ]);
    }
}
