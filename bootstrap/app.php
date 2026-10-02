<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders()
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            $modulesPath = base_path('routes/modules');
            if (is_dir($modulesPath)) {
                $routeFiles = glob($modulesPath . '/*.php') ?: [];
                sort($routeFiles);
                foreach ($routeFiles as $routeFile) {
                    Route::middleware('web')->group($routeFile);
                }
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\InactivityTimeout::class,
            \App\Http\Middleware\CheckSystemMaintenanceMode::class,
        ]);

        $middleware->alias([
            'verify.option' => \App\Http\Middleware\VerifyOption::class,
            'verify.position' => \App\Http\Middleware\VerifyPermissionPosition::class,
            'security.headers' => \App\Http\Middleware\SecurityHeaders::class,
            'inactivity.timeout' => \App\Http\Middleware\InactivityTimeout::class,
            'check.maintenance' => \App\Http\Middleware\CheckSystemMaintenanceMode::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
