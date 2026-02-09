<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\LogsAudit;
use App\Models\CaishhaSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CaishhaSettingsController extends Controller
{
    use LogsAudit;
    /**
     * Display all Caishha settings (Admin only)
     */
    public function index(): JsonResponse
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['Only admins can access Caishha settings']]
            ], 403);
        }

        $settings = CaishhaSetting::getAllSettings();

        return response()->json([
            'status' => 'success',
            'message' => 'Caishha settings retrieved successfully',
            'data' => $settings
        ]);
    }

    /**
     * Update multiple Caishha settings (Admin only)
     */
    public function update(Request $request): JsonResponse
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['Only admins can modify Caishha settings']]
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'code' => 422,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $updatedSettings = [];
            $errors = [];

            foreach ($request->settings as $settingData) {
                $key = $settingData['key'];
                $value = $settingData['value'];

                // Validate specific settings
                if (!$this->validateSetting($key, $value)) {
                    $errors[$key] = $this->getValidationErrorForSetting($key, $value);
                    continue;
                }

                // Determine type based on key
                $type = $this->getTypeForSetting($key);
                
                // Update setting
                CaishhaSetting::set($key, $value, $type);
                $updatedSettings[$key] = $value;
            }

            if (!empty($errors)) {
                return response()->json([
                    'status' => 'error',
                    'code' => 422,
                    'message' => 'Some settings validation failed',
                    'errors' => $errors
                ], 422);
            }

            $this->auditLog(
                actionType: 'caishha_setting.bulk_updated',
                resourceType: 'caishha_setting',
                resourceId: null,
                details: ['updated_settings' => $updatedSettings],
                severity: 'warning'
            );

            Log::info('Caishha settings updated', [
                'user_id' => auth()->id(),
                'updated_settings' => array_keys($updatedSettings)
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Settings updated successfully',
                'data' => [
                    'updated_count' => count($updatedSettings),
                    'updated_settings' => $updatedSettings
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update Caishha settings', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to update settings',
                'errors' => ['general' => ['An unexpected error occurred']]
            ], 500);
        }
    }

    /**
     * Update a single Caishha setting (Admin only)
     */
    public function updateSingle(Request $request, string $key): JsonResponse
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['Only admins can modify Caishha settings']]
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'value' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'code' => 422,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $value = $request->value;

        // Validate setting
        if (!$this->validateSetting($key, $value)) {
            return response()->json([
                'status' => 'error',
                'code' => 422,
                'message' => 'Invalid setting value',
                'errors' => ['value' => [$this->getValidationErrorForSetting($key, $value)]]
            ], 422);
        }

        try {
            $type = $this->getTypeForSetting($key);
            $oldValue = CaishhaSetting::get($key);
            CaishhaSetting::set($key, $value, $type);

            $this->auditLog(
                actionType: 'caishha_setting.updated',
                resourceType: 'caishha_setting',
                resourceId: null,
                details: [
                    'key' => $key,
                    'old_value' => $oldValue,
                    'new_value' => $value
                ],
                severity: 'warning'
            );

            Log::info('Caishha setting updated', [
                'user_id' => auth()->id(),
                'setting_key' => $key,
                'setting_value' => $value
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Setting updated successfully',
                'data' => [
                    'key' => $key,
                    'value' => $value,
                    'type' => $type
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update Caishha setting', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'setting_key' => $key
            ]);

            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to update setting',
                'errors' => ['general' => ['An unexpected error occurred']]
            ], 500);
        }
    }

    /**
     * Get time period presets for the frontend
     */
    public function presets(): JsonResponse
    {
        $presets = [
            'dealer_window' => [
                ['label' => '1 Hour', 'value' => 3600],
                ['label' => '6 Hours', 'value' => 21600],
                ['label' => '12 Hours', 'value' => 43200],
                ['label' => '24 Hours (1 Day)', 'value' => 86400],
                ['label' => '36 Hours (Default)', 'value' => 129600],
                ['label' => '48 Hours (2 Days)', 'value' => 172800],
                ['label' => '72 Hours (3 Days)', 'value' => 259200],
                ['label' => '7 Days', 'value' => 604800],
            ],
            'visibility_period' => [
                ['label' => 'Immediate (0 Hours)', 'value' => 0],
                ['label' => '1 Hour', 'value' => 3600],
                ['label' => '6 Hours', 'value' => 21600],
                ['label' => '12 Hours', 'value' => 43200],
                ['label' => '24 Hours (1 Day)', 'value' => 86400],
                ['label' => '36 Hours (Default)', 'value' => 129600],
                ['label' => '48 Hours (2 Days)', 'value' => 172800],
                ['label' => '72 Hours (3 Days)', 'value' => 259200],
                ['label' => '7 Days', 'value' => 604800],
            ]
        ];

        return response()->json([
            'status' => 'success',
            'data' => $presets
        ]);
    }

    /**
     * Validate a setting value based on its key
     */
    private function validateSetting(string $key, mixed $value): bool
    {
        return match ($key) {
            'default_dealer_window_seconds' => is_numeric($value) && CaishhaSetting::isValidDealerWindowPeriod((int) $value),
            'default_visibility_period_seconds' => is_numeric($value) && CaishhaSetting::isValidVisibilityPeriod((int) $value),
            'min_dealer_window_seconds' => is_numeric($value) && $value >= 3600, // At least 1 hour
            'max_dealer_window_seconds' => is_numeric($value) && $value <= 2592000, // Max 30 days
            'min_visibility_period_seconds' => is_numeric($value) && $value >= 0,
            'max_visibility_period_seconds' => is_numeric($value) && $value <= 2592000, // Max 30 days
            default => true, // Allow other settings for future expansion
        };
    }

    /**
     * Get validation error message for a setting
     */
    private function getValidationErrorForSetting(string $key, mixed $value): string
    {
        return match ($key) {
            'default_dealer_window_seconds' => 'Default dealer window must be between ' . 
                CaishhaSetting::getMinDealerWindowSeconds() . ' and ' . 
                CaishhaSetting::getMaxDealerWindowSeconds() . ' seconds',
            'default_visibility_period_seconds' => 'Default visibility period must be between ' . 
                CaishhaSetting::getMinVisibilityPeriodSeconds() . ' and ' . 
                CaishhaSetting::getMaxVisibilityPeriodSeconds() . ' seconds',
            'min_dealer_window_seconds' => 'Minimum dealer window must be at least 3600 seconds (1 hour)',
            'max_dealer_window_seconds' => 'Maximum dealer window must not exceed 2592000 seconds (30 days)',
            'min_visibility_period_seconds' => 'Minimum visibility period must be at least 0 seconds',
            'max_visibility_period_seconds' => 'Maximum visibility period must not exceed 2592000 seconds (30 days)',
            default => 'Invalid setting value',
        };
    }

    /**
     * Get the data type for a setting key
     */
    private function getTypeForSetting(string $key): string
    {
        return match ($key) {
            'default_dealer_window_seconds',
            'default_visibility_period_seconds',
            'min_dealer_window_seconds',
            'max_dealer_window_seconds',
            'min_visibility_period_seconds',
            'max_visibility_period_seconds' => 'integer',
            default => 'string',
        };
    }
}