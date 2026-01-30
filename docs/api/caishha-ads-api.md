# Caishha Ads API - Curl Examples

This document provides comprehensive curl examples for all Caishha Ads API endpoints.

## Base URL
```
http://localhost:8000/api/v1
```

## Authentication
Most endpoints require authentication via Bearer token. Get a token by logging in:
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "user@example.com", "password": "password123"}'
```

Response:
```json
{
  "status": "success",
  "message": "Login successful",
  "data": {
    "user": { ... },
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
  }
}
```

---

## Public Endpoints (No Authentication Required)

### List Published Caishha Ads
```bash
curl -X GET "http://localhost:8000/api/v1/caishha-ads" \
  -H "Accept: application/json"
```

**With Filters:**
```bash
# Filter by brand
curl -X GET "http://localhost:8000/api/v1/caishha-ads?brand_id=1" \
  -H "Accept: application/json"

# Filter by city
curl -X GET "http://localhost:8000/api/v1/caishha-ads?city_id=2" \
  -H "Accept: application/json"

# Filter by year range
curl -X GET "http://localhost:8000/api/v1/caishha-ads?min_year=2020&max_year=2024" \
  -H "Accept: application/json"

# Search by keyword
curl -X GET "http://localhost:8000/api/v1/caishha-ads?search=BMW" \
  -H "Accept: application/json"

# Filter by window status (dealer_window or open)
curl -X GET "http://localhost:8000/api/v1/caishha-ads?window_status=dealer_window" \
  -H "Accept: application/json"

# Pagination and sorting
curl -X GET "http://localhost:8000/api/v1/caishha-ads?limit=10&sort_by=created_at&sort_direction=desc" \
  -H "Accept: application/json"
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "type": "caishha",
      "title": "2023 Mercedes C-Class for Caishha Auction",
      "description": "Excellent condition...",
      "status": "published",
      "views_count": 150,
      "offers_count": 5,
      "offers_window_period": 129600,
      "sellers_visibility_period": 129600,
      "window_status": {
        "is_in_dealer_window": false,
        "is_in_individual_window": true,
        "are_offers_visible_to_seller": true,
        "can_accept_offers": true,
        "dealer_window_ends_at": "2024-01-15T12:00:00.000000Z",
        "visibility_period_ends_at": "2024-01-15T12:00:00.000000Z"
      },
      "user": {
        "id": 5,
        "name": "John Seller"
      },
      "brand": {
        "id": 3,
        "name": "Mercedes"
      },
      "model": {
        "id": 12,
        "name": "C-Class"
      },
      "created_at": "2024-01-14T00:00:00.000000Z"
    }
  ],
  "links": { ... },
  "meta": { ... }
}
```

### View Single Caishha Ad
```bash
curl -X GET "http://localhost:8000/api/v1/caishha-ads/1" \
  -H "Accept: application/json"
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "type": "caishha",
    "title": "2023 Mercedes C-Class for Caishha Auction",
    "description": "Excellent condition, full service history...",
    "status": "published",
    "views_count": 151,
    "offers_count": 5,
    "window_status": {
      "is_in_dealer_window": false,
      "is_in_individual_window": true,
      "are_offers_visible_to_seller": true,
      "can_accept_offers": true
    },
    "media": [
      { "id": 1, "url": "...", "type": "image" }
    ]
  }
}
```

---

## Authenticated Endpoints

### Create Caishha Ad
```bash
curl -X POST "http://localhost:8000/api/v1/caishha-ads" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "title": "2023 BMW X5 for Caishha Auction",
    "description": "Pristine condition, low mileage, full dealer service history.",
    "category_id": 1,
    "brand_id": 2,
    "model_id": 5,
    "city_id": 1,
    "country_id": 1,
    "year": 2023,
    "offers_window_period": 129600,
    "sellers_visibility_period": 129600,
    "contact_phone": "+971501234567",
    "whatsapp_number": "+971501234567",
    "media_ids": [1, 2, 3]
  }'
```

**Response (201 Created):**
```json
{
  "status": "success",
  "message": "Caishha ad created successfully",
  "data": {
    "id": 10,
    "type": "caishha",
    "title": "2023 BMW X5 for Caishha Auction",
    "status": "published",
    "offers_count": 0,
    "offers_window_period": 129600,
    "sellers_visibility_period": 129600,
    "window_status": {
      "is_in_dealer_window": true,
      "is_in_individual_window": false,
      "are_offers_visible_to_seller": false
    }
  }
}
```

### Update Caishha Ad
```bash
curl -X PUT "http://localhost:8000/api/v1/caishha-ads/10" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "title": "2023 BMW X5 xDrive - Updated Title",
    "description": "Updated description with more details..."
  }'
```

**Response:**
```json
{
  "status": "success",
  "message": "Ad updated successfully",
  "data": { ... }
}
```

### Delete Caishha Ad
```bash
curl -X DELETE "http://localhost:8000/api/v1/caishha-ads/10" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response:**
```json
{
  "status": "success",
  "message": "Ad deleted successfully"
}
```

---

## Lifecycle Actions

### Publish Ad
```bash
curl -X POST "http://localhost:8000/api/v1/caishha-ads/10/actions/publish" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Unpublish Ad (Set to Draft)
```bash
curl -X POST "http://localhost:8000/api/v1/caishha-ads/10/actions/unpublish" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Expire Ad
```bash
curl -X POST "http://localhost:8000/api/v1/caishha-ads/10/actions/expire" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Archive Ad
```bash
curl -X POST "http://localhost:8000/api/v1/caishha-ads/10/actions/archive" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Restore Ad
```bash
curl -X POST "http://localhost:8000/api/v1/caishha-ads/10/actions/restore" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## User's Own Ads

### List My Caishha Ads
```bash
curl -X GET "http://localhost:8000/api/v1/caishha-ads/my-ads" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**With Filters:**
```bash
# Filter by status
curl -X GET "http://localhost:8000/api/v1/caishha-ads/my-ads?status=published" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## Offers Management

### Submit Offer (Dealer during dealer window, or anyone after)
```bash
curl -X POST "http://localhost:8000/api/v1/caishha-ads/1/offers" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer DEALER_TOKEN" \
  -d '{
    "price": 45000,
    "comment": "Interested in purchasing. Can inspect immediately."
  }'
```

**Response (201 Created):**
```json
{
  "status": "success",
  "message": "Offer submitted successfully",
  "data": {
    "id": 15,
    "ad_id": 1,
    "user_id": 8,
    "price": 45000.00,
    "comment": "Interested in purchasing. Can inspect immediately.",
    "status": "pending",
    "is_visible_to_seller": false,
    "created_at": "2024-01-15T10:30:00.000000Z"
  }
}
```

**Error Response (During dealer window for non-dealer):**
```json
{
  "status": "error",
  "code": 403,
  "message": "Not authorized to submit offer",
  "errors": {
    "window": ["Only dealers can submit offers during the exclusive dealer window"],
    "dealer_window_ends_at": "2024-01-15T12:00:00.000000Z"
  }
}
```

### List Offers on Ad (Owner or Admin Only)
```bash
# After visibility period has passed (for owner)
curl -X GET "http://localhost:8000/api/v1/caishha-ads/1/offers" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer OWNER_TOKEN"
```

**With Filters:**
```bash
# Filter by status
curl -X GET "http://localhost:8000/api/v1/caishha-ads/1/offers?status=pending" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer OWNER_TOKEN"

# Sort by price
curl -X GET "http://localhost:8000/api/v1/caishha-ads/1/offers?sort_by=price&sort_direction=desc" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer OWNER_TOKEN"
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "data": [
      {
        "id": 15,
        "ad_id": 1,
        "price": 48000.00,
        "comment": "Best offer!",
        "status": "pending",
        "user": {
          "id": 8,
          "name": "Premium Dealer",
          "account_type": "dealer"
        },
        "created_at": "2024-01-15T10:30:00.000000Z"
      },
      {
        "id": 14,
        "ad_id": 1,
        "price": 45000.00,
        "status": "pending",
        "user": {
          "id": 9,
          "name": "Another Buyer"
        }
      }
    ],
    "links": { ... },
    "meta": { ... }
  }
}
```

**Error (Before Visibility Period):**
```json
{
  "status": "error",
  "code": 403,
  "message": "Offers not yet visible",
  "errors": {
    "visibility": ["Offers will be visible after the visibility period ends"],
    "visibility_ends_at": "2024-01-15T12:00:00.000000Z"
  }
}
```

### Accept Offer
```bash
curl -X POST "http://localhost:8000/api/v1/caishha-ads/1/offers/15/accept" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer OWNER_TOKEN"
```

**Response:**
```json
{
  "status": "success",
  "message": "Offer accepted successfully",
  "data": {
    "id": 15,
    "ad_id": 1,
    "price": 48000.00,
    "status": "accepted",
    "is_visible_to_seller": true,
    "user": { ... }
  }
}
```

### Reject Offer
```bash
curl -X POST "http://localhost:8000/api/v1/caishha-ads/1/offers/14/reject" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer OWNER_TOKEN"
```

**Response:**
```json
{
  "status": "success",
  "message": "Offer rejected successfully",
  "data": {
    "id": 14,
    "status": "rejected"
  }
}
```

### View My Submitted Offers
```bash
curl -X GET "http://localhost:8000/api/v1/caishha-offers/my-offers" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**With Filters:**
```bash
# Filter by status
curl -X GET "http://localhost:8000/api/v1/caishha-offers/my-offers?status=accepted" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "data": [
      {
        "id": 15,
        "ad_id": 1,
        "price": 48000.00,
        "status": "accepted",
        "ad": {
          "id": 1,
          "title": "2023 Mercedes C-Class",
          "status": "published",
          "brand": { "id": 3, "name": "Mercedes" },
          "model": { "id": 12, "name": "C-Class" }
        },
        "created_at": "2024-01-15T10:30:00.000000Z"
      }
    ]
  }
}
```

---

## Admin Endpoints

### Admin: List All Caishha Ads
```bash
curl -X GET "http://localhost:8000/api/v1/caishha-ads/admin" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer ADMIN_TOKEN"
```

**With Filters:**
```bash
# Filter by status
curl -X GET "http://localhost:8000/api/v1/caishha-ads/admin?status=pending" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer ADMIN_TOKEN"

# Filter by user
curl -X GET "http://localhost:8000/api/v1/caishha-ads/admin?user_id=5" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer ADMIN_TOKEN"
```

### Admin: Global Statistics
```bash
curl -X GET "http://localhost:8000/api/v1/caishha-ads/stats" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer ADMIN_TOKEN"
```

**Response:**
```json
{
  "status": "success",
  "message": "Global statistics retrieved successfully",
  "data": {
    "total_ads": 150,
    "published_ads": 120,
    "draft_ads": 15,
    "pending_ads": 5,
    "expired_ads": 8,
    "removed_ads": 2,
    "total_views": 15000,
    "total_offers": 450,
    "pending_offers": 200,
    "accepted_offers": 100,
    "rejected_offers": 150,
    "ads_today": 5,
    "ads_this_week": 25,
    "ads_this_month": 80
  }
}
```

### Admin: Bulk Actions
```bash
# Bulk publish
curl -X POST "http://localhost:8000/api/v1/caishha-ads/actions/bulk" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -d '{
    "action": "publish",
    "ad_ids": [1, 2, 3, 4, 5]
  }'

# Bulk archive
curl -X POST "http://localhost:8000/api/v1/caishha-ads/actions/bulk" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -d '{
    "action": "archive",
    "ad_ids": [10, 11, 12]
  }'

# Bulk delete
curl -X POST "http://localhost:8000/api/v1/caishha-ads/actions/bulk" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -d '{
    "action": "delete",
    "ad_ids": [20, 21]
  }'
```

**Supported Actions:** `publish`, `unpublish`, `expire`, `archive`, `restore`, `delete`

**Response:**
```json
{
  "status": "success",
  "message": "Bulk publish completed successfully",
  "data": {
    "affected_count": 5
  }
}
```

---

## Error Responses

### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

### 403 Forbidden
```json
{
  "status": "error",
  "code": 403,
  "message": "Unauthorized",
  "errors": {
    "authorization": ["You do not have permission to perform this action"]
  }
}
```

### 404 Not Found
```json
{
  "status": "error",
  "code": 404,
  "message": "Ad not found",
  "errors": {
    "ad": ["The requested Caishha ad does not exist"]
  }
}
```

### 422 Validation Error
```json
{
  "status": "error",
  "code": 422,
  "message": "Validation failed",
  "errors": {
    "title": ["The ad title must be at least 5 characters."],
    "description": ["The ad description is required."],
    "price": ["The offer price must be greater than 0."]
  }
}
```

### 500 Server Error
```json
{
  "status": "error",
  "code": 500,
  "message": "Failed to create ad",
  "errors": {
    "general": ["An unexpected error occurred"]
  }
}
```

---

## Window Timing Logic

### Dealer Window (36 hours by default)
- **First 36 hours after publishing:** Only dealers/showrooms can submit offers
- **After 36 hours:** Anyone can submit offers

### Visibility Period (36 hours by default)
- **First 36 hours after publishing:** Seller cannot see offers (to prevent bias)
- **After 36 hours:** Seller can view and act on offers

### Custom Window Configuration
You can customize window periods when creating an ad:
```json
{
  "offers_window_period": 43200,         // 12 hours (min: 3600, max: 604800)
  "sellers_visibility_period": 86400     // 24 hours (min: 0, max: 604800)
}
```

---

## Notes

1. **Media IDs:** Upload media first using `/api/v1/media` endpoint, then reference the IDs when creating ads
2. **Offer Submission:** A user can only submit one offer per ad
3. **Accepting Offers:** When an offer is accepted, all other pending offers are automatically rejected
4. **Admin Override:** Admins can view offers anytime (bypassing visibility period)
5. **Window Period Updates:** Cannot change window periods after offers have been submitted
