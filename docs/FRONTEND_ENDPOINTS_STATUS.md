# Frontend Endpoints Status Report

**Date:** February 10, 2026  
**Status:** Complete Analysis  

---

## Executive Summary

**Total Requested:** 13 endpoints  
**✅ Exist:** 3 endpoints  
**❌ Missing:** 10 endpoints  
**Action Required:** Add missing endpoints to routes and create controllers  

---

## 1. Dashboard Service ❌ MISSING

### Requested Endpoints:
```
GET /api/v1/admin/dashboard/stats
GET /api/v1/admin/dashboard/activity
```

### Current Status:
- ❌ `/admin/dashboard/stats` - **MISSING**
- ❌ `/admin/dashboard/activity` - **MISSING**

### What Exists:
✅ **Alternative:** `GET /api/v1/admin/stats/dashboard` exists in routes  
**Controller:** `AdminStatsController@dashboard`  
**Location:** Line 398 in routes/api.php

**Current Response Format:**
```json
{
  "status": "success",
  "message": "Admin dashboard stats retrieved successfully",
  "data": {
    "total_users": 150,
    "total_ads": 450,
    "active_ads": 320,
    "total_views": 15420,
    "total_contacts": 892,
    "ads_by_type": {
      "normal": 200,
      "unique": 100,
      "auction": 50,
      "caishha": 100
    }
  }
}
```

### Frontend Expectation:
Frontend expects: `GET /api/v1/admin/dashboard/stats`  
Backend provides: `GET /api/v1/admin/stats/dashboard`

**Solution Options:**
1. **Update frontend** to use existing endpoint `/admin/stats/dashboard`
2. **Add route alias** for `/admin/dashboard/stats` → `AdminStatsController@dashboard`
3. **Create new endpoint** `/admin/dashboard/activity` for activity feed

---

## 2. Settings Service ❌ MISSING

### Requested Endpoints:
```
GET /api/v1/admin/settings
PUT /api/v1/admin/settings
GET /api/v1/admin/settings/features
PUT /api/v1/admin/settings/features/{key}
POST /api/v1/admin/settings/clear-cache
```

### Current Status:
- ❌ **ALL MISSING** - No admin settings controller exists
- ❌ No routes for general admin settings

### What Exists:
✅ **Company Settings** (different purpose):
- `GET /api/v1/admin/company-settings`
- `PUT /api/v1/admin/company-settings`
- Controller: `CompanySettingController` (for company contact info)

✅ **Caishha Settings** (specific to Caishha ads):
- `GET /api/v1/caishha-settings`
- `PUT /api/v1/caishha-settings`
- Controller: `CaishhaSettingsController`

### Required:
**New Controller:** `AdminSettingsController`  
**Purpose:** General application settings (site title, features, configurations)

---

## 3. Profile Service ❌ MISSING

### Requested Endpoints:
```
GET /api/v1/admin/profile
PUT /api/v1/admin/profile
POST /api/v1/admin/profile/image
PUT /api/v1/admin/profile/change-password
GET /api/v1/admin/profile/activity
```

### Current Status:
- ❌ **ALL MISSING** - No admin profile controller exists
- ❌ No dedicated profile management routes

### What Exists:
✅ **User Management** (can access own data):
- `GET /api/v1/users/{user}` - Can view own profile
- `PUT /api/v1/users/{user}` - Can update own profile
- Controller: `UserController`

### Required:
**New Controller:** `AdminProfileController`  
**Purpose:** Simplified admin-specific profile management with activity tracking

---

## 4. User Status Toggle ❌ MISSING

### Requested Endpoint:
```
PUT /api/v1/admin/users/{id}/toggle-status
```

### Current Status:
- ❌ **MISSING** - No toggle-status endpoint exists
- ❌ No ban/suspend functionality in UserController

### What Exists:
✅ **User Verification:**
- `POST /api/v1/users/{user}/verify` - Admin can verify users
- Controller: `UserController@verify`

**User model has fields for status tracking:**
- `is_verified` (boolean)
- `is_active` (assumed field for ban/suspend)
- `banned_at` (assumed field)

### Required:
**Add Method:** `UserController@toggleStatus`  
**Purpose:** Ban, suspend, or activate user accounts

---

## 5. User Role Assignment ✅ EXISTS (Different Format)

### Requested Endpoint:
```
POST /api/v1/admin/users/{id}/assign-role
```

### Current Status:
✅ **EXISTS** but with different name: `POST /api/v1/users/{user}/roles`

**Controller:** `RoleController@assignRoles`  
**Location:** Line 60 in routes/api.php

**Request Format:**
```json
{
  "roles": ["admin", "moderator"]  // Array of role names
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Roles assigned successfully",
  "data": {
    "user_id": 5,
    "account_type": "admin",
    "roles": [
      {
        "id": 1,
        "name": "admin",
        "display_name": "Administrator"
      }
    ]
  }
}
```

### Solution:
**Option 1:** Update frontend to use existing endpoint  
**Option 2:** Add route alias for compatibility

---

## Missing Endpoints Implementation Required

### Priority 1: Critical (Frontend Breaking)

#### 1. Dashboard Activity Feed
```
GET /api/v1/admin/dashboard/activity
```
**Purpose:** Recent activity feed for admin dashboard  
**Returns:** List of recent actions, user registrations, ad postings

#### 2. User Status Toggle
```
PUT /api/v1/admin/users/{id}/toggle-status
```
**Purpose:** Ban, suspend, or activate user accounts  
**Request:**
```json
{
  "status": "banned",  // enum: active|banned|suspended
  "reason": "Spam activity"  // optional
}
```

### Priority 2: Important (Settings Management)

#### 3. Admin Settings CRUD
```
GET /api/v1/admin/settings
PUT /api/v1/admin/settings
```
**Purpose:** Manage application-wide settings  
**Settings Types:**
- Site configuration (title, description, logo)
- Feature flags (enable/disable features)
- Notification preferences
- Email settings
- Default values

#### 4. Settings Features
```
GET /api/v1/admin/settings/features
PUT /api/v1/admin/settings/features/{key}
```
**Purpose:** Toggle individual feature flags  
**Examples:**
- `enable_auctions: true`
- `enable_caishha_ads: true`
- `require_seller_verification: false`

#### 5. Cache Management
```
POST /api/v1/admin/settings/clear-cache
```
**Purpose:** Clear application cache  
**Response:**
```json
{
  "status": "success",
  "message": "Cache cleared successfully",
  "data": {
    "cleared_at": "2026-02-10T10:00:00Z",
    "cache_types": ["config", "routes", "views"]
  }
}
```

### Priority 3: Nice to Have (Profile Management)

#### 6. Admin Profile
```
GET /api/v1/admin/profile
PUT /api/v1/admin/profile
POST /api/v1/admin/profile/image
PUT /api/v1/admin/profile/change-password
GET /api/v1/admin/profile/activity
```
**Purpose:** Simplified admin profile management  
**Note:** Can use existing `/users/{user}` endpoints with current user ID

---

## Recommendations

### Immediate Actions Required:

1. **Create `AdminSettingsController`**
   - Location: `app/Http/Controllers/Api/V1/AdminSettingsController.php`
   - Methods: `index`, `update`, `getFeatures`, `updateFeature`, `clearCache`

2. **Create `AdminProfileController`**
   - Location: `app/Http/Controllers/Api/V1/AdminProfileController.php`
   - Methods: `show`, `update`, `updateImage`, `changePassword`, `activity`

3. **Add to `UserController`:**
   - Method: `toggleStatus` for ban/suspend functionality

4. **Add to `AdminStatsController`:**
   - Method: `activity` for recent activity feed

5. **Update Routes:**
   - Add all new endpoints to `routes/api.php` under `auth:sanctum` middleware
   - Add admin authorization checks

### Frontend Updates Required:

1. **Update dashboard.ts:**
   - Change from `/admin/dashboard/stats` to `/admin/stats/dashboard` (existing)
   - Keep `/admin/dashboard/activity` (will be added)

2. **Update users.ts:**
   - Change from `/admin/users/{id}/assign-role` to `/users/{user}/roles` (existing)
   - Update request format to `{ "roles": ["admin"] }` array format

3. **settings.ts & profile.ts:**
   - Wait for backend implementation, then integrate

---

## Code to Add

### 1. Add to routes/api.php

```php
// Add after line 28 (with other use statements)
use App\Http\Controllers\Api\V1\AdminSettingsController;
use App\Http\Controllers\Api\V1\AdminProfileController;

// Add in auth:sanctum middleware group (around line 400)

// Admin Dashboard Activity
Route::get('admin/dashboard/activity', [AdminStatsController::class, 'activity']);

// Admin Settings Management
Route::get('admin/settings', [AdminSettingsController::class, 'index']);
Route::put('admin/settings', [AdminSettingsController::class, 'update']);
Route::get('admin/settings/features', [AdminSettingsController::class, 'getFeatures']);
Route::put('admin/settings/features/{key}', [AdminSettingsController::class, 'updateFeature']);
Route::post('admin/settings/clear-cache', [AdminSettingsController::class, 'clearCache']);

// Admin Profile Management
Route::get('admin/profile', [AdminProfileController::class, 'show']);
Route::put('admin/profile', [AdminProfileController::class, 'update']);
Route::post('admin/profile/image', [AdminProfileController::class, 'updateImage']);
Route::put('admin/profile/change-password', [AdminProfileController::class, 'changePassword']);
Route::get('admin/profile/activity', [AdminProfileController::class, 'activity']);

// User Status Toggle (add near other user routes)
Route::put('users/{user}/toggle-status', [UserController::class, 'toggleStatus']);
```

### 2. Migration for Settings Table

```sql
CREATE TABLE admin_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(255) NOT NULL UNIQUE,
    `value` TEXT,
    `type` VARCHAR(50) DEFAULT 'string', -- string, boolean, integer, json
    `group` VARCHAR(100) DEFAULT 'general', -- general, features, notifications, email
    `description` TEXT,
    `is_public` BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

---

## Summary Table

| Endpoint | Status | Action Required |
|----------|--------|-----------------|
| `GET /admin/dashboard/stats` | ⚠️ Different path | Use `/admin/stats/dashboard` |
| `GET /admin/dashboard/activity` | ❌ Missing | Create method in AdminStatsController |
| `GET /admin/settings` | ❌ Missing | Create AdminSettingsController |
| `PUT /admin/settings` | ❌ Missing | Create AdminSettingsController |
| `GET /admin/settings/features` | ❌ Missing | Create AdminSettingsController |
| `PUT /admin/settings/features/{key}` | ❌ Missing | Create AdminSettingsController |
| `POST /admin/settings/clear-cache` | ❌ Missing | Create AdminSettingsController |
| `GET /admin/profile` | ❌ Missing | Create AdminProfileController |
| `PUT /admin/profile` | ❌ Missing | Create AdminProfileController |
| `POST /admin/profile/image` | ❌ Missing | Create AdminProfileController |
| `PUT /admin/profile/change-password` | ❌ Missing | Create AdminProfileController |
| `GET /admin/profile/activity` | ❌ Missing | Create AdminProfileController |
| `PUT /admin/users/{id}/toggle-status` | ❌ Missing | Add to UserController |
| `POST /admin/users/{id}/assign-role` | ✅ Exists | Use `/users/{user}/roles` instead |

**Next Steps:** Would you like me to create the missing controllers and add the routes?
