<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CompanySetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CompanySettingController extends Controller
{
    /**
     * Display all company settings grouped by type (Admin only).
     */
    public function index(): JsonResponse
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['Only admins can access company settings']]
            ], 403);
        }

        $settings = CompanySetting::getAllSettings();

        return response()->json([
            'status' => 'success',
            'message' => 'Company settings retrieved successfully',
            'data' => $settings
        ]);
    }

    /**
     * Display settings by type (Admin only).
     */
    public function showByType(string $type): JsonResponse
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['Only admins can access company settings']]
            ], 403);
        }

        if (!in_array($type, CompanySetting::TYPES)) {
            return response()->json([
                'status' => 'error',
                'code' => 422,
                'message' => 'Invalid type',
                'errors' => ['type' => ['Valid types are: ' . implode(', ', CompanySetting::TYPES)]]
            ], 422);
        }

        $settings = CompanySetting::getByType($type);

        return response()->json([
            'status' => 'success',
            'message' => "Company {$type} settings retrieved successfully",
            'data' => $settings
        ]);
    }

    /**
     * Update a single company setting (Admin only).
     */
    public function updateSingle(Request $request, string $key): JsonResponse
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['Only admins can modify company settings']]
            ], 403);
        }

        if (!CompanySetting::isValidKey($key)) {
            return response()->json([
                'status' => 'error',
                'code' => 422,
                'message' => 'Invalid setting key',
                'errors' => ['key' => ['Valid keys are: ' . implode(', ', CompanySetting::VALID_KEYS)]]
            ], 422);
        }

        $rules = [
            'value' => 'nullable|string|max:2048',
            'is_active' => 'sometimes|boolean',
        ];

        // Add URL validation for link-type settings
        if (str_contains($key, '_link')) {
            $rules['value'] = 'nullable|url|max:2048';
        }

        // Add email validation
        if ($key === 'email') {
            $rules['value'] = 'nullable|email|max:255';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'code' => 422,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $setting = CompanySetting::where('key', $key)->first();

            if (!$setting) {
                return response()->json([
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'Setting not found',
                    'errors' => ['key' => ["No setting found for key: {$key}"]]
                ], 404);
            }

            $updateData = [];
            if ($request->has('value')) {
                $updateData['value'] = $request->input('value');
            }
            if ($request->has('is_active')) {
                $updateData['is_active'] = $request->boolean('is_active');
            }

            $setting->update($updateData);

            // Clear cache
            CompanySetting::clearCache($key);

            Log::info('Company setting updated', [
                'user_id' => auth()->id(),
                'key' => $key,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Setting updated successfully',
                'data' => $setting->fresh()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update company setting', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'key' => $key,
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
     * Bulk update multiple company settings (Admin only).
     */
    public function updateBulk(Request $request): JsonResponse
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['Only admins can modify company settings']]
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'settings' => 'required|array|min:1',
            'settings.*.key' => 'required|string|in:' . implode(',', CompanySetting::VALID_KEYS),
            'settings.*.value' => 'nullable|string|max:2048',
            'settings.*.is_active' => 'sometimes|boolean',
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

                // Validate URLs for link fields
                if (str_contains($key, '_link') && !empty($settingData['value'])) {
                    if (!filter_var($settingData['value'], FILTER_VALIDATE_URL)) {
                        $errors[$key] = 'Invalid URL format';
                        continue;
                    }
                }

                // Validate email
                if ($key === 'email' && !empty($settingData['value'])) {
                    if (!filter_var($settingData['value'], FILTER_VALIDATE_EMAIL)) {
                        $errors[$key] = 'Invalid email format';
                        continue;
                    }
                }

                $setting = CompanySetting::where('key', $key)->first();
                if (!$setting) {
                    $errors[$key] = 'Setting not found';
                    continue;
                }

                $updateData = [];
                if (array_key_exists('value', $settingData)) {
                    $updateData['value'] = $settingData['value'];
                }
                if (array_key_exists('is_active', $settingData)) {
                    $updateData['is_active'] = (bool) $settingData['is_active'];
                }

                $setting->update($updateData);
                $updatedSettings[] = $key;
            }

            // Clear all cache
            CompanySetting::clearCache();

            if (!empty($errors)) {
                return response()->json([
                    'status' => 'partial',
                    'message' => 'Some settings could not be updated',
                    'data' => [
                        'updated' => $updatedSettings,
                        'errors' => $errors,
                    ]
                ], 422);
            }

            Log::info('Company settings bulk updated', [
                'user_id' => auth()->id(),
                'updated_keys' => $updatedSettings,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'All settings updated successfully',
                'data' => [
                    'updated_count' => count($updatedSettings),
                    'updated_keys' => $updatedSettings,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to bulk update company settings', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
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
     * Toggle active status for a setting (Admin only).
     */
    public function toggleActive(string $key): JsonResponse
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['Only admins can modify company settings']]
            ], 403);
        }

        if (!CompanySetting::isValidKey($key)) {
            return response()->json([
                'status' => 'error',
                'code' => 422,
                'message' => 'Invalid setting key',
                'errors' => ['key' => ['Valid keys are: ' . implode(', ', CompanySetting::VALID_KEYS)]]
            ], 422);
        }

        try {
            $setting = CompanySetting::where('key', $key)->first();

            if (!$setting) {
                return response()->json([
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'Setting not found',
                    'errors' => ['key' => ["No setting found for key: {$key}"]]
                ], 404);
            }

            $setting->update(['is_active' => !$setting->is_active]);

            // Clear cache
            CompanySetting::clearCache($key);

            Log::info('Company setting active status toggled', [
                'user_id' => auth()->id(),
                'key' => $key,
                'new_status' => $setting->fresh()->is_active,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Setting active status toggled successfully',
                'data' => $setting->fresh()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to toggle company setting active status', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'key' => $key,
            ]);

            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to toggle setting status',
                'errors' => ['general' => ['An unexpected error occurred']]
            ], 500);
        }
    }

    // =====================
    // PUBLIC ENDPOINTS
    // =====================

    /**
     * Get all active company settings (public, no auth required).
     * Only returns settings where is_active = true.
     */
    public function publicIndex(): JsonResponse
    {
        $settings = CompanySetting::getActiveSettings();

        return response()->json([
            'status' => 'success',
            'message' => 'Company information retrieved successfully',
            'data' => $settings
        ]);
    }
}
