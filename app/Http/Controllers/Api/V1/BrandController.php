<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Brand\StoreBrandRequest;
use App\Http\Requests\Brand\StoreBrandModelRequest;
use App\Http\Resources\BrandResource;
use App\Http\Resources\ModelResource;
use App\Models\Brand;
use App\Models\CarModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Brand\UpdateBrandRequest;
use App\Http\Requests\Brand\UpdateBrandModelRequest;

class BrandController extends BaseApiController
{
    /**
     * Display a listing of brands.
     *
     * GET /api/v1/brands
     */
    public function index(Request $request): JsonResponse
    {
        $brands = Brand::orderBy('name_en')
            ->paginate($request->get('per_page', 20));

        return $this->successPaginated(
            $brands->through(fn($brand) => new BrandResource($brand)),
            'Brands retrieved successfully'
        );
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
        $models = $brand->models()
            ->orderBy('name_en')
            ->paginate($request->get('per_page', 20));

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

        if ($request->hasFile('image')) {
            // delete old image if exists
            if ($brand->image) {
                Storage::disk('public')->delete($brand->image);
            }
            $validated['image'] = $request->file('image')->store('brands', 'public');
        }

        $brand->update($validated);

        return $this->success(new BrandResource($brand->fresh()), 'Brand updated successfully');
    }

    /**
     * Remove the specified brand.
     *
     * DELETE /api/v1/brands/{brand}
     */
    public function destroy(Brand $brand): JsonResponse
    {
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

        if ($request->hasFile('image')) {
            if ($model->image) {
                Storage::disk('public')->delete($model->image);
            }
            $validated['image'] = $request->file('image')->store('models', 'public');
        }

        $model->update($validated);
        $model->load('brand');

        return $this->success(new ModelResource($model->fresh()), 'Model updated successfully');
    }

    /**
     * Delete a model for a brand.
     *
     * DELETE /api/v1/brands/{brand}/models/{model}
     */
    public function destroyModel(Brand $brand, CarModel $model): JsonResponse
    {
        if ($model->image) {
            Storage::disk('public')->delete($model->image);
        }

        $model->delete();

        return $this->success(null, 'Model deleted successfully');
    }
}