<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends users who have not confirmed their email or phone yet to the verification page.
 */
class EnsureAccountIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->isVerified()) {
            return $request->expectsJson()
                ? abort(403, 'Your account is not verified.')
                : redirect()->route('verification.notice');
        }

        return $next($request);
    }
}
