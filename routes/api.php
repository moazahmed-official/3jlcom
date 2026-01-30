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

        // Caishha Settings routes (admin only)
        Route::get('caishha-settings', [\App\Http\Controllers\Api\V1\CaishhaSettingsController::class, 'index']);
        Route::put('caishha-settings', [\App\Http\Controllers\Api\V1\CaishhaSettingsController::class, 'update']);
        Route::put('caishha-settings/{key}', [\App\Http\Controllers\Api\V1\CaishhaSettingsController::class, 'updateSingle']);
        Route::get('caishha-settings/presets', [\App\Http\Controllers\Api\V1\CaishhaSettingsController::class, 'presets']);

        // Caishha Ads routes (authenticated)
        Route::get('caishha-ads/my-ads', [CaishhaAdsController::class, 'myAds']); // User's ads with all statuses
        Route::get('caishha-ads/admin', [CaishhaAdsController::class, 'adminIndex']); // Admin: all ads with all statuses
        Route::get('caishha-ads/stats', [CaishhaAdsController::class, 'globalStats']); // Admin: global statistics
        Route::post('caishha-ads/actions/bulk', [CaishhaAdsController::class, 'bulkAction']); // Admin: bulk operations
        Route::post('caishha-ads', [CaishhaAdsController::class, 'store']);
        Route::put('caishha-ads/{ad}', [CaishhaAdsController::class, 'update']);
        Route::delete('caishha-ads/{ad}', [CaishhaAdsController::class, 'destroy']);
        
        // Caishha Ad lifecycle actions
        Route::post('caishha-ads/{ad}/actions/publish', [CaishhaAdsController::class, 'publish']);
        Route::post('caishha-ads/{ad}/actions/unpublish', [CaishhaAdsController::class, 'unpublish']);
        Route::post('caishha-ads/{ad}/actions/expire', [CaishhaAdsController::class, 'expire']);
        Route::post('caishha-ads/{ad}/actions/archive', [CaishhaAdsController::class, 'archive']);
        Route::post('caishha-ads/{ad}/actions/restore', [CaishhaAdsController::class, 'restore']);
        
        // Caishha Offers management
        Route::post('caishha-ads/{ad}/offers', [CaishhaAdsController::class, 'submitOffer']); // Submit offer on ad
        Route::get('caishha-ads/{ad}/offers', [CaishhaAdsController::class, 'listOffers']); // List offers (owner/admin)
        Route::post('caishha-ads/{ad}/offers/{offer}/accept', [CaishhaAdsController::class, 'acceptOffer']); // Accept offer
        Route::post('caishha-ads/{ad}/offers/{offer}/reject', [CaishhaAdsController::class, 'rejectOffer']); // Reject offer
        Route::get('caishha-offers/my-offers', [CaishhaAdsController::class, 'myOffers']); // User's submitted offers
        Route::get('caishha-offers/{offer}', [CaishhaAdsController::class, 'showOffer']); // Get specific offer details
        Route::put('caishha-offers/{offer}', [CaishhaAdsController::class, 'updateOffer']); // Update offer
        Route::delete('caishha-offers/{offer}', [CaishhaAdsController::class, 'deleteOffer']); // Delete/withdraw offer
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

    // Public Caishha Ads routes (no authentication required)
    Route::get('caishha-ads', [CaishhaAdsController::class, 'index']);
    Route::get('caishha-ads/{ad}', [CaishhaAdsController::class, 'show']);
});
