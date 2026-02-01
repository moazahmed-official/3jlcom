# Reviews and Reports API - Complete cURL Examples

## Authentication Token
```bash
# First, login to get your token
curl -X POST 'http://localhost:8000/api/v1/auth/login' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
    "email": "user@example.com",
    "password": "password123"
  }'

# Response:
{
  "status": "success",
  "code": 200,
  "message": "Login successful",
  "data": {
    "token": "4|wrtCvSB6lQn20CWclSAqhi4H0mx9dmPgKRAtZzUe6eff0f6c",
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "user@example.com"
    }
  }
}
```

---

## REVIEWS API

### 1. List All Reviews (Public)

```bash
curl -X GET 'http://localhost:8000/api/v1/reviews' \
  --header 'Accept: application/json'
```

**Response:**
```json
{
  "status": "success",
  "code": 200,
  "message": "Reviews retrieved successfully",
  "data": {
    "reviews": {
      "data": [
        {
          "id": 1,
          "title": "Excellent product!",
          "body": "I am very satisfied with this purchase. The seller was professional and the item arrived as described.",
          "stars": 5,
          "target_type": "ad",
          "user": {
            "id": 2,
            "name": "Jane Smith",
            "email": "jane@example.com"
          },
          "seller": {
            "id": 3,
            "name": "Mike Johnson",
            "email": "mike@example.com",
            "avg_rating": 4.75,
            "reviews_count": 8
          },
          "ad": {
            "id": 5,
            "title": "iPhone 13 Pro Max 256GB",
            "price": 999.99,
            "avg_rating": 4.80,
            "reviews_count": 15
          },
          "can_edit": false,
          "can_delete": false,
          "created_at": "2026-01-15T10:30:00.000000Z",
          "updated_at": "2026-01-15T10:30:00.000000Z"
        },
        {
          "id": 2,
          "title": "Good communication",
          "body": "The seller was very responsive to my questions.",
          "stars": 4,
          "target_type": "seller",
          "user": {
            "id": 4,
            "name": "Sarah Wilson",
            "email": "sarah@example.com"
          },
          "seller": {
            "id": 3,
            "name": "Mike Johnson",
            "email": "mike@example.com",
            "avg_rating": 4.75,
            "reviews_count": 8
          },
          "ad": null,
          "can_edit": false,
          "can_delete": false,
          "created_at": "2026-01-14T15:20:00.000000Z",
          "updated_at": "2026-01-14T15:20:00.000000Z"
        }
      ],
      "pagination": {
        "current_page": 1,
        "per_page": 15,
        "total": 42,
        "last_page": 3,
        "from": 1,
        "to": 15
      }
    }
  }
}
```

### 2. List Reviews with Filters

```bash
# Filter by minimum stars
curl -X GET 'http://localhost:8000/api/v1/reviews?min_stars=4' \
  --header 'Accept: application/json'

# Filter by ad_id
curl -X GET 'http://localhost:8000/api/v1/reviews?ad_id=5' \
  --header 'Accept: application/json'

# Filter by seller_id
curl -X GET 'http://localhost:8000/api/v1/reviews?seller_id=3' \
  --header 'Accept: application/json'

# Pagination
curl -X GET 'http://localhost:8000/api/v1/reviews?page=2&limit=25' \
  --header 'Accept: application/json'

# Combined filters
curl -X GET 'http://localhost:8000/api/v1/reviews?min_stars=4&ad_id=5&limit=10' \
  --header 'Accept: application/json'
```

**Response:** Same structure as List All Reviews

### 3. Get Single Review (Public)

```bash
curl -X GET 'http://localhost:8000/api/v1/reviews/1' \
  --header 'Accept: application/json'
```

**Response:**
```json
{
  "status": "success",
  "code": 200,
  "message": "Review retrieved successfully",
  "data": {
    "review": {
      "id": 1,
      "title": "Excellent product!",
      "body": "I am very satisfied with this purchase. The seller was professional and the item arrived as described.",
      "stars": 5,
      "target_type": "ad",
      "user": {
        "id": 2,
        "name": "Jane Smith",
        "email": "jane@example.com"
      },
      "seller": {
        "id": 3,
        "name": "Mike Johnson",
        "email": "mike@example.com",
        "avg_rating": 4.75,
        "reviews_count": 8
      },
      "ad": {
        "id": 5,
        "title": "iPhone 13 Pro Max 256GB",
        "price": 999.99,
        "avg_rating": 4.80,
        "reviews_count": 15
      },
      "can_edit": false,
      "can_delete": false,
      "created_at": "2026-01-15T10:30:00.000000Z",
      "updated_at": "2026-01-15T10:30:00.000000Z"
    }
  }
}
```

### 4. Get Reviews for Specific Ad (Public)

```bash
curl -X GET 'http://localhost:8000/api/v1/ads/5/reviews' \
  --header 'Accept: application/json'
```

**Response:**
```json
{
  "status": "success",
  "code": 200,
  "message": "Reviews for ad retrieved successfully",
  "data": {
    "reviews": {
      "data": [
        {
          "id": 1,
          "title": "Excellent product!",
          "body": "I am very satisfied with this purchase.",
          "stars": 5,
          "target_type": "ad",
          "user": {
            "id": 2,
            "name": "Jane Smith"
          },
          "seller": {
            "id": 3,
            "name": "Mike Johnson"
          },
          "ad": {
            "id": 5,
            "title": "iPhone 13 Pro Max 256GB"
          },
          "created_at": "2026-01-15T10:30:00.000000Z"
        }
      ],
      "pagination": {
        "current_page": 1,
        "per_page": 15,
        "total": 8,
        "last_page": 1
      }
    }
  }
}
```

### 5. Get Reviews for Specific User/Seller (Public)

```bash
curl -X GET 'http://localhost:8000/api/v1/users/3/reviews' \
  --header 'Accept: application/json'
```

**Response:**
```json
{
  "status": "success",
  "code": 200,
  "message": "Reviews for user retrieved successfully",
  "data": {
    "reviews": {
      "data": [
        {
          "id": 1,
          "title": "Excellent product!",
          "body": "I am very satisfied with this purchase.",
          "stars": 5,
          "target_type": "ad",
          "user": {
            "id": 2,
            "name": "Jane Smith"
          },
          "seller": {
            "id": 3,
            "name": "Mike Johnson",
            "avg_rating": 4.75,
            "reviews_count": 8
          },
          "created_at": "2026-01-15T10:30:00.000000Z"
        }
      ],
      "pagination": {
        "current_page": 1,
        "per_page": 15,
        "total": 12,
        "last_page": 1
      }
    }
  }
}
```

### 6. Create Review for Ad (Protected, Rate Limited)

```bash
curl -X POST 'http://localhost:8000/api/v1/reviews' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'Authorization: Bearer 4|wrtCvSB6lQn20CWclSAqhi4H0mx9dmPgKRAtZzUe6eff0f6c' \
  --data '{
    "target_type": "ad",
    "target_id": 5,
    "title": "Great experience!",
    "body": "The product was exactly as described and arrived quickly. Very happy with my purchase!",
    "stars": 5
  }'
```

**Success Response (201):**
```json
{
  "status": "success",
  "code": 201,
  "message": "Review created successfully",
  "data": {
    "review": {
      "id": 43,
      "title": "Great experience!",
      "body": "The product was exactly as described and arrived quickly. Very happy with my purchase!",
      "stars": 5,
      "target_type": "ad",
      "user_id": 2,
      "seller_id": 3,
      "ad_id": 5,
      "user": {
        "id": 2,
        "name": "Jane Smith",
        "email": "jane@example.com"
      },
      "seller": {
        "id": 3,
        "name": "Mike Johnson",
        "email": "mike@example.com",
        "avg_rating": 4.78,
        "reviews_count": 9
      },
      "ad": {
        "id": 5,
        "title": "iPhone 13 Pro Max 256GB",
        "avg_rating": 4.85,
        "reviews_count": 16
      },
      "can_edit": true,
      "can_delete": true,
      "created_at": "2026-02-01T14:25:30.000000Z",
      "updated_at": "2026-02-01T14:25:30.000000Z"
    }
  }
}
```

**Headers in Response:**
```
X-RateLimit-Limit: 10
X-RateLimit-Remaining: 9
```

### 7. Create Review for Seller Only (No Specific Ad)

```bash
curl -X POST 'http://localhost:8000/api/v1/reviews' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'Authorization: Bearer 4|wrtCvSB6lQn20CWclSAqhi4H0mx9dmPgKRAtZzUe6eff0f6c' \
  --data '{
    "target_type": "seller",
    "target_id": 3,
    "title": "Professional seller",
    "body": "Very responsive and helpful throughout the transaction. Would buy from again!",
    "stars": 4
  }'
```

**Success Response (201):**
```json
{
  "status": "success",
  "code": 201,
  "message": "Review created successfully",
  "data": {
    "review": {
      "id": 44,
      "title": "Professional seller",
      "body": "Very responsive and helpful throughout the transaction. Would buy from again!",
      "stars": 4,
      "target_type": "seller",
      "user_id": 2,
      "seller_id": 3,
      "ad_id": null,
      "user": {
        "id": 2,
        "name": "Jane Smith"
      },
      "seller": {
        "id": 3,
        "name": "Mike Johnson",
        "avg_rating": 4.70,
        "reviews_count": 10
      },
      "ad": null,
      "can_edit": true,
      "can_delete": true,
      "created_at": "2026-02-01T14:30:15.000000Z",
      "updated_at": "2026-02-01T14:30:15.000000Z"
    }
  }
}
```

### 8. Get My Reviews (Protected)

```bash
curl -X GET 'http://localhost:8000/api/v1/reviews/my-reviews' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer 4|wrtCvSB6lQn20CWclSAqhi4H0mx9dmPgKRAtZzUe6eff0f6c'
```

**Response:**
```json
{
  "status": "success",
  "code": 200,
  "message": "Your reviews retrieved successfully",
  "data": {
    "reviews": {
      "data": [
        {
          "id": 43,
          "title": "Great experience!",
          "body": "The product was exactly as described and arrived quickly.",
          "stars": 5,
          "target_type": "ad",
          "seller": {
            "id": 3,
            "name": "Mike Johnson"
          },
          "ad": {
            "id": 5,
            "title": "iPhone 13 Pro Max 256GB"
          },
          "can_edit": true,
          "can_delete": true,
          "created_at": "2026-02-01T14:25:30.000000Z"
        }
      ],
      "pagination": {
        "current_page": 1,
        "per_page": 15,
        "total": 5,
        "last_page": 1
      }
    }
  }
}
```

### 9. Update Review (Protected - Owner or Admin)

```bash
curl -X PUT 'http://localhost:8000/api/v1/reviews/43' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'Authorization: Bearer 4|wrtCvSB6lQn20CWclSAqhi4H0mx9dmPgKRAtZzUe6eff0f6c' \
  --data '{
    "title": "Updated: Great experience!",
    "body": "After using it for a week, I am even more satisfied. The product exceeded my expectations!",
    "stars": 5
  }'
```

**Success Response (200):**
```json
{
  "status": "success",
  "code": 200,
  "message": "Review updated successfully",
  "data": {
    "review": {
      "id": 43,
      "title": "Updated: Great experience!",
      "body": "After using it for a week, I am even more satisfied. The product exceeded my expectations!",
      "stars": 5,
      "target_type": "ad",
      "user": {
        "id": 2,
        "name": "Jane Smith"
      },
      "seller": {
        "id": 3,
        "name": "Mike Johnson",
        "avg_rating": 4.78,
        "reviews_count": 9
      },
      "ad": {
        "id": 5,
        "title": "iPhone 13 Pro Max 256GB",
        "avg_rating": 4.85,
        "reviews_count": 16
      },
      "can_edit": true,
      "can_delete": true,
      "created_at": "2026-02-01T14:25:30.000000Z",
      "updated_at": "2026-02-01T15:10:45.000000Z"
    }
  }
}
```

### 10. Delete Review (Protected - Owner or Admin)

```bash
curl -X DELETE 'http://localhost:8000/api/v1/reviews/43' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer 4|wrtCvSB6lQn20CWclSAqhi4H0mx9dmPgKRAtZzUe6eff0f6c'
```

**Success Response (200):**
```json
{
  "status": "success",
  "code": 200,
  "message": "Review deleted successfully",
  "data": {}
}
```

---

## REPORTS API

### 11. Create Report for Ad (Protected, Rate Limited)

```bash
curl -X POST 'http://localhost:8000/api/v1/reports' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'Authorization: Bearer 4|wrtCvSB6lQn20CWclSAqhi4H0mx9dmPgKRAtZzUe6eff0f6c' \
  --data '{
    "target_type": "ad",
    "target_id": 7,
    "reason": "spam",
    "title": "Spam advertisement",
    "details": "This ad is clearly spam and contains misleading information about the product specifications."
  }'
```

**Success Response (201):**
```json
{
  "status": "success",
  "code": 201,
  "message": "Report submitted successfully",
  "data": {
    "report": {
      "id": 15,
      "reason": "spam",
      "title": "Spam advertisement",
      "details": "This ad is clearly spam and contains misleading information about the product specifications.",
      "status": "open",
      "status_label": "Open",
      "target": {
        "type": "ad",
        "id": 7,
        "title": "iPhone 14 - Too Good to be True",
        "price": 50.00
      },
      "created_at": "2026-02-01T14:35:20.000000Z",
      "updated_at": "2026-02-01T14:35:20.000000Z"
    }
  }
}
```

**Headers in Response:**
```
X-RateLimit-Limit: 10
X-RateLimit-Remaining: 9
```

### 12. Create Report for User

```bash
curl -X POST 'http://localhost:8000/api/v1/reports' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'Authorization: Bearer 4|wrtCvSB6lQn20CWclSAqhi4H0mx9dmPgKRAtZzUe6eff0f6c' \
  --data '{
    "target_type": "user",
    "target_id": 8,
    "reason": "fraud",
    "title": "Fraudulent seller",
    "details": "This user is engaging in fraudulent activities. They took payment but never delivered the product and are now unresponsive."
  }'
```

**Success Response (201):**
```json
{
  "status": "success",
  "code": 201,
  "message": "Report submitted successfully",
  "data": {
    "report": {
      "id": 16,
      "reason": "fraud",
      "title": "Fraudulent seller",
      "details": "This user is engaging in fraudulent activities. They took payment but never delivered the product and are now unresponsive.",
      "status": "open",
      "status_label": "Open",
      "target": {
        "type": "user",
        "id": 8,
        "name": "Suspicious User",
        "email": "suspicious@example.com"
      },
      "created_at": "2026-02-01T14:40:10.000000Z",
      "updated_at": "2026-02-01T14:40:10.000000Z"
    }
  }
}
```

### 13. Get My Reports (Protected)

```bash
curl -X GET 'http://localhost:8000/api/v1/reports/my-reports' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer 4|wrtCvSB6lQn20CWclSAqhi4H0mx9dmPgKRAtZzUe6eff0f6c'
```

**Response:**
```json
{
  "status": "success",
  "code": 200,
  "message": "Your reports retrieved successfully",
  "data": {
    "reports": {
      "data": [
        {
          "id": 15,
          "reason": "spam",
          "title": "Spam advertisement",
          "details": "This ad is clearly spam...",
          "status": "open",
          "status_label": "Open",
          "target": {
            "type": "ad",
            "id": 7,
            "title": "iPhone 14 - Too Good to be True"
          },
          "created_at": "2026-02-01T14:35:20.000000Z",
          "updated_at": "2026-02-01T14:35:20.000000Z"
        },
        {
          "id": 16,
          "reason": "fraud",
          "title": "Fraudulent seller",
          "details": "This user is engaging in fraudulent activities...",
          "status": "under_review",
          "status_label": "Under Review",
          "target": {
            "type": "user",
            "id": 8,
            "name": "Suspicious User"
          },
          "created_at": "2026-02-01T14:40:10.000000Z",
          "updated_at": "2026-02-01T15:00:00.000000Z"
        }
      ],
      "pagination": {
        "current_page": 1,
        "per_page": 15,
        "total": 2,
        "last_page": 1
      }
    }
  }
}
```

### 14. View Single Report (Protected - Owner/Assigned/Admin)

```bash
curl -X GET 'http://localhost:8000/api/v1/reports/15' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer 4|wrtCvSB6lQn20CWclSAqhi4H0mx9dmPgKRAtZzUe6eff0f6c'
```

**Response (Regular User - Reporter):**
```json
{
  "status": "success",
  "code": 200,
  "message": "Report retrieved successfully",
  "data": {
    "report": {
      "id": 15,
      "reason": "spam",
      "title": "Spam advertisement",
      "details": "This ad is clearly spam and contains misleading information about the product specifications.",
      "status": "open",
      "status_label": "Open",
      "target": {
        "type": "ad",
        "id": 7,
        "title": "iPhone 14 - Too Good to be True",
        "price": 50.00
      },
      "created_at": "2026-02-01T14:35:20.000000Z",
      "updated_at": "2026-02-01T14:35:20.000000Z"
    }
  }
}
```

**Response (Admin - Shows Additional Fields):**
```json
{
  "status": "success",
  "code": 200,
  "message": "Report retrieved successfully",
  "data": {
    "report": {
      "id": 15,
      "reason": "spam",
      "title": "Spam advertisement",
      "details": "This ad is clearly spam and contains misleading information about the product specifications.",
      "status": "open",
      "status_label": "Open",
      "reporter": {
        "id": 2,
        "name": "Jane Smith",
        "email": "jane@example.com"
      },
      "assigned_to": null,
      "target": {
        "type": "ad",
        "id": 7,
        "title": "iPhone 14 - Too Good to be True",
        "price": 50.00,
        "user": {
          "id": 8,
          "name": "Suspicious User"
        }
      },
      "created_at": "2026-02-01T14:35:20.000000Z",
      "updated_at": "2026-02-01T14:35:20.000000Z"
    }
  }
}
```

### 15. Admin: List All Reports (Admin/Moderator Only)

```bash
curl -X GET 'http://localhost:8000/api/v1/reports/admin/index' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer 7|adminTokenXYZ123456789abcdefghijklmnopqrst'
```

**Response:**
```json
{
  "status": "success",
  "code": 200,
  "message": "Reports retrieved successfully",
  "data": {
    "reports": {
      "data": [
        {
          "id": 15,
          "reason": "spam",
          "title": "Spam advertisement",
          "details": "This ad is clearly spam...",
          "status": "open",
          "status_label": "Open",
          "reporter": {
            "id": 2,
            "name": "Jane Smith",
            "email": "jane@example.com"
          },
          "assigned_to": null,
          "target": {
            "type": "ad",
            "id": 7,
            "title": "iPhone 14 - Too Good to be True"
          },
          "created_at": "2026-02-01T14:35:20.000000Z",
          "updated_at": "2026-02-01T14:35:20.000000Z"
        },
        {
          "id": 16,
          "reason": "fraud",
          "title": "Fraudulent seller",
          "details": "This user is engaging in fraudulent activities...",
          "status": "under_review",
          "status_label": "Under Review",
          "reporter": {
            "id": 2,
            "name": "Jane Smith",
            "email": "jane@example.com"
          },
          "assigned_to": {
            "id": 4,
            "name": "Moderator John",
            "email": "moderator@example.com"
          },
          "target": {
            "type": "user",
            "id": 8,
            "name": "Suspicious User"
          },
          "created_at": "2026-02-01T14:40:10.000000Z",
          "updated_at": "2026-02-01T15:00:00.000000Z"
        }
      ],
      "pagination": {
        "current_page": 1,
        "per_page": 15,
        "total": 48,
        "last_page": 4
      }
    },
    "statistics": {
      "total": 48,
      "open": 12,
      "under_review": 18,
      "resolved": 15,
      "closed": 3
    }
  }
}
```

### 16. Admin: Filter Reports by Status

```bash
curl -X GET 'http://localhost:8000/api/v1/reports/admin/index?status=open' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer 7|adminTokenXYZ123456789abcdefghijklmnopqrst'
```

**Response:**
```json
{
  "status": "success",
  "code": 200,
  "message": "Reports retrieved successfully",
  "data": {
    "reports": {
      "data": [
        {
          "id": 15,
          "reason": "spam",
          "title": "Spam advertisement",
          "status": "open",
          "status_label": "Open",
          "reporter": {
            "id": 2,
            "name": "Jane Smith"
          },
          "target": {
            "type": "ad",
            "id": 7,
            "title": "iPhone 14 - Too Good to be True"
          },
          "created_at": "2026-02-01T14:35:20.000000Z"
        }
      ],
      "pagination": {
        "current_page": 1,
        "per_page": 15,
        "total": 12,
        "last_page": 1
      }
    }
  }
}
```

### 17. Admin: Filter Reports by Target Type

```bash
curl -X GET 'http://localhost:8000/api/v1/reports/admin/index?target_type=ad' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer 7|adminTokenXYZ123456789abcdefghijklmnopqrst'
```

**Response:** Same structure as admin list, filtered by target_type

### 18. Admin: Filter Reports by Date Range

```bash
curl -X GET 'http://localhost:8000/api/v1/reports/admin/index?from_date=2026-01-01&to_date=2026-01-31' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer 7|adminTokenXYZ123456789abcdefghijklmnopqrst'
```

**Response:** Same structure as admin list, filtered by date range

### 19. Admin: Filter Reports by Assigned Moderator

```bash
curl -X GET 'http://localhost:8000/api/v1/reports/admin/index?assigned_to=4' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer 7|adminTokenXYZ123456789abcdefghijklmnopqrst'
```

**Response:** Same structure as admin list, showing only reports assigned to moderator ID 4

### 20. Admin: Combined Filters

```bash
curl -X GET 'http://localhost:8000/api/v1/reports/admin/index?status=under_review&target_type=ad&assigned_to=4&limit=20' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer 7|adminTokenXYZ123456789abcdefghijklmnopqrst'
```

**Response:** Same structure as admin list, with all filters applied

### 21. Admin: Assign Report to Moderator (Admin Only)

```bash
curl -X POST 'http://localhost:8000/api/v1/reports/15/assign' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'Authorization: Bearer 7|adminTokenXYZ123456789abcdefghijklmnopqrst' \
  --data '{
    "moderator_id": 4
  }'
```

**Success Response (200):**
```json
{
  "status": "success",
  "code": 200,
  "message": "Report assigned successfully",
  "data": {
    "report": {
      "id": 15,
      "reason": "spam",
      "title": "Spam advertisement",
      "details": "This ad is clearly spam...",
      "status": "under_review",
      "status_label": "Under Review",
      "reporter": {
        "id": 2,
        "name": "Jane Smith"
      },
      "assigned_to": {
        "id": 4,
        "name": "Moderator John",
        "email": "moderator@example.com",
        "role": "moderator"
      },
      "target": {
        "type": "ad",
        "id": 7,
        "title": "iPhone 14 - Too Good to be True"
      },
      "created_at": "2026-02-01T14:35:20.000000Z",
      "updated_at": "2026-02-01T16:00:00.000000Z"
    }
  }
}
```

### 22. Admin/Moderator: Update Report Status

```bash
curl -X PUT 'http://localhost:8000/api/v1/reports/15/status' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'Authorization: Bearer 7|adminTokenXYZ123456789abcdefghijklmnopqrst' \
  --data '{
    "status": "resolved",
    "admin_message": "We have investigated this report and removed the offending ad. Thank you for reporting."
  }'
```

**Success Response (200):**
```json
{
  "status": "success",
  "code": 200,
  "message": "Report status updated successfully",
  "data": {
    "report": {
      "id": 15,
      "reason": "spam",
      "title": "Spam advertisement",
      "details": "This ad is clearly spam...",
      "status": "resolved",
      "status_label": "Resolved",
      "reporter": {
        "id": 2,
        "name": "Jane Smith"
      },
      "assigned_to": {
        "id": 4,
        "name": "Moderator John"
      },
      "target": {
        "type": "ad",
        "id": 7,
        "title": "iPhone 14 - Too Good to be True"
      },
      "created_at": "2026-02-01T14:35:20.000000Z",
      "updated_at": "2026-02-01T16:30:15.000000Z"
    }
  }
}
```

### 23. Admin/Moderator: Resolve Report

```bash
curl -X POST 'http://localhost:8000/api/v1/reports/15/actions/resolve' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'Authorization: Bearer 7|adminTokenXYZ123456789abcdefghijklmnopqrst' \
  --data '{
    "admin_message": "The reported content has been removed and the user has been warned. We appreciate your vigilance in keeping our platform safe."
  }'
```

**Success Response (200):**
```json
{
  "status": "success",
  "code": 200,
  "message": "Report resolved successfully",
  "data": {
    "report": {
      "id": 15,
      "reason": "spam",
      "title": "Spam advertisement",
      "details": "This ad is clearly spam...",
      "status": "resolved",
      "status_label": "Resolved",
      "reporter": {
        "id": 2,
        "name": "Jane Smith"
      },
      "assigned_to": {
        "id": 4,
        "name": "Moderator John"
      },
      "target": {
        "type": "ad",
        "id": 7,
        "title": "iPhone 14 - Too Good to be True"
      },
      "created_at": "2026-02-01T14:35:20.000000Z",
      "updated_at": "2026-02-01T17:00:00.000000Z"
    }
  }
}
```

**Note:** Reporter receives `ReportResolvedNotification` in their database notifications.

### 24. Admin/Moderator: Close Report

```bash
curl -X POST 'http://localhost:8000/api/v1/reports/16/actions/close' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'Authorization: Bearer 7|adminTokenXYZ123456789abcdefghijklmnopqrst' \
  --data '{
    "admin_message": "After thorough investigation, we found no evidence of fraudulent activity. The issue appears to be a misunderstanding between parties."
  }'
```

**Success Response (200):**
```json
{
  "status": "success",
  "code": 200,
  "message": "Report closed successfully",
  "data": {
    "report": {
      "id": 16,
      "reason": "fraud",
      "title": "Fraudulent seller",
      "details": "This user is engaging in fraudulent activities...",
      "status": "closed",
      "status_label": "Closed",
      "reporter": {
        "id": 2,
        "name": "Jane Smith"
      },
      "assigned_to": {
        "id": 4,
        "name": "Moderator John"
      },
      "target": {
        "type": "user",
        "id": 8,
        "name": "Suspicious User"
      },
      "created_at": "2026-02-01T14:40:10.000000Z",
      "updated_at": "2026-02-01T17:15:30.000000Z"
    }
  }
}
```

**Note:** Reporter receives `ReportResolvedNotification` in their database notifications.

### 25. Admin: Delete Report (Admin Only)

```bash
curl -X DELETE 'http://localhost:8000/api/v1/reports/15' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer 7|adminTokenXYZ123456789abcdefghijklmnopqrst'
```

**Success Response (200):**
```json
{
  "status": "success",
  "code": 200,
  "message": "Report deleted successfully",
  "data": {}
}
```

---

## ERROR RESPONSES

### 1. Validation Error (422) - Missing Required Fields

```bash
curl -X POST 'http://localhost:8000/api/v1/reviews' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'Authorization: Bearer 4|wrtCvSB6lQn20CWclSAqhi4H0mx9dmPgKRAtZzUe6eff0f6c' \
  --data '{
    "target_type": "ad"
  }'
```

**Response:**
```json
{
  "status": "error",
  "code": 422,
  "message": "Validation failed",
  "errors": {
    "target_id": [
      "The target id field is required."
    ],
    "title": [
      "The title field is required."
    ],
    "body": [
      "The body field is required."
    ],
    "stars": [
      "The stars field is required."
    ]
  }
}
```

### 2. Validation Error (422) - Invalid Stars Rating

```bash
curl -X POST 'http://localhost:8000/api/v1/reviews' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'Authorization: Bearer 4|wrtCvSB6lQn20CWclSAqhi4H0mx9dmPgKRAtZzUe6eff0f6c' \
  --data '{
    "target_type": "ad",
    "target_id": 5,
    "title": "Test",
    "body": "Test body",
    "stars": 6
  }'
```

**Response:**
```json
{
  "status": "error",
  "code": 422,
  "message": "Validation failed",
  "errors": {
    "stars": [
      "The stars field must not be greater than 5."
    ]
  }
}
```

### 3. Validation Error (422) - Duplicate Review

```bash
curl -X POST 'http://localhost:8000/api/v1/reviews' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'Authorization: Bearer 4|wrtCvSB6lQn20CWclSAqhi4H0mx9dmPgKRAtZzUe6eff0f6c' \
  --data '{
    "target_type": "ad",
    "target_id": 5,
    "title": "Another review",
    "body": "Trying to review again",
    "stars": 4
  }'
```

**Response:**
```json
{
  "status": "error",
  "code": 422,
  "message": "Validation failed",
  "errors": {
    "ad_id": [
      "You have already reviewed this ad."
    ]
  }
}
```

### 4. Validation Error (422) - Self-Review Prevention

```bash
curl -X POST 'http://localhost:8000/api/v1/reviews' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'Authorization: Bearer 4|wrtCvSB6lQn20CWclSAqhi4H0mx9dmPgKRAtZzUe6eff0f6c' \
  --data '{
    "target_type": "ad",
    "target_id": 10,
    "title": "My own ad",
    "body": "Reviewing my own ad",
    "stars": 5
  }'
```

**Response:**
```json
{
  "status": "error",
  "code": 422,
  "message": "Validation failed",
  "errors": {
    "ad_id": [
      "You cannot review your own ad."
    ]
  }
}
```

### 5. Validation Error (422) - Duplicate Report within 24 Hours

```bash
curl -X POST 'http://localhost:8000/api/v1/reports' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'Authorization: Bearer 4|wrtCvSB6lQn20CWclSAqhi4H0mx9dmPgKRAtZzUe6eff0f6c' \
  --data '{
    "target_type": "ad",
    "target_id": 7,
    "reason": "spam",
    "title": "Still spam",
    "details": "Trying to report again"
  }'
```

**Response:**
```json
{
  "status": "error",
  "code": 422,
  "message": "Validation failed",
  "errors": {
    "target_id": [
      "You have already reported this item within the last 24 hours."
    ]
  }
}
```

### 6. Unauthorized (401) - No Token

```bash
curl -X POST 'http://localhost:8000/api/v1/reviews' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
    "target_type": "ad",
    "target_id": 5,
    "title": "Test",
    "body": "Test",
    "stars": 5
  }'
```

**Response:**
```json
{
  "status": "error",
  "code": 401,
  "message": "Unauthenticated",
  "errors": {}
}
```

### 7. Forbidden (403) - Cannot Edit Other's Review

```bash
curl -X PUT 'http://localhost:8000/api/v1/reviews/50' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'Authorization: Bearer 4|wrtCvSB6lQn20CWclSAqhi4H0mx9dmPgKRAtZzUe6eff0f6c' \
  --data '{
    "title": "Hacked",
    "body": "Trying to edit",
    "stars": 1
  }'
```

**Response:**
```json
{
  "status": "error",
  "code": 403,
  "message": "Forbidden",
  "errors": {}
}
```

### 8. Forbidden (403) - Regular User Accessing Admin Endpoint

```bash
curl -X GET 'http://localhost:8000/api/v1/reports/admin/index' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer 4|wrtCvSB6lQn20CWclSAqhi4H0mx9dmPgKRAtZzUe6eff0f6c'
```

**Response:**
```json
{
  "status": "error",
  "code": 403,
  "message": "Forbidden",
  "errors": {}
}
```

### 9. Not Found (404) - Review Doesn't Exist

```bash
curl -X GET 'http://localhost:8000/api/v1/reviews/99999' \
  --header 'Accept: application/json'
```

**Response:**
```json
{
  "status": "error",
  "code": 404,
  "message": "Review not found",
  "errors": {}
}
```

### 10. Rate Limit Exceeded (429) - Too Many Requests

```bash
# After 10 review creations within 1 hour
curl -X POST 'http://localhost:8000/api/v1/reviews' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'Authorization: Bearer 4|wrtCvSB6lQn20CWclSAqhi4H0mx9dmPgKRAtZzUe6eff0f6c' \
  --data '{
    "target_type": "ad",
    "target_id": 15,
    "title": "11th review",
    "body": "This should be blocked",
    "stars": 5
  }'
```

**Response:**
```json
{
  "status": "error",
  "code": 429,
  "message": "Too many review submissions. Please try again later.",
  "errors": {},
  "retry_after": 3450
}
```

**Headers in Response:**
```
X-RateLimit-Limit: 10
X-RateLimit-Remaining: 0
Retry-After: 3450
```

---

## AVAILABLE REPORT REASONS

When creating a report, you can use these reason values:
- `spam` - Spam content
- `fraud` - Fraudulent activity
- `inappropriate` - Inappropriate content
- `misleading` - Misleading information
- `scam` - Scam attempt
- `offensive` - Offensive material
- `duplicate` - Duplicate listing
- `counterfeit` - Counterfeit products
- `harassment` - Harassment or bullying
- `violence` - Violent content
- `illegal` - Illegal activity
- `copyright` - Copyright violation
- `other` - Other reason (explain in details)

---

## NOTES

1. **Rate Limiting**: Both reviews and reports creation endpoints are limited to **10 requests per hour** per authenticated user. Headers indicate remaining limit.

2. **Authentication**: Most endpoints require authentication via Bearer token. Public endpoints: list reviews, view review, ad reviews, user reviews.

3. **Notifications**: 
   - Sellers automatically receive `ReviewReceivedNotification` when they get a review
   - Reporters receive `ReportResolvedNotification` when their report is resolved or closed
   - Notifications are queued and processed asynchronously

4. **Rating Aggregation**: Creating, updating, or deleting reviews automatically updates `avg_rating` and `reviews_count` on related ads and users in real-time via ReviewObserver.

5. **Report Status Flow**:
   - `open` (default when created)
   - `under_review` (automatically set when assigned to moderator)
   - `resolved` (action taken)
   - `closed` (no action needed)

6. **Pagination**: Default 15 items per page, maximum 50 items per page. Use `?page=2&limit=25` to customize.

7. **Filtering**: Reviews support `min_stars`, `ad_id`, `seller_id`. Reports support `status`, `target_type`, `assigned_to`, `from_date`, `to_date`.

8. **Permissions**:
   - Reviews: Owner or admin can edit/delete
   - Reports: Admin can delete, assign. Admin/moderator/assigned can update status
   - Resource fields vary based on user role (admin sees more details)
