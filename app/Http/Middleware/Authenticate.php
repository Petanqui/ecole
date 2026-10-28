<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate as FilamentAuthenticate;
use Symfony\Component\HttpFoundation\Response;

/**
 * Both spaces share one session: someone signed in to the other space is sent to its home page
 * instead of being refused.
 */
class Authenticate extends FilamentAuthenticate
{
    /**
     * @param  string  ...$guards
     */
    public function handle($request, Closure $next, ...$guards): Response
    {
        $user = Filament::auth()->user();

        if ($user instanceof User && ! $user->canAccessPanel(Filament::getCurrentOrDefaultPanel())) {
            return redirect(Filament::getPanel($user->role->panelId())->getUrl());
        }

        return parent::handle($request, $next, ...$guards);
    }
}
