<?php

namespace App\Filament\Auth;

use App\Enums\Core\UserRole;
use App\Models\User;
use App\Support\PhoneNumber;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Validation\ValidationException;
use SensitiveParameter;

class Login extends BaseLogin
{
    /**
     * Set when the phone number and password are right but the account belongs to the other space.
     */
    protected ?User $userOfAnotherPanel = null;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getPhoneFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
            ]);
    }

    protected function getPhoneFormComponent(): Component
    {
        return TextInput::make('phone')
            ->label('Numéro de téléphone')
            ->tel()
            ->placeholder('07 00 00 00 00')
            ->required()
            ->autocomplete('tel')
            ->autofocus();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{phone: string, password: string}
     */
    protected function getCredentialsFromFormData(#[SensitiveParameter] array $data): array
    {
        return [
            'phone' => PhoneNumber::normalize($data['phone']) ?? $data['phone'],
            'password' => $data['password'],
        ];
    }

    protected function isUserAllowedToAccessPanel(Authenticatable $user): bool
    {
        $isAllowed = parent::isUserAllowedToAccessPanel($user);
        $this->userOfAnotherPanel = ! $isAllowed && $user instanceof User ? $user : null;

        return $isAllowed;
    }

    /**
     * With the right password, someone at the wrong door is told where to sign in instead.
     */
    protected function throwFailureValidationException(): never
    {
        $loginUrl = $this->userOfAnotherPanel === null
            ? null
            : Filament::getPanel($this->userOfAnotherPanel->role->panelId())->getLoginUrl();

        throw ValidationException::withMessages([
            'data.phone' => match (true) {
                $loginUrl === null => __('filament-panels::auth/pages/login.messages.failed'),
                $this->userOfAnotherPanel->role === UserRole::Guardian => "Ce compte est un compte parent : connectez-vous sur le portail, à l'adresse {$loginUrl}",
                default => "Ce compte est un compte de l'école : connectez-vous sur l'espace de gestion, à l'adresse {$loginUrl}",
            },
        ]);
    }
}
