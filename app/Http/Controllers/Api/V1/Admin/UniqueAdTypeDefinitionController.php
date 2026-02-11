<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\UniqueAdType\StoreUniqueAdTypeDefinitionRequest;
use App\Http\Requests\UniqueAdType\UpdateUniqueAdTypeDefinitionRequest;
use App\Http\Resources\UniqueAdTypeDefinitionResource;
use App\Http\Traits\LogsAudit;
use App\Models\UniqueAdTypeDefinition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UniqueAdTypeDefinitionController extends BaseApiController
{
    use LogsAudit;

    /**
     * List all unique ad type definitions.
     *
     * GET /api/v1/admin/unique-ad-types
     */
    public function index(Request $request): JsonResponse
    {
        $query = UniqueAdTypeDefinition::query();

        // Filter by active status
        if ($request->has('active')) {
            $query->where('active', $request->boolean('active'));
        }

        // Search by name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('display_name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'priority');
        $sortDir = $request->get('sort_dir', 'asc');
        
        if (in_array($sortBy, ['priority', 'name', 'price', 'created_at'])) {
            $query->orderBy($sortBy, $sortDir);
        }

        $perPage = $request->get('per_page', 20);
        
        if ($request->boolean('no_pagination')) {
            $types = $query->get();
            return $this->success(
                UniqueAdTypeDefinitionResource::collection($types),
                'Unique ad types retrieved successfully'
            );
        }

        $types = $query->paginate($perPage);

        return $this->successPaginated(
            $types->through(fn($type) => new UniqueAdTypeDefinitionResource($type)),
            'Unique ad types retrieved successfully'
        );
    }

    /**
     * Create a new unique ad type definition.
     *
     * POST /api/v1/admin/unique-ad-types
     */
    public function store(StoreUniqueAdTypeDefinitionRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Auto-generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $type = UniqueAdTypeDefinition::create($validated);

        $this->logAudit('created', UniqueAdTypeDefinition::class, $type->id, null, $type->toArray());

        return $this->success(
            new UniqueAdTypeDefinitionResource($type),
            'Unique ad type created successfully',
            201
        );
    }

    /**
     * Get a specific unique ad type definition.
     *
     * GET /api/v1/admin/unique-ad-types/{id}
     */
    public function show(UniqueAdTypeDefinition $uniqueAdType): JsonResponse
    {
        $uniqueAdType->load(['uniqueAds', 'packages']);

        return $this->success(
            new UniqueAdTypeDefinitionResource($uniqueAdType),
            'Unique ad type retrieved successfully'
        );
    }

    /**
     * Update a unique ad type definition.
     *
     * PUT/PATCH /api/v1/admin/unique-ad-types/{id}
     */
    public function update(UpdateUniqueAdTypeDefinitionRequest $request, UniqueAdTypeDefinition $uniqueAdType): JsonResponse
    {
        $oldData = $uniqueAdType->toArray();
        $validated = $request->validated();

        $uniqueAdType->update($validated);

        $this->logAudit('updated', UniqueAdTypeDefinition::class, $uniqueAdType->id, $oldData, $uniqueAdType->toArray());

        return $this->success(
            new UniqueAdTypeDefinitionResource($uniqueAdType),
            'Unique ad type updated successfully'
        );
    }

    /**
     * Delete/deactivate a unique ad type definition.
     *
     * DELETE /api/v1/admin/unique-ad-types/{id}
     */
    public function destroy(UniqueAdTypeDefinition $uniqueAdType): JsonResponse
    {
        // Check if any active ads are using this type
        $activeAdsCount = $uniqueAdType->uniqueAds()
            ->whereHas('ad', function ($query) {
                $query->where('status', 'published');
            })
            ->count();

        if ($activeAdsCount > 0) {
            return $this->error(
                409,
                "Cannot delete this ad type. {$activeAdsCount} active ads are using it. Deactivate it instead.",
                ['active_ads' => $activeAdsCount]
            );
        }

        $this->logAudit('deleted', UniqueAdTypeDefinition::class, $uniqueAdType->id, $uniqueAdType->toArray(), null);

        $uniqueAdType->delete();

        return $this->success(null, 'Unique ad type deleted successfully');
    }

    /**
     * Reorder priorities for multiple ad types.
     *
     * PATCH /api/v1/admin/unique-ad-types/reorder
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'types' => ['required', 'array', 'min:1'],
            'types.*.id' => ['required', 'exists:unique_ad_type_definitions,id'],
            'types.*.priority' => ['required', 'integer', 'min:1'],
        ]);

        foreach ($validated['types'] as $typeData) {
            UniqueAdTypeDefinition::where('id', $typeData['id'])
                ->update(['priority' => $typeData['priority']]);
        }

        $this->logAudit('reordered', UniqueAdTypeDefinition::class, null, null, $validated['types']);

        return $this->success(null, 'Priorities updated successfully');
    }

    /**
     * Toggle active status.
     *
     * PATCH /api/v1/admin/unique-ad-types/{id}/toggle-active
     */
    public function toggleActive(UniqueAdTypeDefinition $uniqueAdType): JsonResponse
    {
        $uniqueAdType->active = !$uniqueAdType->active;
        $uniqueAdType->save();

        $status = $uniqueAdType->active ? 'activated' : 'deactivated';

        $this->logAudit('toggled_active', UniqueAdTypeDefinition::class, $uniqueAdType->id, null, [
            'active' => $uniqueAdType->active
        ]);

        return $this->success(
            new UniqueAdTypeDefinitionResource($uniqueAdType),
            "Unique ad type {$status} successfully"
        );
    }
}
