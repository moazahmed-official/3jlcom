<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Country;
use App\Models\City;
use Illuminate\Http\Request;

class CountryController extends BaseApiController
{
    /**
     * GET /api/countries
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page');
        $query = Country::orderBy('name');

        if ($perPage) {
            $p = $query->paginate($perPage);
            return $this->success([
                'page' => $p->currentPage(),
                'per_page' => $p->perPage(),
                'total' => $p->total(),
                'items' => $p->items()
            ], 'Countries retrieved successfully');
        }

        $countries = $query->get();
        return $this->success($countries, 'Countries retrieved successfully');
    }

    /**
     * GET /api/countries/{country}/cities
     */
    public function cities(Request $request, Country $country)
    {
        $perPage = $request->get('per_page');
        $query = $country->cities()->orderBy('name');

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
