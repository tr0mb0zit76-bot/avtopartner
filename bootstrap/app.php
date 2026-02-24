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
    ->withMiddleware(function (Middleware $middleware) {
        // Глобальные middleware для всех запросов
        $middleware->append(\App\Http\Middleware\DetectSite::class);
        $middleware->append(\App\Http\Middleware\LoadUserTheme::class);
    
        // Или можно добавить в группу 'web'
        $middleware->web(append: [
            \App\Http\Middleware\DetectSite::class,
            \App\Http\Middleware\LoadUserTheme::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
