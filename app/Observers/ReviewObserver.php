<?php

namespace App\Observers;

use App\Models\Review;
use App\Models\User;
use App\Models\Ad;
use Illuminate\Support\Facades\DB;

class ReviewObserver
{
    /**
     * Handle the Review "created" event.
     */
    public function created(Review $review): void
    {
        $this->updateRatingCache($review);
    }

    /**
     * Handle the Review "updated" event.
     */
    public function updated(Review $review): void
    {
        // If stars changed, update the cache
        if ($review->wasChanged('stars')) {
            $this->updateRatingCache($review);
        }
    }

    /**
     * Handle the Review "deleted" event.
     */
    public function deleted(Review $review): void
    {
        $this->updateRatingCache($review);
    }

    /**
     * Update the rating cache for the target entity (ad or user)
     */
    protected function updateRatingCache(Review $review): void
    {
        // Update for ad if ad_id exists
        if ($review->ad_id) {
            $this->updateAdRating($review->ad_id);
        }

        // Update for seller/user if seller_id exists
        if ($review->seller_id) {
            $this->updateUserRating($review->seller_id);
        }
    }

    /**
     * Update rating cache for an ad
     */
    protected function updateAdRating(int $adId): void
    {
        $stats = Review::where('ad_id', $adId)
            ->select(
                DB::raw('AVG(stars) as avg_rating'),
                DB::raw('COUNT(*) as reviews_count')
            )
            ->first();

        Ad::where('id', $adId)->update([
            'avg_rating' => $stats->avg_rating ? round($stats->avg_rating, 2) : 0,
            'reviews_count' => $stats->reviews_count ?? 0,
        ]);
    }

    /**
     * Update rating cache for a user/seller
     */
    protected function updateUserRating(int $userId): void
    {
        $stats = Review::where('seller_id', $userId)
            ->select(
                DB::raw('AVG(stars) as avg_rating'),
                DB::raw('COUNT(*) as reviews_count')
            )
            ->first();

        User::where('id', $userId)->update([
            'avg_rating' => $stats->avg_rating ? round($stats->avg_rating, 2) : 0,
            'reviews_count' => $stats->reviews_count ?? 0,
        ]);
    }
}
