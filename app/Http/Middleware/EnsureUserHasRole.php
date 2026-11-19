<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route::middleware('role:admin') or 'role:admin,reviewer'.
 * The role always comes from the database record, never from the request.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $allowed = array_map(fn (string $role) => Role::from($role), $roles);

        abort_unless($request->user()?->hasRole(...$allowed), 403, 'You do not have permission to open this page.');

        return $next($request);
    }
}
