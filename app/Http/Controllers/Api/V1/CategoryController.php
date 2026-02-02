<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends BaseApiController
{
    /**
     * List categories (admin-only).
     */
    public function index(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return $this->error(403, 'Unauthorized');
        }

        $query = Category::query();

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

        $category->update($validated);

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

        $category->delete();

        return $this->success(
            null,
            'Category deleted successfully'
        );
    }
}
