<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\CategoryResource;
use App\Http\Traits\LogsAudit;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends BaseApiController
{
    use LogsAudit;
    /**
     * List categories (admin-only).
     */
    public function index(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return $this->error(403, 'Unauthorized');
        }

        $query = Category::with('specifications');

        // Search
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->ofStatus($request->status);
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $categories = $query->paginate($request->input('per_page', 15));

        return $this->successPaginated(
            CategoryResource::collection($categories),
            'Categories retrieved successfully'
        );
    }

    /**
     * Show a single category (admin-only).
     */
    public function show(Request $request, Category $category)
    {
        if (!$request->user()->isAdmin()) {
            return $this->error(403, 'Unauthorized');
        }

        $category->load('specifications');

        return $this->success(
            new CategoryResource($category),
            'Category retrieved successfully'
        );
    }

    /**
     * Create a new category (admin-only).
     */
    public function store(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return $this->error(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'name_en' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'status' => 'sometimes|in:active,inactive',
            'specs_group_id' => 'nullable|integer',
        ]);

        $category = Category::create($validated);

        $this->auditLog(
            actionType: 'category.created',
            resourceType: 'category',
            resourceId: $category->id,
            details: ['name_en' => $category->name_en, 'name_ar' => $category->name_ar],
            severity: 'info'
        );

        return $this->success(
            new CategoryResource($category),
            'Category created successfully',
            201
        );
    }

    /**
     * Update a category (admin-only).
     */
    public function update(Request $request, Category $category)
    {
        if (!$request->user()->isAdmin()) {
            return $this->error(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'name_en' => 'sometimes|required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'status' => 'sometimes|in:active,inactive',
            'specs_group_id' => 'nullable|integer',
        ]);

        $oldData = $category->only(['name_en', 'name_ar', 'status']);
        $category->update($validated);

        $this->auditLog(
            actionType: 'category.updated',
            resourceType: 'category',
            resourceId: $category->id,
            details: ['old' => $oldData, 'new' => $category->only(['name_en', 'name_ar', 'status'])],
            severity: 'info'
        );

        return $this->success(
            new CategoryResource($category),
            'Category updated successfully'
        );
    }

    /**
     * Delete a category (admin-only).
     */
    public function destroy(Request $request, Category $category)
    {
        if (!$request->user()->isAdmin()) {
            return $this->error(403, 'Unauthorized');
        }

        $this->auditLogDestructive(
            actionType: 'category.deleted',
            resourceType: 'category',
            resourceId: $category->id,
            details: ['name_en' => $category->name_en, 'name_ar' => $category->name_ar]
        );

        $category->delete();

        return $this->success(
            null,
            'Category deleted successfully'
        );
    }

    /**
     * Get specifications for a category.
     */
    public function specifications(Request $request, Category $category)
    {
        if (!$request->user()->isAdmin()) {
            return $this->error(403, 'Unauthorized');
        }

        $specifications = $category->specifications()->get();

        return $this->success(
            $specifications,
            'Category specifications retrieved successfully'
        );
    }

    /**
     * Assign specifications to a category (admin-only).
     * Replaces all existing specifications with the provided list.
     */
    public function assignSpecifications(Request $request, Category $category)
    {
        if (!$request->user()->isAdmin()) {
            return $this->error(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'specification_ids' => 'required|array',
            'specification_ids.*' => 'exists:specifications,id',
        ]);

        // Prepare sync data with order
        $syncData = [];
        foreach ($validated['specification_ids'] as $index => $specId) {
            $syncData[$specId] = ['order' => $index];
        }

        $oldSpecs = $category->specifications()->pluck('id')->toArray();

        // Sync specifications (removes old ones, adds new ones)
        $category->specifications()->sync($syncData);

        $this->auditLog(
            actionType: 'category.specifications_assigned',
            resourceType: 'category',
            resourceId: $category->id,
            details: [
                'category_name' => $category->name_en,
                'old_specification_ids' => $oldSpecs,
                'new_specification_ids' => $validated['specification_ids']
            ],
            severity: 'info'
        );

        $category->load('specifications');

        return $this->success(
            new CategoryResource($category),
            'Specifications assigned to category successfully'
        );
    }

    /**
     * Add a single specification to a category (admin-only).
     */
    public function attachSpecification(Request $request, Category $category)
    {
        if (!$request->user()->isAdmin()) {
            return $this->error(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'specification_id' => 'required|exists:specifications,id',
            'order' => 'nullable|integer|min:0',
        ]);

        // Check if already attached
        if ($category->specifications()->where('specification_id', $validated['specification_id'])->exists()) {
            return $this->error(409, 'Specification already attached to this category');
        }

        $category->specifications()->attach($validated['specification_id'], [
            'order' => $validated['order'] ?? $category->specifications()->count(),
        ]);

        $category->load('specifications');

        return $this->success(
            new CategoryResource($category),
            'Specification attached to category successfully'
        );
    }

    /**
     * Remove a specification from a category (admin-only).
     */
    public function detachSpecification(Request $request, Category $category, $specificationId)
    {
        if (!$request->user()->isAdmin()) {
            return $this->error(403, 'Unauthorized');
        }

        // Check if specification exists
        $exists = $category->specifications()->where('specification_id', $specificationId)->exists();
        
        if (!$exists) {
            return $this->error(404, 'Specification not attached to this category');
        }

        $category->specifications()->detach($specificationId);

        $category->load('specifications');

        return $this->success(
            new CategoryResource($category),
            'Specification detached from category successfully'
        );
    }
}
