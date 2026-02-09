<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Package\StorePackageRequest;
use App\Http\Requests\Package\UpdatePackageRequest;
use App\Http\Requests\Package\AssignPackageRequest;
use App\Http\Requests\StorePackageFeatureRequest;
use App\Http\Requests\UpdatePackageFeatureRequest;
use App\Http\Resources\PackageResource;
use App\Http\Resources\UserPackageResource;
use App\Http\Resources\PackageFeatureResource;
use App\Http\Traits\LogsAudit;
use App\Models\Package;
use App\Models\PackageFeature;
use App\Models\User;
use App\Models\UserPackage;
use App\Services\PackageFeatureService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PackageController extends BaseApiController
{
    use LogsAudit;
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

        // AUDIT LOG: Record package creation
        $this->auditLogPackage('created', $package->id, [
            'name' => $package->name,
            'price' => $package->price,
            'duration_days' => $package->duration_days,
            'active' => $package->active,
        ]);

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

        // Track changes for audit log
        $changes = [];
        foreach ($validated as $key => $value) {
            if ($package->{$key} !== $value) {
                $changes[$key] = [
                    'old' => $package->{$key},
                    'new' => $value,
                ];
            }
        }

        $package->update($validated);

        // AUDIT LOG: Record package update
        if (!empty($changes)) {
            $this->auditLogPackage('updated', $package->id, [
                'changes' => $changes,
            ]);
        }

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

        // AUDIT LOG: Record package deletion (destructive action)
        $this->auditLogDestructive('package.deleted', 'Package', $package->id, [
            'name' => $package->name,
            'price' => $package->price,
            'active_subscribers' => $activeSubscribers,
        ]);

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

        // AUDIT LOG: Record package assignment (billing action)
        $this->auditLogPackage('assigned', $package->id, [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'start_date' => $userPackage->start_date,
            'end_date' => $userPackage->end_date,
            'subscription_id' => $userPackage->id,
        ], 'notice');

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

    // ========================================
    // PACKAGE FEATURES MANAGEMENT (Step 2)
    // ========================================

    /**
     * Get features for a package.
     */
    public function getFeatures(Request $request, Package $package): JsonResponse
    {
        // Check authorization
        $isAdmin = $request->user() && $request->user()->hasAnyRole(['admin', 'super_admin']);
        
        if (!$package->active && !$isAdmin) {
            return $this->error(404, 'Package not found');
        }

        $features = $package->packageFeatures;
        
        if (!$features) {
            // Return default features structure if not configured
            return $this->success([
                'package_id' => $package->id,
                'configured' => false,
                'features' => $package->getFeatureSummary(),
            ], 'Package features retrieved (using defaults)');
        }

        return $this->success(
            new PackageFeatureResource($features),
            'Package features retrieved successfully'
        );
    }

    /**
     * Store/create features for a package (admin only).
     */
    public function storeFeatures(StorePackageFeatureRequest $request, Package $package): JsonResponse
    {
        // Check if user is admin
        if (!$request->user()->hasAnyRole(['admin', 'super_admin'])) {
            return $this->error(403, 'You are not authorized to manage package features');
        }

        // Check if features already exist
        if ($package->packageFeatures) {
            return $this->error(
                409,
                'Features already exist for this package. Use PUT to update.'
            );
        }

        $validated = $request->validated();
        $validated['package_id'] = $package->id;

        $features = PackageFeature::create($validated);

        // Apply role upgrades to existing subscribers if needed
        $this->applyFeaturesToExistingSubscribers($package, $features);

        return $this->success(
            new PackageFeatureResource($features),
            'Package features created successfully',
            201
        );
    }

    /**
     * Update features for a package (admin only).
     */
    public function updateFeatures(UpdatePackageFeatureRequest $request, Package $package): JsonResponse
    {
        // Check if user is admin
        if (!$request->user()->hasAnyRole(['admin', 'super_admin'])) {
            return $this->error(403, 'You are not authorized to manage package features');
        }

        $features = $package->packageFeatures;
        
        if (!$features) {
            // Create if doesn't exist
            $validated = $request->validated();
            $validated['package_id'] = $package->id;
            $features = PackageFeature::create($validated);
        } else {
            $features->update($request->validated());
            $features->refresh();
        }

        // Apply role upgrades to existing subscribers if needed
        $this->applyFeaturesToExistingSubscribers($package, $features);

        return $this->success(
            new PackageFeatureResource($features),
            'Package features updated successfully'
        );
    }

    /**
     * Delete features for a package (admin only).
     * This will reset the package to default features.
     */
    public function destroyFeatures(Request $request, Package $package): JsonResponse
    {
        // Check if user is admin
        if (!$request->user()->hasAnyRole(['admin', 'super_admin'])) {
            return $this->error(403, 'You are not authorized to manage package features');
        }

        $features = $package->packageFeatures;
        
        if (!$features) {
            return $this->error(404, 'Package features not found');
        }

        $features->delete();

        return $this->success([], 'Package features deleted. Package now uses default features.');
    }

    /**
     * Get feature summary for the current user's package.
     */
    public function myFeatures(Request $request): JsonResponse
    {
        $user = $request->user();
        
        return $this->success(
            $user->getPackageFeatureSummary(),
            'Your package features retrieved successfully'
        );
    }

    /**
     * Check if user can perform a specific action based on package.
     */
    public function checkCapability(Request $request): JsonResponse
    {
        $request->validate([
            'capability' => ['required', 'string'],
            'ad_type' => ['nullable', 'string', 'in:normal,unique,caishha,findit,auction'],
        ]);

        $user = $request->user();
        $capability = $request->input('capability');
        $adType = $request->input('ad_type');

        $result = [
            'capability' => $capability,
            'allowed' => false,
            'reason' => null,
        ];

        switch ($capability) {
            case 'publish_ad':
                if (!$adType) {
                    $result['reason'] = 'ad_type is required for publish_ad capability check';
                } else {
                    $result['allowed'] = $user->canPublishAdType($adType);
                    $result['remaining'] = $user->getRemainingAdsForType($adType);
                    if (!$result['allowed']) {
                        $result['reason'] = "Your package does not allow {$adType} ads";
                    } elseif ($result['remaining'] !== null && $result['remaining'] <= 0) {
                        $result['allowed'] = false;
                        $result['reason'] = "You have reached your {$adType} ads limit";
                    }
                }
                break;

            case 'push_to_facebook':
                $result['allowed'] = $user->canPushToFacebook();
                if (!$result['allowed']) {
                    $result['reason'] = 'Your package does not include Facebook push';
                }
                break;

            case 'auto_republish':
                $result['allowed'] = $user->canAutoRepublish();
                if (!$result['allowed']) {
                    $result['reason'] = 'Your package does not include auto-republish';
                }
                break;

            case 'use_banner':
                $result['allowed'] = $user->canUseBanner();
                if (!$result['allowed']) {
                    $result['reason'] = 'Your package does not include banner feature';
                }
                break;

            case 'use_background_color':
                $result['allowed'] = $user->canUseBackgroundColor();
                if (!$result['allowed']) {
                    $result['reason'] = 'Your package does not include background color feature';
                }
                break;

            case 'feature_ad':
                $result['allowed'] = $user->canFeatureAds();
                if (!$result['allowed']) {
                    $result['reason'] = 'Your package does not include ad featuring';
                }
                break;

            case 'bulk_upload':
                $result['allowed'] = $user->canBulkUpload();
                if (!$result['allowed']) {
                    $result['reason'] = 'Your package does not include bulk upload';
                }
                break;

            default:
                $result['reason'] = 'Unknown capability';
        }

        return $this->success($result, 'Capability check completed');
    }

    /**
     * Apply package features (role upgrades) to existing subscribers.
     */
    protected function applyFeaturesToExistingSubscribers(Package $package, PackageFeature $features): void
    {
        // Get all active subscribers
        $activeSubscribers = UserPackage::where('package_id', $package->id)
            ->valid()
            ->with('user')
            ->get();

        foreach ($activeSubscribers as $userPackage) {
            $user = $userPackage->user;
            
            if (!$user) {
                continue;
            }

            // Grant seller status if feature is enabled
            if ($features->grants_seller_status) {
                $user->assignRole('seller');
                
                // Auto-verify if enabled
                if ($features->auto_verify_seller && !$user->seller_verified) {
                    $user->update([
                        'seller_verified' => true,
                        'seller_verified_at' => now(),
                    ]);
                }
            }

            // Grant marketer status if feature is enabled
            if ($features->grants_marketer_status) {
                $user->assignRole('marketer');
            }

            // Update account type if needed
            if ($features->grants_seller_status && !in_array($user->account_type, ['dealer', 'showroom'])) {
                $user->update(['account_type' => 'dealer']);
            }
        }
    }
}
