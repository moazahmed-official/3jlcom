# Registration with Standard Package - Test Scenarios

## Overview
This document provides curl test scenarios for user registration with automatic Standard Package assignment and package feature application.

## Changes Made
1. **Fixed account_type null bug**: Registration now defaults to `"individual"` if not provided
2. **Created Standard Package**: A free package (365 days) that persists across database resets via seeder
3. **Auto-assignment**: New users automatically receive the Standard Package upon registration
4. **Feature application**: Package features are applied immediately via `PackageFeatureService::applyPackageFeatures()`

---

## Test Scenario 1: Public User Registration (Account Type = Individual)

### Step 1: Register a new user
```bash
curl -X POST "http://localhost:8000/api/v1/auth/register" \
 -H "Content-Type: application/json" \
 -d '{
   "name": "John Doe",
   "email": "john.doe@example.com",
   "phone": "+201234567890",
   "country_id": 1,
   "password": "Secret123!",
   "password_confirmation": "Secret123!"
 }'
```

**Expected Response (200)**:
```json
{
  "success": true,
  "data": {
    "user_id": 45,
    "phone": "+201234567890",
    "expires_in_minutes": 10
  },
  "message": "Registration successful. Please verify your account with the OTP sent to your phone."
}
```

**What happened behind the scenes**:
- User created with `account_type: "individual"` (default)
- Standard Package (ID 3, free, 365 days) automatically assigned to user
- Package features applied (roles, verification flags based on package settings)

---

### Step 2: Get OTP from logs or phone
Check your application logs or SMS/email for the 6-digit OTP code.

---

### Step 3: Verify OTP
```bash
curl -X POST "http://localhost:8000/api/v1/auth/verify" \
 -H "Content-Type: application/json" \
 -d '{
   "phone": "+201234567890",
   "code": "123456"
 }'
```

**Expected Response (200)**:
```json
{
  "success": true,
  "data": {
    "token": "3|AbCdEfGhIjKlMnOpQrStUvWxYz...",
    "token_type": "Bearer",
    "expires_in": 3600,
    "user": {
      "id": 45,
      "name": "John Doe",
      "email": "john.doe@example.com",
      "phone": "+201234567890",
      "account_type": "individual",
      "is_verified": true,
      "seller_verified": false,
      "seller_verified_at": null,
      "created_at": "2026-02-13T11:00:00.000000Z",
      "updated_at": "2026-02-13T11:00:30.000000Z"
    }
  },
  "message": "Account verified successfully."
}
```

**Key fields to verify**:
- `account_type`: Should be `"individual"` (not null)
- `is_verified`: `true` after OTP verification
- Token is provided for authenticated API requests

---

### Step 4: Check user's assigned packages
```bash
curl -X GET "http://localhost:8000/api/v1/users/45/packages" \
 -H "Authorization: Bearer 3|AbCdEfGhIjKlMnOpQrStUvWxYz..." \
 -H "Accept: application/json"
```

**Expected Response (200)**:
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 78,
        "user_id": 45,
        "package_id": 3,
        "start_date": "2026-02-13",
        "end_date": "2027-02-13",
        "active": true,
        "is_valid": true,
        "remaining_days": 365,
        "package": {
          "id": 3,
          "name": "Standard Package",
          "description": "Free standard package for all users with basic features",
          "price": 0,
          "duration_days": 365,
          "active": true
        }
      }
    ],
    "links": { "...": "..." },
    "meta": { "...": "..." }
  },
  "message": "User packages retrieved successfully"
}
```

**Key fields to verify**:
- `package.name`: "Standard Package"
- `package.price`: 0 (free)
- `active`: true
- `end_date`: One year from registration

---

### Step 5: Check package features
```bash
curl -X GET "http://localhost:8000/api/v1/packages/my-features" \
 -H "Authorization: Bearer 3|AbCdEfGhIjKlMnOpQrStUvWxYz..." \
 -H "Accept: application/json"
```

**Expected Response (200)**:
```json
{
  "success": true,
  "data": {
    "facebook_push": false,
    "auto_republish": false,
    "banner": false,
    "background_color": false,
    "featuring": false,
    "images_limit": 5,
    "videos_limit": 1,
    "default_duration": 30,
    "max_duration": 30,
    "show_contact_immediately": true
  },
  "message": "Your package features retrieved successfully"
}
```

---

### Step 6: Check ad publishing capability
```bash
curl -X POST "http://localhost:8000/api/v1/packages/check-capability" \
 -H "Authorization: Bearer 3|AbCdEfGhIjKlMnOpQrStUvWxYz..." \
 -H "Content-Type: application/json" \
 -d '{
   "capability": "publish_ad",
   "ad_type": "normal"
 }'
```

**Expected Response (200)**:
```json
{
  "success": true,
  "data": {
    "capability": "publish_ad",
    "allowed": true,
    "remaining": 10,
    "reason": null
  },
  "message": "Capability check completed"
}
```

---

## Test Scenario 2: Admin Creates User Account (Custom Account Type)

### Admin creates a user with account_type = "dealer"
```bash
curl -X POST "http://localhost:8000/api/v1/users" \
 -H "Authorization: Bearer {ADMIN_TOKEN}" \
 -H "Content-Type: application/json" \
 -d '{
   "name": "Jane Dealer",
   "email": "jane.dealer@example.com",
   "phone": "+201987654321",
   "country_id": 1,
   "password": "AdminPass123!",
   "password_confirmation": "AdminPass123!",
   "account_type": "dealer"
 }'
```

**Expected Response (201)**:
```json
{
  "success": true,
  "data": {
    "id": 46,
    "name": "Jane Dealer",
    "email": "jane.dealer@example.com",
    "phone": "+201987654321",
    "account_type": "dealer",
    "is_verified": false,
    "created_at": "2026-02-13T12:00:00.000000Z"
  },
  "message": "User created successfully"
}
```

**Key fields to verify**:
- `account_type`: "dealer" (admin-specified, not defaulted to individual)
- Standard Package automatically assigned (check with GET users/{id}/packages)

### Verify Standard Package was assigned to admin-created user
```bash
curl -X GET "http://localhost:8000/api/v1/users/46/packages" \
 -H "Authorization: Bearer {ADMIN_TOKEN}" \
 -H "Accept: application/json"
```

**Expected Response**: Should show Standard Package assigned with 365 days duration.

---

## Test Scenario 3: Database Reset - Standard Package Persistence

### Step 1: Reset database
```bash
php artisan migrate:fresh --seed
```

### Step 2: Verify Standard Package still exists
```bash
php artisan tinker --execute "dd(\App\Models\Package::where('name', 'Standard Package')->first())"
```

**Expected Output**:
```php
App\Models\Package^ {
  #attributes: array:11 [
    "id" => 3
    "name" => "Standard Package"
    "price" => "0.00"
    "duration_days" => 365
    "active" => 1
    // ...
  ]
}
```

**Result**: Standard Package persists because `PackagesSeeder` uses `updateOrInsert()` which ensures it's recreated on every seed.

---

## Verification Checklist

- [ ] Registration without `account_type` defaults to `"individual"` (not null)
- [ ] Registration with `account_type` in request uses the provided value
- [ ] Standard Package automatically assigned to new users
- [ ] Standard Package has:
  - Name: "Standard Package"
  - Price: 0.00
  - Duration: 365 days
  - Active: true
- [ ] Package features applied immediately (check user roles/verification)
- [ ] `GET /api/v1/users/{id}/packages` shows Standard Package
- [ ] `GET /api/v1/packages/my-features` returns package feature limits
- [ ] Standard Package persists after `php artisan migrate:fresh --seed`

---

## Important Notes

1. **Standard Package seed**: Run `php artisan db:seed --class=PackagesSeeder` to ensure Standard Package exists
2. **Default account_type**: If not provided in registration, defaults to `"individual"`
3. **Admin user creation**: Should allow admin to specify custom `account_type`
4. **Package features**: Configure PackageFeature for Standard Package to define limits (normal ads, images, videos, etc.)
5. **OTP retrieval**: Check logs at `storage/logs/laravel.log` or configure SMS/email gateway

---

## Configuration

### To change the default package, update:
- File: `app/Http/Controllers/Api/V1/AuthController.php`
- Line: `$defaultPackage = Package::where('name', 'Standard Package')->where('active', true)->first();`

### To modify Standard Package settings:
- File: `database/seeders/PackagesSeeder.php`
- Adjust price, duration_days, or description as needed
- Re-run seeder: `php artisan db:seed --class=PackagesSeeder`
