<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;
use Throwable;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class Handler extends ExceptionHandler
{
    protected $levels = [
        //
    ];

    protected $dontReport = [
        //
    ];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    protected function invalidJson($request, ValidationException $exception)
    {
        return response()->json([
            'status' => 'error',
            'code' => 422,
            'message' => 'Validation failed',
            'errors' => $exception->errors(),
        ], 422);
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        // Always return JSON for API routes (never redirect to login)
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'status' => 'error',
                'code' => 401,
                'message' => 'Unauthenticated',
                'errors' => (object) [],
            ], 401);
        }
        
        // For web routes, redirect to login (if login route exists)
        return redirect()->guest(route('login'));
    }

    public function render($request, Throwable $e)
    {
        // Always return JSON for API routes
        if ($request->wantsJson() || $request->expectsJson() || $request->is('api/*')) {
            if ($e instanceof ValidationException) {
                return $this->invalidJson($request, $e);
            }

            if ($e instanceof AuthenticationException) {
                return $this->unauthenticated($request, $e);
            }

            if ($e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();
                $message = $e->getMessage() ?: 'Error';
                return response()->json([
                    'status' => 'error',
                    'code' => $status,
                    'message' => $message,
                    'errors' => (object) [],
                ], $status);
            }

            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => config('app.debug') ? $e->getMessage() : 'Server Error',
                'errors' => (object) [],
            ], 500);
        }

        return parent::render($request, $e);
    }
}
