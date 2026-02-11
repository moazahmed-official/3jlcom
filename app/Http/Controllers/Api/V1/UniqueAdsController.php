<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUniqueAdRequest;
use App\Http\Requests\UpdateUniqueAdRequest;
use App\Http\Resources\UniqueAdResource;
use App\Http\Traits\LogsAudit;
use App\Models\Ad;
use App\Models\UniqueAd;
use App\Models\Media;
use App\Models\UniqueAdTypeDefinition;
use App\Services\PackageFeatureService;
use App\Services\UniqueAdTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UniqueAdsController extends Controller
{
    use LogsAudit;
    /**
     * List published unique ads (public)
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Ad::where('type', 'unique')
            ->with(['uniqueAd', 'uniqueAd.bannerImage', 'user', 'brand', 'model', 'city', 'country', 'category', 'media'])
            ->where('status', 'published');

        // Filter by brand
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        // Filter by model
        if ($request->filled('model_id')) {
            $query->where('model_id', $request->model_id);
        }

        // Filter by city
        if ($request->filled('city_id')) {
            $query->where('city_id', $request->city_id);
        }

        // Filter by country
        if ($request->filled('country_id')) {
            $query->where('country_id', $request->country_id);
        }

        // Filter by verified ads only
        if ($request->boolean('verified_only')) {
            $query->whereHas('uniqueAd', fn($q) => $q->where('is_verified_ad', true));
        }

        // Filter by featured ads only
        if ($request->boolean('featured_only')) {
            $query->whereHas('uniqueAd', fn($q) => $q->where('is_featured', true));
        }

        // Filter by year range
        if ($request->filled('min_year')) {
            $query->where('year', '>=', $request->min_year);
        }

        if ($request->filled('max_year')) {
            $query->where('year', '<=', $request->max_year);
        }

        // Search by title or description
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                  ->orWhere('description', 'like', "%{$searchTerm}%");
            });
        }

        // Sort options
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        if (in_array($sortBy, ['created_at', 'updated_at', 'views_count', 'title'])) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $limit = min($request->get('limit', 15), 50);
        $ads = $query->paginate($limit);

        return UniqueAdResource::collection($ads);
    }

    /**
     * Get all unique ads for the authenticated user (all statuses)
     */
    public function myAds(Request $request): AnonymousResourceCollection
    {
        $query = Ad::where('type', 'unique')
            ->where('user_id', auth()->id())
            ->with(['uniqueAd', 'uniqueAd.bannerImage', 'user', 'brand', 'model', 'city', 'country', 'category', 'media']);

        // Filter by status if provided
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by brand
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        // Search by title or description
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                  ->orWhere('description', 'like', "%{$searchTerm}%");
            });
        }

        // Sort options
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        if (in_array($sortBy, ['created_at', 'updated_at', 'views_count', 'title', 'status'])) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $limit = min($request->get('limit', 15), 50);
        $ads = $query->paginate($limit);

        return UniqueAdResource::collection($ads);
    }

    /**
     * Get all unique ads for admin (all statuses, all users)
     */
    public function adminIndex(Request $request): JsonResponse|AnonymousResourceCollection
    {
        // Check if user is admin
        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['Only admins can access this endpoint']]
            ], 403);
        }

        $query = Ad::where('type', 'unique')
            ->with(['uniqueAd', 'uniqueAd.bannerImage', 'user', 'brand', 'model', 'city', 'country', 'category', 'media']);

        // Filter by status if provided
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by verified ads
        if ($request->has('is_verified')) {
            $query->whereHas('uniqueAd', fn($q) => $q->where('is_verified_ad', $request->boolean('is_verified')));
        }

        // Filter by featured ads
        if ($request->has('is_featured')) {
            $query->whereHas('uniqueAd', fn($q) => $q->where('is_featured', $request->boolean('is_featured')));
        }

        // Filter by brand
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        // Filter by city
        if ($request->filled('city_id')) {
            $query->where('city_id', $request->city_id);
        }

        // Filter by country
        if ($request->filled('country_id')) {
            $query->where('country_id', $request->country_id);
        }

        // Search by title or description
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                  ->orWhere('description', 'like', "%{$searchTerm}%");
            });
        }

        // Sort options
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        if (in_array($sortBy, ['created_at', 'updated_at', 'views_count', 'title', 'status', 'user_id'])) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $limit = min($request->get('limit', 15), 50);
        $ads = $query->paginate($limit);

        return UniqueAdResource::collection($ads);
    }

    /**
     * Store a new unique ad
     *
     * Business logic:
     * - PAID plan users: Can directly create unique ads with a type (features come from package).
     * - FREE plan users: Cannot directly create unique ads with types.
     *   They must create a normal ad first, then use the upgrade request system.
     *   Admins may still create unique ads for free-plan users.
     */
    public function store(StoreUniqueAdRequest $request, PackageFeatureService $packageService, UniqueAdTypeService $typeService): JsonResponse
    {
        // Determine user_id - admin can create for other users, regular user only for themselves
        $userId = $request->user_id ?? auth()->id();
        $user = \App\Models\User::findOrFail($userId);
        
        // Authorization check - only admins can create ads for other users
        if ($userId !== auth()->id() && !auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['Only admins can create ads for other users']]
            ], 403);
        }

        // Determine plan type
        $activePackage = $user->activePackage;
        $isFreeUser = !$activePackage || $activePackage->isFree();
        $isAdmin = auth()->user()->isAdmin();

        // FREE plan users cannot directly create unique ads (unless admin is creating for them)
        if ($isFreeUser && !$isAdmin) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Free plan users cannot directly create unique ads. Create a normal ad first, then request an upgrade via the upgrade request system.',
                'errors' => ['plan' => ['Unique ad creation requires a paid plan. Use POST /api/v1/ads/{ad}/upgrade-request to request an upgrade for an existing normal ad.']]
            ], 403);
        }

        // Get unique ad type if specified
        $uniqueAdType = null;
        if ($request->filled('unique_ad_type_id')) {
            $uniqueAdType = UniqueAdTypeDefinition::find($request->unique_ad_type_id);
            
            if (!$uniqueAdType) {
                return response()->json([
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'Unique ad type not found',
                    'errors' => ['unique_ad_type_id' => ['The selected unique ad type does not exist']]
                ], 404);
            }

            // Validate user can create this type (paid plan + package check)
            try {
                $typeService->validateUserCanCreateType($user, $uniqueAdType);
            } catch (\Exception $e) {
                return response()->json([
                    'status' => 'error',
                    'code' => 403,
                    'message' => $e->getMessage(),
                    'errors' => ['unique_ad_type_id' => [$e->getMessage()]]
                ], 403);
            }

            // Validate requested features against type definition
            $featureErrors = $typeService->validateRequestedFeatures($request->validated(), $uniqueAdType);
            if (!empty($featureErrors)) {
                return response()->json([
                    'status' => 'error',
                    'code' => 422,
                    'message' => 'Feature validation failed',
                    'errors' => $featureErrors
                ], 422);
            }

            // Validate media counts
            if ($request->has('media_ids') && !empty($request->media_ids)) {
                $mediaErrors = $typeService->validateMediaCounts($request->media_ids, $uniqueAdType);
                if (!empty($mediaErrors)) {
                    return response()->json([
                        'status' => 'error',
                        'code' => 422,
                        'message' => 'Media validation failed',
                        'errors' => $mediaErrors
                    ], 422);
                }
            }
        } else {
            // Fallback to generic package validation for backward compatibility
            $adValidation = $packageService->validateAdCreation($user, 'unique');
            if (!$adValidation['allowed']) {
                return response()->json([
                    'status' => 'error',
                    'code' => 403,
                    'message' => $adValidation['reason'],
                    'errors' => ['package' => [$adValidation['reason']]],
                    'remaining' => $adValidation['remaining']
                ], 403);
            }
        }

        // Validate media limits
        if ($request->has('media_ids') && !empty($request->media_ids) && !$uniqueAdType) {
            $imageCount = Media::whereIn('id', $request->media_ids)->where('type', 'image')->count();
            $videoCount = Media::whereIn('id', $request->media_ids)->where('type', 'video')->count();
            
            $mediaValidation = $packageService->validateMediaLimits($user, $imageCount, $videoCount);
            if (!$mediaValidation['allowed']) {
                return response()->json([
                    'status' => 'error',
                    'code' => 403,
                    'message' => $mediaValidation['reason'],
                    'errors' => ['media' => [$mediaValidation['reason']]]
                ], 403);
            }
        }

        try {
            $ad = DB::transaction(function () use ($request, $userId, $uniqueAdType, $typeService) {
                // Create the main ad record
                $ad = Ad::create([
                    'user_id' => $userId,
                    'type' => 'unique',
                    'title' => $request->title,
                    'description' => $request->description,
                    'category_id' => $request->category_id,
                    'city_id' => $request->city_id,
                    'country_id' => $request->country_id,
                    'brand_id' => $request->brand_id,
                    'model_id' => $request->model_id,
                    'year' => $request->year,
                    'contact_phone' => $request->contact_phone,
                    'whatsapp_number' => $request->whatsapp_number,
                    'status' => 'published',
                    'period_days' => 30,
                ]);

                // Create the unique ad specific record
                $uniqueAd = UniqueAd::create([
                    'ad_id' => $ad->id,
                    'unique_ad_type_id' => $uniqueAdType?->id,
                    'banner_image_id' => $request->banner_image_id,
                    'banner_color' => $request->banner_color,
                    'is_auto_republished' => $request->boolean('is_auto_republished', false),
                    'is_verified_ad' => auth()->user()->isAdmin() ? $request->boolean('is_verified_ad', false) : false,
                ]);

                // Apply type features if type is specified
                if ($uniqueAdType) {
                    $typeService->applyTypeFeatures($uniqueAd, $uniqueAdType);
                }

                // Enable Caishha feature if requested and allowed
                if ($request->boolean('enable_caishha_feature') && $uniqueAdType && $uniqueAdType->caishha_feature_enabled) {
                    $typeService->enableCaishhaFeature($ad, $uniqueAd);
                }

                // Attach media if provided
                if ($request->has('media_ids') && !empty($request->media_ids)) {
                    $ad->media()->sync($request->media_ids);
                    
                    \App\Models\Media::whereIn('id', $request->media_ids)
                        ->update([
                            'related_resource' => 'ads',
                            'related_id' => $ad->id
                        ]);
                }

                return $ad;
            });

            // Load relationships for response
            $ad->load(['uniqueAd', 'uniqueAd.bannerImage', 'uniqueAd.typeDefinition', 'user', 'brand', 'model', 'city', 'country', 'category', 'media']);

            Log::info('Unique ad created successfully', [
                'ad_id' => $ad->id,
                'user_id' => auth()->id(),
                'title' => $ad->title,
                'unique_ad_type_id' => $uniqueAdType?->id
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Unique ad created successfully',
                'data' => new UniqueAdResource($ad)
            ], 201);

        } catch (\Exception $e) {
            Log::error('Unique ad creation failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request_data' => $request->validated()
            ]);

            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to create unique ad',
                'errors' => ['general' => ['An unexpected error occurred while creating the ad']]
            ], 500);
        }
    }

    /**
     * Show a unique ad
     */
    public function show($id): JsonResponse
    {
        $ad = Ad::where('type', 'unique')
            ->with(['uniqueAd', 'uniqueAd.bannerImage', 'user', 'brand', 'model', 'city', 'country', 'category', 'media'])
            ->find($id);

        if (!$ad) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ad not found',
                'errors' => ['ad' => ['The requested unique ad does not exist']]
            ], 404);
        }

        // Increment view count if not viewing own ad
        if (!auth()->check() || auth()->id() !== $ad->user_id) {
            $ad->increment('views_count');
        }

        return response()->json([
            'status' => 'success',
            'data' => new UniqueAdResource($ad)
        ]);
    }

    /**
     * Update a unique ad
     */
    public function update(UpdateUniqueAdRequest $request, $id): JsonResponse
    {
        $ad = Ad::where('type', 'unique')->find($id);

        if (!$ad) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ad not found',
                'errors' => ['ad' => ['The requested unique ad does not exist']]
            ], 404);
        }

        // Authorization check - owner or admin can update
        if (auth()->id() !== $ad->user_id && !auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['You do not have permission to update this ad']]
            ], 403);
        }

        try {
            DB::transaction(function () use ($request, $ad) {
                // Update main ad fields
                $adData = $request->only([
                    'title', 'description', 'brand_id', 'model_id', 'year', 
                    'contact_phone', 'whatsapp_number', 'status'
                ]);
                
                if (!empty($adData)) {
                    $ad->update($adData);
                }

                // Update unique ad specific fields
                $uniqueAdData = $request->only(['banner_image_id', 'banner_color', 'is_auto_republished']);
                
                // Only admin can update is_verified_ad
                if (auth()->user()->isAdmin() && $request->has('is_verified_ad')) {
                    $uniqueAdData['is_verified_ad'] = $request->boolean('is_verified_ad');
                }
                
                if (!empty($uniqueAdData) && $ad->uniqueAd) {
                    $ad->uniqueAd->update($uniqueAdData);
                }

                // Update media associations if provided
                if ($request->has('media_ids')) {
                    $ad->media()->sync([]);
                    
                    if (!empty($request->media_ids)) {
                        $ad->media()->sync($request->media_ids);
                        
                        \App\Models\Media::whereIn('id', $request->media_ids)
                            ->update([
                                'related_resource' => 'ads',
                                'related_id' => $ad->id
                            ]);
                    }
                }
            });

            // Load relationships for response
            $ad->load(['uniqueAd', 'uniqueAd.bannerImage', 'user', 'brand', 'model', 'city', 'country', 'category', 'media']);

            Log::info('Unique ad updated successfully', [
                'ad_id' => $ad->id,
                'user_id' => auth()->id(),
                'updated_fields' => array_keys($request->validated())
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Unique ad updated successfully',
                'data' => new UniqueAdResource($ad)
            ]);

        } catch (\Exception $e) {
            Log::error('Unique ad update failed', [
                'error' => $e->getMessage(),
                'ad_id' => $ad->id,
                'user_id' => auth()->id(),
                'request_data' => $request->validated()
            ]);

            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to update unique ad',
                'errors' => ['general' => ['An unexpected error occurred while updating the ad']]
            ], 500);
        }
    }

    /**
     * Delete a unique ad
     */
    public function destroy($id): JsonResponse
    {
        $ad = Ad::where('type', 'unique')->find($id);

        if (!$ad) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ad not found',
                'errors' => ['ad' => ['The requested unique ad does not exist']]
            ], 404);
        }

        // Authorization check - owner or admin can delete
        if (auth()->id() !== $ad->user_id && !auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['You do not have permission to delete this ad']]
            ], 403);
        }

        try {
            $adId = $ad->id;
            
            DB::transaction(function () use ($ad) {
                // Remove media associations
                $ad->media()->detach();

                // Delete related unique ad record
                $ad->uniqueAd?->delete();

                // Delete the main ad record (soft delete if using SoftDeletes)
                $ad->delete();
            });

            Log::info('Unique ad deleted successfully', [
                'ad_id' => $adId,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Unique ad deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Unique ad deletion failed', [
                'error' => $e->getMessage(),
                'ad_id' => $ad->id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to delete unique ad',
                'errors' => ['general' => ['An unexpected error occurred while deleting the ad']]
            ], 500);
        }
    }

    /**
     * Republish a unique ad
     */
    public function republish($id): JsonResponse
    {
        $ad = Ad::where('type', 'unique')->find($id);

        if (!$ad) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ad not found',
                'errors' => ['ad' => ['The requested unique ad does not exist']]
            ], 404);
        }

        // Authorization check - owner or admin can republish
        if (auth()->id() !== $ad->user_id && !auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['You do not have permission to republish this ad']]
            ], 403);
        }

        try {
            // Update timestamps to push ad to top
            $ad->touch();

            // Set status to published if it was expired or draft
            if (in_array($ad->status, ['expired', 'draft', 'removed'])) {
                $ad->update(['status' => 'published']);
            }

            Log::info('Unique ad republished successfully', [
                'ad_id' => $ad->id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Unique ad republished successfully',
                'data' => ['republished_at' => now()->toISOString()]
            ]);

        } catch (\Exception $e) {
            Log::error('Unique ad republish failed', [
                'error' => $e->getMessage(),
                'ad_id' => $ad->id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to republish unique ad',
                'errors' => ['general' => ['An unexpected error occurred while republishing the ad']]
            ], 500);
        }
    }

    /**
     * Feature/promote a unique ad (admin/marketer only)
     */
    public function feature($id): JsonResponse
    {
        $ad = Ad::where('type', 'unique')->find($id);

        if (!$ad) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ad not found',
                'errors' => ['ad' => ['The requested unique ad does not exist']]
            ], 404);
        }

        // Authorization check - admin or marketer can feature
        $user = auth()->user();
        if (!$user->isAdmin() && !$user->hasRole('marketer')) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['Only admins and marketers can feature ads']]
            ], 403);
        }

        if ($ad->uniqueAd) {
            $ad->uniqueAd->update([
                'is_featured' => true,
                'featured_at' => now()
            ]);
        }

        $this->auditLog(
            actionType: 'ad.featured',
            resourceType: 'ad',
            resourceId: $ad->id,
            details: [
                'ad_type' => 'unique',
                'title' => $ad->title
            ],
            severity: 'info'
        );

        $ad->load(['uniqueAd', 'uniqueAd.bannerImage', 'user', 'brand', 'model', 'city', 'country', 'category', 'media']);

        Log::info('Unique ad featured successfully', [
            'ad_id' => $ad->id,
            'user_id' => auth()->id()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Unique ad featured successfully',
            'data' => new UniqueAdResource($ad)
        ]);
    }

    /**
     * Unfeature a unique ad (admin/marketer only)
     */
    public function unfeature($id): JsonResponse
    {
        $ad = Ad::where('type', 'unique')->find($id);

        if (!$ad) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ad not found',
                'errors' => ['ad' => ['The requested unique ad does not exist']]
            ], 404);
        }

        // Authorization check - admin or marketer can unfeature
        $user = auth()->user();
        if (!$user->isAdmin() && !$user->hasRole('marketer')) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['Only admins and marketers can unfeature ads']]
            ], 403);
        }

        if ($ad->uniqueAd) {
            $ad->uniqueAd->update([
                'is_featured' => false,
                'featured_at' => null
            ]);
        }

        $ad->load(['uniqueAd', 'uniqueAd.bannerImage', 'user', 'brand', 'model', 'city', 'country', 'category', 'media']);

        Log::info('Unique ad unfeatured successfully', [
            'ad_id' => $ad->id,
            'user_id' => auth()->id()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Unique ad unfeatured successfully',
            'data' => new UniqueAdResource($ad)
        ]);
    }

    /**
     * Request verification for a unique ad (owner only)
     */
    public function requestVerification($id): JsonResponse
    {
        $ad = Ad::where('type', 'unique')->find($id);

        if (!$ad) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ad not found',
                'errors' => ['ad' => ['The requested unique ad does not exist']]
            ], 404);
        }

        // Only owner can request verification
        if (auth()->id() !== $ad->user_id) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['Only ad owner can request verification']]
            ], 403);
        }

        // Check if already verified
        if ($ad->uniqueAd?->is_verified_ad) {
            return response()->json([
                'status' => 'error',
                'code' => 422,
                'message' => 'Ad is already verified',
                'errors' => ['verification' => ['This ad is already verified']]
            ], 422);
        }

        // Check if already pending verification
        if ($ad->uniqueAd?->verification_status === 'pending') {
            return response()->json([
                'status' => 'error',
                'code' => 422,
                'message' => 'Verification already requested',
                'errors' => ['verification' => ['A verification request is already pending']]
            ], 422);
        }

        if ($ad->uniqueAd) {
            $ad->uniqueAd->update([
                'verification_status' => 'pending',
                'verification_requested_at' => now()
            ]);
        }

        Log::info('Unique ad verification requested', [
            'ad_id' => $ad->id,
            'user_id' => auth()->id()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Verification request submitted successfully',
            'data' => ['verification_status' => 'pending', 'requested_at' => now()->toISOString()]
        ]);
    }

    /**
     * Approve verification request (admin only)
     */
    public function approveVerification($id): JsonResponse
    {
        $ad = Ad::where('type', 'unique')->find($id);

        if (!$ad) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ad not found',
                'errors' => ['ad' => ['The requested unique ad does not exist']]
            ], 404);
        }

        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['Only admins can approve verification']]
            ], 403);
        }

        if ($ad->uniqueAd) {
            $ad->uniqueAd->update([
                'is_verified_ad' => true,
                'verification_status' => 'approved',
                'verified_at' => now(),
                'verified_by' => auth()->id()
            ]);
        }

        $this->auditLog(
            actionType: 'ad.verification_approved',
            resourceType: 'ad',
            resourceId: $ad->id,
            details: [
                'ad_type' => 'unique',
                'title' => $ad->title,
                'user_id' => $ad->user_id
            ],
            severity: 'warning'
        );

        $ad->load(['uniqueAd', 'uniqueAd.bannerImage', 'user', 'brand', 'model', 'city', 'country', 'category', 'media']);

        Log::info('Unique ad verification approved', [
            'ad_id' => $ad->id,
            'approved_by' => auth()->id()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Ad verification approved successfully',
            'data' => new UniqueAdResource($ad)
        ]);
    }

    /**
     * Reject verification request (admin only)
     */
    public function rejectVerification(Request $request, $id): JsonResponse
    {
        $ad = Ad::where('type', 'unique')->find($id);

        if (!$ad) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ad not found',
                'errors' => ['ad' => ['The requested unique ad does not exist']]
            ], 404);
        }

        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['Only admins can reject verification']]
            ], 403);
        }

        if ($ad->uniqueAd) {
            $ad->uniqueAd->update([
                'is_verified_ad' => false,
                'verification_status' => 'rejected',
                'verification_rejection_reason' => $request->get('reason')
            ]);
        }

        $this->auditLog(
            actionType: 'ad.verification_rejected',
            resourceType: 'ad',
            resourceId: $ad->id,
            details: [
                'ad_type' => 'unique',
                'title' => $ad->title,
                'user_id' => $ad->user_id,
                'rejection_reason' => $request->get('reason')
            ],
            severity: 'warning'
        );

        $ad->load(['uniqueAd', 'uniqueAd.bannerImage', 'user', 'brand', 'model', 'city', 'country', 'category', 'media']);

        Log::info('Unique ad verification rejected', [
            'ad_id' => $ad->id,
            'rejected_by' => auth()->id(),
            'reason' => $request->get('reason')
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Ad verification rejected',
            'data' => new UniqueAdResource($ad)
        ]);
    }

    /**
     * Toggle auto-republish setting
     */
    public function toggleAutoRepublish($id): JsonResponse
    {
        $ad = Ad::where('type', 'unique')->find($id);

        if (!$ad) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ad not found',
                'errors' => ['ad' => ['The requested unique ad does not exist']]
            ], 404);
        }

        // Authorization check - owner or admin
        if (auth()->id() !== $ad->user_id && !auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['You do not have permission to modify this ad']]
            ], 403);
        }

        $newState = !($ad->uniqueAd?->is_auto_republished ?? false);
        
        if ($ad->uniqueAd) {
            $ad->uniqueAd->update(['is_auto_republished' => $newState]);
        }

        Log::info('Unique ad auto-republish toggled', [
            'ad_id' => $ad->id,
            'user_id' => auth()->id(),
            'is_auto_republished' => $newState
        ]);

        return response()->json([
            'status' => 'success',
            'message' => $newState ? 'Auto-republish enabled' : 'Auto-republish disabled',
            'data' => ['is_auto_republished' => $newState]
        ]);
    }

    /**
     * Add unique ad to favorites
     */
    public function favorite($id): JsonResponse
    {
        $ad = Ad::where('type', 'unique')->find($id);

        if (!$ad) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ad not found',
                'errors' => ['ad' => ['The requested unique ad does not exist']]
            ], 404);
        }

        $user = auth()->user();
        
        // Check if already favorited
        if ($user->favoriteAds()->where('ad_id', $ad->id)->exists()) {
            return response()->json([
                'status' => 'error',
                'code' => 422,
                'message' => 'Already in favorites',
                'errors' => ['favorite' => ['This ad is already in your favorites']]
            ], 422);
        }

        $user->favoriteAds()->attach($ad->id);

        Log::info('Unique ad added to favorites', [
            'ad_id' => $ad->id,
            'user_id' => auth()->id()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Ad added to favorites'
        ]);
    }

    /**
     * Remove unique ad from favorites
     */
    public function unfavorite($id): JsonResponse
    {
        $ad = Ad::where('type', 'unique')->find($id);

        if (!$ad) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ad not found',
                'errors' => ['ad' => ['The requested unique ad does not exist']]
            ], 404);
        }

        $user = auth()->user();
        
        // Check if not favorited
        if (!$user->favoriteAds()->where('ad_id', $ad->id)->exists()) {
            return response()->json([
                'status' => 'error',
                'code' => 422,
                'message' => 'Not in favorites',
                'errors' => ['favorite' => ['This ad is not in your favorites']]
            ], 422);
        }

        $user->favoriteAds()->detach($ad->id);

        Log::info('Unique ad removed from favorites', [
            'ad_id' => $ad->id,
            'user_id' => auth()->id()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Ad removed from favorites'
        ]);
    }

    /**
     * List user's favorite unique ads
     */
    public function favorites(Request $request): AnonymousResourceCollection
    {
        $query = auth()->user()
            ->favoriteAds()
            ->where('type', 'unique')
            ->with(['uniqueAd', 'uniqueAd.bannerImage', 'user', 'brand', 'model', 'city', 'country', 'category', 'media']);

        $limit = min($request->get('limit', 15), 50);
        $ads = $query->paginate($limit);

        return UniqueAdResource::collection($ads);
    }

    /**
     * Track contact seller action
     */
    public function contactSeller($id, Request $request): JsonResponse
    {
        $ad = Ad::where('type', 'unique')->find($id);

        if (!$ad) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ad not found',
                'errors' => ['ad' => ['The requested unique ad does not exist']]
            ], 404);
        }

        $contactType = $request->get('type', 'phone'); // phone, whatsapp

        // Increment contact count
        $ad->increment('contact_count');

        // Log the contact action for analytics
        Log::info('Unique ad contact action', [
            'ad_id' => $ad->id,
            'contact_type' => $contactType,
            'user_id' => auth()->id()
        ]);

        $contactInfo = [
            'phone' => $ad->contact_phone,
            'whatsapp' => $ad->whatsapp_number
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'Contact recorded',
            'data' => [
                'contact_type' => $contactType,
                'contact_info' => $contactInfo[$contactType] ?? $contactInfo['phone']
            ]
        ]);
    }

    /**
     * Get statistics for a specific unique ad
     */
    public function stats($id): JsonResponse
    {
        $ad = Ad::where('type', 'unique')->find($id);

        if (!$ad) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ad not found',
                'errors' => ['ad' => ['The requested unique ad does not exist']]
            ], 404);
        }

        // Authorization check - owner or admin
        if (auth()->id() !== $ad->user_id && !auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['You do not have permission to view stats for this ad']]
            ], 403);
        }

        $favoriteCount = DB::table('favorites')->where('ad_id', $ad->id)->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'ad_id' => $ad->id,
                'views_count' => $ad->views_count ?? 0,
                'contact_count' => $ad->contact_count ?? 0,
                'favorite_count' => $favoriteCount,
                'is_featured' => $ad->uniqueAd?->is_featured ?? false,
                'is_verified' => $ad->uniqueAd?->is_verified_ad ?? false,
                'created_at' => $ad->created_at,
                'updated_at' => $ad->updated_at
            ]
        ]);
    }

    /**
     * Get global unique ads statistics (admin only)
     */
    public function globalStats(): JsonResponse
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['Only admins can view global statistics']]
            ], 403);
        }

        $stats = [
            'total_unique_ads' => Ad::where('type', 'unique')->count(),
            'published' => Ad::where('type', 'unique')->where('status', 'published')->count(),
            'draft' => Ad::where('type', 'unique')->where('status', 'draft')->count(),
            'pending' => Ad::where('type', 'unique')->where('status', 'pending')->count(),
            'expired' => Ad::where('type', 'unique')->where('status', 'expired')->count(),
            'archived' => Ad::where('type', 'unique')->where('status', 'archived')->count(),
            'featured' => Ad::where('type', 'unique')
                ->whereHas('uniqueAd', fn($q) => $q->where('is_featured', true))
                ->count(),
            'verified' => Ad::where('type', 'unique')
                ->whereHas('uniqueAd', fn($q) => $q->where('is_verified_ad', true))
                ->count(),
            'pending_verification' => Ad::where('type', 'unique')
                ->whereHas('uniqueAd', fn($q) => $q->where('verification_status', 'pending'))
                ->count(),
            'total_views' => Ad::where('type', 'unique')->sum('views_count'),
            'total_contacts' => Ad::where('type', 'unique')->sum('contact_count'),
            'ads_created_today' => Ad::where('type', 'unique')->whereDate('created_at', today())->count(),
            'ads_created_this_week' => Ad::where('type', 'unique')->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'ads_created_this_month' => Ad::where('type', 'unique')->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }

    /**
     * List unique ads by user (public profile)
     */
    public function listByUser($userId): AnonymousResourceCollection
    {
        $query = Ad::where('type', 'unique')
            ->where('user_id', $userId)
            ->where('status', 'published')
            ->with(['uniqueAd', 'uniqueAd.bannerImage', 'user', 'brand', 'model', 'city', 'country', 'category', 'media'])
            ->orderBy('created_at', 'desc');

        $ads = $query->paginate(15);

        return UniqueAdResource::collection($ads);
    }

    /**
     * Publish a unique ad
     */
    public function publish($id): JsonResponse
    {
        $ad = Ad::where('type', 'unique')->find($id);

        if (!$ad) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ad not found',
                'errors' => ['ad' => ['The requested unique ad does not exist']]
            ], 404);
        }

        // Authorization check - owner or admin
        if (auth()->id() !== $ad->user_id && !auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['You do not have permission to publish this ad']]
            ], 403);
        }

        if ($ad->status === 'published') {
            return response()->json([
                'status' => 'error',
                'code' => 422,
                'message' => 'Ad is already published',
                'errors' => ['status' => ['This ad is already published']]
            ], 422);
        }

        $ad->update([
            'status' => 'published',
            'published_at' => now()
        ]);

        $ad->load(['uniqueAd', 'uniqueAd.bannerImage', 'user', 'brand', 'model', 'city', 'country', 'category', 'media']);

        Log::info('Unique ad published', [
            'ad_id' => $ad->id,
            'user_id' => auth()->id()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Ad published successfully',
            'data' => new UniqueAdResource($ad)
        ]);
    }

    /**
     * Unpublish a unique ad
     */
    public function unpublish($id): JsonResponse
    {
        $ad = Ad::where('type', 'unique')->find($id);

        if (!$ad) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ad not found',
                'errors' => ['ad' => ['The requested unique ad does not exist']]
            ], 404);
        }

        // Authorization check - owner or admin
        if (auth()->id() !== $ad->user_id && !auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['You do not have permission to unpublish this ad']]
            ], 403);
        }

        if ($ad->status !== 'published') {
            return response()->json([
                'status' => 'error',
                'code' => 422,
                'message' => 'Ad is not published',
                'errors' => ['status' => ['This ad is not currently published']]
            ], 422);
        }

        $ad->update(['status' => 'draft']);

        $ad->load(['uniqueAd', 'uniqueAd.bannerImage', 'user', 'brand', 'model', 'city', 'country', 'category', 'media']);

        Log::info('Unique ad unpublished', [
            'ad_id' => $ad->id,
            'user_id' => auth()->id()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Ad unpublished successfully',
            'data' => new UniqueAdResource($ad)
        ]);
    }

    /**
     * Expire a unique ad
     */
    public function expire($id): JsonResponse
    {
        $ad = Ad::where('type', 'unique')->find($id);

        if (!$ad) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ad not found',
                'errors' => ['ad' => ['The requested unique ad does not exist']]
            ], 404);
        }

        // Authorization check - admin only for manual expiration
        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['Only admins can manually expire ads']]
            ], 403);
        }

        $ad->update([
            'status' => 'expired',
            'expired_at' => now()
        ]);

        $ad->load(['uniqueAd', 'uniqueAd.bannerImage', 'user', 'brand', 'model', 'city', 'country', 'category', 'media']);

        Log::info('Unique ad expired', [
            'ad_id' => $ad->id,
            'user_id' => auth()->id()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Ad expired successfully',
            'data' => new UniqueAdResource($ad)
        ]);
    }

    /**
     * Archive a unique ad
     */
    public function archive($id): JsonResponse
    {
        $ad = Ad::where('type', 'unique')->find($id);

        if (!$ad) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ad not found',
                'errors' => ['ad' => ['The requested unique ad does not exist']]
            ], 404);
        }

        // Authorization check - owner or admin
        if (auth()->id() !== $ad->user_id && !auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['You do not have permission to archive this ad']]
            ], 403);
        }

        $ad->update([
            'status' => 'removed',
            'updated_at' => now()
        ]);

        $ad->load(['uniqueAd', 'uniqueAd.bannerImage', 'user', 'brand', 'model', 'city', 'country', 'category', 'media']);

        Log::info('Unique ad archived', [
            'ad_id' => $ad->id,
            'user_id' => auth()->id()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Ad archived successfully',
            'data' => new UniqueAdResource($ad)
        ]);
    }

    /**
     * Restore an archived/expired unique ad
     */
    public function restore($id): JsonResponse
    {
        $ad = Ad::where('type', 'unique')->find($id);

        if (!$ad) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ad not found',
                'errors' => ['ad' => ['The requested unique ad does not exist']]
            ], 404);
        }

        // Authorization check - owner or admin
        if (auth()->id() !== $ad->user_id && !auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['You do not have permission to restore this ad']]
            ], 403);
        }

        if (!in_array($ad->status, ['archived', 'expired', 'removed'])) {
            return response()->json([
                'status' => 'error',
                'code' => 422,
                'message' => 'Cannot restore',
                'errors' => ['status' => ['Only archived, expired, or removed ads can be restored']]
            ], 422);
        }

        $ad->update([
            'status' => 'draft',
            'archived_at' => null,
            'expired_at' => null
        ]);

        $ad->load(['uniqueAd', 'uniqueAd.bannerImage', 'user', 'brand', 'model', 'city', 'country', 'category', 'media']);

        Log::info('Unique ad restored', [
            'ad_id' => $ad->id,
            'user_id' => auth()->id()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Ad restored successfully',
            'data' => new UniqueAdResource($ad)
        ]);
    }

    /**
     * Bulk actions on unique ads (admin only)
     */
    public function bulkAction(Request $request): JsonResponse
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['Only admins can perform bulk actions']]
            ], 403);
        }

        $request->validate([
            'action' => 'required|string|in:publish,unpublish,expire,archive,delete,feature,unfeature',
            'ad_ids' => 'required|array|min:1',
            'ad_ids.*' => 'integer|exists:ads,id'
        ]);

        $action = $request->action;
        $adIds = $request->ad_ids;

        // Verify all ads are unique type
        $ads = Ad::where('type', 'unique')->whereIn('id', $adIds)->get();
        
        if ($ads->count() !== count($adIds)) {
            return response()->json([
                'status' => 'error',
                'code' => 422,
                'message' => 'Invalid ads',
                'errors' => ['ad_ids' => ['Some ads are not unique ads or do not exist']]
            ], 422);
        }

        $successCount = 0;
        $failedIds = [];

        foreach ($ads as $ad) {
            try {
                switch ($action) {
                    case 'publish':
                        $ad->update(['status' => 'published', 'published_at' => now()]);
                        break;
                    case 'unpublish':
                        $ad->update(['status' => 'draft']);
                        break;
                    case 'expire':
                        $ad->update(['status' => 'expired', 'expired_at' => now()]);
                        break;
                    case 'archive':
                        $ad->update(['status' => 'archived', 'archived_at' => now()]);
                        break;
                    case 'delete':
                        $ad->media()->detach();
                        $ad->uniqueAd?->delete();
                        $ad->delete();
                        break;
                    case 'feature':
                        $ad->uniqueAd?->update(['is_featured' => true, 'featured_at' => now()]);
                        break;
                    case 'unfeature':
                        $ad->uniqueAd?->update(['is_featured' => false, 'featured_at' => null]);
                        break;
                }
                $successCount++;
            } catch (\Exception $e) {
                $failedIds[] = $ad->id;
                Log::error('Bulk action failed for ad', [
                    'ad_id' => $ad->id,
                    'action' => $action,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Bulk action completed', [
            'action' => $action,
            'total' => count($adIds),
            'success' => $successCount,
            'failed' => count($failedIds),
            'user_id' => auth()->id()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => "Bulk {$action} completed",
            'data' => [
                'total' => count($adIds),
                'success_count' => $successCount,
                'failed_count' => count($failedIds),
                'failed_ids' => $failedIds
            ]
        ]);
    }

    /**
     * Convert unique ad to normal ad
     */
    public function convertToNormal(Request $request, $id): JsonResponse
    {
        $ad = Ad::where('type', 'unique')->find($id);

        if (!$ad) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ad not found',
                'errors' => ['ad' => ['The requested unique ad does not exist']]
            ], 404);
        }

        // Authorization check - owner or admin
        if (auth()->id() !== $ad->user_id && !auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['You do not have permission to convert this ad']]
            ], 403);
        }

        try {
            DB::transaction(function () use ($ad, $request) {
                // If there is a banner image on the unique ad, transfer it to the ad's media
                $bannerId = $ad->uniqueAd?->banner_image_id ?? null;
                if ($bannerId) {
                    $media = \App\Models\Media::find($bannerId);
                    if ($media) {
                        // attach without detaching existing media
                        $ad->media()->syncWithoutDetaching([$bannerId]);
                        $media->update([
                            'related_resource' => 'ads',
                            'related_id' => $ad->id
                        ]);
                    }
                }

                // Delete unique ad specific record
                $ad->uniqueAd?->delete();

                // Change type to normal
                $ad->update(['type' => 'normal']);

                // Validate/installment existence
                $installmentId = $request->input('installment_id');
                if ($installmentId && !DB::table('installments')->where('id', $installmentId)->exists()) {
                    $installmentId = null;
                }

                $priceCash = $request->input('price_cash');

                // Create normal ad record if needed
                \App\Models\NormalAd::create([
                    'ad_id' => $ad->id,
                    'price_cash' => $priceCash ?? null,
                    'installment_id' => $installmentId ?? null,
                    'start_time' => now(),
                    'update_time' => now()
                ]);
            });

            $ad->load(['normalAd', 'user', 'brand', 'model', 'city', 'country', 'category', 'media']);

            Log::info('Unique ad converted to normal', [
                'ad_id' => $ad->id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Ad converted to normal ad successfully',
                'data' => [
                    'id' => $ad->id,
                    'type' => 'normal',
                    'title' => $ad->title
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to convert unique ad to normal', [
                'ad_id' => $ad->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to convert ad',
                'errors' => ['general' => ['An error occurred while converting the ad']]
            ], 500);
        }
    }
}
