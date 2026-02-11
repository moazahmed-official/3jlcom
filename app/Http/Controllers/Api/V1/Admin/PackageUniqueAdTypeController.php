<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Traits\LogsAudit;
use App\Models\Package;
use App\Models\UniqueAdTypeDefinition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PackageUniqueAdTypeController extends BaseApiController
{
    use LogsAudit;

    /**
     * Get unique ad types assigned to a package.
     *
     * GET /api/v1/admin/packages/{package}/unique-ad-types
     */
    public function index(Package $package): JsonResponse
    {
        $uniqueAdTypes = $package->uniqueAdTypes()
            ->withPivot('ads_limit')
            ->byPriority()
            ->get()
            ->map(function ($type) {
                return [
                    'id' => $type->id,
                    'name' => $type->name,
                    'display_name' => $type->display_name,
                    'priority' => $type->priority,
                    'active' => $type->active,
                    'ads_limit' => $type->pivot->ads_limit,
                    'is_unlimited' => $type->pivot->ads_limit === null,
                ];
            });

        return $this->success($uniqueAdTypes, 'Unique ad types retrieved successfully');
    }

    /**
     * Assign unique ad types to a package (replace all).
     *
     * PUT /api/v1/admin/packages/{package}/unique-ad-types
     */
    public function sync(Request $request, Package $package): JsonResponse
    {
        $validated = $request->validate([
            'unique_ad_types' => ['required', 'array'],
            'unique_ad_types.*.id' => ['required', 'integer', 'exists:unique_ad_type_definitions,id'],
            'unique_ad_types.*.ads_limit' => ['nullable', 'integer', 'min:0'],
        ]);

        $syncData = [];
        foreach ($validated['unique_ad_types'] as $typeData) {
            $syncData[$typeData['id']] = [
                'ads_limit' => $typeData['ads_limit'] ?? null,
            ];
        }

        $package->uniqueAdTypes()->sync($syncData);

        $this->logAudit('synced_unique_ad_types', Package::class, $package->id, null, [
            'unique_ad_types' => $syncData
        ]);

        return $this->success(null, 'Unique ad types assigned to package successfully');
    }

    /**
     * Add a unique ad type to a package.
     *
     * POST /api/v1/admin/packages/{package}/unique-ad-types
     */
    public function attach(Request $request, Package $package): JsonResponse
    {
        $validated = $request->validate([
            'unique_ad_type_id' => ['required', 'integer', 'exists:unique_ad_type_definitions,id'],
            'ads_limit' => ['nullable', 'integer', 'min:0'],
        ]);

        // Check if already attached
        if ($package->uniqueAdTypes()->where('unique_ad_type_id', $validated['unique_ad_type_id'])->exists()) {
            return $this->error(
                409,
                'This unique ad type is already assigned to the package',
                ['unique_ad_type_id' => ['Already assigned']]
            );
        }

        $package->uniqueAdTypes()->attach($validated['unique_ad_type_id'], [
            'ads_limit' => $validated['ads_limit'] ?? null,
        ]);

        $this->logAudit('attached_unique_ad_type', Package::class, $package->id, null, $validated);

        return $this->success(null, 'Unique ad type added to package successfully');
    }

    /**
     * Update the limit for a specific unique ad type in a package.
     *
     * PATCH /api/v1/admin/packages/{package}/unique-ad-types/{uniqueAdType}
     */
    public function update(Request $request, Package $package, UniqueAdTypeDefinition $uniqueAdType): JsonResponse
    {
        $validated = $request->validate([
            'ads_limit' => ['nullable', 'integer', 'min:0'],
        ]);

        // Check if attached
        if (!$package->uniqueAdTypes()->where('unique_ad_type_id', $uniqueAdType->id)->exists()) {
            return $this->error(
                404,
                'This unique ad type is not assigned to the package'
            );
        }

        $package->uniqueAdTypes()->updateExistingPivot($uniqueAdType->id, [
            'ads_limit' => $validated['ads_limit'] ?? null,
        ]);

        $this->logAudit('updated_unique_ad_type_limit', Package::class, $package->id, null, [
            'unique_ad_type_id' => $uniqueAdType->id,
            'ads_limit' => $validated['ads_limit']
        ]);

        return $this->success(null, 'Ad limit updated successfully');
    }

    /**
     * Remove a unique ad type from a package.
     *
     * DELETE /api/v1/admin/packages/{package}/unique-ad-types/{uniqueAdType}
     */
    public function detach(Package $package, UniqueAdTypeDefinition $uniqueAdType): JsonResponse
    {
        if (!$package->uniqueAdTypes()->where('unique_ad_type_id', $uniqueAdType->id)->exists()) {
            return $this->error(
                404,
                'This unique ad type is not assigned to the package'
            );
        }

        $package->uniqueAdTypes()->detach($uniqueAdType->id);

        $this->logAudit('detached_unique_ad_type', Package::class, $package->id, null, [
            'unique_ad_type_id' => $uniqueAdType->id
        ]);

        return $this->success(null, 'Unique ad type removed from package successfully');
    }
}
