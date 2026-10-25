<?php

namespace App\Filament\Gestion\Resources\Users;

use App\Enums\Core\UserRole;
use App\Filament\Gestion\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use App\Rules\ValidPhoneNumber;
use App\Support\PhoneNumber;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Staff accounts. Parent accounts are managed from each family's page.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static ?string $modelLabel = 'compte du personnel';

    protected static ?string $pluralModelLabel = 'comptes du personnel';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereIn('role', [UserRole::Admin, UserRole::Staff]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nom complet')
                    ->required()
                    ->maxLength(255),
                TextInput::make('phone')
                    ->label('Téléphone (identifiant de connexion)')
                    ->tel()
                    ->placeholder('07 00 00 00 00')
                    ->required()
                    ->maxLength(20)
                    ->rule(new ValidPhoneNumber)
                    ->mutateStateForValidationUsing(fn (?string $state): ?string => PhoneNumber::normalize($state) ?? $state)
                    ->unique(ignoreRecord: true)
                    ->validationMessages([
                        'unique' => 'Un compte utilise déjà ce numéro.',
                    ]),
                Select::make('role')
                    ->label('Rôle')
                    ->options([
                        UserRole::Admin->value => UserRole::Admin->getLabel(),
                        UserRole::Staff->value => UserRole::Staff->getLabel(),
                    ])
                    ->default(UserRole::Staff->value)
                    ->required()
                    ->disabled(fn (?User $record): bool => $record?->is(auth()->user()) ?? false)
                    ->helperText(fn (?User $record): ?string => $record?->is(auth()->user()) ? 'Vous ne pouvez pas modifier votre propre rôle.' : null),
                TextInput::make('email')
                    ->label('E-mail')
                    ->email()
                    ->maxLength(255),
                TextInput::make('password')
                    ->label('Mot de passe')
                    ->password()
                    ->revealable()
                    ->minLength(8)
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->helperText(fn (string $operation): ?string => $operation === 'edit' ? 'Laisser vide pour ne pas le changer.' : null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->label('Téléphone')
                    ->searchable(),
                TextColumn::make('role')
                    ->label('Rôle')
                    ->badge(),
                TextColumn::make('email')
                    ->label('E-mail')
                    ->placeholder('—'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}
