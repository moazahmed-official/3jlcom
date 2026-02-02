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
use App\Http\Controllers\Api\V1\AuctionAdsController;
use App\Http\Controllers\FindItAdsController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\PackageController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\FavoriteController;
use App\Http\Controllers\Api\V1\SavedSearchController;
use App\Http\Controllers\Api\V1\BlogController;
use App\Http\Controllers\Api\V1\SpecificationController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\SellerStatsController;
use App\Http\Controllers\Api\V1\AdminStatsController;

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

        // =====================
        // AUCTION ADS ROUTES
        // =====================
        
        // Auction Ads routes (authenticated)
        Route::get('auction-ads/my-ads', [AuctionAdsController::class, 'myAds']); // User's auctions with all statuses
        Route::get('auction-ads/admin', [AuctionAdsController::class, 'adminIndex']); // Admin: all auctions with all statuses
        Route::get('auction-ads/stats', [AuctionAdsController::class, 'globalStats']); // Admin: global statistics
        Route::post('auction-ads', [AuctionAdsController::class, 'store']); // Create auction
        Route::put('auction-ads/{ad}', [AuctionAdsController::class, 'update']); // Update auction
        Route::delete('auction-ads/{ad}', [AuctionAdsController::class, 'destroy']); // Delete auction
        
        // Auction lifecycle actions
        Route::post('auction-ads/{ad}/actions/publish', [AuctionAdsController::class, 'publish']); // Publish auction
        Route::post('auction-ads/{ad}/actions/close', [AuctionAdsController::class, 'closeAuction']); // Close auction
        Route::post('auction-ads/{ad}/actions/cancel', [AuctionAdsController::class, 'cancelAuction']); // Cancel auction
        
        // Bid management routes
        Route::post('auction-ads/{ad}/bids', [AuctionAdsController::class, 'placeBid']); // Place a bid
        Route::get('auction-ads/{ad}/bids', [AuctionAdsController::class, 'listBids']); // List bids (owner/admin/moderator)
        Route::get('auction-ads/{ad}/bids/{bid}', [AuctionAdsController::class, 'showBid']); // Get bid details
        Route::delete('auction-ads/{ad}/bids/{bid}', [AuctionAdsController::class, 'withdrawBid']); // Withdraw own bid
        Route::get('auction-bids/my-bids', [AuctionAdsController::class, 'myBids']); // User's bids across all auctions

        // =====================
        // FINDIT ADS ROUTES (Private search requests)
        // =====================
        
        // FindIt request management (authenticated)
        Route::get('findit-ads/my-requests', [FindItAdsController::class, 'myRequests']); // User's FindIt requests
        Route::get('findit-ads/admin', [FindItAdsController::class, 'adminIndex']); // Admin: all requests
        Route::get('findit-ads/stats', [FindItAdsController::class, 'stats']); // User's FindIt statistics
        Route::post('findit-ads/actions/bulk', [FindItAdsController::class, 'bulkAction']); // Admin: bulk operations
        Route::post('findit-ads', [FindItAdsController::class, 'store']); // Create FindIt request
        Route::get('findit-ads/{findit_ad}', [FindItAdsController::class, 'show']); // Get request details
        Route::put('findit-ads/{findit_ad}', [FindItAdsController::class, 'update']); // Update request
        Route::delete('findit-ads/{findit_ad}', [FindItAdsController::class, 'destroy']); // Delete request
        
        // FindIt lifecycle actions
        Route::post('findit-ads/{findit_ad}/activate', [FindItAdsController::class, 'activate']); // Activate draft
        Route::post('findit-ads/{findit_ad}/close', [FindItAdsController::class, 'close']); // Close request
        Route::post('findit-ads/{findit_ad}/extend', [FindItAdsController::class, 'extend']); // Extend expiration
        Route::post('findit-ads/{findit_ad}/reactivate', [FindItAdsController::class, 'reactivate']); // Reactivate closed/expired
        
        // FindIt matches management
        Route::get('findit-ads/{findit_ad}/matches', [FindItAdsController::class, 'listMatches']); // List matching ads
        Route::get('findit-ads/{findit_ad}/matches/{match}', [FindItAdsController::class, 'showMatch']); // Get match details
        Route::post('findit-ads/{findit_ad}/matches/{match}/dismiss', [FindItAdsController::class, 'dismissMatch']); // Dismiss match
        Route::post('findit-ads/{findit_ad}/matches/{match}/restore', [FindItAdsController::class, 'restoreMatch']); // Restore dismissed match
        Route::post('findit-ads/{findit_ad}/refresh-matches', [FindItAdsController::class, 'refreshMatches']); // Refresh matches
        Route::get('findit-ads/{findit_ad}/similar', [FindItAdsController::class, 'similarAds']); // Get similar ads for notifications
        
        // =====================
        // REVIEWS ROUTES (Protected)
        // =====================
        
        // Review management (authenticated users)
        Route::post('reviews', [ReviewController::class, 'store'])->middleware('throttle:review'); // Create review with rate limit
        Route::get('reviews/my-reviews', [ReviewController::class, 'myReviews']); // User's own reviews
        Route::put('reviews/{review}', [ReviewController::class, 'update']); // Update review (owner or admin)
        Route::delete('reviews/{review}', [ReviewController::class, 'destroy']); // Delete review (owner or admin)
        
        // =====================
        // REPORTS ROUTES (Protected)
        // =====================
        
        // Report management (authenticated users)
        Route::post('reports', [ReportController::class, 'store'])->middleware('throttle:report'); // Create report with rate limit
        Route::get('reports/my-reports', [ReportController::class, 'myReports']); // User's own reports
        Route::get('reports/{report}', [ReportController::class, 'show']); // View report (owner/assigned/admin)
        
        // Report admin routes (admin/moderator only)
        Route::get('reports/admin/index', [ReportController::class, 'adminIndex']); // Admin: all reports with filters
        Route::post('reports/{report}/assign', [ReportController::class, 'assign']); // Admin: assign to moderator
        Route::put('reports/{report}/status', [ReportController::class, 'updateStatus']); // Admin/Moderator: update status
        Route::post('reports/{report}/actions/resolve', [ReportController::class, 'resolve']); // Admin/Moderator: mark resolved
        Route::post('reports/{report}/actions/close', [ReportController::class, 'close']); // Admin/Moderator: close report
        Route::delete('reports/{report}', [ReportController::class, 'destroy']); // Admin only: delete report
        
        // =====================
        // PACKAGES ROUTES
        // =====================
        
        // Package management (admin only for CUD)
        Route::get('packages/stats', [PackageController::class, 'stats']); // Admin: package statistics
        Route::post('packages', [PackageController::class, 'store']); // Admin: create package
        Route::put('packages/{package}', [PackageController::class, 'update']); // Admin: update package
        Route::delete('packages/{package}', [PackageController::class, 'destroy']); // Admin: delete package
        Route::post('packages/{package}/assign', [PackageController::class, 'assign']); // Admin: assign to user
        
        // Package features management (Step 2 - admin only for CUD)
        Route::get('packages/{package}/features', [PackageController::class, 'getFeatures']); // Get package features
        Route::post('packages/{package}/features', [PackageController::class, 'storeFeatures']); // Create features (admin)
        Route::put('packages/{package}/features', [PackageController::class, 'updateFeatures']); // Update features (admin)
        Route::delete('packages/{package}/features', [PackageController::class, 'destroyFeatures']); // Delete features (admin)
        
        // User packages management
        Route::get('packages/my-packages', [PackageController::class, 'myPackages']); // Current user's packages
        Route::get('packages/my-features', [PackageController::class, 'myFeatures']); // Current user's package features
        Route::post('packages/check-capability', [PackageController::class, 'checkCapability']); // Check if user can do action
        Route::get('users/{user}/packages', [PackageController::class, 'userPackages']); // User's packages (self or admin)
        Route::put('user-packages/{userPackage}', [PackageController::class, 'updateUserPackage']); // Admin: update subscription
        Route::delete('user-packages/{userPackage}', [PackageController::class, 'destroyUserPackage']); // Admin: remove subscription
        
        // =====================
        // NOTIFICATIONS ROUTES
        // =====================
        
        // Notification management (authenticated users)
        Route::get('notifications', [NotificationController::class, 'index']); // List user's notifications
        Route::get('notifications/{id}', [NotificationController::class, 'show']); // Get specific notification
        Route::patch('notifications/{id}/read', [NotificationController::class, 'markAsRead']); // Mark as read
        Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead']); // Mark all as read
        Route::delete('notifications/{id}', [NotificationController::class, 'destroy']); // Delete notification
        
        // Admin notification sending
        Route::post('notifications/send', [NotificationController::class, 'send']); // Admin: send notification
        Route::post('findit-ads/{findit_ad}/refresh-matches', [FindItAdsController::class, 'refreshMatches']); // Refresh matches
        
        // =====================
        // FAVORITES ROUTES
        // =====================
        
        // Favorite management (authenticated users)
        Route::get('favorites', [FavoriteController::class, 'index']); // List user's favorites
        Route::get('favorites/count', [FavoriteController::class, 'count']); // Get favorites count
        Route::get('favorites/check/{ad}', [FavoriteController::class, 'check']); // Check if ad is favorited
        Route::post('favorites/{ad}', [FavoriteController::class, 'store']); // Add ad to favorites
        Route::post('favorites/toggle/{ad}', [FavoriteController::class, 'toggle']); // Toggle favorite status
        Route::delete('favorites/{favorite}', [FavoriteController::class, 'destroy']); // Remove favorite by ID
        Route::delete('favorites/ad/{ad}', [FavoriteController::class, 'destroyByAd']); // Remove favorite by ad ID
        
        // SAVED SEARCHES ROUTES
        // =====================
        
        // Saved search management (authenticated users)
        Route::get('saved-searches', [SavedSearchController::class, 'index']); // List user's saved searches
        Route::post('saved-searches', [SavedSearchController::class, 'store']); // Create new saved search
        Route::get('saved-searches/{savedSearch}', [SavedSearchController::class, 'show']); // Show specific saved search
        Route::put('saved-searches/{savedSearch}', [SavedSearchController::class, 'update']); // Update saved search
        Route::delete('saved-searches/{savedSearch}', [SavedSearchController::class, 'destroy']); // Delete saved search
        
        // BLOG ROUTES (Admin)
        // =====================
        
        // Blog management (admin only)
        Route::get('admin/blogs', [BlogController::class, 'adminIndex']); // List all blogs (admin)
        Route::get('admin/blogs/{blog}', [BlogController::class, 'adminShow']); // Show any blog (admin)
        Route::post('admin/blogs', [BlogController::class, 'store']); // Create blog (admin)
        Route::put('admin/blogs/{blog}', [BlogController::class, 'update']); // Update blog (admin)
        Route::delete('admin/blogs/{blog}', [BlogController::class, 'destroy']); // Delete blog (admin)
        
        // SPECIFICATION ROUTES (Admin)
        // ============================
        
        // Specification management (admin only)
        Route::get('admin/specifications', [SpecificationController::class, 'index']); // List specifications (admin)
        Route::get('admin/specifications/{specification}', [SpecificationController::class, 'show']); // Show specification (admin)
        Route::post('admin/specifications', [SpecificationController::class, 'store']); // Create specification (admin)
        Route::put('admin/specifications/{specification}', [SpecificationController::class, 'update']); // Update specification (admin)
        Route::delete('admin/specifications/{specification}', [SpecificationController::class, 'destroy']); // Delete specification (admin)
        
        // CATEGORY ROUTES (Admin)
        // =======================
        
        // Category management (admin only)
        Route::get('admin/categories', [CategoryController::class, 'index']); // List categories (admin)
        Route::get('admin/categories/{category}', [CategoryController::class, 'show']); // Show category (admin)
        Route::post('admin/categories', [CategoryController::class, 'store']); // Create category (admin)
        Route::put('admin/categories/{category}', [CategoryController::class, 'update']); // Update category (admin)
        Route::delete('admin/categories/{category}', [CategoryController::class, 'destroy']); // Delete category (admin)
        
        // Category specifications management (admin only)
        Route::get('admin/categories/{category}/specifications', [CategoryController::class, 'specifications']); // Get category specifications
        Route::post('admin/categories/{category}/specifications/assign', [CategoryController::class, 'assignSpecifications']); // Assign specifications to category (replaces all)
        Route::post('admin/categories/{category}/specifications/attach', [CategoryController::class, 'attachSpecification']); // Add single specification
        Route::delete('admin/categories/{category}/specifications/{specification}', [CategoryController::class, 'detachSpecification']); // Remove specification
        
        // SELLER STATS ROUTES
        // ===================
        
        // Seller dashboard and analytics
        Route::get('seller/dashboard', [SellerStatsController::class, 'dashboard']); // Seller dashboard stats
        Route::get('seller/stats/views', [SellerStatsController::class, 'totalViews']); // Total views for seller
        Route::get('seller/stats/contacts', [SellerStatsController::class, 'totalContacts']); // Total contacts for seller
        Route::get('seller/stats/clicks', [SellerStatsController::class, 'totalClicks']); // Total clicks for seller
        Route::get('seller/ads/{ad}/views', [SellerStatsController::class, 'adViews']); // Views for specific ad
        Route::get('seller/ads/{ad}/contacts', [SellerStatsController::class, 'adContacts']); // Contacts for specific ad
        Route::get('seller/ads/{ad}/clicks', [SellerStatsController::class, 'adClicks']); // Clicks for specific ad
        Route::post('seller/ads/{ad}/views', [SellerStatsController::class, 'incrementAdViews']); // Increment view count
        Route::post('seller/ads/{ad}/contacts', [SellerStatsController::class, 'incrementAdContacts']); // Increment contact count
        Route::post('seller/ads/{ad}/clicks', [SellerStatsController::class, 'incrementAdClicks']); // Increment click count
        
        // SLIDER ROUTES (Admin)
        // =====================
        
        // Slider management (admin only)
        Route::post('admin/sliders', [SliderController::class, 'store']); // Create slider (admin)
        Route::put('admin/sliders/{slider}', [SliderController::class, 'update']); // Update slider (admin)
        Route::delete('admin/sliders/{slider}', [SliderController::class, 'destroy']); // Delete slider (admin)
        Route::post('admin/sliders/{slider}/activate', [SliderController::class, 'activate']); // Activate slider (admin)
        Route::post('admin/sliders/{slider}/deactivate', [SliderController::class, 'deactivate']); // Deactivate slider (admin)
        
        // ADMIN STATS ROUTES
        // ==================
        
        // Admin analytics and statistics (admin only)
        Route::get('admin/stats/dashboard', [AdminStatsController::class, 'dashboard']); // Overall platform stats
        Route::get('admin/stats/ads/{ad}/views', [AdminStatsController::class, 'adViews']); // Ad views count
        Route::get('admin/stats/ads/{ad}/clicks', [AdminStatsController::class, 'adClicks']); // Ad clicks count
        Route::get('admin/stats/dealer/{user}', [AdminStatsController::class, 'dealerStats']); // Dealer statistics
        Route::get('admin/stats/user/{user}', [AdminStatsController::class, 'userStats']); // User statistics
        Route::get('admin/stats/ads/{type}', [AdminStatsController::class, 'adsByType']); // Count ads by type
    });

    // Public brand routes
    Route::get('/brands', [BrandController::class, 'index']);
    Route::get('/brands/{brand}/models', [BrandController::class, 'models']);
    
    // Public blog routes
    Route::get('/blogs', [BlogController::class, 'index']); // List published blogs
    Route::get('/blogs/{blog}', [BlogController::class, 'show']); // Show published blog

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

    // Public Auction Ads routes (no authentication required)
    Route::get('auction-ads', [AuctionAdsController::class, 'index']);
    Route::get('auction-ads/{ad}', [AuctionAdsController::class, 'show']);
    Route::get('users/{user}/auction-ads', [AuctionAdsController::class, 'listByUser']); // Public auctions by user
    
    // Public Reviews routes (no authentication required)
    Route::get('reviews', [ReviewController::class, 'index']); // List all reviews with filters
    Route::get('reviews/{review}', [ReviewController::class, 'show']); // View specific review
    Route::get('ads/{ad}/reviews', [ReviewController::class, 'adReviews']); // Reviews for a specific ad
    Route::get('users/{user}/reviews', [ReviewController::class, 'userReviews']); // Reviews for a specific seller
    
    // Public Packages routes (no authentication required)
    Route::get('packages', [PackageController::class, 'index']); // List active packages
    Route::get('packages/{package}', [PackageController::class, 'show']); // View package details
});
