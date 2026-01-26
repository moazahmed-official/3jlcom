<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Load API routes if present (fallback when RouteServiceProvider not registering routes/api.php)
if (file_exists(__DIR__ . '/api.php')) {
    require __DIR__ . '/api.php';
}
