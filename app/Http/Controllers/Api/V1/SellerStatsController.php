<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Ad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SellerStatsController extends BaseApiController
{
    /**
     * Get seller dashboard statistics.
     * GET /api/v1/seller/dashboard
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();
        
        // Date range filtering
        $dateFrom = $request->input('date_from', now()->subMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));

        $adsQuery = Ad::where('user_id', $user->id)
            ->whereBetween('created_at', [$dateFrom, $dateTo]);

        $summary = [
            'total_views' => Ad::where('user_id', $user->id)->sum('views_count'),
            'total_contacts' => Ad::where('user_id', $user->id)->sum('contact_count'),
            'total_clicks' => 0, // TODO: Implement click tracking
            'active_ads_count' => Ad::where('user_id', $user->id)
                ->where('status', 'published')
                ->count(),
            'total_ads_count' => Ad::where('user_id', $user->id)->count(),
            'draft_ads_count' => Ad::where('user_id', $user->id)
                ->where('status', 'draft')
                ->count(),
        ];

        // Top performing ads
        $topAds = Ad::where('user_id', $user->id)
            ->where('status', 'published')
            ->select('id', 'title', 'views_count', 'contact_count')
            ->orderByDesc('views_count')
            ->limit(5)
            ->get()
            ->map(function ($ad) {
                return [
                    'id' => $ad->id,
                    'title' => $ad->title,
                    'views' => $ad->views_count ?? 0,
                    'contacts' => $ad->contact_count ?? 0,
                    'clicks' => 0, // TODO: Implement click tracking
                ];
            });

        return $this->success([
            'summary' => $summary,
            'top_ads' => $topAds,
            'date_range' => [
                'from' => $dateFrom,
                'to' => $dateTo,
            ],
        ], 'Dashboard stats retrieved successfully');
    }

    /**
     * Get total views for seller's ads.
     * GET /api/v1/seller/stats/views
     */
    public function totalViews(Request $request)
    {
        $user = $request->user();
        $totalViews = Ad::where('user_id', $user->id)->sum('views_count');

        return $this->success([
            'total_views' => $totalViews,
        ], 'Total views retrieved successfully');
    }

    /**
     * Get views for a specific ad.
     * GET /api/v1/seller/ads/{id}/views
     */
    public function adViews(Request $request, $adId)
    {
        $user = $request->user();
        
        $ad = Ad::where('id', $adId)
            ->where('user_id', $user->id)
            ->first();

        if (!$ad) {
            return $this->error(404, 'Ad not found or you do not have permission to view it');
        }

        return $this->success([
            'ad_id' => $ad->id,
            'views_count' => $ad->views_count ?? 0,
        ], 'Ad views retrieved successfully');
    }

    /**
     * Get total contacts for seller's ads.
     * GET /api/v1/seller/stats/contacts
     */
    public function totalContacts(Request $request)
    {
        $user = $request->user();
        $totalContacts = Ad::where('user_id', $user->id)->sum('contact_count');

        return $this->success([
            'total_contacts' => $totalContacts,
        ], 'Total contacts retrieved successfully');
    }

    /**
     * Get contacts for a specific ad.
     * GET /api/v1/seller/ads/{id}/contacts
     */
    public function adContacts(Request $request, $adId)
    {
        $user = $request->user();
        
        $ad = Ad::where('id', $adId)
            ->where('user_id', $user->id)
            ->first();

        if (!$ad) {
            return $this->error(404, 'Ad not found or you do not have permission to view it');
        }

        return $this->success([
            'ad_id' => $ad->id,
            'contact_count' => $ad->contact_count ?? 0,
        ], 'Ad contacts retrieved successfully');
    }

    /**
     * Increment view count for an ad.
     * POST /api/v1/seller/ads/{id}/views
     */
    public function incrementAdViews(Request $request, $adId)
    {
        $ad = Ad::find($adId);

        if (!$ad) {
            return $this->error(404, 'Ad not found');
        }

        // Only increment if user is not the owner
        if ($request->user() && $request->user()->id === $ad->user_id) {
            return $this->success([
                'message' => 'View not counted for ad owner',
                'views_count' => $ad->views_count ?? 0,
            ], 'View not counted');
        }

        $ad->increment('views_count');

        return $this->success([
            'ad_id' => $ad->id,
            'views_count' => $ad->fresh()->views_count ?? 0,
        ], 'View count incremented successfully');
    }

    /**
     * Increment contact count for an ad.
     * POST /api/v1/seller/ads/{id}/contacts
     */
    public function incrementAdContacts(Request $request, $adId)
    {
        $ad = Ad::find($adId);

        if (!$ad) {
            return $this->error(404, 'Ad not found');
        }

        $ad->increment('contact_count');

        return $this->success([
            'ad_id' => $ad->id,
            'contact_count' => $ad->fresh()->contact_count ?? 0,
        ], 'Contact count incremented successfully');
    }

    /**
     * Get total link clicks for seller's ads.
     * GET /api/v1/seller/stats/clicks
     */
    public function totalClicks(Request $request)
    {
        $user = $request->user();
        
        // TODO: Implement actual click tracking
        $totalClicks = 0;

        return $this->success([
            'total_clicks' => $totalClicks,
        ], 'Total clicks retrieved successfully');
    }

    /**
     * Get clicks for a specific ad.
     * GET /api/v1/seller/ads/{id}/clicks
     */
    public function adClicks(Request $request, $adId)
    {
        $user = $request->user();
        
        $ad = Ad::where('id', $adId)
            ->where('user_id', $user->id)
            ->first();

        if (!$ad) {
            return $this->error(404, 'Ad not found or you do not have permission to view it');
        }

        // TODO: Implement actual click tracking
        $clickCount = 0;

        return $this->success([
            'ad_id' => $ad->id,
            'click_count' => $clickCount,
        ], 'Ad clicks retrieved successfully');
    }

    /**
     * Increment click count for an ad.
     * POST /api/v1/seller/ads/{id}/clicks
     */
    public function incrementAdClicks(Request $request, $adId)
    {
        $ad = Ad::find($adId);

        if (!$ad) {
            return $this->error(404, 'Ad not found');
        }

        // TODO: Implement actual click tracking
        
        return $this->success([
            'ad_id' => $ad->id,
            'message' => 'Click tracking not yet implemented',
        ], 'Click recorded');
    }
}
