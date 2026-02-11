# Unique Ads System - Phase 2 Implementation

**Implementation Date:** February 11, 2026  
**Status:** Complete  
**Version:** 2.0

---

## Table of Contents
1. [Executive Summary](#executive-summary)
2. [Business Logic Rules](#business-logic-rules)
3. [Database Schema Changes](#database-schema-changes)
4. [New Models & Services](#new-models--services)
5. [New API Endpoints](#new-api-endpoints)
6. [Modified Controllers](#modified-controllers)
7. [Testing Guide](#testing-guide)
8. [Migration Commands](#migration-commands)

---

## Executive Summary

Phase 2 implements the **dual-track system** for unique ads and actionable feature credits:

### Key Features Implemented:
- **FREE Plans**: Users create normal ads → request admin-approved upgrades → receive unique ad type features
- **PAID Plans**: Users create unique ads directly with package-based features OR convert existing ads between types
- **Actionable Feature Credits**: AI video, auto-background, Pixblin, Carseer API, Facebook push with credit tracking and usage logs
- **Ad Type Conversion**: Paid users can convert ads between types (both counters deducted)

---

## Business Logic Rules

### 1. Free vs Paid Plan Flow

#### FREE Plan Users (Package price = 0 OR no active package):
- ❌ **CANNOT** directly create unique ads
- ✅ **CAN** create normal ads
- ✅ **CAN** request upgrades via `POST /api/v1/ads/{ad}/upgrade-request`
- ✅ Admin approves → ad becomes unique with type features
- ✅ Features come from `UniqueAdTypeDefinition`
- ❌ **CANNOT** use ad type conversion

#### PAID Plan Users (Package price > 0):
- ✅ **CAN** directly create unique ads with types
- ✅ Features come from `PackageFeature` credits
- ✅ **CAN** convert ads between types using `POST /api/v1/ads/{ad}/convert`
- ❌ **CANNOT** use upgrade request system (blocked with 403)

### 2. Ad Type Conversion Rules (Paid Only)
- Both source and destination type counters are deducted
- Ad's `type` column is updated
- Sub-table records created/removed (e.g., `normal_ads`, `unique_ads`)
- Requires active paid package
- History tracked in `ad_type_conversions` table

### 3. Actionable Feature Credits

#### Credit Sources:
1. **Package Credits** (Paid Plans): From `package_features` table
2. **Type Credits** (Free Plans): From `unique_ad_type_definitions` table

#### Trackable Features:
- `facebook_push` - Push ad to Facebook marketplace
- `ai_video` - Generate AI video for ad
- `auto_bg` - Auto-background removal/editing
- `pixblin` - Pixblin image editing
- `carseer` - Carseer vehicle inspection API

#### Credit Consumption:
- Each action consumes 1 credit (configurable per feature)
- Usage logged in `feature_usage_logs` table
- Credits checked before action execution
- Insufficient credits = 403 error response

---

## Database Schema Changes

### Migration 000006: Add Feature Credits to `package_features`

**File:** `2026_02_11_000006_add_feature_credits_to_package_features_table.php`

**New Columns:**
```sql
allows_image_frame          BOOLEAN      DEFAULT false
caishha_feature_enabled     BOOLEAN      DEFAULT false
facebook_push_limit         INTEGER      DEFAULT 0
carseer_api_credits         INTEGER      DEFAULT 0
auto_bg_credits             INTEGER      DEFAULT 0
pixblin_credits             INTEGER      DEFAULT 0
ai_video_credits            INTEGER      DEFAULT 0
custom_features_text        JSON         NULL
```

**Purpose:** Store actionable feature credits for paid plan packages.

---

### Migration 000007: Create `feature_usage_logs` Table

**File:** `2026_02_11_000007_create_feature_usage_logs_table.php`

**Schema:**
```sql
CREATE TABLE feature_usage_logs (
    id                  BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id             BIGINT UNSIGNED NOT NULL,
    ad_id               BIGINT UNSIGNED NULL,
    feature             VARCHAR(50) NOT NULL,
    credits_source      ENUM('package', 'unique_ad_type') NOT NULL,
    source_id           BIGINT UNSIGNED NOT NULL,
    credits_used        INTEGER DEFAULT 1,
    metadata            JSON NULL,
    created_at          TIMESTAMP,
    updated_at          TIMESTAMP,
    
    INDEX idx_user_feature (user_id, feature),
    INDEX idx_ad_feature (ad_id, feature),
    INDEX idx_source (credits_source, source_id),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (ad_id) REFERENCES ads(id) ON DELETE CASCADE
)
```

**Purpose:** Track every actionable feature usage with credit source attribution.

**Tracked Features:**
- `facebook_push`
- `ai_video`
- `auto_bg`
- `pixblin`
- `carseer`

---

### Migration 000008: Create `ad_type_conversions` Table

**File:** `2026_02_11_000008_create_ad_type_conversions_table.php`

**Schema:**
```sql
CREATE TABLE ad_type_conversions (
    id                      BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    ad_id                   BIGINT UNSIGNED NOT NULL,
    user_id                 BIGINT UNSIGNED NOT NULL,
    from_type               VARCHAR(50) NOT NULL,
    to_type                 VARCHAR(50) NOT NULL,
    unique_ad_type_id       BIGINT UNSIGNED NULL,
    created_at              TIMESTAMP,
    updated_at              TIMESTAMP,
    
    INDEX idx_ad_conversions (ad_id),
    INDEX idx_user_conversions (user_id),
    
    FOREIGN KEY (ad_id) REFERENCES ads(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (unique_ad_type_id) REFERENCES unique_ad_type_definitions(id) ON DELETE SET NULL
)
```

**Purpose:** Audit trail for all ad type conversions.

---

## New Models & Services

### 1. FeatureUsageLog Model

**File:** `app/Models/FeatureUsageLog.php`

**Constants:**
```php
const FEATURE_FACEBOOK_PUSH = 'facebook_push';
const FEATURE_AI_VIDEO = 'ai_video';
const FEATURE_AUTO_BG = 'auto_bg';
const FEATURE_PIXBLIN = 'pixblin';
const FEATURE_CARSEER = 'carseer';

const SOURCE_PACKAGE = 'package';
const SOURCE_UNIQUE_AD_TYPE = 'unique_ad_type';
```

**Key Methods:**
- `getUsedCredits($userId, $feature, $source, $sourceId)` - Total credits used from a source
- `getTotalUsedCredits($userId, $feature)` - Total credits used across all sources
- `getAdUsage($adId, $feature)` - Usage for specific ad

**Relationships:**
- `user()` - BelongsTo User
- `ad()` - BelongsTo Ad

---

### 2. AdTypeConversion Model

**File:** `app/Models/AdTypeConversion.php`

**Fillable:**
- `ad_id`, `user_id`, `from_type`, `to_type`, `unique_ad_type_id`

**Relationships:**
- `ad()` - BelongsTo Ad
- `user()` - BelongsTo User
- `uniqueAdTypeDefinition()` - BelongsTo UniqueAdTypeDefinition

---

### 3. FeatureUsageService

**File:** `app/Services/FeatureUsageService.php`

**Purpose:** Central service for managing actionable feature credits from both sources.

**Key Methods:**

#### `checkCredits(User $user, string $feature, ?int $adId = null): array`
Returns credit availability with source information:
```php
[
    'has_credits' => bool,
    'remaining' => int,
    'total' => int,
    'source' => 'package|unique_ad_type|none',
    'source_id' => int
]
```

**Logic:**
1. **Admin users**: Unlimited credits
2. **Paid plan users**: Credits from `PackageFeature`
3. **Free plan users with unique ad**: Credits from `UniqueAdTypeDefinition`
4. **No package/no ad**: No credits

#### `consumeCredits(User $user, string $feature, ?int $adId, int $credits = 1, array $metadata = []): array`
Checks availability, creates usage log, returns result:
```php
[
    'success' => bool,
    'reason' => string|null,
    'remaining' => int,
    'log_id' => int|null
]
```

#### `getAllCredits(User $user): array`
Returns remaining credits for all features:
```php
[
    'facebook_push' => [...],
    'ai_video' => [...],
    'auto_bg' => [...],
    // etc.
]
```

#### `getUsageHistory(User $user, ?string $feature, ?int $adId, int $limit): Collection`
Retrieves paginated usage logs with filters.

---

### 4. Updated PackageFeature Model

**File:** `app/Models/PackageFeature.php`

**New Methods:**

```php
allowsImageFrame(): bool
hasCaishhaFeature(): bool
getFeatureCredits(string $feature): int
getActionableFeatureCredits(): array
```

**New Fillable Fields:**
- `allows_image_frame`, `caishha_feature_enabled`, `facebook_push_limit`, `carseer_api_credits`, `auto_bg_credits`, `pixblin_credits`, `ai_video_credits`, `custom_features_text`

---

## New API Endpoints

### Ad Type Conversion (Authenticated, Paid Plans Only)

#### Convert Ad Between Types
```http
POST /api/v1/ads/{ad}/convert
Authorization: Bearer {token}
Content-Type: application/json

{
    "to_type": "unique",          // normal|unique|caishha
    "unique_ad_type_id": 2        // Required when to_type = unique
}
```

**Response 200:**
```json
{
    "status": "success",
    "message": "Ad converted from normal to unique successfully",
    "data": {
        "ad": {
            "id": 123,
            "type": "unique",
            "title": "...",
            "slug": "..."
        },
        "conversion": {
            "id": 1,
            "ad_id": 123,
            "from_type": "normal",
            "to_type": "unique",
            "unique_ad_type_id": 2,
            "created_at": "2026-02-11T10:00:00Z"
        }
    }
}
```

**Error Responses:**
- `403` - Free plan user attempted conversion
- `403` - Destination type not allowed by package
- `422` - Converting to same type

---

#### Get Ad Conversion History
```http
GET /api/v1/ads/{ad}/conversions
Authorization: Bearer {token}
```

**Response 200:**
```json
{
    "status": "success",
    "message": "Ad conversion history retrieved",
    "data": [
        {
            "id": 1,
            "from_type": "normal",
            "to_type": "unique",
            "unique_ad_type_id": 2,
            "created_at": "2026-02-11T10:00:00Z"
        }
    ]
}
```

---

### Actionable Features (Authenticated)

#### Get User's Feature Credits
```http
GET /api/v1/feature-credits
Authorization: Bearer {token}
```

**Response 200:**
```json
{
    "status": "success",
    "message": "Feature credits retrieved",
    "data": {
        "facebook_push": {
            "has_credits": true,
            "remaining": 5,
            "total": 10,
            "source": "package",
            "source_id": 42
        },
        "ai_video": {
            "has_credits": true,
            "remaining": 3,
            "total": 5,
            "source": "package",
            "source_id": 42
        },
        "auto_bg": { ... },
        "pixblin": { ... },
        "carseer": { ... }
    }
}
```

---

#### Get Usage History
```http
GET /api/v1/feature-usage?feature=ai_video&ad_id=123&limit=50
Authorization: Bearer {token}
```

**Response 200:**
```json
{
    "status": "success",
    "message": "Feature usage history retrieved",
    "data": [
        {
            "id": 1,
            "user_id": 45,
            "ad_id": 123,
            "feature": "ai_video",
            "credits_source": "package",
            "source_id": 42,
            "credits_used": 1,
            "metadata": {
                "style": "modern",
                "duration": 30
            },
            "created_at": "2026-02-11T09:30:00Z"
        }
    ]
}
```

---

#### Push Ad to Facebook
```http
POST /api/v1/ads/{ad}/push-facebook
Authorization: Bearer {token}
Content-Type: application/json

{}
```

**Response 200:**
```json
{
    "status": "success",
    "message": "Facebook push credits consumed. Processing will begin shortly.",
    "data": {
        "feature": "facebook_push",
        "ad_id": 123,
        "credits_remaining": 4,
        "usage_log_id": 1,
        "message": "Facebook push action initiated successfully."
    }
}
```

**Error 403:**
```json
{
    "status": "error",
    "code": 403,
    "message": "Insufficient facebook_push credits. Remaining: 0, required: 1.",
    "errors": {
        "feature": ["Insufficient facebook_push credits. Remaining: 0, required: 1."],
        "remaining": 0
    }
}
```

---

#### Generate AI Video
```http
POST /api/v1/ads/{ad}/ai-video
Authorization: Bearer {token}
Content-Type: application/json

{
    "style": "modern",        // Optional
    "duration": 30            // Optional, 5-60 seconds
}
```

**Response:** Same structure as Facebook push

---

#### Auto-Background Editing
```http
POST /api/v1/ads/{ad}/auto-bg
Authorization: Bearer {token}
Content-Type: application/json

{
    "media_id": 456,                      // Required
    "background_type": "transparent",     // Optional: transparent|blur|color|scene
    "background_value": "#FFFFFF"         // Optional: hex color or scene name
}
```

---

#### Pixblin Image Editing
```http
POST /api/v1/ads/{ad}/pixblin
Authorization: Bearer {token}
Content-Type: application/json

{
    "media_id": 456,          // Required
    "edits": {                // Optional: editing parameters
        "brightness": 1.2,
        "contrast": 1.1
    }
}
```

---

#### Carseer Vehicle API
```http
POST /api/v1/ads/{ad}/carseer
Authorization: Bearer {token}
Content-Type: application/json

{
    "vin": "1HGBH41JXMN109186",      // Optional
    "inspection_type": "full"         // Optional: basic|full
}
```

---

## Modified Controllers

### 1. UniqueAdsController - store() Method

**File:** `app/Http/Controllers/Api/V1/UniqueAdsController.php`

**Changes:**
- Added free plan blocking logic
- Free plan users get `403` with message to use upgrade request system
- Only admins can create unique ads for free plan users

**New Error Response:**
```json
{
    "status": "error",
    "code": 403,
    "message": "Free plan users cannot directly create unique ads. Create a normal ad first, then request an upgrade via the upgrade request system.",
    "errors": {
        "plan": [
            "Unique ad creation requires a paid plan. Use POST /api/v1/ads/{ad}/upgrade-request to request an upgrade for an existing normal ad."
        ]
    }
}
```

---

### 2. AdUpgradeRequestController - store() Method

**File:** `app/Http/Controllers/Api/V1/AdUpgradeRequestController.php`

**Changes:**
- Added paid plan blocking logic
- Paid plan users get `403` with message to use conversion system

**New Error Response:**
```json
{
    "status": "error",
    "code": 403,
    "message": "Paid plan users can create unique ads directly or use ad type conversion. Upgrade requests are for free plan users only.",
    "errors": {
        "plan": [
            "Use POST /api/v1/ads/{ad}/convert for paid plan ad type conversion."
        ]
    }
}
```

---

### 3. PackageFeatureResource

**File:** `app/Http/Resources/PackageFeatureResource.php`

**Added Section:**
```php
'actionable_features' => [
    'allows_image_frame' => $this->allows_image_frame,
    'caishha_feature_enabled' => $this->caishha_feature_enabled,
    'facebook_push_limit' => $this->facebook_push_limit,
    'carseer_api_credits' => $this->carseer_api_credits,
    'auto_bg_credits' => $this->auto_bg_credits,
    'pixblin_credits' => $this->pixblin_credits,
    'ai_video_credits' => $this->ai_video_credits,
    'custom_features_text' => $this->custom_features_text ?? [],
]
```

---

## Testing Guide

### Prerequisites
```bash
# Ensure database is up to date
php artisan migrate

# Clear caches
php artisan config:clear
php artisan route:clear
php artisan cache:clear

# Verify routes exist
php artisan route:list --path=convert
php artisan route:list --path=feature
```

---

### Test Suite 1: Database Migrations

#### Test 1.1: Verify Migration Status
```bash
php artisan migrate:status
```

**Expected:** All three new migrations should show "Ran"
- `2026_02_11_000006_add_feature_credits_to_package_features_table`
- `2026_02_11_000007_create_feature_usage_logs_table`
- `2026_02_11_000008_create_ad_type_conversions_table`

---

#### Test 1.2: Verify Table Structures
```sql
-- Check package_features has new columns
DESCRIBE package_features;
-- Should show: facebook_push_limit, ai_video_credits, etc.

-- Check feature_usage_logs exists
DESCRIBE feature_usage_logs;
-- Should show: feature, credits_source, source_id, etc.

-- Check ad_type_conversions exists
DESCRIBE ad_type_conversions;
-- Should show: from_type, to_type, unique_ad_type_id
```

---

### Test Suite 2: Free Plan User Flow

#### Test 2.1: Free User Cannot Create Unique Ads Directly

**Setup:**
- Create/use a user with a free package (price = 0)
- Get auth token for this user

**Request:**
```bash
curl -X POST http://localhost/api/v1/unique-ads \
  -H "Authorization: Bearer {FREE_USER_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Test Unique Ad",
    "description": "Testing...",
    "category_id": 1,
    "city_id": 1,
    "country_id": 1,
    "unique_ad_type_id": 1
  }'
```

**Expected Response:** `403 Forbidden`
```json
{
    "status": "error",
    "code": 403,
    "message": "Free plan users cannot directly create unique ads..."
}
```

✅ **Pass Criteria:** Free user blocked from direct unique ad creation

---

#### Test 2.2: Free User Creates Normal Ad

**Request:**
```bash
curl -X POST http://localhost/api/v1/normal-ads \
  -H "Authorization: Bearer {FREE_USER_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "My Normal Ad",
    "description": "Testing normal ad creation",
    "category_id": 1,
    "city_id": 1,
    "country_id": 1
  }'
```

**Expected Response:** `201 Created`
```json
{
    "status": "success",
    "message": "Normal ad created successfully",
    "data": {
        "id": 123,
        "type": "normal",
        "title": "My Normal Ad"
    }
}
```

✅ **Pass Criteria:** Normal ad created successfully, note the `ad_id`

---

#### Test 2.3: Free User Requests Upgrade

**Request:**
```bash
curl -X POST http://localhost/api/v1/ads/{AD_ID}/upgrade-request \
  -H "Authorization: Bearer {FREE_USER_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "requested_unique_type_id": 1,
    "user_message": "Please upgrade my ad to Turbo Unique"
  }'
```

**Expected Response:** `201 Created`
```json
{
    "status": "success",
    "message": "Upgrade request submitted successfully...",
    "data": {
        "id": 1,
        "ad_id": 123,
        "requested_unique_type_id": 1,
        "status": "pending"
    }
}
```

✅ **Pass Criteria:** Upgrade request created with `pending` status

---

#### Test 2.4: Admin Approves Upgrade Request

**Request:**
```bash
curl -X PATCH http://localhost/api/v1/admin/ad-upgrade-requests/1/approve \
  -H "Authorization: Bearer {ADMIN_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "admin_notes": "Approved for promotion"
  }'
```

**Expected:** `200 OK`, ad is now type `unique` with `unique_ad_type_id = 1`

**Verify:**
```bash
curl -X GET http://localhost/api/v1/ads/123 \
  -H "Authorization: Bearer {FREE_USER_TOKEN}"
```

Should show `"type": "unique"` and unique ad features applied

✅ **Pass Criteria:** Ad converted to unique with type features

---

### Test Suite 3: Paid Plan User Flow

#### Test 3.1: Paid User Can Create Unique Ads Directly

**Setup:**
- Create/use a user with a paid package (price > 0)
- Get auth token

**Request:**
```bash
curl -X POST http://localhost/api/v1/unique-ads \
  -H "Authorization: Bearer {PAID_USER_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Premium Unique Ad",
    "description": "Direct creation test",
    "category_id": 1,
    "city_id": 1,
    "country_id": 1,
    "unique_ad_type_id": 1
  }'
```

**Expected Response:** `201 Created`
```json
{
    "status": "success",
    "message": "Unique ad created successfully",
    "data": {
        "id": 456,
        "type": "unique",
        "unique_ad": {
            "unique_ad_type_id": 1
        }
    }
}
```

✅ **Pass Criteria:** Paid user can directly create unique ads

---

#### Test 3.2: Paid User Cannot Use Upgrade Request

**Request:**
```bash
curl -X POST http://localhost/api/v1/ads/456/upgrade-request \
  -H "Authorization: Bearer {PAID_USER_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "requested_unique_type_id": 2
  }'
```

**Expected Response:** `403 Forbidden`
```json
{
    "status": "error",
    "code": 403,
    "message": "Paid plan users can create unique ads directly or use ad type conversion..."
}
```

✅ **Pass Criteria:** Paid user blocked from upgrade requests

---

### Test Suite 4: Ad Type Conversion

#### Test 4.1: Convert Normal to Unique

**Setup:**
- Paid user creates a normal ad (ID: 789)

**Request:**
```bash
curl -X POST http://localhost/api/v1/ads/789/convert \
  -H "Authorization: Bearer {PAID_USER_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "to_type": "unique",
    "unique_ad_type_id": 1
  }'
```

**Expected Response:** `200 OK`
```json
{
    "status": "success",
    "message": "Ad converted from normal to unique successfully",
    "data": {
        "ad": {
            "id": 789,
            "type": "unique"
        },
        "conversion": {
            "from_type": "normal",
            "to_type": "unique",
            "unique_ad_type_id": 1
        }
    }
}
```

**Verify in Database:**
```sql
-- Check ad type changed
SELECT type FROM ads WHERE id = 789;
-- Should return: unique

-- Check sub-table records
SELECT * FROM normal_ads WHERE ad_id = 789;
-- Should return: 0 rows (deleted)

SELECT * FROM unique_ads WHERE ad_id = 789;
-- Should return: 1 row (created)

-- Check conversion logged
SELECT * FROM ad_type_conversions WHERE ad_id = 789;
-- Should return: 1 row with from_type=normal, to_type=unique
```

✅ **Pass Criteria:** Ad converted, sub-tables updated, conversion logged

---

#### Test 4.2: Get Conversion History

**Request:**
```bash
curl -X GET http://localhost/api/v1/ads/789/conversions \
  -H "Authorization: Bearer {PAID_USER_TOKEN}"
```

**Expected Response:** `200 OK`
```json
{
    "status": "success",
    "message": "Ad conversion history retrieved",
    "data": [
        {
            "id": 1,
            "from_type": "normal",
            "to_type": "unique",
            "created_at": "..."
        }
    ]
}
```

✅ **Pass Criteria:** Conversion history visible

---

#### Test 4.3: Free User Cannot Convert

**Request:**
```bash
curl -X POST http://localhost/api/v1/ads/{FREE_USER_AD_ID}/convert \
  -H "Authorization: Bearer {FREE_USER_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "to_type": "unique",
    "unique_ad_type_id": 1
  }'
```

**Expected Response:** `403 Forbidden`

✅ **Pass Criteria:** Free user blocked from conversions

---

### Test Suite 5: Actionable Feature Credits

#### Test 5.1: Setup Package with Credits

**Database Setup:**
```sql
-- Update a paid package's features with credits
UPDATE package_features 
SET 
    facebook_push_limit = 10,
    ai_video_credits = 5,
    auto_bg_credits = 20,
    pixblin_credits = 15,
    carseer_api_credits = 3
WHERE package_id = {PAID_PACKAGE_ID};
```

---

#### Test 5.2: Get User's Feature Credits

**Request:**
```bash
curl -X GET http://localhost/api/v1/feature-credits \
  -H "Authorization: Bearer {PAID_USER_TOKEN}"
```

**Expected Response:** `200 OK`
```json
{
    "status": "success",
    "message": "Feature credits retrieved",
    "data": {
        "facebook_push": {
            "has_credits": true,
            "remaining": 10,
            "total": 10,
            "source": "package",
            "source_id": 42
        },
        "ai_video": {
            "has_credits": true,
            "remaining": 5,
            "total": 5,
            "source": "package",
            "source_id": 42
        },
        "auto_bg": { "remaining": 20, ... },
        "pixblin": { "remaining": 15, ... },
        "carseer": { "remaining": 3, ... }
    }
}
```

✅ **Pass Criteria:** All credits displayed correctly from package

---

#### Test 5.3: Use Facebook Push Feature

**Request:**
```bash
curl -X POST http://localhost/api/v1/ads/{AD_ID}/push-facebook \
  -H "Authorization: Bearer {PAID_USER_TOKEN}" \
  -H "Content-Type: application/json"
```

**Expected Response:** `200 OK`
```json
{
    "status": "success",
    "message": "Facebook push credits consumed...",
    "data": {
        "feature": "facebook_push",
        "ad_id": 123,
        "credits_remaining": 9,
        "usage_log_id": 1
    }
}
```

**Verify in Database:**
```sql
SELECT * FROM feature_usage_logs 
WHERE user_id = {USER_ID} AND feature = 'facebook_push'
ORDER BY created_at DESC LIMIT 1;
```

Should show:
- `credits_source = 'package'`
- `source_id = {PACKAGE_FEATURE_ID}`
- `credits_used = 1`

**Get Credits Again:**
```bash
curl -X GET http://localhost/api/v1/feature-credits \
  -H "Authorization: Bearer {PAID_USER_TOKEN}"
```

Should show `facebook_push.remaining = 9`

✅ **Pass Criteria:** Credit consumed, usage logged, remaining decremented

---

#### Test 5.4: Use All Available Credits

**Repeat Test 5.3 nine more times** until `facebook_push` credits = 0

**10th Request Should Fail:**
```bash
curl -X POST http://localhost/api/v1/ads/{AD_ID}/push-facebook \
  -H "Authorization: Bearer {PAID_USER_TOKEN}"
```

**Expected Response:** `403 Forbidden`
```json
{
    "status": "error",
    "code": 403,
    "message": "Insufficient facebook_push credits. Remaining: 0, required: 1.",
    "errors": {
        "feature": ["Insufficient facebook_push credits..."],
        "remaining": 0
    }
}
```

✅ **Pass Criteria:** User blocked when credits exhausted

---

#### Test 5.5: AI Video Generation

**Request:**
```bash
curl -X POST http://localhost/api/v1/ads/{AD_ID}/ai-video \
  -H "Authorization: Bearer {PAID_USER_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "style": "modern",
    "duration": 30
  }'
```

**Expected Response:** `200 OK` with `ai_video` credit consumed

**Verify Metadata Stored:**
```sql
SELECT metadata FROM feature_usage_logs 
WHERE feature = 'ai_video' 
ORDER BY created_at DESC LIMIT 1;
```

Should contain: `{"style": "modern", "duration": 30}`

✅ **Pass Criteria:** AI video credit consumed, metadata stored

---

#### Test 5.6: Auto-Background Feature

**Request:**
```bash
curl -X POST http://localhost/api/v1/ads/{AD_ID}/auto-bg \
  -H "Authorization: Bearer {PAID_USER_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "media_id": 456,
    "background_type": "transparent"
  }'
```

**Expected Response:** `200 OK` with `auto_bg` credit consumed

✅ **Pass Criteria:** Auto-BG credit consumed

---

#### Test 5.7: Pixblin and Carseer

**Test Pixblin:**
```bash
curl -X POST http://localhost/api/v1/ads/{AD_ID}/pixblin \
  -H "Authorization: Bearer {PAID_USER_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "media_id": 456,
    "edits": {"brightness": 1.2}
  }'
```

**Test Carseer:**
```bash
curl -X POST http://localhost/api/v1/ads/{AD_ID}/carseer \
  -H "Authorization: Bearer {PAID_USER_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "vin": "1HGBH41JXMN109186",
    "inspection_type": "full"
  }'
```

✅ **Pass Criteria:** Both features consume credits correctly

---

#### Test 5.8: Get Usage History

**Request:**
```bash
curl -X GET "http://localhost/api/v1/feature-usage?limit=20" \
  -H "Authorization: Bearer {PAID_USER_TOKEN}"
```

**Expected Response:** `200 OK`
```json
{
    "status": "success",
    "message": "Feature usage history retrieved",
    "data": [
        {
            "id": 5,
            "feature": "carseer",
            "credits_source": "package",
            "credits_used": 1,
            "created_at": "..."
        },
        {
            "id": 4,
            "feature": "pixblin",
            "credits_used": 1,
            "created_at": "..."
        }
        // ... more logs
    ]
}
```

**Filter by feature:**
```bash
curl -X GET "http://localhost/api/v1/feature-usage?feature=ai_video&limit=5" \
  -H "Authorization: Bearer {PAID_USER_TOKEN}"
```

Should only show AI video usage

✅ **Pass Criteria:** Usage history retrieved with filters working

---

### Test Suite 6: Free Plan Credits from Unique Ad Type

#### Test 6.1: Setup Type with Credits

**Database Setup:**
```sql
-- Update a unique ad type definition with credits
UPDATE unique_ad_type_definitions 
SET 
    facebook_push_enabled = 1,
    carseer_api_credits = 2,
    auto_bg_credits = 10,
    pixblin_credits = 5
WHERE id = 1;
```

---

#### Test 6.2: Free User with Approved Unique Ad

**Setup:**
- Free user has a unique ad (from approved upgrade request)
- Ad has `unique_ad_type_id = 1`

**Get Credits:**
```bash
curl -X GET http://localhost/api/v1/feature-credits \
  -H "Authorization: Bearer {FREE_USER_TOKEN}"
```

**Expected:** Credits come from `unique_ad_type_definitions`:
```json
{
    "data": {
        "facebook_push": {
            "has_credits": true,
            "remaining": 1,
            "total": 1,
            "source": "unique_ad_type",
            "source_id": 1
        },
        "carseer": {
            "remaining": 2,
            "source": "unique_ad_type",
            "source_id": 1
        }
    }
}
```

---

#### Test 6.3: Free User Uses Feature

**Request:**
```bash
curl -X POST http://localhost/api/v1/ads/{UNIQUE_AD_ID}/auto-bg \
  -H "Authorization: Bearer {FREE_USER_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "media_id": 789,
    "background_type": "transparent"
  }'
```

**Expected Response:** `200 OK`

**Verify in Database:**
```sql
SELECT credits_source, source_id 
FROM feature_usage_logs 
WHERE user_id = {FREE_USER_ID} 
ORDER BY created_at DESC LIMIT 1;
```

Should show:
- `credits_source = 'unique_ad_type'`
- `source_id = 1` (the unique_ad_type_definition.id)

✅ **Pass Criteria:** Free user consumes credits from type definition

---

### Test Suite 7: Edge Cases & Validation

#### Test 7.1: Convert to Same Type
```bash
curl -X POST http://localhost/api/v1/ads/{UNIQUE_AD_ID}/convert \
  -H "Authorization: Bearer {PAID_USER_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{"to_type": "unique"}'
```

**Expected:** `422 Unprocessable Entity`
```json
{
    "status": "error",
    "message": "The ad is already of this type",
    "errors": {
        "to_type": ["Cannot convert to the same type"]
    }
}
```

✅ **Pass Criteria:** Validation blocks same-type conversion

---

#### Test 7.2: Non-Owner Access
```bash
curl -X POST http://localhost/api/v1/ads/{OTHER_USER_AD}/push-facebook \
  -H "Authorization: Bearer {USER_TOKEN}"
```

**Expected:** `403 Forbidden`
```json
{
    "status": "error",
    "message": "You do not own this ad"
}
```

✅ **Pass Criteria:** Ownership validation working

---

#### Test 7.3: Admin Unlimited Credits
```bash
# Admin should be able to use features unlimited times
curl -X POST http://localhost/api/v1/ads/{ANY_AD}/ai-video \
  -H "Authorization: Bearer {ADMIN_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{"style": "modern"}'
```

**Expected:** `200 OK` regardless of credits

✅ **Pass Criteria:** Admins bypass credit checks

---

#### Test 7.4: Package Features Resource
```bash
curl -X GET http://localhost/api/v1/packages/my-features \
  -H "Authorization: Bearer {PAID_USER_TOKEN}"
```

**Expected Response:** Should include new `actionable_features` section:
```json
{
    "data": {
        "actionable_features": {
            "facebook_push_limit": 10,
            "ai_video_credits": 5,
            "auto_bg_credits": 20,
            "pixblin_credits": 15,
            "carseer_api_credits": 3,
            "allows_image_frame": true,
            "caishha_feature_enabled": false,
            "custom_features_text": []
        }
    }
}
```

✅ **Pass Criteria:** Resource outputs new fields

---

## Migration Commands

### Run All Migrations
```bash
php artisan migrate
```

### Rollback Phase 2 Migrations Only
```bash
php artisan migrate:rollback --step=3
```

### Fresh Migration (⚠️ CAUTION: Drops all tables)
```bash
php artisan migrate:fresh
```

### Check Migration Status
```bash
php artisan migrate:status
```

---

## Summary Checklist

### Database ✅
- [ ] All 3 migrations ran successfully
- [ ] `package_features` has 8 new columns
- [ ] `feature_usage_logs` table created
- [ ] `ad_type_conversions` table created

### Free Plan Flow ✅
- [ ] Free users blocked from direct unique ad creation
- [ ] Free users can create normal ads
- [ ] Free users can request upgrades
- [ ] Admin can approve/reject upgrade requests
- [ ] Free users get credits from unique ad type definition

### Paid Plan Flow ✅
- [ ] Paid users can create unique ads directly
- [ ] Paid users blocked from upgrade requests
- [ ] Paid users can convert ads between types
- [ ] Paid users get credits from package features

### Actionable Features ✅
- [ ] GET `/feature-credits` returns all credits correctly
- [ ] GET `/feature-usage` returns history with filters
- [ ] POST `/ads/{ad}/push-facebook` consumes credits
- [ ] POST `/ads/{ad}/ai-video` consumes credits
- [ ] POST `/ads/{ad}/auto-bg` consumes credits
- [ ] POST `/ads/{ad}/pixblin` consumes credits
- [ ] POST `/ads/{ad}/carseer` consumes credits
- [ ] Insufficient credits return 403 error
- [ ] Usage logs track source (package vs type)
- [ ] Admins have unlimited credits

### Ad Type Conversion ✅
- [ ] POST `/ads/{ad}/convert` works for paid users
- [ ] Conversion blocked for free users
- [ ] Sub-table records created/removed correctly
- [ ] Conversion history tracked in database
- [ ] GET `/ads/{ad}/conversions` returns history

---

## Additional Notes

### Performance Considerations
- `feature_usage_logs` table will grow quickly - consider archival strategy after 6-12 months
- Indexes on `(user_id, feature)` and `(ad_id, feature)` optimize credit checks
- Consider caching user credit balances (invalidate on consumption)

### Future Enhancements
- Bulk credit purchase system
- Credit expiration dates
- Credit transfer between users
- Monthly credit reset for subscriptions
- Credit usage analytics dashboard

---

**Document Version:** 2.0  
**Last Updated:** February 11, 2026  
**Status:** Implementation Complete, Testing Ready
