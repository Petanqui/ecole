<?php

namespace App\Filament\Gestion\Concerns;

use App\Models\User;

/**
 * Hides a /gestion dashboard widget from teachers: only administrators and staff see it.
 */
trait VisibleToStaffMembers
{
    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isStaffMember();
    }
}
