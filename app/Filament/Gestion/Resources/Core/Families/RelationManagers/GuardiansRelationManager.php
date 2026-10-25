<?php

namespace App\Filament\Gestion\Resources\Core\Families\RelationManagers;

use App\Enums\Core\GuardianRelationship;
use App\Exceptions\Core\PhoneNumberAlreadyUsedException;
use App\Models\Core\Guardian;
use App\Models\User;
use App\Rules\ValidPhoneNumber;
use App\Support\PhoneNumber;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
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
                    ->rule(new ValidPhoneNumber)
                    ->rule(fn (?Guardian $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                        $usedByAnotherAccount = $record?->user_id && User::query()
                            ->where('phone', PhoneNumber::normalize($value))
                            ->whereKeyNot($record->user_id)
                            ->exists();

                        if ($usedByAnotherAccount) {
                            $fail('Ce numéro est déjà utilisé par un autre compte.');
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
                IconColumn::make('user_id')
                    ->label('Accès portail')
                    ->boolean()
                    ->state(fn (Guardian $record): bool => $record->user_id !== null),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                Action::make('createPortalAccount')
                    ->label('Créer l\'accès portail')
                    ->icon(Heroicon::OutlinedKey)
                    ->requiresConfirmation()
                    ->modalDescription('Le parent se connectera sur /portail avec son numéro de téléphone. Un mot de passe sera généré : notez-le pour le lui transmettre.')
                    ->visible(fn (Guardian $record): bool => $record->user_id === null)
                    ->action(function (Guardian $record, Action $action): void {
                        try {
                            $password = $record->createPortalAccount();
                        } catch (PhoneNumberAlreadyUsedException $exception) {
                            Notification::make()->danger()->title($exception->getMessage())->send();
                            $action->halt();
                        }

                        static::notifyPassword('Accès portail créé', $record, $password);
                    }),
                Action::make('resetPortalPassword')
                    ->label('Nouveau mot de passe')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->requiresConfirmation()
                    ->modalDescription('L\'ancien mot de passe ne fonctionnera plus.')
                    ->visible(fn (Guardian $record): bool => $record->user_id !== null)
                    ->action(fn (Guardian $record) => static::notifyPassword('Nouveau mot de passe', $record, $record->resetPortalPassword())),
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription('Son accès au portail sera aussi supprimé.'),
            ]);
    }

    /**
     * Show the credentials to hand over to the parent. The notification stays until it is closed.
     */
    protected static function notifyPassword(string $title, Guardian $guardian, string $password): void
    {
        Notification::make()
            ->success()
            ->title($title)
            ->body("Identifiant : {$guardian->phone}\nMot de passe : {$password}")
            ->persistent()
            ->send();
    }
}
