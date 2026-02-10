<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

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
        }

        return $next($request);
    }
}
