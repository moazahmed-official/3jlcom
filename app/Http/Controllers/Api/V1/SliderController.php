<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSliderRequest;
use App\Http\Requests\UpdateSliderRequest;
use App\Http\Resources\SliderResource;
use App\Models\Slider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SliderController extends Controller
{
    /**
     * List sliders (public - shows only active sliders unless admin requests all)
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Slider::query()->with('media');

        // Public users only see active sliders; admins can see all with ?include_inactive=1
        $user = $request->user('sanctum') ?? $request->user();
        $isAdmin = $user && method_exists($user, 'isAdmin') ? $user->isAdmin() : false;
        $includeInactive = $request->boolean('include_inactive');

        if (!$isAdmin || !$includeInactive) {
            $query->active();
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->category($request->category_id);
        }

        // Filter by status (admin only)
        if ($isAdmin && $request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Order by id, then by created_at
        $query->ordered('asc')->orderBy('created_at', 'desc');

        // Pagination
        $limit = min($request->get('limit', 15), 100);
        $sliders = $query->paginate($limit);

        return SliderResource::collection($sliders);
    }

    /**
     * Get slider details (public)
     */
    public function show($id): JsonResponse
    {
        $slider = Slider::with('media')->find($id);

        if (!$slider) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Slider not found',
                'errors' => ['slider' => ['The requested slider does not exist']]
            ], 404);
        }

        // Public users can only see active sliders. Use Sanctum guard when available so
        // bearer tokens are recognized on public routes (no auth middleware applied).
        $user = request()->user('sanctum') ?? request()->user();
        $isAdmin = $user && method_exists($user, 'isAdmin') ? $user->isAdmin() : false;
        if (!$isAdmin && !$slider->isActive()) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Slider not found',
                'errors' => ['slider' => ['The requested slider does not exist']]
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Slider retrieved successfully',
            'data' => new SliderResource($slider)
        ], 200);
    }

    /**
     * Create a new slider (admin only)
     */
    public function store(StoreSliderRequest $request): JsonResponse
    {
        // Authorization check - admin only
        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['Only admins can create sliders']]
            ], 403);
        }

        try {
            $slider = DB::transaction(function () use ($request) {
                $slider = Slider::create([
                    'name' => $request->name,
                    'image_id' => $request->image_id,
                    'category_id' => $request->category_id,
                    'value' => $request->value,
                    'status' => $request->status ?? 'active',
                ]);

                return $slider;
            });

            $slider->load('media');

            Log::info('Slider created', [
                'slider_id' => $slider->id,
                'name' => $slider->name,
                'category_id' => $slider->category_id,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Slider created successfully',
                'data' => new SliderResource($slider)
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to create slider', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id(),
                'request_data' => $request->validated()
            ]);

            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to create slider',
                'errors' => ['server' => ['An unexpected error occurred while creating the slider']]
            ], 500);
        }
    }

    /**
     * Update a slider (admin only)
     */
    public function update(UpdateSliderRequest $request, $id): JsonResponse
    {
        // Authorization check - admin only
        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['Only admins can update sliders']]
            ], 403);
        }

        $slider = Slider::find($id);

        if (!$slider) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Slider not found',
                'errors' => ['slider' => ['The requested slider does not exist']]
            ], 404);
        }

        try {
            DB::transaction(function () use ($request, $slider) {
                $slider->update($request->only([
                    'name',
                    'image_id',
                    'category_id',
                    'value',
                    'status',
                ]));
            });

            $slider->load('media');

            Log::info('Slider updated', [
                'slider_id' => $slider->id,
                'name' => $slider->name,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Slider updated successfully',
                'data' => new SliderResource($slider)
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to update slider', [
                'error' => $e->getMessage(),
                'slider_id' => $id,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to update slider',
                'errors' => ['server' => ['An unexpected error occurred while updating the slider']]
            ], 500);
        }
    }

    /**
     * Delete a slider (admin only)
     */
    public function destroy($id): JsonResponse
    {
        // Authorization check - admin only
        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['Only admins can delete sliders']]
            ], 403);
        }

        $slider = Slider::find($id);

        if (!$slider) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Slider not found',
                'errors' => ['slider' => ['The requested slider does not exist']]
            ], 404);
        }

        try {
            $sliderId = $slider->id;
            $sliderName = $slider->name;

            DB::transaction(function () use ($slider) {
                $slider->delete();
            });

            Log::info('Slider deleted', [
                'slider_id' => $sliderId,
                'name' => $sliderName,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Slider deleted successfully',
                'data' => null
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to delete slider', [
                'error' => $e->getMessage(),
                'slider_id' => $id,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to delete slider',
                'errors' => ['server' => ['An unexpected error occurred while deleting the slider']]
            ], 500);
        }
    }

    /**
     * Activate a slider (admin only)
     */
    public function activate($id): JsonResponse
    {
        // Authorization check - admin only
        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['Only admins can activate sliders']]
            ], 403);
        }

        $slider = Slider::find($id);

        if (!$slider) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Slider not found',
                'errors' => ['slider' => ['The requested slider does not exist']]
            ], 404);
        }

        if ($slider->isActive()) {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'message' => 'Slider is already active',
                'errors' => ['status' => ['This slider is already in active status']]
            ], 400);
        }

        try {
            $slider->activate();
            $slider->load('media');

            Log::info('Slider activated', [
                'slider_id' => $slider->id,
                'name' => $slider->name,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Slider activated successfully',
                'data' => new SliderResource($slider)
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to activate slider', [
                'error' => $e->getMessage(),
                'slider_id' => $id,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to activate slider',
                'errors' => ['server' => ['An unexpected error occurred while activating the slider']]
            ], 500);
        }
    }

    /**
     * Deactivate a slider (admin only)
     */
    public function deactivate($id): JsonResponse
    {
        // Authorization check - admin only
        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['Only admins can deactivate sliders']]
            ], 403);
        }

        $slider = Slider::find($id);

        if (!$slider) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Slider not found',
                'errors' => ['slider' => ['The requested slider does not exist']]
            ], 404);
        }

        if (!$slider->isActive()) {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'message' => 'Slider is already inactive',
                'errors' => ['status' => ['This slider is already in inactive status']]
            ], 400);
        }

        try {
            $slider->deactivate();
            $slider->load('media');

            Log::info('Slider deactivated', [
                'slider_id' => $slider->id,
                'name' => $slider->name,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Slider deactivated successfully',
                'data' => new SliderResource($slider)
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to deactivate slider', [
                'error' => $e->getMessage(),
                'slider_id' => $id,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to deactivate slider',
                'errors' => ['server' => ['An unexpected error occurred while deactivating the slider']]
            ], 500);
        }
    }
}
