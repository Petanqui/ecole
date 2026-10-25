<?php

namespace App\Filament\Gestion\Concerns;

use App\Models\User;

/**
 * Hides a /gestion resource from teachers: only administrators and staff can open it.
 */
trait RestrictedToStaffMembers
{
    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isStaffMember();
    }
}
