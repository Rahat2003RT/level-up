<?php

use App\Http\Middleware\CheckSubscription;
use App\Http\Middleware\CheckUserStatus;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
        $middleware->redirectGuestsTo(fn () => response()->json([
            'status' => 'error',
            'message' => 'Unauthenticated. Please log in.'
        ], 401));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $firstErrorMessage = collect($e->errors())->flatten()->first();
                return response()->json([
                    'status'  => 'error',
                    'message' => $firstErrorMessage ?? $e->getMessage(),
                ], 422);
            }
            return null;
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                if ($e->getPrevious() instanceof ModelNotFoundException) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Resource not found.',
                    ], 404);
                }

                return response()->json([
                    'status'  => 'error',
                    'message' => 'Endpoint not found.',
                ], 404);
            }
            return null;
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Unauthenticated. Please log in.',
                ], 401);
            }
            return null;
        });

        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Access denied. You do not have permission for this action.',
                ], 403);
            }
            return null;
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $message = config('app.debug') ? $e->getMessage() : 'Server error. Please try again later.';
                $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
                return response()->json([
                    'status'  => 'error',
                    'message' => $message,
                ], $status);
            }
            return null;
        });
    })->create();
