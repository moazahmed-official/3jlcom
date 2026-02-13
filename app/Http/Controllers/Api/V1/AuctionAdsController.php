<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAuctionAdRequest;
use App\Http\Requests\UpdateAuctionAdRequest;
use App\Http\Requests\PlaceBidRequest;
use App\Http\Requests\CloseAuctionRequest;
use App\Http\Resources\AuctionAdResource;
use App\Http\Resources\BidResource;
use App\Models\Ad;
use App\Models\Auction;
use App\Models\Bid;
use App\Models\Media;
use App\Services\PackageFeatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuctionAdsController extends Controller
{
    /**
     * List published auction ads (public)
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Ad::where('type', 'auction')
            ->with(['auction', 'user', 'brand', 'model', 'city', 'country', 'category', 'media', 'specifications'])
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

        // Filter by price range
        if ($request->filled('min_price')) {
            $query->whereHas('auction', function ($q) use ($request) {
                $q->where(function ($subQ) use ($request) {
                    $subQ->where('last_price', '>=', $request->min_price)
                         ->orWhere(function ($subSubQ) use ($request) {
                             $subSubQ->whereNull('last_price')
                                     ->where('start_price', '>=', $request->min_price);
                         });
                });
            });
        }

        if ($request->filled('max_price')) {
            $query->whereHas('auction', function ($q) use ($request) {
                $q->where(function ($subQ) use ($request) {
                    $subQ->where('last_price', '<=', $request->max_price)
                         ->orWhere(function ($subSubQ) use ($request) {
                             $subSubQ->whereNull('last_price')
                                     ->where('start_price', '<=', $request->max_price);
                         });
                });
            });
        }

        // Search by title or description
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                  ->orWhere('description', 'like', "%{$searchTerm}%");
            });
        }

        // Filter by auction status
        if ($request->filled('auction_status')) {
            $auctionStatus = $request->auction_status;
            if ($auctionStatus === 'active') {
                $query->whereHas('auction', function ($q) {
                    $q->where('status', 'active')
                      ->where('start_time', '<=', now())
                      ->where('end_time', '>', now());
                });
            } elseif ($auctionStatus === 'upcoming') {
                $query->whereHas('auction', function ($q) {
                    $q->where('status', 'active')
                      ->where('start_time', '>', now());
                });
            } elseif ($auctionStatus === 'ended') {
                $query->whereHas('auction', function ($q) {
                    $q->where('end_time', '<=', now());
                });
            } elseif ($auctionStatus === 'ending_soon') {
                $query->whereHas('auction', function ($q) {
                    $q->where('status', 'active')
                      ->where('end_time', '>', now())
                      ->where('end_time', '<=', now()->addHour());
                });
            }
        }

        // Sort options
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        if (in_array($sortBy, ['created_at', 'updated_at', 'views_count', 'title'])) {
            $query->orderBy($sortBy, $sortDirection);
        } elseif ($sortBy === 'end_time') {
            $query->join('auctions', 'ads.id', '=', 'auctions.ad_id')
                  ->orderBy('auctions.end_time', $sortDirection)
                  ->select('ads.*');
        } elseif ($sortBy === 'bid_count') {
            $query->join('auctions', 'ads.id', '=', 'auctions.ad_id')
                  ->orderBy('auctions.bid_count', $sortDirection)
                  ->select('ads.*');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $limit = min($request->get('limit', 15), 50);
        $ads = $query->paginate($limit);

        return AuctionAdResource::collection($ads);
    }

    /**
     * Get all auction ads for the authenticated user (all statuses)
     */
    public function myAds(Request $request): AnonymousResourceCollection
    {
        $query = Ad::where('type', 'auction')
            ->where('user_id', auth()->id())
            ->with(['auction', 'auction.bids', 'user', 'brand', 'model', 'city', 'country', 'category', 'media', 'specifications']);

        // Filter by status if provided
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by auction status
        if ($request->filled('auction_status')) {
            $query->whereHas('auction', function ($q) use ($request) {
                $q->where('status', $request->auction_status);
            });
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

        return AuctionAdResource::collection($ads);
    }

    /**
     * Get all auction ads for admin (all statuses, all users)
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

        $query = Ad::where('type', 'auction')
            ->with(['auction', 'auction.bids.user', 'user', 'brand', 'model', 'city', 'country', 'category', 'media', 'specifications']);

        // Filter by status if provided
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by auction status
        if ($request->filled('auction_status')) {
            $query->whereHas('auction', function ($q) use ($request) {
                $q->where('status', $request->auction_status);
            });
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

        return AuctionAdResource::collection($ads);
    }

    /**
     * Store a new auction ad
     */
    public function store(StoreAuctionAdRequest $request, PackageFeatureService $packageService): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Determine user_id - admin can create for other users
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
            $adValidation = $packageService->validateAdCreation($user, 'auction');
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
                'type' => 'auction',
                'title' => $request->title,
                'description' => $request->description,
                'category_id' => $request->category_id,
                'city_id' => $request->city_id,
                'country_id' => $request->country_id,
                'brand_id' => $request->brand_id,
                'model_id' => $request->model_id,
                'year' => $request->year,
                'color' => $request->color,
                'millage' => $request->millage,
                'contact_phone' => $request->contact_phone,
                'whatsapp_number' => $request->whatsapp_number,
                'status' => 'published',
                'published_at' => now(),
                'period_days' => $request->period_days ?? 30,
            ]);

            // Create the auction record
            Auction::create([
                'ad_id' => $ad->id,
                'start_price' => $request->start_price ?? 0,
                'reserve_price' => $request->reserve_price,
                'minimum_bid_increment' => $request->minimum_bid_increment ?? 100,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'auto_close' => $request->auto_close ?? true,
                'is_last_price_visible' => $request->is_last_price_visible ?? true,
                'anti_snip_window_seconds' => $request->anti_snip_window_seconds ?? 300,
                'anti_snip_extension_seconds' => $request->anti_snip_extension_seconds ?? 300,
                'status' => 'active',
                'bid_count' => 0,
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

            // Handle specifications
            if ($request->has('specifications') && !empty($request->specifications)) {
                foreach ($request->specifications as $spec) {
                    \App\Models\AdSpecification::create([
                        'ad_id' => $ad->id,
                        'specification_id' => $spec['specification_id'],
                        'value' => $spec['value'],
                    ]);
                }
            }

            DB::commit();

            // Load relationships for response
            $ad->load(['auction', 'user', 'brand', 'model', 'city', 'country', 'category', 'media', 'specifications']);

            Log::info('Auction ad created successfully', [
                'ad_id' => $ad->id,
                'user_id' => auth()->id(),
                'title' => $ad->title,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Auction created successfully',
                'data' => new AuctionAdResource($ad)
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Auction ad creation failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request_data' => $request->validated()
            ]);

            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to create auction',
                'errors' => ['general' => ['An unexpected error occurred while creating the auction']]
            ], 500);
        }
    }

    /**
     * Show a single auction ad
     */
    public function show($id): JsonResponse
    {
        $ad = Ad::where('type', 'auction')
            ->with(['auction.bids.user', 'auction.winner', 'user', 'brand', 'model', 'city', 'country', 'category', 'media', 'specifications'])
            ->find($id);

        if (!$ad) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Auction not found',
                'errors' => ['ad' => ['The requested auction does not exist']]
            ], 404);
        }

        // Increment view count if not viewing own ad
        if (!auth()->check() || auth()->id() !== $ad->user_id) {
            $ad->increment('views_count');
        }

        return response()->json([
            'status' => 'success',
            'data' => new AuctionAdResource($ad)
        ]);
    }

    /**
     * Update an auction ad
     */
    public function update(UpdateAuctionAdRequest $request, $id): JsonResponse
    {
        $ad = Ad::where('type', 'auction')->find($id);

        if (!$ad) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Auction not found',
                'errors' => ['ad' => ['The requested auction does not exist']]
            ], 404);
        }

        try {
            DB::beginTransaction();

            // Update ad fields
            $adFields = [
                'title', 'description', 'category_id', 'brand_id', 'model_id',
                'city_id', 'country_id', 'year', 'color', 'millage', 'contact_phone', 'whatsapp_number', 'period_days'
            ];

            foreach ($adFields as $field) {
                if ($request->has($field)) {
                    $ad->$field = $request->$field;
                }
            }
            $ad->save();

            // Update auction fields
            $auction = $ad->auction;
            if ($auction) {
                $auctionFields = [
                    'start_price', 'reserve_price', 'minimum_bid_increment',
                    'start_time', 'end_time', 'auto_close', 'is_last_price_visible',
                    'anti_snip_window_seconds', 'anti_snip_extension_seconds'
                ];

                foreach ($auctionFields as $field) {
                    if ($request->has($field)) {
                        $auction->$field = $request->$field;
                    }
                }
                $auction->save();
            }

            // Sync media if provided
            if ($request->has('media_ids')) {
                $ad->media()->sync($request->media_ids ?? []);
                
                if (!empty($request->media_ids)) {
                    \App\Models\Media::whereIn('id', $request->media_ids)
                        ->update([
                            'related_resource' => 'ads',
                            'related_id' => $ad->id
                        ]);
                }
            }

            // Handle specifications
            if ($request->has('specifications')) {
                \App\Models\AdSpecification::where('ad_id', $ad->id)->delete();
                
                if (!empty($request->specifications)) {
                    foreach ($request->specifications as $spec) {
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
            $ad->load(['auction', 'user', 'brand', 'model', 'city', 'country', 'category', 'media', 'specifications']);

            Log::info('Auction ad updated successfully', [
                'ad_id' => $ad->id,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Auction updated successfully',
                'data' => new AuctionAdResource($ad)
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Auction ad update failed', [
                'error' => $e->getMessage(),
                'ad_id' => $id,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to update auction',
                'errors' => ['general' => ['An unexpected error occurred while updating the auction']]
            ], 500);
        }
    }

    /**
     * Delete an auction ad
     */
    public function destroy($id): JsonResponse
    {
        $ad = Ad::where('type', 'auction')->with('auction')->find($id);

        if (!$ad) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Auction not found',
                'errors' => ['ad' => ['The requested auction does not exist']]
            ], 404);
        }

        // Check authorization
        if ($ad->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['You do not have permission to delete this auction']]
            ], 403);
        }

        // Check if auction has bids
        if ($ad->auction && $ad->auction->bid_count > 0) {
            return response()->json([
                'status' => 'error',
                'code' => 422,
                'message' => 'Cannot delete auction',
                'errors' => ['auction' => ['Cannot delete auction with existing bids. Cancel the auction instead.']]
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Delete auction record first (due to FK constraint)
            if ($ad->auction) {
                $ad->auction->delete();
            }

            // Delete ad (soft delete if enabled)
            $ad->delete();

            DB::commit();

            Log::info('Auction ad deleted successfully', [
                'ad_id' => $id,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Auction deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Auction ad deletion failed', [
                'error' => $e->getMessage(),
                'ad_id' => $id,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to delete auction',
                'errors' => ['general' => ['An unexpected error occurred while deleting the auction']]
            ], 500);
        }
    }

    /**
     * Place a bid on an auction
     */
    public function placeBid(PlaceBidRequest $request, $id): JsonResponse
    {
        $ad = Ad::where('type', 'auction')->with('auction')->find($id);

        if (!$ad || !$ad->auction) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Auction not found',
                'errors' => ['ad' => ['The requested auction does not exist']]
            ], 404);
        }

        try {
            DB::beginTransaction();

            $auction = $ad->auction;

            // Lock the auction row for update to prevent race conditions
            $auction = Auction::lockForUpdate()->find($auction->id);

            // Double-check bid is still valid after lock
            $minimumBid = $auction->getMinimumNextBid();
            if ($request->price < $minimumBid) {
                DB::rollback();
                return response()->json([
                    'status' => 'error',
                    'code' => 422,
                    'message' => 'Bid too low',
                    'errors' => ['price' => ["Your bid must be at least " . number_format($minimumBid, 2)]]
                ], 422);
            }

            // Create the bid
            $bid = Bid::create([
                'auction_id' => $auction->id,
                'user_id' => auth()->id(),
                'price' => $request->price,
                'comment' => $request->comment,
                'status' => 'active',
            ]);

            // Update auction with new highest bid
            $auction->last_price = $request->price;
            $auction->bid_count = $auction->bid_count + 1;

            // Check for anti-sniping
            $antiSnipeTriggered = false;
            if ($auction->shouldTriggerAntiSnipe()) {
                $oldEndTime = $auction->end_time->copy();
                $auction->extendEndTime();
                $antiSnipeTriggered = true;
                
                Log::info('Anti-snipe triggered', [
                    'auction_id' => $auction->id,
                    'bid_id' => $bid->id,
                    'old_end_time' => $oldEndTime->toISOString(),
                    'new_end_time' => $auction->end_time->toISOString(),
                ]);
            }

            $auction->save();

            DB::commit();

            // Load relationships for response
            $bid->load('user');

            Log::info('Bid placed successfully', [
                'bid_id' => $bid->id,
                'auction_id' => $auction->id,
                'user_id' => auth()->id(),
                'price' => $request->price,
                'anti_snipe_triggered' => $antiSnipeTriggered,
            ]);

            $responseData = [
                'status' => 'success',
                'message' => 'Bid placed successfully',
                'data' => new BidResource($bid),
            ];

            if ($antiSnipeTriggered) {
                $responseData['anti_snipe'] = [
                    'triggered' => true,
                    'new_end_time' => $auction->end_time->toISOString(),
                    'extension_seconds' => $auction->anti_snip_extension_seconds,
                ];
            }

            return response()->json($responseData, 201);

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Bid placement failed', [
                'error' => $e->getMessage(),
                'auction_id' => $id,
                'user_id' => auth()->id(),
                'price' => $request->price,
            ]);

            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to place bid',
                'errors' => ['general' => ['An unexpected error occurred while placing your bid']]
            ], 500);
        }
    }

    /**
     * List bids for an auction (owner/admin only)
     */
    public function listBids(Request $request, $id): JsonResponse
    {
        $ad = Ad::where('type', 'auction')->with('auction')->find($id);

        if (!$ad || !$ad->auction) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Auction not found',
                'errors' => ['ad' => ['The requested auction does not exist']]
            ], 404);
        }

        // Check authorization - owner, admin, or moderator
        $isOwner = $ad->user_id === auth()->id();
        $isAdmin = auth()->user()->isAdmin();
        $isModerator = auth()->user()->hasRole('moderator');
        
        if (!$isOwner && !$isAdmin && !$isModerator) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['Only the auction owner, admin, or moderator can view all bids']]
            ], 403);
        }

        $query = Bid::where('auction_id', $ad->auction->id)
            ->with('user')
            ->active() // Only show active (non-withdrawn) bids
            ->orderBy('price', 'desc');

        $limit = min($request->get('limit', 20), 100);
        $bids = $query->paginate($limit);

        return response()->json([
            'status' => 'success',
            'data' => BidResource::collection($bids),
            'meta' => [
                'current_page' => $bids->currentPage(),
                'last_page' => $bids->lastPage(),
                'per_page' => $bids->perPage(),
                'total' => $bids->total(),
            ]
        ]);
    }

    /**
     * Get user's own bids across all auctions
     */
    public function myBids(Request $request): JsonResponse
    {
        $query = Bid::where('user_id', auth()->id())
            ->with(['auction.ad', 'user'])
            ->orderBy('created_at', 'desc');

        // Filter by auction status
        if ($request->filled('auction_status')) {
            $query->whereHas('auction', function ($q) use ($request) {
                $q->where('status', $request->auction_status);
            });
        }

        $limit = min($request->get('limit', 20), 100);
        $bids = $query->paginate($limit);

        return response()->json([
            'status' => 'success',
            'data' => BidResource::collection($bids),
            'meta' => [
                'current_page' => $bids->currentPage(),
                'last_page' => $bids->lastPage(),
                'per_page' => $bids->perPage(),
                'total' => $bids->total(),
            ]
        ]);
    }

    /**
     * Close an auction (owner/admin)
     */
    public function closeAuction(CloseAuctionRequest $request, $id): JsonResponse
    {
        $ad = Ad::where('type', 'auction')->with(['auction.bids'])->find($id);

        if (!$ad || !$ad->auction) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Auction not found',
                'errors' => ['ad' => ['The requested auction does not exist']]
            ], 404);
        }

        try {
            DB::beginTransaction();

            $auction = $ad->auction;
            $result = $auction->closeAuction();

            DB::commit();

            // Reload with relationships
            $ad->load(['auction.winner', 'user', 'brand', 'model', 'city', 'country', 'category', 'media']);

            Log::info('Auction closed', [
                'auction_id' => $auction->id,
                'ad_id' => $ad->id,
                'user_id' => auth()->id(),
                'winner_id' => $result['winner_id'],
                'winning_bid' => $result['winning_bid'],
                'reserve_met' => $result['reserve_met'],
            ]);

            return response()->json([
                'status' => 'success',
                'message' => $result['message'],
                'data' => new AuctionAdResource($ad),
                'result' => [
                    'winner_id' => $result['winner_id'],
                    'winning_bid' => $result['winning_bid'],
                    'reserve_met' => $result['reserve_met'],
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Auction closure failed', [
                'error' => $e->getMessage(),
                'auction_id' => $id,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to close auction',
                'errors' => ['general' => ['An unexpected error occurred while closing the auction']]
            ], 500);
        }
    }

    /**
     * Cancel an auction (owner/admin only, before it has bids)
     */
    public function cancelAuction($id): JsonResponse
    {
        $ad = Ad::where('type', 'auction')->with('auction')->find($id);

        if (!$ad || !$ad->auction) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Auction not found',
                'errors' => ['ad' => ['The requested auction does not exist']]
            ], 404);
        }

        // Check authorization
        if ($ad->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['You do not have permission to cancel this auction']]
            ], 403);
        }

        $auction = $ad->auction;

        // Cannot cancel if already closed
        if ($auction->status === 'closed') {
            return response()->json([
                'status' => 'error',
                'code' => 422,
                'message' => 'Cannot cancel auction',
                'errors' => ['auction' => ['This auction has already been closed']]
            ], 422);
        }

        // Cannot cancel if already cancelled
        if ($auction->status === 'cancelled') {
            return response()->json([
                'status' => 'error',
                'code' => 422,
                'message' => 'Auction already cancelled',
                'errors' => ['auction' => ['This auction has already been cancelled']]
            ], 422);
        }

        // Non-admin cannot cancel if there are bids
        if (!auth()->user()->isAdmin() && $auction->bid_count > 0) {
            return response()->json([
                'status' => 'error',
                'code' => 422,
                'message' => 'Cannot cancel auction',
                'errors' => ['auction' => ['Cannot cancel an auction with existing bids. Contact admin for assistance.']]
            ], 422);
        }

        try {
            DB::beginTransaction();

            $auction->cancelAuction();

            DB::commit();

            Log::info('Auction cancelled', [
                'auction_id' => $auction->id,
                'ad_id' => $ad->id,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Auction cancelled successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Auction cancellation failed', [
                'error' => $e->getMessage(),
                'auction_id' => $id,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to cancel auction',
                'errors' => ['general' => ['An unexpected error occurred while cancelling the auction']]
            ], 500);
        }
    }

    /**
     * Publish an auction ad
     */
    public function publish($id): JsonResponse
    {
        $ad = Ad::where('type', 'auction')->with('auction')->find($id);

        if (!$ad) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Auction not found',
                'errors' => ['ad' => ['The requested auction does not exist']]
            ], 404);
        }

        // Check authorization
        if ($ad->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['You do not have permission to publish this auction']]
            ], 403);
        }

        if ($ad->status === 'published') {
            return response()->json([
                'status' => 'error',
                'code' => 422,
                'message' => 'Already published',
                'errors' => ['ad' => ['This auction is already published']]
            ], 422);
        }

        $ad->status = 'published';
        $ad->published_at = now();
        $ad->save();

        // Ensure auction is active
        if ($ad->auction && $ad->auction->status !== 'active') {
            $ad->auction->status = 'active';
            $ad->auction->save();
        }

        $ad->load(['auction', 'user', 'brand', 'model', 'city', 'country', 'category', 'media']);

        Log::info('Auction published', [
            'ad_id' => $ad->id,
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Auction published successfully',
            'data' => new AuctionAdResource($ad)
        ]);
    }

    /**
     * Get global statistics for auctions (admin only)
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
            'total_auctions' => Ad::where('type', 'auction')->count(),
            'active_auctions' => Ad::where('type', 'auction')
                ->where('status', 'published')
                ->whereHas('auction', function ($q) {
                    $q->where('status', 'active')
                      ->where('start_time', '<=', now())
                      ->where('end_time', '>', now());
                })
                ->count(),
            'pending_auctions' => Ad::where('type', 'auction')->where('status', 'pending')->count(),
            'closed_auctions' => Ad::where('type', 'auction')
                ->whereHas('auction', function ($q) {
                    $q->where('status', 'closed');
                })
                ->count(),
            'total_bids' => Bid::count(),
            'bids_today' => Bid::whereDate('created_at', today())->count(),
            'auctions_ending_soon' => Ad::where('type', 'auction')
                ->where('status', 'published')
                ->whereHas('auction', function ($q) {
                    $q->where('status', 'active')
                      ->where('end_time', '>', now())
                      ->where('end_time', '<=', now()->addHour());
                })
                ->count(),
            'total_bid_value' => (float) Bid::sum('price'),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }

    /**
     * Withdraw a bid (owner only, with restrictions)
     */
    public function withdrawBid($adId, $bidId): JsonResponse
    {
        $ad = Ad::where('type', 'auction')->with('auction')->find($adId);

        if (!$ad || !$ad->auction) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Auction not found',
                'errors' => ['ad' => ['The requested auction does not exist']]
            ], 404);
        }

        $bid = Bid::where('id', $bidId)
            ->where('auction_id', $ad->auction->id)
            ->first();

        if (!$bid) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Bid not found',
                'errors' => ['bid' => ['The requested bid does not exist']]
            ], 404);
        }

        // Check authorization - only bid owner can withdraw
        if ($bid->user_id !== auth()->id()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['You can only withdraw your own bids']]
            ], 403);
        }

        // Check if bid can be withdrawn
        if (!$bid->canBeWithdrawn()) {
            $reason = 'This bid cannot be withdrawn';
            
            if ($bid->status === 'withdrawn') {
                $reason = 'This bid has already been withdrawn';
            } elseif ($bid->isHighestBid()) {
                $reason = 'The highest bid cannot be withdrawn';
            } elseif ($ad->auction->status !== 'active') {
                $reason = 'Cannot withdraw bids from a closed or cancelled auction';
            } elseif ($ad->auction->hasEnded()) {
                $reason = 'Cannot withdraw bids after the auction has ended';
            }

            return response()->json([
                'status' => 'error',
                'code' => 422,
                'message' => 'Cannot withdraw bid',
                'errors' => ['bid' => [$reason]]
            ], 422);
        }

        try {
            $bid->withdraw();

            Log::info('Bid withdrawn', [
                'bid_id' => $bid->id,
                'auction_id' => $ad->auction->id,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Bid withdrawn successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Bid withdrawal failed', [
                'error' => $e->getMessage(),
                'bid_id' => $bidId,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to withdraw bid',
                'errors' => ['general' => ['An unexpected error occurred while withdrawing the bid']]
            ], 500);
        }
    }

    /**
     * List public auction ads by a specific user
     */
    public function listByUser(Request $request, $userId): AnonymousResourceCollection
    {
        $query = Ad::where('type', 'auction')
            ->where('user_id', $userId)
            ->where('status', 'published')
            ->with(['auction', 'user', 'brand', 'model', 'city', 'country', 'category', 'media', 'specifications']);

        // Filter by auction status
        if ($request->filled('auction_status')) {
            $auctionStatus = $request->auction_status;
            if ($auctionStatus === 'active') {
                $query->whereHas('auction', function ($q) {
                    $q->where('status', 'active')
                      ->where('start_time', '<=', now())
                      ->where('end_time', '>', now());
                });
            } elseif ($auctionStatus === 'upcoming') {
                $query->whereHas('auction', function ($q) {
                    $q->where('status', 'active')
                      ->where('start_time', '>', now());
                });
            } elseif ($auctionStatus === 'ended') {
                $query->whereHas('auction', function ($q) {
                    $q->where('end_time', '<=', now());
                });
            } elseif ($auctionStatus === 'closed') {
                $query->whereHas('auction', function ($q) {
                    $q->where('status', 'closed');
                });
            }
        }

        // Sort options
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        if (in_array($sortBy, ['created_at', 'updated_at', 'views_count', 'title'])) {
            $query->orderBy($sortBy, $sortDirection);
        } elseif ($sortBy === 'end_time') {
            $query->join('auctions', 'ads.id', '=', 'auctions.ad_id')
                  ->orderBy('auctions.end_time', $sortDirection)
                  ->select('ads.*');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $limit = min($request->get('limit', 15), 50);
        $ads = $query->paginate($limit);

        return AuctionAdResource::collection($ads);
    }

    /**
     * Get a specific bid details
     */
    public function showBid($adId, $bidId): JsonResponse
    {
        $ad = Ad::where('type', 'auction')->with('auction')->find($adId);

        if (!$ad || !$ad->auction) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Auction not found',
                'errors' => ['ad' => ['The requested auction does not exist']]
            ], 404);
        }

        $bid = Bid::where('id', $bidId)
            ->where('auction_id', $ad->auction->id)
            ->with('user')
            ->first();

        if (!$bid) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Bid not found',
                'errors' => ['bid' => ['The requested bid does not exist']]
            ], 404);
        }

        // Check authorization - bid owner, auction owner, admin, or moderator
        $isBidOwner = $bid->user_id === auth()->id();
        $isAuctionOwner = $ad->user_id === auth()->id();
        $isAdmin = auth()->user()->isAdmin();
        $isModerator = auth()->user()->hasRole('moderator');

        if (!$isBidOwner && !$isAuctionOwner && !$isAdmin && !$isModerator) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['You do not have permission to view this bid']]
            ], 403);
        }

        return response()->json([
            'status' => 'success',
            'data' => new BidResource($bid)
        ]);
    }
}
