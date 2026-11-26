<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Alleen beheerders (users.is_admin) mogen het admin-dashboard openen.
 *
 * Het dashboard draait op de server: we kijken in de database of de gebruiker
 * nog bestaat en nog beheerder is (de sessie zelf kan verouderd zijn).
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $userId = $request->user()?->getAuthIdentifier();
        $isAdmin = $userId !== null && User::query()->whereKey($userId)->value('is_admin');

        abort_unless((bool) $isAdmin, 403, 'Alleen beheerders hebben toegang tot het admin-dashboard.');

        return $next($request);
    }
}
