<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
            'active' => \App\Http\Middleware\EnsureAccountIsActive::class,
            'verified.account' => \App\Http\Middleware\EnsureAccountIsVerified::class,
        ]);

        // Trust X-Forwarded-* headers only from a proxy on this same machine (for example a Cloudflare
        // Tunnel for demos), so links use https there. Headers sent by anyone else are still ignored,
        // so visitors cannot fake their IP address to get around rate limits.
        $middleware->trustProxies(at: ['127.0.0.1', '::1']);

        // Security headers on every web page
        $middleware->web(append: [\App\Http\Middleware\SecurityHeaders::class]);

        // Where "auth" sends guests, and where "guest" sends signed-in users
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
