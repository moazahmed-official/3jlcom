<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | This file defines the CORS settings for the API. When credentials are
    | enabled, do NOT use a wildcard origin. In production we return the exact
    | admin origin and enable credentials. In local development we allow the
    | local admin origin (e.g. http://localhost:5173) for the dev admin app.
    |
    */

    // Cover all API routes and Sanctum CSRF
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    // Allowed origins — must not be '*' when supports_credentials is true
    'allowed_origins' => (function () {
        $env = env('APP_ENV', 'production');
        $adminOrigin = env('ADMIN_ORIGIN');

        if ($env === 'local' || $env === 'development') {
            // Local dev: default to Vite dev server
            return [$adminOrigin ?: 'http://localhost:5173'];
        }

        // Production: require explicit ADMIN_ORIGIN or default to admin subdomain
        if ($adminOrigin) {
            return [$adminOrigin];
        }

        return ['https://admin.example.com'];
    })(),

    // Allowed methods — include OPTIONS for preflight, use ['*'] for all
    'allowed_methods' => ['*'],

    // Allowed headers — allow all
    'allowed_headers' => ['*'],

    // Expose headers to the browser
    'exposed_headers' => [],

    // Whether or not the response to the request can be exposed when the
    // credentials flag is true. When used with credentials, allowed_origins
    // MUST NOT be ['*'].
    'supports_credentials' => true,

    // Preflight cache duration
    'max_age' => 0,
];
