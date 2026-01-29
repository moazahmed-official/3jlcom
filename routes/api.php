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
use App\Http\Controllers\Api\V1\BrandController;
use App\Http\Controllers\Api\V1\SliderController;
use App\Http\Controllers\MediaController;

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
        
        // Brand management routes (admin only for creation)
        Route::post('/brands', [BrandController::class, 'store']);
        Route::put('/brands/{brand}', [BrandController::class, 'update']);
        Route::delete('/brands/{brand}', [BrandController::class, 'destroy']);
        Route::post('/brands/{brand}/models', [BrandController::class, 'storeModel']);
        Route::put('/brands/{brand}/models/{model}', [BrandController::class, 'updateModel']);
        Route::delete('/brands/{brand}/models/{model}', [BrandController::class, 'destroyModel']);
        
        // Media management routes
        Route::get('/media', [MediaController::class, 'index']);
        Route::post('/media', [MediaController::class, 'store']);
        Route::get('/media/{media}', [MediaController::class, 'show']);
        Route::patch('/media/{media}', [MediaController::class, 'update']);
        Route::delete('/media/{media}', [MediaController::class, 'destroy']);
        
        // Normal Ads routes (authenticated)
        Route::get('normal-ads/my-ads', [NormalAdsController::class, 'myAds']); // User's ads with all statuses
        Route::get('normal-ads/admin', [NormalAdsController::class, 'adminIndex']); // Admin: all ads with all statuses
        Route::get('normal-ads/stats', [NormalAdsController::class, 'globalStats']); // Admin: global statistics
        Route::get('normal-ads/favorites', [NormalAdsController::class, 'favorites']); // Authenticated user's favorites
        Route::post('normal-ads/actions/bulk', [NormalAdsController::class, 'bulkAction']); // Admin: bulk operations
        Route::post('normal-ads', [NormalAdsController::class, 'store']);
        Route::put('normal-ads/{ad}', [NormalAdsController::class, 'update']);
        Route::delete('normal-ads/{ad}', [NormalAdsController::class, 'destroy']);
        
        // Ad lifecycle actions
        Route::post('normal-ads/{ad}/actions/republish', [NormalAdsController::class, 'republish']);
        Route::post('normal-ads/{ad}/actions/publish', [NormalAdsController::class, 'publish']);
        Route::post('normal-ads/{ad}/actions/unpublish', [NormalAdsController::class, 'unpublish']);
        Route::post('normal-ads/{ad}/actions/expire', [NormalAdsController::class, 'expire']);
        Route::post('normal-ads/{ad}/actions/archive', [NormalAdsController::class, 'archive']);
        Route::post('normal-ads/{ad}/actions/restore', [NormalAdsController::class, 'restore']);
        
        // Ad statistics and interactions
        Route::get('normal-ads/{ad}/stats', [NormalAdsController::class, 'stats']);
        Route::post('normal-ads/{ad}/favorite', [NormalAdsController::class, 'favorite']);
        Route::delete('normal-ads/{ad}/favorite', [NormalAdsController::class, 'unfavorite']);
        Route::post('normal-ads/{ad}/contact', [NormalAdsController::class, 'contactSeller']);
        
        // Convert normal ad to unique ad
        Route::post('normal-ads/{ad}/actions/convert-to-unique', [NormalAdsController::class, 'convertToUnique']);

        // Unique Ads routes (authenticated)
        Route::get('unique-ads/my-ads', [UniqueAdsController::class, 'myAds']); // User's ads with all statuses
        Route::get('unique-ads/admin', [UniqueAdsController::class, 'adminIndex']); // Admin: all ads with all statuses
        Route::get('unique-ads/stats', [UniqueAdsController::class, 'globalStats']); // Admin: global statistics
        Route::get('unique-ads/favorites', [UniqueAdsController::class, 'favorites']); // Authenticated user's favorites
        Route::post('unique-ads/actions/bulk', [UniqueAdsController::class, 'bulkAction']); // Admin: bulk operations
        Route::post('unique-ads', [UniqueAdsController::class, 'store']);
        Route::put('unique-ads/{ad}', [UniqueAdsController::class, 'update']);
        Route::delete('unique-ads/{ad}', [UniqueAdsController::class, 'destroy']);
        
        // Unique Ad lifecycle actions
        Route::post('unique-ads/{ad}/actions/republish', [UniqueAdsController::class, 'republish']);
        Route::post('unique-ads/{ad}/actions/publish', [UniqueAdsController::class, 'publish']);
        Route::post('unique-ads/{ad}/actions/unpublish', [UniqueAdsController::class, 'unpublish']);
        Route::post('unique-ads/{ad}/actions/expire', [UniqueAdsController::class, 'expire']);
        Route::post('unique-ads/{ad}/actions/archive', [UniqueAdsController::class, 'archive']);
        Route::post('unique-ads/{ad}/actions/restore', [UniqueAdsController::class, 'restore']);
        
        // Unique Ad feature/verification actions
        Route::post('unique-ads/{ad}/actions/feature', [UniqueAdsController::class, 'feature']);
        Route::delete('unique-ads/{ad}/actions/feature', [UniqueAdsController::class, 'unfeature']);
        Route::post('unique-ads/{ad}/actions/verify', [UniqueAdsController::class, 'requestVerification']);
        Route::post('unique-ads/{ad}/actions/approve-verification', [UniqueAdsController::class, 'approveVerification']);
        Route::post('unique-ads/{ad}/actions/reject-verification', [UniqueAdsController::class, 'rejectVerification']);
        Route::post('unique-ads/{ad}/actions/auto-republish', [UniqueAdsController::class, 'toggleAutoRepublish']);
        Route::post('unique-ads/{ad}/actions/convert-to-normal', [UniqueAdsController::class, 'convertToNormal']);
        
        // Unique Ad statistics and interactions
        Route::get('unique-ads/{ad}/stats', [UniqueAdsController::class, 'stats']);
        Route::post('unique-ads/{ad}/favorite', [UniqueAdsController::class, 'favorite']);
        Route::delete('unique-ads/{ad}/favorite', [UniqueAdsController::class, 'unfavorite']);
        Route::post('unique-ads/{ad}/contact', [UniqueAdsController::class, 'contactSeller']);

        // Slider routes (admin operations)
        Route::post('sliders', [SliderController::class, 'store']);
        Route::put('sliders/{slider}', [SliderController::class, 'update']);
        Route::delete('sliders/{slider}', [SliderController::class, 'destroy']);
        Route::post('sliders/{slider}/actions/activate', [SliderController::class, 'activate']);
        Route::post('sliders/{slider}/actions/deactivate', [SliderController::class, 'deactivate']);
    });

    // Public brand routes
    Route::get('/brands', [BrandController::class, 'index']);
    Route::get('/brands/{brand}/models', [BrandController::class, 'models']);

    // Public Normal Ads routes (no authentication required)
    Route::get('normal-ads', [NormalAdsController::class, 'index']);
    Route::get('normal-ads/{ad}', [NormalAdsController::class, 'show']);
    Route::get('users/{user}/normal-ads', [NormalAdsController::class, 'listByUser']); // Public ads by user

    // Public Unique Ads routes (no authentication required)
    Route::get('unique-ads', [UniqueAdsController::class, 'index']);
    Route::get('unique-ads/{ad}', [UniqueAdsController::class, 'show']);
    Route::get('users/{user}/unique-ads', [UniqueAdsController::class, 'listByUser']); // Public unique ads by user

    // Public Slider routes (no authentication required)
    Route::get('sliders', [SliderController::class, 'index']);
    Route::get('sliders/{slider}', [SliderController::class, 'show']);

    Route::apiResource('caishha-ads', CaishhaAdsController::class);
});
