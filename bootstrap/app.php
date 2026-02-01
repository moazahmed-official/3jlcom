<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Middleware registrations (keep empty for framework defaults)
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle validation exceptions with structured JSON format
        $exceptions->render(function (ValidationException $e, $request) {
            // Always return JSON for API routes
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'code' => 422,
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        // Handle authentication exceptions with structured JSON format
        $exceptions->render(function (AuthenticationException $e, $request) {
            // Always return JSON for API routes (never redirect to login)
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'code' => 401,
                    'message' => 'Unauthenticated',
                    'errors' => (object) [],
                ], 401);
            }
        });

        // Handle HTTP exceptions with structured JSON format
        $exceptions->render(function (HttpExceptionInterface $e, $request) {
            // Always return JSON for API routes
            if ($request->expectsJson() || $request->is('api/*')) {
                $status = $e->getStatusCode();
                $message = $e->getMessage() ?: 'Error';
                return response()->json([
                    'status' => 'error',
                    'code' => $status,
                    'message' => $message,
                    'errors' => (object) [],
                ], $status);
            }
        });
    })->create();
