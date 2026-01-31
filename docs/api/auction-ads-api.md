# Auction Ads API Documentation

## Overview

The Auction Ads API provides endpoints for creating, managing, and bidding on auction-style advertisements. This API supports:

- Creating auction ads with configurable settings
- Placing bids with anti-sniping protection
- Auction lifecycle management (publish, close, cancel)
- Reserve price functionality
- Owner and admin controls

## Base URL

```
/api/v1
```

## Authentication

All endpoints except public listing and viewing require Bearer token authentication via Laravel Sanctum.

```bash
Authorization: Bearer {your_token}
```

---

## Public Endpoints

### List All Auction Ads

Get a paginated list of published auction ads.

```http
GET /auction-ads
```

#### Query Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `brand_id` | integer | Filter by brand ID |
| `model_id` | integer | Filter by model ID |
| `city_id` | integer | Filter by city ID |
| `country_id` | integer | Filter by country ID |
| `min_year` | integer | Filter by minimum year |
| `max_year` | integer | Filter by maximum year |
| `min_price` | numeric | Filter by minimum current price |
| `max_price` | numeric | Filter by maximum current price |
| `search` | string | Search in title and description |
| `auction_status` | string | Filter by auction status: `active`, `upcoming`, `ended`, `ending_soon` |
| `sort_by` | string | Sort field: `created_at`, `updated_at`, `views_count`, `title`, `end_time`, `bid_count` |
| `sort_direction` | string | Sort direction: `asc`, `desc` |
| `limit` | integer | Items per page (max: 50, default: 15) |

#### Example Request

```bash
curl -X GET "http://localhost:8000/api/v1/auction-ads?auction_status=active&sort_by=end_time&sort_direction=asc&limit=10" \
  -H "Accept: application/json"
```

#### Example Response

```json
{
  "data": [
    {
      "id": 1,
      "title": "2020 Toyota Camry",
      "description": "Low mileage, excellent condition",
      "type": "auction",
      "status": "published",
      "views_count": 150,
      "user": {
        "id": 5,
        "name": "John Seller"
      },
      "brand": {
        "id": 1,
        "name": "Toyota"
      },
      "model": {
        "id": 10,
        "name": "Camry"
      },
      "auction": {
        "start_price": 15000,
        "current_price": 18500,
        "bid_count": 12,
        "start_time": "2024-01-15T10:00:00Z",
        "end_time": "2024-01-22T10:00:00Z",
        "status": "active",
        "is_last_price_visible": true,
        "time_remaining": {
          "days": 3,
          "hours": 5,
          "minutes": 30,
          "seconds": 15,
          "total_seconds": 278415
        },
        "auction_state": {
          "is_active": true,
          "is_started": true,
          "is_ended": false,
          "can_accept_bids": true
        }
      },
      "media": [],
      "created_at": "2024-01-14T08:00:00Z"
    }
  ],
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  },
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 10,
    "total": 47
  }
}
```

---

### List Auctions by User

Get all published auctions from a specific user.

```http
GET /users/{userId}/auction-ads
```

#### Query Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `auction_status` | string | Filter by auction status: `active`, `upcoming`, `ended`, `closed` |
| `sort_by` | string | Sort field: `created_at`, `updated_at`, `views_count`, `title`, `end_time` |
| `sort_direction` | string | Sort direction: `asc`, `desc` |
| `limit` | integer | Items per page (max: 50, default: 15) |

#### Example Request

```bash
curl -X GET "http://localhost:8000/api/v1/users/5/auction-ads?auction_status=active" \
  -H "Accept: application/json"
```

---

### Get Single Auction Ad

Get detailed information about a specific auction.

```http
GET /auction-ads/{id}
```

#### Example Request

```bash
curl -X GET "http://localhost:8000/api/v1/auction-ads/1" \
  -H "Accept: application/json"
```

#### Example Response

```json
{
  "status": "success",
  "data": {
    "id": 1,
    "title": "2020 Toyota Camry",
    "description": "Low mileage, excellent condition...",
    "type": "auction",
    "auction": {
      "start_price": 15000,
      "current_price": 18500,
      "minimum_next_bid": 18600,
      "bid_count": 12,
      "start_time": "2024-01-15T10:00:00Z",
      "end_time": "2024-01-22T10:00:00Z",
      "status": "active",
      "is_last_price_visible": true,
      "time_remaining": {
        "days": 3,
        "hours": 5,
        "minutes": 30,
        "seconds": 15,
        "total_seconds": 278415
      }
    }
  }
}
```

---

## Authenticated Endpoints

### Create Auction Ad

Create a new auction advertisement.

```http
POST /auction-ads
```

#### Request Body

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `title` | string | Yes | Ad title (max 255 chars) |
| `description` | string | Yes | Ad description |
| `category_id` | integer | Yes | Category ID |
| `brand_id` | integer | Yes | Brand ID |
| `model_id` | integer | Yes | Model ID |
| `city_id` | integer | Yes | City ID |
| `country_id` | integer | Yes | Country ID |
| `year` | integer | Yes | Vehicle year (1900-current+1) |
| `start_price` | numeric | No | Starting price (default: 0) |
| `reserve_price` | numeric | No | Reserve price (must be >= start_price) |
| `start_time` | datetime | Yes | Auction start time (ISO 8601) |
| `end_time` | datetime | Yes | Auction end time (1 hour - 30 days after start) |
| `minimum_bid_increment` | integer | No | Minimum bid increment (default: 100) |
| `is_last_price_visible` | boolean | No | Show current price publicly (default: true) |
| `anti_snip_window_seconds` | integer | No | Anti-snipe window in seconds (default: 300) |
| `anti_snip_extension_seconds` | integer | No | Extension when anti-snipe triggers (default: 300) |
| `contact_phone` | string | No | Contact phone number |
| `whatsapp_number` | string | No | WhatsApp number |
| `media_ids` | array | No | Array of media IDs to attach |

#### Example Request

```bash
curl -X POST "http://localhost:8000/api/v1/auction-ads" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "title": "2020 Toyota Camry - Excellent Condition",
    "description": "Low mileage vehicle, single owner, full service history...",
    "category_id": 1,
    "brand_id": 1,
    "model_id": 10,
    "city_id": 5,
    "country_id": 1,
    "year": 2020,
    "start_price": 15000,
    "reserve_price": 20000,
    "start_time": "2024-01-15T10:00:00Z",
    "end_time": "2024-01-22T10:00:00Z",
    "minimum_bid_increment": 100,
    "is_last_price_visible": true,
    "contact_phone": "+1234567890"
  }'
```

#### Example Response

```json
{
  "status": "success",
  "message": "Auction created successfully",
  "data": {
    "id": 1,
    "title": "2020 Toyota Camry - Excellent Condition",
    "type": "auction",
    "status": "published",
    "auction": {
      "start_price": 15000,
      "reserve_price": 20000,
      "current_price": null,
      "minimum_next_bid": 15000,
      "bid_count": 0,
      "start_time": "2024-01-15T10:00:00Z",
      "end_time": "2024-01-22T10:00:00Z",
      "status": "active"
    }
  }
}
```

---

### Update Auction Ad

Update an existing auction. Some fields cannot be changed after bids are placed.

```http
PUT /auction-ads/{id}
```

#### Restrictions

After bids are placed, the following fields **cannot** be changed:
- `start_price`
- `start_time`
- `minimum_bid_increment`

The `end_time` can only be extended, not shortened.

#### Example Request

```bash
curl -X PUT "http://localhost:8000/api/v1/auction-ads/1" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "title": "2020 Toyota Camry - UPDATED",
    "description": "Updated description...",
    "end_time": "2024-01-25T10:00:00Z"
  }'
```

---

### Delete Auction Ad

Delete an auction that has no bids.

```http
DELETE /auction-ads/{id}
```

#### Example Request

```bash
curl -X DELETE "http://localhost:8000/api/v1/auction-ads/1" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

#### Example Response

```json
{
  "status": "success",
  "message": "Auction deleted successfully"
}
```

#### Error Response (Has Bids)

```json
{
  "status": "error",
  "code": 422,
  "message": "Cannot delete auction",
  "errors": {
    "auction": ["Cannot delete auction with existing bids. Cancel the auction instead."]
  }
}
```

---

## Bidding Endpoints

### Place a Bid

Place a bid on an active auction.

```http
POST /auction-ads/{id}/bids
```

#### Request Body

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `price` | numeric | Yes | Bid amount (must be >= minimum next bid) |
| `comment` | string | No | Optional comment with your bid (max 1000 chars) |

#### Example Request

```bash
curl -X POST "http://localhost:8000/api/v1/auction-ads/1/bids" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "price": 18600,
    "comment": "Serious buyer, can complete transaction quickly"
  }'
```

#### Example Response (Success)

```json
{
  "status": "success",
  "message": "Bid placed successfully",
  "data": {
    "id": 15,
    "price": 18600,
    "comment": "Serious buyer, can complete transaction quickly",
    "status": "active",
    "is_own_bid": true,
    "is_highest_bid": true,
    "created_at": "2024-01-18T14:30:00Z",
    "withdrawn_at": null
  }
}
```

#### Example Response (Anti-Snipe Triggered)

```json
{
  "status": "success",
  "message": "Bid placed successfully",
  "data": {
    "id": 16,
    "price": 19000,
    "is_own_bid": true,
    "is_highest_bid": true,
    "created_at": "2024-01-22T09:57:00Z"
  },
  "anti_snipe": {
    "triggered": true,
    "new_end_time": "2024-01-22T10:02:00Z",
    "extension_seconds": 300
  }
}
```

#### Error Responses

**Bid Too Low:**
```json
{
  "status": "error",
  "code": 422,
  "message": "Validation failed",
  "errors": {
    "price": ["Your bid must be at least 18,600.00"]
  }
}
```

**Auction Ended:**
```json
{
  "status": "error",
  "code": 403,
  "message": "Unauthorized",
  "errors": {
    "authorization": ["This auction has ended and is no longer accepting bids"]
  }
}
```

---

### List Bids (Owner/Admin/Moderator)

Get all bids for a specific auction. Only available to auction owner, admin, or moderator.

```http
GET /auction-ads/{id}/bids
```

#### Example Request

```bash
curl -X GET "http://localhost:8000/api/v1/auction-ads/1/bids?limit=20" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

#### Example Response

```json
{
  "status": "success",
  "data": [
    {
      "id": 16,
      "price": 19000,
      "comment": "Final offer",
      "status": "active",
      "user": {
        "id": 8,
        "name": "Jane Bidder"
      },
      "is_highest_bid": true,
      "is_winning_bid": false,
      "created_at": "2024-01-22T09:57:00Z",
      "withdrawn_at": null
    },
    {
      "id": 15,
      "price": 18600,
      "comment": null,
      "status": "active",
      "user": {
        "id": 12,
        "name": "Bob Smith"
      },
      "is_highest_bid": false,
      "is_winning_bid": false,
      "created_at": "2024-01-18T14:30:00Z",
      "withdrawn_at": null
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 20,
    "total": 16
  }
}
```

---

### Withdraw Bid

Withdraw your own bid from an auction. Restrictions apply.

```http
DELETE /auction-ads/{id}/bids/{bidId}
```

#### Restrictions

- You can only withdraw your own bids
- Cannot withdraw the highest bid
- Cannot withdraw after the auction has ended
- Cannot withdraw from closed or cancelled auctions

#### Example Request

```bash
curl -X DELETE "http://localhost:8000/api/v1/auction-ads/1/bids/15" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

#### Example Response (Success)

```json
{
  "status": "success",
  "message": "Bid withdrawn successfully"
}
```

#### Error Response (Cannot Withdraw)

```json
{
  "status": "error",
  "code": 422,
  "message": "Cannot withdraw bid",
  "errors": {
    "bid": ["The highest bid cannot be withdrawn"]
  }
}
```

---

### Get Bid Details

Get details of a specific bid.

```http
GET /auction-ads/{id}/bids/{bidId}
```

#### Example Request

```bash
curl -X GET "http://localhost:8000/api/v1/auction-ads/1/bids/15" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

#### Example Response

```json
{
  "status": "success",
  "data": {
    "id": 15,
    "auction_id": 1,
    "price": 18600,
    "comment": "Serious buyer",
    "status": "active",
    "created_at": "2024-01-18T14:30:00Z",
    "withdrawn_at": null,
    "user": {
      "id": 12,
      "name": "Bob Smith"
    },
    "is_own_bid": true,
    "is_highest_bid": false,
    "is_winning_bid": false
  }
}
```

---

### My Bids

Get all bids placed by the authenticated user.

```http
GET /auction-bids/my-bids
```

#### Query Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `auction_status` | string | Filter by auction status: `active`, `closed`, `cancelled` |
| `limit` | integer | Items per page (max: 100, default: 20) |

#### Example Request

```bash
curl -X GET "http://localhost:8000/api/v1/auction-bids/my-bids?auction_status=active" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

## Auction Lifecycle Endpoints

### Publish Auction

Publish a draft auction ad.

```http
POST /auction-ads/{id}/actions/publish
```

#### Example Request

```bash
curl -X POST "http://localhost:8000/api/v1/auction-ads/1/actions/publish" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### Close Auction

Close an auction. Owner can only close after `end_time`. Admin can close anytime.

```http
POST /auction-ads/{id}/actions/close
```

#### Example Request

```bash
curl -X POST "http://localhost:8000/api/v1/auction-ads/1/actions/close" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

#### Example Response

```json
{
  "status": "success",
  "message": "Auction closed with a winner",
  "data": { ... },
  "result": {
    "winner_id": 8,
    "winning_bid": 19000,
    "reserve_met": true
  }
}
```

#### Response When Reserve Not Met

```json
{
  "status": "success",
  "message": "Auction closed but reserve price was not met",
  "data": { ... },
  "result": {
    "winner_id": null,
    "winning_bid": 19000,
    "reserve_met": false
  }
}
```

---

### Cancel Auction

Cancel an auction. Owner cannot cancel if bids exist (admin can).

```http
POST /auction-ads/{id}/actions/cancel
```

#### Example Request

```bash
curl -X POST "http://localhost:8000/api/v1/auction-ads/1/actions/cancel" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

## User-Specific Endpoints

### My Auctions

Get all auctions created by the authenticated user.

```http
GET /auction-ads/my-ads
```

#### Query Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `status` | string | Filter by ad status: `draft`, `pending`, `published`, `expired`, `archived` |
| `auction_status` | string | Filter by auction status: `active`, `closed`, `cancelled` |
| `brand_id` | integer | Filter by brand ID |
| `search` | string | Search in title and description |
| `sort_by` | string | Sort field |
| `sort_direction` | string | Sort direction |
| `limit` | integer | Items per page |

#### Example Request

```bash
curl -X GET "http://localhost:8000/api/v1/auction-ads/my-ads?auction_status=active" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

## Admin Endpoints

### Admin List All Auctions

Get all auctions with all statuses (admin only).

```http
GET /auction-ads/admin
```

#### Query Parameters

Same as public listing, plus:

| Parameter | Type | Description |
|-----------|------|-------------|
| `user_id` | integer | Filter by user ID |

#### Example Request

```bash
curl -X GET "http://localhost:8000/api/v1/auction-ads/admin?status=published&user_id=5" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### Global Statistics

Get global auction statistics (admin only).

```http
GET /auction-ads/stats
```

#### Example Request

```bash
curl -X GET "http://localhost:8000/api/v1/auction-ads/stats" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

#### Example Response

```json
{
  "status": "success",
  "data": {
    "total_auctions": 150,
    "active_auctions": 45,
    "pending_auctions": 12,
    "closed_auctions": 78,
    "total_bids": 1250,
    "bids_today": 35,
    "auctions_ending_soon": 8,
    "total_bid_value": 2500000.00
  }
}
```

---

## Anti-Sniping Protection

The auction system includes anti-sniping protection to prevent last-second bidding tactics.

### How It Works

1. **Window**: When a bid is placed within the `anti_snip_window_seconds` before auction end (default: 5 minutes)
2. **Extension**: The auction `end_time` is extended by `anti_snip_extension_seconds` (default: 5 minutes)
3. **Notification**: The API response includes an `anti_snipe` object when triggered

### Configuration

Both values can be set when creating an auction:

```json
{
  "anti_snip_window_seconds": 300,
  "anti_snip_extension_seconds": 300
}
```

---

## Reserve Price

### Overview

The reserve price is the minimum price at which the seller is willing to sell. If bidding doesn't reach the reserve:

- The auction closes without a winner
- The highest bidder is notified they didn't meet reserve
- The seller can choose to contact bidders

### Visibility

- **Public**: Reserve price status (met/not met) is shown, but exact value is hidden
- **Owner/Admin**: Full reserve price is visible in responses

---

## Error Responses

All error responses follow this format:

```json
{
  "status": "error",
  "code": 422,
  "message": "Human-readable error message",
  "errors": {
    "field_name": ["Specific error message"]
  }
}
```

### Common Error Codes

| Code | Description |
|------|-------------|
| 400 | Bad Request - Invalid request format |
| 401 | Unauthorized - Missing or invalid token |
| 403 | Forbidden - Insufficient permissions |
| 404 | Not Found - Resource doesn't exist |
| 422 | Unprocessable Entity - Validation failed |
| 500 | Server Error - Unexpected error |

---

## Scheduled Tasks

### Auto-Close Expired Auctions

The system automatically closes auctions that have passed their `end_time` and have `auto_close` enabled.

**Schedule**: Every 5 minutes

**Command**: `php artisan auctions:close-expired`

**Dry Run**: `php artisan auctions:close-expired --dry-run`

---

## Rate Limits

- **Bidding**: 10 bids per minute per user
- **Creating Auctions**: 5 per hour
- **General API**: 60 requests per minute

---

## Webhooks (Future)

The following webhook events are planned:

- `auction.created`
- `auction.published`
- `auction.bid_placed`
- `auction.ending_soon`
- `auction.closed`
- `auction.cancelled`
- `auction.reserve_met`
- `auction.reserve_not_met`
