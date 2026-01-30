<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCaishhaAdRequest;
use App\Http\Requests\UpdateCaishhaAdRequest;
use App\Http\Requests\SubmitCaishhaOfferRequest;
use App\Http\Requests\AcceptCaishhaOfferRequest;
use App\Http\Requests\UpdateCaishhaOfferRequest;
use App\Http\Requests\DeleteCaishhaOfferRequest;
use App\Http\Resources\CaishhaAdResource;
use App\Http\Resources\CaishhaOfferResource;
use App\Models\Ad;
use App\Models\CaishhaAd;
use App\Models\CaishhaOffer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CaishhaAdsController extends Controller
{
    /**
     * List published Caishha ads (public)
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Ad::where('type', 'caishha')
            ->with(['caishhaAd', 'user', 'brand', 'model', 'city', 'country', 'category', 'media'])
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

        // Filter by window status
        if ($request->filled('window_status')) {
            $windowStatus = $request->window_status;
            if ($windowStatus === 'dealer_window') {
                // Ads still in dealer-only window
                $query->whereHas('caishhaAd', function ($q) {
                    $q->whereRaw('published_at + INTERVAL offers_window_period SECOND > NOW()');
                });
            } elseif ($windowStatus === 'open') {
                // Ads open to all (dealer window passed)
                $query->whereHas('caishhaAd', function ($q) {
                    $q->whereRaw('published_at + INTERVAL offers_window_period SECOND <= NOW()');
                });
            }
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

        return CaishhaAdResource::collection($ads);
    }

    /**
     * Get all Caishha ads for the authenticated user (all statuses)
     */
    public function myAds(Request $request): AnonymousResourceCollection
    {
        $query = Ad::where('type', 'caishha')
            ->where('user_id', auth()->id())
            ->with(['caishhaAd', 'caishhaAd.offers', 'user', 'brand', 'model', 'city', 'country', 'category', 'media']);

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

        return CaishhaAdResource::collection($ads);
    }

    /**
     * Get all Caishha ads for admin (all statuses, all users)
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

        $query = Ad::where('type', 'caishha')
            ->with(['caishhaAd', 'caishhaAd.offers', 'user', 'brand', 'model', 'city', 'country', 'category', 'media']);

        // Filter by status if provided
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

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

        return CaishhaAdResource::collection($ads);
    }

    /**
     * Store a new Caishha ad
     */
    public function store(StoreCaishhaAdRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Determine user_id - admin can create for other users
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

            // Create the main ad record
            $ad = Ad::create([
                'user_id' => $userId,
                'type' => 'caishha',
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
                'published_at' => now(),
                'period_days' => $request->period_days ?? 30,
            ]);

            // Create the caishha ad specific record
            CaishhaAd::create([
                'ad_id' => $ad->id,
                'offers_window_period' => $request->offers_window_period ?? \App\Models\CaishhaSetting::getDefaultDealerWindowSeconds(),
                'sellers_visibility_period' => $request->sellers_visibility_period ?? \App\Models\CaishhaSetting::getDefaultVisibilityPeriodSeconds(),
                'offers_count' => 0,
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

            DB::commit();

            // Load relationships for response
            $ad->load(['caishhaAd', 'user', 'brand', 'model', 'city', 'country', 'category', 'media']);

            Log::info('Caishha ad created successfully', [
                'ad_id' => $ad->id,
                'user_id' => auth()->id(),
                'title' => $ad->title
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Caishha ad created successfully',
                'data' => new CaishhaAdResource($ad)
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Caishha ad creation failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request_data' => $request->validated()
            ]);

            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to create ad',
                'errors' => ['general' => ['An unexpected error occurred while creating the ad']]
            ], 500);
        }
    }

    /**
     * Show a single Caishha ad
     */
    public function show($id): JsonResponse
    {
        $ad = Ad::where('type', 'caishha')
            ->with(['caishhaAd', 'caishhaAd.offers.user', 'user', 'brand', 'model', 'city', 'country', 'category', 'media'])
            ->find($id);

        if (!$ad) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ad not found',
                'errors' => ['ad' => ['The requested Caishha ad does not exist']]
            ], 404);
        }

        // Increment view count if not viewing own ad
        if (!auth()->check() || auth()->id() !== $ad->user_id) {
            $ad->increment('views_count');
        }

        return response()->json([
            'status' => 'success',
            'data' => new CaishhaAdResource($ad)
        ]);
    }

    /**
     * Update a Caishha ad
     */
    public function update(UpdateCaishhaAdRequest $request, $id): JsonResponse
    {
        $ad = Ad::where('type', 'caishha')->find($id);

        if (!$ad) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ad not found',
                'errors' => ['ad' => ['The requested Caishha ad does not exist']]
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
            DB::beginTransaction();

            // Update main ad fields
            $adData = $request->only([
                'title', 'description', 'brand_id', 'model_id', 'year', 
                'contact_phone', 'whatsapp_number'
            ]);
            
            if (!empty($adData)) {
                $ad->update($adData);
            }

            // Update caishha ad specific fields
            $caishhaAdData = $request->only(['offers_window_period', 'sellers_visibility_period']);
            if (!empty($caishhaAdData) && $ad->caishhaAd) {
                $ad->caishhaAd->update($caishhaAdData);
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

            DB::commit();

            // Load relationships for response
            $ad->load(['caishhaAd', 'user', 'brand', 'model', 'city', 'country', 'category', 'media']);

            Log::info('Caishha ad updated successfully', [
                'ad_id' => $ad->id,
                'user_id' => auth()->id(),
                'updated_fields' => array_keys($request->validated())
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Ad updated successfully',
                'data' => new CaishhaAdResource($ad)
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Caishha ad update failed', [
                'error' => $e->getMessage(),
                'ad_id' => $ad->id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to update ad',
                'errors' => ['general' => ['An unexpected error occurred while updating the ad']]
            ], 500);
        }
    }

    /**
     * Delete a Caishha ad
     */
    public function destroy($id): JsonResponse
    {
        $ad = Ad::where('type', 'caishha')->find($id);

        if (!$ad) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ad not found',
                'errors' => ['ad' => ['The requested Caishha ad does not exist']]
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
            DB::beginTransaction();

            // Remove media associations
            $ad->media()->detach();

            // Delete related caishha offers
            if ($ad->caishhaAd) {
                $ad->caishhaAd->offers()->delete();
                $ad->caishhaAd->delete();
            }

            // Delete the main ad record
            $ad->delete();

            DB::commit();

            Log::info('Caishha ad deleted successfully', [
                'ad_id' => $id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Ad deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Caishha ad deletion failed', [
                'error' => $e->getMessage(),
                'ad_id' => $id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to delete ad',
                'errors' => ['general' => ['An unexpected error occurred while deleting the ad']]
            ], 500);
        }
    }

    /**
     * Publish a Caishha ad
     */
    public function publish($id): JsonResponse
    {
        $ad = Ad::where('type', 'caishha')->find($id);
        
        if (!$ad) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ad not found',
                'errors' => ['ad' => ['Caishha ad not found']]
            ], 404);
        }

        // Authorization check
        if (auth()->id() !== $ad->user_id && !auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['You do not have permission to publish this ad']]
            ], 403);
        }

        try {
            DB::beginTransaction();

            $ad->update([
                'status' => 'published',
                'published_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();

            Log::info('Caishha ad published', ['ad_id' => $ad->id, 'user_id' => auth()->id()]);

            return response()->json([
                'status' => 'success',
                'message' => 'Ad published successfully',
                'data' => new CaishhaAdResource($ad->load(['caishhaAd', 'user', 'brand', 'model', 'city', 'country', 'category', 'media']))
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error publishing Caishha ad: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to publish ad',
                'errors' => ['server' => ['An error occurred while publishing the ad']]
            ], 500);
        }
    }

    /**
     * Unpublish a Caishha ad (set to draft)
     */
    public function unpublish($id): JsonResponse
    {
        $ad = Ad::where('type', 'caishha')->find($id);
        
        if (!$ad) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ad not found',
                'errors' => ['ad' => ['Caishha ad not found']]
            ], 404);
        }

        // Authorization check
        if (auth()->id() !== $ad->user_id && !auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['You do not have permission to unpublish this ad']]
            ], 403);
        }

        try {
            DB::beginTransaction();

            $ad->update([
                'status' => 'draft',
                'updated_at' => now()
            ]);

            DB::commit();

            Log::info('Caishha ad unpublished', ['ad_id' => $ad->id, 'user_id' => auth()->id()]);

            return response()->json([
                'status' => 'success',
                'message' => 'Ad unpublished successfully',
                'data' => new CaishhaAdResource($ad->load(['caishhaAd', 'user', 'brand', 'model', 'city', 'country', 'category', 'media']))
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error unpublishing Caishha ad: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to unpublish ad',
                'errors' => ['server' => ['An error occurred while unpublishing the ad']]
            ], 500);
        }
    }

    /**
     * Expire a Caishha ad
     */
    public function expire($id): JsonResponse
    {
        $ad = Ad::where('type', 'caishha')->find($id);
        
        if (!$ad) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ad not found',
                'errors' => ['ad' => ['Caishha ad not found']]
            ], 404);
        }

        // Authorization check
        if (auth()->id() !== $ad->user_id && !auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['You do not have permission to expire this ad']]
            ], 403);
        }

        try {
            DB::beginTransaction();

            $ad->update([
                'status' => 'expired',
                'expired_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();

            Log::info('Caishha ad expired', ['ad_id' => $ad->id, 'user_id' => auth()->id()]);

            return response()->json([
                'status' => 'success',
                'message' => 'Ad expired successfully',
                'data' => new CaishhaAdResource($ad->load(['caishhaAd', 'user', 'brand', 'model', 'city', 'country', 'category', 'media']))
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error expiring Caishha ad: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to expire ad',
                'errors' => ['server' => ['An error occurred while expiring the ad']]
            ], 500);
        }
    }

    /**
     * Archive a Caishha ad (set to removed)
     */
    public function archive($id): JsonResponse
    {
        $ad = Ad::where('type', 'caishha')->find($id);
        
        if (!$ad) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ad not found',
                'errors' => ['ad' => ['Caishha ad not found']]
            ], 404);
        }

        // Authorization check
        if (auth()->id() !== $ad->user_id && !auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['You do not have permission to archive this ad']]
            ], 403);
        }

        try {
            DB::beginTransaction();

            $ad->update([
                'status' => 'removed',
                'archived_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();

            Log::info('Caishha ad archived', ['ad_id' => $ad->id, 'user_id' => auth()->id()]);

            return response()->json([
                'status' => 'success',
                'message' => 'Ad archived successfully',
                'data' => new CaishhaAdResource($ad->load(['caishhaAd', 'user', 'brand', 'model', 'city', 'country', 'category', 'media']))
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error archiving Caishha ad: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to archive ad',
                'errors' => ['server' => ['An error occurred while archiving the ad']]
            ], 500);
        }
    }

    /**
     * Restore a Caishha ad (set to draft)
     */
    public function restore($id): JsonResponse
    {
        $ad = Ad::withTrashed()->where('type', 'caishha')->find($id);
        
        if (!$ad) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ad not found',
                'errors' => ['ad' => ['Caishha ad not found']]
            ], 404);
        }

        // Authorization check
        if (auth()->id() !== $ad->user_id && !auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['You do not have permission to restore this ad']]
            ], 403);
        }

        try {
            DB::beginTransaction();

            // If soft deleted, restore it
            if ($ad->trashed()) {
                $ad->restore();
            }

            $ad->update([
                'status' => 'draft',
                'archived_at' => null,
                'updated_at' => now()
            ]);

            DB::commit();

            Log::info('Caishha ad restored', ['ad_id' => $ad->id, 'user_id' => auth()->id()]);

            return response()->json([
                'status' => 'success',
                'message' => 'Ad restored successfully',
                'data' => new CaishhaAdResource($ad->load(['caishhaAd', 'user', 'brand', 'model', 'city', 'country', 'category', 'media']))
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error restoring Caishha ad: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to restore ad',
                'errors' => ['server' => ['An error occurred while restoring the ad']]
            ], 500);
        }
    }

    /**
     * Get global Caishha ad statistics (admin only)
     */
    public function globalStats(): JsonResponse
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['Only admins can access global statistics']]
            ], 403);
        }

        $stats = [
            'total_ads' => Ad::where('type', 'caishha')->count(),
            'published_ads' => Ad::where('type', 'caishha')->where('status', 'published')->count(),
            'draft_ads' => Ad::where('type', 'caishha')->where('status', 'draft')->count(),
            'pending_ads' => Ad::where('type', 'caishha')->where('status', 'pending')->count(),
            'expired_ads' => Ad::where('type', 'caishha')->where('status', 'expired')->count(),
            'removed_ads' => Ad::where('type', 'caishha')->where('status', 'removed')->count(),
            'total_views' => Ad::where('type', 'caishha')->sum('views_count'),
            'total_offers' => CaishhaOffer::count(),
            'pending_offers' => CaishhaOffer::where('status', 'pending')->count(),
            'accepted_offers' => CaishhaOffer::where('status', 'accepted')->count(),
            'rejected_offers' => CaishhaOffer::where('status', 'rejected')->count(),
            'ads_today' => Ad::where('type', 'caishha')->whereDate('created_at', today())->count(),
            'ads_this_week' => Ad::where('type', 'caishha')->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'ads_this_month' => Ad::where('type', 'caishha')->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count()
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'Global statistics retrieved successfully',
            'data' => $stats
        ]);
    }

    /**
     * Bulk operations (admin only)
     */
    public function bulkAction(Request $request): JsonResponse
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['Only admins can perform bulk operations']]
            ], 403);
        }

        $request->validate([
            'action' => 'required|in:publish,unpublish,expire,archive,restore,delete',
            'ad_ids' => 'required|array|min:1',
            'ad_ids.*' => 'integer|exists:ads,id'
        ]);

        try {
            DB::beginTransaction();

            $ads = Ad::whereIn('id', $request->ad_ids)->where('type', 'caishha')->get();
            
            if ($ads->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'No valid ads found',
                    'errors' => ['ads' => ['No valid Caishha ads found for the provided IDs']]
                ], 404);
            }

            $updated = 0;
            foreach ($ads as $ad) {
                switch ($request->action) {
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
                        $ad->update(['status' => 'removed', 'archived_at' => now()]);
                        break;
                    case 'restore':
                        $ad->update(['status' => 'draft', 'archived_at' => null]);
                        break;
                    case 'delete':
                        $ad->media()->detach();
                        if ($ad->caishhaAd) {
                            $ad->caishhaAd->offers()->delete();
                            $ad->caishhaAd->delete();
                        }
                        $ad->delete();
                        break;
                }
                $updated++;
            }

            DB::commit();

            Log::info('Bulk action on Caishha ads', [
                'action' => $request->action,
                'count' => $updated,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => "Bulk {$request->action} completed successfully",
                'data' => ['affected_count' => $updated]
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Bulk action on Caishha ads failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to perform bulk action',
                'errors' => ['general' => ['An unexpected error occurred']]
            ], 500);
        }
    }

    // ==================== OFFERS MANAGEMENT ====================

    /**
     * Submit an offer on a Caishha ad
     */
    public function submitOffer(SubmitCaishhaOfferRequest $request, $id): JsonResponse
    {
        $ad = Ad::where('type', 'caishha')->with('caishhaAd')->find($id);
        
        if (!$ad) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ad not found',
                'errors' => ['ad' => ['Caishha ad not found']]
            ], 404);
        }

        try {
            DB::beginTransaction();

            $offer = CaishhaOffer::create([
                'ad_id' => $ad->id,
                'user_id' => auth()->id(),
                'price' => $request->price,
                'comment' => $request->comment,
                'status' => 'pending',
                'is_visible_to_seller' => false,
            ]);

            // Increment offers count
            $ad->caishhaAd->increment('offers_count');

            DB::commit();

            Log::info('Caishha offer submitted', [
                'offer_id' => $offer->id,
                'ad_id' => $ad->id,
                'user_id' => auth()->id(),
                'price' => $offer->price
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Offer submitted successfully',
                'data' => new CaishhaOfferResource($offer->load('user'))
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Caishha offer submission failed', [
                'error' => $e->getMessage(),
                'ad_id' => $id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to submit offer',
                'errors' => ['general' => ['An unexpected error occurred while submitting the offer']]
            ], 500);
        }
    }

    /**
     * List offers on a Caishha ad (for ad owner or admin)
     */
    public function listOffers(Request $request, $id): JsonResponse
    {
        $ad = Ad::where('type', 'caishha')->with('caishhaAd')->find($id);
        
        if (!$ad) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ad not found',
                'errors' => ['ad' => ['Caishha ad not found']]
            ], 404);
        }

        $isOwner = auth()->id() === $ad->user_id;
        $isAdmin = auth()->user()->isAdmin();

        if (!$isOwner && !$isAdmin) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['Only the ad owner or admin can view offers']]
            ], 403);
        }

        // Check visibility period for owner (admin can always see)
        if ($isOwner && !$isAdmin && !$ad->caishhaAd->areOffersVisibleToSeller()) {
            $visibilityEndsAt = $ad->caishhaAd->getVisibilityPeriodEndsAt();
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Offers not yet visible',
                'errors' => [
                    'visibility' => ['Offers will be visible after the visibility period ends'],
                    'visibility_ends_at' => $visibilityEndsAt?->toISOString()
                ]
            ], 403);
        }

        $query = $ad->caishhaAd->offers()->with('user');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Sort options
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        if (in_array($sortBy, ['created_at', 'price', 'status'])) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $limit = min($request->get('limit', 15), 50);
        $offers = $query->paginate($limit);

        return response()->json([
            'status' => 'success',
            'data' => CaishhaOfferResource::collection($offers)->response()->getData(true)
        ]);
    }

    /**
     * Accept an offer on a Caishha ad
     */
    public function acceptOffer(AcceptCaishhaOfferRequest $request, $id, $offerId): JsonResponse
    {
        $ad = Ad::where('type', 'caishha')->with('caishhaAd')->find($id);
        
        if (!$ad) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ad not found',
                'errors' => ['ad' => ['Caishha ad not found']]
            ], 404);
        }

        $offer = CaishhaOffer::where('ad_id', $ad->id)->find($offerId);
        
        if (!$offer) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Offer not found',
                'errors' => ['offer' => ['The specified offer does not exist']]
            ], 404);
        }

        try {
            DB::beginTransaction();

            // Accept this offer
            $offer->accept();

            // Reject all other pending offers
            CaishhaOffer::where('ad_id', $ad->id)
                ->where('id', '!=', $offerId)
                ->where('status', 'pending')
                ->update(['status' => 'rejected']);

            // Mark all offers as visible to seller
            CaishhaOffer::where('ad_id', $ad->id)
                ->update(['is_visible_to_seller' => true]);

            DB::commit();

            Log::info('Caishha offer accepted', [
                'offer_id' => $offer->id,
                'ad_id' => $ad->id,
                'user_id' => auth()->id(),
                'accepted_price' => $offer->price
            ]);

            // TODO: Send notification to offer maker

            return response()->json([
                'status' => 'success',
                'message' => 'Offer accepted successfully',
                'data' => new CaishhaOfferResource($offer->fresh()->load('user'))
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Caishha offer acceptance failed', [
                'error' => $e->getMessage(),
                'offer_id' => $offerId,
                'ad_id' => $id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to accept offer',
                'errors' => ['general' => ['An unexpected error occurred while accepting the offer']]
            ], 500);
        }
    }

    /**
     * Reject an offer on a Caishha ad
     */
    public function rejectOffer(Request $request, $id, $offerId): JsonResponse
    {
        $ad = Ad::where('type', 'caishha')->with('caishhaAd')->find($id);
        
        if (!$ad) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ad not found',
                'errors' => ['ad' => ['Caishha ad not found']]
            ], 404);
        }

        // Authorization check - owner or admin
        if (auth()->id() !== $ad->user_id && !auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['Only the ad owner can reject offers']]
            ], 403);
        }

        $offer = CaishhaOffer::where('ad_id', $ad->id)->find($offerId);
        
        if (!$offer) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Offer not found',
                'errors' => ['offer' => ['The specified offer does not exist']]
            ], 404);
        }

        if (!$offer->isPending()) {
            return response()->json([
                'status' => 'error',
                'code' => 422,
                'message' => 'Invalid offer status',
                'errors' => ['offer' => ['Only pending offers can be rejected']]
            ], 422);
        }

        try {
            $offer->reject();

            Log::info('Caishha offer rejected', [
                'offer_id' => $offer->id,
                'ad_id' => $ad->id,
                'user_id' => auth()->id()
            ]);

            // TODO: Send notification to offer maker

            return response()->json([
                'status' => 'success',
                'message' => 'Offer rejected successfully',
                'data' => new CaishhaOfferResource($offer->fresh()->load('user'))
            ]);

        } catch (\Exception $e) {
            Log::error('Caishha offer rejection failed', [
                'error' => $e->getMessage(),
                'offer_id' => $offerId,
                'ad_id' => $id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to reject offer',
                'errors' => ['general' => ['An unexpected error occurred while rejecting the offer']]
            ], 500);
        }
    }

    /**
     * Get user's own offers (offers they've made on Caishha ads)
     */
    public function myOffers(Request $request): JsonResponse
    {
        $query = CaishhaOffer::where('user_id', auth()->id())
            ->with(['ad', 'ad.brand', 'ad.model', 'ad.media']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Sort options
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        if (in_array($sortBy, ['created_at', 'price', 'status'])) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $limit = min($request->get('limit', 15), 50);
        $offers = $query->paginate($limit);

        return response()->json([
            'status' => 'success',
            'data' => CaishhaOfferResource::collection($offers)->response()->getData(true)
        ]);
    }

    /**
     * Get details of a specific offer
     */
    public function showOffer($offerId): JsonResponse
    {
        $offer = CaishhaOffer::with(['ad', 'ad.brand', 'ad.model', 'ad.media', 'user', 'caishhaAd'])
            ->find($offerId);

        if (!$offer) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Offer not found',
                'errors' => ['offer' => ['The specified offer does not exist']]
            ], 404);
        }

        $user = auth()->user();
        
        // Authorization: owner of offer, owner of ad, or admin
        $isOwner = $user && $offer->user_id === $user->id;
        $isAdOwner = $user && $offer->ad && $offer->ad->user_id === $user->id;
        $isAdmin = $user && method_exists($user, 'isAdmin') && $user->isAdmin();

        if (!$isOwner && !$isAdOwner && !$isAdmin) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['You do not have permission to view this offer']]
            ], 403);
        }

        return response()->json([
            'status' => 'success',
            'data' => new CaishhaOfferResource($offer)
        ]);
    }

    /**
     * Update an offer (price and/or comment)
     */
    public function updateOffer(UpdateCaishhaOfferRequest $request, $offerId): JsonResponse
    {
        $offer = CaishhaOffer::with(['ad', 'caishhaAd'])->find($offerId);

        if (!$offer) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Offer not found',
                'errors' => ['offer' => ['The specified offer does not exist']]
            ], 404);
        }

        try {
            DB::beginTransaction();

            $data = [];
            if ($request->filled('price')) {
                $data['price'] = $request->price;
            }
            if ($request->has('comment')) {
                $data['comment'] = $request->comment;
            }

            $offer->update($data);

            DB::commit();

            Log::info('Caishha offer updated', [
                'offer_id' => $offer->id,
                'ad_id' => $offer->ad_id,
                'user_id' => auth()->id(),
                'updated_fields' => array_keys($data)
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Offer updated successfully',
                'data' => new CaishhaOfferResource($offer->fresh()->load('user', 'ad'))
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Caishha offer update failed', [
                'error' => $e->getMessage(),
                'offer_id' => $offerId,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to update offer',
                'errors' => ['general' => ['An unexpected error occurred while updating the offer']]
            ], 500);
        }
    }

    /**
     * Delete/withdraw an offer
     */
    public function deleteOffer(DeleteCaishhaOfferRequest $request, $offerId): JsonResponse
    {
        $offer = CaishhaOffer::with(['ad', 'caishhaAd'])->find($offerId);

        if (!$offer) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Offer not found',
                'errors' => ['offer' => ['The specified offer does not exist']]
            ], 404);
        }

        // Only allow deletion of pending offers
        if (!$offer->isPending()) {
            return response()->json([
                'status' => 'error',
                'code' => 422,
                'message' => 'Cannot delete offer',
                'errors' => ['offer' => ['Only pending offers can be deleted']]
            ], 422);
        }

        try {
            DB::beginTransaction();

            $adId = $offer->ad_id;
            $offerId = $offer->id;

            $offer->delete();

            // Decrement offers count on the Caishha ad
            $caishhaAd = CaishhaAd::where('ad_id', $adId)->first();
            if ($caishhaAd) {
                $caishhaAd->decrementOffersCount();
            }

            DB::commit();

            Log::info('Caishha offer deleted', [
                'offer_id' => $offerId,
                'ad_id' => $adId,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Offer withdrawn successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Caishha offer deletion failed', [
                'error' => $e->getMessage(),
                'offer_id' => $offerId,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to delete offer',
                'errors' => ['general' => ['An unexpected error occurred while deleting the offer']]
            ], 500);
        }
    }
}

