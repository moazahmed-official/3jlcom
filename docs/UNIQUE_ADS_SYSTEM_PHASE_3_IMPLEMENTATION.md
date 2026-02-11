# Unique Ads System - Phase 3 Implementation Summary

## Overview
Phase 3 introduces two major enhancements to the unique ads system:
1. **Enhanced Ad Type Conversion**: Flexible conversion rules allowing admins unrestricted access and free users to convert if package permits
2. **Package Visibility System**: Three-tier visibility control (public, role-based, user-specific) for targeted package offerings

## 1. Package Visibility System

### Business Requirements
- **Public Packages**: Visible to all users (default behavior)
- **Role-Based Packages**: Visible only to users with specific roles (user, seller, showroom, marketer, admin)
- **User-Specific Packages**: Visible only to explicitly granted users (for VIP/custom packages)

### Database Schema

#### Updated `packages` Table
```sql
ALTER TABLE packages
ADD COLUMN visibility_type ENUM('public', 'role_based', 'user_specific') NOT NULL DEFAULT 'public' AFTER active,
ADD COLUMN allowed_roles JSON NULL AFTER visibility_type;
```

#### New `package_user_access` Pivot Table
```sql
CREATE TABLE package_user_access (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    package_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY unique_package_user (package_id, user_id),
    FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Model Updates (`app/Models/Package.php`)

#### New Properties
```php
// Visibility constants
const VISIBILITY_PUBLIC = 'public';
const VISIBILITY_ROLE_BASED = 'role_based';
const VISIBILITY_USER_SPECIFIC = 'user_specific';

// Added to $fillable
'visibility_type', 'allowed_roles'

// Added to $casts
'visibility_type' => 'string',
'allowed_roles' => 'array'

// Added to $attributes
'visibility_type' => 'public'
```

#### New Relationship
```php
public function userAccess(): BelongsToMany
{
    return $this->belongsToMany(User::class, 'package_user_access')
        ->withTimestamps();
}
```

#### Visibility Scopes
```php
// Filter packages visible to a specific user
public function scopeVisibleTo($query, User $user)
{
    return $query->where(function ($q) use ($user) {
        $q->where('visibility_type', 'public')
          ->orWhere(function ($q) use ($user) {
              $q->where('visibility_type', 'role_based')
                ->whereJsonContains('allowed_roles', $user->role);
          })
          ->orWhere(function ($q) use ($user) {
              $q->where('visibility_type', 'user_specific')
                ->whereHas('userAccess', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
          });
    });
}

// Only public packages
public function scopePublicOnly($query)
{
    return $query->where('visibility_type', 'public');
}

// Only role-based packages
public function scopeRoleBased($query)
{
    return $query->where('visibility_type', 'role_based');
}

// Only user-specific packages
public function scopeUserSpecific($query)
{
    return $query->where('visibility_type', 'user_specific');
}
```

#### Helper Methods
```php
public function isPublic(): bool;
public function isRoleBased(): bool;
public function isUserSpecific(): bool;
public function isVisibleTo(User $user): bool;
public function grantAccessToUsers(array $userIds): void;
public function revokeAccessFromUsers(array $userIds): void;
```

### API Endpoints

#### 1. Get Package Visibility Settings
```
GET /api/v1/admin/packages/{package}/visibility
Authorization: Bearer {admin_token}
```

**Response:**
```json
{
    "success": true,
    "message": "Package visibility settings retrieved",
    "data": {
        "visibility_type": "user_specific",
        "allowed_roles": null,
        "user_access": [
            {
                "id": 123,
                "name": "John Doe",
                "email": "john@example.com",
                "role": "seller"
            }
        ]
    }
}
```

#### 2. Update Package Visibility Settings
```
POST /api/v1/admin/packages/{package}/visibility
Authorization: Bearer {admin_token}
Content-Type: application/json

{
    "visibility_type": "role_based",
    "allowed_roles": ["seller", "showroom"],
    "user_ids": []  // Optional: For user_specific visibility
}
```

**Validation Rules:**
- `visibility_type`: required, string, in: public, role_based, user_specific
- `allowed_roles`: nullable, array (required if visibility_type = role_based)
- `allowed_roles.*`: string, in: user, seller, showroom, marketer, admin
- `user_ids`: nullable, array (used for user_specific visibility)
- `user_ids.*`: integer, exists in users table

**Response:**
```json
{
    "success": true,
    "message": "Package visibility updated successfully",
    "data": {
        "package": {
            "id": 5,
            "name": "Premium Seller Package",
            "visibility_type": "role_based",
            "allowed_roles": ["seller", "showroom"],
            "user_access_count": 0
        }
    }
}
```

#### 3. Grant User-Specific Access
```
POST /api/v1/admin/packages/{package}/grant-access
Authorization: Bearer {admin_token}
Content-Type: application/json

{
    "user_ids": [123, 456, 789]
}
```

**Validation:**
- Package must have `visibility_type = 'user_specific'`
- All user IDs must exist in users table

**Response:**
```json
{
    "success": true,
    "message": "Access granted successfully",
    "data": {
        "granted_users_count": 3,
        "total_users_with_access": 8
    }
}
```

#### 4. Revoke User-Specific Access
```
POST /api/v1/admin/packages/{package}/revoke-access
Authorization: Bearer {admin_token}
Content-Type: application/json

{
    "user_ids": [123, 456]
}
```

**Response:**
```json
{
    "success": true,
    "message": "Access revoked successfully",
    "data": {
        "revoked_users_count": 2,
        "total_users_with_access": 6
    }
}
```

#### 5. List Users With Access
```
GET /api/v1/admin/packages/{package}/users-with-access
Authorization: Bearer {admin_token}
```

**Response:**
```json
{
    "success": true,
    "message": "Users with access retrieved",
    "data": {
        "users": [
            {
                "id": 123,
                "name": "John Doe",
                "email": "john@example.com",
                "role": "seller"
            }
        ],
        "total_count": 1
    }
}
```

### Updated Endpoints

#### Get All Packages (with Visibility Filtering)
```
GET /api/v1/packages
```

**Behavior Changes:**
- **Guest Users**: Only see public packages
- **Authenticated Users**: See public + role-based (if role matches) + user-specific (if granted)
- **Admins**: See all packages (can filter by `visibility_type` query parameter)

**Query Parameters (Admin Only):**
- `visibility_type`: Filter by visibility type (public, role_based, user_specific)

### Updated PackageResource

New fields added to package response:
```json
{
    "id": 5,
    "name": "Premium Package",
    // ... other fields
    "visibility_type": "role_based",
    "is_visible_to_user": true,
    
    // Admin-only fields
    "allowed_roles": ["seller", "showroom"],
    "user_access_count": 0  // Only for user_specific packages
}
```

---

## 2. Enhanced Ad Type Conversion

### Business Logic Changes

#### Previous Rules (Phase 2):
- Only **PAID** users could convert ad types
- Free users had to request admin approval for upgrade

#### New Rules (Phase 3):
1. **Admins**: Unrestricted conversion to ANY ad type (bypass all checks)
2. **Paid Users**: Can convert between types allowed by their active package
3. **Free Users**: Can now convert IF their active package allows the destination type
   - No longer restricted to upgrade requests only
   - Must have package permission for destination ad type

### Updated Controller (`app/Http/Controllers/Api/V1/AdTypeConversionController.php`)

#### Convert Method Logic
```php
// 1. Admin bypass - can convert to anything
if (auth()->user()->hasRole('admin')) {
    // Convert without restrictions
}

// 2. Check user's active package
$activePackage = auth()->user()->activePackage();
if (!$activePackage) {
    return error("No active package");
}

// 3. For ALL users (free and paid), check if package allows destination type
$packageAllowsType = $this->packageAllowsAdType($activePackage, $to_type);
if (!$packageAllowsType) {
    return error("Your package does not allow {$to_type} ads");
}

// 4. Proceed with conversion
```

#### Supported Ad Types
Extended to support all 5 ad types:
- `normal` → NormalAd model
- `unique` → UniqueAd model
- `caishha` → CaishhaAd model
- `findit` → FindItAd model
- `auction` → Auction model

#### Sub-Table Management
```php
protected function createSubTableRecord(Ad $ad, string $type): void
{
    switch ($type) {
        case 'normal':
            NormalAd::create(['ad_id' => $ad->id]);
            break;
        case 'unique':
            UniqueAd::create(['ad_id' => $ad->id]);
            break;
        case 'caishha':
            CaishhaAd::create(['ad_id' => $ad->id]);
            break;
        case 'findit':
            FindItAd::create(['ad_id' => $ad->id]);
            break;
        case 'auction':
            Auction::create(['ad_id' => $ad->id]);
            break;
    }
}
```

### Updated UniqueAdsController

Error message for free users now mentions BOTH options:
```php
return $this->error(403, 'Upgrade to unique ad not allowed', [
    'message' => 'Your free package does not allow creating unique ads directly. 
                  You can: 1) Submit an upgrade request for admin approval, or 
                  2) Convert an existing ad if your package permits.'
]);
```

---

## Testing Guide

### 1. Package Visibility Tests

#### Test Public Package
```bash
# Create public package
POST /api/v1/packages
{
    "name": "Basic Package",
    "visibility_type": "public"
}

# Verify all users can see it
GET /api/v1/packages  # As guest
GET /api/v1/packages  # As authenticated user
```

#### Test Role-Based Package
```bash
# Create role-based package for sellers only
POST /api/v1/admin/packages/5/visibility
{
    "visibility_type": "role_based",
    "allowed_roles": ["seller", "showroom"]
}

# Verify visibility
GET /api/v1/packages  # As seller (should see it)
GET /api/v1/packages  # As regular user (should NOT see it)
```

#### Test User-Specific Package
```bash
# Create user-specific package
POST /api/v1/admin/packages/7/visibility
{
    "visibility_type": "user_specific",
    "user_ids": [123, 456]
}

# Grant additional access
POST /api/v1/admin/packages/7/grant-access
{
    "user_ids": [789]
}

# Verify only granted users can see it
GET /api/v1/packages  # As user 123 (should see it)
GET /api/v1/packages  # As user 999 (should NOT see it)

# Revoke access
POST /api/v1/admin/packages/7/revoke-access
{
    "user_ids": [456]
}
```

### 2. Enhanced Ad Type Conversion Tests

#### Test Admin Unrestricted Conversion
```bash
# Admin can convert to any type without restrictions
POST /api/v1/ads/123/convert
Authorization: Bearer {admin_token}
{
    "to_type": "unique"
}
# Should succeed regardless of package
```

#### Test Free User Conversion (Package Allowed)
```bash
# Free user with package that allows unique ads
POST /api/v1/ads/456/convert
Authorization: Bearer {free_user_token}
{
    "to_type": "unique"
}
# Should succeed if package allows unique type
```

#### Test Free User Conversion (Package Denied)
```bash
# Free user with package that does NOT allow unique ads
POST /api/v1/ads/789/convert
Authorization: Bearer {free_user_token}
{
    "to_type": "unique"
}
# Should fail with: "Your package does not allow unique ads"
```

#### Test Conversion to New Types (FindIt, Auction)
```bash
# Convert to FindIt ad
POST /api/v1/ads/100/convert
{
    "to_type": "findit"
}

# Convert to Auction ad
POST /api/v1/ads/101/convert
{
    "to_type": "auction"
}
```

---

## Database Verification

### Check Package Visibility
```sql
-- List all packages with visibility settings
SELECT id, name, visibility_type, allowed_roles
FROM packages;

-- List user-specific package access
SELECT p.name, u.name AS user_name, u.email, pua.created_at
FROM package_user_access pua
JOIN packages p ON p.id = pua.package_id
JOIN users u ON u.id = pua.user_id
ORDER BY p.id, u.name;

-- Count users with access to each user-specific package
SELECT p.id, p.name, COUNT(pua.user_id) AS users_with_access
FROM packages p
LEFT JOIN package_user_access pua ON p.id = pua.package_id
WHERE p.visibility_type = 'user_specific'
GROUP BY p.id, p.name;
```

### Check Ad Type Conversions
```sql
-- List recent conversions by free users
SELECT atc.*, u.name AS user_name, p.name AS package_name, p.price
FROM ad_type_conversions atc
JOIN users u ON u.id = atc.user_id
JOIN user_packages up ON up.user_id = u.id AND up.active = 1
JOIN packages p ON p.id = up.package_id
WHERE p.price = 0
ORDER BY atc.created_at DESC
LIMIT 20;
```

---

## Audit Logging

### Visibility Management Events
```php
// Package visibility updated
'action' => 'updated_visibility',
'model_type' => 'Package',
'old_data' => ['visibility_type' => 'public', ...],
'new_data' => ['visibility_type' => 'role_based', 'allowed_roles' => ['seller']]

// User access granted
'action' => 'granted_access',
'model_type' => 'Package',
'new_data' => ['user_ids' => [123, 456]]

// User access revoked
'action' => 'revoked_access',
'model_type' => 'Package',
'new_data' => ['user_ids' => [789]]
```

---

## Migration Files

### Phase 3 Migration
**File**: `database/migrations/2026_02_11_000009_add_package_visibility_system.php`

**Contents:**
- Add `visibility_type` enum column to packages table (default: 'public')
- Add `allowed_roles` JSON column to packages table
- Create `package_user_access` pivot table with unique constraint

**Run Migration:**
```bash
php artisan migrate
```

**Rollback:**
```bash
php artisan migrate:rollback --step=1
```

---

## API Route Summary

### New Routes (Admin Only)
```php
GET    /api/v1/admin/packages/{package}/visibility          // Get visibility settings
POST   /api/v1/admin/packages/{package}/visibility          // Update visibility
POST   /api/v1/admin/packages/{package}/grant-access        // Grant user access
POST   /api/v1/admin/packages/{package}/revoke-access       // Revoke user access
GET    /api/v1/admin/packages/{package}/users-with-access   // List users with access
```

### Updated Routes
```php
GET    /api/v1/packages                                     // Now filters by visibility
POST   /api/v1/ads/{ad}/convert                             // Enhanced conversion rules
```

---

## Error Responses

### Package Visibility Errors
```json
{
    "success": false,
    "message": "Package must have user_specific visibility type",
    "errors": {
        "visibility_type": ["Current visibility type is: public"]
    },
    "error_code": 422
}
```

```json
{
    "success": false,
    "message": "allowed_roles is required for role_based visibility",
    "errors": {
        "allowed_roles": ["Specify at least one role for role-based visibility"]
    },
    "error_code": 422
}
```

### Ad Conversion Errors
```json
{
    "success": false,
    "message": "Ad type conversion not allowed",
    "errors": {
        "to_type": ["Your package does not allow unique ads"]
    },
    "error_code": 403
}
```

---

## Implementation Checklist

- [x] Migration created (000009_add_package_visibility_system)
- [x] Migration executed successfully
- [x] Package model updated with visibility system
- [x] PackageVisibilityController created
- [x] PackageResource updated with visibility fields
- [x] PackageController updated with visibility filtering
- [x] AdTypeConversionController updated for enhanced rules
- [x] UniqueAdsController error message updated
- [x] Routes added for visibility management
- [x] All 5 ad types supported in conversion
- [x] Admin bypass implemented for unrestricted conversion
- [x] Free user conversion enabled (with package permission)

---

## Next Steps

1. **Test all visibility scenarios** (public, role-based, user-specific)
2. **Test ad type conversion** for admins, free users, and paid users
3. **Verify audit logs** are being created correctly
4. **Update frontend** to show visibility-filtered packages
5. **Add admin UI** for managing package visibility
6. **Monitor database** for performance with visibility queries

---

## Related Documentation

- [Phase 1 Implementation](UNIQUE_ADS_SYSTEM_IMPLEMENTATION.md)
- [Phase 2 Implementation](UNIQUE_ADS_SYSTEM_PHASE_2_IMPLEMENTATION.md)
- [API Documentation](docs/API_COMPLETE_DOCUMENTATION.md)
- [Database Schema](db/schema.sql)
