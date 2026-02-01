<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReviewPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any reviews.
     */
    public function viewAny(?User $user): bool
    {
        // Anyone can view reviews (public)
        return true;
    }

    /**
     * Determine whether the user can view the review.
     */
    public function view(?User $user, Review $review): bool
    {
        // Anyone can view individual reviews (public)
        return true;
    }

    /**
     * Determine whether the user can create reviews.
     */
    public function create(User $user): bool
    {
        // Any authenticated user can create reviews
        return true;
    }

    /**
     * Determine whether the user can update the review.
     */
    public function update(User $user, Review $review): bool
    {
        // Only the review owner or admin can update
        return $user->id === $review->user_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the review.
     */
    public function delete(User $user, Review $review): bool
    {
        // Only the review owner or admin can delete
        return $user->id === $review->user_id || $user->isAdmin();
    }
}
