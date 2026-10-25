<?php

namespace App\Enums\Core;

use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasLabel
{
    case Admin = 'admin';
    case Staff = 'staff';
    case Teacher = 'teacher';
    case Guardian = 'guardian';

    public function getLabel(): string
    {
        return match ($this) {
            self::Admin => 'Administrateur',
            self::Staff => 'Personnel',
            self::Teacher => 'Enseignant',
            self::Guardian => 'Parent',
        };
    }

    /**
     * Administrators and staff run the school; teachers only reach their own classes and subjects.
     */
    public function isStaffMember(): bool
    {
        return in_array($this, [self::Admin, self::Staff], true);
    }

    /**
     * The Filament panel this role may sign in to.
     */
    public function panelId(): string
    {
        return match ($this) {
            self::Admin, self::Staff, self::Teacher => 'gestion',
            self::Guardian => 'portail',
        };
    }
}
