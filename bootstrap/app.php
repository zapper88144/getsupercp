<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/security.php'));
            Route::middleware('web')
                ->group(base_path('routes/ai.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\RateLimiting::class,
        ]);

        $middleware->alias([
            'VerifyPhpMyAdminAccess' => \App\Http\Middleware\VerifyPhpMyAdminAccess::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'two-factor' => \App\Http\Middleware\TwoFactorAuthMiddleware::class,
            'brute-force' => \App\Http\Middleware\BruteForceMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
