<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Traits\LogsAudit;
use App\Models\AdminSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class AdminSettingsController extends BaseApiController
{
    use LogsAudit;

    /**
     * Get all admin settings
     * GET /api/v1/admin/settings
     */
    public function index(Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return $this->error(403, 'Unauthorized');
        }

        $group = $request->get('group'); // Filter by group: general, features, notifications, email
        
        $query = AdminSetting::query();
        
        if ($group) {
            $query->where('group', $group);
        }
        
        $settings = $query->orderBy('group')->orderBy('key')->get();
        
        // Transform settings into key-value pairs grouped by category
        $grouped = $settings->groupBy('group')->map(function ($items) {
            return $items->mapWithKeys(function ($item) {
                return [$item->key => [
                    'value' => $this->castValue($item->value, $item->type),
                    'type' => $item->type,
                    'description' => $item->description,
                ]];
            });
        });

        return $this->success($grouped, 'Settings retrieved successfully');
    }

    /**
     * Update admin settings
     * PUT /api/v1/admin/settings
     */
    public function update(Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return $this->error(403, 'Unauthorized');
        }

        $settings = $request->all();
        $updated = [];

        foreach ($settings as $key => $value) {
            $setting = AdminSetting::where('key', $key)->first();
            
            if (!$setting) {
                // Create new setting if it doesn't exist
                $setting = AdminSetting::create([
                    'key' => $key,
                    'value' => is_array($value) ? json_encode($value) : $value,
                    'type' => $this->inferType($value),
                    'group' => 'general',
                ]);
            } else {
                $setting->update([
                    'value' => is_array($value) ? json_encode($value) : $value,
                ]);
            }
            
            $updated[$key] = $this->castValue($setting->value, $setting->type);
        }

        // Audit log
        $this->auditLog(
            actionType: 'settings.updated',
            resourceType: 'admin_settings',
            resourceId: null,
            details: ['updated_keys' => array_keys($updated)],
            severity: 'warning'
        );

        // Clear cache
        Cache::forget('admin_settings');

        return $this->success($updated, 'Settings updated successfully');
    }

    /**
     * Get feature flags
     * GET /api/v1/admin/settings/features
     */
    public function getFeatures(Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return $this->error(403, 'Unauthorized');
        }

        $features = AdminSetting::where('group', 'features')->get();
        
        $featureFlags = $features->mapWithKeys(function ($item) {
            return [$item->key => [
                'enabled' => $this->castValue($item->value, $item->type),
                'description' => $item->description,
            ]];
        });

        return $this->success($featureFlags, 'Feature flags retrieved successfully');
    }

    /**
     * Update a single feature flag
     * PUT /api/v1/admin/settings/features/{key}
     */
    public function updateFeature(Request $request, string $key): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return $this->error(403, 'Unauthorized');
        }

        $validator = Validator::make($request->all(), [
            'enabled' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error(422, 'Validation failed', $validator->errors()->toArray());
        }

        $enabled = $request->input('enabled');
        
        $setting = AdminSetting::where('key', $key)->where('group', 'features')->first();
        
        if (!$setting) {
            // Create new feature flag
            $setting = AdminSetting::create([
                'key' => $key,
                'value' => $enabled ? 'true' : 'false',
                'type' => 'boolean',
                'group' => 'features',
                'description' => "Feature: {$key}",
            ]);
        } else {
            $setting->update([
                'value' => $enabled ? 'true' : 'false',
            ]);
        }

        // Audit log
        $this->auditLog(
            actionType: 'feature.toggled',
            resourceType: 'admin_settings',
            resourceId: $setting->id,
            details: [
                'feature' => $key,
                'enabled' => $enabled,
            ],
            severity: 'warning'
        );

        Cache::forget('admin_settings');

        return $this->success([
            'key' => $key,
            'enabled' => $enabled,
        ], 'Feature flag updated successfully');
    }

    /**
     * Clear application cache
     * POST /api/v1/admin/settings/clear-cache
     */
    public function clearCache(Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return $this->error(403, 'Unauthorized');
        }

        $cacheTypes = $request->input('types', ['config', 'route', 'view', 'cache']);
        $cleared = [];

        foreach ($cacheTypes as $type) {
            switch ($type) {
                case 'config':
                    Artisan::call('config:clear');
                    $cleared[] = 'config';
                    break;
                case 'route':
                    Artisan::call('route:clear');
                    $cleared[] = 'route';
                    break;
                case 'view':
                    Artisan::call('view:clear');
                    $cleared[] = 'view';
                    break;
                case 'cache':
                    Artisan::call('cache:clear');
                    $cleared[] = 'cache';
                    break;
            }
        }

        // Audit log
        $this->auditLog(
            actionType: 'cache.cleared',
            resourceType: 'system',
            resourceId: null,
            details: ['cache_types' => $cleared],
            severity: 'warning'
        );

        return $this->success([
            'cleared_at' => now()->toIso8601String(),
            'cache_types' => $cleared,
        ], 'Cache cleared successfully');
    }

    /**
     * Cast value based on type
     */
    private function castValue($value, $type)
    {
        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return (int) $value;
            case 'json':
                return json_decode($value, true);
            default:
                return $value;
        }
    }

    /**
     * Infer type from value
     */
    private function inferType($value): string
    {
        if (is_bool($value)) {
            return 'boolean';
        }
        if (is_int($value)) {
            return 'integer';
        }
        if (is_array($value)) {
            return 'json';
        }
        return 'string';
    }
}
