<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Add CORS middleware for future API usage
        $middleware->web(append: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);

        // Define middleware aliases for future use
        $middleware->alias([
            'admin' => \Illuminate\Auth\Middleware\Authenticate::class, // For future admin authentication
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
