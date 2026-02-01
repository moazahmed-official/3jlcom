<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RateLimitReviewsReports
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $type = 'review'): Response
    {
        if (!auth()->check()) {
            return $next($request);
        }

        $key = $this->resolveRequestSignature($request, $type);
        $maxAttempts = 10; // 10 attempts
        $decayMinutes = 60; // per hour

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            
            return response()->json([
                'status' => 'error',
                'code' => 429,
                'message' => 'Too many ' . $type . ' submissions. Please try again in ' . ceil($seconds / 60) . ' minutes.',
                'errors' => [
                    'rate_limit' => ['You have exceeded the rate limit for ' . $type . ' submissions.']
                ],
                'retry_after' => $seconds,
            ], 429)->header('Retry-After', $seconds)
                ->header('X-RateLimit-Limit', $maxAttempts)
                ->header('X-RateLimit-Remaining', 0);
        }

        RateLimiter::hit($key, $decayMinutes * 60);
        
        $response = $next($request);
        
        $remaining = $maxAttempts - RateLimiter::attempts($key);
        
        return $response->header('X-RateLimit-Limit', $maxAttempts)
                       ->header('X-RateLimit-Remaining', max(0, $remaining));
    }

    /**
     * Resolve the rate limiter signature for the request.
     */
    protected function resolveRequestSignature(Request $request, string $type): string
    {
        return $type . '|' . auth()->id() . '|' . $request->ip();
    }
}

