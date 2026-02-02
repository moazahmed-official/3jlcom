<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Package\StorePackageRequest;
use App\Http\Requests\Package\UpdatePackageRequest;
use App\Http\Requests\Package\AssignPackageRequest;
use App\Http\Resources\PackageResource;
use App\Http\Resources\UserPackageResource;
use App\Models\Package;
use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PackageController extends BaseApiController
{
    /**
     * Display a listing of packages (public).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Package::query();

        // Public users see only active packages
        $isAdmin = $request->user() && $request->user()->hasAnyRole(['admin', 'super_admin']);
        
        if (!$isAdmin) {
            $query->active();
        } else {
            // Admin can filter by active status
            if ($request->has('active')) {
                $query->where('active', $request->boolean('active'));
            }
        }

        // Filter by price range
        if ($request->filled('price_min')) {
            $query->where('price', '>=', $request->price_min);
        }
        if ($request->filled('price_max')) {
            $query->where('price', '<=', $request->price_max);
        }

        // Filter free/paid packages
        if ($request->has('free')) {
            if ($request->boolean('free')) {
                $query->free();
            } else {
                $query->paid();
            }
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'price');
        $sortOrder = $request->get('sort_order', 'asc');
        $allowedSorts = ['name', 'price', 'duration_days', 'created_at'];
        
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder === 'desc' ? 'desc' : 'asc');
        }

        $limit = min($request->get('limit', 20), 100);
        $packages = $query->paginate($limit);

        return $this->successPaginated(
            $packages->setCollection(
                $packages->getCollection()->map(fn($package) => new PackageResource($package))
            ),
            'Packages retrieved successfully'
        );
    }

    /**
     * Store a newly created package (admin only).
     */
    public function store(StorePackageRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $package = Package::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Package created successfully',
            'data' => new PackageResource($package),
        ], 201);
    }

    /**
     * Display the specified package.
     */
    public function show(Request $request, Package $package): JsonResponse
    {
        $isAdmin = $request->user() && $request->user()->hasAnyRole(['admin', 'super_admin']);

        // Non-admins can only view active packages
        if (!$package->active && !$isAdmin) {
            return $this->error(404, 'Package not found');
        }

        return $this->success(
            new PackageResource($package),
            'Package retrieved successfully'
        );
    }

    /**
     * Update the specified package (admin only).
     */
    public function update(UpdatePackageRequest $request, Package $package): JsonResponse
    {
        $validated = $request->validated();

        $package->update($validated);

        return $this->success(
            new PackageResource($package->fresh()),
            'Package updated successfully'
        );
    }

    /**
     * Remove the specified package (admin only).
     */
    public function destroy(Request $request, Package $package): JsonResponse
    {
        // Check if user is admin
        if (!$request->user()->hasAnyRole(['admin', 'super_admin'])) {
            return $this->error(403, 'You are not authorized to delete packages');
        }

        // Check if package has active subscribers
        $activeSubscribers = $package->userPackages()->where('active', true)->count();
        
        if ($activeSubscribers > 0) {
            return $this->error(
                409,
                "Cannot delete package with {$activeSubscribers} active subscriber(s). Deactivate the package instead."
            );
        }

        $package->delete();

        return $this->success([], 'Package deleted successfully');
    }

    /**
     * Assign package to a user (admin only).
     */
    public function assign(AssignPackageRequest $request, Package $package): JsonResponse
    {
        $validated = $request->validated();

        // Check if package is active
        if (!$package->active) {
            return $this->error(400, 'Cannot assign an inactive package');
        }

        $user = User::findOrFail($validated['user_id']);

        // Check if user already has this package active
        $existingActive = UserPackage::where('user_id', $user->id)
            ->where('package_id', $package->id)
            ->valid()
            ->first();

        if ($existingActive) {
            return $this->error(
                409,
                'User already has an active subscription to this package'
            );
        }

        $userPackage = UserPackage::create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'start_date' => $validated['start_date'] ?? now()->toDateString(),
            'end_date' => $validated['end_date'] ?? null,
            'active' => $validated['active'] ?? true,
        ]);

        $userPackage->load(['package', 'user']);

        return response()->json([
            'success' => true,
            'message' => 'Package assigned to user successfully',
            'data' => new UserPackageResource($userPackage),
        ], 201);
    }

    /**
     * Get packages for a specific user.
     */
    public function userPackages(Request $request, User $user): JsonResponse
    {
        // Authorization: user can view their own, admin can view any
        $currentUser = $request->user();
        
        if ($currentUser->id !== $user->id && !$currentUser->hasAnyRole(['admin', 'super_admin'])) {
            return $this->error(403, 'You are not authorized to view this user\'s packages');
        }

        $query = UserPackage::where('user_id', $user->id)
            ->with('package');

        // Filter by active status
        if ($request->has('active')) {
            $query->where('active', $request->boolean('active'));
        }

        // Filter by validity
        if ($request->has('valid')) {
            if ($request->boolean('valid')) {
                $query->valid();
            } else {
                $query->expired();
            }
        }

        $query->orderBy('created_at', 'desc');

        $limit = min($request->get('limit', 15), 50);
        $userPackages = $query->paginate($limit);

        return $this->successPaginated(
            $userPackages->setCollection(
                $userPackages->getCollection()->map(fn($up) => new UserPackageResource($up))
            ),
            'User packages retrieved successfully'
        );
    }

    /**
     * Get current user's packages.
     */
    public function myPackages(Request $request): JsonResponse
    {
        return $this->userPackages($request, $request->user());
    }

    /**
     * Update a user package subscription (admin only).
     */
    public function updateUserPackage(Request $request, UserPackage $userPackage): JsonResponse
    {
        // Check if user is admin
        if (!$request->user()->hasAnyRole(['admin', 'super_admin'])) {
            return $this->error(403, 'You are not authorized to update user packages');
        }

        $validated = $request->validate([
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'active' => ['sometimes', 'boolean'],
        ]);

        $userPackage->update($validated);

        $userPackage->load(['package', 'user']);

        return $this->success(
            new UserPackageResource($userPackage),
            'User package updated successfully'
        );
    }

    /**
     * Remove a user package subscription (admin only).
     */
    public function destroyUserPackage(Request $request, UserPackage $userPackage): JsonResponse
    {
        // Check if user is admin
        if (!$request->user()->hasAnyRole(['admin', 'super_admin'])) {
            return $this->error(403, 'You are not authorized to remove user packages');
        }

        $userPackage->delete();

        return $this->success([], 'User package removed successfully');
    }

    /**
     * Get package statistics (admin only).
     */
    public function stats(Request $request): JsonResponse
    {
        // Check if user is admin
        if (!$request->user()->hasAnyRole(['admin', 'super_admin'])) {
            return $this->error(403, 'You are not authorized to view package statistics');
        }

        $stats = [
            'total_packages' => Package::count(),
            'active_packages' => Package::where('active', true)->count(),
            'inactive_packages' => Package::where('active', false)->count(),
            'free_packages' => Package::where('price', 0)->count(),
            'paid_packages' => Package::where('price', '>', 0)->count(),
            'total_subscriptions' => UserPackage::count(),
            'active_subscriptions' => UserPackage::valid()->count(),
            'expired_subscriptions' => UserPackage::expired()->count(),
            'revenue_potential' => UserPackage::valid()
                ->join('packages', 'user_packages.package_id', '=', 'packages.id')
                ->sum('packages.price'),
        ];

        return $this->success($stats, 'Package statistics retrieved successfully');
    }
}
