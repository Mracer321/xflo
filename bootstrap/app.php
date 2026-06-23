<?php

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Surface infrastructure failures at the highest log level so they stand
        // out in production monitoring. HTTP-shaped exceptions (validation, 403,
        // 404, 419) are normal flow and keep their default reporting.
        $exceptions->report(function (QueryException $e): void {
            Log::critical('Database query failure', [
                'message' => $e->getMessage(),
                'sql' => $e->getSql(),
            ]);
        });

        // Anything that escapes to a 500 (and isn't already handled above) is a
        // genuine unexpected failure worth a critical-level record.
        $exceptions->report(function (\Throwable $e): void {
            if (! $e instanceof QueryException
                && ! $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                Log::critical('Unhandled application error', [
                    'exception' => $e::class,
                    'message' => $e->getMessage(),
                ]);
            }
        });
    })->create();
