<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ad;
use App\Models\CaishhaAd;

class CaishhaAdsController extends Controller
{
    public function index(Request $request)
    {
        $query = Ad::where('type', 'caishha')->with('caishhaAd');
        $ads = $query->paginate($request->get('limit', 15));
        return response()->json($ads);
    }

    public function store(Request $request)
    {
        $ad = Ad::create(array_merge($request->only(['user_id','title','description','category_id','city_id','country_id','brand_id','model_id','year']), ['type' => 'caishha']));
        CaishhaAd::create(array_merge(['ad_id' => $ad->id], $request->only(['offers_window_period','offers_count','sellers_visibility_period'])));
        return response()->json(['success' => true, 'data' => $ad], 201);
    }

    public function show($id)
    {
        $ad = Ad::with('caishhaAd','media')->findOrFail($id);
        if ($ad->type !== 'caishha') {
            return response()->json(['error' => 'Ad is not a caishha ad'], 400);
        }
        return response()->json($ad);
    }

    public function update(Request $request, $id)
    {
        $ad = Ad::findOrFail($id);
        if ($ad->type !== 'caishha') {
            return response()->json(['error' => 'Ad is not a caishha ad'], 400);
        }
        $ad->update($request->only(['title','description','brand_id','model_id','year']));
        $ca = $ad->caishhaAd;
        if ($ca) {
            $ca->update($request->only(['offers_window_period','offers_count','sellers_visibility_period']));
        }
        return response()->json($ad->fresh());
    }

    public function destroy($id)
    {
        $ad = Ad::findOrFail($id);
        if ($ad->type !== 'caishha') {
            return response()->json(['error' => 'Ad is not a caishha ad'], 400);
        }
        $ad->delete();
        return response()->noContent();
    }
}
