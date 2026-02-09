<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\LogsAudit;
use App\Models\PageContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PageContentController extends Controller
{
    use LogsAudit;
    /**
     * Display all page contents (Admin only).
     */
    public function index(): JsonResponse
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['Only admins can access page content management']]
            ], 403);
        }

        $pages = PageContent::all();

        return response()->json([
            'status' => 'success',
            'message' => 'Page contents retrieved successfully',
            'data' => $pages
        ]);
    }

    /**
     * Display a single page content by key (Admin only).
     */
    public function show(string $pageKey): JsonResponse
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['Only admins can access page content management']]
            ], 403);
        }

        if (!PageContent::isValidPageKey($pageKey)) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Invalid page key',
                'errors' => ['page_key' => ['Valid keys are: ' . implode(', ', PageContent::VALID_PAGES)]]
            ], 404);
        }

        $page = PageContent::where('page_key', $pageKey)->first();

        if (!$page) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Page content not found',
                'errors' => ['page_key' => ["No content found for page: {$pageKey}"]]
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Page content retrieved successfully',
            'data' => $page
        ]);
    }

    /**
     * Update a page content by key (Admin only).
     */
    public function update(Request $request, string $pageKey): JsonResponse
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['Only admins can modify page content']]
            ], 403);
        }

        if (!PageContent::isValidPageKey($pageKey)) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Invalid page key',
                'errors' => ['page_key' => ['Valid keys are: ' . implode(', ', PageContent::VALID_PAGES)]]
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title_en' => 'sometimes|required|string|max:255',
            'title_ar' => 'sometimes|required|string|max:255',
            'body_en' => 'sometimes|required|string',
            'body_ar' => 'sometimes|required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'code' => 422,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $page = PageContent::where('page_key', $pageKey)->first();

            if (!$page) {
                return response()->json([
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'Page content not found',
                    'errors' => ['page_key' => ["No content found for page: {$pageKey}"]]
                ], 404);
            }

            $oldData = $page->only(['title_en', 'title_ar']);
            $page->update($request->only(['title_en', 'title_ar', 'body_en', 'body_ar']));

            // Clear cache
            PageContent::clearCache($pageKey);

            $this->auditLog(
                actionType: 'page_content.updated',
                resourceType: 'page_content',
                resourceId: $page->id,
                details: [
                    'page_key' => $pageKey,
                    'old_title_en' => $oldData['title_en'],
                    'new_title_en' => $page->title_en
                ],
                severity: 'warning'
            );

            Log::info('Page content updated', [
                'user_id' => auth()->id(),
                'page_key' => $pageKey,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Page content updated successfully',
                'data' => $page->fresh()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update page content', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'page_key' => $pageKey,
            ]);

            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to update page content',
                'errors' => ['general' => ['An unexpected error occurred']]
            ], 500);
        }
    }

    // =====================
    // PUBLIC ENDPOINTS
    // =====================

    /**
     * Get a public page content by key (no auth required).
     */
    public function publicShow(string $pageKey): JsonResponse
    {
        if (!PageContent::isValidPageKey($pageKey)) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Page not found',
                'errors' => ['page_key' => ['Invalid page key']]
            ], 404);
        }

        $page = PageContent::getByKey($pageKey);

        if (!$page) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Page content not found',
                'errors' => ['page_key' => ["No content found for page: {$pageKey}"]]
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Page content retrieved successfully',
            'data' => [
                'page_key' => $page->page_key,
                'title_en' => $page->title_en,
                'title_ar' => $page->title_ar,
                'body_en' => $page->body_en,
                'body_ar' => $page->body_ar,
            ]
        ]);
    }

    /**
     * Get all public page contents (no auth required).
     */
    public function publicIndex(): JsonResponse
    {
        $pages = PageContent::getAllPages();

        return response()->json([
            'status' => 'success',
            'message' => 'Page contents retrieved successfully',
            'data' => $pages
        ]);
    }
}
