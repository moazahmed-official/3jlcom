<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\BlogResource;
use App\Models\Blog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BlogController extends BaseApiController
{
    /**
     * List published blogs (public)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Blog::query()->published()->with(['category', 'image']);

            // Search filter
            if ($request->has('search')) {
                $query->search($request->input('search'));
            }

            // Category filter
            if ($request->has('category_id')) {
                $query->where('category_id', $request->input('category_id'));
            }

            // Sorting
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $blogs = $query->paginate($request->input('per_page', 15));

            return $this->successPaginated(
                BlogResource::collection($blogs),
                'Blogs retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->error(500, 'Failed to retrieve blogs: ' . $e->getMessage());
        }
    }

    /**
     * Show a specific published blog (public)
     */
    public function show(Blog $blog): JsonResponse
    {
        // Only show published blogs to public
        if ($blog->status !== 'published') {
            return $this->error(404, 'Blog not found');
        }

        $blog->load(['category', 'image']);

        return $this->success(
            new BlogResource($blog),
            'Blog retrieved successfully'
        );
    }

    /**
     * List all blogs for admin (admin only)
     */
    public function adminIndex(Request $request): JsonResponse
    {
        // Check admin authorization
        if (!$request->user()->isAdmin()) {
            return $this->error(403, 'Unauthorized');
        }

        try {
            $query = Blog::query()->with(['category', 'image']);

            // Status filter
            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }

            // Search filter
            if ($request->has('search')) {
                $query->search($request->input('search'));
            }

            // Category filter
            if ($request->has('category_id')) {
                $query->where('category_id', $request->input('category_id'));
            }

            // Sorting
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $blogs = $query->paginate($request->input('per_page', 15));

            return $this->successPaginated(
                BlogResource::collection($blogs),
                'Blogs retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->error(500, 'Failed to retrieve blogs: ' . $e->getMessage());
        }
    }

    /**
     * Show any blog for admin (admin only)
     */
    public function adminShow(Request $request, Blog $blog): JsonResponse
    {
        // Check admin authorization
        if (!$request->user()->isAdmin()) {
            return $this->error(403, 'Unauthorized');
        }

        $blog->load(['category', 'image']);

        return $this->success(
            new BlogResource($blog),
            'Blog retrieved successfully'
        );
    }

    /**
     * Create a new blog (admin only)
     */
    public function store(Request $request): JsonResponse
    {
        // Check admin authorization
        if (!$request->user()->isAdmin()) {
            return $this->error(403, 'Unauthorized');
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'image_id' => 'nullable|exists:media,id',
            'body' => 'required|string',
            'status' => 'required|in:draft,published,archived',
        ]);

        if ($validator->fails()) {
            return $this->error(422, 'Validation failed', $validator->errors()->toArray());
        }

        try {
            $blog = Blog::create($request->all());
            $blog->load(['category', 'image']);

            return $this->success(
                new BlogResource($blog),
                'Blog created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->error(500, 'Failed to create blog: ' . $e->getMessage());
        }
    }

    /**
     * Update a blog (admin only)
     */
    public function update(Request $request, Blog $blog): JsonResponse
    {
        // Check admin authorization
        if (!$request->user()->isAdmin()) {
            return $this->error(403, 'Unauthorized');
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'image_id' => 'nullable|exists:media,id',
            'body' => 'sometimes|required|string',
            'status' => 'sometimes|required|in:draft,published,archived',
        ]);

        if ($validator->fails()) {
            return $this->error(422, 'Validation failed', $validator->errors()->toArray());
        }

        try {
            $blog->update($request->all());
            $blog->load(['category', 'image']);

            return $this->success(
                new BlogResource($blog->fresh()),
                'Blog updated successfully'
            );
        } catch (\Exception $e) {
            return $this->error(500, 'Failed to update blog: ' . $e->getMessage());
        }
    }

    /**
     * Delete a blog (admin only)
     */
    public function destroy(Request $request, Blog $blog): JsonResponse
    {
        // Check admin authorization
        if (!$request->user()->isAdmin()) {
            return $this->error(403, 'Unauthorized');
        }

        try {
            $blog->delete();

            return $this->success(null, 'Blog deleted successfully');
        } catch (\Exception $e) {
            return $this->error(500, 'Failed to delete blog: ' . $e->getMessage());
        }
    }
}
