<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Auth;

class InjectAuthFromCookie
{
    /**
     * If an admin token cookie is present and no Authorization header,
     * inject the Bearer header so Sanctum can authenticate.
     */
    public function handle(Request $request, Closure $next)
    {
        $cookieName = 'admin_token';

        if (!$request->header('Authorization') && $request->cookie($cookieName)) {
            $token = $request->cookie($cookieName);
            $request->headers->set('Authorization', 'Bearer ' . $token);
            // Attempt to resolve the personal access token and set the authenticated user
            try {
                $pat = PersonalAccessToken::findToken($token);
                if ($pat && $pat->tokenable) {
                    // Set the authenticated user for the current request lifecycle
                    Auth::setUser($pat->tokenable);
                    $request->setUserResolver(function () use ($pat) {
                        return $pat->tokenable;
                    });
                }
            } catch (\Throwable $e) {
                // Swallow resolution errors; header injection is still useful for Sanctum
            }
        }

        return $next($request);
    }
}
