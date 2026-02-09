<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\SpecificationResource;
use App\Http\Traits\LogsAudit;
use App\Models\Specification;
use Illuminate\Http\Request;

class SpecificationController extends BaseApiController
{
    use LogsAudit;
    /**
     * List specifications (admin-only).
     */
    public function index(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return $this->error(403, 'Unauthorized');
        }

        $query = Specification::query()->with('image');

        // Search
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->ofType($request->type);
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $specifications = $query->paginate($request->input('per_page', 15));

        return $this->successPaginated(
            SpecificationResource::collection($specifications),
            'Specifications retrieved successfully'
        );
    }

    /**
     * Show a single specification (admin-only).
     */
    public function show(Request $request, Specification $specification)
    {
        if (!$request->user()->isAdmin()) {
            return $this->error(403, 'Unauthorized');
        }

        $specification->load('image');

        return $this->success(
            new SpecificationResource($specification),
            'Specification retrieved successfully'
        );
    }

    /**
     * Create a new specification (admin-only).
     */
    public function store(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return $this->error(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'name_en' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'type' => 'required|in:text,number,select,boolean',
            'values' => 'nullable|array',
            'image_id' => 'nullable|exists:media,id',
        ]);

        $specification = Specification::create($validated);
        $specification->load('image');

        $this->auditLog(
            actionType: 'specification.created',
            resourceType: 'specification',
            resourceId: $specification->id,
            details: ['name_en' => $specification->name_en, 'type' => $specification->type],
            severity: 'info'
        );

        return $this->success(
            new SpecificationResource($specification),
            'Specification created successfully',
            201
        );
    }

    /**
     * Update a specification (admin-only).
     */
    public function update(Request $request, Specification $specification)
    {
        if (!$request->user()->isAdmin()) {
            return $this->error(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'name_en' => 'sometimes|required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'type' => 'sometimes|required|in:text,number,select,boolean',
            'values' => 'nullable|array',
            'image_id' => 'nullable|exists:media,id',
        ]);

        $oldData = $specification->only(['name_en', 'name_ar', 'type']);
        $specification->update($validated);
        $specification->load('image');

        $this->auditLog(
            actionType: 'specification.updated',
            resourceType: 'specification',
            resourceId: $specification->id,
            details: ['old' => $oldData, 'new' => $specification->only(['name_en', 'name_ar', 'type'])],
            severity: 'info'
        );

        return $this->success(
            new SpecificationResource($specification),
            'Specification updated successfully'
        );
    }

    /**
     * Delete a specification (admin-only).
     */
    public function destroy(Request $request, Specification $specification)
    {
        if (!$request->user()->isAdmin()) {
            return $this->error(403, 'Unauthorized');
        }

        $this->auditLogDestructive(
            actionType: 'specification.deleted',
            resourceType: 'specification',
            resourceId: $specification->id,
            details: ['name_en' => $specification->name_en, 'type' => $specification->type]
        );

        $specification->delete();

        return $this->success(
            null,
            'Specification deleted successfully'
        );
    }
}
