# FindIt Ads API Documentation

## Overview

The FindIt Ads feature allows users to create "wanted" requests for vehicles they're looking for. Dealers and showrooms can then submit offers matching those requests, and the system automatically finds matching ads.

**Base URL:** `/api/v1/findit-ads`

**Authentication:** All endpoints require Bearer token authentication via Laravel Sanctum.

---

## Endpoints Summary

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/findit-ads` | Create a new FindIt request |
| `GET` | `/findit-ads/my-requests` | List user's own requests |
| `GET` | `/findit-ads/admin` | List all requests (admin only) |
| `GET` | `/findit-ads/stats` | Get user's FindIt statistics |
| `GET` | `/findit-ads/{id}` | Get request details |
| `PUT` | `/findit-ads/{id}` | Update a request |
| `DELETE` | `/findit-ads/{id}` | Delete a request |
| `POST` | `/findit-ads/{id}/activate` | Activate a draft request |
| `POST` | `/findit-ads/{id}/close` | Close an active request |
| `POST` | `/findit-ads/{id}/offers` | Submit an offer |
| `GET` | `/findit-ads/{id}/offers` | List offers for a request |
| `PUT` | `/findit-ads/{id}/offers/{offerId}/accept` | Accept an offer |
| `PUT` | `/findit-ads/{id}/offers/{offerId}/reject` | Reject an offer |
| `DELETE` | `/findit-offers/{offerId}` | Withdraw an offer |
| `GET` | `/findit-ads/{id}/matches` | List matching ads |
| `POST` | `/findit-ads/{id}/matches/{matchId}/dismiss` | Dismiss a match |
| `POST` | `/findit-ads/{id}/refresh-matches` | Trigger matching process |

---

## FindIt Request Management

### Create FindIt Request

Create a new FindIt request (starts as draft).

**Endpoint:** `POST /api/v1/findit-ads`

**Request Body:**

```json
{
  "title": "Looking for Toyota Camry 2020-2023",
  "description": "Need a reliable family sedan, preferably white or silver",
  "brand_id": 1,
  "model_id": 5,
  "category_id": null,
  "min_price": 15000,
  "max_price": 25000,
  "min_year": 2020,
  "max_year": 2023,
  "min_mileage": null,
  "max_mileage": 50000,
  "city_id": 1,
  "country_id": 1,
  "transmission": "automatic",
  "fuel_type": "petrol",
  "body_type": "sedan",
  "color": "white",
  "condition": "used",
  "expires_in_days": 30,
  "media_ids": [1, 2, 3]
}
```

**Required Fields:**
- `title` (string, 3-200 characters)

**Optional Fields:**
- `description` (string, max 2000 characters)
- `brand_id` (integer, must exist in brands table)
- `model_id` (integer, must exist in models table)
- `category_id` (integer, must exist in categories table)
- `min_price` / `max_price` (numeric, min: 0)
- `min_year` / `max_year` (integer, 1900-2100)
- `min_mileage` / `max_mileage` (integer, min: 0)
- `city_id` (integer, must exist in cities table)
- `country_id` (integer, must exist in countries table)
- `transmission` (enum: automatic, manual, cvt, dct)
- `fuel_type` (enum: petrol, diesel, hybrid, electric, lpg, cng)
- `body_type` (enum: sedan, suv, hatchback, coupe, convertible, wagon, van, truck, pickup)
- `color` (string, max 50 characters)
- `condition` (enum: new, used, certified)
- `expires_in_days` (integer, 1-90, default: 30)
- `media_ids` (array of media IDs)

**Response (201 Created):**

```json
{
  "success": true,
  "message": "FindIt request created as draft.",
  "data": {
    "id": 1,
    "title": "Looking for Toyota Camry 2020-2023",
    "description": "Need a reliable family sedan...",
    "status": "draft",
    "status_label": "Draft",
    "user_id": 1,
    "brand": { "id": 1, "name": "Toyota", "logo": null },
    "brand_id": 1,
    "model": { "id": 5, "name": "Camry" },
    "model_id": 5,
    "min_price": "15000.00",
    "max_price": "25000.00",
    "price_range": "15,000 - 25,000",
    "min_year": 2020,
    "max_year": 2023,
    "year_range": "2020 - 2023",
    "expires_at": "2026-03-02T00:00:00+00:00",
    "expires_in": "1 month from now",
    "is_expired": false,
    "can_accept_offers": false,
    "is_active": false,
    "permissions": {
      "can_view": true,
      "can_edit": true,
      "can_delete": true,
      "can_close": false,
      "can_activate": true,
      "can_submit_offer": false
    },
    "created_at": "2026-01-31T15:00:00+00:00",
    "updated_at": "2026-01-31T15:00:00+00:00"
  }
}
```

---

### List My Requests

Get all FindIt requests created by the authenticated user.

**Endpoint:** `GET /api/v1/findit-ads/my-requests`

**Query Parameters:**
- `status` (optional): Filter by status (draft, active, closed, expired)
- `search` (optional): Search in title and description
- `sort_by` (optional): Sort field (created_at, expires_at, offers_count, matches_count)
- `sort_order` (optional): asc or desc (default: desc)
- `per_page` (optional): Items per page (default: 15, max: 50)

**Response (200 OK):**

```json
{
  "data": [
    {
      "id": 1,
      "title": "Looking for Toyota Camry",
      "status": "active",
      "offers_count": 3,
      "matches_count": 12,
      "expires_at": "2026-03-02T00:00:00+00:00",
      ...
    }
  ],
  "links": {
    "first": "http://localhost:8000/api/v1/findit-ads/my-requests?page=1",
    "last": "http://localhost:8000/api/v1/findit-ads/my-requests?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "per_page": 15,
    "to": 1,
    "total": 1
  }
}
```

---

### Get Request Details

Get details of a specific FindIt request.

**Endpoint:** `GET /api/v1/findit-ads/{id}`

**Authorization:** User must own the request or be an admin.

**Response (200 OK):**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Looking for Toyota Camry",
    "user": {
      "id": 1,
      "name": "John Doe",
      "profile_image": null
    },
    "offers": [...],
    "matches": [...],
    ...
  }
}
```

---

### Update Request

Update an existing FindIt request.

**Endpoint:** `PUT /api/v1/findit-ads/{id}`

**Authorization:** User must own the request.

**Request Body:** Same fields as create (all optional).

**Response (200 OK):**

```json
{
  "success": true,
  "message": "FindIt request updated successfully.",
  "data": { ... }
}
```

---

### Delete Request

Soft delete a FindIt request.

**Endpoint:** `DELETE /api/v1/findit-ads/{id}`

**Authorization:** User must own the request.

**Response (200 OK):**

```json
{
  "success": true,
  "message": "FindIt request deleted successfully."
}
```

---

### Activate Request

Activate a draft request to start receiving offers and matches.

**Endpoint:** `POST /api/v1/findit-ads/{id}/activate`

**Authorization:** User must own the request. Request must be in draft status.

**Response (200 OK):**

```json
{
  "success": true,
  "message": "FindIt request activated successfully.",
  "data": {
    "status": "active",
    ...
  }
}
```

**Error Response (422):**

```json
{
  "success": false,
  "message": "Only draft requests can be activated."
}
```

---

### Close Request

Close an active request to stop receiving offers.

**Endpoint:** `POST /api/v1/findit-ads/{id}/close`

**Authorization:** User must own the request. Request must be in active status.

**Response (200 OK):**

```json
{
  "success": true,
  "message": "FindIt request closed successfully.",
  "data": {
    "status": "closed",
    ...
  }
}
```

---

## Offer Management

### Submit Offer

Submit an offer on a FindIt request (dealers/showrooms only).

**Endpoint:** `POST /api/v1/findit-ads/{id}/offers`

**Authorization:** User must be a dealer, showroom, or marketer. Cannot submit on own request.

**Request Body:**

```json
{
  "price": 18000,
  "message": "I have a perfect 2021 Toyota Camry with low mileage",
  "ad_id": 123,
  "contact_phone": "+1234567890"
}
```

**Required Fields:**
- `price` (numeric, min: 0)

**Optional Fields:**
- `message` (string, max 1000 characters)
- `ad_id` (integer, must be an active ad owned by the offerer)
- `contact_phone` (string, max 20 characters)

**Response (201 Created):**

```json
{
  "success": true,
  "message": "Offer submitted successfully.",
  "data": {
    "id": 1,
    "findit_request_id": 1,
    "price": "18000.00",
    "formatted_price": "18,000",
    "status": "pending",
    "status_label": "Pending",
    "user": {
      "id": 2,
      "name": "Car Dealer",
      "is_verified": true
    },
    "is_pending": true,
    "time_since": "just now",
    "permissions": {
      "can_accept": false,
      "can_reject": false,
      "can_withdraw": true,
      "can_view_contact": false
    }
  }
}
```

---

### List Offers

List all offers for a FindIt request.

**Endpoint:** `GET /api/v1/findit-ads/{id}/offers`

**Authorization:** User must own the request.

**Query Parameters:**
- `status` (optional): Filter by status (pending, accepted, rejected, withdrawn)
- `per_page` (optional): Items per page (default: 15)

**Response (200 OK):**

```json
{
  "data": [
    {
      "id": 1,
      "price": "18000.00",
      "status": "pending",
      "user": { ... },
      ...
    }
  ],
  "links": { ... },
  "meta": { ... }
}
```

---

### Accept Offer

Accept a pending offer.

**Endpoint:** `PUT /api/v1/findit-ads/{id}/offers/{offerId}/accept`

**Authorization:** User must own the request.

**Response (200 OK):**

```json
{
  "success": true,
  "message": "Offer accepted successfully.",
  "data": {
    "status": "accepted",
    "responded_at": "2026-01-31T16:00:00+00:00",
    ...
  }
}
```

---

### Reject Offer

Reject a pending offer.

**Endpoint:** `PUT /api/v1/findit-ads/{id}/offers/{offerId}/reject`

**Authorization:** User must own the request.

**Response (200 OK):**

```json
{
  "success": true,
  "message": "Offer rejected successfully.",
  "data": {
    "status": "rejected",
    ...
  }
}
```

---

### Withdraw Offer

Withdraw own pending offer.

**Endpoint:** `DELETE /api/v1/findit-offers/{offerId}`

**Authorization:** User must own the offer. Offer must be pending.

**Response (200 OK):**

```json
{
  "success": true,
  "message": "Offer withdrawn successfully.",
  "data": {
    "status": "withdrawn",
    ...
  }
}
```

---

## Matching System

### List Matches

Get all matched ads for a FindIt request.

**Endpoint:** `GET /api/v1/findit-ads/{id}/matches`

**Authorization:** User must own the request.

**Query Parameters:**
- `status` (optional): Filter by status (new, viewed, contacted, dismissed)
- `min_score` (optional): Minimum match score (0-100)
- `per_page` (optional): Items per page (default: 20)

**Response (200 OK):**

```json
{
  "data": [
    {
      "id": 1,
      "findit_request_id": 1,
      "ad_id": 123,
      "ad": {
        "id": 123,
        "title": "2021 Toyota Camry SE",
        "price": 17500,
        "year": 2021,
        ...
      },
      "score": 85,
      "score_label": "Excellent Match",
      "status": "new",
      "matched_at": "2026-01-31T15:00:00+00:00",
      "permissions": {
        "can_dismiss": true,
        "can_contact": true
      }
    }
  ],
  "links": { ... },
  "meta": { ... }
}
```

---

### Dismiss Match

Mark a match as dismissed (won't appear in results).

**Endpoint:** `POST /api/v1/findit-ads/{id}/matches/{matchId}/dismiss`

**Authorization:** User must own the request.

**Response (200 OK):**

```json
{
  "success": true,
  "message": "Match dismissed successfully."
}
```

---

### Refresh Matches

Trigger the matching algorithm to find new matches.

**Endpoint:** `POST /api/v1/findit-ads/{id}/refresh-matches`

**Authorization:** User must own the request. Request must be active.

**Response (200 OK):**

```json
{
  "success": true,
  "message": "Found 5 new matches.",
  "new_matches_count": 5,
  "total_matches_count": 17
}
```

---

## Statistics

### Get Statistics

Get user's FindIt statistics.

**Endpoint:** `GET /api/v1/findit-ads/stats`

**Response (200 OK):**

```json
{
  "success": true,
  "data": {
    "total_requests": 10,
    "active_requests": 5,
    "draft_requests": 2,
    "closed_requests": 3,
    "expired_requests": 0,
    "total_offers_received": 25,
    "pending_offers": 8,
    "total_matches": 150,
    "offers_submitted": 12,
    "offers_accepted": 3
  }
}
```

---

## Request Status Flow

```
draft → active → closed
         ↓
       expired (automatic)
```

- **draft**: Initial state, not visible to dealers
- **active**: Accepting offers and finding matches
- **closed**: Manually closed by user
- **expired**: Automatically set when expires_at is reached

---

## Offer Status Flow

```
pending → accepted
    ↓
 rejected
    ↓
withdrawn (by offerer)
```

---

## Match Status Flow

```
new → viewed → contacted
 ↓
dismissed
```

---

## Matching Score

The matching algorithm calculates a score from 0-100 based on:

| Criteria | Weight | Description |
|----------|--------|-------------|
| Brand | 30 | Exact brand match |
| Model | 25 | Exact model match |
| Price | 20 | Price within requested range |
| Year | 15 | Year within requested range |
| City | 10 | Same city location |

**Score Labels:**
- 80-100: Excellent Match
- 60-79: Good Match
- 40-59: Fair Match
- 30-39: Possible Match

Matches below 30% score are not shown.

---

## Console Command

For cron-based matching:

```bash
# Process all active requests
php artisan findit:process-matches

# Dry run (preview without changes)
php artisan findit:process-matches --dry-run

# Process specific request
php artisan findit:process-matches --request=1

# Cleanup invalid matches
php artisan findit:process-matches --cleanup
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
  "message": "This action is unauthorized.",
  "errors": {}
}
```

### 404 Not Found

```json
{
  "success": false,
  "message": "FindIt request not found."
}
```

### 422 Unprocessable Entity

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "title": ["The title field is required."],
    "max_price": ["The max price must be greater than min price."]
  }
}
```

---

## cURL Examples

### Create Request

```bash
curl -X POST http://localhost:8000/api/v1/findit-ads \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "title": "Looking for Toyota Camry",
    "min_price": 15000,
    "max_price": 25000,
    "min_year": 2020,
    "max_year": 2023
  }'
```

### Activate Request

```bash
curl -X POST http://localhost:8000/api/v1/findit-ads/1/activate \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

### Submit Offer (as dealer)

```bash
curl -X POST http://localhost:8000/api/v1/findit-ads/1/offers \
  -H "Authorization: Bearer {dealer_token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "price": 18000,
    "message": "I have a perfect match for you"
  }'
```

### Accept Offer

```bash
curl -X PUT http://localhost:8000/api/v1/findit-ads/1/offers/1/accept \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

### Refresh Matches

```bash
curl -X POST http://localhost:8000/api/v1/findit-ads/1/refresh-matches \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```
