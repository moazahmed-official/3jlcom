<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\AdUpgradeRequest\StoreAdUpgradeRequestRequest;
use App\Http\Requests\AdUpgradeRequest\ReviewAdUpgradeRequestRequest;
use App\Http\Resources\AdUpgradeRequestResource;
use App\Http\Traits\LogsAudit;
use App\Models\Ad;
use App\Models\AdUpgradeRequest;
use App\Models\UniqueAd;
use App\Models\UniqueAdTypeDefinition;
use App\Services\UniqueAdTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdUpgradeRequestController extends BaseApiController
{
    use LogsAudit;

    protected UniqueAdTypeService $typeService;

    public function __construct(UniqueAdTypeService $typeService)
    {
        $this->typeService = $typeService;
    }

    /**
     * User requests to upgrade their ad to a specific unique type.
     *
    /**
     * User requests to upgrade their ad to a specific unique type.
     * This is only available for FREE plan users.
     * Paid plan users should use the ad type conversion system instead.
     *
     * POST /api/v1/ads/{ad}/upgrade-request
     */
    public function store(StoreAdUpgradeRequestRequest $request, Ad $ad): JsonResponse
    {
        $user = auth()->user();

        // Verify ownership
        if ($ad->user_id !== $user->id && !$user->isAdmin()) {
            return $this->error(403, 'You do not own this ad');
        }

        // Upgrade requests are only for free plan users
        $activePackage = $user->activePackage;
        if ($activePackage && !$activePackage->isFree()) {
            return $this->error(
                403,
                'Paid plan users can create unique ads directly or use ad type conversion. Upgrade requests are for free plan users only.',
                ['plan' => ['Use POST /api/v1/ads/{ad}/convert for paid plan ad type conversion.']]
            );
        }

        // Check if ad already has a pending upgrade request
        $existingRequest = AdUpgradeRequest::where('ad_id', $ad->id)
            ->where('status', 'pending')
            ->first();

        if ($existingRequest) {
            return $this->error(
                409,
                'This ad already has a pending upgrade request',
                ['ad' => ['A pending upgrade request already exists for this ad']]
            );
        }

        // Get the requested type
        $requestedType = UniqueAdTypeDefinition::find($request->requested_unique_type_id);

        if (!$requestedType || !$requestedType->active) {
            return $this->error(
                404,
                'The requested ad type is not available',
                ['requested_unique_type_id' => ['The selected ad type is not currently available']]
            );
        }

        // Create the upgrade request
        $upgradeRequest = AdUpgradeRequest::create([
            'ad_id' => $ad->id,
            'requested_unique_type_id' => $request->requested_unique_type_id,
            'user_id' => auth()->id(),
            'status' => 'pending',
            'user_message' => $request->user_message,
        ]);

        $upgradeRequest->load(['ad', 'requestedType', 'user']);

        $this->logAudit('created', AdUpgradeRequest::class, $upgradeRequest->id, null, $upgradeRequest->toArray());

        return $this->success(
            new AdUpgradeRequestResource($upgradeRequest),
            'Upgrade request submitted successfully. Please wait for admin approval.',
            201
        );
    }

    /**
     * Get authenticated user's upgrade requests.
     *
     * GET /api/v1/user/ad-upgrade-requests
     */
    public function myRequests(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = AdUpgradeRequest::where('user_id', $user->id)
            ->with(['ad', 'requestedType', 'reviewer'])
            ->orderByDesc('created_at');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->paginate($request->get('per_page', 20));

        return $this->successPaginated(
            $requests->through(fn($req) => new AdUpgradeRequestResource($req)),
            'Upgrade requests retrieved successfully'
        );
    }

    /**
     * Cancel a pending upgrade request.
     *
     * DELETE /api/v1/user/ad-upgrade-requests/{upgradeRequest}
     */
    public function cancel(AdUpgradeRequest $upgradeRequest): JsonResponse
    {
        // Authorization check
        if ($upgradeRequest->user_id !== auth()->id() && auth()->user()->role !== 'admin') {
            return $this->error(403, 'Unauthorized to cancel this request');
        }

        if ($upgradeRequest->status !== 'pending') {
            return $this->error(
                409,
                'Only pending requests can be cancelled',
                ['status' => ['This request has already been ' . $upgradeRequest->status]]
            );
        }

        $this->logAudit('cancelled', AdUpgradeRequest::class, $upgradeRequest->id, $upgradeRequest->toArray(), null);

        $upgradeRequest->delete();

        return $this->success(null, 'Upgrade request cancelled successfully');
    }

    /**
     * Admin: List all upgrade requests.
     *
     * GET /api/v1/admin/ad-upgrade-requests
     */
    public function index(Request $request): JsonResponse
    {
        $query = AdUpgradeRequest::with(['user', 'ad', 'requestedType', 'reviewer'])
            ->orderByDesc('created_at');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by user_id
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by requested type
        if ($request->has('requested_unique_type_id')) {
            $query->where('requested_unique_type_id', $request->requested_unique_type_id);
        }

        // Search by ad title
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('ad', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%");
            });
        }

        $requests = $query->paginate($request->get('per_page', 20));

        return $this->successPaginated(
            $requests->through(fn($req) => new AdUpgradeRequestResource($req)),
            'Upgrade requests retrieved successfully'
        );
    }

    /**
     * Admin: Get a specific upgrade request.
     *
     * GET /api/v1/admin/ad-upgrade-requests/{upgradeRequest}
     */
    public function show(AdUpgradeRequest $upgradeRequest): JsonResponse
    {
        $upgradeRequest->load(['user', 'ad', 'ad.media', 'requestedType', 'reviewer']);

        return $this->success(
            new AdUpgradeRequestResource($upgradeRequest),
            'Upgrade request retrieved successfully'
        );
    }

    /**
     * Admin: Approve an upgrade request.
     *
     * PATCH /api/v1/admin/ad-upgrade-requests/{upgradeRequest}/approve
     */
    public function approve(ReviewAdUpgradeRequestRequest $request, AdUpgradeRequest $upgradeRequest): JsonResponse
    {
        if ($upgradeRequest->status !== 'pending') {
            return $this->error(
                409,
                'This request has already been reviewed',
                ['request' => ['Request status is ' . $upgradeRequest->status]]
            );
        }

        $requestedType = $upgradeRequest->requestedType;

        if (!$requestedType || !$requestedType->active) {
            return $this->error(
                422,
                'Cannot approve: The requested ad type is no longer available'
            );
        }

        try {
            DB::transaction(function () use ($upgradeRequest, $request, $requestedType) {
                $ad = $upgradeRequest->ad;

                // Convert to unique type if currently normal
                if ($ad->type !== 'unique') {
                    $ad->type = 'unique';
                    $ad->save();

                    // Create unique_ads record if doesn't exist
                    if (!$ad->uniqueAd) {
                        UniqueAd::create([
                            'ad_id' => $ad->id,
                            'unique_ad_type_id' => $requestedType->id,
                        ]);
                    }
                }

                // Update or get unique ad record
                $uniqueAd = $ad->uniqueAd;
                $uniqueAd->unique_ad_type_id = $requestedType->id;
                $uniqueAd->save();

                // Apply type features
                $this->typeService->applyTypeFeatures($uniqueAd, $requestedType);

                // Update request status
                $upgradeRequest->status = 'approved';
                $upgradeRequest->admin_message = $request->admin_message;
                $upgradeRequest->reviewed_by = auth()->id();
                $upgradeRequest->reviewed_at = now();
                $upgradeRequest->save();
            });

            $this->logAudit('approved', AdUpgradeRequest::class, $upgradeRequest->id, null, [
                'ad_id' => $upgradeRequest->ad_id,
                'requested_type_id' => $upgradeRequest->requested_unique_type_id,
                'admin_message' => $request->admin_message
            ]);

            $upgradeRequest->load(['ad', 'requestedType', 'reviewer']);

            return $this->success(
                new AdUpgradeRequestResource($upgradeRequest),
                'Upgrade request approved successfully'
            );

        } catch (\Exception $e) {
            return $this->error(
                500,
                'Failed to approve upgrade request: ' . $e->getMessage()
            );
        }
    }

    /**
     * Admin: Reject an upgrade request.
     *
     * PATCH /api/v1/admin/ad-upgrade-requests/{upgradeRequest}/reject
     */
    public function reject(ReviewAdUpgradeRequestRequest $request, AdUpgradeRequest $upgradeRequest): JsonResponse
    {
        if ($upgradeRequest->status !== 'pending') {
            return $this->error(
                409,
                'This request has already been reviewed',
                ['request' => ['Request status is ' . $upgradeRequest->status]]
            );
        }

        $upgradeRequest->status = 'rejected';
        $upgradeRequest->admin_message = $request->admin_message;
        $upgradeRequest->reviewed_by = auth()->id();
        $upgradeRequest->reviewed_at = now();
        $upgradeRequest->save();

        $this->logAudit('rejected', AdUpgradeRequest::class, $upgradeRequest->id, null, [
            'ad_id' => $upgradeRequest->ad_id,
            'admin_message' => $request->admin_message
        ]);

        $upgradeRequest->load(['ad', 'requestedType', 'reviewer']);

        return $this->success(
            new AdUpgradeRequestResource($upgradeRequest),
            'Upgrade request rejected'
        );
    }
}
