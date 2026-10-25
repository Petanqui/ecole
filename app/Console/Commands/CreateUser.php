<?php

namespace App\Console\Commands;

use App\Enums\Core\UserRole;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[Signature('app:create-user {--name= : Full name} {--phone= : Phone number, e.g. 07 00 00 00 00} {--email= : Optional e-mail address} {--password= : Password (prompted when omitted)} {--role=admin : admin or staff}')]
#[Description('Create a staff account that logs in to /gestion with a phone number and password')]
class CreateUser extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $role = UserRole::tryFrom((string) $this->option('role'));

        if (! in_array($role, [UserRole::Admin, UserRole::Staff], true)) {
            $this->components->error('Rôle invalide : utilisez admin ou staff. Les comptes parents se créent depuis la fiche famille.');

            return self::FAILURE;
        }

        $name = $this->option('name') ?: text(label: 'Nom complet', required: true);

        $phone = PhoneNumber::normalize($this->option('phone') ?: text(
            label: 'Numéro de téléphone',
            placeholder: '07 00 00 00 00',
            required: true,
        ));

        if ($phone === null) {
            $this->components->error('Numéro de téléphone invalide.');

            return self::FAILURE;
        }

        if (User::where('phone', $phone)->exists()) {
            $this->components->error("Un utilisateur avec le numéro {$phone} existe déjà.");

            return self::FAILURE;
        }

        $password = $this->option('password') ?: password(label: 'Mot de passe', required: true);

        $user = User::create([
            'name' => $name,
            'phone' => $phone,
            'email' => $this->option('email') ?: null,
            'role' => $role,
            'password' => $password,
        ]);

        $this->components->info("{$role->getLabel()} « {$user->name} » créé avec le numéro {$user->phone}.");

        return self::SUCCESS;
    }
}
