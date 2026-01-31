<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFindItRequest;
use App\Http\Requests\UpdateFindItRequest;
use App\Http\Resources\FindItMatchResource;
use App\Http\Resources\FindItRequestResource;
use App\Models\FinditMatch;
use App\Models\FinditRequest;
use App\Services\FindItMatchingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class FindItAdsController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected FindItMatchingService $matchingService
    ) {}

    /**
     * List the current user's FindIt requests.
     * 
     * GET /api/v1/findit-ads/my-requests
     */
    public function myRequests(Request $request): AnonymousResourceCollection
    {
        $query = FinditRequest::with(['brand', 'carModel', 'category', 'city', 'country', 'media'])
            ->withCount(['matches'])
            ->byUser(auth()->id())
            ->latest();

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter active only
        if ($request->boolean('active_only')) {
            $query->active();
        }

        // Include expired
        if (!$request->boolean('include_expired')) {
            $query->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
        }

        $perPage = min($request->integer('per_page', 15), 50);
        $requests = $query->paginate($perPage);

        return FindItRequestResource::collection($requests);
    }

    /**
     * List all FindIt requests (admin only).
     * 
     * GET /api/v1/findit-ads/admin
     */
    public function adminIndex(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', FinditRequest::class);

        $query = FinditRequest::with(['user', 'brand', 'carModel', 'category', 'city', 'country'])
            ->withCount(['matches'])
            ->latest();

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by user
        if ($request->has('user_id')) {
            $query->byUser($request->user_id);
        }

        // Filter by country
        if ($request->has('country_id')) {
            $query->inCountry($request->country_id);
        }

        // Filter expiring soon
        if ($request->boolean('expiring_soon')) {
            $query->expiring(7);
        }

        // Search by title
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $perPage = min($request->integer('per_page', 15), 100);
        $requests = $query->paginate($perPage);

        return FindItRequestResource::collection($requests);
    }

    /**
     * Store a new FindIt request.
     * 
     * POST /api/v1/findit-ads
     */
    public function store(StoreFindItRequest $request): JsonResponse
    {
        $validated = $request->validated();
        
        // Extract media IDs for syncing
        $mediaIds = $validated['media'] ?? [];
        unset($validated['media']);
        
        // Auto-activate flag - default to true (active)
        $autoActivate = $validated['auto_activate'] ?? true;
        unset($validated['auto_activate']);

        // Set status based on auto_activate (default: active)
        $validated['status'] = $autoActivate ? 'active' : 'draft';
        $validated['user_id'] = auth()->id();
        
        // Set expiration date if activating (30 days from now)
        if ($autoActivate) {
            $validated['expires_at'] = now()->addDays(30);
        }

        $finditRequest = FinditRequest::create($validated);

        // Sync media attachments
        if (!empty($mediaIds)) {
            $finditRequest->syncMedia($mediaIds);
        }

        // If auto-activated, trigger initial matching
        if ($autoActivate) {
            $this->matchingService->processRequest($finditRequest);
        }

        $finditRequest->load(['brand', 'carModel', 'category', 'city', 'country', 'media']);

        return response()->json([
            'success' => true,
            'message' => $autoActivate 
                ? 'FindIt request created and activated successfully.' 
                : 'FindIt request created as draft.',
            'data' => new FindItRequestResource($finditRequest),
        ], 201);
    }

    /**
     * Display a specific FindIt request.
     * 
     * GET /api/v1/findit-ads/{findit_ad}
     */
    public function show(FinditRequest $findit_ad): JsonResponse
    {
        $this->authorize('view', $findit_ad);

        $findit_ad->load([
            'user',
            'brand',
            'carModel',
            'category',
            'city',
            'country',
            'media',
            'matches' => function ($query) {
                $query->with('ad.brand', 'ad.carModel', 'ad.city', 'ad.user', 'ad.media')
                    ->notDismissed()
                    ->orderByScore()
                    ->limit(20);
            },
        ]);

        $findit_ad->loadCount(['matches']);

        return response()->json([
            'success' => true,
            'data' => new FindItRequestResource($findit_ad),
        ]);
    }

    /**
     * Update a FindIt request.
     * 
     * PUT /api/v1/findit-ads/{findit_ad}
     */
    public function update(UpdateFindItRequest $request, FinditRequest $findit_ad): JsonResponse
    {
        $this->authorize('update', $findit_ad);

        $validated = $request->validated();

        // Extract media IDs for syncing
        $mediaIds = $validated['media'] ?? null;
        unset($validated['media']);

        // Handle status change
        $statusChanged = isset($validated['status']) && $validated['status'] !== $findit_ad->status;
        $wasActivated = $statusChanged && $validated['status'] === 'active';

        $findit_ad->update($validated);

        // Sync media if provided
        if ($mediaIds !== null) {
            $findit_ad->syncMedia($mediaIds);
        }

        // If just activated, trigger matching
        if ($wasActivated) {
            $this->matchingService->processRequest($findit_ad);
        }

        $findit_ad->load(['brand', 'carModel', 'category', 'city', 'country', 'media']);

        return response()->json([
            'success' => true,
            'message' => 'FindIt request updated successfully.',
            'data' => new FindItRequestResource($findit_ad),
        ]);
    }

    /**
     * Delete a FindIt request.
     * 
     * DELETE /api/v1/findit-ads/{findit_ad}
     */
    public function destroy(FinditRequest $findit_ad): JsonResponse
    {
        $this->authorize('delete', $findit_ad);

        // Delete related records
        $findit_ad->matches()->delete();
        $findit_ad->media()->detach();
        
        $findit_ad->delete();

        return response()->json([
            'success' => true,
            'message' => 'FindIt request deleted successfully.',
        ]);
    }

    /**
     * Activate a draft FindIt request.
     * 
     * POST /api/v1/findit-ads/{findit_ad}/activate
     */
    public function activate(FinditRequest $findit_ad): JsonResponse
    {
        $this->authorize('update', $findit_ad);

        if ($findit_ad->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft requests can be activated.',
            ], 422);
        }

        if ($findit_ad->isExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot activate an expired request.',
            ], 422);
        }

        $findit_ad->activate();
        
        // Trigger initial matching
        $this->matchingService->processRequest($findit_ad);

        return response()->json([
            'success' => true,
            'message' => 'FindIt request activated successfully.',
            'data' => new FindItRequestResource($findit_ad->fresh()),
        ]);
    }

    /**
     * Close a FindIt request.
     * 
     * POST /api/v1/findit-ads/{findit_ad}/close
     */
    public function close(FinditRequest $findit_ad): JsonResponse
    {
        $this->authorize('update', $findit_ad);

        if (!$findit_ad->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Only active requests can be closed.',
            ], 422);
        }

        $findit_ad->close();

        return response()->json([
            'success' => true,
            'message' => 'FindIt request closed successfully.',
            'data' => new FindItRequestResource($findit_ad->fresh()),
        ]);
    }

    /**
     * List matching ads for a FindIt request.
     * 
     * GET /api/v1/findit-ads/{findit_ad}/matches
     */
    public function listMatches(Request $request, FinditRequest $findit_ad): AnonymousResourceCollection
    {
        $this->authorize('view', $findit_ad);

        $query = $findit_ad->matches()
            ->with(['ad.brand', 'ad.model', 'ad.city', 'ad.country', 'ad.user', 'ad.media'])
            ->orderByScore();

        // Filter dismissed
        if (!$request->boolean('include_dismissed')) {
            $query->notDismissed();
        }

        // Filter by minimum score
        if ($request->has('min_score')) {
            $query->highScore($request->integer('min_score'));
        }

        // Filter only valid ads
        if ($request->boolean('valid_only')) {
            $query->whereHas('ad', function ($q) {
                $q->where('status', 'active');
            });
        }

        $perPage = min($request->integer('per_page', 20), 50);
        $matches = $query->paginate($perPage);

        return FindItMatchResource::collection($matches);
    }

    /**
     * Dismiss a match (hide it from results).
     * 
     * POST /api/v1/findit-ads/{findit_ad}/matches/{match}/dismiss
     */
    public function dismissMatch(FinditRequest $findit_ad, FinditMatch $match): JsonResponse
    {
        $this->authorize('view', $findit_ad);

        if ($match->findit_request_id !== $findit_ad->id) {
            return response()->json([
                'success' => false,
                'message' => 'Match does not belong to this request.',
            ], 404);
        }

        if ($match->dismissed) {
            return response()->json([
                'success' => false,
                'message' => 'Match is already dismissed.',
            ], 422);
        }

        $match->dismiss();

        return response()->json([
            'success' => true,
            'message' => 'Match dismissed successfully.',
        ]);
    }

    /**
     * Manually trigger matching for a FindIt request.
     * 
     * POST /api/v1/findit-ads/{findit_ad}/refresh-matches
     */
    public function refreshMatches(FinditRequest $findit_ad): JsonResponse
    {
        $this->authorize('update', $findit_ad);

        if (!$findit_ad->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Only active requests can be refreshed.',
            ], 422);
        }

        // Use refreshMatches to clear and re-find all matches
        $newMatchesCount = $this->matchingService->refreshMatches($findit_ad);

        return response()->json([
            'success' => true,
            'message' => $newMatchesCount > 0 
                ? "Found {$newMatchesCount} matching ads." 
                : 'No matches found.',
            'new_matches_count' => $newMatchesCount,
            'total_matches_count' => $findit_ad->fresh()->matches_count,
        ]);
    }

    /**
     * Get statistics for user's FindIt activity.
     * 
     * GET /api/v1/findit-ads/stats
     */
    public function stats(): JsonResponse
    {
        $userId = auth()->id();

        $stats = [
            'total_requests' => FinditRequest::byUser($userId)->count(),
            'active_requests' => FinditRequest::byUser($userId)->active()->count(),
            'draft_requests' => FinditRequest::byUser($userId)->where('status', 'draft')->count(),
            'closed_requests' => FinditRequest::byUser($userId)->where('status', 'closed')->count(),
            'expired_requests' => FinditRequest::byUser($userId)->expired()->count(),
            'total_matches' => FinditMatch::whereHas('finditRequest', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })->notDismissed()->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Extend expiration for a FindIt request.
     * 
     * POST /api/v1/findit-ads/{findit_ad}/extend
     */
    public function extend(Request $request, FinditRequest $findit_ad): JsonResponse
    {
        $this->authorize('update', $findit_ad);

        $request->validate([
            'days' => 'sometimes|integer|min:1|max:90',
        ]);

        if ($findit_ad->status === 'closed') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot extend a closed request. Use reactivate instead.',
            ], 422);
        }

        $days = $request->integer('days', 30);
        $findit_ad->extend($days);

        return response()->json([
            'success' => true,
            'message' => "Request extended by {$days} days.",
            'data' => new FindItRequestResource($findit_ad->fresh()),
        ]);
    }

    /**
     * Reactivate a closed or expired FindIt request.
     * 
     * POST /api/v1/findit-ads/{findit_ad}/reactivate
     */
    public function reactivate(Request $request, FinditRequest $findit_ad): JsonResponse
    {
        $this->authorize('update', $findit_ad);

        if ($findit_ad->status === 'active' && !$findit_ad->isExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'Request is already active.',
            ], 422);
        }

        $request->validate([
            'days' => 'sometimes|integer|min:1|max:90',
        ]);

        $days = $request->integer('days', 30);
        $findit_ad->reactivate($days);

        // Refresh matches on reactivation
        $this->matchingService->processRequest($findit_ad);

        return response()->json([
            'success' => true,
            'message' => 'Request reactivated successfully.',
            'data' => new FindItRequestResource($findit_ad->fresh()),
        ]);
    }

    /**
     * Get details of a specific match.
     * 
     * GET /api/v1/findit-ads/{findit_ad}/matches/{match}
     */
    public function showMatch(FinditRequest $findit_ad, FinditMatch $match): JsonResponse
    {
        $this->authorize('view', $findit_ad);

        if ($match->findit_request_id !== $findit_ad->id) {
            return response()->json([
                'success' => false,
                'message' => 'Match does not belong to this request.',
            ], 404);
        }

        $match->load(['ad.brand', 'ad.model', 'ad.city', 'ad.country', 'ad.user', 'ad.media']);

        return response()->json([
            'success' => true,
            'data' => new FindItMatchResource($match),
        ]);
    }

    /**
     * Restore a dismissed match.
     * 
     * POST /api/v1/findit-ads/{findit_ad}/matches/{match}/restore
     */
    public function restoreMatch(FinditRequest $findit_ad, FinditMatch $match): JsonResponse
    {
        $this->authorize('view', $findit_ad);

        if ($match->findit_request_id !== $findit_ad->id) {
            return response()->json([
                'success' => false,
                'message' => 'Match does not belong to this request.',
            ], 404);
        }

        if (!$match->dismissed) {
            return response()->json([
                'success' => false,
                'message' => 'Match is not dismissed.',
            ], 422);
        }

        $match->restore();

        return response()->json([
            'success' => true,
            'message' => 'Match restored successfully.',
        ]);
    }

    /**
     * Perform bulk actions on FindIt requests (admin only).
     * 
     * POST /api/v1/findit-ads/actions/bulk
     * 
     * Supported actions:
     * - activate: Activate multiple draft requests
     * - close: Close multiple active requests
     * - delete: Delete multiple requests
     * - extend: Extend expiration for multiple requests
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $this->authorize('bulkAction', FinditRequest::class);

        $request->validate([
            'action' => 'required|string|in:activate,close,delete,extend',
            'ids' => 'required|array|min:1|max:100',
            'ids.*' => 'integer|exists:findit_requests,id',
            'days' => 'required_if:action,extend|integer|min:1|max:90',
        ]);

        $action = $request->input('action');
        $ids = $request->input('ids');
        $days = $request->integer('days', 30);

        $processed = 0;
        $failed = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            $requests = FinditRequest::whereIn('id', $ids)->get();

            foreach ($requests as $finditRequest) {
                try {
                    switch ($action) {
                        case 'activate':
                            if ($finditRequest->status === 'draft') {
                                $finditRequest->activate();
                                $this->matchingService->processRequest($finditRequest);
                                $processed++;
                            } else {
                                $failed++;
                                $errors[] = "Request #{$finditRequest->id}: Can only activate draft requests.";
                            }
                            break;

                        case 'close':
                            if ($finditRequest->isActive()) {
                                $finditRequest->close();
                                $processed++;
                            } else {
                                $failed++;
                                $errors[] = "Request #{$finditRequest->id}: Can only close active requests.";
                            }
                            break;

                        case 'delete':
                            $finditRequest->matches()->delete();
                            $finditRequest->media()->detach();
                            $finditRequest->delete();
                            $processed++;
                            break;

                        case 'extend':
                            if ($finditRequest->status !== 'closed') {
                                $finditRequest->extend($days);
                                $processed++;
                            } else {
                                $failed++;
                                $errors[] = "Request #{$finditRequest->id}: Cannot extend closed requests.";
                            }
                            break;
                    }
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = "Request #{$finditRequest->id}: {$e->getMessage()}";
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Bulk {$action} completed: {$processed} processed, {$failed} failed.",
                'data' => [
                    'processed' => $processed,
                    'failed' => $failed,
                    'errors' => $errors,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Bulk action failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
