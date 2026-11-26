<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Alleen beheerders (users.is_admin) mogen het admin-dashboard openen.
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->is_admin === true, 403, 'Alleen beheerders hebben toegang tot het admin-dashboard.');

        return $next($request);
    }
}
