# Phase 3 Testing Quick Start Guide

## Overview
This guide provides quick tests for Phase 3 features:
1. Package Visibility System (public, role-based, user-specific)
2. Enhanced Ad Type Conversion (admin bypass, free user conversion)

**Estimated Time**: 20 minutes

---

## Prerequisites

```powershell
# Set base URL
$baseUrl = "http://localhost:8000/api/v1"

# Login as admin
$adminLogin = Invoke-RestMethod -Uri "$baseUrl/auth/login" -Method POST -Body (@{
    email = "admin@example.com"
    password = "password"
} | ConvertTo-Json) -ContentType "application/json"
$adminToken = $adminLogin.data.token

# Login as regular user (seller role)
$sellerLogin = Invoke-RestMethod -Uri "$baseUrl/auth/login" -Method POST -Body (@{
    email = "seller@example.com"
    password = "password"
} | ConvertTo-Json) -ContentType "application/json"
$sellerToken = $sellerLogin.data.token

# Login as regular user (buyer role)
$buyerLogin = Invoke-RestMethod -Uri "$baseUrl/auth/login" -Method POST -Body (@{
    email = "buyer@example.com"
    password = "password"
} | ConvertTo-Json) -ContentType "application/json"
$buyerToken = $buyerLogin.data.token

# Headers
$adminHeaders = @{ "Authorization" = "Bearer $adminToken"; "Content-Type" = "application/json" }
$sellerHeaders = @{ "Authorization" = "Bearer $sellerToken"; "Content-Type" = "application/json" }
$buyerHeaders = @{ "Authorization" = "Bearer $buyerToken"; "Content-Type" = "application/json" }
```

---

## Test Suite 1: Package Visibility System (10 min)

### Test 1.1: Public Package (Visible to All)

```powershell
# Create a public package
$publicPackage = Invoke-RestMethod -Uri "$baseUrl/packages" -Method POST `
    -Headers $adminHeaders -Body (@{
        name = "Public Gold Package"
        description = "Available to everyone"
        price = 49.99
        duration_days = 30
        active = $true
        visibility_type = "public"
    } | ConvertTo-Json)

$packageId = $publicPackage.data.package.id
Write-Host "Created public package ID: $packageId"

# Verify all users can see it
Write-Host "`nTesting visibility..."

# Guest user (no auth)
$guestPackages = Invoke-RestMethod -Uri "$baseUrl/packages"
$visible = $guestPackages.data.data | Where-Object { $_.id -eq $packageId }
Write-Host "Guest can see: $(if ($visible) { 'YES ✓' } else { 'NO ✗' })"

# Seller user
$sellerPackages = Invoke-RestMethod -Uri "$baseUrl/packages" -Headers $sellerHeaders
$visible = $sellerPackages.data.data | Where-Object { $_.id -eq $packageId }
Write-Host "Seller can see: $(if ($visible) { 'YES ✓' } else { 'NO ✗' })"

# Buyer user
$buyerPackages = Invoke-RestMethod -Uri "$baseUrl/packages" -Headers $buyerHeaders
$visible = $buyerPackages.data.data | Where-Object { $_.id -eq $packageId }
Write-Host "Buyer can see: $(if ($visible) { 'YES ✓' } else { 'NO ✗' })"
```

**Expected Results:**
- ✓ Guest can see public package
- ✓ Seller can see public package
- ✓ Buyer can see public package

---

### Test 1.2: Role-Based Package (Sellers Only)

```powershell
# Create package
$rolePackage = Invoke-RestMethod -Uri "$baseUrl/packages" -Method POST `
    -Headers $adminHeaders -Body (@{
        name = "Seller-Only Package"
        description = "Exclusive to sellers"
        price = 99.99
        duration_days = 30
        active = $true
    } | ConvertTo-Json)

$rolePackageId = $rolePackage.data.package.id

# Set role-based visibility
Invoke-RestMethod -Uri "$baseUrl/admin/packages/$rolePackageId/visibility" -Method POST `
    -Headers $adminHeaders -Body (@{
        visibility_type = "role_based"
        allowed_roles = @("seller", "showroom")
    } | ConvertTo-Json)

Write-Host "`nSet role-based visibility (sellers, showrooms only)"

# Test visibility
Start-Sleep -Seconds 1

# Seller user (should see it)
$sellerPackages = Invoke-RestMethod -Uri "$baseUrl/packages" -Headers $sellerHeaders
$visible = $sellerPackages.data.data | Where-Object { $_.id -eq $rolePackageId }
Write-Host "Seller can see: $(if ($visible) { 'YES ✓' } else { 'NO ✗' })"

# Buyer user (should NOT see it)
$buyerPackages = Invoke-RestMethod -Uri "$baseUrl/packages" -Headers $buyerHeaders
$visible = $buyerPackages.data.data | Where-Object { $_.id -eq $rolePackageId }
Write-Host "Buyer can see: $(if ($visible) { 'NO ✓' } else { 'YES ✗ (ERROR)' })"

# Guest user (should NOT see it)
$guestPackages = Invoke-RestMethod -Uri "$baseUrl/packages"
$visible = $guestPackages.data.data | Where-Object { $_.id -eq $rolePackageId }
Write-Host "Guest can see: $(if ($visible) { 'NO ✓' } else { 'YES ✗ (ERROR)' })"
```

**Expected Results:**
- ✓ Seller CAN see role-based package
- ✓ Buyer CANNOT see role-based package
- ✓ Guest CANNOT see role-based package

---

### Test 1.3: User-Specific Package (VIP Users)

```powershell
# Get seller and buyer user IDs
$users = Invoke-RestMethod -Uri "$baseUrl/users" -Headers $adminHeaders
$sellerId = ($users.data.data | Where-Object { $_.email -eq "seller@example.com" }).id
$buyerId = ($users.data.data | Where-Object { $_.email -eq "buyer@example.com" }).id

# Create VIP package
$vipPackage = Invoke-RestMethod -Uri "$baseUrl/packages" -Method POST `
    -Headers $adminHeaders -Body (@{
        name = "VIP Exclusive Package"
        description = "By invitation only"
        price = 299.99
        duration_days = 90
        active = $true
    } | ConvertTo-Json)

$vipPackageId = $vipPackage.data.package.id

# Set user-specific visibility and grant access to seller only
Invoke-RestMethod -Uri "$baseUrl/admin/packages/$vipPackageId/visibility" -Method POST `
    -Headers $adminHeaders -Body (@{
        visibility_type = "user_specific"
        user_ids = @($sellerId)
    } | ConvertTo-Json)

Write-Host "`nCreated VIP package and granted access to seller (ID: $sellerId)"

# Test visibility
Start-Sleep -Seconds 1

# Seller (has access)
$sellerPackages = Invoke-RestMethod -Uri "$baseUrl/packages" -Headers $sellerHeaders
$visible = $sellerPackages.data.data | Where-Object { $_.id -eq $vipPackageId }
Write-Host "Seller can see VIP package: $(if ($visible) { 'YES ✓' } else { 'NO ✗' })"

# Buyer (no access)
$buyerPackages = Invoke-RestMethod -Uri "$baseUrl/packages" -Headers $buyerHeaders
$visible = $buyerPackages.data.data | Where-Object { $_.id -eq $vipPackageId }
Write-Host "Buyer can see VIP package: $(if ($visible) { 'YES ✗ (ERROR)' } else { 'NO ✓' })"

# Grant access to buyer
Write-Host "`nGranting VIP access to buyer..."
Invoke-RestMethod -Uri "$baseUrl/admin/packages/$vipPackageId/grant-access" -Method POST `
    -Headers $adminHeaders -Body (@{
        user_ids = @($buyerId)
    } | ConvertTo-Json)

Start-Sleep -Seconds 1

# Buyer should now see it
$buyerPackages = Invoke-RestMethod -Uri "$baseUrl/packages" -Headers $buyerHeaders
$visible = $buyerPackages.data.data | Where-Object { $_.id -eq $vipPackageId }
Write-Host "Buyer can now see VIP package: $(if ($visible) { 'YES ✓' } else { 'NO ✗' })"

# List users with access
$accessList = Invoke-RestMethod -Uri "$baseUrl/admin/packages/$vipPackageId/users-with-access" `
    -Headers $adminHeaders
Write-Host "`nUsers with VIP access: $($accessList.data.total_count)"
$accessList.data.users | ForEach-Object { Write-Host "  - $($_.name) ($($_.email))" }

# Revoke access from seller
Write-Host "`nRevoking VIP access from seller..."
Invoke-RestMethod -Uri "$baseUrl/admin/packages/$vipPackageId/revoke-access" -Method POST `
    -Headers $adminHeaders -Body (@{
        user_ids = @($sellerId)
    } | ConvertTo-Json)

Start-Sleep -Seconds 1

# Seller should no longer see it
$sellerPackages = Invoke-RestMethod -Uri "$baseUrl/packages" -Headers $sellerHeaders
$visible = $sellerPackages.data.data | Where-Object { $_.id -eq $vipPackageId }
Write-Host "Seller can see VIP package after revoke: $(if ($visible) { 'YES ✗ (ERROR)' } else { 'NO ✓' })"
```

**Expected Results:**
- ✓ Initially only seller can see VIP package
- ✓ After granting access, buyer can see it
- ✓ Admin can list users with access
- ✓ After revoking, seller cannot see it anymore

---

## Test Suite 2: Enhanced Ad Type Conversion (10 min)

### Test 2.1: Admin Unrestricted Conversion

```powershell
# Create a normal ad as admin
$ad = Invoke-RestMethod -Uri "$baseUrl/normal-ads" -Method POST `
    -Headers $adminHeaders -Body (@{
        title = "Test Car for Conversion"
        description = "Testing admin conversion"
        price = 15000
        category_id = 1
        brand_id = 1
        model_id = 1
        year = 2020
        condition = "used"
        status = "active"
    } | ConvertTo-Json)

$adId = $ad.data.id
Write-Host "Created normal ad ID: $adId"

# Admin converts to unique (no restrictions)
Write-Host "`nAdmin converting to unique ad..."
$conversion = Invoke-RestMethod -Uri "$baseUrl/ads/$adId/convert" -Method POST `
    -Headers $adminHeaders -Body (@{
        to_type = "unique"
    } | ConvertTo-Json)

Write-Host "Conversion status: $(if ($conversion.success) { 'SUCCESS ✓' } else { 'FAILED ✗' })"
Write-Host "New ad type: $($conversion.data.ad.type)"

# Convert to caishha
Write-Host "`nAdmin converting to caishha ad..."
$conversion2 = Invoke-RestMethod -Uri "$baseUrl/ads/$adId/convert" -Method POST `
    -Headers $adminHeaders -Body (@{
        to_type = "caishha"
    } | ConvertTo-Json)

Write-Host "Conversion status: $(if ($conversion2.success) { 'SUCCESS ✓' } else { 'FAILED ✗' })"
Write-Host "New ad type: $($conversion2.data.ad.type)"

# Convert to findit
Write-Host "`nAdmin converting to findit ad..."
$conversion3 = Invoke-RestMethod -Uri "$baseUrl/ads/$adId/convert" -Method POST `
    -Headers $adminHeaders -Body (@{
        to_type = "findit"
    } | ConvertTo-Json)

Write-Host "Conversion status: $(if ($conversion3.success) { 'SUCCESS ✓' } else { 'FAILED ✗' })"
Write-Host "New ad type: $($conversion3.data.ad.type)"
```

**Expected Results:**
- ✓ Admin can convert to unique (success)
- ✓ Admin can convert to caishha (success)
- ✓ Admin can convert to findit (success)
- ✓ No package restrictions applied to admin

---

### Test 2.2: Free User Conversion (Package Allows)

```powershell
# Check seller's active package
$myPackages = Invoke-RestMethod -Uri "$baseUrl/packages/my-packages" -Headers $sellerHeaders
$activePackage = $myPackages.data.data | Where-Object { $_.subscription.active -eq $true } | Select-Object -First 1

Write-Host "Seller's active package: $($activePackage.name) (Price: $($activePackage.price))"
Write-Host "Package allows unique ads: $(if ($activePackage.features -contains 'unique_ads') { 'YES' } else { 'NO' })"

# If free package allows unique ads, test conversion
if ($activePackage.price -eq 0 -and $activePackage.features -contains 'unique_ads') {
    # Create normal ad as seller
    $sellerAd = Invoke-RestMethod -Uri "$baseUrl/normal-ads" -Method POST `
        -Headers $sellerHeaders -Body (@{
            title = "Seller Test Car"
            description = "Testing free user conversion"
            price = 10000
            category_id = 1
            brand_id = 1
            model_id = 1
            year = 2019
            condition = "used"
            status = "active"
        } | ConvertTo-Json)

    $sellerAdId = $sellerAd.data.id
    Write-Host "`nCreated seller ad ID: $sellerAdId"

    # Attempt conversion to unique
    Write-Host "Free user attempting conversion to unique..."
    try {
        $sellerConversion = Invoke-RestMethod -Uri "$baseUrl/ads/$sellerAdId/convert" -Method POST `
            -Headers $sellerHeaders -Body (@{
                to_type = "unique"
            } | ConvertTo-Json)

        Write-Host "Conversion status: $(if ($sellerConversion.success) { 'SUCCESS ✓' } else { 'FAILED ✗' })"
        Write-Host "New ad type: $($sellerConversion.data.ad.type)"
    } catch {
        $errorResponse = $_.Exception.Response
        Write-Host "Conversion FAILED ✗"
        Write-Host "Error: $($_.ErrorDetails.Message)"
    }
} else {
    Write-Host "`nSkipping free user conversion test (package does not allow or is not free)"
}
```

**Expected Results:**
- ✓ If free package allows unique ads: Conversion succeeds
- ✓ If free package doesn't allow: Conversion fails with package error

---

### Test 2.3: Free User Conversion (Package Denies)

```powershell
# Create a FREE package that does NOT allow unique ads
$basicPackage = Invoke-RestMethod -Uri "$baseUrl/packages" -Method POST `
    -Headers $adminHeaders -Body (@{
        name = "Basic Free Package"
        description = "Limited features"
        price = 0
        duration_days = 30
        active = $true
        features = @("normal_ads_only")
    } | ConvertTo-Json)

$basicPackageId = $basicPackage.data.package.id
Write-Host "Created basic free package (no unique ads allowed)"

# Assign to buyer
Invoke-RestMethod -Uri "$baseUrl/packages/$basicPackageId/assign" -Method POST `
    -Headers $adminHeaders -Body (@{
        user_id = $buyerId
        active = $true
    } | ConvertTo-Json)

Write-Host "Assigned basic package to buyer"

# Create ad as buyer
$buyerAd = Invoke-RestMethod -Uri "$baseUrl/normal-ads" -Method POST `
    -Headers $buyerHeaders -Body (@{
        title = "Buyer Test Car"
        description = "Testing conversion denial"
        price = 8000
        category_id = 1
        brand_id = 1
        model_id = 1
        year = 2018
        condition = "used"
        status = "active"
    } | ConvertTo-Json)

$buyerAdId = $buyerAd.data.id
Write-Host "Created buyer ad ID: $buyerAdId"

# Attempt conversion (should fail)
Write-Host "`nBuyer attempting conversion to unique (should fail)..."
try {
    $buyerConversion = Invoke-RestMethod -Uri "$baseUrl/ads/$buyerAdId/convert" -Method POST `
        -Headers $buyerHeaders -Body (@{
            to_type = "unique"
        } | ConvertTo-Json)

    Write-Host "Conversion status: UNEXPECTED SUCCESS ✗"
} catch {
    $statusCode = $_.Exception.Response.StatusCode.value__
    Write-Host "Conversion blocked: $(if ($statusCode -eq 403) { 'CORRECT ✓' } else { 'WRONG STATUS ✗' })"
    Write-Host "Error message: $($_.ErrorDetails.Message)"
}
```

**Expected Results:**
- ✓ Buyer with free package (no unique ads) cannot convert
- ✓ Error status: 403 Forbidden
- ✓ Error message mentions package restriction

---

## Test Suite 3: Regression Tests (Optional, 5 min)

### Test 3.1: Paid User Conversion Still Works

```powershell
# Assign paid package with unique ads to seller
$paidPackage = Invoke-RestMethod -Uri "$baseUrl/packages" -Method POST `
    -Headers $adminHeaders -Body (@{
        name = "Paid Premium Package"
        description = "All features"
        price = 99.99
        duration_days = 30
        active = $true
        features = @("unique_ads", "caishha_ads", "featured_listing")
    } | ConvertTo-Json)

$paidPackageId = $paidPackage.data.package.id

Invoke-RestMethod -Uri "$baseUrl/packages/$paidPackageId/assign" -Method POST `
    -Headers $adminHeaders -Body (@{
        user_id = $sellerId
        active = $true
    } | ConvertTo-Json)

Write-Host "Assigned paid package to seller"

# Create ad and convert
$paidUserAd = Invoke-RestMethod -Uri "$baseUrl/normal-ads" -Method POST `
    -Headers $sellerHeaders -Body (@{
        title = "Paid User Test Car"
        description = "Testing paid conversion"
        price = 20000
        category_id = 1
        brand_id = 1
        model_id = 1
        year = 2021
        condition = "new"
        status = "active"
    } | ConvertTo-Json)

$paidUserAdId = $paidUserAd.data.id

Write-Host "`nPaid user converting to unique..."
$paidConversion = Invoke-RestMethod -Uri "$baseUrl/ads/$paidUserAdId/convert" -Method POST `
    -Headers $sellerHeaders -Body (@{
        to_type = "unique"
    } | ConvertTo-Json)

Write-Host "Conversion status: $(if ($paidConversion.success) { 'SUCCESS ✓' } else { 'FAILED ✗' })"
```

**Expected Results:**
- ✓ Paid users can still convert ads normally
- ✓ No breaking changes to existing functionality

---

## Verification Queries

### Check Package Visibility in Database

```sql
-- List all packages with visibility settings
SELECT id, name, price, visibility_type, allowed_roles
FROM packages
ORDER BY visibility_type, id;

-- Count packages by visibility type
SELECT visibility_type, COUNT(*) as count
FROM packages
GROUP BY visibility_type;

-- List user-specific package grants
SELECT p.name AS package_name, u.name AS user_name, u.email, pua.created_at
FROM package_user_access pua
JOIN packages p ON p.id = pua.package_id
JOIN users u ON u.id = pua.user_id
ORDER BY p.id, u.name;
```

### Check Ad Type Conversions

```sql
-- Recent conversions by free users
SELECT 
    atc.ad_id,
    a.title,
    atc.from_type,
    atc.to_type,
    u.name AS user_name,
    p.name AS package_name,
    p.price AS package_price,
    atc.created_at
FROM ad_type_conversions atc
JOIN ads a ON a.id = atc.ad_id
JOIN users u ON u.id = atc.user_id
JOIN user_packages up ON up.user_id = u.id AND up.active = 1
JOIN packages p ON p.id = up.package_id
WHERE p.price = 0
ORDER BY atc.created_at DESC
LIMIT 10;

-- Conversion stats by user type (free vs paid)
SELECT 
    CASE WHEN p.price = 0 THEN 'Free' ELSE 'Paid' END AS user_type,
    atc.to_type,
    COUNT(*) AS conversion_count
FROM ad_type_conversions atc
JOIN users u ON u.id = atc.user_id
JOIN user_packages up ON up.user_id = u.id AND up.active = 1
JOIN packages p ON p.id = up.package_id
GROUP BY user_type, atc.to_type
ORDER BY user_type, conversion_count DESC;
```

---

## Summary Checklist

### Package Visibility System
- [ ] Public packages visible to all users (guest, authenticated)
- [ ] Role-based packages visible only to allowed roles
- [ ] User-specific packages visible only to granted users
- [ ] Admin can grant/revoke user-specific access
- [ ] Admin can list users with access
- [ ] Package list endpoint filters correctly based on visibility

### Enhanced Ad Type Conversion
- [ ] Admin can convert to any ad type without restrictions
- [ ] Free users can convert IF package allows destination type
- [ ] Free users CANNOT convert if package denies
- [ ] Paid users can still convert as before
- [ ] All 5 ad types supported (normal, unique, caishha, findit, auction)
- [ ] Appropriate error messages for denied conversions

### Audit & Logging
- [ ] Visibility changes logged in audit_logs table
- [ ] Ad type conversions logged correctly
- [ ] User access grants/revokes logged

---

## Quick Rollback (If Needed)

```bash
# Rollback the migration
php artisan migrate:rollback --step=1

# This will remove:
# - visibility_type column from packages
# - allowed_roles column from packages
# - package_user_access table
```

---

## Support

If tests fail, check:
1. Migration ran successfully: `php artisan migrate:status`
2. Database connection: `php artisan tinker` → `DB::connection()->getPdo()`
3. Cache cleared: `php artisan cache:clear`
4. Config cached: `php artisan config:cache`
5. Routes cached: `php artisan route:cache`

For issues, refer to:
- [Phase 3 Implementation Docs](UNIQUE_ADS_SYSTEM_PHASE_3_IMPLEMENTATION.md)
- [API Documentation](API_COMPLETE_DOCUMENTATION.md)
- Audit logs table for detailed error tracking
