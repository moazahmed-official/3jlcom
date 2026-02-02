<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\FavoriteResource;
use App\Models\Ad;
use App\Models\Favorite;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FavoriteController extends BaseApiController
{
    use AuthorizesRequests;

    /**
     * List user's favorites.
     * 
     * GET /api/v1/favorites
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        
        $favorites = Favorite::with(['ad.brand', 'ad.city', 'ad.country', 'ad.media', 'ad.user'])
            ->byUser(auth()->id())
            ->latest()
            ->paginate($perPage);

        return $this->successPaginated(
            FavoriteResource::collection($favorites),
            'Favorites retrieved successfully'
        );
    }

    /**
     * Add ad to favorites.
     * 
     * POST /api/v1/favorites/{ad}
     */
    public function store(Ad $ad): JsonResponse
    {
        try {
            // Check if already favorited
            $existing = Favorite::byUser(auth()->id())
                ->byAd($ad->id)
                ->first();

            if ($existing) {
                return $this->error(422, 'Ad is already in favorites');
            }

            $favorite = Favorite::create([
                'user_id' => auth()->id(),
                'ad_id' => $ad->id,
            ]);

            $favorite->load(['ad.brand', 'ad.city', 'ad.country', 'ad.media', 'ad.user']);

            return $this->success(
                new FavoriteResource($favorite),
                'Ad added to favorites successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->error(500, 'Failed to add favorite: ' . $e->getMessage());
        }
    }

    /**
     * Remove ad from favorites.
     * 
     * DELETE /api/v1/favorites/{favorite}
     */
    public function destroy(Favorite $favorite): JsonResponse
    {
        try {
            // Ensure user owns this favorite
            if ($favorite->user_id !== auth()->id()) {
                return $this->error(403, 'Unauthorized to delete this favorite');
            }

            $favorite->delete();

            return $this->success(
                null,
                'Favorite removed successfully'
            );
        } catch (\Exception $e) {
            return $this->error(500, 'Failed to remove favorite: ' . $e->getMessage());
        }
    }

    /**
     * Remove ad from favorites by ad ID.
     * 
     * DELETE /api/v1/favorites/ad/{ad}
     */
    public function destroyByAd(Ad $ad): JsonResponse
    {
        try {
            $favorite = Favorite::byUser(auth()->id())
                ->byAd($ad->id)
                ->first();

            if (!$favorite) {
                return $this->error(404, 'Ad is not in favorites');
            }

            $favorite->delete();

            return $this->success(
                null,
                'Favorite removed successfully'
            );
        } catch (\Exception $e) {
            return $this->error(500, 'Failed to remove favorite: ' . $e->getMessage());
        }
    }

    /**
     * Check if ad is favorited by current user.
     * 
     * GET /api/v1/favorites/check/{ad}
     */
    public function check(Ad $ad): JsonResponse
    {
        $isFavorited = Favorite::byUser(auth()->id())
            ->byAd($ad->id)
            ->exists();

        return $this->success([
            'is_favorited' => $isFavorited,
            'ad_id' => $ad->id,
        ], 'Favorite status checked');
    }

    /**
     * Get favorites count for current user.
     * 
     * GET /api/v1/favorites/count
     */
    public function count(): JsonResponse
    {
        $count = Favorite::byUser(auth()->id())->count();

        return $this->success([
            'count' => $count,
        ], 'Favorites count retrieved');
    }

    /**
     * Toggle favorite status (add if not exists, remove if exists).
     * 
     * POST /api/v1/favorites/toggle/{ad}
     */
    public function toggle(Ad $ad): JsonResponse
    {
        try {
            $favorite = Favorite::byUser(auth()->id())
                ->byAd($ad->id)
                ->first();

            if ($favorite) {
                // Remove from favorites
                $favorite->delete();
                return $this->success([
                    'is_favorited' => false,
                    'ad_id' => $ad->id,
                ], 'Favorite removed successfully');
            } else {
                // Add to favorites
                $favorite = Favorite::create([
                    'user_id' => auth()->id(),
                    'ad_id' => $ad->id,
                ]);

                return $this->success([
                    'is_favorited' => true,
                    'ad_id' => $ad->id,
                    'favorite_id' => $favorite->id,
                ], 'Favorite added successfully', 201);
            }
        } catch (\Exception $e) {
            return $this->error(500, 'Failed to toggle favorite: ' . $e->getMessage());
        }
    }
}
