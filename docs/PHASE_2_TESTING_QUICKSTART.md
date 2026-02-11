# Phase 2 Testing Quick Start Guide

**Date:** February 11, 2026  
**Testing Duration:** ~30-45 minutes for complete suite

---

## Quick Setup (5 minutes)

### 1. Database Setup
```bash
# Run migrations
cd e:\digiway\3jlcom
php artisan migrate

# Verify migrations
php artisan migrate:status | findstr "2026_02_11"
```

**Expected Output:**
```
Ran  2026_02_11_000006_add_feature_credits_to_package_features_table
Ran  2026_02_11_000007_create_feature_usage_logs_table
Ran  2026_02_11_000008_create_ad_type_conversions_table
```

---

### 2. Test Data Setup

#### Create Free Plan User
```sql
-- Create a free package if not exists
INSERT INTO packages (name, price, period_type) 
VALUES ('Free Basic', 0.00, 'lifetime');

-- Assign to a test user
INSERT INTO user_packages (user_id, package_id, is_active, started_at) 
VALUES (1, LAST_INSERT_ID(), 1, NOW());
```

#### Create Paid Plan User with Credits
```sql
-- Create a paid package
INSERT INTO packages (name, price, period_type) 
VALUES ('Premium Monthly', 29.99, 'monthly');

SET @paid_package_id = LAST_INSERT_ID();

-- Add feature credits
UPDATE package_features 
SET 
    unique_ads_allowed = 1,
    unique_ads_limit = 10,
    facebook_push_limit = 5,
    ai_video_credits = 3,
    auto_bg_credits = 10,
    pixblin_credits = 10,
    carseer_api_credits = 2
WHERE package_id = @paid_package_id;

-- Assign to test user
INSERT INTO user_packages (user_id, package_id, is_active, started_at) 
VALUES (2, @paid_package_id, 1, NOW());
```

#### Create Unique Ad Type with Credits
```sql
UPDATE unique_ad_type_definitions 
SET 
    facebook_push_enabled = 1,
    carseer_api_credits = 2,
    auto_bg_credits = 5,
    pixblin_credits = 5
WHERE id = 1;
```

---

## Critical Path Tests (15 minutes)

### Test 1: Free User Flow (5 min)

#### Step 1.1: Attempt Direct Unique Ad Creation (Should FAIL)
```bash
# Get auth token for free user
curl -X POST http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "freeuser@test.com", "password": "password"}'

# Copy token, then try to create unique ad
curl -X POST http://localhost/api/v1/unique-ads \
  -H "Authorization: Bearer {FREE_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Test Unique",
    "description": "Should fail",
    "category_id": 1,
    "city_id": 1,
    "country_id": 1
  }'
```

✅ **Expected:** `403 Forbidden` - "Free plan users cannot directly create unique ads"

---

#### Step 1.2: Create Normal Ad (Should SUCCEED)
```bash
curl -X POST http://localhost/api/v1/normal-ads \
  -H "Authorization: Bearer {FREE_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "My Normal Ad",
    "description": "This should work",
    "category_id": 1,
    "city_id": 1,
    "country_id": 1
  }'
```

✅ **Expected:** `201 Created` - Note the `ad_id`

---

#### Step 1.3: Request Upgrade (Should SUCCEED)
```bash
curl -X POST http://localhost/api/v1/ads/{AD_ID}/upgrade-request \
  -H "Authorization: Bearer {FREE_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "requested_unique_type_id": 1,
    "user_message": "Please upgrade"
  }'
```

✅ **Expected:** `201 Created` - Status is `pending`

---

#### Step 1.4: Admin Approves (Should SUCCEED)
```bash
# Login as admin
curl -X POST http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@test.com", "password": "password"}'

# Approve the request
curl -X PATCH http://localhost/api/v1/admin/ad-upgrade-requests/1/approve \
  -H "Authorization: Bearer {ADMIN_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{"admin_notes": "Approved"}'
```

✅ **Expected:** `200 OK` - Ad is now type `unique`

**Verify:** Check ad details show type changed
```bash
curl http://localhost/api/v1/ads/{AD_ID}
```

---

### Test 2: Paid User Flow (5 min)

#### Step 2.1: Direct Unique Ad Creation (Should SUCCEED)
```bash
# Login as paid user
curl -X POST http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "paiduser@test.com", "password": "password"}'

# Create unique ad directly
curl -X POST http://localhost/api/v1/unique-ads \
  -H "Authorization: Bearer {PAID_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Premium Unique Ad",
    "description": "Direct creation",
    "category_id": 1,
    "city_id": 1,
    "country_id": 1,
    "unique_ad_type_id": 1
  }'
```

✅ **Expected:** `201 Created` - Unique ad created successfully

---

#### Step 2.2: Attempt Upgrade Request (Should FAIL)
```bash
curl -X POST http://localhost/api/v1/ads/{PAID_USER_AD}/upgrade-request \
  -H "Authorization: Bearer {PAID_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{"requested_unique_type_id": 2}'
```

✅ **Expected:** `403 Forbidden` - "Paid plan users can create unique ads directly..."

---

#### Step 2.3: Ad Type Conversion (Should SUCCEED)
```bash
# First create a normal ad
curl -X POST http://localhost/api/v1/normal-ads \
  -H "Authorization: Bearer {PAID_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Normal to Convert",
    "description": "Will convert this",
    "category_id": 1,
    "city_id": 1,
    "country_id": 1
  }'

# Note the ad_id, then convert
curl -X POST http://localhost/api/v1/ads/{AD_ID}/convert \
  -H "Authorization: Bearer {PAID_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "to_type": "unique",
    "unique_ad_type_id": 1
  }'
```

✅ **Expected:** `200 OK` - Conversion successful

**Verify in database:**
```sql
SELECT type FROM ads WHERE id = {AD_ID};
-- Should show: unique

SELECT * FROM ad_type_conversions WHERE ad_id = {AD_ID};
-- Should show conversion record
```

---

### Test 3: Feature Credits (5 min)

#### Step 3.1: Check Available Credits
```bash
curl -X GET http://localhost/api/v1/feature-credits \
  -H "Authorization: Bearer {PAID_TOKEN}"
```

✅ **Expected:** JSON with all feature credits:
```json
{
  "facebook_push": {"remaining": 5, "total": 5},
  "ai_video": {"remaining": 3, "total": 3},
  // etc.
}
```

---

#### Step 3.2: Use Facebook Push
```bash
curl -X POST http://localhost/api/v1/ads/{AD_ID}/push-facebook \
  -H "Authorization: Bearer {PAID_TOKEN}"
```

✅ **Expected:** `200 OK` - Credit consumed, `credits_remaining: 4`

---

#### Step 3.3: Verify Credit Deduction
```bash
curl -X GET http://localhost/api/v1/feature-credits \
  -H "Authorization: Bearer {PAID_TOKEN}"
```

✅ **Expected:** `facebook_push.remaining = 4` (decreased by 1)

---

#### Step 3.4: Check Usage History
```bash
curl -X GET http://localhost/api/v1/feature-usage \
  -H "Authorization: Bearer {PAID_TOKEN}"
```

✅ **Expected:** Array with usage log showing:
- `feature: "facebook_push"`
- `credits_source: "package"`
- `credits_used: 1`

---

#### Step 3.5: Exhaust Credits
```bash
# Use Facebook push 4 more times (total 5)
for i in 1 2 3 4; do
  curl -X POST http://localhost/api/v1/ads/{AD_ID}/push-facebook \
    -H "Authorization: Bearer {PAID_TOKEN}"
done
```

✅ **Expected:** All succeed with decreasing credits

---

#### Step 3.6: Attempt When Exhausted
```bash
curl -X POST http://localhost/api/v1/ads/{AD_ID}/push-facebook \
  -H "Authorization: Bearer {PAID_TOKEN}"
```

✅ **Expected:** `403 Forbidden` - "Insufficient facebook_push credits"

---

## Edge Case Tests (10 minutes)

### Test 4: Validation & Security

#### 4.1: Convert to Same Type
```bash
curl -X POST http://localhost/api/v1/ads/{UNIQUE_AD}/convert \
  -H "Authorization: Bearer {PAID_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{"to_type": "unique"}'
```

✅ **Expected:** `422` - "Cannot convert to the same type"

---

#### 4.2: Non-Owner Attempts Feature
```bash
# User 1 tries to use User 2's ad
curl -X POST http://localhost/api/v1/ads/{USER2_AD}/push-facebook \
  -H "Authorization: Bearer {USER1_TOKEN}"
```

✅ **Expected:** `403` - "You do not own this ad"

---

#### 4.3: Admin Unlimited Credits
```bash
# Admin uses feature multiple times
curl -X POST http://localhost/api/v1/ads/{ANY_AD}/ai-video \
  -H "Authorization: Bearer {ADMIN_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{"style": "modern"}'
```

✅ **Expected:** `200 OK` - Always succeeds regardless of credits

---

#### 4.4: Missing Required Fields
```bash
# Auto-BG without media_id
curl -X POST http://localhost/api/v1/ads/{AD_ID}/auto-bg \
  -H "Authorization: Bearer {PAID_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{}'
```

✅ **Expected:** `422` - Validation error for `media_id`

---

## Database Verification Tests (5 minutes)

### Test 5: Database Integrity

#### 5.1: Check Feature Usage Logs
```sql
SELECT 
    u.email,
    f.feature,
    f.credits_source,
    f.credits_used,
    f.created_at
FROM feature_usage_logs f
JOIN users u ON f.user_id = u.id
ORDER BY f.created_at DESC 
LIMIT 10;
```

✅ **Expected:** Rows showing all feature usages with correct sources

---

#### 5.2: Verify Credit Calculations
```sql
-- Check total credits used by a user
SELECT 
    feature,
    SUM(credits_used) as total_used
FROM feature_usage_logs
WHERE user_id = 2
GROUP BY feature;
```

Compare with available credits from API

---

#### 5.3: Check Conversion History
```sql
SELECT 
    a.title,
    c.from_type,
    c.to_type,
    c.created_at
FROM ad_type_conversions c
JOIN ads a ON c.ad_id = a.id
ORDER BY c.created_at DESC;
```

✅ **Expected:** All conversions logged correctly

---

## Automated Test Script (PowerShell)

Save as `test-phase2.ps1`:

```powershell
# Phase 2 Automated Test Script
$baseUrl = "http://localhost/api/v1"

Write-Host "=== Phase 2 Testing ===" -ForegroundColor Cyan

# Test 1: Routes exist
Write-Host "`n[1] Checking routes..." -ForegroundColor Yellow
php artisan route:list --path=convert | Select-String "convert"
php artisan route:list --path=feature-credits | Select-String "feature-credits"

# Test 2: Database tables
Write-Host "`n[2] Checking database tables..." -ForegroundColor Yellow
php artisan tinker --execute="
echo 'feature_usage_logs: ' . DB::table('feature_usage_logs')->count() . PHP_EOL;
echo 'ad_type_conversions: ' . DB::table('ad_type_conversions')->count() . PHP_EOL;
"

# Test 3: Package Features
Write-Host "`n[3] Checking package features..." -ForegroundColor Yellow
php artisan tinker --execute="
\$pf = App\Models\PackageFeature::first();
echo 'Has facebook_push_limit: ' . (isset(\$pf->facebook_push_limit) ? 'YES' : 'NO') . PHP_EOL;
echo 'Has ai_video_credits: ' . (isset(\$pf->ai_video_credits) ? 'YES' : 'NO') . PHP_EOL;
"

Write-Host "`n=== Tests Complete ===" -ForegroundColor Green
```

Run with:
```bash
cd e:\digiway\3jlcom
powershell -ExecutionPolicy Bypass -File test-phase2.ps1
```

---

## Test Results Template

Copy and fill out:

```
PHASE 2 TESTING RESULTS
Date: __________
Tester: __________

✅ = Pass | ❌ = Fail | ⚠️ = Partial

[ ] Database Migrations
    [ ] Migration 000006 ran successfully
    [ ] Migration 000007 ran successfully
    [ ] Migration 000008 ran successfully

[ ] Free Plan Flow
    [ ] Free user blocked from direct unique ads
    [ ] Free user can create normal ads
    [ ] Free user can request upgrades
    [ ] Admin can approve upgrades
    [ ] Free user gets credits from type definition

[ ] Paid Plan Flow
    [ ] Paid user can create unique ads directly
    [ ] Paid user blocked from upgrade requests
    [ ] Paid user can convert ad types
    [ ] Paid user gets credits from package

[ ] Feature Credits
    [ ] GET /feature-credits returns correct data
    [ ] POST /ads/{ad}/push-facebook works
    [ ] POST /ads/{ad}/ai-video works
    [ ] POST /ads/{ad}/auto-bg works
    [ ] POST /ads/{ad}/pixblin works
    [ ] POST /ads/{ad}/carseer works
    [ ] Insufficient credits blocked with 403
    [ ] Usage history tracked correctly

[ ] Edge Cases
    [ ] Same-type conversion blocked
    [ ] Non-owner access blocked
    [ ] Admin has unlimited credits
    [ ] Validation errors work

Notes:
_________________________________
_________________________________
```

---

## Quick Commands Reference

```bash
# Clear all caches
php artisan config:clear && php artisan route:clear && php artisan cache:clear

# Check routes
php artisan route:list --path=convert
php artisan route:list --path=feature

# Migration commands
php artisan migrate:status
php artisan migrate:rollback --step=3

# Database queries
php artisan tinker
>>> DB::table('feature_usage_logs')->count()
>>> DB::table('ad_type_conversions')->latest()->first()
```

---

**Estimated Total Testing Time:** 30-45 minutes  
**Priority Level:** Critical (affects billing & user experience)
