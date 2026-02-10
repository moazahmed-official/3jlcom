<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Ad;
use App\Models\User;
use Illuminate\Http\Request;

class AdminStatsController extends BaseApiController
{
    /**
     * Get ad views count by ad ID
     * GET /api/admin/stats/ads/{id}/views
     */
    public function adViews(Request $request, $adId)
    {
        if (!$request->user()->isAdmin()) {
            return $this->error(403, 'Unauthorized');
        }

        $ad = Ad::find($adId);

        if (!$ad) {
            return $this->error(404, 'Ad not found');
        }

        return $this->success([
            'ad_id' => $ad->id,
            'views_count' => $ad->views_count ?? 0,
        ], 'Ad views retrieved successfully');
    }

    /**
     * Get ad clicks count by ad ID
     * GET /api/admin/stats/ads/{id}/clicks
     */
    public function adClicks(Request $request, $adId)
    {
        if (!$request->user()->isAdmin()) {
            return $this->error(403, 'Unauthorized');
        }

        $ad = Ad::find($adId);

        if (!$ad) {
            return $this->error(404, 'Ad not found');
        }

        // TODO: Implement click tracking
        return $this->success([
            'ad_id' => $ad->id,
            'clicks_count' => 0,
        ], 'Ad clicks retrieved successfully');
    }

    /**
     * Get dealer statistics
     * GET /api/admin/stats/dealer/{id}
     */
    public function dealerStats(Request $request, $userId)
    {
        if (!$request->user()->isAdmin()) {
            return $this->error(403, 'Unauthorized');
        }

        $dealer = User::find($userId);

        if (!$dealer) {
            return $this->error(404, 'Dealer not found');
        }

        // Get dealer ads statistics
        $totalAds = Ad::where('user_id', $userId)->count();
        $activeAds = Ad::where('user_id', $userId)->where('status', 'published')->count();
        $totalViews = Ad::where('user_id', $userId)->sum('views_count');
        $totalContacts = Ad::where('user_id', $userId)->sum('contact_count');

        // Get ads by type
        $adsByType = Ad::where('user_id', $userId)
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->get()
            ->pluck('count', 'type');

        return $this->success([
            'dealer_id' => $dealer->id,
            'dealer_name' => $dealer->name,
            'dealer_email' => $dealer->email,
            'total_ads' => $totalAds,
            'active_ads' => $activeAds,
            'total_views' => $totalViews ?? 0,
            'total_contacts' => $totalContacts ?? 0,
            'ads_by_type' => $adsByType,
        ], 'Dealer stats retrieved successfully');
    }

    /**
     * Get user statistics
     * GET /api/admin/stats/user/{id}
     */
    public function userStats(Request $request, $userId)
    {
        if (!$request->user()->isAdmin()) {
            return $this->error(403, 'Unauthorized');
        }

        $user = User::find($userId);

        if (!$user) {
            return $this->error(404, 'User not found');
        }

        // Get user statistics
        $totalAds = Ad::where('user_id', $userId)->count();
        $activeAds = Ad::where('user_id', $userId)->where('status', 'published')->count();
        $draftAds = Ad::where('user_id', $userId)->where('status', 'draft')->count();
        $totalViews = Ad::where('user_id', $userId)->sum('views_count');

        return $this->success([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'total_ads' => $totalAds,
            'active_ads' => $activeAds,
            'draft_ads' => $draftAds,
            'total_views' => $totalViews ?? 0,
        ], 'User stats retrieved successfully');
    }

    /**
     * Get number of ads by type
     * GET /api/admin/stats/ads/{type}
     */
    public function adsByType(Request $request, $type)
    {
        if (!$request->user()->isAdmin()) {
            return $this->error(403, 'Unauthorized');
        }

        // Validate ad type
        $validTypes = ['normal', 'caishha', 'findit', 'auction', 'unique'];
        if (!in_array($type, $validTypes)) {
            return $this->error(400, 'Invalid ad type. Valid types: ' . implode(', ', $validTypes));
        }

        $count = Ad::where('type', $type)->count();
        $activeCount = Ad::where('type', $type)->where('status', 'published')->count();
        $draftCount = Ad::where('type', $type)->where('status', 'draft')->count();

        return $this->success([
            'ad_type' => $type,
            'total_count' => $count,
            'active_count' => $activeCount,
            'draft_count' => $draftCount,
        ], "Stats for {$type} ads retrieved successfully");
    }

    /**
     * Get overall platform statistics (dashboard)
     * GET /api/admin/stats/dashboard
     */
    public function dashboard(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return $this->error(403, 'Unauthorized');
        }

        $totalUsers = User::count();
        $totalAds = Ad::count();
        $activeAds = Ad::where('status', 'published')->count();
        $totalViews = Ad::sum('views_count');
        $totalContacts = Ad::sum('contact_count');

        // Ads by type
        $adsByType = Ad::selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->get()
            ->pluck('count', 'type');

        return $this->success([
            'total_users' => $totalUsers,
            'total_ads' => $totalAds,
            'active_ads' => $activeAds,
            'total_views' => $totalViews ?? 0,
            'total_contacts' => $totalContacts ?? 0,
            'ads_by_type' => $adsByType,
        ], 'Admin dashboard stats retrieved successfully');
    }

    /**
     * Get recent activity feed for dashboard
     * GET /api/admin/dashboard/activity
     */
    public function activity(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return $this->error(403, 'Unauthorized');
        }

        $limit = $request->get('limit', 50);

        // Get recent audit logs for activity feed
        $activities = \App\Models\AuditLog::with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'user' => [
                        'id' => $log->user_id,
                        'name' => $log->user->name ?? 'System',
                        'email' => $log->user->email ?? null,
                    ],
                    'action' => $log->action_type,
                    'resource_type' => $log->resource_type,
                    'resource_id' => $log->resource_id,
                    'description' => $this->formatActivityDescription($log),
                    'severity' => $log->severity,
                    'timestamp' => $log->created_at->toIso8601String(),
                    'ip_address' => $log->ip_address,
                ];
            });

        return $this->success([
            'activities' => $activities,
            'total' => $activities->count(),
        ], 'Activity feed retrieved successfully');
    }

    /**
     * Format activity description
     */
    private function formatActivityDescription($log): string
    {
        $action = str_replace('.', ' ', $log->action_type);
        $resource = $log->resource_type ?? 'resource';
        
        return ucfirst($action) . " {$resource}" . ($log->resource_id ? " #{$log->resource_id}" : "");
    }
}
