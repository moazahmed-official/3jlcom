<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\City;
use Illuminate\Http\Request;

class CityController extends BaseApiController
{
    /**
     * GET /api/v1/cities
     * Optional query: ?country_id={id}&per_page={n}
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page');
        $countryId = $request->get('country_id');

        $query = City::query()->orderBy('name');

        if ($countryId) {
            $query->where('country_id', $countryId);
        }

        if ($perPage) {
            $p = $query->paginate($perPage);
            return $this->success([
                'page' => $p->currentPage(),
                'per_page' => $p->perPage(),
                'total' => $p->total(),
                'items' => $p->items()
            ], 'Cities retrieved successfully');
        }

        $cities = $query->get();
        return $this->success($cities, 'Cities retrieved successfully');
    }
}
