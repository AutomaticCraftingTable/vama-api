<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\HasRole;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . "/../routes/api.php",
        commands: __DIR__ . "/../routes/console.php",
        health: "/up",
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'checkRole' => HasRole::class,
            'canAccessContent' => \App\Http\Middleware\CanAccessContent::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
    })->create();
