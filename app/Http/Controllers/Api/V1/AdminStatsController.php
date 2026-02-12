<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Ad;
use App\Models\User;
use App\Models\Category;
use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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

        // Total ads by all types
        $totalAds = Ad::count();
        $activeAds = Ad::where('status', 'published')->count();
        
        // Ads by type
        $adsByType = Ad::selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->get()
            ->pluck('count', 'type');

        // Total ads for each category
        $adsByCategory = Ad::join('categories', 'ads.category_id', '=', 'categories.id')
            ->select('categories.id', 'categories.name_en', 'categories.name_ar', DB::raw('COUNT(ads.id) as ads_count'))
            ->groupBy('categories.id', 'categories.name_en', 'categories.name_ar')
            ->get()
            ->map(function ($item) {
                return [
                    'category_id' => $item->id,
                    'category_name_en' => $item->name_en,
                    'category_name_ar' => $item->name_ar,
                    'total_ads' => $item->ads_count,
                ];
            });

        // Total categories
        $totalCategories = Category::count();
        $activeCategories = Category::where('status', 'active')->count();

        // Total blogs
        $totalBlogs = Blog::count();
        $publishedBlogs = Blog::where('status', 'published')->count();

        // Total users by all types
        $totalUsers = User::count();
        
        // Total users by account type (excluding admins)
        $usersByAccountType = User::select('account_type', DB::raw('COUNT(*) as count'))
            ->whereNotNull('account_type')
            ->groupBy('account_type')
            ->get()
            ->pluck('count', 'account_type');
        
        // Get admin users count
        $adminUsers = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['admin', 'super-admin']);
        })->count();
        
        // Non-admin users count
        $nonAdminUsers = $totalUsers - $adminUsers;

        // Total views and contacts
        $totalViews = Ad::sum('views_count') ?? 0;
        $totalContacts = Ad::sum('contact_count') ?? 0;

        // Build response data
        $responseData = [
            'total_ads' => $totalAds,
            'active_ads' => $activeAds,
            'ads_by_type' => $adsByType,
            'ads_by_category' => $adsByCategory->values(),
            'total_categories' => $totalCategories,
            'active_categories' => $activeCategories,
            'total_blogs' => $totalBlogs,
            'published_blogs' => $publishedBlogs,
            'total_users' => $totalUsers,
            'non_admin_users' => $nonAdminUsers,
            'admin_users' => $adminUsers,
            'users_by_account_type' => [
                'individual' => $usersByAccountType['individual'] ?? 0,
                'seller' => $usersByAccountType['seller'] ?? 0,
                'showroom' => $usersByAccountType['showroom'] ?? 0,
                'dealer' => $usersByAccountType['dealer'] ?? 0,
                'marketeer' => $usersByAccountType['marketeer'] ?? 0,
            ],
            'total_views' => $totalViews,
            'total_contacts' => $totalContacts,
        ];

        // If caller requested time-series / chart-ready data, provide series
        if ($request->boolean('time_series') || $request->filled('start') || $request->filled('end')) {
            $end = $request->filled('end') ? Carbon::parse($request->get('end'))->endOfDay() : Carbon::now();
            $start = $request->filled('start') ? Carbon::parse($request->get('start'))->startOfDay() : $end->copy()->subDays(30);
            $interval = $request->get('interval', 'day'); // day|week

            // Build date buckets
            $period = new \DatePeriod(
                $start->toDateTimeImmutable(),
                new \DateInterval($interval === 'week' ? 'P7D' : 'P1D'),
                $end->toDateTimeImmutable()->modify('+1 day')
            );

            $userGrowth = [];
            $adsPublished = [];

            // Query aggregated counts grouped by date for users and ads
            $usersByDate = DB::table('users')
                ->select(DB::raw("DATE(created_at) as day"), DB::raw('COUNT(*) as count'))
                ->whereBetween('created_at', [$start->toDateTimeString(), $end->toDateTimeString()])
                ->groupBy('day')
                ->pluck('count', 'day')
                ->toArray();

            $adsByDate = DB::table('ads')
                ->select(DB::raw("DATE(published_at) as day"), DB::raw('COUNT(*) as count'))
                ->whereNotNull('published_at')
                ->whereBetween('published_at', [$start->toDateTimeString(), $end->toDateTimeString()])
                ->groupBy('day')
                ->pluck('count', 'day')
                ->toArray();

            foreach ($period as $dt) {
                $day = Carbon::instance($dt)->toDateString();
                $userGrowth[] = [
                    'timestamp' => $day,
                    'value' => (int) ($usersByDate[$day] ?? 0),
                ];
                $adsPublished[] = [
                    'timestamp' => $day,
                    'value' => (int) ($adsByDate[$day] ?? 0),
                ];
            }

            $responseData['time_series'] = [
                'userGrowth' => $userGrowth,
                'adsPublished' => $adsPublished,
            ];

            return $this->success($responseData, 'Admin dashboard stats retrieved successfully (with time-series)');
        }

        return $this->success($responseData, 'Admin dashboard stats retrieved successfully');
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
        $activities = \App\Models\AuditLog::with('actor')
            ->orderBy('timestamp', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'user' => [
                        'id' => $log->actor_id,
                        'name' => $log->actor_name ?? $log->actor->name ?? 'System',
                        'email' => $log->actor->email ?? null,
                    ],
                    'action' => $log->action_type,
                    'resource_type' => $log->resource_type,
                    'resource_id' => $log->resource_id,
                    'description' => $this->formatActivityDescription($log),
                    'severity' => $log->severity,
                    'timestamp' => $log->timestamp->toIso8601String(),
                    'ip_address' => $log->ip_address,
                ];
            });

        return $this->success([
            'activities' => $activities,
            'total' => $activities->count(),
        ], 'Activity feed retrieved successfully');
    }

    /**
     * Get ads distribution by category for charts
     * GET /api/admin/stats/ads-by-category-chart
     */
    public function adsByCategoryChart(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return $this->error(403, 'Unauthorized');
        }

        // Get total ads count
        $totalAds = Ad::count();

        if ($totalAds === 0) {
            return $this->success([
                'total_ads' => 0,
                'categories' => [],
            ], 'No ads found');
        }

        // Get ads count by category with percentages
        $adsByCategory = Ad::join('categories', 'ads.category_id', '=', 'categories.id')
            ->select(
                'categories.id',
                'categories.name_en',
                'categories.name_ar',
                DB::raw('COUNT(ads.id) as ads_count'),
                DB::raw('ROUND((COUNT(ads.id) * 100.0 / ' . $totalAds . '), 2) as percentage')
            )
            ->groupBy('categories.id', 'categories.name_en', 'categories.name_ar')
            ->orderBy('ads_count', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'category_id' => $item->id,
                    'category_name_en' => $item->name_en,
                    'category_name_ar' => $item->name_ar,
                    'ads_count' => $item->ads_count,
                    'percentage' => (float) $item->percentage,
                ];
            });

        return $this->success([
            'total_ads' => $totalAds,
            'categories' => $adsByCategory->values(),
        ], 'Ads distribution by category retrieved successfully');
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
