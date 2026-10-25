<?php

namespace App\Filament\Gestion\Resources\Core\Families\RelationManagers;

use App\Enums\Core\GuardianRelationship;
use App\Support\PhoneNumber;
use Closure;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GuardiansRelationManager extends RelationManager
{
    protected static string $relationship = 'guardians';

    protected static ?string $title = 'Parents et tuteurs';

    protected static ?string $modelLabel = 'parent';

    protected static ?string $pluralModelLabel = 'parents';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('relationship')
                    ->label('Lien avec l\'élève')
                    ->options(GuardianRelationship::class)
                    ->required(),
                TextInput::make('phone')
                    ->label('Téléphone')
                    ->tel()
                    ->placeholder('07 00 00 00 00')
                    ->required()
                    ->maxLength(20)
                    ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                        if (PhoneNumber::normalize($value) === null) {
                            $fail('Numéro de téléphone invalide.');
                        }
                    }),
                TextInput::make('last_name')
                    ->label('Nom')
                    ->required()
                    ->maxLength(255),
                TextInput::make('first_name')
                    ->label('Prénoms')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('E-mail')
                    ->email()
                    ->maxLength(255),
                TextInput::make('profession')
                    ->label('Profession')
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('full_name')
            ->columns([
                TextColumn::make('relationship')
                    ->label('Lien'),
                TextColumn::make('last_name')
                    ->label('Nom'),
                TextColumn::make('first_name')
                    ->label('Prénoms'),
                TextColumn::make('phone')
                    ->label('Téléphone')
                    ->copyable(),
                TextColumn::make('profession')
                    ->label('Profession')
                    ->placeholder('—'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
