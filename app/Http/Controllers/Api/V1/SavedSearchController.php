<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\SavedSearchResource;
use App\Models\SavedSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SavedSearchController extends BaseApiController
{
    /**
     * List all saved searches for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $searches = SavedSearch::byUser($request->user()->id)
                ->orderBy('created_at', 'desc')
                ->paginate($request->input('per_page', 15));

            return $this->successPaginated(
                SavedSearchResource::collection($searches),
                'Saved searches retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->error(500, 'Failed to retrieve saved searches: ' . $e->getMessage());
        }
    }

    /**
     * Store a new saved search
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'query_params' => 'required|array',
        ]);

        if ($validator->fails()) {
            return $this->error(422, 'Validation failed', $validator->errors()->toArray());
        }

        try {
            $search = SavedSearch::create([
                'user_id' => $request->user()->id,
                'query_params' => $request->input('query_params'),
            ]);

            return $this->success(
                new SavedSearchResource($search),
                'Search saved successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->error(500, 'Failed to save search: ' . $e->getMessage());
        }
    }

    /**
     * Display a specific saved search
     */
    public function show(Request $request, SavedSearch $savedSearch): JsonResponse
    {
        // Check ownership
        if ($savedSearch->user_id !== $request->user()->id) {
            return $this->error(403, 'Unauthorized to access this saved search');
        }

        return $this->success(
            new SavedSearchResource($savedSearch),
            'Saved search retrieved successfully'
        );
    }

    /**
     * Update a saved search
     */
    public function update(Request $request, SavedSearch $savedSearch): JsonResponse
    {
        // Check ownership
        if ($savedSearch->user_id !== $request->user()->id) {
            return $this->error(403, 'Unauthorized to update this saved search');
        }

        $validator = Validator::make($request->all(), [
            'query_params' => 'required|array',
        ]);

        if ($validator->fails()) {
            return $this->error(422, 'Validation failed', $validator->errors()->toArray());
        }

        try {
            $savedSearch->update([
                'query_params' => $request->input('query_params'),
            ]);

            return $this->success(
                new SavedSearchResource($savedSearch->fresh()),
                'Saved search updated successfully'
            );
        } catch (\Exception $e) {
            return $this->error(500, 'Failed to update saved search: ' . $e->getMessage());
        }
    }

    /**
     * Delete a saved search
     */
    public function destroy(Request $request, SavedSearch $savedSearch): JsonResponse
    {
        // Check ownership
        if ($savedSearch->user_id !== $request->user()->id) {
            return $this->error(403, 'Unauthorized to delete this saved search');
        }

        try {
            $savedSearch->delete();

            return $this->success(null, 'Saved search deleted successfully');
        } catch (\Exception $e) {
            return $this->error(500, 'Failed to delete saved search: ' . $e->getMessage());
        }
    }
}
