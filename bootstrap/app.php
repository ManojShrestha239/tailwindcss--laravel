<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register the middleware alias for route usage
        $middleware->alias([
            'CheckSubscription' => \App\Http\Middleware\CheckSubscriptionMiddleware::class,
        ]);

        // Add middleware globally to all routes
        $middleware->append(\App\Http\Middleware\CheckSubscriptionMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
