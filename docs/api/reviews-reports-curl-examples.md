# Reviews and Reports API - cURL Examples

This document provides comprehensive cURL examples for all Reviews and Reports API endpoints.

## Table of Contents
1. [Authentication](#authentication)
2. [Reviews API](#reviews-api)
3. [Reports API](#reports-api)
4. [Rate Limiting](#rate-limiting)
5. [Common Error Responses](#common-error-responses)

---

## Authentication

All protected endpoints require a Bearer token obtained from login:

```bash
# Login to get token
curl -X POST http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123"
  }'

# Response:
# {
#   "status": "success",
#   "code": 200,
#   "message": "Login successful",
#   "data": {
#     "token": "1|abc123xyz...",
#     "user": { ... }
#   }
# }
```

Use this token in subsequent requests:
```bash
-H "Authorization: Bearer 1|abc123xyz..."
```

---

## Reviews API

### 1. List All Reviews (Public)

```bash
# Get all reviews with default pagination (15 per page)
curl -X GET "http://localhost/api/v1/reviews" \
  -H "Accept: application/json"

# Filter by minimum stars
curl -X GET "http://localhost/api/v1/reviews?min_stars=4" \
  -H "Accept: application/json"

# Filter by ad_id
curl -X GET "http://localhost/api/v1/reviews?ad_id=5" \
  -H "Accept: application/json"

# Filter by seller_id
curl -X GET "http://localhost/api/v1/reviews?seller_id=3" \
  -H "Accept: application/json"

# Custom pagination
curl -X GET "http://localhost/api/v1/reviews?page=2&limit=25" \
  -H "Accept: application/json"

# Response:
# {
#   "status": "success",
#   "code": 200,
#   "message": "Reviews retrieved successfully",
#   "data": {
#     "reviews": {
#       "data": [
#         {
#           "id": 1,
#           "title": "Great product!",
#           "body": "Very satisfied with this purchase.",
#           "stars": 5,
#           "user": { "id": 2, "name": "John Doe" },
#           "seller": { "id": 3, "name": "Jane Seller" },
#           "ad": { "id": 5, "title": "iPhone 13" },
#           "created_at": "2024-01-15T10:30:00Z"
#         }
#       ],
#       "pagination": {
#         "current_page": 1,
#         "per_page": 15,
#         "total": 45,
#         "last_page": 3
#       }
#     }
#   }
# }
```

### 2. Get Single Review (Public)

```bash
curl -X GET "http://localhost/api/v1/reviews/1" \
  -H "Accept: application/json"

# Response:
# {
#   "status": "success",
#   "code": 200,
#   "message": "Review retrieved successfully",
#   "data": {
#     "review": {
#       "id": 1,
#       "title": "Great product!",
#       "body": "Very satisfied with this purchase.",
#       "stars": 5,
#       "user": { ... },
#       "seller": { ... },
#       "ad": { ... },
#       "created_at": "2024-01-15T10:30:00Z"
#     }
#   }
# }
```

### 3. Get Reviews for Specific Ad (Public)

```bash
curl -X GET "http://localhost/api/v1/ads/5/reviews" \
  -H "Accept: application/json"

# Response: Similar to list all reviews, filtered by ad
```

### 4. Get Reviews for Specific User/Seller (Public)

```bash
curl -X GET "http://localhost/api/v1/users/3/reviews" \
  -H "Accept: application/json"

# Response: Similar to list all reviews, filtered by seller
```

### 5. Create Review for Ad (Protected, Rate Limited: 10/hour)

```bash
curl -X POST "http://localhost/api/v1/reviews" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "target_type": "ad",
    "target_id": 5,
    "title": "Excellent product!",
    "body": "I am very satisfied with this purchase. The seller was professional and the item arrived as described. Highly recommend!",
    "stars": 5
  }'

# Success Response (201):
# {
#   "status": "success",
#   "code": 201,
#   "message": "Review created successfully",
#   "data": {
#     "review": {
#       "id": 42,
#       "title": "Excellent product!",
#       "body": "I am very satisfied...",
#       "stars": 5,
#       "user_id": 2,
#       "seller_id": 3,
#       "ad_id": 5,
#       "created_at": "2024-01-15T10:30:00Z"
#     }
#   }
# }
```

### 6. Create Review for Seller Only (No Specific Ad)

```bash
curl -X POST "http://localhost/api/v1/reviews" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "target_type": "seller",
    "target_id": 3,
    "title": "Great communication",
    "body": "The seller was very responsive and helpful throughout the transaction.",
    "stars": 4
  }'
```

### 7. Get My Reviews (Protected)

```bash
curl -X GET "http://localhost/api/v1/reviews/my-reviews" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"

# Response: List of reviews created by authenticated user
```

### 8. Update Review (Protected - Owner or Admin)

```bash
curl -X PUT "http://localhost/api/v1/reviews/42" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "title": "Updated title",
    "body": "Updated review content after further use.",
    "stars": 4
  }'

# Success Response (200):
# {
#   "status": "success",
#   "code": 200,
#   "message": "Review updated successfully",
#   "data": {
#     "review": { ... }
#   }
# }
```

### 9. Delete Review (Protected - Owner or Admin)

```bash
curl -X DELETE "http://localhost/api/v1/reviews/42" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"

# Success Response (200):
# {
#   "status": "success",
#   "code": 200,
#   "message": "Review deleted successfully",
#   "data": {}
# }
```

---

## Reports API

### 1. Create Report (Protected, Rate Limited: 10/hour)

#### Report an Ad

```bash
curl -X POST "http://localhost/api/v1/reports" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "target_type": "ad",
    "target_id": 5,
    "reason": "spam",
    "title": "Spam advertisement",
    "details": "This ad is clearly spam and contains misleading information about the product."
  }'

# Success Response (201):
# {
#   "status": "success",
#   "code": 201,
#   "message": "Report submitted successfully",
#   "data": {
#     "report": {
#       "id": 15,
#       "reason": "spam",
#       "title": "Spam advertisement",
#       "details": "This ad is clearly spam...",
#       "status": "open",
#       "target_type": "ad",
#       "target_id": 5,
#       "created_at": "2024-01-15T10:30:00Z"
#     }
#   }
# }
```

#### Report a User

```bash
curl -X POST "http://localhost/api/v1/reports" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "target_type": "user",
    "target_id": 8,
    "reason": "fraud",
    "title": "Fraudulent seller",
    "details": "This user is engaging in fraudulent activities and should be investigated."
  }'
```

#### Common Report Reasons
- `spam` - Spam content
- `fraud` - Fraudulent activity
- `inappropriate` - Inappropriate content
- `misleading` - Misleading information
- `scam` - Scam attempt
- `offensive` - Offensive material
- `duplicate` - Duplicate listing
- `counterfeit` - Counterfeit products

### 2. Get My Reports (Protected)

```bash
curl -X GET "http://localhost/api/v1/reports/my-reports" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"

# Response:
# {
#   "status": "success",
#   "code": 200,
#   "message": "Reports retrieved successfully",
#   "data": {
#     "reports": {
#       "data": [
#         {
#           "id": 15,
#           "reason": "spam",
#           "title": "Spam advertisement",
#           "details": "This ad is clearly spam...",
#           "status": "open",
#           "status_label": "Open",
#           "target": { "type": "ad", "id": 5, "title": "iPhone 13" },
#           "created_at": "2024-01-15T10:30:00Z"
#         }
#       ]
#     }
#   }
# }
```

### 3. View Single Report (Protected - Owner/Assigned/Admin)

```bash
curl -X GET "http://localhost/api/v1/reports/15" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"

# Response:
# {
#   "status": "success",
#   "code": 200,
#   "message": "Report retrieved successfully",
#   "data": {
#     "report": {
#       "id": 15,
#       "reason": "spam",
#       "title": "Spam advertisement",
#       "details": "This ad is clearly spam...",
#       "status": "under_review",
#       "status_label": "Under Review",
#       "assigned_to": { "id": 4, "name": "Moderator John" },  // Admin-only field
#       "reporter": { "id": 2, "name": "Reporter Jane" },      // Admin-only field
#       "target": { ... },
#       "created_at": "2024-01-15T10:30:00Z",
#       "updated_at": "2024-01-15T11:00:00Z"
#     }
#   }
# }
```

### 4. Admin: List All Reports with Filters (Admin/Moderator Only)

```bash
# Get all reports
curl -X GET "http://localhost/api/v1/reports/admin/index" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -H "Accept: application/json"

# Filter by status
curl -X GET "http://localhost/api/v1/reports/admin/index?status=open" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -H "Accept: application/json"

# Filter by target_type
curl -X GET "http://localhost/api/v1/reports/admin/index?target_type=ad" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -H "Accept: application/json"

# Filter by assigned moderator
curl -X GET "http://localhost/api/v1/reports/admin/index?assigned_to=4" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -H "Accept: application/json"

# Filter by date range
curl -X GET "http://localhost/api/v1/reports/admin/index?from_date=2024-01-01&to_date=2024-01-31" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -H "Accept: application/json"

# Combine filters
curl -X GET "http://localhost/api/v1/reports/admin/index?status=open&target_type=ad&limit=20" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -H "Accept: application/json"
```

### 5. Admin: Assign Report to Moderator (Admin Only)

```bash
curl -X POST "http://localhost/api/v1/reports/15/assign" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "moderator_id": 4
  }'

# Success Response (200):
# {
#   "status": "success",
#   "code": 200,
#   "message": "Report assigned successfully",
#   "data": {
#     "report": {
#       "id": 15,
#       "assigned_to": { "id": 4, "name": "Moderator John" },
#       "status": "under_review"
#     }
#   }
# }
```

### 6. Admin/Moderator: Update Report Status

```bash
curl -X PUT "http://localhost/api/v1/reports/15/status" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "status": "resolved",
    "admin_message": "We have reviewed this report and taken appropriate action."
  }'

# Success Response (200):
# {
#   "status": "success",
#   "code": 200,
#   "message": "Report status updated successfully",
#   "data": {
#     "report": {
#       "id": 15,
#       "status": "resolved",
#       "status_label": "Resolved"
#     }
#   }
# }
```

### 7. Admin/Moderator: Resolve Report

```bash
curl -X POST "http://localhost/api/v1/reports/15/actions/resolve" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "admin_message": "The reported content has been removed and the user has been warned."
  }'

# Success Response (200):
# {
#   "status": "success",
#   "code": 200,
#   "message": "Report resolved successfully",
#   "data": {
#     "report": { ... }
#   }
# }
```

### 8. Admin/Moderator: Close Report

```bash
curl -X POST "http://localhost/api/v1/reports/15/actions/close" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "admin_message": "After investigation, no action is required."
  }'

# Success Response (200):
# {
#   "status": "success",
#   "code": 200,
#   "message": "Report closed successfully",
#   "data": {
#     "report": { ... }
#   }
# }
```

### 9. Admin: Delete Report (Admin Only)

```bash
curl -X DELETE "http://localhost/api/v1/reports/15" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -H "Accept: application/json"

# Success Response (200):
# {
#   "status": "success",
#   "code": 200,
#   "message": "Report deleted successfully",
#   "data": {}
# }
```

---

## Rate Limiting

Both review and report creation endpoints are rate limited to **10 requests per hour** per user.

### Rate Limit Headers

```bash
# Successful request within limit
HTTP/1.1 201 Created
X-RateLimit-Limit: 10
X-RateLimit-Remaining: 7
Content-Type: application/json

# Rate limit exceeded (429)
curl -X POST "http://localhost/api/v1/reviews" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{ ... }'

HTTP/1.1 429 Too Many Requests
X-RateLimit-Limit: 10
X-RateLimit-Remaining: 0
Retry-After: 3450
Content-Type: application/json

{
  "status": "error",
  "code": 429,
  "message": "Too many review submissions. Please try again later.",
  "errors": {},
  "retry_after": 3450
}
```

---

## Common Error Responses

### 1. Validation Errors (422)

```bash
# Missing required fields
curl -X POST "http://localhost/api/v1/reviews" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "target_type": "ad"
  }'

# Response:
{
  "status": "error",
  "code": 422,
  "message": "Validation failed",
  "errors": {
    "target_id": ["The target id field is required."],
    "title": ["The title field is required."],
    "body": ["The body field is required."],
    "stars": ["The stars field is required."]
  }
}
```

### 2. Duplicate Review (422)

```bash
# Response:
{
  "status": "error",
  "code": 422,
  "message": "Validation failed",
  "errors": {
    "ad_id": ["You have already reviewed this ad."]
  }
}
```

### 3. Self-Review Prevention (422)

```bash
# Response:
{
  "status": "error",
  "code": 422,
  "message": "Validation failed",
  "errors": {
    "ad_id": ["You cannot review your own ad."]
  }
}
```

### 4. Duplicate Report within 24h (422)

```bash
# Response:
{
  "status": "error",
  "code": 422,
  "message": "Validation failed",
  "errors": {
    "target_id": ["You have already reported this item within the last 24 hours."]
  }
}
```

### 5. Unauthorized (401)

```bash
# Missing or invalid token
{
  "status": "error",
  "code": 401,
  "message": "Unauthenticated",
  "errors": {}
}
```

### 6. Forbidden (403)

```bash
# Attempting to update another user's review
{
  "status": "error",
  "code": 403,
  "message": "Forbidden",
  "errors": {}
}
```

### 7. Not Found (404)

```bash
# Resource doesn't exist
{
  "status": "error",
  "code": 404,
  "message": "Resource not found",
  "errors": {}
}
```

---

## Testing with cURL

### Quick Test Workflow

```bash
# 1. Login
TOKEN=$(curl -s -X POST http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}' | jq -r '.data.token')

# 2. Create a review
curl -X POST http://localhost/api/v1/reviews \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "target_type": "ad",
    "target_id": 1,
    "title": "Great!",
    "body": "Excellent product",
    "stars": 5
  }'

# 3. View your reviews
curl -X GET http://localhost/api/v1/reviews/my-reviews \
  -H "Authorization: Bearer $TOKEN"

# 4. Create a report
curl -X POST http://localhost/api/v1/reports \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "target_type": "ad",
    "target_id": 2,
    "reason": "spam",
    "title": "Spam ad",
    "details": "This is spam"
  }'

# 5. View your reports
curl -X GET http://localhost/api/v1/reports/my-reports \
  -H "Authorization: Bearer $TOKEN"
```

---

## Notes

1. **Rate Limiting**: Both reviews and reports creation endpoints are limited to 10 requests per hour per authenticated user.

2. **Authentication**: Most endpoints require authentication via Bearer token except public listing endpoints.

3. **Notifications**: 
   - Sellers receive notifications when they receive reviews
   - Reporters receive notifications when their reports are resolved/closed

4. **Automatic Rating Cache**: Creating, updating, or deleting reviews automatically updates the `avg_rating` and `reviews_count` fields on the related ad and user.

5. **Report Status Flow**:
   - `open` → `under_review` → `resolved` or `closed`
   - Assigning a report automatically transitions it to `under_review`

6. **Pagination**: Default is 15 items per page, maximum is 50 items per page.

7. **Filtering**: Use query parameters to filter results (status, target_type, date ranges, etc.)
