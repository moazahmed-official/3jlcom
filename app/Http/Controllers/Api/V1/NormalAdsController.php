<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ad;
use App\Models\NormalAd;

class NormalAdsController extends Controller
{
    public function index(Request $request)
    {
        $query = Ad::where('type', 'normal')->with('normalAd');
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }
        $ads = $query->paginate($request->get('limit', 15));
        return response()->json($ads);
    }

    public function store(Request $request)
    {
        $ad = Ad::create(array_merge($request->only(['user_id','title','description','category_id','city_id','country_id','brand_id','model_id','year']), ['type' => 'normal']));
        NormalAd::create(array_merge(['ad_id' => $ad->id], $request->only(['price_cash','installment_id','start_time','update_time'])));
        return response()->json(['success' => true, 'data' => $ad], 201);
    }

    public function show($id)
    {
        $ad = Ad::with('normalAd','media')->findOrFail($id);
        if ($ad->type !== 'normal') {
            return response()->json(['error' => 'Ad is not a normal ad'], 400);
        }
        return response()->json($ad);
    }

    public function update(Request $request, $id)
    {
        $ad = Ad::findOrFail($id);
        if ($ad->type !== 'normal') {
            return response()->json(['error' => 'Ad is not a normal ad'], 400);
        }
        $ad->update($request->only(['title','description','brand_id','model_id','year']));
        $normal = $ad->normalAd;
        if ($normal) {
            $normal->update($request->only(['price_cash','installment_id']));
        }
        return response()->json($ad->fresh());
    }

    public function destroy($id)
    {
        $ad = Ad::findOrFail($id);
        if ($ad->type !== 'normal') {
            return response()->json(['error' => 'Ad is not a normal ad'], 400);
        }
        $ad->delete();
        return response()->noContent();
    }

    public function republish($id)
    {
        // enqueue republish job or update timestamps — placeholder
        $ad = Ad::findOrFail($id);
        if ($ad->type !== 'normal') {
            return response()->json(['error' => 'Ad is not a normal ad'], 400);
        }
        // simple action: touch updated_at
        $ad->touch();
        return response()->json(['success' => true]);
    }
}
