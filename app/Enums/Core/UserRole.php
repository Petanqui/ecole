<?php

namespace App\Enums\Core;

use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasLabel
{
    case Admin = 'admin';
    case Staff = 'staff';
    case Guardian = 'guardian';

    public function getLabel(): string
    {
        return match ($this) {
            self::Admin => 'Administrateur',
            self::Staff => 'Personnel',
            self::Guardian => 'Parent',
        };
    }

    /**
     * The Filament panel this role may sign in to.
     */
    public function panelId(): string
    {
        return match ($this) {
            self::Admin, self::Staff => 'gestion',
            self::Guardian => 'portail',
        };
    }
}
