<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;

Route::get('/', function () {
    return view('welcome');
});

// Mailtrap test route
Route::get('/mail-test', function () {
    try {
        \Illuminate\Support\Facades\Mail::raw('Mailtrap OTP test working ✅', function ($message) {
            $message->to('user@test.com')
                    ->subject('Mailtrap Test');
        });

        return response('Mail sent', 200);
    } catch (\Throwable $e) {
        logger()->error('Mail test failed: ' . $e->getMessage());

        return response()->json([
            'status' => 'error',
            'message' => 'Failed to send mail',
            'error' => $e->getMessage(),
            'suggestion' => 'Check MAIL_HOST/MAIL_PORT/MAIL_USERNAME/MAIL_PASSWORD and network connectivity. For local development you can set MAIL_MAILER=log to avoid SMTP.'
        ], 500);
    }
});

// Load API routes if present (fallback when RouteServiceProvider not registering routes/api.php)
if (file_exists(__DIR__ . '/api.php')) {
    require __DIR__ . '/api.php';
}
