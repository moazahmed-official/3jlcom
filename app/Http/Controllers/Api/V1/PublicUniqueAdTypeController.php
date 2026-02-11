<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\UniqueAdTypeDefinitionResource;
use App\Models\UniqueAdTypeDefinition;
use App\Services\UniqueAdTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicUniqueAdTypeController extends BaseApiController
{
    protected UniqueAdTypeService $typeService;

    public function __construct(UniqueAdTypeService $typeService)
    {
        $this->typeService = $typeService;
    }

    /**
     * Get all available unique ad types (public endpoint).
     *
     * GET /api/v1/unique-ad-types
     */
    public function index(Request $request): JsonResponse
    {
        $query = UniqueAdTypeDefinition::active()->byPriority();

        $types = $query->get();

        return $this->success(
            UniqueAdTypeDefinitionResource::collection($types),
            'Available unique ad types retrieved successfully'
        );
    }

    /**
     * Get available unique ad types for authenticated user based on their package.
     *
     * GET /api/v1/user/available-unique-ad-types
     */
    public function availableForUser(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return $this->error(401, 'Authentication required');
        }

        $availableTypes = $this->typeService->getAvailableTypesForUser($user);

        return $this->success(
            $availableTypes,
            'Your available unique ad types retrieved successfully'
        );
    }

    /**
     * Get a specific unique ad type details.
     *
     * GET /api/v1/unique-ad-types/{slug}
     */
    public function show(string $slug): JsonResponse
    {
        $type = UniqueAdTypeDefinition::where('slug', $slug)
            ->where('active', true)
            ->first();

        if (!$type) {
            return $this->error(404, 'Unique ad type not found');
        }

        return $this->success(
            new UniqueAdTypeDefinitionResource($type),
            'Unique ad type retrieved successfully'
        );
    }
}
