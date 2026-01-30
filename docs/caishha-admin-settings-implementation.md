# Caishha Admin Settings Implementation Summary

## Problem Solved

The original Caishha Ads system had **hard-coded 36-hour periods** for both dealer window and visibility periods. The admin requested the ability to **change these time periods** to be configurable rather than fixed values.

## Solution Implemented

### 1. Database Settings Infrastructure

**Created:** `database/migrations/2026_01_29_400000_create_caishha_settings_table.php`
- Settings table with key/value storage
- Default values populated (36 hours = 129600 seconds)
- Min/max validation limits configured

**Migration Output:**
```sql
CREATE TABLE `caishha_settings` (
  `id` bigint unsigned PRIMARY KEY AUTO_INCREMENT,
  `key` varchar(255) NOT NULL UNIQUE,
  `value` text NOT NULL,
  `type` varchar(255) DEFAULT 'string',
  `description` text,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL
);
```

**Default Settings Installed:**
- `default_dealer_window_seconds`: 129600 (36 hours)
- `default_visibility_period_seconds`: 129600 (36 hours)  
- `min_dealer_window_seconds`: 3600 (1 hour)
- `max_dealer_window_seconds`: 604800 (7 days)
- `min_visibility_period_seconds`: 0 (immediate)
- `max_visibility_period_seconds`: 604800 (7 days)

### 2. Settings Model with Caching

**Created:** `app/Models/CaishhaSetting.php`

**Key Features:**
- ✅ Cached settings (60-minute duration)
- ✅ Type casting (integer, string, boolean)
- ✅ Helper methods for common settings
- ✅ Validation against min/max ranges
- ✅ Automatic cache clearing on updates

**Helper Methods:**
```php
CaishhaSetting::getDefaultDealerWindowSeconds()     // Returns: 129600
CaishhaSetting::getDefaultVisibilityPeriodSeconds() // Returns: 129600  
CaishhaSetting::getMinDealerWindowSeconds()         // Returns: 3600
CaishhaSetting::getMaxDealerWindowSeconds()         // Returns: 604800
CaishhaSetting::getMinVisibilityPeriodSeconds()     // Returns: 0
CaishhaSetting::getMaxVisibilityPeriodSeconds()     // Returns: 604800
```

### 3. Updated Core Models

**Modified:** `app/Models/CaishhaAd.php`

**Before (Hard-coded):**
```php
public const DEFAULT_DEALER_WINDOW_SECONDS = 129600;
public const DEFAULT_VISIBILITY_PERIOD_SECONDS = 129600;
```

**After (Dynamic):**
```php
public function getDealerWindowPeriod(): int
{
    return $this->offers_window_period ?? CaishhaSetting::getDefaultDealerWindowSeconds();
}

public function getVisibilityPeriod(): int  
{
    return $this->sellers_visibility_period ?? CaishhaSetting::getDefaultVisibilityPeriodSeconds();
}
```

### 4. Dynamic Validation Rules

**Updated:** 
- `app/Http/Requests/StoreCaishhaAdRequest.php`
- `app/Http/Requests/UpdateCaishhaAdRequest.php`

**Before (Fixed limits):**
```php
'offers_window_period' => 'sometimes|integer|min:3600|max:604800',
'sellers_visibility_period' => 'sometimes|integer|min:0|max:604800',
```

**After (Dynamic limits):**
```php
$minDealerWindow = CaishhaSetting::getMinDealerWindowSeconds();
$maxDealerWindow = CaishhaSetting::getMaxDealerWindowSeconds();
// ... validation uses dynamic values
```

### 5. Admin Management API

**Created:** `app/Http/Controllers/Api/V1/CaishhaSettingsController.php`

**API Endpoints:**
- `GET /api/v1/caishha/admin/settings` - List all settings
- `PUT /api/v1/caishha/admin/settings` - Bulk update settings
- `PUT /api/v1/caishha/admin/settings/{key}` - Update single setting
- `GET /api/v1/caishha/admin/settings/presets` - Get preset configurations

**Added Routes:** `routes/api.php`
```php
Route::middleware(['auth:sanctum', 'admin'])->prefix('caishha/admin')->group(function () {
    Route::get('settings', [CaishhaSettingsController::class, 'index']);
    Route::put('settings', [CaishhaSettingsController::class, 'update']);
    Route::put('settings/{key}', [CaishhaSettingsController::class, 'updateSingle']);
    Route::get('settings/presets', [CaishhaSettingsController::class, 'presets']);
});
```

## Admin Usage Examples

### Change Default Period to 24 Hours
```bash
curl -X PUT \
  http://your-domain/api/v1/caishha/admin/settings/default_dealer_window_seconds \
  -H 'Authorization: Bearer {admin_token}' \
  -H 'Content-Type: application/json' \
  -d '{"value": "86400"}'  # 24 hours in seconds
```

### Apply Quick Turnaround Preset (1-hour windows)
```bash
curl -X PUT \
  http://your-domain/api/v1/caishha/admin/settings \
  -H 'Authorization: Bearer {admin_token}' \
  -H 'Content-Type: application/json' \
  -d '{
    "settings": [
      {"key": "default_dealer_window_seconds", "value": "3600"},
      {"key": "default_visibility_period_seconds", "value": "7200"}
    ]
  }'
```

### View Current Settings
```bash
curl -X GET \
  http://your-domain/api/v1/caishha/admin/settings \
  -H 'Authorization: Bearer {admin_token}'
```

## Time Conversion Quick Reference

| Period | Seconds | Human-Readable |
|--------|---------|----------------|
| 1 hour | 3,600 | Minimum dealer window |
| 6 hours | 21,600 | Quarter day |
| 12 hours | 43,200 | Half day |
| 24 hours | 86,400 | Full day |
| **36 hours** | **129,600** | **Original default** |
| 48 hours | 172,800 | 2 days |
| 72 hours | 259,200 | 3 days |
| 7 days | 604,800 | Maximum allowed |

## Testing Results

**✅ Migration Success:** Settings table created with default values
**✅ Model Methods:** All helper methods working correctly
```
Default dealer window: 129600 seconds
Min: 3600s, Max: 604800s  
Visibility min: 0s, max: 604800s
```

## System Behavior

### New Ads
- Use admin-configured default periods if no custom values provided
- Validation enforces admin-configured min/max limits

### Existing Ads  
- Retain their original settings (not affected by changes)
- Updates must comply with current validation limits

### Cache Management
- Settings cached for 60 minutes for performance
- Cache automatically cleared when admin updates settings
- Changes take effect immediately for new requests

## Files Modified/Created

### Created Files:
1. `database/migrations/2026_01_29_400000_create_caishha_settings_table.php` - Database schema
2. `app/Models/CaishhaSetting.php` - Settings model with caching
3. `app/Http/Controllers/Api/V1/CaishhaSettingsController.php` - Admin API
4. `docs/api/caishha-settings-api.md` - API documentation

### Modified Files:
1. `app/Models/CaishhaAd.php` - Use dynamic settings instead of constants
2. `app/Http/Requests/StoreCaishhaAdRequest.php` - Dynamic validation
3. `app/Http/Requests/UpdateCaishhaAdRequest.php` - Dynamic validation  
4. `app/Http/Controllers/Api/V1/CaishhaAdsController.php` - Use dynamic defaults
5. `database/factories/CaishhaAdFactory.php` - Use dynamic values
6. `routes/api.php` - Added admin settings routes

## Summary

The hard-coded 36-hour limitation has been **completely eliminated**. The admin now has **full control** over:

✅ **Default dealer window period** (how long dealers have to submit offers)
✅ **Default visibility period** (how long ads remain visible to sellers)  
✅ **Min/max validation limits** (prevents invalid configurations)
✅ **Quick preset configurations** (for common scenarios)
✅ **Real-time changes** (via cached API with immediate effect)

**Result:** The system is now **flexible and admin-configurable** instead of being locked to fixed 36-hour periods.