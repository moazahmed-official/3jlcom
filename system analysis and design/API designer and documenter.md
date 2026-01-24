# API Designer & Documenter

Detailed API documentation with request/response examples, authorization requirements, and function descriptions.

---

## Admin APIs

### 1. Admin Login

| Field | Value |
|-------|-------|
| **API Name** | Admin Login |
| **Method** | POST |
| **Route** | /api/admin/auth/login |
| **Authorization** | None (Public) |
| **Function Description** | Authenticate admin user and return JWT token for subsequent requests. |
| **Description** | تسجيل دخول الادمن |

**Request Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "email": "admin@smartcars.com",
  "password": "SecurePassword123!",
  "remember_me": false
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "Admin User",
      "email": "admin@smartcars.com",
      "role": "super_admin"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "token_type": "Bearer",
    "expires_in": 3600
  }
}
```

**Error Response (401):**
```json
{
  "success": false,
  "message": "Invalid credentials",
  "errors": {
    "email": ["The provided credentials do not match our records."]
  }
}
```

---

### 2. List Users

| Field | Value |
|-------|-------|
| **API Name** | List Users |
| **Method** | GET |
| **Route** | /api/admin/users |
| **Authorization** | Admin, Country Manager |
| **Function Description** | Retrieve paginated list of all users with optional filters (role, country, status). |
| **Description** | جلب كل المستخدمين |

**Request Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Query Parameters:**
```
?page=1&per_page=20&role=individual&country_id=1&status=active
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Users retrieved successfully",
  "data": {
    "users": [
      {
        "id": 5,
        "name": "Ahmed Al-Mansouri",
        "phone": "+962791234567",
        "email": "ahmed@example.com",
        "role": "individual",
        "country": "Jordan",
        "status": "active",
        "is_verified": false,
        "created_at": "2026-01-15T10:30:00Z"
      },
      {
        "id": 6,
        "name": "Showroom Pro",
        "phone": "+962792234567",
        "email": "showroom@example.com",
        "role": "showroom",
        "country": "Jordan",
        "status": "active",
        "is_verified": true,
        "created_at": "2026-01-10T14:20:00Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 156,
      "total_pages": 8
    }
  }
}
```

**Error Response (403):**
```json
{
  "success": false,
  "message": "Unauthorized. Admin access required.",
  "errors": {}
}
```

---

### 3. Create User

| Field | Value |
|-------|-------|
| **API Name** | Create User |
| **Method** | POST |
| **Route** | /api/admin/users |
| **Authorization** | Admin, Country Manager |
| **Function Description** | Create a new user account with specified role, country, and optional details. |
| **Description** | إنشاء مستخدم جديد |

**Request Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "name": "Fatima Al-Rashid",
  "phone": "+962793456789",
  "email": "fatima@example.com",
  "country_id": 1,
  "city_id": 5,
  "role": "dealer",
  "password": "SecurePass123!",
  "password_confirmation": "SecurePass123!",
  "is_verified": false
}
```

**Success Response (201):**
```json
{
  "success": true,
  "message": "User created successfully",
  "data": {
    "id": 157,
    "name": "Fatima Al-Rashid",
    "phone": "+962793456789",
    "email": "fatima@example.com",
    "country_id": 1,
    "city_id": 5,
    "role": "dealer",
    "is_verified": false,
    "created_at": "2026-01-24T12:00:00Z"
  }
}
```

**Error Response (422):**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email has already been taken."],
    "phone": ["The phone has already been taken."]
  }
}
```

---

### 4. Create Ad (Admin)

| Field | Value |
|-------|-------|
| **API Name** | Create Ad |
| **Method** | POST |
| **Route** | /api/admin/ads |
| **Authorization** | Admin, Country Manager |
| **Function Description** | Create a new ad of any type (Normal, Featured, Caishha, FindIt, Auction) with full specs. |
| **Description** | إنشاء إعلان عام (all ad types) |

**Request Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "seller_id": 45,
  "ad_type": "normal",
  "title": "2019 Toyota Corolla XLI",
  "description": "Well-maintained sedan, single owner, no accidents",
  "category_id": 2,
  "city_id": 3,
  "country_id": 1,
  "car_details": {
    "brand_id": 12,
    "model_id": 156,
    "year": 2019,
    "color": "Silver",
    "body_type": "Sedan",
    "fuel_type": "Petrol",
    "transmission": "Automatic",
    "owners_count": 1,
    "mileage": 45000,
    "is_customs_cleared": true
  },
  "price": {
    "price_cash": 12500,
    "currency": "JOD"
  },
  "contact": {
    "phone": "+962791234567",
    "whatsapp": "+962791234567"
  },
  "media": {
    "images": [
      "https://cdn.smartcars.local/image1.jpg",
      "https://cdn.smartcars.local/image2.jpg"
    ],
    "video_url": "https://cdn.smartcars.local/video.mp4"
  },
  "period_days": 30,
  "push_facebook": false
}
```

**Success Response (201):**
```json
{
  "success": true,
  "message": "Ad created successfully",
  "data": {
    "id": 2456,
    "seller_id": 45,
    "ad_type": "normal",
    "title": "2019 Toyota Corolla XLI",
    "status": "published",
    "price": 12500,
    "views_count": 0,
    "created_at": "2026-01-24T12:30:00Z",
    "expires_at": "2026-02-23T23:59:59Z"
  }
}
```

**Error Response (422):**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "seller_id": ["The seller does not exist or is not verified."],
    "price_cash": ["Price must be greater than 0."]
  }
}
```

---

## User APIs

### 5. User Registration

| Field | Value |
|-------|-------|
| **API Name** | Register Account |
| **Method** | POST |
| **Route** | /api/user/auth/register |
| **Authorization** | None (Public) |
| **Function Description** | Register a new user with phone number, OTP verification required post-signup. |
| **Description** | إنشاء حساب مستخدم جديد |

**Request Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "name": "Mohammed Sharqi",
  "phone": "+962791122334",
  "country_id": 1,
  "password": "MySecurePass456!",
  "password_confirmation": "MySecurePass456!",
  "account_type": "individual"
}
```

**Success Response (201):**
```json
{
  "success": true,
  "message": "Registration successful. Please verify your phone.",
  "data": {
    "id": 200,
    "name": "Mohammed Sharqi",
    "phone": "+962791122334",
    "country": "Jordan",
    "account_type": "individual",
    "is_verified": false,
    "otp_sent_at": "2026-01-24T13:00:00Z"
  }
}
```

**Error Response (422):**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "phone": ["The phone number has already been registered."]
  }
}
```

---

### 6. User Login

| Field | Value |
|-------|-------|
| **API Name** | Login |
| **Method** | POST |
| **Route** | /api/user/auth/login |
| **Authorization** | None (Public) |
| **Function Description** | Authenticate user with phone and password, return JWT token. |
| **Description** | تسجيل دخول المستخدم |

**Request Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "phone": "+962791122334",
  "password": "MySecurePass456!",
  "remember_me": true
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 200,
      "name": "Mohammed Sharqi",
      "phone": "+962791122334",
      "country": "Jordan",
      "account_type": "individual",
      "is_verified": true
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "token_type": "Bearer",
    "expires_in": 604800
  }
}
```

**Error Response (401):**
```json
{
  "success": false,
  "message": "Invalid phone or password",
  "errors": {}
}
```

---

### 7. Create Normal Ad (User)

| Field | Value |
|-------|-------|
| **API Name** | Create Ad |
| **Method** | POST |
| **Route** | /api/user/normal_ad |
| **Authorization** | Individual, Dealer, Showroom |
| **Function Description** | Create a new normal ad with car specs, media, and contact details. |
| **Description** | إنشاء إعلان عادي جديد |

**Request Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "title": "2020 Hyundai Tucson GLX",
  "description": "Family car, excellent condition, low mileage",
  "category_id": 2,
  "car_details": {
    "brand_id": 8,
    "model_id": 98,
    "year": 2020,
    "color": "Blue",
    "body_type": "SUV",
    "fuel_type": "Petrol",
    "transmission": "Automatic",
    "owners_count": 1,
    "mileage": 25000,
    "is_customs_cleared": true
  },
  "price": {
    "price_cash": 18500,
    "currency": "JOD"
  },
  "contact": {
    "phone": "+962791234567",
    "whatsapp": "+962791234567"
  },
  "city_id": 3,
  "period_days": 30,
  "push_facebook": true,
  "images": [
    "https://cdn.smartcars.local/user-ads/hyundai1.jpg",
    "https://cdn.smartcars.local/user-ads/hyundai2.jpg"
  ]
}
```

**Success Response (201):**
```json
{
  "success": true,
  "message": "Ad created successfully",
  "data": {
    "id": 5678,
    "user_id": 200,
    "ad_type": "normal",
    "title": "2020 Hyundai Tucson GLX",
    "status": "pending_review",
    "price": 18500,
    "views_count": 0,
    "created_at": "2026-01-24T14:15:00Z",
    "expires_at": "2026-02-23T23:59:59Z"
  }
}
```

**Error Response (422):**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "price_cash": ["Price is required for normal ads."],
    "title": ["Title must be between 5 and 150 characters."]
  }
}
```

---

### 8. List User Ads

| Field | Value |
|-------|-------|
| **API Name** | List Ads |
| **Method** | GET |
| **Route** | /api/user/normal_ad |
| **Authorization** | Individual, Dealer, Showroom (own ads only) |
| **Function Description** | Retrieve user's normal ads with pagination and filters (status, date range). |
| **Description** | عرض إعلانات المستخدم |

**Request Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Query Parameters:**
```
?page=1&per_page=10&status=published&sort_by=created_at&sort_order=desc
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "User ads retrieved successfully",
  "data": {
    "ads": [
      {
        "id": 5678,
        "title": "2020 Hyundai Tucson GLX",
        "price": 18500,
        "status": "published",
        "views_count": 34,
        "contact_count": 7,
        "image_thumbnail": "https://cdn.smartcars.local/user-ads/hyundai1.jpg",
        "created_at": "2026-01-24T14:15:00Z",
        "expires_at": "2026-02-23T23:59:59Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 10,
      "total": 12,
      "total_pages": 2
    }
  }
}
```

---

### 9. Create Caishha Ad (User)

| Field | Value |
|-------|-------|
| **API Name** | Create Ad (Caishha) |
| **Method** | POST |
| **Route** | /api/user/caishha |
| **Authorization** | Individual, Dealer, Showroom |
| **Function Description** | Create Caishha ad (offers-first model) without price; sellers can submit offers. |
| **Description** | إنشاء اعلان كيشها (بدون سعر) |

**Request Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "title": "2021 Mercedes-Benz C300",
  "description": "Luxury sedan, fully serviced, mint condition",
  "category_id": 2,
  "car_details": {
    "brand_id": 20,
    "model_id": 210,
    "year": 2021,
    "color": "Black",
    "body_type": "Sedan",
    "fuel_type": "Petrol",
    "transmission": "Automatic",
    "owners_count": 1,
    "mileage": 15000,
    "is_customs_cleared": true
  },
  "contact": {
    "phone": "+962791234567",
    "whatsapp": "+962791234567"
  },
  "city_id": 3,
  "period_days": 7,
  "images": ["https://cdn.smartcars.local/caishha/mercedes1.jpg"]
}
```

**Success Response (201):**
```json
{
  "success": true,
  "message": "Caishha ad created successfully",
  "data": {
    "id": 3456,
    "user_id": 200,
    "ad_type": "caishha",
    "title": "2021 Mercedes-Benz C300",
    "status": "published",
    "offers_window_ends_at": "2026-01-31T14:15:00Z",
    "created_at": "2026-01-24T14:15:00Z"
  }
}
```

---

### 10. Create Caishha Offer (User)

| Field | Value |
|-------|-------|
| **API Name** | Create Offer (Caishha) |
| **Method** | POST |
| **Route** | /api/user/caishha/{id}/offers |
| **Authorization** | Individual, Dealer, Showroom (within offer window) |
| **Function Description** | Submit an offer for a Caishha ad with price and optional comment. |
| **Description** | تقديم عرض على إعلان كيشها |

**Request Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "ad_id": 3456,
  "price": 38000,
  "comment": "Ready to deal immediately, cash payment available"
}
```

**Success Response (201):**
```json
{
  "success": true,
  "message": "Offer submitted successfully",
  "data": {
    "id": 1289,
    "ad_id": 3456,
    "offerer_id": 45,
    "price": 38000,
    "comment": "Ready to deal immediately, cash payment available",
    "status": "pending",
    "created_at": "2026-01-24T15:30:00Z"
  }
}
```

---

### 11. Create FindIt Request (User)

| Field | Value |
|-------|-------|
| **API Name** | Create (FindIt) |
| **Method** | POST |
| **Route** | /api/user/findit |
| **Authorization** | Individual, Dealer, Showroom |
| **Function Description** | Create a FindIt request specifying desired car specs; dealers are notified for matching inventory. |
| **Description** | إنشاء طلب "لاقيها" (بناءً على المواصفات) |

**Request Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "brand_id": 12,
  "model_id": null,
  "min_year": 2018,
  "max_year": 2023,
  "min_price": 12000,
  "max_price": 18000,
  "fuel_type": "Petrol",
  "transmission": "Automatic",
  "city_id": 3,
  "country_id": 1,
  "comments": "Looking for a well-maintained Toyota Corolla"
}
```

**Success Response (201):**
```json
{
  "success": true,
  "message": "FindIt request created successfully. Dealers will be notified.",
  "data": {
    "id": 8901,
    "requester_id": 200,
    "brand": "Toyota",
    "min_year": 2018,
    "max_year": 2023,
    "price_range": "12000 - 18000 JOD",
    "status": "active",
    "created_at": "2026-01-24T16:00:00Z"
  }
}
```

---

## Seller APIs

### 12. Seller Dashboard Stats

| Field | Value |
|-------|-------|
| **API Name** | Dashboard Stats |
| **Method** | GET |
| **Route** | /api/seller/dashboard |
| **Authorization** | Dealer, Showroom |
| **Function Description** | Retrieve seller dashboard summary: total views, contacts, ads count, clicks, and revenue. |
| **Description** | ملخص شامل لمعلومات البائع |

**Request Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Query Parameters:**
```
?date_from=2026-01-01&date_to=2026-01-24
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Dashboard stats retrieved",
  "data": {
    "summary": {
      "total_views": 1245,
      "total_contacts": 89,
      "total_clicks": 234,
      "active_ads_count": 12,
      "revenue": 2500.00,
      "currency": "JOD"
    },
    "top_ads": [
      {
        "id": 5678,
        "title": "2020 Hyundai Tucson GLX",
        "views": 450,
        "contacts": 32,
        "clicks": 78
      }
    ]
  }
}
```

---

### 13. Request Seller Verification

| Field | Value |
|-------|-------|
| **API Name** | Request Verification |
| **Method** | POST |
| **Route** | /api/seller/verify/request |
| **Authorization** | Dealer, Showroom |
| **Function Description** | Submit verification request with business documents; admin reviews and grants badge. |
| **Description** | طلب توثيق الحساب |

**Request Headers:**
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
Accept: application/json
```

**Success Response (201):**
```json
{
  "success": true,
  "message": "Verification request submitted successfully",
  "data": {
    "request_id": 567,
    "seller_id": 45,
    "status": "pending",
    "submitted_at": "2026-01-24T17:00:00Z",
    "message": "Your request is being reviewed. We will notify you within 3 business days."
  }
}
```

---

## Public APIs

### 14. List Published Blogs

| Field | Value |
|-------|-------|
| **API Name** | List Published Blogs |
| **Method** | GET |
| **Route** | /api/blogs |
| **Authorization** | None (Guest/Public) |
| **Function Description** | Retrieve published blog articles with pagination, sorted by date. |
| **Description** | عرض المقالات المنشورة |

**Request Headers:**
```
Accept: application/json
```

**Query Parameters:**
```
?page=1&per_page=10&sort_by=published_at&sort_order=desc
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Blogs retrieved successfully",
  "data": {
    "blogs": [
      {
        "id": 45,
        "title": "Tips for Buying a Used Car",
        "category": "Buying Guide",
        "image": "https://cdn.smartcars.local/blogs/buying-tips.jpg",
        "excerpt": "Learn the essential steps before purchasing...",
        "author": "Admin",
        "published_at": "2026-01-20T10:00:00Z",
        "read_time_minutes": 5
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 10,
      "total": 48,
      "total_pages": 5
    }
  }
}
```

---

### 15. Get Home Sliders

| Field | Value |
|-------|-------|
| **API Name** | Get Home Sliders |
| **Method** | GET |
| **Route** | /api/sliders/home |
| **Authorization** | None (Guest/Public) |
| **Function Description** | Retrieve active sliders for homepage display; admin-controlled content. |
| **Description** | سلايدرز الصفحة الرئيسية |

**Request Headers:**
```
Accept: application/json
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Home sliders retrieved",
  "data": {
    "sliders": [
      {
        "id": 1,
        "name": "Winter Sale Banner",
        "image": "https://cdn.smartcars.local/sliders/winter-sale.jpg",
        "category": "Promotion",
        "order": 1,
        "status": "active"
      }
    ]
  }
}
```

---

## Standard Error Responses

### 400 - Bad Request
```json
{
  "success": false,
  "message": "Invalid request format",
  "errors": {"field_name": ["Error description"]}
}
```

### 401 - Unauthorized
```json
{
  "success": false,
  "message": "Unauthenticated. Please login.",
  "errors": {}
}
```

### 403 - Forbidden
```json
{
  "success": false,
  "message": "Forbidden. You do not have permission.",
  "errors": {}
}
```

### 404 - Not Found
```json
{
  "success": false,
  "message": "Resource not found",
  "errors": {}
}
```

### 422 - Unprocessable Entity
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {"field_name": ["Field error message"]}
}
```

### 500 - Internal Server Error
```json
{
  "success": false,
  "message": "Server error. Please try again later.",
  "errors": {}
}
```

---

## Authorization Roles Summary

| Role | Access Level | Description |
|------|--------------|-------------|
| Guest | Public | Read-only access to listings and public content |
| Individual | User | Create/manage personal ads, offers, favorites |
| Dealer | Seller | Create/manage ads, request verification, analytics |
| Showroom | Seller | Same as Dealer + multiple ads support |
| Marketer | User | Create offered/requested ads with special flag |
| Moderator | Admin | Review and moderate ads, handle reports |
| Country Manager | Admin | Manage country-specific content and settings |
| Super Admin | Admin | Full system control, all operations |

---

## Authentication Notes

- **Token Type:** Laravel Sanctum or Passport JWT
- **Header Format:** `Authorization: Bearer {token}`
- **Token Expiry:** Typically 24 hours for mobile, 1 hour for admin
- **Refresh Token:** Included in login response for long-lived sessions
- **OTP Validation:** Phone verification required for user registration; SMS per country

---

**File Created:** API Designer and Documenter — 15 key APIs expanded with detailed 8-column format, realistic requests/responses, auth requirements, and descriptions.
