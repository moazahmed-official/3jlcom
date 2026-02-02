<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\PackageRequest\ReviewPackageRequestRequest;
use App\Http\Requests\PackageRequest\StorePackageRequestRequest;
use App\Http\Resources\PackageRequestResource;
use App\Models\Package;
use App\Models\PackageRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PackageRequestController extends BaseApiController
{
    /**
     * User requests a package.
     *
     * POST /api/v1/packages/{package}/request
     */
    public function store(StorePackageRequestRequest $request, Package $package): JsonResponse
    {
        $user = $request->user();

        // Check if user already has a pending request for this package
        $existingRequest = PackageRequest::where('user_id', $user->id)
            ->where('package_id', $package->id)
            ->where('status', 'pending')
            ->first();

        if ($existingRequest) {
            return $this->error(
                409,
                'You already have a pending request for this package',
                ['package' => ['A pending request already exists']]
            );
        }

        // Check if user already has this package active
        if ($user->package_id === $package->id) {
            return $this->error(
                409,
                'You already have this package assigned',
                ['package' => ['This package is already assigned to you']]
            );
        }

        $validated = $request->validated();

        $packageRequest = PackageRequest::create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'user_message' => $validated['user_message'] ?? null,
            'status' => 'pending',
        ]);

        $packageRequest->load(['package', 'user']);

        return $this->success(
            new PackageRequestResource($packageRequest),
            'Package request submitted successfully',
            201
        );
    }

    /**
     * Get authenticated user's package requests.
     *
     * GET /api/v1/user/package-requests
     */
    public function myRequests(Request $request): JsonResponse
    {
        $user = $request->user();

        $requests = PackageRequest::where('user_id', $user->id)
            ->with(['package', 'reviewer'])
            ->orderByDesc('created_at')
            ->paginate($request->get('per_page', 20));

        return $this->successPaginated(
            $requests->through(fn($req) => new PackageRequestResource($req)),
            'Package requests retrieved successfully'
        );
    }

    /**
     * Admin: List all package requests.
     *
     * GET /api/v1/admin/package-requests
     */
    public function index(Request $request): JsonResponse
    {
        $query = PackageRequest::with(['user', 'package', 'reviewer'])
            ->orderByDesc('created_at');

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by user_id if provided
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by package_id if provided
        if ($request->has('package_id')) {
            $query->where('package_id', $request->package_id);
        }

        $requests = $query->paginate($request->get('per_page', 20));

        return $this->successPaginated(
            $requests->through(fn($req) => new PackageRequestResource($req)),
            'Package requests retrieved successfully'
        );
    }

    /**
     * Admin: Get a specific package request.
     *
     * GET /api/v1/admin/package-requests/{id}
     */
    public function show(PackageRequest $packageRequest): JsonResponse
    {
        $packageRequest->load(['user', 'package', 'reviewer']);

        return $this->success(
            new PackageRequestResource($packageRequest),
            'Package request retrieved successfully'
        );
    }

    /**
     * Admin: Review a package request (approve or reject).
     *
     * PATCH /api/v1/admin/package-requests/{id}/review
     */
    public function review(ReviewPackageRequestRequest $request, PackageRequest $packageRequest): JsonResponse
    {
        // Check if already reviewed
        if ($packageRequest->status !== 'pending') {
            return $this->error(
                409,
                'This request has already been reviewed',
                ['request' => ['Request status is ' . $packageRequest->status]]
            );
        }

        $validated = $request->validated();

        $packageRequest->update([
            'status' => $validated['status'],
            'admin_notes' => $validated['admin_notes'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        // If approved, assign the package to the user
        if ($validated['status'] === 'approved') {
            $user = $packageRequest->user;
            $user->update([
                'package_id' => $packageRequest->package_id,
            ]);
        }

        $packageRequest->load(['user', 'package', 'reviewer']);

        return $this->success(
            new PackageRequestResource($packageRequest),
            'Package request reviewed successfully'
        );
    }

    /**
     * Admin: Approve a package request.
     *
     * POST /api/v1/admin/package-requests/{id}/approve
     */
    public function approve(Request $request, PackageRequest $packageRequest): JsonResponse
    {
        // Check if already reviewed
        if ($packageRequest->status !== 'pending') {
            return $this->error(
                409,
                'This request has already been reviewed',
                ['request' => ['Request status is ' . $packageRequest->status]]
            );
        }

        $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $packageRequest->update([
            'status' => 'approved',
            'admin_notes' => $request->admin_notes,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        // Assign the package to the user
        $user = $packageRequest->user;
        $user->update([
            'package_id' => $packageRequest->package_id,
        ]);

        $packageRequest->load(['user', 'package', 'reviewer']);

        return $this->success(
            new PackageRequestResource($packageRequest),
            'Package request approved successfully'
        );
    }

    /**
     * Admin: Reject a package request.
     *
     * POST /api/v1/admin/package-requests/{id}/reject
     */
    public function reject(Request $request, PackageRequest $packageRequest): JsonResponse
    {
        // Check if already reviewed
        if ($packageRequest->status !== 'pending') {
            return $this->error(
                409,
                'This request has already been reviewed',
                ['request' => ['Request status is ' . $packageRequest->status]]
            );
        }

        $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $packageRequest->update([
            'status' => 'rejected',
            'admin_notes' => $request->admin_notes,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $packageRequest->load(['user', 'package', 'reviewer']);

        return $this->success(
            new PackageRequestResource($packageRequest),
            'Package request rejected successfully'
        );
    }
}
