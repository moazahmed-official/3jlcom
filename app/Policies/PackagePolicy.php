<?php

namespace App\Policies;

use App\Models\Package;
use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Auth\Access\HandlesAuthorization;

class PackagePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any packages.
     * Public access allowed for listing active packages.
     */
    public function viewAny(?User $user): bool
    {
        return true; // Anyone can view packages list
    }

    /**
     * Determine whether the user can view the package.
     * Public access allowed for active packages.
     */
    public function view(?User $user, Package $package): bool
    {
        // Public can view active packages
        if ($package->active) {
            return true;
        }

        // Admins can view all packages including inactive
        return $user && $user->hasAnyRole(['admin', 'super_admin']);
    }

    /**
     * Determine whether the user can create packages.
     * Admin only.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    /**
     * Determine whether the user can update the package.
     * Admin only.
     */
    public function update(User $user, Package $package): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    /**
     * Determine whether the user can delete the package.
     * Admin only.
     */
    public function delete(User $user, Package $package): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    /**
     * Determine whether the user can assign packages to users.
     * Admin only.
     */
    public function assign(User $user, Package $package): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    /**
     * Determine whether the user can view user packages.
     * User can view their own, admin can view any.
     */
    public function viewUserPackages(User $user, User $targetUser): bool
    {
        // Users can view their own packages
        if ($user->id === $targetUser->id) {
            return true;
        }

        // Admins can view any user's packages
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    /**
     * Determine whether the user can manage a specific user package.
     */
    public function manageUserPackage(User $user, UserPackage $userPackage): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }
}
