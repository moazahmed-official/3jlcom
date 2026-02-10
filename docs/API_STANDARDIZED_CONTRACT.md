# API Standardized Contract (v1)

**Version:** 1.0.0  
**Last Updated:** February 10, 2026  
**Base URL:** `/api/v1`  

---

## Design Principles

This API follows REST principles with a **consistent JSON envelope structure** for all responses:

### Success Response Envelope

```json
{
  "status": "success",
  "message": "Operation completed successfully",
  "data": { /* resource or collection */ }
}
```

### Paginated Response Envelope

```json
{
  "status": "success",
  "message": "Data retrieved successfully",
  "data": {
    "items": [ /* array of resources */ ],
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

### Error Response Envelope

```json
{
  "status": "error",
  "code": 400,
  "message": "Error description",
  "errors": {
    "field_name": ["Error message 1", "Error message 2"]
  }
}
```

**Common HTTP Status Codes:**
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

## Authentication

All protected endpoints require Bearer token authentication using Laravel Sanctum.

**Header:**
```
Authorization: Bearer {token}
```

**Obtaining a token:** Use `POST /api/v1/auth/login` or `POST /api/v1/auth/register` + `PUT /api/v1/auth/verify`

---

## Global Error Responses

All endpoints may return these errors:

- **401 Unauthenticated** (for protected endpoints)
- **500 Server Error**

---

# Endpoints

## Authentication

### POST /auth/login

Authenticate user and receive access token.

**Middleware:** Public  
**Admin Only:** No

**Request Body:**
```json
{
  "email": "user@example.com",  // required_without:phone
  "phone": "+962791234567",     // required_without:email
  "password": "password123"     // required, string
}
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Authenticated",
  "data": {
    "token": "1|abcdef123456...",
    "token_type": "Bearer",
    "expires_in": 3600,  // seconds, null if no expiration
    "user": {
      "id": 1,
      "name": "John Doe",
      "phone": "+962791234567",
      "account_type": "individual",
      "is_verified": true,
      "seller_verified": false,
      "seller_verified_at": null,
      "created_at": "2024-01-15T10:30:00Z"
    }
  }
}
```

**Error Responses:**
- **401:** Invalid credentials
- **422:** Validation failed

---

### POST /auth/register

Register a new user account and send OTP verification.

**Middleware:** Public  
**Admin Only:** No

**Request Body:**
```json
{
  "name": "John Doe",                    // required, string, max:255
  "email": "user@example.com",          // required, email, unique
  "phone": "+962791234567",             // required, string, unique
  "country_id": 1,                      // nullable, integer, exists:countries
  "password": "password123",            // required, string, min:8, confirmed
  "password_confirmation": "password123",
  "account_type": "individual"          // optional, enum: individual|dealer|showroom
}
```

**Success Response (201):**
```json
{
  "status": "success",
  "message": "Registration successful. Please verify your account with the OTP sent to your phone.",
  "data": {
    "user_id": 1,
    "phone": "+962791234567",
    "expires_in_minutes": 10
  }
}
```

**Error Responses:**
- **422:** Validation failed (email/phone already exists, etc.)
- **500:** Registration failed

---

### PUT /auth/verify

Verify account with OTP code.

**Middleware:** Public  
**Admin Only:** No

**Request Body:**
```json
{
  "phone": "+962791234567",  // required_without:email
  "email": "user@example.com",  // required_without:phone
  "code": "123456"  // required, string, size:6
}
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Account verified successfully.",
  "data": {
    "token": "1|abcdef123456...",
    "token_type": "Bearer",
    "expires_in": 3600,
    "user": { /* User object */ }
  }
}
```

**Error Responses:**
- **404:** User not found
- **400:** OTP expired or invalid
- **422:** Validation failed

---

### POST /auth/password/reset-request

Request password reset OTP.

**Middleware:** Public  
**Admin Only:** No

**Request Body:**
```json
{
  "phone": "+962791234567"  // required, exists:users
}
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Password reset OTP sent successfully.",
  "data": {
    "phone": "+962791234567",
    "expires_in_minutes": 10
  }
}
```

---

### PUT /auth/password/reset

Confirm password reset with OTP.

**Middleware:** Public  
**Admin Only:** No

**Request Body:**
```json
{
  "phone": "+962791234567",  // required
  "code": "123456",  // required, size:6
  "new_password": "newpass123",  // required, min:8, confirmed
  "new_password_confirmation": "newpass123"
}
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Password reset successfully."
}
```

---

### POST /auth/logout

Logout and revoke current access token.

**Middleware:** auth:sanctum  
**Admin Only:** No

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Logged out"
}
```

---

## Users

### POST /users

Create a new user (admin only).

**Middleware:** auth:sanctum  
**Admin Only:** Yes

**Request Body:**
```json
{
  "name": "Jane Smith",
  "email": "jane@example.com",
  "phone": "+962791234568",
  "country_id": 1,
  "account_type": "dealer",  // optional
  "password": "password123"
}
```

**Success Response (201):**
```json
{
  "status": "success",
  "message": "User created successfully",
  "data": {
    "id": 2,
    "name": "Jane Smith",
    "phone": "+962791234568",
    "account_type": "dealer",
    "is_verified": false,
    "seller_verified": false,
    "seller_verified_at": null,
    "created_at": "2024-01-15T10:30:00Z"
  }
}
```

**Error Responses:**
- **403:** Insufficient permissions (not admin)
- **422:** Validation failed

---

### GET /users

List users with pagination.

**Middleware:** auth:sanctum  
**Admin Only:** No

**Query Parameters:**
- `page` - Page number (default: 1)
- `per_page` - Items per page (default: 20, max: 50)

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Users retrieved successfully",
  "data": {
    "items": [
      {
        "id": 1,
        "name": "John Doe",
        "phone": "+962791234567",
        "account_type": "individual",
        "is_verified": true,
        "seller_verified": false,
        "seller_verified_at": null,
        "created_at": "2024-01-15T10:30:00Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 100,
      "last_page": 5,
      "from": 1,
      "to": 20
    }
  }
}
```

---

### GET /users/{user}

Get specific user details.

**Middleware:** auth:sanctum  
**Admin Only:** No

**Success Response (200):**
```json
{
  "status": "success",
  "message": "User retrieved successfully",
  "data": {
    "id": 1,
    "name": "John Doe",
    "phone": "+962791234567",
    "account_type": "individual",
    "is_verified": true,
    "seller_verified": false,
    "seller_verified_at": null,
    "created_at": "2024-01-15T10:30:00Z"
  }
}
```

**Error Responses:**
- **404:** User not found

---

### PUT /users/{user}

Update user details (owner or admin).

**Middleware:** auth:sanctum  
**Admin Only:** No (owner or admin)

**Request Body:**
```json
{
  "name": "John Updated",  // optional
  "email": "newemail@example.com",  // optional
  "phone": "+962791234569",  // optional
  "country_id": 2,  // optional
  "password": "newpass123"  // optional, min:8
}
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "User updated successfully",
  "data": { /* Updated User object */ }
}
```

**Error Responses:**
- **403:** Unauthorized
- **404:** User not found
- **422:** Validation failed

---

### DELETE /users/{user}

Delete a user (admin only).

**Middleware:** auth:sanctum  
**Admin Only:** Yes

**Success Response (200):**
```json
{
  "status": "success",
  "message": "User deleted successfully"
}
```

**Error Responses:**
- **403:** Cannot delete yourself / Insufficient permissions
- **404:** User not found

---

### POST /users/{user}/verify

Verify a user/seller account (admin only).

**Middleware:** auth:sanctum  
**Admin Only:** Yes

**Request Body:**
```json
{
  "status": "approved",  // required, enum: approved|rejected
  "admin_comments": "Verified documents"  // optional
}
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "User verification processed successfully",
  "data": {
    "user_id": 1,
    "verification_status": "approved",
    "admin_comments": "Verified documents",
    "verified_at": "2024-01-15T11:00:00Z"
  }
}
```

---

## Media

### POST /media

Upload a media file (image or video).

**Middleware:** auth:sanctum  
**Admin Only:** No

**Request Body (multipart/form-data):**
- `file` - required, file, max:10240 (10MB for images, 100MB for videos)
- `purpose` - optional, string (general|avatar|ad|banner)
- `related_resource` - optional, string
- `related_id` - optional, integer

**Success Response (201):**
```json
{
  "status": "success",
  "message": "Media uploaded successfully",
  "data": {
    "id": 1,
    "filename": "uuid-filename.jpg",
    "path": "general/2024/01/uuid-filename.jpg",
    "url": "https://example.com/storage/general/2024/01/uuid-filename.jpg",
    "type": "image",
    "status": "ready",
    "thumbnail_url": "https://example.com/storage/general/2024/01/thumbs/uuid-filename_thumb.jpg",
    "user_id": 1,
    "related_resource": "ads",
    "related_id": 5,
    "created_at": "2024-01-15T10:30:00Z",
    "updated_at": "2024-01-15T10:30:00Z"
  }
}
```

**Error Responses:**
- **422:** Validation failed (file too large, invalid type)

---

### GET /media

List user's uploaded media.

**Middleware:** auth:sanctum  
**Admin Only:** No

**Query Parameters:**
- `page` - Page number
- `per_page` - Items per page (max: 50)
- `type` - Filter by type (image|video)

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Media retrieved successfully",
  "data": {
    "items": [
      { /* Media object */ }
    ],
    "pagination": { /* ... */ }
  }
}
```

---

### GET /media/{media}

Get specific media details.

**Middleware:** auth:sanctum  
**Admin Only:** No

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Media retrieved successfully",
  "data": { /* Media object */ }
}
```

---

### DELETE /media/{media}

Delete a media file (owner or admin).

**Middleware:** auth:sanctum  
**Admin Only:** No

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Media deleted successfully"
}
```

**Error Responses:**
- **403:** Unauthorized (not owner or admin)
- **404:** Media not found

---

## Normal Ads

### GET /normal-ads

List published normal ads (public).

**Middleware:** Public  
**Admin Only:** No

**Query Parameters:**
- `page` - Page number (default: 1)
- `limit` - Items per page (default: 15, max: 50)
- `brand_id` - Filter by brand
- `model_id` - Filter by model
- `city_id` - Filter by city
- `country_id` - Filter by country
- `min_price` - Minimum price
- `max_price` - Maximum price
- `min_year` - Minimum year
- `max_year` - Maximum year
- `search` - Search in title/description
- `sort_by` - Sort field (created_at|views_count|title)
- `sort_direction` - Sort direction (asc|desc)

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Ads retrieved successfully",
  "data": {
    "items": [
      {
        "id": 1,
        "type": "normal",
        "title": "2020 Toyota Camry",
        "description": "Excellent condition...",
        "status": "published",
        "views_count": 150,
        "contact_phone": "+962791234567",
        "whatsapp_number": "+962791234567",
        "period_days": 30,
        "is_pushed_facebook": false,
        "category_id": 1,
        "city_id": 1,
        "country_id": 1,
        "brand_id": 5,
        "model_id": 23,
        "year": 2020,
        "price_cash": 15000.00,
        "installment_id": null,
        "start_time": "2024-01-15T10:00:00Z",
        "update_time": "2024-01-15T10:00:00Z",
        "user_id": 1,
        "created_at": "2024-01-15T10:00:00Z",
        "updated_at": "2024-01-15T10:00:00Z",
        "user": {
          "id": 1,
          "full_name": "John Doe",
          "profile_image": "https://..."
        },
        "brand": {
          "id": 5,
          "name": "Toyota",
          "image": "https://..."
        },
        "model": {
          "id": 23,
          "name": "Camry"
        },
        "city": {
          "id": 1,
          "name": "Amman"
        },
        "country": {
          "id": 1,
          "name": "Jordan",
          "currency": "JOD"
        },
        "category": {
          "id": 1,
          "name": "Cars"
        },
        "media": [
          {
            "id": 1,
            "filename": "car1.jpg",
            "path": "ads/2024/01/car1.jpg",
            "url": "https://example.com/storage/ads/2024/01/car1.jpg",
            "type": "image",
            "status": "ready",
            "thumbnail_url": "https://...",
            "user_id": 1,
            "related_resource": "ads",
            "related_id": 1,
            "created_at": "2024-01-15T09:00:00Z",
            "updated_at": "2024-01-15T09:00:00Z"
          }
        ]
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total": 250,
      "last_page": 17,
      "from": 1,
      "to": 15
    }
  }
}
```

---

### GET /normal-ads/{ad}

Get specific normal ad details (public).

**Middleware:** Public  
**Admin Only:** No

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Ad retrieved successfully",
  "data": {
    /* Normal Ad object with all relationships loaded */
  }
}
```

**Error Responses:**
- **404:** Ad not found

---

### GET /users/{user}/normal-ads

List public normal ads by specific user.

**Middleware:** Public  
**Admin Only:** No

**Success Response (200):**
```json
{
  "status": "success",
  "message": "User ads retrieved successfully",
  "data": {
    "items": [ /* Normal Ad objects */ ],
    "pagination": { /* ... */ }
  }
}
```

---

### GET /normal-ads/my-ads

List authenticated user's own ads (all statuses).

**Middleware:** auth:sanctum  
**Admin Only:** No

**Query Parameters:**
- `status` - Filter by status (draft|published|expired|archived)
- `brand_id` - Filter by brand
- `search` - Search query
- `sort_by` - Sort field
- `sort_direction` - Sort direction
- `page`, `limit`

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Your ads retrieved successfully",
  "data": {
    "items": [ /* Normal Ad objects including draft/expired */ ],
    "pagination": { /* ... */ }
  }
}
```

---

### GET /normal-ads/admin

List all normal ads with all statuses (admin only).

**Middleware:** auth:sanctum  
**Admin Only:** Yes

**Query Parameters:**
- All public filters plus:
- `user_id` - Filter by user
- `status` - Filter by status

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Admin ads retrieved successfully",
  "data": {
    "items": [ /* Normal Ad objects */ ],
    "pagination": { /* ... */ }
  }
}
```

**Error Responses:**
- **403:** Unauthorized (not admin)

---

### GET /normal-ads/stats

Get global normal ads statistics (admin only).

**Middleware:** auth:sanctum  
**Admin Only:** Yes

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Statistics retrieved successfully",
  "data": {
    "total_ads": 1250,
    "published_ads": 980,
    "draft_ads": 50,
    "expired_ads": 180,
    "archived_ads": 40,
    "total_views": 45000,
    "average_views_per_ad": 36
  }
}
```

---

### POST /normal-ads

Create a new normal ad.

**Middleware:** auth:sanctum  
**Admin Only:** No

**Request Body:**
```json
{
  "user_id": 1,  // optional, admin can create for other users
  "title": "2020 Toyota Camry",  // required
  "description": "Excellent condition...",  // required
  "category_id": 1,  // required
  "city_id": 1,  // required
  "country_id": 1,  // required
  "brand_id": 5,  // required
  "model_id": 23,  // required
  "year": 2020,  // required
  "contact_phone": "+962791234567",  // required
  "whatsapp_number": "+962791234567",  // optional
  "price_cash": 15000.00,  // required
  "installment_id": null,  // optional
  "media_ids": [1, 2, 3]  // optional, array of media IDs
}
```

**Success Response (201):**
```json
{
  "status": "success",
  "message": "Ad created successfully",
  "data": {
    /* Created Normal Ad object */
  }
}
```

**Error Responses:**
- **403:** Package limit reached
  ```json
  {
    "status": "error",
    "code": 403,
    "message": "You have reached your ad creation limit",
    "errors": {
      "package": ["You have reached your ad creation limit"],
      "limit_info": {
        "allowed": 10,
        "used": 10,
        "remaining": 0
      }
    }
  }
  ```
- **403:** Media limit exceeded
- **403:** Unauthorized (admin creating for other user)
- **422:** Validation failed

---

### PUT /normal-ads/{ad}

Update a normal ad (owner or admin).

**Middleware:** auth:sanctum  
**Admin Only:** No

**Request Body:**
```json
{
  "title": "Updated Title",  // optional
  "description": "Updated description",  // optional
  "price_cash": 14500.00,  // optional
  "media_ids": [1, 2, 4]  // optional
  // ... other updatable fields
}
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Ad updated successfully",
  "data": { /* Updated Normal Ad object */ }
}
```

**Error Responses:**
- **403:** Unauthorized
- **404:** Ad not found
- **422:** Validation failed

---

### DELETE /normal-ads/{ad}

Delete a normal ad (owner or admin).

**Middleware:** auth:sanctum  
**Admin Only:** No

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Ad deleted successfully"
}
```

**Error Responses:**
- **403:** Unauthorized
- **404:** Ad not found

---

### POST /normal-ads/{ad}/actions/publish

Publish a draft ad.

**Middleware:** auth:sanctum  
**Admin Only:** No

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Ad published successfully",
  "data": { /* Updated ad */ }
}
```

---

### POST /normal-ads/{ad}/actions/republish

Republish an expired ad.

**Middleware:** auth:sanctum  
**Admin Only:** No

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Ad republished successfully",
  "data": { /* Updated ad */ }
}
```

---

### POST /normal-ads/{ad}/actions/unpublish

Unpublish a published ad.

**Middleware:** auth:sanctum  
**Admin Only:** No

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Ad unpublished successfully",
  "data": { /* Updated ad */ }
}
```

---

### POST /normal-ads/{ad}/actions/archive

Archive an ad.

**Middleware:** auth:sanctum  
**Admin Only:** No

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Ad archived successfully",
  "data": { /* Updated ad */ }
}
```

---

### POST /normal-ads/{ad}/actions/restore

Restore an archived ad.

**Middleware:** auth:sanctum  
**Admin Only:** No

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Ad restored successfully",
  "data": { /* Updated ad */ }
}
```

---

### POST /normal-ads/{ad}/favorite

Add ad to favorites.

**Middleware:** auth:sanctum  
**Admin Only:** No

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Ad added to favorites",
  "data": {
    "favorite_id": 1,
    "ad_id": 5
  }
}
```

---

### DELETE /normal-ads/{ad}/favorite

Remove ad from favorites.

**Middleware:** auth:sanctum  
**Admin Only:** No

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Ad removed from favorites"
}
```

---

### POST /normal-ads/{ad}/contact

Contact seller (logs contact action).

**Middleware:** auth:sanctum  
**Admin Only:** No

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Contact logged successfully",
  "data": {
    "contact_phone": "+962791234567",
    "whatsapp_number": "+962791234567"
  }
}
```

---

## Unique Ads

Unique ads follow the same pattern as Normal Ads with additional fields and actions.

**Additional Fields:**
- `banner_color` - Color for banner display
- `banner_image_id` - ID of banner media
- `is_auto_republished` - Auto-republish when expired
- `is_verified_ad` - Verification badge (admin only)
- `is_featured` - Featured status
- `featured_at` - Featured timestamp

**Additional Actions:**
- `POST /unique-ads/{ad}/actions/feature` - Mark as featured (admin)
- `DELETE /unique-ads/{ad}/actions/feature` - Unmark as featured (admin)
- `POST /unique-ads/{ad}/actions/verify` - Request verification
- `POST /unique-ads/{ad}/actions/approve-verification` - Approve verification (admin)
- `POST /unique-ads/{ad}/actions/reject-verification` - Reject verification (admin)
- `POST /unique-ads/{ad}/actions/auto-republish` - Toggle auto-republish
- `POST /unique-ads/{ad}/actions/convert-to-normal` - Convert to normal ad

All endpoints follow the same structure as Normal Ads with standardized envelopes.

---

## Caishha Ads

Caishha ads are special "request for offers" listings where dealers submit price offers during a dealer-only window, followed by individual buyers.

### GET /caishha-ads

List published Caishha ads (public).

**Middleware:** Public  
**Admin Only:** No

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Caishha ads retrieved successfully",
  "data": {
    "items": [
      {
        "id": 1,
        "type": "caishha",
        "title": "Looking for Toyota Camry 2020",
        "description": "...",
        "status": "published",
        "views_count": 50,
        "contact_count": 5,
        "offers_count": 3,
        "offers_window_period": 48,  // hours
        "sellers_visibility_period": 72,  // hours
        "window_status": {
          "is_in_dealer_window": true,
          "is_in_individual_window": false,
          "are_offers_visible_to_seller": false,
          "can_accept_offers": false,
          "dealer_window_ends_at": "2024-01-17T10:00:00Z",
          "visibility_period_ends_at": "2024-01-18T10:00:00Z"
        },
        "published_at": "2024-01-15T10:00:00Z",
        /* ... other ad fields, relationships */
      }
    ],
    "pagination": { /* ... */ }
  }
}
```

---

### POST /caishha-ads/{ad}/offers

Submit an offer on a Caishha ad.

**Middleware:** auth:sanctum  
**Admin Only:** No

**Request Body:**
```json
{
  "price": 15000.00,  // required, numeric
  "comment": "Best offer, verified dealer"  // optional, string
}
```

**Success Response (201):**
```json
{
  "status": "success",
  "message": "Offer submitted successfully",
  "data": {
    "id": 1,
    "ad_id": 5,
    "user_id": 2,
    "price": 15000.00,
    "comment": "Best offer, verified dealer",
    "status": "pending",
    "is_visible_to_seller": false,
    "created_at": "2024-01-16T10:00:00Z",
    "updated_at": "2024-01-16T10:00:00Z"
  }
}
```

**Error Responses:**
- **403:** Window closed for offers
- **400:** Already submitted an offer

---

### GET /caishha-ads/{ad}/offers

List offers on an ad (owner or admin only, visible after window period).

**Middleware:** auth:sanctum  
**Admin Only:** No (owner or admin)

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Offers retrieved successfully",
  "data": {
    "items": [
      {
        "id": 1,
        "ad_id": 5,
        "user_id": 2,
        "price": 15000.00,
        "comment": "Best offer",
        "status": "pending",
        "is_visible_to_seller": true,
        "created_at": "2024-01-16T10:00:00Z",
        "updated_at": "2024-01-16T10:00:00Z",
        "user": {
          "id": 2,
          "name": "Dealer Co",
          "profile_image_id": 5,
          "seller_verified": true,
          "account_type": "dealer"
        }
      }
    ]
  }
}
```

**Error Responses:**
- **403:** Not authorized or window not closed
- **404:** Ad not found

---

### POST /caishha-ads/{ad}/offers/{offer}/accept

Accept an offer (owner only).

**Middleware:** auth:sanctum  
**Admin Only:** No

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Offer accepted successfully",
  "data": {
    /* Updated offer with status: "accepted" */
  }
}
```

---

### POST /caishha-ads/{ad}/offers/{offer}/reject

Reject an offer (owner only).

**Middleware:** auth:sanctum  
**Admin Only:** No

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Offer rejected successfully",
  "data": {
    /* Updated offer with status: "rejected" */
  }
}
```

---

### GET /caishha-offers/my-offers

List authenticated user's submitted offers.

**Middleware:** auth:sanctum  
**Admin Only:** No

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Your offers retrieved successfully",
  "data": {
    "items": [
      {
        "id": 1,
        "ad_id": 5,
        "price": 15000.00,
        "comment": "...",
        "status": "accepted",
        "created_at": "2024-01-16T10:00:00Z",
        "ad": {
          "id": 5,
          "title": "Looking for Toyota Camry 2020",
          "status": "published",
          /* ... */
        }
      }
    ],
    "pagination": { /* ... */ }
  }
}
```

---

## Auction Ads

Real-time auction listings with bidding functionality.

### GET /auction-ads

List published auction ads (public).

**Middleware:** Public  
**Admin Only:** No

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Auction ads retrieved successfully",
  "data": {
    "items": [
      {
        "id": 1,
        "type": "auction",
        "title": "2019 BMW X5 Auction",
        "description": "...",
        "status": "published",
        "start_price": 25000.00,
        "last_price": 27500.00,  // visible if is_last_price_visible = true
        "minimum_bid_increment": 100.00,
        "minimum_next_bid": 27600.00,
        "start_time": "2024-01-15T10:00:00Z",
        "end_time": "2024-01-20T10:00:00Z",
        "auction_status": "active",
        "bid_count": 15,
        "is_last_price_visible": true,
        "auction_state": {
          "is_active": true,
          "has_started": true,
          "has_ended": false,
          "can_accept_bids": true,
          "time_remaining_seconds": 345600,
          "time_remaining_human": "4 days",
          "meets_reserve": false
        },
        /* ... other ad fields, relationships */
      }
    ],
    "pagination": { /* ... */ }
  }
}
```

---

### POST /auction-ads/{ad}/bids

Place a bid on an auction.

**Middleware:** auth:sanctum  
**Admin Only:** No

**Request Body:**
```json
{
  "amount": 27600.00  // required, must be >= minimum_next_bid
}
```

**Success Response (201):**
```json
{
  "status": "success",
  "message": "Bid placed successfully",
  "data": {
    "id": 1,
    "auction_id": 5,
    "user_id": 2,
    "amount": 27600.00,
    "status": "active",
    "is_winning": true,
    "created_at": "2024-01-16T10:30:00Z"
  }
}
```

**Error Responses:**
- **400:** Auction not active
- **400:** Bid amount too low
- **400:** Cannot bid on own auction

---

### GET /auction-ads/{ad}/bids

List bids on an auction (owner or admin only).

**Middleware:** auth:sanctum  
**Admin Only:** No (owner or admin)

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Bids retrieved successfully",
  "data": {
    "items": [
      {
        "id": 1,
        "auction_id": 5,
        "user_id": 2,
        "amount": 27600.00,
        "status": "active",
        "is_winning": true,
        "created_at": "2024-01-16T10:30:00Z",
        "user": {
          "id": 2,
          "name": "Bidder Name"
        }
      }
    ]
  }
}
```

---

### GET /auction-bids/my-bids

List authenticated user's bids across all auctions.

**Middleware:** auth:sanctum  
**Admin Only:** No

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Your bids retrieved successfully",
  "data": {
    "items": [
      {
        "id": 1,
        "auction_id": 5,
        "amount": 27600.00,
        "status": "active",
        "is_winning": true,
        "created_at": "2024-01-16T10:30:00Z",
        "auction": {
          "id": 5,
          "title": "2019 BMW X5 Auction",
          "auction_status": "active",
          "end_time": "2024-01-20T10:00:00Z"
        }
      }
    ],
    "pagination": { /* ... */ }
  }
}
```

---

### DELETE /auction-ads/{ad}/bids/{bid}

Withdraw a bid (if allowed by auction rules).

**Middleware:** auth:sanctum  
**Admin Only:** No

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Bid withdrawn successfully"
}
```

**Error Responses:**
- **400:** Cannot withdraw bid (auction rules)

---

## Reviews

### POST /reviews

Create a review for an ad or seller.

**Middleware:** auth:sanctum, throttle:review  
**Admin Only:** No

**Request Body:**
```json
{
  "target_type": "ad",  // required, enum: ad|seller
  "target_id": 5,  // required, integer
  "stars": 5,  // required, integer, 1-5
  "title": "Great car!",  // optional, string
  "body": "Very satisfied with the purchase..."  // required, string
}
```

**Success Response (201):**
```json
{
  "status": "success",
  "message": "Review created successfully",
  "data": {
    "id": 1,
    "target_type": "ad",
    "target_id": 5,
    "stars": 5,
    "title": "Great car!",
    "body": "Very satisfied...",
    "created_at": "2024-01-16T10:00:00Z",
    "updated_at": "2024-01-16T10:00:00Z",
    "user": {
      "id": 2,
      "name": "John Doe",
      "profile_image": null
    },
    "permissions": {
      "can_edit": true,
      "can_delete": true
    }
  }
}
```

**Error Responses:**
- **422:** Validation failed
- **429:** Rate limit exceeded

---

### GET /reviews

List all reviews (public, with filters).

**Middleware:** Public  
**Admin Only:** No

**Query Parameters:**
- `target_type` - Filter by target type (ad|seller)
- `target_id` - Filter by target ID
- `stars` - Filter by star rating
- `page`, `per_page`

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Reviews retrieved successfully",
  "data": {
    "items": [ /* Review objects */ ],
    "pagination": { /* ... */ }
  }
}
```

---

### GET /ads/{ad}/reviews

List reviews for a specific ad (public).

**Middleware:** Public  
**Admin Only:** No

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Ad reviews retrieved successfully",
  "data": {
    "items": [ /* Review objects */ ],
    "pagination": { /* ... */ },
    "summary": {
      "average_rating": 4.5,
      "total_reviews": 25,
      "rating_distribution": {
        "5": 15,
        "4": 7,
        "3": 2,
        "2": 1,
        "1": 0
      }
    }
  }
}
```

---

### PUT /reviews/{review}

Update a review (owner or admin).

**Middleware:** auth:sanctum  
**Admin Only:** No

**Request Body:**
```json
{
  "stars": 4,  // optional
  "title": "Updated title",  // optional
  "body": "Updated review body"  // optional
}
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Review updated successfully",
  "data": { /* Updated review */ }
}
```

---

### DELETE /reviews/{review}

Delete a review (owner or admin).

**Middleware:** auth:sanctum  
**Admin Only:** No

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Review deleted successfully"
}
```

---

## Reports

### POST /reports

Create a report for inappropriate content.

**Middleware:** auth:sanctum, throttle:report  
**Admin Only:** No

**Request Body:**
```json
{
  "target_type": "ad",  // required, enum: ad|user|dealer
  "target_id": 5,  // required, integer
  "reason": "inappropriate_content",  // required, enum
  "title": "Spam advertisement",  // required, string
  "description": "This ad contains spam links..."  // optional, string
}
```

**Success Response (201):**
```json
{
  "status": "success",
  "message": "Report submitted successfully",
  "data": {
    "id": 1,
    "title": "Spam advertisement",
    "reason": "inappropriate_content",
    "target_type": "ad",
    "target_id": 5,
    "status": "open",
    "created_at": "2024-01-16T10:00:00Z"
  }
}
```

---

### GET /reports/my-reports

List authenticated user's submitted reports.

**Middleware:** auth:sanctum  
**Admin Only:** No

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Your reports retrieved successfully",
  "data": {
    "items": [ /* Report objects */ ],
    "pagination": { /* ... */ }
  }
}
```

---

### GET /reports/admin/index

List all reports with filters (admin/moderator only).

**Middleware:** auth:sanctum  
**Admin Only:** Yes

**Query Parameters:**
- `status` - Filter by status (open|under_review|resolved|closed)
- `target_type` - Filter by target type
- `assigned_to` - Filter by assigned moderator
- `page`, `per_page`

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Reports retrieved successfully",
  "data": {
    "items": [ /* Report objects with full details */ ],
    "pagination": { /* ... */ },
    "summary": {
      "total": 150,
      "open": 25,
      "under_review": 10,
      "resolved": 100,
      "closed": 15
    }
  }
}
```

---

### POST /reports/{report}/assign

Assign report to a moderator (admin only).

**Middleware:** auth:sanctum  
**Admin Only:** Yes

**Request Body:**
```json
{
  "assigned_to": 5  // required, user ID of moderator
}
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Report assigned successfully",
  "data": { /* Updated report */ }
}
```

---

### PUT /reports/{report}/status

Update report status (admin/assigned moderator).

**Middleware:** auth:sanctum  
**Admin Only:** Yes (or assigned moderator)

**Request Body:**
```json
{
  "status": "under_review",  // required, enum
  "notes": "Investigating the issue"  // optional
}
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Report status updated successfully",
  "data": { /* Updated report */ }
}
```

---

## Packages

### GET /packages

List all active packages (public).

**Middleware:** Public  
**Admin Only:** No

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Packages retrieved successfully",
  "data": {
    "items": [
      {
        "id": 1,
        "name": "Basic Package",
        "description": "Perfect for individuals",
        "price": 0.00,
        "price_formatted": "$0.00",
        "duration_days": 30,
        "duration_formatted": "1 month",
        "features": {
          "max_normal_ads": 5,
          "max_unique_ads": 0,
          "max_images_per_ad": 5,
          "max_videos_per_ad": 0,
          "can_create_caishha_ads": false,
          "can_create_auction_ads": false
        },
        "is_free": true,
        "active": true,
        "created_at": "2024-01-01T00:00:00Z",
        "updated_at": "2024-01-01T00:00:00Z"
      }
    ]
  }
}
```

---

### POST /packages/{package}/request

Request a package subscription.

**Middleware:** auth:sanctum  
**Admin Only:** No

**Request Body:**
```json
{
  "payment_method": "credit_card",  // optional
  "notes": "Please activate immediately"  // optional
}
```

**Success Response (201):**
```json
{
  "status": "success",
  "message": "Package request submitted successfully",
  "data": {
    "id": 1,
    "user_id": 2,
    "package_id": 3,
    "status": "pending",
    "requested_at": "2024-01-16T10:00:00Z"
  }
}
```

---

### GET /packages/my-packages

List authenticated user's active packages.

**Middleware:** auth:sanctum  
**Admin Only:** No

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Your packages retrieved successfully",
  "data": {
    "items": [
      {
        "id": 1,
        "package_id": 2,
        "package_name": "Premium Package",
        "active": true,
        "start_date": "2024-01-01T00:00:00Z",
        "end_date": "2024-02-01T00:00:00Z",
        "days_remaining": 15,
        "features": { /* ... */ }
      }
    ]
  }
}
```

---

### POST /packages/check-capability

Check if user can perform an action based on package limits.

**Middleware:** auth:sanctum  
**Admin Only:** No

**Request Body:**
```json
{
  "action": "create_normal_ad"  // required, enum
}
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Capability check completed",
  "data": {
    "allowed": true,
    "reason": null,
    "limit_info": {
      "allowed": 10,
      "used": 3,
      "remaining": 7
    }
  }
}
```

---

## Notifications

### GET /notifications

List authenticated user's notifications.

**Middleware:** auth:sanctum  
**Admin Only:** No

**Query Parameters:**
- `unread_only` - Show only unread notifications (boolean)
- `page`, `per_page`

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Notifications retrieved successfully",
  "data": {
    "items": [
      {
        "id": "uuid-1234",
        "type": "review_received",
        "type_raw": "ReviewReceivedNotification",
        "title": "New Review Received",
        "body": "Someone left a 5-star review on your ad",
        "data": {
          "ad_id": 5,
          "review_id": 10,
          "stars": 5
        },
        "read": false,
        "read_at": null,
        "created_at": "2024-01-16T10:00:00Z"
      }
    ],
    "pagination": { /* ... */ },
    "unread_count": 5
  }
}
```

---

### PATCH /notifications/{id}/read

Mark a notification as read.

**Middleware:** auth:sanctum  
**Admin Only:** No

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Notification marked as read"
}
```

---

### POST /notifications/read-all

Mark all notifications as read.

**Middleware:** auth:sanctum  
**Admin Only:** No

**Success Response (200):**
```json
{
  "status": "success",
  "message": "All notifications marked as read"
}
```

---

### DELETE /notifications/{id}

Delete a notification.

**Middleware:** auth:sanctum  
**Admin Only:** No

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Notification deleted successfully"
}
```

---

## Favorites

### GET /favorites

List authenticated user's favorite ads.

**Middleware:** auth:sanctum  
**Admin Only:** No

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Favorites retrieved successfully",
  "data": {
    "items": [
      {
        "id": 1,
        "user_id": 2,
        "ad_id": 5,
        "created_at": "2024-01-16T09:00:00Z",
        "ad": {
          /* Full ad object (NormalAd/UniqueAd/CaishhaAd/AuctionAd) */
        }
      }
    ],
    "pagination": { /* ... */ }
  }
}
```

---

### GET /favorites/count

Get favorite count.

**Middleware:** auth:sanctum  
**Admin Only:** No

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Favorite count retrieved successfully",
  "data": {
    "count": 15
  }
}
```

---

### GET /favorites/check/{ad}

Check if an ad is favorited.

**Middleware:** auth:sanctum  
**Admin Only:** No

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Favorite status retrieved",
  "data": {
    "is_favorited": true,
    "favorite_id": 5
  }
}
```

---

### POST /favorites/{ad}

Add ad to favorites.

**Middleware:** auth:sanctum  
**Admin Only:** No

**Success Response (201):**
```json
{
  "status": "success",
  "message": "Ad added to favorites",
  "data": {
    "id": 1,
    "user_id": 2,
    "ad_id": 5,
    "created_at": "2024-01-16T10:00:00Z"
  }
}
```

---

### POST /favorites/toggle/{ad}

Toggle favorite status (add or remove).

**Middleware:** auth:sanctum  
**Admin Only:** No

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Favorite toggled successfully",
  "data": {
    "is_favorited": true,
    "favorite_id": 5
  }
}
```

---

## Admin-Only Endpoints

### Brands

- **POST /brands** - Create brand
- **PUT /brands/{brand}** - Update brand
- **DELETE /brands/{brand}** - Delete brand
- **POST /brands/{brand}/models** - Add car model
- **PUT /brands/{brand}/models/{model}** - Update model
- **DELETE /brands/{brand}/models/{model}** - Delete model

### Specifications

- **GET /admin/specifications** - List specifications
- **POST /admin/specifications** - Create specification
- **PUT /admin/specifications/{specification}** - Update specification
- **DELETE /admin/specifications/{specification}** - Delete specification

### Categories

- **GET /admin/categories** - List categories
- **POST /admin/categories** - Create category
- **PUT /admin/categories/{category}** - Update category
- **DELETE /admin/categories/{category}** - Delete category
- **POST /admin/categories/{category}/specifications/assign** - Assign specifications

### Sliders

- **POST /admin/sliders** - Create slider
- **PUT /admin/sliders/{slider}** - Update slider
- **DELETE /admin/sliders/{slider}** - Delete slider
- **POST /admin/sliders/{slider}/activate** - Activate slider
- **POST /admin/sliders/{slider}/deactivate** - Deactivate slider

### Blog

- **GET /admin/blogs** - List all blogs (including draft)
- **POST /admin/blogs** - Create blog post
- **PUT /admin/blogs/{blog}** - Update blog post
- **DELETE /admin/blogs/{blog}** - Delete blog post

### Statistics

- **GET /admin/stats/dashboard** - Overall platform statistics
- **GET /admin/stats/ads/{ad}/views** - Ad view count
- **GET /admin/stats/dealer/{user}** - Dealer statistics
- **GET /admin/stats/user/{user}** - User statistics

### Audit Logs

- **GET /admin/audit-logs** - List all audit logs with filters
- **GET /admin/audit-logs/stats** - Audit log statistics
- **GET /admin/audit-logs/{audit_log}** - View specific audit log

All admin endpoints return standardized success/error envelopes as documented above.

---

## Rate Limiting

Certain endpoints have rate limits to prevent abuse:

- **POST /reviews** - `throttle:review` (e.g., 5 per minute)
- **POST /reports** - `throttle:report` (e.g., 3 per minute)

Rate limit exceeded response (429):
```json
{
  "status": "error",
  "code": 429,
  "message": "Too many requests. Please try again later.",
  "errors": {
    "rate_limit": {
      "retry_after": 60,  // seconds
      "limit": 5,
      "remaining": 0
    }
  }
}
```

---

## Pagination

All paginated endpoints support these query parameters:

- `page` - Page number (default: 1)
- `per_page` or `limit` - Items per page (default: 15, max: 50)

Response pagination object:
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

## Changelog

### Version 1.0.0 (February 10, 2026)

**Standardization Changes:**
- ✅ All listing endpoints now return consistent paginated envelope
- ✅ All error responses follow standard error envelope structure
- ✅ Package limit errors include `limit_info` nested in `errors` object
- ✅ All show endpoints include `message` field in success response
- ✅ ResourceCollection responses wrapped in standard envelope

**Breaking Changes:**
- Listing endpoints (e.g., `/normal-ads`, `/unique-ads`) changed from Laravel ResourceCollection format to standardized envelope
- Package limit error `remaining` field moved from top-level to `errors.limit_info.remaining`
- All responses now consistently include `status` and `message` fields

---

## Migration Guide

See [API_MIGRATION_PLAN.md](./API_MIGRATION_PLAN.md) for detailed migration instructions and code changes required.
