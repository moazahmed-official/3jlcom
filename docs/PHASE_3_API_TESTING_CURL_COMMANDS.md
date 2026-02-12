# Phase 3 API Testing - Complete cURL Commands

## Prerequisites Setup

### Step 0: Login and Get Tokens

```bash
# Login as Admin
curl -X POST 'http://localhost:8000/api/v1/auth/login' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
    "email": "admin@example.com",
    "password": "password"
  }'

# Save the token from response
# ADMIN_TOKEN="your_admin_token_here"
```

```bash
# Login as Seller (for testing role-based visibility)
curl -X POST 'http://localhost:8000/api/v1/auth/login' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
    "email": "seller@example.com",
    "password": "password"
  }'

# Save the token from response
# SELLER_TOKEN="your_seller_token_here"
```

```bash
# Login as Regular User/Buyer
curl -X POST 'http://localhost:8000/api/v1/auth/login' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
    "email": "user@example.com",
    "password": "password"
  }'

# Save the token from response
# USER_TOKEN="your_user_token_here"
```

---

## Test Suite 1: Package Visibility System

### Step 1.1: Create a Public Package (Default)

```bash
curl -X POST 'http://localhost:8000/api/v1/packages' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'Authorization: Bearer ADMIN_TOKEN' \
  --data '{
    "name": "Public Gold Package",
    "description": "Available to everyone - no restrictions",
    "price": 49.99,
    "duration_days": 30,
    "active": true,
    "features": ["normal_ads", "unique_ads", "5_featured_slots"]
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Package created successfully",
  "data": {
    "package": {
      "id": 1,
      "name": "Public Gold Package",
      "visibility_type": "public"
    }
  }
}
```

**Save Package ID**: `PUBLIC_PACKAGE_ID=1`

---

### Step 1.2: List Packages as Guest User

```bash
curl -X GET 'http://localhost:8000/api/v1/packages' \
  --header 'Accept: application/json'
```

**Expected**: Should see the public package

---

### Step 1.3: List Packages as Authenticated Seller

```bash
curl -X GET 'http://localhost:8000/api/v1/packages' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer SELLER_TOKEN'
```

**Expected**: Should see the public package

---

### Step 1.4: Create a Role-Based Package

```bash
curl -X POST 'http://localhost:8000/api/v1/packages' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'Authorization: Bearer ADMIN_TOKEN' \
  --data '{
    "name": "Seller Premium Package",
    "description": "Exclusive features for verified sellers and showrooms",
    "price": 99.99,
    "duration_days": 30,
    "active": true,
    "features": ["normal_ads", "unique_ads", "caishha_ads", "unlimited_featured", "priority_support"]
  }'
```

**Save Package ID**: `SELLER_PACKAGE_ID=2`

---

### Step 1.5: Set Role-Based Visibility (Sellers & Showrooms Only)

```bash
curl -X POST 'http://localhost:8000/api/v1/admin/packages/2/visibility' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'Authorization: Bearer ADMIN_TOKEN' \
  --data '{
    "visibility_type": "role_based",
    "allowed_roles": ["seller", "showroom"]
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Package visibility updated successfully",
  "data": {
    "package": {
      "id": 2,
      "name": "Seller Premium Package",
      "visibility_type": "role_based",
      "allowed_roles": ["seller", "showroom"],
      "user_access_count": 0
    }
  }
}
```

---

### Step 1.6: Verify Seller CAN See Role-Based Package

```bash
curl -X GET 'http://localhost:8000/api/v1/packages' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer SELLER_TOKEN'
```

**Expected**: Response should include BOTH packages (ID 1 and ID 2)

---

### Step 1.7: Verify Regular User CANNOT See Role-Based Package

```bash
curl -X GET 'http://localhost:8000/api/v1/packages' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer USER_TOKEN'
```

**Expected**: Response should include ONLY public package (ID 1), NOT the seller package (ID 2)

---

### Step 1.8: Create a VIP User-Specific Package

```bash
curl -X POST 'http://localhost:8000/api/v1/packages' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'Authorization: Bearer ADMIN_TOKEN' \
  --data '{
    "name": "VIP Exclusive Package",
    "description": "By invitation only - custom features for select clients",
    "price": 299.99,
    "duration_days": 90,
    "active": true,
    "features": ["all_ad_types", "unlimited_everything", "dedicated_support", "custom_branding"]
  }'
```

**Save Package ID**: `VIP_PACKAGE_ID=3`

---

### Step 1.9: Get User IDs for VIP Access

```bash
# List users to get their IDs
curl -X GET 'http://localhost:8000/api/v1/users' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer ADMIN_TOKEN'
```

**Find and save**: 
- `SELLER_USER_ID` (e.g., 5)
- `REGULAR_USER_ID` (e.g., 10)

---

### Step 1.10: Set User-Specific Visibility and Grant Access to Seller

```bash
curl -X POST 'http://localhost:8000/api/v1/admin/packages/3/visibility' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'Authorization: Bearer ADMIN_TOKEN' \
  --data '{
    "visibility_type": "user_specific",
    "user_ids": [5]
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Package visibility updated successfully",
  "data": {
    "package": {
      "id": 3,
      "name": "VIP Exclusive Package",
      "visibility_type": "user_specific",
      "allowed_roles": null,
      "user_access_count": 1
    }
  }
}
```

---

### Step 1.11: Verify Seller CAN See VIP Package

```bash
curl -X GET 'http://localhost:8000/api/v1/packages' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer SELLER_TOKEN'
```

**Expected**: Should see public package (1), role-based package (2), AND VIP package (3)

---

### Step 1.12: Verify Regular User CANNOT See VIP Package

```bash
curl -X GET 'http://localhost:8000/api/v1/packages' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer USER_TOKEN'
```

**Expected**: Should see ONLY public package (1), NOT seller package (2) or VIP package (3)

---

### Step 1.13: Grant VIP Access to Regular User

```bash
curl -X POST 'http://localhost:8000/api/v1/admin/packages/3/grant-access' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'Authorization: Bearer ADMIN_TOKEN' \
  --data '{
    "user_ids": [10]
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Access granted successfully",
  "data": {
    "granted_users_count": 1,
    "total_users_with_access": 2
  }
}
```

---

### Step 1.14: Verify Regular User NOW CAN See VIP Package

```bash
curl -X GET 'http://localhost:8000/api/v1/packages' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer USER_TOKEN'
```

**Expected**: Should now see public package (1) AND VIP package (3)

---

### Step 1.15: List All Users With VIP Access

```bash
curl -X GET 'http://localhost:8000/api/v1/admin/packages/3/users-with-access' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer ADMIN_TOKEN'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Users with access retrieved",
  "data": {
    "users": [
      {
        "id": 5,
        "name": "Seller User",
        "email": "seller@example.com",
        "role": "seller"
      },
      {
        "id": 10,
        "name": "Regular User",
        "email": "user@example.com",
        "role": "user"
      }
    ],
    "total_count": 2
  }
}
```

---

### Step 1.16: Get Current Visibility Settings for VIP Package

```bash
curl -X GET 'http://localhost:8000/api/v1/admin/packages/3/visibility' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer ADMIN_TOKEN'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Package visibility settings retrieved",
  "data": {
    "visibility_type": "user_specific",
    "allowed_roles": null,
    "user_access": [
      {
        "id": 5,
        "name": "Seller User",
        "email": "seller@example.com",
        "role": "seller"
      },
      {
        "id": 10,
        "name": "Regular User",
        "email": "user@example.com",
        "role": "user"
      }
    ]
  }
}
```

---

### Step 1.17: Revoke VIP Access from Seller

```bash
curl -X POST 'http://localhost:8000/api/v1/admin/packages/3/revoke-access' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'Authorization: Bearer ADMIN_TOKEN' \
  --data '{
    "user_ids": [5]
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Access revoked successfully",
  "data": {
    "revoked_users_count": 1,
    "total_users_with_access": 1
  }
}
```

---

### Step 1.18: Verify Seller NO LONGER Sees VIP Package

```bash
curl -X GET 'http://localhost:8000/api/v1/packages' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer SELLER_TOKEN'
```

**Expected**: Should see public package (1) and role-based package (2), but NOT VIP package (3)

---

### Step 1.19: Admin Filter Packages by Visibility Type

```bash
# Filter for role-based packages only
curl -X GET 'http://localhost:8000/api/v1/packages?visibility_type=role_based' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer ADMIN_TOKEN'
```

**Expected**: Should return only the Seller Premium Package (ID 2)

```bash
# Filter for user-specific packages only
curl -X GET 'http://localhost:8000/api/v1/packages?visibility_type=user_specific' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer ADMIN_TOKEN'
```

**Expected**: Should return only the VIP Exclusive Package (ID 3)

---

## Test Suite 2: Enhanced Ad Type Conversion

### Step 2.1: Create a Normal Ad as Admin

```bash
curl -X POST 'http://localhost:8000/api/v1/normal-ads' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'Authorization: Bearer ADMIN_TOKEN' \
  --data '{
    "title": "2020 Toyota Camry - Admin Test",
    "description": "Testing admin unrestricted conversion",
    "price": 25000,
    "category_id": 1,
    "brand_id": 1,
    "model_id": 1,
    "year": 2020,
    "mileage": 35000,
    "condition": "used",
    "fuel_type": "gasoline",
    "transmission": "automatic",
    "phone": "+1234567890",
    "location": "New York",
    "status": "active"
  }'
```

**Save Ad ID**: `ADMIN_AD_ID=100`

---

### Step 2.2: Admin Converts to Unique Ad (Unrestricted)

```bash
curl -X POST 'http://localhost:8000/api/v1/ads/100/convert' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'Authorization: Bearer ADMIN_TOKEN' \
  --data '{
    "to_type": "unique"
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Ad type converted successfully",
  "data": {
    "ad": {
      "id": 100,
      "type": "unique",
      "title": "2020 Toyota Camry - Admin Test"
    },
    "conversion": {
      "from_type": "normal",
      "to_type": "unique",
      "user_id": 1,
      "ad_id": 100
    }
  }
}
```

---

### Step 2.3: Admin Converts to Caishha Ad (Still Unrestricted)

```bash
curl -X POST 'http://localhost:8000/api/v1/ads/100/convert' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'Authorization: Bearer ADMIN_TOKEN' \
  --data '{
    "to_type": "caishha"
  }'
```

**Expected**: Success - Admin can convert multiple times without restrictions

---

### Step 2.4: Admin Converts to FindIt Ad

```bash
curl -X POST 'http://localhost:8000/api/v1/ads/100/convert' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'Authorization: Bearer ADMIN_TOKEN' \
  --data '{
    "to_type": "findit"
  }'
```

**Expected**: Success - FindIt ad type now supported

---

### Step 2.5: Admin Converts to Auction Ad

```bash
curl -X POST 'http://localhost:8000/api/v1/ads/100/convert' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'Authorization: Bearer ADMIN_TOKEN' \
  --data '{
    "to_type": "auction"
  }'
```

**Expected**: Success - All 5 ad types now supported

---

### Step 2.6: Create Free Package That Allows Unique Ads

```bash
curl -X POST 'http://localhost:8000/api/v1/packages' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'Authorization: Bearer ADMIN_TOKEN' \
  --data '{
    "name": "Free Starter Package",
    "description": "Free package with unique ads enabled",
    "price": 0,
    "duration_days": 30,
    "active": true,
    "features": ["normal_ads", "unique_ads", "3_featured_slots"]
  }'
```

**Save Package ID**: `FREE_PACKAGE_ID=4`

---

### Step 2.7: Assign Free Package to Seller

```bash
curl -X POST 'http://localhost:8000/api/v1/packages/4/assign' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'Authorization: Bearer ADMIN_TOKEN' \
  --data '{
    "user_id": 5,
    "active": true
  }'
```

---

### Step 2.8: Create Normal Ad as Free User (Seller)

```bash
curl -X POST 'http://localhost:8000/api/v1/normal-ads' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'Authorization: Bearer SELLER_TOKEN' \
  --data '{
    "title": "2019 Honda Civic - Free User Test",
    "description": "Testing free user conversion with permission",
    "price": 18000,
    "category_id": 1,
    "brand_id": 2,
    "model_id": 5,
    "year": 2019,
    "mileage": 42000,
    "condition": "used",
    "fuel_type": "gasoline",
    "transmission": "manual",
    "phone": "+1234567891",
    "location": "Los Angeles",
    "status": "active"
  }'
```

**Save Ad ID**: `SELLER_AD_ID=101`

---

### Step 2.9: Free User Converts to Unique (Should Succeed)

```bash
curl -X POST 'http://localhost:8000/api/v1/ads/101/convert' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'Authorization: Bearer SELLER_TOKEN' \
  --data '{
    "to_type": "unique"
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Ad type converted successfully",
  "data": {
    "ad": {
      "id": 101,
      "type": "unique",
      "title": "2019 Honda Civic - Free User Test"
    }
  }
}
```

**✓ Free user CAN convert because package allows unique ads**

---

### Step 2.10: Create Free Package That Does NOT Allow Unique Ads

```bash
curl -X POST 'http://localhost:8000/api/v1/packages' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'Authorization: Bearer ADMIN_TOKEN' \
  --data '{
    "name": "Basic Free Package",
    "description": "Free package - normal ads only",
    "price": 0,
    "duration_days": 30,
    "active": true,
    "features": ["normal_ads_only", "1_featured_slot"]
  }'
```

**Save Package ID**: `BASIC_FREE_PACKAGE_ID=5`

---

### Step 2.11: Assign Basic Free Package to Regular User

```bash
curl -X POST 'http://localhost:8000/api/v1/packages/5/assign' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'Authorization: Bearer ADMIN_TOKEN' \
  --data '{
    "user_id": 10,
    "active": true
  }'
```

---

### Step 2.12: Create Normal Ad as Basic Free User

```bash
curl -X POST 'http://localhost:8000/api/v1/normal-ads' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'Authorization: Bearer USER_TOKEN' \
  --data '{
    "title": "2018 Ford Focus - Basic User Test",
    "description": "Testing conversion denial for basic free user",
    "price": 14000,
    "category_id": 1,
    "brand_id": 3,
    "model_id": 8,
    "year": 2018,
    "mileage": 55000,
    "condition": "used",
    "fuel_type": "gasoline",
    "transmission": "automatic",
    "phone": "+1234567892",
    "location": "Chicago",
    "status": "active"
  }'
```

**Save Ad ID**: `USER_AD_ID=102`

---

### Step 2.13: Basic Free User Attempts Conversion (Should FAIL)

```bash
curl -X POST 'http://localhost:8000/api/v1/ads/102/convert' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'Authorization: Bearer USER_TOKEN' \
  --data '{
    "to_type": "unique"
  }'
```

**Expected Response:**
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

**✓ Free user CANNOT convert because package does not allow unique ads**

---

### Step 2.14: Create Paid Package With All Ad Types

```bash
curl -X POST 'http://localhost:8000/api/v1/packages' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'Authorization: Bearer ADMIN_TOKEN' \
  --data '{
    "name": "Paid Premium Package",
    "description": "All features unlocked",
    "price": 149.99,
    "duration_days": 30,
    "active": true,
    "features": ["all_ad_types", "unlimited_featured", "priority_listing", "analytics"]
  }'
```

**Save Package ID**: `PAID_PACKAGE_ID=6`

---

### Step 2.15: Assign Paid Package to Regular User

```bash
curl -X POST 'http://localhost:8000/api/v1/packages/6/assign' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'Authorization: Bearer ADMIN_TOKEN' \
  --data '{
    "user_id": 10,
    "active": true
  }'
```

---

### Step 2.16: Paid User Converts to Unique (Should Succeed)

```bash
curl -X POST 'http://localhost:8000/api/v1/ads/102/convert' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'Authorization: Bearer USER_TOKEN' \
  --data '{
    "to_type": "unique"
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Ad type converted successfully",
  "data": {
    "ad": {
      "id": 102,
      "type": "unique"
    }
  }
}
```

**✓ Paid user CAN convert because package allows unique ads**

---

### Step 2.17: Paid User Converts to Caishha

```bash
curl -X POST 'http://localhost:8000/api/v1/ads/102/convert' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'Authorization: Bearer USER_TOKEN' \
  --data '{
    "to_type": "caishha"
  }'
```

**Expected**: Success

---

### Step 2.18: Attempt Conversion Without Active Package (Should FAIL)

```bash
# First, deactivate all user's packages
curl -X DELETE 'http://localhost:8000/api/v1/user-packages/{user_package_id}' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer ADMIN_TOKEN'

# Then try conversion
curl -X POST 'http://localhost:8000/api/v1/ads/102/convert' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'Authorization: Bearer USER_TOKEN' \
  --data '{
    "to_type": "normal"
  }'
```

**Expected Response:**
```json
{
  "success": false,
  "message": "No active package",
  "error_code": 403
}
```

---

## Test Suite 3: Edge Cases & Error Handling

### Step 3.1: Try to Grant Access to Non-User-Specific Package (Should FAIL)

```bash
# Try to grant user access to public package (ID 1)
curl -X POST 'http://localhost:8000/api/v1/admin/packages/1/grant-access' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'Authorization: Bearer ADMIN_TOKEN' \
  --data '{
    "user_ids": [10]
  }'
```

**Expected Response:**
```json
{
  "success": false,
  "message": "Package must have user_specific visibility type to grant user access",
  "errors": {
    "visibility_type": ["Current visibility type is: public"]
  },
  "error_code": 422
}
```

---

### Step 3.2: Try Role-Based Without Specifying Roles (Should FAIL)

```bash
curl -X POST 'http://localhost:8000/api/v1/admin/packages/1/visibility' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'Authorization: Bearer ADMIN_TOKEN' \
  --data '{
    "visibility_type": "role_based"
  }'
```

**Expected Response:**
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

---

### Step 3.3: Try Invalid Role in Allowed Roles (Should FAIL)

```bash
curl -X POST 'http://localhost:8000/api/v1/admin/packages/1/visibility' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'Authorization: Bearer ADMIN_TOKEN' \
  --data '{
    "visibility_type": "role_based",
    "allowed_roles": ["seller", "invalid_role", "admin"]
  }'
```

**Expected Response:**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "allowed_roles.1": ["The selected allowed_roles.1 is invalid."]
  },
  "error_code": 422
}
```

**Valid roles**: user, seller, showroom, marketer, admin

---

### Step 3.4: Try Invalid Ad Type in Conversion (Should FAIL)

```bash
curl -X POST 'http://localhost:8000/api/v1/ads/100/convert' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'Authorization: Bearer ADMIN_TOKEN' \
  --data '{
    "to_type": "invalid_type"
  }'
```

**Expected Response:**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "to_type": ["The selected to_type is invalid."]
  },
  "error_code": 422
}
```

**Valid types**: normal, unique, caishha, findit, auction

---

### Step 3.5: Non-Admin Tries to Manage Visibility (Should FAIL)

```bash
curl -X POST 'http://localhost:8000/api/v1/admin/packages/1/visibility' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'Authorization: Bearer USER_TOKEN' \
  --data '{
    "visibility_type": "public"
  }'
```

**Expected Response:**
```json
{
  "success": false,
  "message": "Unauthorized",
  "error_code": 403
}
```

---

## Summary of Test Results

### Package Visibility System - Expected Results:
- ✅ Public packages visible to ALL users (guest, authenticated)
- ✅ Role-based packages visible ONLY to users with allowed roles
- ✅ User-specific packages visible ONLY to granted users
- ✅ Admin can grant/revoke user-specific access
- ✅ Admin can list users with access
- ✅ Package list endpoint filters correctly based on visibility
- ✅ Invalid configurations rejected with proper error messages

### Enhanced Ad Type Conversion - Expected Results:
- ✅ Admin can convert to ANY ad type without restrictions
- ✅ Free users CAN convert IF package allows destination type
- ✅ Free users CANNOT convert if package denies
- ✅ Paid users can convert normally
- ✅ All 5 ad types supported (normal, unique, caishha, findit, auction)
- ✅ Appropriate error messages for denied conversions
- ✅ Users without active packages cannot convert

---

## Quick Verification SQL Queries

```sql
-- Check package visibility settings
SELECT id, name, price, visibility_type, allowed_roles, active
FROM packages
ORDER BY id;

-- Check user-specific package access
SELECT 
    p.id AS package_id,
    p.name AS package_name,
    u.id AS user_id,
    u.name AS user_name,
    u.email,
    pua.created_at AS granted_at
FROM package_user_access pua
JOIN packages p ON p.id = pua.package_id
JOIN users u ON u.id = pua.user_id
ORDER BY p.id, u.name;

-- Check ad type conversions
SELECT 
    atc.id,
    atc.ad_id,
    a.title,
    atc.from_type,
    atc.to_type,
    u.name AS user_name,
    u.role,
    p.name AS package_name,
    p.price AS package_price,
    atc.created_at
FROM ad_type_conversions atc
JOIN ads a ON a.id = atc.ad_id
JOIN users u ON u.id = atc.user_id
LEFT JOIN user_packages up ON up.user_id = u.id AND up.active = 1
LEFT JOIN packages p ON p.id = up.package_id
ORDER BY atc.created_at DESC
LIMIT 20;

-- Count conversions by user type (free vs paid)
SELECT 
    CASE WHEN p.price = 0 THEN 'Free User' ELSE 'Paid User' END AS user_type,
    atc.to_type,
    COUNT(*) AS conversion_count
FROM ad_type_conversions atc
JOIN users u ON u.id = atc.user_id
JOIN user_packages up ON up.user_id = u.id AND up.active = 1
JOIN packages p ON p.id = up.package_id
GROUP BY user_type, atc.to_type
ORDER BY user_type, conversion_count DESC;

-- Check audit logs for visibility changes
SELECT 
    al.action,
    al.model_type,
    al.model_id,
    p.name AS package_name,
    u.name AS performed_by,
    al.new_data,
    al.created_at
FROM audit_logs al
JOIN packages p ON p.id = al.model_id
JOIN users u ON u.id = al.user_id
WHERE al.model_type = 'Package'
AND al.action IN ('updated_visibility', 'granted_access', 'revoked_access')
ORDER BY al.created_at DESC
LIMIT 20;
```

---

## Troubleshooting

If tests fail, check:

1. **Migration Status**
   ```bash
   php artisan migrate:status
   ```

2. **Cache Clear**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   ```

3. **Database Connection**
   ```bash
   php artisan tinker
   DB::connection()->getPdo();
   ```

4. **Check Latest Errors**
   ```bash
   tail -f storage/logs/laravel.log
   ```

---

## Notes

- Replace `ADMIN_TOKEN`, `SELLER_TOKEN`, `USER_TOKEN` with actual tokens from login responses
- Replace user IDs (5, 10) with actual IDs from your database
- Replace ad IDs (100, 101, 102) with actual created ad IDs
- All examples use `http://localhost:8000` - adjust if your server runs on a different port
- Some responses are abbreviated for clarity - actual responses will include more fields
