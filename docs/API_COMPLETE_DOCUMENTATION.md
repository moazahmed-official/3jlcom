# Complete API Documentation (v1)

**Version:** 1.0.0  
**Last Updated:** February 11, 2026  
**Base URL:** `/api/v1`  

This document contains **ALL** API endpoints in the system. For standardization details and migration guide, see [API_STANDARDIZED_CONTRACT.md](./API_STANDARDIZED_CONTRACT.md).

---

## Table of Contents

1. [Authentication](#authentication)
2. [Users](#users)
3. [Roles](#roles)
4. [Seller Verification](#seller-verification)
5. [Brands & Models](#brands--models)
6. [Media](#media)
7. [Normal Ads](#normal-ads)
8. [Unique Ads](#unique-ads)
9. [Caishha Ads](#caishha-ads)
10. [Caishha Settings](#caishha-settings)
11. [Auction Ads](#auction-ads)
12. [FindIt Ads](#findit-ads)
13. [Reviews](#reviews)
14. [Reports](#reports)
15. [Packages](#packages)
16. [Package Visibility Management](#package-visibility-management)
17. [Package Requests](#package-requests)
18. [Unique Ad Type Definitions](#unique-ad-type-definitions)
19. [Ad Upgrade Requests](#ad-upgrade-requests)
20. [Ad Type Conversion](#ad-type-conversion)
21. [Feature Actions & Usage](#feature-actions--usage)
22. [Notifications](#notifications)
23. [Favorites](#favorites)
24. [Saved Searches](#saved-searches)
25. [Blog](#blog)
26. [Specifications](#specifications)
27. [Categories](#categories)
28. [Seller Stats](#seller-stats)
29. [Sliders](#sliders)
30. [Admin Stats](#admin-stats)
31. [Audit Logs](#audit-logs)
32. [Page Content](#page-content)
33. [Company Settings](#company-settings)

---

## Response Envelope Standards

All responses follow consistent JSON envelopes:

**Success:**
```json
{
  "status": "success",
  "message": "Operation completed successfully",
  "data": { /* resource or collection */ }
}
```

**Paginated:**
```json
{
  "status": "success",
  "message": "Data retrieved successfully",
  "data": {
    "items": [ /* array */ ],
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total": 100,
      "last_page": 7,
      "from": 1,
      "to": 15
    }
  }
}
```

**Error:**
```json
{
  "status": "error",
  "code": 400,
  "message": "Error description",
  "errors": {
    "field_name": ["Error message"]
  }
}
```

---

## Authentication

### Auth methods

- Primary: API token (Sanctum personal access tokens) via `Authorization: Bearer <token>` header.
- Admin SPA: backend issues an HttpOnly cookie named `admin_token` when login requests originate from the admin frontend.
  - **Local dev:** Cookie uses `Secure=false` to work with `http://localhost:5173`. No domain restriction.
  - **Production:** Cookie uses `Secure=true`, scoped to parent domain for `admin.example.com`.
- The API accepts authentication via either the `Authorization` header or the `admin_token` cookie.

Frontend admin apps should call the API with credentials enabled (`axios` `withCredentials: true`) so the browser sends the admin cookie automatically. Do NOT store admin tokens in `localStorage`.

CORS notes:
- In production the API will return the exact admin origin (not `*`) and `Access-Control-Allow-Credentials: true` so cookies can be sent. Configure `ADMIN_ORIGIN` in production to the admin frontend origin (e.g., `https://admin.example.com`).
- In local development the API allows `http://localhost:5173` by default for the admin frontend. We recommend using a frontend proxy for local dev to simplify CORS.

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| POST | `/auth/login` | Authenticate user and receive token or cookie | No | No |
| POST | `/auth/register` | Register new user | No | No |
| PUT | `/auth/verify` | Verify OTP and complete registration | No | No |
| POST | `/auth/password/reset-request` | Request password reset OTP | No | No |
| PUT | `/auth/password/reset` | Reset password with OTP | No | No |
| POST | `/auth/logout` | Logout and invalidate token (supports cookie or header) | Yes | Yes |

---

## Users

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| GET | `/users` | List all users | Yes | Yes |
| POST | `/users` | Create new user | Yes | Yes |
| GET | `/users/{user}` | Get user details | Yes | No* |
| PUT | `/users/{user}` | Update user | Yes | No* |
| DELETE | `/users/{user}` | Delete user | Yes | Yes |
| POST | `/users/{user}/verify` | Verify user account (admin) | Yes | Yes |

*Self or admin

---

## Roles

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| GET | `/roles` | List all roles | Yes | Yes |
| POST | `/roles` | Create new role | Yes | Yes |
| GET | `/roles/{role}` | Get role details | Yes | Yes |
| PUT | `/roles/{role}` | Update role | Yes | Yes |
| DELETE | `/roles/{role}` | Delete role | Yes | Yes |
| POST | `/users/{user}/roles` | Assign roles to user | Yes | Yes |
| GET | `/users/{user}/roles` | Get user's roles | Yes | Yes |

---

## Seller Verification

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| POST | `/seller-verification` | Submit verification request | Yes | No |
| GET | `/seller-verification` | Get own verification status | Yes | No |
| GET | `/seller-verification/admin` | List all verification requests | Yes | Yes |
| PUT | `/seller-verification/{verificationRequest}` | Review verification (approve/reject) | Yes | Yes |

---

## Brands & Models

### Brands

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| GET | `/brands` | List all brands | No | No |
| POST | `/brands` | Create new brand | Yes | Yes |
| PUT | `/brands/{brand}` | Update brand | Yes | Yes |
| DELETE | `/brands/{brand}` | Delete brand | Yes | Yes |
| GET | `/brands/{brand}/models` | Get brand models | No | No |

### Models

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| POST | `/brands/{brand}/models` | Create new model | Yes | Yes |
| PUT | `/brands/{brand}/models/{model}` | Update model | Yes | Yes |
| DELETE | `/brands/{brand}/models/{model}` | Delete model | Yes | Yes |

---

## Media

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| GET | `/media` | List user's media | Yes | No |
| POST | `/media` | Upload media file | Yes | No |
| GET | `/media/{media}` | Get media details | Yes | No* |
| PATCH | `/media/{media}` | Update media metadata | Yes | No* |
| DELETE | `/media/{media}` | Delete media | Yes | No* |

*Owner or admin

---

## Normal Ads

### Public Routes

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| GET | `/normal-ads` | List published normal ads | No | No |
| GET | `/normal-ads/{ad}` | Get ad details | No | No |
| GET | `/users/{user}/normal-ads` | List user's published ads | No | No |

### Protected Routes

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| GET | `/normal-ads/my-ads` | List own ads (all statuses) | Yes | No |
| GET | `/normal-ads/admin` | List all ads (admin) | Yes | Yes |
| GET | `/normal-ads/stats` | Global ad statistics | Yes | Yes |
| GET | `/normal-ads/favorites` | Get own favorite ads | Yes | No |
| POST | `/normal-ads` | Create new ad | Yes | No |
| PUT | `/normal-ads/{ad}` | Update ad | Yes | No* |
| DELETE | `/normal-ads/{ad}` | Delete ad | Yes | No* |

*Owner or admin

### Lifecycle Actions

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| POST | `/normal-ads/{ad}/actions/republish` | Republish expired ad | Yes | No* |
| POST | `/normal-ads/{ad}/actions/publish` | Publish draft ad | Yes | No* |
| POST | `/normal-ads/{ad}/actions/unpublish` | Unpublish active ad | Yes | No* |
| POST | `/normal-ads/{ad}/actions/expire` | Expire active ad | Yes | No* |
| POST | `/normal-ads/{ad}/actions/archive` | Archive ad | Yes | No* |
| POST | `/normal-ads/{ad}/actions/restore` | Restore archived ad | Yes | No* |

*Owner or admin

### Interactions

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| GET | `/normal-ads/{ad}/stats` | Get ad statistics | Yes | No* |
| POST | `/normal-ads/{ad}/favorite` | Add to favorites | Yes | No |
| DELETE | `/normal-ads/{ad}/favorite` | Remove from favorites | Yes | No |
| POST | `/normal-ads/{ad}/contact` | Contact seller | Yes | No |
| POST | `/normal-ads/{ad}/actions/convert-to-unique` | Convert to unique ad | Yes | No* |

*Owner or admin

### Bulk Actions

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| POST | `/normal-ads/actions/bulk` | Bulk operations (admin) | Yes | Yes |

---

## Unique Ads

**Same structure as Normal Ads** with additional endpoints:

### Additional Actions

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| POST | `/unique-ads/{ad}/actions/feature` | Mark as featured | Yes | Yes |
| DELETE | `/unique-ads/{ad}/actions/feature` | Remove featured status | Yes | Yes |
| POST | `/unique-ads/{ad}/actions/verify` | Request verification | Yes | No* |
| POST | `/unique-ads/{ad}/actions/approve-verification` | Approve verification | Yes | Yes |
| POST | `/unique-ads/{ad}/actions/reject-verification` | Reject verification | Yes | Yes |
| POST | `/unique-ads/{ad}/actions/auto-republish` | Toggle auto-republish | Yes | No* |
| POST | `/unique-ads/{ad}/actions/convert-to-normal` | Convert to normal ad | Yes | No* |

*Owner

---

## Caishha Ads

Caishha ads are "request for offers" where dealers/buyers submit price offers.

### Public Routes

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| GET | `/caishha-ads` | List published Caishha ads | No | No |
| GET | `/caishha-ads/{ad}` | Get ad details | No | No |

### Protected Routes

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| GET | `/caishha-ads/my-ads` | List own ads | Yes | No |
| GET | `/caishha-ads/admin` | List all ads | Yes | Yes |
| GET | `/caishha-ads/stats` | Global statistics | Yes | Yes |
| POST | `/caishha-ads` | Create new ad | Yes | No |
| PUT | `/caishha-ads/{ad}` | Update ad | Yes | No* |
| DELETE | `/caishha-ads/{ad}` | Delete ad | Yes | No* |

*Owner or admin

### Lifecycle Actions

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| POST | `/caishha-ads/{ad}/actions/publish` | Publish draft | Yes | No* |
| POST | `/caishha-ads/{ad}/actions/unpublish` | Unpublish | Yes | No* |
| POST | `/caishha-ads/{ad}/actions/expire` | Expire | Yes | No* |
| POST | `/caishha-ads/{ad}/actions/archive` | Archive | Yes | No* |
| POST | `/caishha-ads/{ad}/actions/restore` | Restore | Yes | No* |

*Owner or admin

### Offers Management

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| POST | `/caishha-ads/{ad}/offers` | Submit offer on ad | Yes | No |
| GET | `/caishha-ads/{ad}/offers` | List offers on ad | Yes | No** |
| POST | `/caishha-ads/{ad}/offers/{offer}/accept` | Accept offer | Yes | No** |
| POST | `/caishha-ads/{ad}/offers/{offer}/reject` | Reject offer | Yes | No** |
| GET | `/caishha-offers/my-offers` | Get own submitted offers | Yes | No |
| GET | `/caishha-offers/{offer}` | Get offer details | Yes | No*** |
| PUT | `/caishha-offers/{offer}` | Update own offer | Yes | No*** |
| DELETE | `/caishha-offers/{offer}` | Withdraw offer | Yes | No*** |

**Owner or admin  
***Owner, ad owner, or admin

### Bulk Actions

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| POST | `/caishha-ads/actions/bulk` | Bulk operations | Yes | Yes |

---

## Caishha Settings

Configure dealer window duration and visibility rules.

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| GET | `/caishha-settings` | Get all settings | Yes | Yes |
| PUT | `/caishha-settings` | Update multiple settings | Yes | Yes |
| PUT | `/caishha-settings/{key}` | Update single setting | Yes | Yes |
| GET | `/caishha-settings/presets` | Get configuration presets | Yes | Yes |

---

## Auction Ads

### Public Routes

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| GET | `/auction-ads` | List published auctions | No | No |
| GET | `/auction-ads/{ad}` | Get auction details | No | No |
| GET | `/users/{user}/auction-ads` | List user's published auctions | No | No |

### Protected Routes

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| GET | `/auction-ads/my-ads` | List own auctions | Yes | No |
| GET | `/auction-ads/admin` | List all auctions | Yes | Yes |
| GET | `/auction-ads/stats` | Global statistics | Yes | Yes |
| POST | `/auction-ads` | Create new auction | Yes | No |
| PUT | `/auction-ads/{ad}` | Update auction | Yes | No* |
| DELETE | `/auction-ads/{ad}` | Delete auction | Yes | No* |

*Owner or admin

### Lifecycle Actions

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| POST | `/auction-ads/{ad}/actions/publish` | Publish auction | Yes | No* |
| POST | `/auction-ads/{ad}/actions/close` | Close auction | Yes | No* |
| POST | `/auction-ads/{ad}/actions/cancel` | Cancel auction | Yes | No* |

*Owner or admin

### Bidding

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| POST | `/auction-ads/{ad}/bids` | Place a bid | Yes | No |
| GET | `/auction-ads/{ad}/bids` | List all bids on auction | Yes | No** |
| GET | `/auction-ads/{ad}/bids/{bid}` | Get bid details | Yes | No*** |
| DELETE | `/auction-ads/{ad}/bids/{bid}` | Withdraw own bid | Yes | No*** |
| GET | `/auction-bids/my-bids` | Get all own bids | Yes | No |

**Owner, admin, or moderator  
***Owner or admin

---

## FindIt Ads

Private search requests where users specify what vehicles they're looking for.

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| GET | `/findit-ads/my-requests` | List own FindIt requests | Yes | No |
| GET | `/findit-ads/admin` | List all requests | Yes | Yes |
| GET | `/findit-ads/stats` | Get user statistics | Yes | No |
| POST | `/findit-ads` | Create FindIt request | Yes | No |
| GET | `/findit-ads/{findit_ad}` | Get request details | Yes | No* |
| PUT | `/findit-ads/{findit_ad}` | Update request | Yes | No* |
| DELETE | `/findit-ads/{findit_ad}` | Delete request | Yes | No* |

*Owner or admin

### Lifecycle Actions

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| POST | `/findit-ads/{findit_ad}/activate` | Activate draft request | Yes | No* |
| POST | `/findit-ads/{findit_ad}/close` | Close active request | Yes | No* |
| POST | `/findit-ads/{findit_ad}/extend` | Extend expiration date | Yes | No* |
| POST | `/findit-ads/{findit_ad}/reactivate` | Reactivate closed/expired | Yes | No* |

*Owner

### Matches

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| GET | `/findit-ads/{findit_ad}/matches` | List matching ads | Yes | No* |
| GET | `/findit-ads/{findit_ad}/matches/{match}` | Get match details | Yes | No* |
| POST | `/findit-ads/{findit_ad}/matches/{match}/dismiss` | Dismiss match | Yes | No* |
| POST | `/findit-ads/{findit_ad}/matches/{match}/restore` | Restore dismissed match | Yes | No* |
| POST | `/findit-ads/{findit_ad}/refresh-matches` | Refresh matches | Yes | No* |
| GET | `/findit-ads/{findit_ad}/similar` | Get similar ads | Yes | No* |

*Owner or admin

### Bulk Actions

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| POST | `/findit-ads/actions/bulk` | Bulk operations | Yes | Yes |

---

## Reviews

### Public Routes

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| GET | `/reviews` | List all reviews | No | No |
| GET | `/reviews/{review}` | Get review details | No | No |
| GET | `/ads/{ad}/reviews` | List reviews for ad | No | No |
| GET | `/users/{user}/reviews` | List reviews for user | No | No |

### Protected Routes

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| POST | `/reviews` | Create review (rate limited) | Yes | No |
| GET | `/reviews/my-reviews` | List own reviews | Yes | No |
| PUT | `/reviews/{review}` | Update review | Yes | No* |
| DELETE | `/reviews/{review}` | Delete review | Yes | No* |

*Owner or admin

**Rate Limit:** `throttle:review` middleware applies to POST /reviews

---

## Reports

### Protected Routes

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| POST | `/reports` | Create report (rate limited) | Yes | No |
| GET | `/reports/my-reports` | List own reports | Yes | No |
| GET | `/reports/{report}` | Get report details | Yes | No** |

**Owner, assigned moderator, or admin

### Admin Routes

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| GET | `/reports/admin/index` | List all reports | Yes | Yes |
| POST | `/reports/{report}/assign` | Assign to moderator | Yes | Yes |
| PUT | `/reports/{report}/status` | Update status | Yes | Yes* |
| POST | `/reports/{report}/actions/resolve` | Mark as resolved | Yes | Yes* |
| POST | `/reports/{report}/actions/close` | Close report | Yes | Yes* |
| DELETE | `/reports/{report}` | Delete report | Yes | Yes |

*Admin or assigned moderator

**Rate Limit:** `throttle:report` middleware applies to POST /reports

---

## Packages

### Public Routes

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| GET | `/packages` | List active packages | No | No |
| GET | `/packages/{package}` | Get package details | No | No |

### User Routes

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| GET | `/packages/my-packages` | Get own packages | Yes | No |
| GET | `/packages/my-features` | Get own package features | Yes | No |
| POST | `/packages/check-capability` | Check if can perform action | Yes | No |
| GET | `/users/{user}/packages` | Get user's packages | Yes | No** |

**Self or admin

### Admin Routes

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| GET | `/packages/stats` | Package statistics | Yes | Yes |
| POST | `/packages` | Create package | Yes | Yes |
| PUT | `/packages/{package}` | Update package | Yes | Yes |
| DELETE | `/packages/{package}` | Delete package | Yes | Yes |
| POST | `/packages/{package}/assign` | Assign package to user | Yes | Yes |

### Package Features

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| GET | `/packages/{package}/features` | Get package features | Yes | No |
| POST | `/packages/{package}/features` | Create features | Yes | Yes |
| PUT | `/packages/{package}/features` | Update features | Yes | Yes |
| DELETE | `/packages/{package}/features` | Delete features | Yes | Yes |

**Package Feature Structure (with Credits - Phase 2):**

Each package feature can have credit limits:

```json
{
  "feature_slug": "ai_video_generation",
  "feature_name": "AI Video Generation",
  "description": "Generate professional AI videos for your ads",
  "is_actionable": true,
  "credits_total": 10,
  "credits_unlimited": false
}
```

**Feature Types:**
- **Actionable Features**: Require user action and consume credits (e.g., `ai_video_generation`, `auto_background_removal`)
- **Passive Features**: Always-on features with no credit consumption (e.g., `priority_listing`, `featured_badge`)

**Credit System:**
- `credits_total`: Number of times feature can be used (null = unlimited)
- `credits_unlimited`: Boolean flag for unlimited usage
- Users track usage via `/features/my-credits` endpoint
- Credit consumption logged in `feature_usage_logs` table

**Common Actionable Features:**
- `ai_video_generation`: Generate AI videos
- `auto_background_removal`: Remove image backgrounds
- `image_enhancement`: Enhance image quality
- `virtual_tour_creation`: Create 360° virtual tours
- `social_media_posts`: Auto-generate social posts

**Common Passive Features:**
- `priority_listing`: Higher placement in search
- `featured_badge`: Display featured badge
- `analytics_access`: View detailed analytics
- `unlimited_images`: No image upload limits

### User Package Management

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| PUT | `/user-packages/{userPackage}` | Update user subscription | Yes | Yes |
| DELETE | `/user-packages/{userPackage}` | Remove subscription | Yes | Yes |

---

## Package Visibility Management

Control package visibility (public, role-based, user-specific) for targeted offerings.

### Admin Routes

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| GET | `/admin/packages/{package}/visibility` | Get visibility settings | Yes | Yes |
| POST | `/admin/packages/{package}/visibility` | Update visibility settings | Yes | Yes |
| POST | `/admin/packages/{package}/grant-access` | Grant user-specific access | Yes | Yes |
| POST | `/admin/packages/{package}/revoke-access` | Revoke user access | Yes | Yes |
| GET | `/admin/packages/{package}/users-with-access` | List users with access | Yes | Yes |

### Visibility Types

- **public**: Visible to all users (default)
- **role_based**: Visible only to specific roles (seller, showroom, marketer, admin)
- **user_specific**: Visible only to explicitly granted users (VIP packages)

### Get Visibility Settings

**GET** `/admin/packages/{package}/visibility`

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

### Update Visibility Settings

**POST** `/admin/packages/{package}/visibility`

**Request Body:**
```json
{
  "visibility_type": "role_based",
  "allowed_roles": ["seller", "showroom"],
  "user_ids": []  // Optional: for user_specific visibility
}
```

**Validation:**
- `visibility_type`: required, one of: public, role_based, user_specific
- `allowed_roles`: required if visibility_type = role_based
- `allowed_roles.*`: one of: user, seller, showroom, marketer, admin
- `user_ids`: optional array for user_specific visibility

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

### Grant User-Specific Access

**POST** `/admin/packages/{package}/grant-access`

**Request Body:**
```json
{
  "user_ids": [123, 456, 789]
}
```

**Requirements:**
- Package must have `visibility_type = 'user_specific'`
- All user IDs must exist

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

### Revoke User Access

**POST** `/admin/packages/{package}/revoke-access`

**Request Body:**
```json
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

### List Users With Access

**GET** `/admin/packages/{package}/users-with-access`

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

### Package List Visibility Filtering

**GET** `/packages` now filters by user visibility:

- **Guest users**: Only see public packages
- **Authenticated users**: See public + role-based (if role matches) + user-specific (if granted)
- **Admins**: See all packages + can filter by `?visibility_type=public|role_based|user_specific`

**Package Resource Updates:**
```json
{
  "id": 5,
  "name": "Premium Package",
  "visibility_type": "role_based",
  "is_visible_to_user": true,
  "allowed_roles": ["seller", "showroom"],  // Admin only
  "user_access_count": 0  // Admin only, for user_specific packages
}
```

---

## Package Requests

Users request to purchase packages, admins review and approve/reject.

### User Routes

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| POST | `/packages/{package}/request` | Submit package request | Yes | No |
| GET | `/user/package-requests` | View own requests | Yes | No |

### Admin Routes

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| GET | `/admin/package-requests` | List all requests | Yes | Yes |
| GET | `/admin/package-requests/{packageRequest}` | View request details | Yes | Yes |
| PATCH | `/admin/package-requests/{packageRequest}/review` | Review request | Yes | Yes |
| POST | `/admin/package-requests/{packageRequest}/approve` | Approve and assign package | Yes | Yes |
| POST | `/admin/package-requests/{packageRequest}/reject` | Reject request | Yes | Yes |

---

## Unique Ad Type Definitions

Admin-managed unique ad type variants with features, pricing, and priority levels.

### Public Routes

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| GET | `/unique-ad-types` | List active unique ad types | No | No |
| GET | `/unique-ad-types/{id}` | Get unique ad type details | No | No |

### Admin Routes

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| GET | `/admin/unique-ad-types` | List all unique ad types | Yes | Yes |
| POST | `/admin/unique-ad-types` | Create unique ad type | Yes | Yes |
| GET | `/admin/unique-ad-types/{id}` | Get unique ad type | Yes | Yes |
| PUT | `/admin/unique-ad-types/{id}` | Update unique ad type | Yes | Yes |
| DELETE | `/admin/unique-ad-types/{id}` | Delete unique ad type | Yes | Yes |
| PATCH | `/admin/unique-ad-types/{id}/toggle-status` | Toggle active status | Yes | Yes |

### Package Association

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| GET | `/admin/packages/{package}/unique-ad-types` | Get package's ad types | Yes | Yes |
| POST | `/admin/packages/{package}/unique-ad-types` | Assign ad types to package | Yes | Yes |
| DELETE | `/admin/packages/{package}/unique-ad-types/{type}` | Remove ad type from package | Yes | Yes |

### Create Unique Ad Type

**POST** `/admin/unique-ad-types`

**Request Body:**
```json
{
  "name": "Turbo",
  "description": "Featured listing with AI video generation",
  "base_price": 99.99,
  "duration_days": 30,
  "features": {
    "ai_video_generation": true,
    "priority_listing": 1,
    "featured_badge": true,
    "social_sharing": true
  },
  "limits": {
    "max_images": 20,
    "max_videos": 3
  },
  "priority_level": 1,
  "active": true
}
```

**Response:**
```json
{
  "success": true,
  "message": "Unique ad type created successfully",
  "data": {
    "id": 5,
    "name": "Turbo",
    "slug": "turbo",
    "base_price": 99.99,
    "priority_level": 1,
    "active": true
  }
}
```

---

## Ad Upgrade Requests

Users request to upgrade normal ads to unique ad types. Admins review and approve/reject.

### User Routes

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| GET | `/ads/upgrade-requests` | List own upgrade requests | Yes | No |
| POST | `/ads/{ad}/upgrade-request` | Create upgrade request | Yes | No |
| GET | `/ads/{ad}/upgrade-request` | Get ad's upgrade request | Yes | No |
| DELETE | `/ads/upgrade-requests/{id}` | Cancel upgrade request | Yes | No* |

*Owner or admin

### Admin Routes

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| GET | `/admin/upgrade-requests` | List all upgrade requests | Yes | Yes |
| GET | `/admin/upgrade-requests/{id}` | Get upgrade request details | Yes | Yes |
| POST | `/admin/upgrade-requests/{id}/approve` | Approve upgrade request | Yes | Yes |
| POST | `/admin/upgrade-requests/{id}/reject` | Reject upgrade request | Yes | Yes |

### Create Upgrade Request

**POST** `/ads/{ad}/upgrade-request`

**Request Body:**
```json
{
  "unique_ad_type_id": 5,
  "notes": "I would like to feature this premium vehicle with AI video"
}
```

**Validation:**
- Ad must be `type = 'normal'` and owned by requester
- Cannot have pending upgrade request
- Unique ad type must exist and be active

**Response:**
```json
{
  "success": true,
  "message": "Upgrade request submitted successfully",
  "data": {
    "id": 123,
    "ad_id": 456,
    "unique_ad_type_id": 5,
    "status": "pending",
    "created_at": "2026-02-11T10:30:00Z"
  }
}
```

### Approve Upgrade Request

**POST** `/admin/upgrade-requests/{id}/approve`

**Request Body:**
```json
{
  "notes": "Approved - vehicle meets quality standards"
}
```

**Actions:**
- Changes ad type from `normal` to `unique`
- Creates UniqueAd record with type definition
- Deletes NormalAd record
- Updates upgrade request status to `approved`
- Sends notification to user

**Response:**
```json
{
  "success": true,
  "message": "Upgrade request approved successfully",
  "data": {
    "request": {
      "id": 123,
      "status": "approved",
      "admin_notes": "Approved - vehicle meets quality standards"
    },
    "ad": {
      "id": 456,
      "type": "unique",
      "unique_ad_type_name": "Turbo"
    }
  }
}
```

---

## Ad Type Conversion

Convert ads between types (normal, unique, caishha, findit, auction). Enhanced in Phase 3 to support free users.

### User Routes

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| POST | `/ads/{ad}/convert` | Convert ad to different type | Yes | No* |
| GET | `/ads/{ad}/conversion-history` | View ad's conversion history | Yes | No* |

*Owner or admin

### Conversion Logic

**Admins:**
- ✅ Unrestricted conversion to ANY type
- ✅ Bypass all package checks

**Paid Users:**
- ✅ Can convert between types allowed by active package
- ✅ Must have package permission for destination type

**Free Users (Phase 3 Enhancement):**
- ✅ Can now convert IF active package allows destination type
- ✅ No longer restricted to upgrade requests only
- ✅ Must have package permission (same as paid users)

### Convert Ad Type

**POST** `/ads/{ad}/convert`

**Request Body:**
```json
{
  "to_type": "unique"
}
```

**Validation:**
- `to_type`: required, one of: normal, unique, caishha, findit, auction
- Must not be same as current type
- User must own ad (or be admin)
- For non-admins: Active package must allow destination type

**Response:**
```json
{
  "success": true,
  "message": "Ad type converted successfully",
  "data": {
    "ad": {
      "id": 456,
      "type": "unique",
      "title": "2020 Toyota Camry"
    },
    "conversion": {
      "id": 789,
      "from_type": "normal",
      "to_type": "unique",
      "converted_at": "2026-02-11T14:20:00Z"
    }
  }
}
```

**Error Responses:**

No active package:
```json
{
  "success": false,
  "message": "No active package found",
  "error_code": 403
}
```

Package doesn't allow type:
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

### Conversion History

**GET** `/ads/{ad}/conversion-history`

**Response:**
```json
{
  "success": true,
  "message": "Conversion history retrieved",
  "data": {
    "conversions": [
      {
        "id": 789,
        "from_type": "normal",
        "to_type": "unique",
        "user_name": "John Doe",
        "package_name": "Premium Package",
        "converted_at": "2026-02-11T14:20:00Z"
      },
      {
        "id": 790,
        "from_type": "unique",
        "to_type": "caishha",
        "user_name": "John Doe",
        "package_name": "Premium Package",
        "converted_at": "2026-02-11T15:10:00Z"
      }
    ],
    "total_conversions": 2
  }
}
```

---

## Feature Actions & Usage

Actionable package features (AI video generation, image editing, etc.) with credit tracking.

### User Routes

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| GET | `/features/my-credits` | Get own feature credits | Yes | No |
| POST | `/features/{feature}/use` | Use a feature (consume credit) | Yes | No |
| GET | `/features/usage-history` | Get own usage history | Yes | No |
| GET | `/ads/{ad}/feature-usage` | Get ad's feature usage | Yes | No* |

*Owner or admin

### Admin Routes

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| GET | `/admin/features/usage-stats` | Feature usage statistics | Yes | Yes |
| POST | `/admin/users/{user}/grant-credits` | Grant feature credits to user | Yes | Yes |

### Get Feature Credits

**GET** `/features/my-credits`

**Response:**
```json
{
  "success": true,
  "message": "Feature credits retrieved",
  "data": {
    "package": {
      "id": 5,
      "name": "Premium Package",
      "active": true
    },
    "credits": [
      {
        "feature_slug": "ai_video_generation",
        "feature_name": "AI Video Generation",
        "total_credits": 10,
        "used_credits": 3,
        "remaining_credits": 7,
        "unlimited": false
      },
      {
        "feature_slug": "auto_background_removal",
        "feature_name": "Auto Background Removal",
        "total_credits": 50,
        "used_credits": 12,
        "remaining_credits": 38,
        "unlimited": false
      },
      {
        "feature_slug": "priority_listing",
        "feature_name": "Priority Listing",
        "unlimited": true
      }
    ]
  }
}
```

### Use Feature

**POST** `/features/{feature}/use`

**Request Body:**
```json
{
  "ad_id": 456,
  "metadata": {
    "video_style": "professional",
    "duration": 30
  }
}
```

**Validation:**
- User must have active package with feature
- Feature must have remaining credits (or be unlimited)
- Ad must belong to user (or user is admin)

**Response:**
```json
{
  "success": true,
  "message": "Feature used successfully",
  "data": {
    "usage": {
      "id": 1234,
      "feature_slug": "ai_video_generation",
      "ad_id": 456,
      "credits_consumed": 1,
      "used_at": "2026-02-11T10:45:00Z"
    },
    "remaining_credits": 6
  }
}
```

**Error Responses:**

No credits remaining:
```json
{
  "success": false,
  "message": "Insufficient credits",
  "errors": {
    "feature": ["You have no remaining credits for ai_video_generation"]
  },
  "error_code": 403
}
```

Feature not in package:
```json
{
  "success": false,
  "message": "Feature not available",
  "errors": {
    "feature": ["Your package does not include ai_video_generation"]
  },
  "error_code": 403
}
```

### Usage History

**GET** `/features/usage-history?feature=ai_video_generation&limit=20`

**Query Parameters:**
- `feature`: Filter by feature slug (optional)
- `ad_id`: Filter by ad ID (optional)
- `limit`: Results per page (default: 20, max: 100)

**Response:**
```json
{
  "success": true,
  "message": "Usage history retrieved",
  "data": {
    "usage_logs": [
      {
        "id": 1234,
        "feature_slug": "ai_video_generation",
        "feature_name": "AI Video Generation",
        "ad_id": 456,
        "ad_title": "2020 Toyota Camry",
        "credits_consumed": 1,
        "metadata": {
          "video_style": "professional",
          "duration": 30
        },
        "used_at": "2026-02-11T10:45:00Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "total": 48,
      "per_page": 20
    }
  }
}
```

---

## Notifications

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| GET | `/notifications` | List own notifications | Yes | No |
| GET | `/notifications/{id}` | Get notification details | Yes | No |
| PATCH | `/notifications/{id}/read` | Mark notification as read | Yes | No |
| POST | `/notifications/read-all` | Mark all as read | Yes | No |
| DELETE | `/notifications/{id}` | Delete notification | Yes | No |
| POST | `/notifications/send` | Send notification (admin) | Yes | Yes |

---

## Favorites

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| GET | `/favorites` | List own favorites | Yes | No |
| GET | `/favorites/count` | Get favorites count | Yes | No |
| GET | `/favorites/check/{ad}` | Check if ad is favorited | Yes | No |
| POST | `/favorites/{ad}` | Add ad to favorites | Yes | No |
| POST | `/favorites/toggle/{ad}` | Toggle favorite status | Yes | No |
| DELETE | `/favorites/{favorite}` | Remove favorite by ID | Yes | No |
| DELETE | `/favorites/ad/{ad}` | Remove favorite by ad ID | Yes | No |

---

## Saved Searches

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| GET | `/saved-searches` | List own saved searches | Yes | No |
| POST | `/saved-searches` | Create saved search | Yes | No |
| GET | `/saved-searches/{savedSearch}` | Get saved search details | Yes | No* |
| PUT | `/saved-searches/{savedSearch}` | Update saved search | Yes | No* |
| DELETE | `/saved-searches/{savedSearch}` | Delete saved search | Yes | No* |

*Owner

---

## Blog

### Public Routes

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| GET | `/blogs` | List published blogs | No | No |
| GET | `/blogs/{blog}` | Get published blog | No | No |

### Admin Routes

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| GET | `/admin/blogs` | List all blogs | Yes | Yes |
| GET | `/admin/blogs/{blog}` | Get any blog | Yes | Yes |
| POST | `/admin/blogs` | Create blog post | Yes | Yes |
| PUT | `/admin/blogs/{blog}` | Update blog post | Yes | Yes |
| DELETE | `/admin/blogs/{blog}` | Delete blog post | Yes | Yes |

---

## Specifications

Admin-only management of vehicle specifications (features, options).

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| GET | `/admin/specifications` | List specifications | Yes | Yes |
| GET | `/admin/specifications/{specification}` | Get specification | Yes | Yes |
| POST | `/admin/specifications` | Create specification | Yes | Yes |
| PUT | `/admin/specifications/{specification}` | Update specification | Yes | Yes |
| DELETE | `/admin/specifications/{specification}` | Delete specification | Yes | Yes |

---

## Categories

Admin-only management of vehicle categories.

### Category CRUD

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| GET | `/admin/categories` | List categories | Yes | Yes |
| GET | `/admin/categories/{category}` | Get category | Yes | Yes |
| POST | `/admin/categories` | Create category | Yes | Yes |
| PUT | `/admin/categories/{category}` | Update category | Yes | Yes |
| DELETE | `/admin/categories/{category}` | Delete category | Yes | Yes |

### Category Specifications

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| GET | `/admin/categories/{category}/specifications` | Get category specs | Yes | Yes |
| POST | `/admin/categories/{category}/specifications/assign` | Assign specs (replace all) | Yes | Yes |
| POST | `/admin/categories/{category}/specifications/attach` | Add single spec | Yes | Yes |
| DELETE | `/admin/categories/{category}/specifications/{specification}` | Remove spec | Yes | Yes |

---

## Seller Stats

Track ad performance metrics.

### Dashboard & Overview

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| GET | `/seller/dashboard` | Seller dashboard overview | Yes | No |
| GET | `/seller/stats/views` | Total views statistics | Yes | No |
| GET | `/seller/stats/contacts` | Total contacts statistics | Yes | No |
| GET | `/seller/stats/clicks` | Total clicks statistics | Yes | No |

### Ad-Specific Stats

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| GET | `/seller/ads/{ad}/views` | Get ad views | Yes | No* |
| GET | `/seller/ads/{ad}/contacts` | Get ad contacts | Yes | No* |
| GET | `/seller/ads/{ad}/clicks` | Get ad clicks | Yes | No* |

*Owner

### Record Interactions

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| POST | `/seller/ads/{ad}/views` | Increment view count | Yes | No |
| POST | `/seller/ads/{ad}/contacts` | Increment contact count | Yes | No |
| POST | `/seller/ads/{ad}/clicks` | Increment click count | Yes | No |

---

## Sliders

Manage homepage/promotional sliders.

### Public Routes

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| GET | `/sliders` | List active sliders | No | No |
| GET | `/sliders/{slider}` | Get slider details | No | No |

### Admin Routes

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| POST | `/admin/sliders` | Create slider | Yes | Yes |
| PUT | `/admin/sliders/{slider}` | Update slider | Yes | Yes |
| DELETE | `/admin/sliders/{slider}` | Delete slider | Yes | Yes |
| POST | `/admin/sliders/{slider}/activate` | Activate slider | Yes | Yes |
| POST | `/admin/sliders/{slider}/deactivate` | Deactivate slider | Yes | Yes |

---

## Admin Stats

Platform-wide analytics and statistics.

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| GET | `/admin/stats/dashboard` | Overall platform stats | Yes | Yes |
| GET | `/admin/stats/ads/{ad}/views` | Ad views count | Yes | Yes |
| GET | `/admin/stats/ads/{ad}/clicks` | Ad clicks count | Yes | Yes |
| GET | `/admin/stats/dealer/{user}` | Dealer statistics | Yes | Yes |
| GET | `/admin/stats/user/{user}` | User statistics | Yes | Yes |
| GET | `/admin/stats/ads/{type}` | Count ads by type | Yes | Yes |

---

## Audit Logs

Admin-only compliance and security monitoring.

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| GET | `/admin/audit-logs` | List audit logs (with filters) | Yes | Yes |
| GET | `/admin/audit-logs/stats` | Audit log statistics | Yes | Yes |
| GET | `/admin/audit-logs/{audit_log}` | View specific audit log | Yes | Yes |

**Security:** Admin and super_admin only via AuditLogPolicy

---

## Page Content

Manage static page content (About Us, Privacy Policy, Terms & Conditions).

### Public Routes

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| GET | `/pages` | List all page contents | No | No |
| GET | `/pages/{pageKey}` | Get specific page (e.g., about_us) | No | No |

### Admin Routes

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| GET | `/admin/pages` | List all pages (admin) | Yes | Yes |
| GET | `/admin/pages/{pageKey}` | Get page (admin) | Yes | Yes |
| PUT | `/admin/pages/{pageKey}` | Update page content | Yes | Yes |

**Page Keys:** `about_us`, `privacy_policy`, `terms_conditions`, etc.

---

## Company Settings

Manage company contact information and social media links.

### Public Routes

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| GET | `/company-info` | Get active company contacts & links | No | No |

### Admin Routes

| Method | Endpoint | Description | Auth | Admin |
|--------|----------|-------------|------|-------|
| GET | `/admin/company-settings` | List all company settings | Yes | Yes |
| GET | `/admin/company-settings/type/{type}` | List by type (contact/social) | Yes | Yes |
| PUT | `/admin/company-settings` | Bulk update settings | Yes | Yes |
| PUT | `/admin/company-settings/{key}` | Update single setting | Yes | Yes |
| POST | `/admin/company-settings/{key}/toggle-active` | Toggle active status | Yes | Yes |

**Setting Types:** `contact` (phone, email, address), `social` (facebook, twitter, instagram)

---

## Query Parameters

### Common Parameters for Listings

- `page` - Page number (default: 1)
- `per_page` or `limit` - Items per page (default: 15, max: 50)
- `sort` - Sort field (e.g., created_at, price, views_count)
- `order` - Sort order (asc, desc)
- `search` - Text search in title/description
- `status` - Filter by status

### Normal/Unique/Auction Ads Specific

- `brand_id` - Filter by brand
- `model_id` - Filter by model
- `category_id` - Filter by category
- `city_id` - Filter by city
- `country_id` - Filter by country
- `min_price` - Minimum price
- `max_price` - Maximum price
- `min_year` - Minimum year
- `max_year` - Maximum year
- `min_mileage` - Minimum mileage
- `max_mileage` - Maximum mileage
- `condition` - Vehicle condition (new, used, certified)
- `transmission` - Transmission type (automatic, manual)
- `fuel_type` - Fuel type (petrol, diesel, electric, hybrid)
- `body_type` - Body type (sedan, suv, truck, etc.)
- `is_featured` - Filter featured ads (boolean)
- `is_verified` - Filter verified ads (boolean)

---

## HTTP Status Codes

- `200` - Success
- `201` - Resource created
- `204` - Success with no content
- `400` - Bad request
- `401` - Unauthenticated
- `403` - Forbidden / Unauthorized
- `404` - Not found
- `422` - Validation error
- `429` - Rate limit exceeded
- `500` - Server error

---

## Rate Limiting

- **POST /reviews** - `throttle:review` (e.g., 5 per minute)
- **POST /reports** - `throttle:report` (e.g., 3 per minute)

Rate limit exceeded (429):
```json
{
  "status": "error",
  "code": 429,
  "message": "Too many requests. Please try again later.",
  "errors": {
    "rate_limit": {
      "retry_after": 60,
      "limit": 5,
      "remaining": 0
    }
  }
}
```

---

## Pagination

Paginated responses include:

```json
{
  "pagination": {
    "current_page": 1,
    "per_page": 15,
    "total": 250,
    "last_page": 17,
    "from": 1,
    "to": 15
  }
}
```

---

## Endpoint Count Summary

Total API endpoints: **240+**

- Authentication: 6
- Users: 6
- Roles: 7
- Seller Verification: 4
- Brands & Models: 8
- Media: 5
- Normal Ads: 25+
- Unique Ads: 32+
- Caishha Ads: 18+
- Caishha Settings: 4
- Auction Ads: 16+
- FindIt Ads: 16+
- Reviews: 8
- Reports: 9
- Packages: 17
- **Package Visibility Management: 5** *(NEW)*
- Package Requests: 7
- **Unique Ad Type Definitions: 9** *(NEW)*
- **Ad Upgrade Requests: 8** *(NEW)*
- **Ad Type Conversion: 2** *(NEW)*
- **Feature Actions & Usage: 6** *(NEW)*
- Notifications: 6
- Favorites: 7
- Saved Searches: 5
- Blog: 7
- Specifications: 5
- Categories: 9
- Seller Stats: 10
- Sliders: 7
- Admin Stats: 6
- Audit Logs: 3
- Page Content: 4
- Company Settings: 6

---

## Related Documentation

- [API Standardized Contract](./API_STANDARDIZED_CONTRACT.md) - Detailed request/response schemas
- [API Migration Plan](./API_MIGRATION_PLAN.md) - Migration guide for envelope standardization
- [Unique Ads System Phase 1 Implementation](./UNIQUE_ADS_SYSTEM_IMPLEMENTATION.md) - Unique ad type definitions
- [Unique Ads System Phase 2 Implementation](./UNIQUE_ADS_SYSTEM_PHASE_2_IMPLEMENTATION.md) - Feature credits & conversion
- [Unique Ads System Phase 3 Implementation](./UNIQUE_ADS_SYSTEM_PHASE_3_IMPLEMENTATION.md) - Package visibility & enhanced conversion

---

**Last Updated:** February 11, 2026  
**Document Version:** 1.1.0  
**Major Updates:**
- Added Package Visibility Management (5 endpoints)
- Added Unique Ad Type Definitions (9 endpoints)
- Added Ad Upgrade Requests (8 endpoints)
- Added Ad Type Conversion (2 endpoints)
- Added Feature Actions & Usage (6 endpoints)
- Enhanced conversion logic for free users
- Total: 30+ new endpoints
