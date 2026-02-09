<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Review\StoreReviewRequest;
use App\Http\Requests\Review\UpdateReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Http\Traits\LogsAudit;
use App\Models\Review;
use App\Models\Ad;
use App\Models\User;
use App\Notifications\ReviewReceivedNotification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReviewController extends BaseApiController
{
    use LogsAudit;
    /**
     * Display a listing of reviews.
     * Can filter by ad_id or seller_id.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Review::with(['user', 'ad', 'seller']);

        // Filter by ad
        if ($request->filled('ad_id')) {
            $query->where('ad_id', $request->ad_id);
        }

        // Filter by seller
        if ($request->filled('seller_id')) {
            $query->where('seller_id', $request->seller_id);
        }

        // Filter by minimum rating
        if ($request->filled('min_stars')) {
            $query->where('stars', '>=', $request->min_stars);
        }

        // Filter by user who created the review
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Sort by creation date (newest first by default)
        $query->orderBy('created_at', $request->get('sort', 'desc'));

        // Pagination
        $limit = min($request->get('limit', 15), 50);
        $reviews = $query->paginate($limit);

        return $this->successPaginated($reviews->setCollection(
            $reviews->getCollection()->map(fn($review) => new ReviewResource($review))
        ), 'Reviews retrieved successfully');
    }

    /**
     * Get reviews for a specific ad.
     */
    public function adReviews(Request $request, int $adId): JsonResponse
    {
        $ad = Ad::findOrFail($adId);

        $query = Review::where('ad_id', $adId)
            ->with(['user']);

        // Filter by minimum rating
        if ($request->filled('min_stars')) {
            $query->where('stars', '>=', $request->min_stars);
        }

        $query->orderBy('created_at', 'desc');

        $limit = min($request->get('limit', 15), 50);
        $reviews = $query->paginate($limit);

        return $this->successPaginated($reviews->setCollection(
            $reviews->getCollection()->map(fn($review) => new ReviewResource($review))
        ), 'Ad reviews retrieved successfully');
    }

    /**
     * Get reviews for a specific seller/user.
     */
    public function userReviews(Request $request, int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        $query = Review::where('seller_id', $userId)
            ->with(['user']);

        // Filter by minimum rating
        if ($request->filled('min_stars')) {
            $query->where('stars', '>=', $request->min_stars);
        }

        $query->orderBy('created_at', 'desc');

        $limit = min($request->get('limit', 15), 50);
        $reviews = $query->paginate($limit);

        return $this->successPaginated($reviews->setCollection(
            $reviews->getCollection()->map(fn($review) => new ReviewResource($review))
        ), 'User reviews retrieved successfully');
    }

    /**
     * Get the authenticated user's reviews.
     */
    public function myReviews(Request $request): JsonResponse
    {
        $query = Review::where('user_id', auth()->id())
            ->with(['ad', 'seller']);

        $query->orderBy('created_at', 'desc');

        $limit = min($request->get('limit', 15), 50);
        $reviews = $query->paginate($limit);

        return $this->successPaginated($reviews->setCollection(
            $reviews->getCollection()->map(fn($review) => new ReviewResource($review))
        ), 'Your reviews retrieved successfully');
    }

    /**
     * Store a newly created review.
     */
    public function store(StoreReviewRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Create review with appropriate target
        $reviewData = [
            'user_id' => auth()->id(),
            'stars' => $validated['stars'],
            'title' => $validated['title'] ?? null,
            'body' => $validated['body'] ?? null,
        ];

        if ($validated['target_type'] === 'ad') {
            $reviewData['ad_id'] = $validated['target_id'];
            $target = Ad::findOrFail($validated['target_id']);
        } else {
            $reviewData['seller_id'] = $validated['target_id'];
            $target = User::findOrFail($validated['target_id']);
        }

        $review = Review::create($reviewData);
        $review->load(['user', 'ad', 'seller']);

        // Send notification to the target (ad owner or seller)
        if ($validated['target_type'] === 'ad' && $target->user) {
            $target->user->notify(new ReviewReceivedNotification($review));
        } elseif ($validated['target_type'] === 'seller') {
            $target->notify(new ReviewReceivedNotification($review));
        }

        return response()->json([
            'success' => true,
            'message' => 'Review created successfully',
            'data' => new ReviewResource($review),
        ], 201);
    }

    /**
     * Display the specified review.
     */
    public function show(Review $review): JsonResponse
    {
        $review->load(['user', 'ad', 'seller']);

        return $this->success(
            new ReviewResource($review),
            'Review retrieved successfully'
        );
    }

    /**
     * Update the specified review.
     */
    public function update(UpdateReviewRequest $request, Review $review): JsonResponse
    {
        $validated = $request->validated();

        $review->update($validated);
        $review->load(['user', 'ad', 'seller']);

        return $this->success(
            new ReviewResource($review),
            'Review updated successfully'
        );
    }

    /**
     * Remove the specified review.
     */
    public function destroy(Review $review): JsonResponse
    {
        $this->authorize('delete', $review);

        $this->auditLogDestructive(
            actionType: 'review.deleted',
            resourceType: 'review',
            resourceId: $review->id,
            details: [
                'user_id' => $review->user_id,
                'ad_id' => $review->ad_id,
                'seller_id' => $review->seller_id,
                'stars' => $review->stars
            ]
        );

        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Review deleted successfully',
        ], 200);
    }
}
