<?php

use App\Http\Middleware\CheckSubscription;
use App\Http\Middleware\CheckUserStatus;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'subscription' => CheckSubscription::class,
            'check.status' => CheckUserStatus::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
        $exceptions->respond(function ($request, Throwable $e) {
            if ($e instanceof ValidationException && $request->expectsJson()) {
                $firstErrorMessage = collect($e->errors())->flatten()->first();

                return response()->json([
                    'status'  => 'error',
                    'message' => $firstErrorMessage ?? $e->getMessage(),
                ], 422);
            }
            return null;
        });
    })->create();
