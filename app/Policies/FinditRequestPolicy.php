<?php

namespace App\Policies;

use App\Models\FinditRequest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FinditRequestPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any FindIt requests.
     * Only admins can view all requests.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can view the FindIt request.
     * Only the owner or admins can view a request.
     */
    public function view(User $user, FinditRequest $finditRequest): bool
    {
        // Owner can view their own request
        if ($finditRequest->user_id === $user->id) {
            return true;
        }

        // Admin can view any request
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can create FindIt requests.
     * Any authenticated user can create requests.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the FindIt request.
     */
    public function update(User $user, FinditRequest $finditRequest): bool
    {
        // Owner can update their own request
        if ($finditRequest->user_id === $user->id) {
            return true;
        }

        // Admin can update any request
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the FindIt request.
     */
    public function delete(User $user, FinditRequest $finditRequest): bool
    {
        // Owner can delete their own request
        if ($finditRequest->user_id === $user->id) {
            return true;
        }

        // Admin can delete any request
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can submit an offer on the FindIt request.
     */
    public function submitOffer(User $user, FinditRequest $finditRequest): bool
    {
        return $user->canSubmitFinditOffer($finditRequest);
    }

    /**
     * Determine whether the user can manage offers on the FindIt request.
     * (accept/reject offers)
     */
    public function manageOffers(User $user, FinditRequest $finditRequest): bool
    {
        // Only owner can manage offers
        if ($finditRequest->user_id === $user->id) {
            return true;
        }

        // Admin can manage offers
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can view matches for the FindIt request.
     */
    public function viewMatches(User $user, FinditRequest $finditRequest): bool
    {
        // Only owner can view matches (FindIt is private)
        if ($finditRequest->user_id === $user->id) {
            return true;
        }

        // Admin can view matches
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can refresh matches for the FindIt request.
     */
    public function refreshMatches(User $user, FinditRequest $finditRequest): bool
    {
        // Only owner can refresh matches for active requests
        if ($finditRequest->user_id === $user->id) {
            return $finditRequest->isActive();
        }

        // Admin can refresh any active request
        return $user->hasRole('admin') && $finditRequest->isActive();
    }

    /**
     * Determine whether the user can perform bulk actions on FindIt requests.
     * Only admins can perform bulk actions.
     */
    public function bulkAction(User $user): bool
    {
        return $user->hasRole('admin');
    }
}
