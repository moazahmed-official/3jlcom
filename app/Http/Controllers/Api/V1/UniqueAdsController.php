<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUniqueAdRequest;
use App\Http\Requests\UpdateUniqueAdRequest;
use App\Http\Resources\UniqueAdResource;
use App\Models\Ad;
use App\Models\UniqueAd;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UniqueAdsController extends Controller
{
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
     */
    public function store(StoreUniqueAdRequest $request): JsonResponse
    {
        // Determine user_id - admin can create for other users, regular user only for themselves
        $userId = $request->user_id ?? auth()->id();
        
        // Authorization check - only admins can create ads for other users
        if ($userId !== auth()->id() && !auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['Only admins can create ads for other users']]
            ], 403);
        }

        try {
            $ad = DB::transaction(function () use ($request, $userId) {
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
                UniqueAd::create([
                    'ad_id' => $ad->id,
                    'banner_image_id' => $request->banner_image_id,
                    'banner_color' => $request->banner_color,
                    'is_auto_republished' => $request->boolean('is_auto_republished', false),
                    'is_verified_ad' => auth()->user()->isAdmin() ? $request->boolean('is_verified_ad', false) : false,
                ]);

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
            $ad->load(['uniqueAd', 'uniqueAd.bannerImage', 'user', 'brand', 'model', 'city', 'country', 'category', 'media']);

            Log::info('Unique ad created successfully', [
                'ad_id' => $ad->id,
                'user_id' => auth()->id(),
                'title' => $ad->title
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
}
