<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\ModelResource;
use App\Models\CarModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ModelController extends BaseApiController
{
    /**
     * List models (global) with optional search and brand filter.
     * GET /api/v1/models
     */
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->get('search', ''));
        $brandId = $request->get('brand_id');
        $perPage = min((int) $request->get('per_page', 20), 100);

        $models = CarModel::with('brand')
            ->when($brandId, fn($q) => $q->where('brand_id', $brandId))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('name_en', 'like', "%{$search}%")
                        ->orWhere('name_ar', 'like', "%{$search}%");
                });
            })
            ->orderBy('name_en')
            ->paginate($perPage);

        return $this->successPaginated(
            $models->through(fn($m) => new ModelResource($m)),
            'Models retrieved successfully'
        );
    }
}
