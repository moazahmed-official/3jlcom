<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sanctum Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains a minimal configuration for Laravel Sanctum used
    | by the application. The `expiration` value controls personal access
    | token lifetime in minutes. Default is 8 days (11520 minutes).
    |
    */

    // Token expiration in minutes. Null = never expires.
    'expiration' => env('SANCTUM_EXPIRATION', 11520),

    // By default, do not modify stateful domains here — keep framework defaults.
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', '')),

    // Middleware used by Sanctum routes (defaults kept minimal)
    'middleware' => [
        'verify_csrf_token' => App\Http\Middleware\VerifyCsrfToken::class,
        'encrypt_cookies' => App\Http\Middleware\EncryptCookies::class,
    ],
];
