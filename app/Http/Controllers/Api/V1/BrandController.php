<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Brand\StoreBrandRequest;
use App\Http\Requests\Brand\StoreBrandModelRequest;
use App\Http\Resources\BrandResource;
use App\Http\Resources\ModelResource;
use App\Http\Traits\LogsAudit;
use App\Models\Brand;
use App\Models\CarModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Brand\UpdateBrandRequest;
use App\Http\Requests\Brand\UpdateBrandModelRequest;

class BrandController extends BaseApiController
{
    use LogsAudit;
    /**
     * Display a listing of brands.
     *
     * GET /api/v1/brands
     */
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->get('search', ''));
        $perPage = min((int) $request->get('per_page', 20), 100);

        $brands = Brand::when($search !== '', function ($q) use ($search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('name_en', 'like', "%{$search}%")
                        ->orWhere('name_ar', 'like', "%{$search}%");
                });
            })
            ->orderBy('name_en')
            ->paginate($perPage);

        return $this->successPaginated(
            $brands->through(fn($brand) => new BrandResource($brand)),
            'Brands retrieved successfully'
        );
    }

    /**
     * Display a single brand.
     *
     * GET /api/v1/brands/{brand}
     */
    public function show(Brand $brand): JsonResponse
    {
        return $this->success(new BrandResource($brand), 'Brand retrieved successfully');
    }

    /**
     * Store a newly created brand.
     *
     * POST /api/v1/brands
     */
    public function store(StoreBrandRequest $request): JsonResponse
    {
        $validated = $request->validated();
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('brands', 'public');
        }

        $brand = Brand::create($validated);

        $this->auditLog(
            actionType: 'brand.created',
            resourceType: 'brand',
            resourceId: $brand->id,
            details: ['name_en' => $brand->name_en, 'name_ar' => $brand->name_ar],
            severity: 'info'
        );

        return $this->success(
            new BrandResource($brand),
            'Brand created successfully',
            201
        );
    }

    /**
     * Display models for a specific brand.
     *
     * GET /api/v1/brands/{brand}/models
     */
    public function models(Request $request, Brand $brand): JsonResponse
    {
        $search = trim((string) $request->get('search', ''));
        $perPage = min((int) $request->get('per_page', 20), 100);

        $models = $brand->models()
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('name_en', 'like', "%{$search}%")
                        ->orWhere('name_ar', 'like', "%{$search}%");
                });
            })
            ->orderBy('name_en')
            ->paginate($perPage);

        return $this->successPaginated(
            $models->through(fn($model) => new ModelResource($model)),
            'Models retrieved successfully'
        );
    }

    /**
     * Store a newly created model for a brand.
     *
     * POST /api/v1/brands/{brand}/models
     */
    public function storeModel(StoreBrandModelRequest $request, Brand $brand): JsonResponse
    {
        $validated = $request->validated();
        $validated['brand_id'] = $brand->id;
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('models', 'public');
        }

        $model = CarModel::create($validated);
        $model->load('brand');

        $this->auditLog(
            actionType: 'model.created',
            resourceType: 'car_model',
            resourceId: $model->id,
            details: [
                'brand_id' => $brand->id,
                'brand_name' => $brand->name_en,
                'model_name_en' => $model->name_en,
                'model_name_ar' => $model->name_ar
            ],
            severity: 'info'
        );

        return $this->success(
            new ModelResource($model),
            'Model created successfully',
            201
        );
    }

    /**
     * Update the specified brand.
     *
     * PUT /api/v1/brands/{brand}
     */
    public function update(UpdateBrandRequest $request, Brand $brand): JsonResponse
    {
        $validated = $request->validated();
        $oldData = $brand->only(['name_en', 'name_ar']);

        if ($request->hasFile('image')) {
            // delete old image if exists
            if ($brand->image) {
                Storage::disk('public')->delete($brand->image);
            }
            $validated['image'] = $request->file('image')->store('brands', 'public');
        }

        $brand->update($validated);

        $this->auditLog(
            actionType: 'brand.updated',
            resourceType: 'brand',
            resourceId: $brand->id,
            details: ['old' => $oldData, 'new' => $brand->only(['name_en', 'name_ar'])],
            severity: 'info'
        );

        return $this->success(new BrandResource($brand->fresh()), 'Brand updated successfully');
    }

    /**
     * Remove the specified brand.
     *
     * DELETE /api/v1/brands/{brand}
     */
    public function destroy(Brand $brand): JsonResponse
    {
        $this->auditLogDestructive(
            actionType: 'brand.deleted',
            resourceType: 'brand',
            resourceId: $brand->id,
            details: ['name_en' => $brand->name_en, 'name_ar' => $brand->name_ar]
        );

        if ($brand->image) {
            Storage::disk('public')->delete($brand->image);
        }

        // Optionally delete related models or enforce FK cascade
        $brand->delete();

        return $this->success(null, 'Brand deleted successfully');
    }

    /**
     * Update a model for a brand.
     *
     * PUT /api/v1/brands/{brand}/models/{model}
     */
    public function updateModel(UpdateBrandModelRequest $request, Brand $brand, CarModel $model): JsonResponse
    {
        $validated = $request->validated();
        $oldData = $model->only(['name_en', 'name_ar']);

        if ($request->hasFile('image')) {
            if ($model->image) {
                Storage::disk('public')->delete($model->image);
            }
            $validated['image'] = $request->file('image')->store('models', 'public');
        }

        $model->update($validated);
        $model->load('brand');

        $this->auditLog(
            actionType: 'model.updated',
            resourceType: 'car_model',
            resourceId: $model->id,
            details: [
                'brand_id' => $brand->id,
                'brand_name' => $brand->name_en,
                'old' => $oldData,
                'new' => $model->only(['name_en', 'name_ar'])
            ],
            severity: 'info'
        );

        return $this->success(new ModelResource($model->fresh()), 'Model updated successfully');
    }

    /**
     * Delete a model for a brand.
     *
     * DELETE /api/v1/brands/{brand}/models/{model}
     */
    public function destroyModel(Brand $brand, CarModel $model): JsonResponse
    {
        $this->auditLogDestructive(
            actionType: 'model.deleted',
            resourceType: 'car_model',
            resourceId: $model->id,
            details: [
                'brand_id' => $brand->id,
                'brand_name' => $brand->name_en,
                'model_name_en' => $model->name_en,
                'model_name_ar' => $model->name_ar
            ]
        );

        if ($model->image) {
            Storage::disk('public')->delete($model->image);
        }

        $model->delete();

        return $this->success(null, 'Model deleted successfully');
    }
}