<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\NormalAdsController;
use App\Http\Controllers\Api\V1\UniqueAdsController;
use App\Http\Controllers\Api\V1\CaishhaAdsController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\SellerVerificationController;

Route::prefix('v1')->group(function () {
    // Public authentication routes
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::put('auth/verify', [AuthController::class, 'verify']);
    Route::post('auth/password/reset-request', [AuthController::class, 'passwordResetRequest']);
    Route::put('auth/password/reset', [AuthController::class, 'passwordResetConfirm']);
    
    // Protected authentication routes
    Route::post('auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

    // Protected routes requiring authentication
    Route::middleware('auth:sanctum')->group(function () {
        // User management routes
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
        
        // User verification route (admin only)
        Route::post('/users/{user}/verify', [UserController::class, 'verify']);
        
        // Role management routes
        Route::apiResource('roles', RoleController::class);
        
        // User role assignment routes
        Route::post('/users/{user}/roles', [RoleController::class, 'assignRoles']);
        Route::get('/users/{user}/roles', [RoleController::class, 'getUserRoles']);
        
        // Seller verification management
        Route::post('/seller-verification', [SellerVerificationController::class, 'store']);
        Route::get('/seller-verification', [SellerVerificationController::class, 'show']);
        Route::get('/seller-verification/admin', [SellerVerificationController::class, 'index']);
        Route::put('/seller-verification/{verificationRequest}', [SellerVerificationController::class, 'update']);
    });

    Route::apiResource('normal-ads', NormalAdsController::class);
    Route::post('normal-ads/{ad}/actions/republish', [NormalAdsController::class, 'republish']);

    Route::apiResource('unique-ads', UniqueAdsController::class);
    Route::apiResource('caishha-ads', CaishhaAdsController::class);
});
