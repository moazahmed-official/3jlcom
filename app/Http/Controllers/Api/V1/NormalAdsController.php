<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNormalAdRequest;
use App\Http\Requests\UpdateNormalAdRequest;
use App\Http\Resources\NormalAdResource;
use App\Models\Ad;
use App\Models\NormalAd;
use App\Models\Media;
use App\Services\PackageFeatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NormalAdsController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Ad::where('type', 'normal')
            ->with(['normalAd', 'user', 'brand', 'model', 'city', 'country', 'category', 'media', 'specifications'])
            ->where('status', 'published'); // Only show published ads

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

        // Filter by price range
        if ($request->filled('min_price')) {
            $query->whereHas('normalAd', function ($q) use ($request) {
                $q->where('price_cash', '>=', $request->min_price);
            });
        }

        if ($request->filled('max_price')) {
            $query->whereHas('normalAd', function ($q) use ($request) {
                $q->where('price_cash', '<=', $request->max_price);
            });
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

        $limit = min($request->get('limit', 15), 50); // Max 50 per page
        $ads = $query->paginate($limit);

        return NormalAdResource::collection($ads);
    }

    /**
     * Get all ads for the authenticated user (all statuses)
     */
    public function myAds(Request $request)
    {
        $query = Ad::where('type', 'normal')
            ->where('user_id', auth()->id())
            ->with(['normalAd', 'user', 'brand', 'model', 'city', 'country', 'category', 'media', 'specifications']);

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

        return NormalAdResource::collection($ads);
    }

    /**
     * Get all ads for admin (all statuses, all users)
     */
    public function adminIndex(Request $request)
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

        $query = Ad::where('type', 'normal')
            ->with(['normalAd', 'user', 'brand', 'model', 'city', 'country', 'category', 'media', 'specifications']);

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

        return NormalAdResource::collection($ads);
    }

    public function store(StoreNormalAdRequest $request, PackageFeatureService $packageService): JsonResponse
    {
        try {
            DB::beginTransaction();

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

            // Validate ad creation limit
            $adValidation = $packageService->validateAdCreation($user, 'normal');
            if (!$adValidation['allowed']) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'code' => 403,
                    'message' => $adValidation['reason'],
                    'errors' => ['package' => [$adValidation['reason']]],
                    'remaining' => $adValidation['remaining']
                ], 403);
            }

            // Validate media limits
            if ($request->has('media_ids') && !empty($request->media_ids)) {
                $imageCount = Media::whereIn('id', $request->media_ids)->where('type', 'image')->count();
                $videoCount = Media::whereIn('id', $request->media_ids)->where('type', 'video')->count();
                
                $mediaValidation = $packageService->validateMediaLimits($user, $imageCount, $videoCount);
                if (!$mediaValidation['allowed']) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'code' => 403,
                        'message' => $mediaValidation['reason'],
                        'errors' => ['media' => [$mediaValidation['reason']]]
                    ], 403);
                }
            }

            // Create the main ad record
            $ad = Ad::create([
                'user_id' => $userId,
                'type' => 'normal',
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
                'status' => 'published', // Publish immediately
                'period_days' => 30, // Default period
            ]);

            // Create the normal ad specific record
            NormalAd::create([
                'ad_id' => $ad->id,
                'price_cash' => $request->price_cash,
                'installment_id' => $request->installment_id,
                'start_time' => now(),
                'update_time' => now(),
            ]);

            // Attach media if provided
            if ($request->has('media_ids') && !empty($request->media_ids)) {
                $ad->media()->sync($request->media_ids);
                
                // Update media to mark them as associated with ads
                \App\Models\Media::whereIn('id', $request->media_ids)
                    ->update([
                        'related_resource' => 'ads',
                        'related_id' => $ad->id
                    ]);
            }

            // Store specifications if provided
            if ($request->has('specifications') && is_array($request->specifications)) {
                foreach ($request->specifications as $spec) {
                    if (isset($spec['specification_id']) && isset($spec['value'])) {
                        \App\Models\AdSpecification::create([
                            'ad_id' => $ad->id,
                            'specification_id' => $spec['specification_id'],
                            'value' => $spec['value'],
                        ]);
                    }
                }
            }

            DB::commit();

            // Load relationships for response
            $ad->load(['normalAd', 'user', 'brand', 'model', 'city', 'country', 'category', 'media', 'specifications']);

            Log::info('Normal ad created successfully', [
                'ad_id' => $ad->id,
                'user_id' => auth()->id(),
                'title' => $ad->title
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Ad created successfully',
                'data' => new NormalAdResource($ad)
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Normal ad creation failed', [
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

    public function show($id): JsonResponse
    {
        $ad = Ad::where('type', 'normal')
            ->with(['normalAd', 'user', 'brand', 'model', 'city', 'country', 'category', 'media', 'specifications'])
            ->find($id);

        if (!$ad) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ad not found',
                'errors' => ['ad' => ['The requested ad does not exist']]
            ], 404);
        }

        // Increment view count if not viewing own ad
        if (!auth()->check() || auth()->id() !== $ad->user_id) {
            $ad->increment('views_count');
        }

        return response()->json([
            'status' => 'success',
            'data' => new NormalAdResource($ad)
        ]);
    }

    public function update(UpdateNormalAdRequest $request, $id): JsonResponse
    {
        $ad = Ad::where('type', 'normal')->find($id);

        if (!$ad) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ad not found',
                'errors' => ['ad' => ['The requested ad does not exist']]
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
                'contact_phone', 'whatsapp_number', 'status'
            ]);
            
            if (!empty($adData)) {
                $ad->update($adData);
            }

            // Update normal ad specific fields
            $normalAdData = $request->only(['price_cash', 'installment_id']);
            if (!empty($normalAdData)) {
                $normalAdData['update_time'] = now();
                $ad->normalAd->update($normalAdData);
            }

            // Update media associations if provided
            if ($request->has('media_ids')) {
                // Remove old media associations
                $ad->media()->sync([]);
                
                // Add new media associations
                if (!empty($request->media_ids)) {
                    $ad->media()->sync($request->media_ids);
                    
                    // Update media to mark them as associated with this ad
                    \App\Models\Media::whereIn('id', $request->media_ids)
                        ->update([
                            'related_resource' => 'ads',
                            'related_id' => $ad->id
                        ]);
                }
            }

            // Update specifications if provided
            if ($request->has('specifications')) {
                // Delete old specifications
                $ad->adSpecifications()->delete();
                
                // Create new ones
                if (is_array($request->specifications)) {
                    foreach ($request->specifications as $spec) {
                        if (isset($spec['specification_id']) && isset($spec['value'])) {
                            \App\Models\AdSpecification::create([
                                'ad_id' => $ad->id,
                                'specification_id' => $spec['specification_id'],
                                'value' => $spec['value'],
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            // Load relationships for response
            $ad->load(['normalAd', 'user', 'brand', 'model', 'city', 'country', 'category', 'media', 'specifications']);

            Log::info('Normal ad updated successfully', [
                'ad_id' => $ad->id,
                'user_id' => auth()->id(),
                'updated_fields' => array_keys($request->validated())
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Ad updated successfully',
                'data' => new NormalAdResource($ad)
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Normal ad update failed', [
                'error' => $e->getMessage(),
                'ad_id' => $ad->id,
                'user_id' => auth()->id(),
                'request_data' => $request->validated()
            ]);

            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to update ad',
                'errors' => ['general' => ['An unexpected error occurred while updating the ad']]
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        $ad = Ad::where('type', 'normal')->find($id);

        if (!$ad) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ad not found',
                'errors' => ['ad' => ['The requested ad does not exist']]
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

            // Delete related normal ad record
            $ad->normalAd?->delete();

            // Delete the main ad record
            $ad->delete();

            DB::commit();

            Log::info('Normal ad deleted successfully', [
                'ad_id' => $ad->id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Ad deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Normal ad deletion failed', [
                'error' => $e->getMessage(),
                'ad_id' => $ad->id,
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

    public function republish($id): JsonResponse
    {
        $ad = Ad::where('type', 'normal')->find($id);

        if (!$ad) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ad not found',
                'errors' => ['ad' => ['The requested ad does not exist']]
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
            $ad->normalAd?->update(['update_time' => now()]);

            // Set status to published if it was expired or draft
            if (in_array($ad->status, ['expired', 'draft', 'removed'])) {
                $ad->update(['status' => 'published']);
            }

            Log::info('Normal ad republished successfully', [
                'ad_id' => $ad->id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Ad republished successfully',
                'data' => ['republished_at' => now()->toISOString()]
            ]);

        } catch (\Exception $e) {
            Log::error('Normal ad republish failed', [
                'error' => $e->getMessage(),
                'ad_id' => $ad->id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to republish ad',
                'errors' => ['general' => ['An unexpected error occurred while republishing the ad']]
            ], 500);
        }
    }

    /**
     * Publish ad (set status to published)
     */
    public function publish($id): JsonResponse
    {
        $ad = Ad::find($id);
        
        if (!$ad || $ad->type !== 'normal') {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ad not found',
                'errors' => ['ad' => ['Ad not found or is not a normal ad']]
            ], 404);
        }

        // Authorization check - owner or admin can publish
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
                'updated_at' => now()
            ]);

            $ad->normalAd()->update([
                'update_time' => now()
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Ad published successfully',
                'data' => new NormalAdResource($ad->load(['normalAd', 'user', 'brand', 'model', 'city', 'country', 'category', 'media']))
            ], 200);
        } catch (Exception $e) {
            Log::error('Error publishing ad: ' . $e->getMessage());
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to publish ad',
                'errors' => ['server' => ['An error occurred while publishing the ad']]
            ], 500);
        }
    }

    /**
     * Unpublish ad (set status to draft)
     */
    public function unpublish($id): JsonResponse
    {
        $ad = Ad::find($id);
        
        if (!$ad || $ad->type !== 'normal') {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ad not found',
                'errors' => ['ad' => ['Ad not found or is not a normal ad']]
            ], 404);
        }

        // Authorization check - owner or admin can unpublish
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

            $ad->normalAd()->update([
                'update_time' => now()
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Ad unpublished successfully',
                'data' => new NormalAdResource($ad->load(['normalAd', 'user', 'brand', 'model', 'city', 'country', 'category', 'media']))
            ], 200);
        } catch (Exception $e) {
            Log::error('Error unpublishing ad: ' . $e->getMessage());
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to unpublish ad',
                'errors' => ['server' => ['An error occurred while unpublishing the ad']]
            ], 500);
        }
    }

    /**
     * Expire ad (set status to expired)
     */
    public function expire($id): JsonResponse
    {
        $ad = Ad::find($id);
        
        if (!$ad || $ad->type !== 'normal') {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ad not found',
                'errors' => ['ad' => ['Ad not found or is not a normal ad']]
            ], 404);
        }

        // Authorization check - owner or admin can expire
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
                'updated_at' => now()
            ]);

            $ad->normalAd()->update([
                'update_time' => now()
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Ad expired successfully',
                'data' => new NormalAdResource($ad->load(['normalAd', 'user', 'brand', 'model', 'city', 'country', 'category', 'media']))
            ], 200);
        } catch (Exception $e) {
            Log::error('Error expiring ad: ' . $e->getMessage());
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to expire ad',
                'errors' => ['server' => ['An error occurred while expiring the ad']]
            ], 500);
        }
    }

    /**
     * Archive ad (set status to removed)
     */
    public function archive($id): JsonResponse
    {
        $ad = Ad::find($id);
        
        if (!$ad || $ad->type !== 'normal') {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ad not found',
                'errors' => ['ad' => ['Ad not found or is not a normal ad']]
            ], 404);
        }

        // Authorization check - owner or admin can archive
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
                'updated_at' => now()
            ]);

            $ad->normalAd()->update([
                'update_time' => now()
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Ad archived successfully',
                'data' => new NormalAdResource($ad->load(['normalAd', 'user', 'brand', 'model', 'city', 'country', 'category', 'media']))
            ], 200);
        } catch (Exception $e) {
            Log::error('Error archiving ad: ' . $e->getMessage());
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to archive ad',
                'errors' => ['server' => ['An error occurred while archiving the ad']]
            ], 500);
        }
    }

    /**
     * Restore ad (set status to draft)
     */
    public function restore($id): JsonResponse
    {
        $ad = Ad::withTrashed()->find($id);
        
        if (!$ad || $ad->type !== 'normal') {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ad not found',
                'errors' => ['ad' => ['Ad not found or is not a normal ad']]
            ], 404);
        }

        // Authorization check - owner or admin can restore
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
                'updated_at' => now()
            ]);

            $ad->normalAd()->update([
                'update_time' => now()
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Ad restored successfully',
                'data' => new NormalAdResource($ad->load(['normalAd', 'user', 'brand', 'model', 'city', 'country', 'category', 'media']))
            ], 200);
        } catch (Exception $e) {
            Log::error('Error restoring ad: ' . $e->getMessage());
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to restore ad',
                'errors' => ['server' => ['An error occurred while restoring the ad']]
            ], 500);
        }
    }

    /**
     * Get ad statistics
     */
    public function stats($id): JsonResponse
    {
        $ad = Ad::find($id);
        
        if (!$ad || $ad->type !== 'normal') {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ad not found',
                'errors' => ['ad' => ['Ad not found or is not a normal ad']]
            ], 404);
        }

        // Authorization check - owner or admin can view stats
        if (auth()->id() !== $ad->user_id && !auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['You do not have permission to view this ad\'s statistics']]
            ], 403);
        }

        $stats = [
            'views' => $ad->views_count ?? 0,
            'contacts' => 0, // TODO: Implement contact tracking
            'impressions' => 0, // TODO: Implement impression tracking
            'created_at' => $ad->created_at,
            'last_updated' => $ad->updated_at,
            'status' => $ad->status
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'Ad statistics retrieved successfully',
            'data' => $stats
        ], 200);
    }

    /**
     * Get global ad statistics (admin only)
     */
    public function globalStats(): JsonResponse
    {
        // Check if user is admin
        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['Only admins can access global statistics']]
            ], 403);
        }

        $stats = [
            'total_ads' => Ad::where('type', 'normal')->count(),
            'published_ads' => Ad::where('type', 'normal')->where('status', 'published')->count(),
            'draft_ads' => Ad::where('type', 'normal')->where('status', 'draft')->count(),
            'pending_ads' => Ad::where('type', 'normal')->where('status', 'pending')->count(),
            'expired_ads' => Ad::where('type', 'normal')->where('status', 'expired')->count(),
            'removed_ads' => Ad::where('type', 'normal')->where('status', 'removed')->count(),
            'total_views' => Ad::where('type', 'normal')->sum('views_count'),
            'ads_today' => Ad::where('type', 'normal')->whereDate('created_at', today())->count(),
            'ads_this_week' => Ad::where('type', 'normal')->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'ads_this_month' => Ad::where('type', 'normal')->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count()
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'Global statistics retrieved successfully',
            'data' => $stats
        ], 200);
    }

    /**
     * Export admin normal ads to CSV
     * GET /api/normal-ads/admin/export
     */
    public function export(Request $request)
    {
        if (!auth()->user() || !auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['Only admins can export data']]
            ], 403);
        }

        $query = Ad::where('type', 'normal')
            ->with(['normalAd', 'user', 'brand', 'model', 'city', 'country']);

        // Apply same filters as adminIndex
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }
        if ($request->filled('model_id')) {
            $query->where('model_id', $request->model_id);
        }
        if ($request->filled('city_id')) {
            $query->where('city_id', $request->city_id);
        }
        if ($request->filled('country_id')) {
            $query->where('country_id', $request->country_id);
        }
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                  ->orWhere('description', 'like', "%{$searchTerm}%");
            });
        }

        $ads = $query->orderBy('created_at', 'desc')->get();

        $columns = [
            'id','title','price_cash','status','brand','model','year','city','country','views_count','contact_count','created_at'
        ];

        $lines = [];
        $lines[] = implode(',', $columns);

        foreach ($ads as $ad) {
            $price = $ad->normalAd->price_cash ?? '';
            $brand = $ad->brand->name_en ?? '';
            $model = $ad->model->name_en ?? '';
            $year = $ad->year ?? '';
            $city = $ad->city->name ?? ($ad->city->name_en ?? '');
            $country = $ad->country->name ?? ($ad->country->name_en ?? '');
            $views = $ad->views_count ?? 0;
            $contacts = $ad->contact_count ?? 0;
            $created = $ad->created_at?->toDateTimeString() ?? '';

            $row = [
                $ad->id,
                '"' . str_replace('"', '""', $ad->title) . '"',
                $price,
                $ad->status,
                '"' . str_replace('"', '""', $brand) . '"',
                '"' . str_replace('"', '""', $model) . '"',
                $year,
                '"' . str_replace('"', '""', $city) . '"',
                '"' . str_replace('"', '""', $country) . '"',
                $views,
                $contacts,
                $created,
            ];

            $lines[] = implode(',', $row);
        }

        $csv = implode("\n", $lines);

        $filename = 'normal-ads-export-' . now()->format('Y-m-d') . '.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ]);
    }

    /**
     * List public ads by user
     */
    public function listByUser($userId): AnonymousResourceCollection
    {
        $query = Ad::where('type', 'normal')
            ->where('user_id', $userId)
            ->where('status', 'published') // Only show published ads publicly
            ->with(['normalAd', 'user', 'brand', 'model', 'city', 'country', 'category', 'media']);

        // Sort by created_at desc by default
        $query->orderBy('created_at', 'desc');

        $limit = min(request()->get('limit', 15), 50);
        $ads = $query->paginate($limit);

        return NormalAdResource::collection($ads);
    }

    /**
     * Add ad to favorites
     */
    public function favorite($id): JsonResponse
    {
        $ad = Ad::find($id);
        
        if (!$ad || $ad->type !== 'normal') {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ad not found',
                'errors' => ['ad' => ['Ad not found or is not a normal ad']]
            ], 404);
        }

        $user = auth()->user();

        // Check if already favorited
        if ($user->favorites()->where('ad_id', $ad->id)->exists()) {
            return response()->json([
                'status' => 'error',
                'code' => 409,
                'message' => 'Ad already in favorites',
                'errors' => ['favorite' => ['This ad is already in your favorites']]
            ], 409);
        }

        $user->favorites()->attach($ad->id, ['created_at' => now(), 'updated_at' => now()]);

        return response()->json([
            'status' => 'success',
            'message' => 'Ad added to favorites successfully'
        ], 200);
    }

    /**
     * Remove ad from favorites
     */
    public function unfavorite($id): JsonResponse
    {
        $ad = Ad::find($id);
        
        if (!$ad || $ad->type !== 'normal') {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ad not found',
                'errors' => ['ad' => ['Ad not found or is not a normal ad']]
            ], 404);
        }

        $user = auth()->user();

        // Check if not favorited
        if (!$user->favorites()->where('ad_id', $ad->id)->exists()) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ad not in favorites',
                'errors' => ['favorite' => ['This ad is not in your favorites']]
            ], 404);
        }

        $user->favorites()->detach($ad->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Ad removed from favorites successfully'
        ], 200);
    }

    /**
     * Contact seller
     */
    public function contactSeller($id): JsonResponse
    {
        $ad = Ad::find($id);
        
        if (!$ad || $ad->type !== 'normal') {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ad not found',
                'errors' => ['ad' => ['Ad not found or is not a normal ad']]
            ], 404);
        }

        if ($ad->status !== 'published') {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Ad not available for contact',
                'errors' => ['ad' => ['This ad is not published and cannot be contacted']]
            ], 403);
        }

        // TODO: Implement contact tracking and rate limiting
        // TODO: Send notification to seller

        $contactInfo = [
            'seller_name' => $ad->user->name,
            'contact_phone' => $ad->contact_phone,
            'whatsapp_number' => $ad->whatsapp_number,
            'ad_title' => $ad->title
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'Contact information retrieved successfully',
            'data' => $contactInfo
        ], 200);
    }

    /**
     * Bulk operations (admin only)
     */
    public function bulkAction(Request $request): JsonResponse
    {
        // Check if user is admin
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

            $ads = Ad::whereIn('id', $request->ad_ids)->where('type', 'normal')->get();
            
            if ($ads->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'No valid ads found',
                    'errors' => ['ads' => ['No valid normal ads found for the provided IDs']]
                ], 404);
            }

            $updated = 0;
            foreach ($ads as $ad) {
                switch ($request->action) {
                    case 'publish':
                        $ad->update(['status' => 'published']);
                        break;
                    case 'unpublish':
                        $ad->update(['status' => 'draft']);
                        break;
                    case 'expire':
                        $ad->update(['status' => 'expired']);
                        break;
                    case 'archive':
                        $ad->update(['status' => 'removed']);
                        break;
                    case 'restore':
                        if ($ad->trashed()) {
                            $ad->restore();
                        }
                        $ad->update(['status' => 'draft']);
                        break;
                    case 'delete':
                        $ad->delete();
                        break;
                }
                
                if (in_array($request->action, ['publish', 'unpublish', 'expire', 'archive', 'restore'])) {
                    $ad->normalAd()->update(['update_time' => now()]);
                }
                
                $updated++;
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => "Bulk {$request->action} completed successfully",
                'data' => [
                    'action' => $request->action,
                    'updated_count' => $updated
                ]
            ], 200);
        } catch (Exception $e) {
            Log::error('Error in bulk action: ' . $e->getMessage());
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to perform bulk action',
                'errors' => ['server' => ['An error occurred while performing the bulk action']]
            ], 500);
        }
    }

    /**
     * List authenticated user's favorite normal ads
     */
    public function favorites(Request $request)
    {
        $user = auth()->user();

        $favoriteIds = $user->favorites()->pluck('ad_id')->toArray();

        $query = Ad::where('type', 'normal')
            ->whereIn('id', $favoriteIds)
            ->with(['normalAd', 'user', 'brand', 'model', 'city', 'country', 'category', 'media'])
            ->orderBy('created_at', 'desc');

        $limit = min($request->get('limit', 15), 50);
        $ads = $query->paginate($limit);

        return NormalAdResource::collection($ads);
    }

    /**
     * Convert normal ad to unique ad
     */
    public function convertToUnique($id, Request $request): JsonResponse
    {
        $ad = Ad::where('type', 'normal')->find($id);

        if (!$ad) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ad not found',
                'errors' => ['ad' => ['The requested normal ad does not exist']]
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
                // Delete normal ad specific record
                $ad->normalAd?->delete();

                // Change type to unique
                $ad->update(['type' => 'unique']);

                // Validate banner_image_id; ensure referenced media exists
                $bannerImageId = $request->get('banner_image_id');
                if ($bannerImageId) {
                    $mediaExists = \App\Models\Media::where('id', $bannerImageId)->exists();
                    if (! $mediaExists) {
                        $bannerImageId = null;
                    }
                }

                // Create unique ad record
                \App\Models\UniqueAd::create([
                    'ad_id' => $ad->id,
                    'banner_image_id' => $bannerImageId,
                    'banner_color' => $request->get('banner_color', '#FFFFFF'),
                    'is_auto_republished' => $request->boolean('is_auto_republished', false),
                    'is_verified_ad' => false,
                    'is_featured' => false
                ]);
            });

            $ad->load(['uniqueAd', 'uniqueAd.bannerImage', 'user', 'brand', 'model', 'city', 'country', 'category', 'media']);

            Log::info('Normal ad converted to unique', [
                'ad_id' => $ad->id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Ad converted to unique ad successfully',
                'data' => [
                    'id' => $ad->id,
                    'type' => 'unique',
                    'title' => $ad->title
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to convert normal ad to unique', [
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
