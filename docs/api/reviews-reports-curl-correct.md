# Reviews and Reports API - Complete cURL Guide (CORRECTED)

## 🚨 CRITICAL - Read This First!

### Authentication is Required for Creating, Updating, and Deleting

**You MUST include a Bearer token for these operations:**
- Creating a review or report
- Updating your review
- Deleting your review
- Viewing/managing your own reviews/reports

### How to Get Your Bearer Token

```bash
# 1. Login first
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "mobile_number": "+962791234567",
    "password": "password123"
  }'

# 2. Copy the token from response:
# {
#   "success": true,
#   "data": {
#     "token": "1|abcd1234efgh5678..."  <-- COPY THIS
#   }
# }

# 3. Use token in all protected endpoints:
# Authorization: Bearer 1|abcd1234efgh5678...
```

---

## Table of Contents

### Reviews API
1. [Create Review (⚠️ AUTH REQUIRED)](#1-create-review-auth-required)
2. [List All Reviews (Public)](#2-list-all-reviews-public)
3. [Get Specific Review (Public)](#3-get-specific-review-public)
4. [Get Reviews for an Ad (Public)](#4-get-reviews-for-an-ad-public)
5. [Get Reviews for a Seller (Public)](#5-get-reviews-for-a-seller-public)
6. [Get My Reviews (⚠️ AUTH REQUIRED)](#6-get-my-reviews-auth-required)
7. [Update My Review (⚠️ AUTH REQUIRED)](#7-update-my-review-auth-required)
8. [Delete My Review (⚠️ AUTH REQUIRED)](#8-delete-my-review-auth-required)

### Reports API
9. [Create Report (⚠️ AUTH REQUIRED)](#9-create-report-auth-required)
10. [Get My Reports (⚠️ AUTH REQUIRED)](#10-get-my-reports-auth-required)
11. [Get Specific Report (⚠️ AUTH REQUIRED)](#11-get-specific-report-auth-required)
12. [Admin: Get All Reports (⚠️ ADMIN)](#12-admin-get-all-reports-admin-only)
13. [Admin: Assign Report (⚠️ ADMIN)](#13-admin-assign-report-admin-only)
14. [Admin: Update Status (⚠️ ADMIN)](#14-admin-update-status-admin-only)
15. [Admin: Resolve Report (⚠️ ADMIN)](#15-admin-resolve-report-admin-only)
16. [Admin: Close Report (⚠️ ADMIN)](#16-admin-close-report-admin-only)
17. [Admin: Delete Report (⚠️ ADMIN)](#17-admin-delete-report-admin-only)

---

# REVIEWS API

## 1. Create Review (⚠️ AUTH REQUIRED)

**Endpoint:** `POST /api/v1/reviews`  
**Rate Limit:** 10 requests per hour  
**Authentication:** Required  

### ✅ Correct Request - Review an Ad

```bash
curl -X POST http://localhost:8000/api/v1/reviews \
  -H "Authorization: Bearer 1|abcd1234efgh5678..." \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "target_type": "ad",
    "target_id": 15,
    "stars": 5,
    "title": "Excellent condition!",
    "body": "Car was exactly as described. Great deal and smooth transaction."
  }'
```

### ✅ Correct Request - Review a Seller

```bash
curl -X POST http://localhost:8000/api/v1/reviews \
  -H "Authorization: Bearer 1|abcd1234efgh5678..." \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "target_type": "seller",
    "target_id": 42,
    "stars": 4,
    "title": "Good seller",
    "body": "Responsive and professional. Would buy from again."
  }'
```

### Required Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `target_type` | string | ✅ Yes | Must be: `"ad"` or `"seller"` |
| `target_id` | integer | ✅ Yes | ID of the ad or seller being reviewed |
| `stars` | integer | ✅ Yes | Rating from 1-5 |
| `title` | string | No | Review title (max 255 chars) |
| `body` | string | No | Review details (max 1000 chars) |

### Success Response (201 Created)

```json
{
  "success": true,
  "message": "Review created successfully",
  "data": {
    "id": 123,
    "user_id": 5,
    "target_type": "ad",
    "target_id": 15,
    "ad_id": 15,
    "seller_id": null,
    "stars": 5,
    "title": "Excellent condition!",
    "body": "Car was exactly as described. Great deal and smooth transaction.",
    "user": {
      "id": 5,
      "name": "Ahmad Salem",
      "email": "ahmad@example.com"
    },
    "ad": {
      "id": 15,
      "title": "Toyota Camry 2020",
      "price": 18500
    },
    "seller": null,
    "created_at": "2026-02-01T14:23:10.000000Z",
    "updated_at": "2026-02-01T14:23:10.000000Z"
  }
}
```

### Error Responses

#### Missing Authentication Token
```json
{
  "message": "Unauthenticated."
}
```
**Solution:** Add `Authorization: Bearer YOUR_TOKEN` header

#### Missing Required Fields
```json
{
  "message": "The target type field is required. (and 2 more errors)",
  "errors": {
    "target_type": ["The target type field is required."],
    "target_id": ["The target id field is required."],
    "stars": ["The stars field is required."]
  }
}
```

#### Already Reviewed
```json
{
  "message": "You have already reviewed this ad.",
  "errors": {
    "target_id": ["You have already reviewed this ad."]
  }
}
```

#### Ad/Seller Not Found
```json
{
  "message": "The selected ad does not exist.",
  "errors": {
    "target_id": ["The selected ad does not exist."]
  }
}
```

---

## 2. List All Reviews (Public)

**Endpoint:** `GET /api/v1/reviews`  
**Authentication:** Not required  

### Basic Request

```bash
curl -X GET http://localhost:8000/api/v1/reviews \
  -H "Accept: application/json"
```

### With Filters

```bash
curl -X GET "http://localhost:8000/api/v1/reviews?ad_id=15&min_stars=4&limit=20&page=1" \
  -H "Accept: application/json"
```

### Available Query Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `ad_id` | integer | Filter by specific ad |
| `seller_id` | integer | Filter by specific seller |
| `user_id` | integer | Filter by reviewer user ID |
| `min_stars` | integer | Minimum rating (1-5) |
| `sort` | string | `asc` or `desc` (default: desc) |
| `page` | integer | Page number |
| `limit` | integer | Results per page (max 50) |

### Success Response (200 OK)

```json
{
  "success": true,
  "message": "Reviews retrieved successfully",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 123,
        "user_id": 5,
        "target_type": "ad",
        "target_id": 15,
        "stars": 5,
        "title": "Excellent condition!",
        "body": "Car was exactly as described.",
        "user": {
          "id": 5,
          "name": "Ahmad Salem"
        },
        "ad": {
          "id": 15,
          "title": "Toyota Camry 2020"
        },
        "created_at": "2026-02-01T14:23:10.000000Z"
      }
    ],
    "first_page_url": "http://localhost:8000/api/v1/reviews?page=1",
    "from": 1,
    "last_page": 3,
    "last_page_url": "http://localhost:8000/api/v1/reviews?page=3",
    "next_page_url": "http://localhost:8000/api/v1/reviews?page=2",
    "path": "http://localhost:8000/api/v1/reviews",
    "per_page": 15,
    "prev_page_url": null,
    "to": 15,
    "total": 42
  }
}
```

---

## 3. Get Specific Review (Public)

**Endpoint:** `GET /api/v1/reviews/{reviewId}`  
**Authentication:** Not required  

```bash
curl -X GET http://localhost:8000/api/v1/reviews/123 \
  -H "Accept: application/json"
```

### Success Response (200 OK)

```json
{
  "success": true,
  "message": "Review retrieved successfully",
  "data": {
    "id": 123,
    "user_id": 5,
    "target_type": "ad",
    "target_id": 15,
    "ad_id": 15,
    "seller_id": null,
    "stars": 5,
    "title": "Excellent condition!",
    "body": "Car was exactly as described. Great deal and smooth transaction.",
    "user": {
      "id": 5,
      "name": "Ahmad Salem",
      "email": "ahmad@example.com",
      "profile_image": "/storage/profiles/ahmad.jpg"
    },
    "ad": {
      "id": 15,
      "title": "Toyota Camry 2020",
      "price": 18500,
      "avg_rating": 4.5,
      "reviews_count": 12
    },
    "seller": null,
    "created_at": "2026-02-01T14:23:10.000000Z",
    "updated_at": "2026-02-01T14:23:10.000000Z"
  }
}
```

---

## 4. Get Reviews for an Ad (Public)

**Endpoint:** `GET /api/v1/ads/{adId}/reviews`  
**Authentication:** Not required  

```bash
curl -X GET http://localhost:8000/api/v1/ads/15/reviews \
  -H "Accept: application/json"
```

### With Filters

```bash
curl -X GET "http://localhost:8000/api/v1/ads/15/reviews?min_stars=4&limit=10" \
  -H "Accept: application/json"
```

### Success Response (200 OK)

```json
{
  "success": true,
  "message": "Ad reviews retrieved successfully",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 123,
        "stars": 5,
        "title": "Excellent condition!",
        "body": "Car was exactly as described.",
        "user": {
          "id": 5,
          "name": "Ahmad Salem"
        },
        "created_at": "2026-02-01T14:23:10.000000Z"
      },
      {
        "id": 98,
        "stars": 4,
        "title": "Good deal",
        "body": "Happy with the purchase.",
        "user": {
          "id": 12,
          "name": "Sara Ali"
        },
        "created_at": "2026-01-28T10:15:00.000000Z"
      }
    ],
    "total": 12,
    "per_page": 15
  }
}
```

---

## 5. Get Reviews for a Seller (Public)

**Endpoint:** `GET /api/v1/users/{userId}/reviews`  
**Authentication:** Not required  

```bash
curl -X GET http://localhost:8000/api/v1/users/42/reviews \
  -H "Accept: application/json"
```

### Success Response (200 OK)

```json
{
  "success": true,
  "message": "User reviews retrieved successfully",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 87,
        "stars": 5,
        "title": "Trustworthy seller",
        "body": "Professional and honest. Highly recommended!",
        "user": {
          "id": 9,
          "name": "Mohammed Hassan"
        },
        "created_at": "2026-01-30T16:45:22.000000Z"
      }
    ],
    "total": 8
  }
}
```

---

## 6. Get My Reviews (⚠️ AUTH REQUIRED)

**Endpoint:** `GET /api/v1/reviews/my-reviews`  
**Authentication:** Required  

Get all reviews created by the authenticated user.

```bash
curl -X GET http://localhost:8000/api/v1/reviews/my-reviews \
  -H "Authorization: Bearer 1|abcd1234efgh5678..." \
  -H "Accept: application/json"
```

### Success Response (200 OK)

```json
{
  "success": true,
  "message": "Your reviews retrieved successfully",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 123,
        "target_type": "ad",
        "target_id": 15,
        "stars": 5,
        "title": "Excellent condition!",
        "body": "Car was exactly as described.",
        "ad": {
          "id": 15,
          "title": "Toyota Camry 2020",
          "price": 18500
        },
        "seller": null,
        "can_edit": true,
        "can_delete": true,
        "created_at": "2026-02-01T14:23:10.000000Z"
      },
      {
        "id": 98,
        "target_type": "seller",
        "target_id": 42,
        "stars": 4,
        "title": "Good seller",
        "ad": null,
        "seller": {
          "id": 42,
          "name": "Car Showroom Ltd"
        },
        "can_edit": true,
        "can_delete": true,
        "created_at": "2026-01-25T09:30:00.000000Z"
      }
    ],
    "total": 2
  }
}
```

---

## 7. Update My Review (⚠️ AUTH REQUIRED)

**Endpoint:** `PUT /api/v1/reviews/{reviewId}`  
**Authentication:** Required  
**Authorization:** Only review owner or admin  

```bash
curl -X PUT http://localhost:8000/api/v1/reviews/123 \
  -H "Authorization: Bearer 1|abcd1234efgh5678..." \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "stars": 4,
    "title": "Updated: Pretty good",
    "body": "After further thought, it was good but not perfect. Still happy though."
  }'
```

### Updatable Fields

| Field | Type | Description |
|-------|------|-------------|
| `stars` | integer | Rating from 1-5 |
| `title` | string | Review title (max 255 chars) |
| `body` | string | Review details (max 1000 chars) |

**Note:** You CANNOT change `target_type` or `target_id` after creation.

### Success Response (200 OK)

```json
{
  "success": true,
  "message": "Review updated successfully",
  "data": {
    "id": 123,
    "stars": 4,
    "title": "Updated: Pretty good",
    "body": "After further thought, it was good but not perfect. Still happy though.",
    "updated_at": "2026-02-01T15:10:45.000000Z"
  }
}
```

### Error: Not Authorized

```json
{
  "message": "This action is unauthorized."
}
```

---

## 8. Delete My Review (⚠️ AUTH REQUIRED)

**Endpoint:** `DELETE /api/v1/reviews/{reviewId}`  
**Authentication:** Required  
**Authorization:** Only review owner or admin  

```bash
curl -X DELETE http://localhost:8000/api/v1/reviews/123 \
  -H "Authorization: Bearer 1|abcd1234efgh5678..." \
  -H "Accept: application/json"
```

### Success Response (200 OK)

```json
{
  "success": true,
  "message": "Review deleted successfully"
}
```

---

# REPORTS API

## 9. Create Report (⚠️ AUTH REQUIRED)

**Endpoint:** `POST /api/v1/reports`  
**Rate Limit:** 10 requests per hour  
**Authentication:** Required  

### ✅ Correct Request - Report an Ad

```bash
curl -X POST http://localhost:8000/api/v1/reports \
  -H "Authorization: Bearer 1|abcd1234efgh5678..." \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "target_type": "ad",
    "target_id": 15,
    "reason": "Misleading price or description",
    "title": "Price doesn not match actual vehicle",
    "details": "The ad shows 2020 model but car is actually 2018. Also missing mentioned features."
  }'
```

### ✅ Correct Request - Report a User

```bash
curl -X POST http://localhost:8000/api/v1/reports \
  -H "Authorization: Bearer 1|abcd1234efgh5678..." \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "target_type": "user",
    "target_id": 99,
    "reason": "Suspicious behavior",
    "title": "Potential scammer",
    "details": "User is asking for upfront payment without meeting. Multiple complaints."
  }'
```

### Required Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `target_type` | string | ✅ Yes | Must be: `"ad"`, `"user"`, or `"dealer"` |
| `target_id` | integer | ✅ Yes | ID of the ad/user/dealer being reported |
| `reason` | string | ✅ Yes | Reason for report (max 255 chars) |
| `title` | string | No | Report title (max 255 chars) |
| `details` | string | No | Additional details (max 2000 chars) |

### Success Response (201 Created)

```json
{
  "success": true,
  "message": "Report submitted successfully. Our team will review it shortly.",
  "data": {
    "id": 456,
    "reported_by_user_id": 5,
    "target_type": "ad",
    "target_id": 15,
    "reason": "Misleading price or description",
    "title": "Price doesn not match actual vehicle",
    "status": "open",
    "reporter": {
      "id": 5,
      "name": "Ahmad Salem"
    },
    "target": {
      "id": 15,
      "title": "Toyota Camry 2020",
      "type": "ad"
    },
    "created_at": "2026-02-01T14:45:33.000000Z"
  }
}
```

### Error Responses

#### Missing Authentication
```json
{
  "message": "Unauthenticated."
}
```

#### Duplicate Report (within 24 hours)
```json
{
  "message": "You have already reported this ad for the same reason within the last 24 hours.",
  "errors": {
    "reason": ["You have already reported this ad for the same reason within the last 24 hours."]
  }
}
```

#### Cannot Report Yourself
```json
{
  "message": "You cannot report yourself.",
  "errors": {
    "target_id": ["You cannot report yourself."]
  }
}
```

---

## 10. Get My Reports (⚠️ AUTH REQUIRED)

**Endpoint:** `GET /api/v1/reports/my-reports`  
**Authentication:** Required  

```bash
curl -X GET http://localhost:8000/api/v1/reports/my-reports \
  -H "Authorization: Bearer 1|abcd1234efgh5678..." \
  -H "Accept: application/json"
```

### With Filters

```bash
curl -X GET "http://localhost:8000/api/v1/reports/my-reports?status=open&limit=20" \
  -H "Authorization: Bearer 1|abcd1234efgh5678..." \
  -H "Accept: application/json"
```

### Query Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `status` | string | Filter by: `open`, `under_review`, `resolved`, `closed` |
| `page` | integer | Page number |
| `limit` | integer | Results per page (max 50) |

### Success Response (200 OK)

```json
{
  "success": true,
  "message": "Your reports retrieved successfully",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 456,
        "target_type": "ad",
        "target_id": 15,
        "reason": "Misleading price or description",
        "title": "Price doesn not match actual vehicle",
        "status": "under_review",
        "target": {
          "id": 15,
          "title": "Toyota Camry 2020"
        },
        "created_at": "2026-02-01T14:45:33.000000Z",
        "updated_at": "2026-02-01T15:20:10.000000Z"
      }
    ],
    "total": 3
  }
}
```

---

## 11. Get Specific Report (⚠️ AUTH REQUIRED)

**Endpoint:** `GET /api/v1/reports/{reportId}`  
**Authentication:** Required  
**Authorization:** Report owner, assigned moderator, or admin  

```bash
curl -X GET http://localhost:8000/api/v1/reports/456 \
  -H "Authorization: Bearer 1|abcd1234efgh5678..." \
  -H "Accept: application/json"
```

### Success Response (200 OK)

```json
{
  "success": true,
  "message": "Report retrieved successfully",
  "data": {
    "id": 456,
    "reported_by_user_id": 5,
    "target_type": "ad",
    "target_id": 15,
    "reason": "Misleading price or description",
    "title": "Price doesn not match actual vehicle",
    "status": "under_review",
    "assigned_to": 2,
    "reporter": {
      "id": 5,
      "name": "Ahmad Salem"
    },
    "assignedTo": {
      "id": 2,
      "name": "Moderator Admin",
      "role": "moderator"
    },
    "target": {
      "id": 15,
      "title": "Toyota Camry 2020",
      "type": "ad"
    },
    "created_at": "2026-02-01T14:45:33.000000Z",
    "updated_at": "2026-02-01T15:20:10.000000Z"
  }
}
```

---

## 12. Admin: Get All Reports (⚠️ ADMIN ONLY)

**Endpoint:** `GET /api/v1/reports/admin/index`  
**Authentication:** Required  
**Authorization:** Admin, Super Admin, or Moderator  

```bash
curl -X GET "http://localhost:8000/api/v1/reports/admin/index?status=open&target_type=ad" \
  -H "Authorization: Bearer 1|ADMIN_TOKEN..." \
  -H "Accept: application/json"
```

### Query Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `status` | string | Filter: `open`, `under_review`, `resolved`, `closed` |
| `target_type` | string | Filter: `ad`, `user`, `dealer` |
| `assigned_to` | integer | Filter by moderator ID or `"unassigned"` |
| `from_date` | date | Filter from date (YYYY-MM-DD) |
| `to_date` | date | Filter to date (YYYY-MM-DD) |
| `page` | integer | Page number |
| `limit` | integer | Results per page (max 50) |

### Success Response (200 OK)

```json
{
  "success": true,
  "message": "All reports retrieved successfully",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 456,
        "target_type": "ad",
        "status": "open",
        "reason": "Misleading price",
        "reporter": {
          "id": 5,
          "name": "Ahmad Salem"
        },
        "target": {
          "id": 15,
          "title": "Toyota Camry 2020"
        },
        "assigned_to": null,
        "created_at": "2026-02-01T14:45:33.000000Z"
      }
    ],
    "total": 45
  }
}
```

---

## 13. Admin: Assign Report (⚠️ ADMIN ONLY)

**Endpoint:** `POST /api/v1/reports/{reportId}/assign`  
**Authentication:** Required  
**Authorization:** Admin or Super Admin only  

```bash
curl -X POST http://localhost:8000/api/v1/reports/456/assign \
  -H "Authorization: Bearer 1|ADMIN_TOKEN..." \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "moderator_id": 12
  }'
```

### Success Response (200 OK)

```json
{
  "success": true,
  "message": "Report assigned successfully",
  "data": {
    "id": 456,
    "status": "under_review",
    "assigned_to": 12,
    "assignedTo": {
      "id": 12,
      "name": "Sarah Moderator"
    }
  }
}
```

---

## 14. Admin: Update Status (⚠️ ADMIN ONLY)

**Endpoint:** `PUT /api/v1/reports/{reportId}/status`  
**Authentication:** Required  
**Authorization:** Admin, Moderator, or assigned moderator  

```bash
curl -X PUT http://localhost:8000/api/v1/reports/456/status \
  -H "Authorization: Bearer 1|ADMIN_TOKEN..." \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "status": "under_review",
    "message": "Investigating this report"
  }'
```

### Valid Status Values

- `open`
- `under_review`
- `resolved`
- `closed`

### Success Response (200 OK)

```json
{
  "success": true,
  "message": "Report status updated successfully",
  "data": {
    "id": 456,
    "status": "under_review",
    "updated_at": "2026-02-01T16:30:00.000000Z"
  }
}
```

---

## 15. Admin: Resolve Report (⚠️ ADMIN ONLY)

**Endpoint:** `POST /api/v1/reports/{reportId}/actions/resolve`  
**Authentication:** Required  
**Authorization:** Admin, Moderator, or assigned moderator  

```bash
curl -X POST http://localhost:8000/api/v1/reports/456/actions/resolve \
  -H "Authorization: Bearer 1|ADMIN_TOKEN..." \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "message": "Issue verified and ad has been removed"
  }'
```

### Success Response (200 OK)

```json
{
  "success": true,
  "message": "Report marked as resolved",
  "data": {
    "id": 456,
    "status": "resolved",
    "updated_at": "2026-02-01T16:45:22.000000Z"
  }
}
```

---

## 16. Admin: Close Report (⚠️ ADMIN ONLY)

**Endpoint:** `POST /api/v1/reports/{reportId}/actions/close`  
**Authentication:** Required  
**Authorization:** Admin, Moderator, or assigned moderator  

```bash
curl -X POST http://localhost:8000/api/v1/reports/456/actions/close \
  -H "Authorization: Bearer 1|ADMIN_TOKEN..." \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "message": "Report closed - no action needed"
  }'
```

### Success Response (200 OK)

```json
{
  "success": true,
  "message": "Report closed successfully",
  "data": {
    "id": 456,
    "status": "closed",
    "updated_at": "2026-02-01T17:00:00.000000Z"
  }
}
```

---

## 17. Admin: Delete Report (⚠️ ADMIN ONLY)

**Endpoint:** `DELETE /api/v1/reports/{reportId}`  
**Authentication:** Required  
**Authorization:** Admin or Super Admin only  

```bash
curl -X DELETE http://localhost:8000/api/v1/reports/456 \
  -H "Authorization: Bearer 1|ADMIN_TOKEN..." \
  -H "Accept: application/json"
```

### Success Response (200 OK)

```json
{
  "success": true,
  "message": "Report deleted successfully"
}
```

---

# ERROR RESPONSES

## Common Authentication Errors

### 401 - Missing or Invalid Token

```json
{
  "message": "Unauthenticated."
}
```

**Solution:** Include valid Bearer token in Authorization header

### 403 - Insufficient Permissions

```json
{
  "message": "This action is unauthorized."
}
```

**Solution:** Use an account with appropriate role/permissions

## Common Validation Errors

### 422 - Validation Failed

```json
{
  "message": "The target type field is required. (and 1 more error)",
  "errors": {
    "target_type": ["The target type field is required."],
    "stars": ["The stars field must be between 1 and 5."]
  }
}
```

### 404 - Resource Not Found

```json
{
  "message": "No query results for model [App\\Models\\Review] 999"
}
```

## Rate Limiting

### 429 - Too Many Requests

```json
{
  "message": "Too Many Attempts."
}
```

**Note:** Review/Report creation is limited to 10 requests per hour per user.

---

# QUICK REFERENCE

## Authentication Flow

```bash
# 1. Login
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"mobile_number":"+962791234567","password":"password123"}'

# 2. Use token in subsequent requests
curl -X POST http://localhost:8000/api/v1/reviews \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{"target_type":"ad","target_id":15,"stars":5}'
```

## Important Field Names

### Reviews
- `target_type`: `"ad"` or `"seller"`
- `target_id`: Integer ID of ad or seller
- `stars`: Integer 1-5

### Reports
- `target_type`: `"ad"`, `"user"`, or `"dealer"`
- `target_id`: Integer ID of target
- `reason`: String description

## Public vs Protected Endpoints

### ✅ Public (No Auth)
- List all reviews
- Get specific review
- Get reviews for ad/seller

### ⚠️ Protected (Auth Required)
- Create review/report
- Update/delete own review
- View my reviews/reports

### 🔒 Admin Only
- View all reports
- Assign/update/resolve/close/delete reports

---

**Last Updated:** February 1, 2026  
**API Version:** v1  
**Base URL:** http://localhost:8000/api/v1
