# 3jlcom API Documentation

**Base URL:** `http://localhost:8000/api/v1`

**Authentication:** Most endpoints require Bearer token authentication via Laravel Sanctum.
```
Authorization: Bearer {your_token}
```

---

## Table of Contents

1. [Authentication](#1-authentication)
2. [Users Management](#2-users-management)
3. [Roles Management](#3-roles-management)
4. [Seller Verification](#4-seller-verification)
5. [Brands & Car Models](#5-brands--car-models)
6. [Media Management](#6-media-management)
7. [Normal Ads](#7-normal-ads)
8. [Unique Ads](#8-unique-ads)
9. [Caishha Ads](#9-caishha-ads)
10. [Auction Ads](#10-auction-ads)
11. [FindIt Ads (Private Search)](#11-findit-ads-private-search)
12. [Reviews](#12-reviews)
13. [Reports](#13-reports)
14. [Packages/Subscriptions](#14-packagessubscriptions)
15. [Notifications](#15-notifications)
16. [Favorites](#16-favorites)
17. [Saved Searches](#17-saved-searches)
18. [Blogs](#18-blogs)
19. [Specifications](#19-specifications)
20. [Categories](#20-categories)
21. [Sliders](#21-sliders)
22. [Seller Stats & Analytics](#22-seller-stats--analytics)
23. [Admin Stats & Analytics](#23-admin-stats--analytics)
24. [Caishha Settings](#24-caishha-settings)
25. [Page Content Management](#25-page-content-management)
26. [Company Settings](#26-company-settings)
27. [Admin Audit Logs](#27-admin-audit-logs)

---

## 1. Authentication

### 1.1 Login
**Description:** تسجيل دخول المستخدم والحصول على التوكن  
**Endpoint:** `POST /api/v1/auth/login`  
**Auth Required:** No

```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123"
  }'
```

**Response:**
```json
{
  "status": "success",
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "user@example.com"
    },
    "token": "1|abcdefghijklmnop..."
  }
}
```

---

### 1.2 Register
**Description:** تسجيل حساب مستخدم جديد  
**Endpoint:** `POST /api/v1/auth/register`  
**Auth Required:** No

```bash
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "John Doe",
    "email": "newuser@example.com",
    "phone": "1234567890",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

---

### 1.3 Verify Account (OTP)
**Description:** تأكيد الحساب باستخدام رمز التحقق  
**Endpoint:** `PUT /api/v1/auth/verify`  
**Auth Required:** No

```bash
curl -X PUT http://localhost:8000/api/v1/auth/verify \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "user@example.com",
    "otp": "123456"
  }'
```

---

### 1.4 Password Reset Request
**Description:** طلب إعادة تعيين كلمة المرور  
**Endpoint:** `POST /api/v1/auth/password/reset-request`  
**Auth Required:** No

```bash
curl -X POST http://localhost:8000/api/v1/auth/password/reset-request \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "user@example.com"
  }'
```

---

### 1.5 Password Reset Confirm
**Description:** تأكيد إعادة تعيين كلمة المرور  
**Endpoint:** `PUT /api/v1/auth/password/reset`  
**Auth Required:** No

```bash
curl -X PUT http://localhost:8000/api/v1/auth/password/reset \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "user@example.com",
    "token": "reset_token_here",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
  }'
```

---

### 1.6 Logout
**Description:** تسجيل خروج المستخدم وإلغاء التوكن  
**Endpoint:** `POST /api/v1/auth/logout`  
**Auth Required:** Yes

```bash
curl -X POST http://localhost:8000/api/v1/auth/logout \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

## 2. Users Management

### 2.1 List Users (Admin)
**Description:** عرض جميع المستخدمين (للأدمن فقط)  
**Endpoint:** `GET /api/v1/users`  
**Auth Required:** Yes (Admin)

```bash
curl -X GET "http://localhost:8000/api/v1/users?page=1&limit=15" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Query Parameters:**
- `page`: Page number (default: 1)
- `limit`: Items per page (default: 15, max: 100)
- `search`: Search by name or email
- `role`: Filter by role
- `status`: Filter by status (active, inactive, banned)

---

### 2.2 Get User Details
**Description:** عرض بيانات مستخدم محدد  
**Endpoint:** `GET /api/v1/users/{id}`  
**Auth Required:** Yes

```bash
curl -X GET http://localhost:8000/api/v1/users/1 \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 2.3 Create User (Admin)
**Description:** إنشاء مستخدم جديد (للأدمن فقط)  
**Endpoint:** `POST /api/v1/users`  
**Auth Required:** Yes (Admin)

```bash
curl -X POST http://localhost:8000/api/v1/users \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "New User",
    "email": "newuser@example.com",
    "phone": "1234567890",
    "password": "password123",
    "status": "active"
  }'
```

---

### 2.4 Update User
**Description:** تعديل بيانات مستخدم  
**Endpoint:** `PUT /api/v1/users/{id}`  
**Auth Required:** Yes

```bash
curl -X PUT http://localhost:8000/api/v1/users/1 \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Updated Name",
    "phone": "9876543210"
  }'
```

---

### 2.5 Delete User (Admin)
**Description:** حذف مستخدم (للأدمن فقط)  
**Endpoint:** `DELETE /api/v1/users/{id}`  
**Auth Required:** Yes (Admin)

```bash
curl -X DELETE http://localhost:8000/api/v1/users/1 \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 2.6 Verify User (Admin)
**Description:** توثيق حساب المستخدم (للأدمن فقط)  
**Endpoint:** `POST /api/v1/users/{id}/verify`  
**Auth Required:** Yes (Admin)

```bash
curl -X POST http://localhost:8000/api/v1/users/1/verify \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

## 3. Roles Management

### 3.1 List Roles
**Description:** عرض جميع الأدوار  
**Endpoint:** `GET /api/v1/roles`  
**Auth Required:** Yes

```bash
curl -X GET http://localhost:8000/api/v1/roles \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 3.2 Create Role (Admin)
**Description:** إنشاء دور جديد  
**Endpoint:** `POST /api/v1/roles`  
**Auth Required:** Yes (Admin)

```bash
curl -X POST http://localhost:8000/api/v1/roles \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "moderator",
    "display_name": "Moderator"
  }'
```

---

### 3.3 Get Role Details
**Description:** عرض تفاصيل دور محدد  
**Endpoint:** `GET /api/v1/roles/{id}`  
**Auth Required:** Yes

```bash
curl -X GET http://localhost:8000/api/v1/roles/1 \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 3.4 Update Role (Admin)
**Description:** تعديل دور  
**Endpoint:** `PUT /api/v1/roles/{id}`  
**Auth Required:** Yes (Admin)

```bash
curl -X PUT http://localhost:8000/api/v1/roles/1 \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "display_name": "Super Moderator"
  }'
```

---

### 3.5 Delete Role (Admin)
**Description:** حذف دور  
**Endpoint:** `DELETE /api/v1/roles/{id}`  
**Auth Required:** Yes (Admin)

```bash
curl -X DELETE http://localhost:8000/api/v1/roles/1 \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 3.6 Assign Roles to User (Admin)
**Description:** تعيين أدوار لمستخدم  
**Endpoint:** `POST /api/v1/users/{user}/roles`  
**Auth Required:** Yes (Admin)

```bash
curl -X POST http://localhost:8000/api/v1/users/1/roles \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "roles": [1, 2]
  }'
```

---

### 3.7 Get User Roles
**Description:** عرض أدوار مستخدم محدد  
**Endpoint:** `GET /api/v1/users/{user}/roles`  
**Auth Required:** Yes

```bash
curl -X GET http://localhost:8000/api/v1/users/1/roles \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

## 4. Seller Verification

### 4.1 Submit Verification Request
**Description:** تقديم طلب توثيق البائع  
**Endpoint:** `POST /api/v1/seller-verification`  
**Auth Required:** Yes

```bash
curl -X POST http://localhost:8000/api/v1/seller-verification \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "business_name": "My Car Shop",
    "business_type": "showroom",
    "license_number": "12345678",
    "documents": [1, 2, 3]
  }'
```

---

### 4.2 Get My Verification Status
**Description:** عرض حالة طلب التوثيق الخاص بي  
**Endpoint:** `GET /api/v1/seller-verification`  
**Auth Required:** Yes

```bash
curl -X GET http://localhost:8000/api/v1/seller-verification \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 4.3 List All Verification Requests (Admin)
**Description:** عرض جميع طلبات التوثيق (للأدمن)  
**Endpoint:** `GET /api/v1/seller-verification/admin`  
**Auth Required:** Yes (Admin)

```bash
curl -X GET "http://localhost:8000/api/v1/seller-verification/admin?status=pending" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 4.4 Update Verification Request (Admin)
**Description:** تحديث حالة طلب التوثيق (قبول/رفض)  
**Endpoint:** `PUT /api/v1/seller-verification/{id}`  
**Auth Required:** Yes (Admin)

```bash
curl -X PUT http://localhost:8000/api/v1/seller-verification/1 \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "status": "approved",
    "admin_notes": "All documents verified"
  }'
```

---

## 5. Brands & Car Models

### 5.1 List Brands (Public)
**Description:** عرض جميع ماركات السيارات  
**Endpoint:** `GET /api/v1/brands`  
**Auth Required:** No

```bash
curl -X GET http://localhost:8000/api/v1/brands \
  -H "Accept: application/json"
```

---

### 5.2 Get Brand Models (Public)
**Description:** عرض موديلات ماركة معينة  
**Endpoint:** `GET /api/v1/brands/{brand}/models`  
**Auth Required:** No

```bash
curl -X GET http://localhost:8000/api/v1/brands/1/models \
  -H "Accept: application/json"
```

---

### 5.3 Create Brand (Admin)
**Description:** إنشاء ماركة جديدة  
**Endpoint:** `POST /api/v1/brands`  
**Auth Required:** Yes (Admin)

```bash
curl -X POST http://localhost:8000/api/v1/brands \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name_en": "Toyota",
    "name_ar": "تويوتا",
    "logo_id": 1,
    "status": "active"
  }'
```

---

### 5.4 Update Brand (Admin)
**Description:** تعديل ماركة  
**Endpoint:** `PUT /api/v1/brands/{id}`  
**Auth Required:** Yes (Admin)

```bash
curl -X PUT http://localhost:8000/api/v1/brands/1 \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name_en": "Toyota Updated"
  }'
```

---

### 5.5 Delete Brand (Admin)
**Description:** حذف ماركة  
**Endpoint:** `DELETE /api/v1/brands/{id}`  
**Auth Required:** Yes (Admin)

```bash
curl -X DELETE http://localhost:8000/api/v1/brands/1 \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 5.6 Create Model (Admin)
**Description:** إنشاء موديل جديد لماركة  
**Endpoint:** `POST /api/v1/brands/{brand}/models`  
**Auth Required:** Yes (Admin)

```bash
curl -X POST http://localhost:8000/api/v1/brands/1/models \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name_en": "Camry",
    "name_ar": "كامري",
    "status": "active"
  }'
```

---

### 5.7 Update Model (Admin)
**Description:** تعديل موديل  
**Endpoint:** `PUT /api/v1/brands/{brand}/models/{model}`  
**Auth Required:** Yes (Admin)

```bash
curl -X PUT http://localhost:8000/api/v1/brands/1/models/1 \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name_en": "Camry 2024"
  }'
```

---

### 5.8 Delete Model (Admin)
**Description:** حذف موديل  
**Endpoint:** `DELETE /api/v1/brands/{brand}/models/{model}`  
**Auth Required:** Yes (Admin)

```bash
curl -X DELETE http://localhost:8000/api/v1/brands/1/models/1 \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

## 6. Media Management

### 6.1 List Media
**Description:** عرض الوسائط  
**Endpoint:** `GET /api/v1/media`  
**Auth Required:** Yes

```bash
curl -X GET http://localhost:8000/api/v1/media \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 6.2 Upload Media
**Description:** رفع ملف وسائط (صورة/فيديو)  
**Endpoint:** `POST /api/v1/media`  
**Auth Required:** Yes

```bash
curl -X POST http://localhost:8000/api/v1/media \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json" \
  -F "file=@/path/to/image.jpg" \
  -F "type=image"
```

---

### 6.3 Get Media Details
**Description:** عرض تفاصيل ملف وسائط  
**Endpoint:** `GET /api/v1/media/{id}`  
**Auth Required:** Yes

```bash
curl -X GET http://localhost:8000/api/v1/media/1 \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 6.4 Update Media
**Description:** تعديل ملف وسائط  
**Endpoint:** `PATCH /api/v1/media/{id}`  
**Auth Required:** Yes

```bash
curl -X PATCH http://localhost:8000/api/v1/media/1 \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "alt_text": "Car image"
  }'
```

---

### 6.5 Delete Media
**Description:** حذف ملف وسائط  
**Endpoint:** `DELETE /api/v1/media/{id}`  
**Auth Required:** Yes

```bash
curl -X DELETE http://localhost:8000/api/v1/media/1 \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

## 7. Normal Ads

### 7.1 List Public Ads
**Description:** عرض الإعلانات المنشورة للعامة  
**Endpoint:** `GET /api/v1/normal-ads`  
**Auth Required:** No

```bash
curl -X GET "http://localhost:8000/api/v1/normal-ads?page=1&limit=15&brand_id=1&min_price=1000&max_price=50000" \
  -H "Accept: application/json"
```

**Query Parameters:**
- `page`: Page number
- `limit`: Items per page (max: 100)
- `brand_id`: Filter by brand
- `model_id`: Filter by model
- `city_id`: Filter by city
- `country_id`: Filter by country
- `min_price`: Minimum price
- `max_price`: Maximum price
- `min_year`: Minimum year
- `max_year`: Maximum year
- `condition`: new, used
- `sort`: price_asc, price_desc, date_asc, date_desc
- `search`: Search in title/description

---

### 7.2 Get Ad Details (Public)
**Description:** عرض تفاصيل إعلان معين  
**Endpoint:** `GET /api/v1/normal-ads/{id}`  
**Auth Required:** No

```bash
curl -X GET http://localhost:8000/api/v1/normal-ads/1 \
  -H "Accept: application/json"
```

---

### 7.3 List User's Ads (Public)
**Description:** عرض إعلانات مستخدم معين  
**Endpoint:** `GET /api/v1/users/{user}/normal-ads`  
**Auth Required:** No

```bash
curl -X GET http://localhost:8000/api/v1/users/1/normal-ads \
  -H "Accept: application/json"
```

---

### 7.4 My Ads
**Description:** عرض إعلاناتي (بكل الحالات)  
**Endpoint:** `GET /api/v1/normal-ads/my-ads`  
**Auth Required:** Yes

```bash
curl -X GET "http://localhost:8000/api/v1/normal-ads/my-ads?status=published" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 7.5 Admin List All Ads
**Description:** عرض جميع الإعلانات (للأدمن)  
**Endpoint:** `GET /api/v1/normal-ads/admin`  
**Auth Required:** Yes (Admin)

```bash
curl -X GET "http://localhost:8000/api/v1/normal-ads/admin?status=pending" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 7.6 Global Stats (Admin)
**Description:** إحصائيات الإعلانات العادية  
**Endpoint:** `GET /api/v1/normal-ads/stats`  
**Auth Required:** Yes (Admin)

```bash
curl -X GET http://localhost:8000/api/v1/normal-ads/stats \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 7.7 My Favorites
**Description:** إعلاناتي المفضلة  
**Endpoint:** `GET /api/v1/normal-ads/favorites`  
**Auth Required:** Yes

```bash
curl -X GET http://localhost:8000/api/v1/normal-ads/favorites \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 7.8 Bulk Action (Admin)
**Description:** عمليات جماعية على الإعلانات  
**Endpoint:** `POST /api/v1/normal-ads/actions/bulk`  
**Auth Required:** Yes (Admin)

```bash
curl -X POST http://localhost:8000/api/v1/normal-ads/actions/bulk \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "action": "publish",
    "ids": [1, 2, 3]
  }'
```

**Actions:** `publish`, `unpublish`, `delete`, `archive`, `restore`

---

### 7.9 Create Ad
**Description:** إنشاء إعلان جديد  
**Endpoint:** `POST /api/v1/normal-ads`  
**Auth Required:** Yes

```bash
curl -X POST http://localhost:8000/api/v1/normal-ads \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "title": "Toyota Camry 2023",
    "description": "Clean car, single owner",
    "price": 25000,
    "brand_id": 1,
    "model_id": 1,
    "year": 2023,
    "mileage": 15000,
    "condition": "used",
    "city_id": 1,
    "country_id": 1,
    "media_ids": [1, 2, 3]
  }'
```

---

### 7.10 Update Ad
**Description:** تعديل إعلان  
**Endpoint:** `PUT /api/v1/normal-ads/{id}`  
**Auth Required:** Yes (Owner or Admin)

```bash
curl -X PUT http://localhost:8000/api/v1/normal-ads/1 \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "title": "Toyota Camry 2023 - Updated",
    "price": 24000
  }'
```

---

### 7.11 Delete Ad
**Description:** حذف إعلان  
**Endpoint:** `DELETE /api/v1/normal-ads/{id}`  
**Auth Required:** Yes (Owner or Admin)

```bash
curl -X DELETE http://localhost:8000/api/v1/normal-ads/1 \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 7.12 Republish Ad
**Description:** إعادة نشر إعلان منتهي  
**Endpoint:** `POST /api/v1/normal-ads/{id}/actions/republish`  
**Auth Required:** Yes (Owner)

```bash
curl -X POST http://localhost:8000/api/v1/normal-ads/1/actions/republish \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 7.13 Publish Ad
**Description:** نشر إعلان (مسودة)  
**Endpoint:** `POST /api/v1/normal-ads/{id}/actions/publish`  
**Auth Required:** Yes (Owner or Admin)

```bash
curl -X POST http://localhost:8000/api/v1/normal-ads/1/actions/publish \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 7.14 Unpublish Ad
**Description:** إلغاء نشر إعلان  
**Endpoint:** `POST /api/v1/normal-ads/{id}/actions/unpublish`  
**Auth Required:** Yes (Owner or Admin)

```bash
curl -X POST http://localhost:8000/api/v1/normal-ads/1/actions/unpublish \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 7.15 Expire Ad (Admin)
**Description:** تعيين إعلان كمنتهي  
**Endpoint:** `POST /api/v1/normal-ads/{id}/actions/expire`  
**Auth Required:** Yes (Admin)

```bash
curl -X POST http://localhost:8000/api/v1/normal-ads/1/actions/expire \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 7.16 Archive Ad
**Description:** أرشفة إعلان  
**Endpoint:** `POST /api/v1/normal-ads/{id}/actions/archive`  
**Auth Required:** Yes (Owner or Admin)

```bash
curl -X POST http://localhost:8000/api/v1/normal-ads/1/actions/archive \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 7.17 Restore Ad
**Description:** استعادة إعلان مؤرشف  
**Endpoint:** `POST /api/v1/normal-ads/{id}/actions/restore`  
**Auth Required:** Yes (Owner or Admin)

```bash
curl -X POST http://localhost:8000/api/v1/normal-ads/1/actions/restore \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 7.18 Get Ad Stats
**Description:** إحصائيات إعلان معين  
**Endpoint:** `GET /api/v1/normal-ads/{id}/stats`  
**Auth Required:** Yes (Owner or Admin)

```bash
curl -X GET http://localhost:8000/api/v1/normal-ads/1/stats \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 7.19 Favorite Ad
**Description:** إضافة إعلان للمفضلة  
**Endpoint:** `POST /api/v1/normal-ads/{id}/favorite`  
**Auth Required:** Yes

```bash
curl -X POST http://localhost:8000/api/v1/normal-ads/1/favorite \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 7.20 Unfavorite Ad
**Description:** إزالة إعلان من المفضلة  
**Endpoint:** `DELETE /api/v1/normal-ads/{id}/favorite`  
**Auth Required:** Yes

```bash
curl -X DELETE http://localhost:8000/api/v1/normal-ads/1/favorite \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 7.21 Contact Seller
**Description:** التواصل مع البائع (تسجيل اتصال)  
**Endpoint:** `POST /api/v1/normal-ads/{id}/contact`  
**Auth Required:** Yes

```bash
curl -X POST http://localhost:8000/api/v1/normal-ads/1/contact \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 7.22 Convert to Unique Ad
**Description:** تحويل إعلان عادي إلى إعلان مميز  
**Endpoint:** `POST /api/v1/normal-ads/{id}/actions/convert-to-unique`  
**Auth Required:** Yes (Owner)

```bash
curl -X POST http://localhost:8000/api/v1/normal-ads/1/actions/convert-to-unique \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

## 8. Unique Ads

### 8.1 List Public Unique Ads
**Description:** عرض الإعلانات المميزة للعامة  
**Endpoint:** `GET /api/v1/unique-ads`  
**Auth Required:** No

```bash
curl -X GET "http://localhost:8000/api/v1/unique-ads?page=1&limit=15" \
  -H "Accept: application/json"
```

---

### 8.2 Get Unique Ad Details (Public)
**Description:** عرض تفاصيل إعلان مميز  
**Endpoint:** `GET /api/v1/unique-ads/{id}`  
**Auth Required:** No

```bash
curl -X GET http://localhost:8000/api/v1/unique-ads/1 \
  -H "Accept: application/json"
```

---

### 8.3 List User's Unique Ads (Public)
**Description:** عرض إعلانات مستخدم المميزة  
**Endpoint:** `GET /api/v1/users/{user}/unique-ads`  
**Auth Required:** No

```bash
curl -X GET http://localhost:8000/api/v1/users/1/unique-ads \
  -H "Accept: application/json"
```

---

### 8.4 My Unique Ads
**Description:** عرض إعلاناتي المميزة  
**Endpoint:** `GET /api/v1/unique-ads/my-ads`  
**Auth Required:** Yes

```bash
curl -X GET http://localhost:8000/api/v1/unique-ads/my-ads \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 8.5 Admin List All Unique Ads
**Description:** عرض جميع الإعلانات المميزة (للأدمن)  
**Endpoint:** `GET /api/v1/unique-ads/admin`  
**Auth Required:** Yes (Admin)

```bash
curl -X GET http://localhost:8000/api/v1/unique-ads/admin \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 8.6 Unique Ads Global Stats (Admin)
**Description:** إحصائيات الإعلانات المميزة  
**Endpoint:** `GET /api/v1/unique-ads/stats`  
**Auth Required:** Yes (Admin)

```bash
curl -X GET http://localhost:8000/api/v1/unique-ads/stats \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 8.7 My Favorite Unique Ads
**Description:** إعلاناتي المميزة المفضلة  
**Endpoint:** `GET /api/v1/unique-ads/favorites`  
**Auth Required:** Yes

```bash
curl -X GET http://localhost:8000/api/v1/unique-ads/favorites \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 8.8 Bulk Action (Admin)
**Description:** عمليات جماعية على الإعلانات المميزة  
**Endpoint:** `POST /api/v1/unique-ads/actions/bulk`  
**Auth Required:** Yes (Admin)

```bash
curl -X POST http://localhost:8000/api/v1/unique-ads/actions/bulk \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "action": "feature",
    "ids": [1, 2, 3]
  }'
```

---

### 8.9 Create Unique Ad
**Description:** إنشاء إعلان مميز  
**Endpoint:** `POST /api/v1/unique-ads`  
**Auth Required:** Yes

```bash
curl -X POST http://localhost:8000/api/v1/unique-ads \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "title": "Rare Vintage Car",
    "description": "Collector item",
    "price": 100000,
    "brand_id": 1,
    "model_id": 1,
    "year": 1970,
    "city_id": 1,
    "country_id": 1,
    "media_ids": [1, 2]
  }'
```

---

### 8.10 Update Unique Ad
**Description:** تعديل إعلان مميز  
**Endpoint:** `PUT /api/v1/unique-ads/{id}`  
**Auth Required:** Yes (Owner or Admin)

```bash
curl -X PUT http://localhost:8000/api/v1/unique-ads/1 \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "price": 95000
  }'
```

---

### 8.11 Delete Unique Ad
**Description:** حذف إعلان مميز  
**Endpoint:** `DELETE /api/v1/unique-ads/{id}`  
**Auth Required:** Yes (Owner or Admin)

```bash
curl -X DELETE http://localhost:8000/api/v1/unique-ads/1 \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 8.12-8.17 Unique Ad Lifecycle Actions
Same as Normal Ads:
- `POST /api/v1/unique-ads/{id}/actions/republish` - Republish
- `POST /api/v1/unique-ads/{id}/actions/publish` - Publish
- `POST /api/v1/unique-ads/{id}/actions/unpublish` - Unpublish
- `POST /api/v1/unique-ads/{id}/actions/expire` - Expire (Admin)
- `POST /api/v1/unique-ads/{id}/actions/archive` - Archive
- `POST /api/v1/unique-ads/{id}/actions/restore` - Restore

---

### 8.18 Feature Ad (Admin)
**Description:** تمييز إعلان كمميز/سوبر مميز  
**Endpoint:** `POST /api/v1/unique-ads/{id}/actions/feature`  
**Auth Required:** Yes (Admin)

```bash
curl -X POST http://localhost:8000/api/v1/unique-ads/1/actions/feature \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "feature_type": "super_featured"
  }'
```

---

### 8.19 Unfeature Ad (Admin)
**Description:** إلغاء تمييز إعلان  
**Endpoint:** `DELETE /api/v1/unique-ads/{id}/actions/feature`  
**Auth Required:** Yes (Admin)

```bash
curl -X DELETE http://localhost:8000/api/v1/unique-ads/1/actions/feature \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 8.20 Request Verification
**Description:** طلب توثيق إعلان  
**Endpoint:** `POST /api/v1/unique-ads/{id}/actions/verify`  
**Auth Required:** Yes (Owner)

```bash
curl -X POST http://localhost:8000/api/v1/unique-ads/1/actions/verify \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 8.21 Approve Verification (Admin)
**Description:** قبول طلب توثيق  
**Endpoint:** `POST /api/v1/unique-ads/{id}/actions/approve-verification`  
**Auth Required:** Yes (Admin)

```bash
curl -X POST http://localhost:8000/api/v1/unique-ads/1/actions/approve-verification \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 8.22 Reject Verification (Admin)
**Description:** رفض طلب توثيق  
**Endpoint:** `POST /api/v1/unique-ads/{id}/actions/reject-verification`  
**Auth Required:** Yes (Admin)

```bash
curl -X POST http://localhost:8000/api/v1/unique-ads/1/actions/reject-verification \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "reason": "Documents not clear"
  }'
```

---

### 8.23 Toggle Auto Republish
**Description:** تفعيل/إلغاء إعادة النشر التلقائي  
**Endpoint:** `POST /api/v1/unique-ads/{id}/actions/auto-republish`  
**Auth Required:** Yes (Owner)

```bash
curl -X POST http://localhost:8000/api/v1/unique-ads/1/actions/auto-republish \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 8.24 Convert to Normal Ad
**Description:** تحويل إعلان مميز إلى عادي  
**Endpoint:** `POST /api/v1/unique-ads/{id}/actions/convert-to-normal`  
**Auth Required:** Yes (Owner)

```bash
curl -X POST http://localhost:8000/api/v1/unique-ads/1/actions/convert-to-normal \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 8.25-8.28 Stats & Interactions
Same as Normal Ads:
- `GET /api/v1/unique-ads/{id}/stats` - Stats
- `POST /api/v1/unique-ads/{id}/favorite` - Favorite
- `DELETE /api/v1/unique-ads/{id}/favorite` - Unfavorite
- `POST /api/v1/unique-ads/{id}/contact` - Contact Seller

---

## 9. Caishha Ads

### 9.1 List Public Caishha Ads
**Description:** عرض إعلانات كيشها للعامة  
**Endpoint:** `GET /api/v1/caishha-ads`  
**Auth Required:** No

```bash
curl -X GET http://localhost:8000/api/v1/caishha-ads \
  -H "Accept: application/json"
```

---

### 9.2 Get Caishha Ad Details (Public)
**Description:** عرض تفاصيل إعلان كيشها  
**Endpoint:** `GET /api/v1/caishha-ads/{id}`  
**Auth Required:** No

```bash
curl -X GET http://localhost:8000/api/v1/caishha-ads/1 \
  -H "Accept: application/json"
```

---

### 9.3 My Caishha Ads
**Description:** عرض إعلاناتي كيشها  
**Endpoint:** `GET /api/v1/caishha-ads/my-ads`  
**Auth Required:** Yes

```bash
curl -X GET http://localhost:8000/api/v1/caishha-ads/my-ads \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 9.4 Admin List All Caishha Ads
**Description:** عرض جميع إعلانات كيشها (للأدمن)  
**Endpoint:** `GET /api/v1/caishha-ads/admin`  
**Auth Required:** Yes (Admin)

```bash
curl -X GET http://localhost:8000/api/v1/caishha-ads/admin \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 9.5 Caishha Global Stats (Admin)
**Description:** إحصائيات كيشها العامة  
**Endpoint:** `GET /api/v1/caishha-ads/stats`  
**Auth Required:** Yes (Admin)

```bash
curl -X GET http://localhost:8000/api/v1/caishha-ads/stats \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 9.6 Bulk Action (Admin)
**Description:** عمليات جماعية على إعلانات كيشها  
**Endpoint:** `POST /api/v1/caishha-ads/actions/bulk`  
**Auth Required:** Yes (Admin)

```bash
curl -X POST http://localhost:8000/api/v1/caishha-ads/actions/bulk \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "action": "publish",
    "ids": [1, 2]
  }'
```

---

### 9.7 Create Caishha Ad
**Description:** إنشاء إعلان كيشها (شراء سيارة)  
**Endpoint:** `POST /api/v1/caishha-ads`  
**Auth Required:** Yes

```bash
curl -X POST http://localhost:8000/api/v1/caishha-ads \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "title": "Looking for Toyota Camry 2020-2023",
    "description": "Budget up to 30000",
    "budget_min": 20000,
    "budget_max": 30000,
    "brand_id": 1,
    "model_id": 1,
    "year_min": 2020,
    "year_max": 2023,
    "city_id": 1
  }'
```

---

### 9.8 Update Caishha Ad
**Description:** تعديل إعلان كيشها  
**Endpoint:** `PUT /api/v1/caishha-ads/{id}`  
**Auth Required:** Yes (Owner or Admin)

```bash
curl -X PUT http://localhost:8000/api/v1/caishha-ads/1 \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "budget_max": 35000
  }'
```

---

### 9.9 Delete Caishha Ad
**Description:** حذف إعلان كيشها  
**Endpoint:** `DELETE /api/v1/caishha-ads/{id}`  
**Auth Required:** Yes (Owner or Admin)

```bash
curl -X DELETE http://localhost:8000/api/v1/caishha-ads/1 \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 9.10-9.14 Caishha Lifecycle Actions
- `POST /api/v1/caishha-ads/{id}/actions/publish` - Publish
- `POST /api/v1/caishha-ads/{id}/actions/unpublish` - Unpublish
- `POST /api/v1/caishha-ads/{id}/actions/expire` - Expire (Admin)
- `POST /api/v1/caishha-ads/{id}/actions/archive` - Archive
- `POST /api/v1/caishha-ads/{id}/actions/restore` - Restore

---

### 9.15 Submit Offer on Caishha Ad
**Description:** تقديم عرض على إعلان كيشها  
**Endpoint:** `POST /api/v1/caishha-ads/{id}/offers`  
**Auth Required:** Yes

```bash
curl -X POST http://localhost:8000/api/v1/caishha-ads/1/offers \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "price": 25000,
    "description": "I have a 2021 Camry in great condition",
    "ad_id": 5
  }'
```

---

### 9.16 List Offers on Caishha Ad
**Description:** عرض العروض على إعلان كيشها  
**Endpoint:** `GET /api/v1/caishha-ads/{id}/offers`  
**Auth Required:** Yes (Owner or Admin)

```bash
curl -X GET http://localhost:8000/api/v1/caishha-ads/1/offers \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 9.17 Accept Offer
**Description:** قبول عرض  
**Endpoint:** `POST /api/v1/caishha-ads/{id}/offers/{offer}/accept`  
**Auth Required:** Yes (Ad Owner)

```bash
curl -X POST http://localhost:8000/api/v1/caishha-ads/1/offers/1/accept \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 9.18 Reject Offer
**Description:** رفض عرض  
**Endpoint:** `POST /api/v1/caishha-ads/{id}/offers/{offer}/reject`  
**Auth Required:** Yes (Ad Owner)

```bash
curl -X POST http://localhost:8000/api/v1/caishha-ads/1/offers/1/reject \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 9.19 My Offers
**Description:** عرض عروضي المقدمة  
**Endpoint:** `GET /api/v1/caishha-offers/my-offers`  
**Auth Required:** Yes

```bash
curl -X GET http://localhost:8000/api/v1/caishha-offers/my-offers \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 9.20 Get Offer Details
**Description:** عرض تفاصيل عرض معين  
**Endpoint:** `GET /api/v1/caishha-offers/{offer}`  
**Auth Required:** Yes (Offer Owner, Ad Owner, or Admin)

```bash
curl -X GET http://localhost:8000/api/v1/caishha-offers/1 \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 9.21 Update Offer
**Description:** تعديل عرض  
**Endpoint:** `PUT /api/v1/caishha-offers/{offer}`  
**Auth Required:** Yes (Offer Owner)

```bash
curl -X PUT http://localhost:8000/api/v1/caishha-offers/1 \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "price": 24000
  }'
```

---

### 9.22 Delete/Withdraw Offer
**Description:** حذف/سحب عرض  
**Endpoint:** `DELETE /api/v1/caishha-offers/{offer}`  
**Auth Required:** Yes (Offer Owner or Admin)

```bash
curl -X DELETE http://localhost:8000/api/v1/caishha-offers/1 \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

## 10. Auction Ads

### 10.1 List Public Auctions
**Description:** عرض المزادات للعامة  
**Endpoint:** `GET /api/v1/auction-ads`  
**Auth Required:** No

```bash
curl -X GET "http://localhost:8000/api/v1/auction-ads?status=active" \
  -H "Accept: application/json"
```

---

### 10.2 Get Auction Details (Public)
**Description:** عرض تفاصيل مزاد  
**Endpoint:** `GET /api/v1/auction-ads/{id}`  
**Auth Required:** No

```bash
curl -X GET http://localhost:8000/api/v1/auction-ads/1 \
  -H "Accept: application/json"
```

---

### 10.3 List User's Auctions (Public)
**Description:** عرض مزادات مستخدم  
**Endpoint:** `GET /api/v1/users/{user}/auction-ads`  
**Auth Required:** No

```bash
curl -X GET http://localhost:8000/api/v1/users/1/auction-ads \
  -H "Accept: application/json"
```

---

### 10.4 My Auctions
**Description:** عرض مزاداتي  
**Endpoint:** `GET /api/v1/auction-ads/my-ads`  
**Auth Required:** Yes

```bash
curl -X GET http://localhost:8000/api/v1/auction-ads/my-ads \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 10.5 Admin List All Auctions
**Description:** عرض جميع المزادات (للأدمن)  
**Endpoint:** `GET /api/v1/auction-ads/admin`  
**Auth Required:** Yes (Admin)

```bash
curl -X GET http://localhost:8000/api/v1/auction-ads/admin \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 10.6 Auction Global Stats (Admin)
**Description:** إحصائيات المزادات العامة  
**Endpoint:** `GET /api/v1/auction-ads/stats`  
**Auth Required:** Yes (Admin)

```bash
curl -X GET http://localhost:8000/api/v1/auction-ads/stats \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 10.7 Create Auction
**Description:** إنشاء مزاد جديد  
**Endpoint:** `POST /api/v1/auction-ads`  
**Auth Required:** Yes

```bash
curl -X POST http://localhost:8000/api/v1/auction-ads \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "title": "2022 Mercedes S-Class Auction",
    "description": "Pristine condition",
    "brand_id": 2,
    "model_id": 5,
    "year": 2022,
    "start_price": 50000,
    "reserve_price": 70000,
    "min_increment": 500,
    "start_time": "2026-02-10T10:00:00Z",
    "end_time": "2026-02-15T18:00:00Z",
    "city_id": 1,
    "country_id": 1,
    "media_ids": [1, 2, 3]
  }'
```

---

### 10.8 Update Auction
**Description:** تعديل مزاد (قبل البدء فقط)  
**Endpoint:** `PUT /api/v1/auction-ads/{id}`  
**Auth Required:** Yes (Owner or Admin)

```bash
curl -X PUT http://localhost:8000/api/v1/auction-ads/1 \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "reserve_price": 75000
  }'
```

---

### 10.9 Delete Auction
**Description:** حذف مزاد (بدون مزايدات فقط)  
**Endpoint:** `DELETE /api/v1/auction-ads/{id}`  
**Auth Required:** Yes (Owner or Admin)

```bash
curl -X DELETE http://localhost:8000/api/v1/auction-ads/1 \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 10.10 Publish Auction
**Description:** نشر مزاد  
**Endpoint:** `POST /api/v1/auction-ads/{id}/actions/publish`  
**Auth Required:** Yes (Owner or Admin)

```bash
curl -X POST http://localhost:8000/api/v1/auction-ads/1/actions/publish \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 10.11 Close Auction
**Description:** إغلاق مزاد  
**Endpoint:** `POST /api/v1/auction-ads/{id}/actions/close`  
**Auth Required:** Yes (Owner after end time, or Admin)

```bash
curl -X POST http://localhost:8000/api/v1/auction-ads/1/actions/close \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 10.12 Cancel Auction
**Description:** إلغاء مزاد  
**Endpoint:** `POST /api/v1/auction-ads/{id}/actions/cancel`  
**Auth Required:** Yes (Owner without bids, or Admin)

```bash
curl -X POST http://localhost:8000/api/v1/auction-ads/1/actions/cancel \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 10.13 Place Bid
**Description:** وضع مزايدة على مزاد  
**Endpoint:** `POST /api/v1/auction-ads/{id}/bids`  
**Auth Required:** Yes

```bash
curl -X POST http://localhost:8000/api/v1/auction-ads/1/bids \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "amount": 52000,
    "comment": "Serious buyer"
  }'
```

---

### 10.14 List Bids
**Description:** عرض المزايدات على مزاد  
**Endpoint:** `GET /api/v1/auction-ads/{id}/bids`  
**Auth Required:** Yes (Owner, Admin, or Moderator)

```bash
curl -X GET http://localhost:8000/api/v1/auction-ads/1/bids \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 10.15 Get Bid Details
**Description:** عرض تفاصيل مزايدة  
**Endpoint:** `GET /api/v1/auction-ads/{id}/bids/{bid}`  
**Auth Required:** Yes (Bidder, Owner, Admin, or Moderator)

```bash
curl -X GET http://localhost:8000/api/v1/auction-ads/1/bids/1 \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 10.16 Withdraw Bid
**Description:** سحب مزايدة (ليست الأعلى)  
**Endpoint:** `DELETE /api/v1/auction-ads/{id}/bids/{bid}`  
**Auth Required:** Yes (Bidder)

```bash
curl -X DELETE http://localhost:8000/api/v1/auction-ads/1/bids/1 \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 10.17 My Bids
**Description:** عرض مزايداتي على جميع المزادات  
**Endpoint:** `GET /api/v1/auction-bids/my-bids`  
**Auth Required:** Yes

```bash
curl -X GET http://localhost:8000/api/v1/auction-bids/my-bids \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

## 11. FindIt Ads (Private Search)

### 11.1 My FindIt Requests
**Description:** عرض طلبات لاقيها الخاصة بي  
**Endpoint:** `GET /api/v1/findit-ads/my-requests`  
**Auth Required:** Yes

```bash
curl -X GET "http://localhost:8000/api/v1/findit-ads/my-requests?status=active" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Query Parameters:**
- `status`: Filter by status (draft, active, expired, closed)
- `page`, `limit`: Pagination

---

### 11.2 Admin List All Requests
**Description:** عرض جميع طلبات لاقيها (للأدمن)  
**Endpoint:** `GET /api/v1/findit-ads/admin`  
**Auth Required:** Yes (Admin)

```bash
curl -X GET http://localhost:8000/api/v1/findit-ads/admin \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 11.3 FindIt Stats
**Description:** إحصائيات لاقيها الخاصة بي  
**Endpoint:** `GET /api/v1/findit-ads/stats`  
**Auth Required:** Yes

```bash
curl -X GET http://localhost:8000/api/v1/findit-ads/stats \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 11.4 Bulk Action (Admin)
**Description:** عمليات جماعية على طلبات لاقيها  
**Endpoint:** `POST /api/v1/findit-ads/actions/bulk`  
**Auth Required:** Yes (Admin)

```bash
curl -X POST http://localhost:8000/api/v1/findit-ads/actions/bulk \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "action": "activate",
    "ids": [1, 2]
  }'
```

**Actions:** `activate`, `close`, `delete`, `extend` (with `days` parameter)

---

### 11.5 Create FindIt Request
**Description:** إنشاء طلب لاقيها جديد  
**Endpoint:** `POST /api/v1/findit-ads`  
**Auth Required:** Yes

```bash
curl -X POST http://localhost:8000/api/v1/findit-ads \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "title": "Looking for Toyota Corolla",
    "description": "2018 or newer, low mileage",
    "brand_id": 1,
    "model_id": 2,
    "year_min": 2018,
    "year_max": 2024,
    "price_min": 10000,
    "price_max": 25000,
    "city_id": 1,
    "country_id": 1,
    "is_private": true
  }'
```

---

### 11.6 Get FindIt Request Details
**Description:** عرض تفاصيل طلب لاقيها  
**Endpoint:** `GET /api/v1/findit-ads/{id}`  
**Auth Required:** Yes (Owner or Admin)

```bash
curl -X GET http://localhost:8000/api/v1/findit-ads/1 \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 11.7 Update FindIt Request
**Description:** تعديل طلب لاقيها  
**Endpoint:** `PUT /api/v1/findit-ads/{id}`  
**Auth Required:** Yes (Owner)

```bash
curl -X PUT http://localhost:8000/api/v1/findit-ads/1 \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "price_max": 28000
  }'
```

---

### 11.8 Delete FindIt Request
**Description:** حذف طلب لاقيها  
**Endpoint:** `DELETE /api/v1/findit-ads/{id}`  
**Auth Required:** Yes (Owner or Admin)

```bash
curl -X DELETE http://localhost:8000/api/v1/findit-ads/1 \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 11.9 Activate Request
**Description:** تفعيل طلب مسودة  
**Endpoint:** `POST /api/v1/findit-ads/{id}/activate`  
**Auth Required:** Yes (Owner)

```bash
curl -X POST http://localhost:8000/api/v1/findit-ads/1/activate \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 11.10 Close Request
**Description:** إغلاق طلب نشط  
**Endpoint:** `POST /api/v1/findit-ads/{id}/close`  
**Auth Required:** Yes (Owner)

```bash
curl -X POST http://localhost:8000/api/v1/findit-ads/1/close \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 11.11 Extend Request
**Description:** تمديد صلاحية الطلب  
**Endpoint:** `POST /api/v1/findit-ads/{id}/extend`  
**Auth Required:** Yes (Owner)

```bash
curl -X POST http://localhost:8000/api/v1/findit-ads/1/extend \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "days": 7
  }'
```

---

### 11.12 Reactivate Request
**Description:** إعادة تفعيل طلب مغلق/منتهي  
**Endpoint:** `POST /api/v1/findit-ads/{id}/reactivate`  
**Auth Required:** Yes (Owner)

```bash
curl -X POST http://localhost:8000/api/v1/findit-ads/1/reactivate \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 11.13 List Matches
**Description:** عرض الإعلانات المطابقة للطلب  
**Endpoint:** `GET /api/v1/findit-ads/{id}/matches`  
**Auth Required:** Yes (Owner)

```bash
curl -X GET "http://localhost:8000/api/v1/findit-ads/1/matches?page=1" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 11.14 Get Match Details
**Description:** عرض تفاصيل تطابق معين  
**Endpoint:** `GET /api/v1/findit-ads/{id}/matches/{match}`  
**Auth Required:** Yes (Owner)

```bash
curl -X GET http://localhost:8000/api/v1/findit-ads/1/matches/1 \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 11.15 Dismiss Match
**Description:** استبعاد تطابق  
**Endpoint:** `POST /api/v1/findit-ads/{id}/matches/{match}/dismiss`  
**Auth Required:** Yes (Owner)

```bash
curl -X POST http://localhost:8000/api/v1/findit-ads/1/matches/1/dismiss \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 11.16 Restore Match
**Description:** استعادة تطابق مستبعد  
**Endpoint:** `POST /api/v1/findit-ads/{id}/matches/{match}/restore`  
**Auth Required:** Yes (Owner)

```bash
curl -X POST http://localhost:8000/api/v1/findit-ads/1/matches/1/restore \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 11.17 Refresh Matches
**Description:** إعادة البحث عن تطابقات جديدة  
**Endpoint:** `POST /api/v1/findit-ads/{id}/refresh-matches`  
**Auth Required:** Yes (Owner)

```bash
curl -X POST http://localhost:8000/api/v1/findit-ads/1/refresh-matches \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 11.18 Similar Ads (Notifications)
**Description:** عرض إعلانات مشابهة للإشعارات  
**Endpoint:** `GET /api/v1/findit-ads/{id}/similar`  
**Auth Required:** Yes (Owner)

```bash
curl -X GET http://localhost:8000/api/v1/findit-ads/1/similar \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

## 12. Reviews

### 12.1 List Reviews (Public)
**Description:** عرض جميع التقييمات  
**Endpoint:** `GET /api/v1/reviews`  
**Auth Required:** No

```bash
curl -X GET "http://localhost:8000/api/v1/reviews?min_stars=4" \
  -H "Accept: application/json"
```

**Query Parameters:**
- `min_stars`: Minimum rating (1-5)
- `page`, `limit`: Pagination

---

### 12.2 Get Review Details (Public)
**Description:** عرض تفاصيل تقييم  
**Endpoint:** `GET /api/v1/reviews/{id}`  
**Auth Required:** No

```bash
curl -X GET http://localhost:8000/api/v1/reviews/1 \
  -H "Accept: application/json"
```

---

### 12.3 Ad Reviews (Public)
**Description:** عرض تقييمات إعلان معين  
**Endpoint:** `GET /api/v1/ads/{ad}/reviews`  
**Auth Required:** No

```bash
curl -X GET http://localhost:8000/api/v1/ads/1/reviews \
  -H "Accept: application/json"
```

---

### 12.4 User Reviews (Public)
**Description:** عرض تقييمات بائع معين  
**Endpoint:** `GET /api/v1/users/{user}/reviews`  
**Auth Required:** No

```bash
curl -X GET http://localhost:8000/api/v1/users/1/reviews \
  -H "Accept: application/json"
```

---

### 12.5 Create Review
**Description:** إنشاء تقييم جديد  
**Endpoint:** `POST /api/v1/reviews`  
**Auth Required:** Yes

```bash
curl -X POST http://localhost:8000/api/v1/reviews \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "ad_id": 1,
    "user_id": 2,
    "stars": 5,
    "comment": "Excellent seller, highly recommended!"
  }'
```

**Note:** Can review an ad, a user, or both. Cannot review your own ad or yourself.

---

### 12.6 My Reviews
**Description:** عرض تقييماتي  
**Endpoint:** `GET /api/v1/reviews/my-reviews`  
**Auth Required:** Yes

```bash
curl -X GET http://localhost:8000/api/v1/reviews/my-reviews \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 12.7 Update Review
**Description:** تعديل تقييم  
**Endpoint:** `PUT /api/v1/reviews/{id}`  
**Auth Required:** Yes (Owner or Admin)

```bash
curl -X PUT http://localhost:8000/api/v1/reviews/1 \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "stars": 4,
    "comment": "Updated review"
  }'
```

---

### 12.8 Delete Review
**Description:** حذف تقييم  
**Endpoint:** `DELETE /api/v1/reviews/{id}`  
**Auth Required:** Yes (Owner or Admin)

```bash
curl -X DELETE http://localhost:8000/api/v1/reviews/1 \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

## 13. Reports

### 13.1 Create Report
**Description:** إنشاء بلاغ  
**Endpoint:** `POST /api/v1/reports`  
**Auth Required:** Yes

```bash
curl -X POST http://localhost:8000/api/v1/reports \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "target_type": "ad",
    "target_id": 1,
    "reason": "spam",
    "description": "This ad contains misleading information"
  }'
```

**Target Types:** `ad`, `user`  
**Reasons:** `spam`, `inappropriate`, `fraud`, `other`

---

### 13.2 My Reports
**Description:** عرض بلاغاتي  
**Endpoint:** `GET /api/v1/reports/my-reports`  
**Auth Required:** Yes

```bash
curl -X GET http://localhost:8000/api/v1/reports/my-reports \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 13.3 Get Report Details
**Description:** عرض تفاصيل بلاغ  
**Endpoint:** `GET /api/v1/reports/{id}`  
**Auth Required:** Yes (Owner, Assigned Moderator, or Admin)

```bash
curl -X GET http://localhost:8000/api/v1/reports/1 \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 13.4 Admin List Reports
**Description:** عرض جميع البلاغات (للأدمن/المشرف)  
**Endpoint:** `GET /api/v1/reports/admin/index`  
**Auth Required:** Yes (Admin or Moderator)

```bash
curl -X GET "http://localhost:8000/api/v1/reports/admin/index?status=pending&target_type=ad" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Query Parameters:**
- `status`: pending, in_review, resolved, closed
- `target_type`: ad, user
- `assigned_to`: Moderator ID

---

### 13.5 Assign Report
**Description:** تعيين بلاغ لمشرف  
**Endpoint:** `POST /api/v1/reports/{id}/assign`  
**Auth Required:** Yes (Admin)

```bash
curl -X POST http://localhost:8000/api/v1/reports/1/assign \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "moderator_id": 5
  }'
```

---

### 13.6 Update Report Status
**Description:** تحديث حالة بلاغ  
**Endpoint:** `PUT /api/v1/reports/{id}/status`  
**Auth Required:** Yes (Admin or Assigned Moderator)

```bash
curl -X PUT http://localhost:8000/api/v1/reports/1/status \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "status": "in_review",
    "notes": "Investigating the issue"
  }'
```

---

### 13.7 Resolve Report
**Description:** حل بلاغ  
**Endpoint:** `POST /api/v1/reports/{id}/actions/resolve`  
**Auth Required:** Yes (Admin or Assigned Moderator)

```bash
curl -X POST http://localhost:8000/api/v1/reports/1/actions/resolve \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "resolution_notes": "Ad removed due to violation"
  }'
```

---

### 13.8 Close Report
**Description:** إغلاق بلاغ  
**Endpoint:** `POST /api/v1/reports/{id}/actions/close`  
**Auth Required:** Yes (Admin or Assigned Moderator)

```bash
curl -X POST http://localhost:8000/api/v1/reports/1/actions/close \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 13.9 Delete Report
**Description:** حذف بلاغ  
**Endpoint:** `DELETE /api/v1/reports/{id}`  
**Auth Required:** Yes (Admin only)

```bash
curl -X DELETE http://localhost:8000/api/v1/reports/1 \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

## 14. Packages/Subscriptions

### 14.1 List Packages (Public)
**Description:** عرض الباقات المتاحة  
**Endpoint:** `GET /api/v1/packages`  
**Auth Required:** No

```bash
curl -X GET http://localhost:8000/api/v1/packages \
  -H "Accept: application/json"
```

---

### 14.2 Get Package Details (Public)
**Description:** عرض تفاصيل باقة  
**Endpoint:** `GET /api/v1/packages/{id}`  
**Auth Required:** No

```bash
curl -X GET http://localhost:8000/api/v1/packages/1 \
  -H "Accept: application/json"
```

---

### 14.3 Package Stats (Admin)
**Description:** إحصائيات الباقات  
**Endpoint:** `GET /api/v1/packages/stats`  
**Auth Required:** Yes (Admin)

```bash
curl -X GET http://localhost:8000/api/v1/packages/stats \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 14.4 Create Package (Admin)
**Description:** إنشاء باقة جديدة  
**Endpoint:** `POST /api/v1/packages`  
**Auth Required:** Yes (Admin)

```bash
curl -X POST http://localhost:8000/api/v1/packages \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name_en": "Premium",
    "name_ar": "مميز",
    "description_en": "Premium package with unlimited ads",
    "price": 99.99,
    "duration_days": 30,
    "max_ads": 50,
    "features": ["featured_ads", "priority_support"],
    "status": "active"
  }'
```

---

### 14.5 Update Package (Admin)
**Description:** تعديل باقة  
**Endpoint:** `PUT /api/v1/packages/{id}`  
**Auth Required:** Yes (Admin)

```bash
curl -X PUT http://localhost:8000/api/v1/packages/1 \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "price": 89.99
  }'
```

---

### 14.6 Delete Package (Admin)
**Description:** حذف باقة  
**Endpoint:** `DELETE /api/v1/packages/{id}`  
**Auth Required:** Yes (Admin)

```bash
curl -X DELETE http://localhost:8000/api/v1/packages/1 \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 14.7 Assign Package to User (Admin)
**Description:** تعيين باقة لمستخدم  
**Endpoint:** `POST /api/v1/packages/{id}/assign`  
**Auth Required:** Yes (Admin)

```bash
curl -X POST http://localhost:8000/api/v1/packages/1/assign \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "user_id": 5,
    "starts_at": "2026-02-01",
    "payment_method": "credit_card",
    "payment_reference": "TXN123456"
  }'
```

---

### 14.8 My Packages
**Description:** عرض باقاتي  
**Endpoint:** `GET /api/v1/packages/my-packages`  
**Auth Required:** Yes

```bash
curl -X GET http://localhost:8000/api/v1/packages/my-packages \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 14.9 User Packages
**Description:** عرض باقات مستخدم  
**Endpoint:** `GET /api/v1/users/{user}/packages`  
**Auth Required:** Yes (Self or Admin)

```bash
curl -X GET http://localhost:8000/api/v1/users/1/packages \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 14.10 Update User Package (Admin)
**Description:** تعديل اشتراك مستخدم  
**Endpoint:** `PUT /api/v1/user-packages/{id}`  
**Auth Required:** Yes (Admin)

```bash
curl -X PUT http://localhost:8000/api/v1/user-packages/1 \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "ends_at": "2026-03-15"
  }'
```

---

### 14.11 Delete User Package (Admin)
**Description:** إلغاء اشتراك مستخدم  
**Endpoint:** `DELETE /api/v1/user-packages/{id}`  
**Auth Required:** Yes (Admin)

```bash
curl -X DELETE http://localhost:8000/api/v1/user-packages/1 \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 14.12 Get Package Features (Admin)
**Description:** الحصول على ميزات وصلاحيات الباقة - عرض جميع الإعدادات التفصيلية للباقة مثل أنواع الإعلانات المسموحة والحدود والميزات الإضافية  
**Endpoint:** `GET /api/v1/packages/{id}/features`  
**Auth Required:** Yes (Admin)

```bash
curl -X GET http://localhost:8000/api/v1/packages/1/features \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Response:**
```json
{
  "status": "success",
  "message": "Package features retrieved successfully",
  "data": {
    "id": 1,
    "package_id": 1,
    "configured": true,
    "ad_types": {
      "normal": {
        "allowed": true,
        "limit": 20,
        "unlimited": false
      },
      "unique": {
        "allowed": true,
        "limit": 5,
        "unlimited": false
      },
      "caishha": {
        "allowed": true,
        "limit": 10,
        "unlimited": false
      },
      "findit": {
        "allowed": true,
        "limit": 3,
        "unlimited": false
      },
      "auction": {
        "allowed": false,
        "limit": 0,
        "unlimited": false
      }
    },
    "role_features": {
      "grants_seller_status": true,
      "auto_verify_seller": false,
      "grants_marketer_status": false,
      "grants_verified_badge": false
    },
    "ad_capabilities": {
      "can_push_to_facebook": true,
      "can_auto_republish": true,
      "can_use_banner": true,
      "can_use_background_color": false,
      "can_bulk_upload": false
    },
    "additional_features": {
      "images_per_ad_limit": 15,
      "videos_per_ad_limit": 2,
      "ad_duration_days": 45,
      "max_ad_duration_days": 90,
      "can_extend_ads": true,
      "priority_support": false,
      "featured_in_search": false
    },
    "summary": {
      "allowed_ad_types": ["normal", "unique", "caishha", "findit"],
      "grants_roles": ["seller"],
      "has_advanced_features": true
    }
  }
}
```

---

### 14.13 Create Package Features (Admin)
**Description:** إنشاء ميزات وصلاحيات الباقة - تحديد أنواع الإعلانات المسموحة والحدود والميزات (خطوة 2 بعد إنشاء الباقة)  
**Endpoint:** `POST /api/v1/packages/{id}/features`  
**Auth Required:** Yes (Admin)

```bash
curl -X POST http://localhost:8000/api/v1/packages/1/features \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "normal_ads_allowed": true,
    "normal_ads_limit": 20,
    "unique_ads_allowed": true,
    "unique_ads_limit": 5,
    "caishha_ads_allowed": true,
    "caishha_ads_limit": 10,
    "findit_ads_allowed": true,
    "findit_ads_limit": 3,
    "auction_ads_allowed": false,
    "auction_ads_limit": 0,
    "grants_seller_status": true,
    "auto_verify_seller": false,
    "grants_marketer_status": false,
    "grants_verified_badge": false,
    "can_push_to_facebook": true,
    "can_auto_republish": true,
    "can_use_banner": true,
    "can_use_background_color": false,
    "can_bulk_upload": false,
    "images_per_ad_limit": 15,
    "videos_per_ad_limit": 2,
    "ad_duration_days": 45,
    "max_ad_duration_days": 90,
    "can_extend_ads": true,
    "priority_support": false,
    "featured_in_search": false
  }'
```

**Response:**
```json
{
  "status": "success",
  "message": "Package features created successfully",
  "data": {
    "id": 1,
    "package_id": 1,
    "configured": true,
    "ad_types": {
      "normal": {
        "allowed": true,
        "limit": 20,
        "unlimited": false
      },
      "unique": {
        "allowed": true,
        "limit": 5,
        "unlimited": false
      }
    }
  }
}
```

**Validation Notes:**
- `*_ads_limit` must be >= 0
- `max_ad_duration_days` must be >= `ad_duration_days`
- `auto_verify_seller` requires `grants_seller_status` to be `true`
- Set limit to `null` for unlimited ads

---

### 14.14 Update Package Features (Admin)
**Description:** تحديث ميزات وصلاحيات الباقة - تعديل الإعدادات التفصيلية للباقة  
**Endpoint:** `PUT /api/v1/packages/{id}/features`  
**Auth Required:** Yes (Admin)

```bash
curl -X PUT http://localhost:8000/api/v1/packages/1/features \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "normal_ads_limit": 25,
    "unique_ads_limit": 10,
    "can_push_to_facebook": false,
    "images_per_ad_limit": 20
  }'
```

**Response:**
```json
{
  "status": "success",
  "message": "Package features updated successfully",
  "data": {
    "id": 1,
    "package_id": 1,
    "configured": true,
    "ad_types": {
      "normal": {
        "allowed": true,
        "limit": 25,
        "unlimited": false
      }
    }
  }
}
```

**Note:** If features don't exist yet, this endpoint will create them automatically.

---

### 14.15 Delete Package Features (Admin)
**Description:** حذف ميزات الباقة - إعادة تعيين الباقة إلى الإعدادات الافتراضية  
**Endpoint:** `DELETE /api/v1/packages/{id}/features`  
**Auth Required:** Yes (Admin)

```bash
curl -X DELETE http://localhost:8000/api/v1/packages/1/features \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Response:**
```json
{
  "status": "success",
  "message": "Package features deleted successfully"
}
```

---

### 14.16 My Package Features
**Description:** عرض ميزات باقتي الحالية - يعرض المستخدم الميزات والصلاحيات المتاحة له من خلال اشتراكه النشط  
**Endpoint:** `GET /api/v1/packages/my-features`  
**Auth Required:** Yes

```bash
curl -X GET http://localhost:8000/api/v1/packages/my-features \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Response (User with Active Package):**
```json
{
  "status": "success",
  "message": "Package features retrieved successfully",
  "data": {
    "has_package": true,
    "package": {
      "id": 1,
      "name": "Premium Package",
      "expires_at": "2026-03-02"
    },
    "features": {
      "id": 1,
      "package_id": 1,
      "configured": true,
      "ad_types": {
        "normal": {
          "allowed": true,
          "limit": 20,
          "unlimited": false
        },
        "unique": {
          "allowed": true,
          "limit": 5,
          "unlimited": false
        }
      },
      "role_features": {
        "grants_seller_status": true,
        "auto_verify_seller": false,
        "grants_marketer_status": false,
        "grants_verified_badge": false
      },
      "ad_capabilities": {
        "can_push_to_facebook": true,
        "can_auto_republish": true,
        "can_use_banner": true,
        "can_use_background_color": false
      }
    }
  }
}
```

**Response (User without Package):**
```json
{
  "status": "success",
  "message": "No active package found",
  "data": {
    "has_package": false,
    "features": {
      "configured": false,
      "ad_types": {
        "normal": {
          "allowed": true,
          "limit": 5,
          "unlimited": false
        },
        "unique": {
          "allowed": false,
          "limit": 0,
          "unlimited": false
        },
        "caishha": {
          "allowed": false,
          "limit": 0,
          "unlimited": false
        },
        "findit": {
          "allowed": false,
          "limit": 0,
          "unlimited": false
        },
        "auction": {
          "allowed": false,
          "limit": 0,
          "unlimited": false
        }
      },
      "summary": {
        "allowed_ad_types": ["normal"]
      }
    }
  }
}
```

---

### 14.17 Check Package Capability
**Description:** التحقق من صلاحية معينة - يتحقق إذا كان المستخدم يمتلك صلاحية معينة من خلال باقته  
**Endpoint:** `POST /api/v1/packages/check-capability`  
**Auth Required:** Yes

```bash
curl -X POST http://localhost:8000/api/v1/packages/check-capability \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "capability": "publish_unique_ads"
  }'
```

**Supported Capabilities:**
- `publish_normal_ads`
- `publish_unique_ads`
- `publish_caishha_ads`
- `publish_findit_ads`
- `publish_auction_ads`
- `push_to_facebook`
- `auto_republish`
- `use_banner`
- `use_background_color`
- `bulk_upload`
- `extend_ads`
- `priority_support`
- `featured_in_search`

**Response (Has Capability):**
```json
{
  "status": "success",
  "data": {
    "has_capability": true,
    "capability": "publish_unique_ads",
    "message": "User has this capability",
    "details": {
      "from_package": "Premium Package",
      "limit": 5,
      "used": 2,
      "remaining": 3
    }
  }
}
```

**Response (No Capability):**
```json
{
  "status": "success",
  "data": {
    "has_capability": false,
    "capability": "publish_auction_ads",
    "message": "User does not have this capability",
    "reason": "Not allowed in current package"
  }
}
```

---

### 14.18 Request a Package (User)
**Description:** طلب باقة - المستخدم يقدم طلب للحصول على باقة معينة  
**Endpoint:** `POST /api/v1/packages/{package}/request`  
**Auth Required:** Yes

```bash
curl -X POST http://localhost:8000/api/v1/packages/1/request \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "user_message": "I would like to subscribe to this package for my showroom business"
  }'
```

**Request Body:**
```json
{
  "user_message": "Optional message explaining why you need this package"
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Package request submitted successfully",
  "data": {
    "id": 15,
    "user_id": 42,
    "package_id": 1,
    "status": "pending",
    "user_message": "I would like to subscribe to this package for my showroom business",
    "admin_notes": null,
    "reviewed_by": null,
    "reviewed_at": null,
    "created_at": "2026-02-02T20:45:00.000000Z",
    "updated_at": "2026-02-02T20:45:00.000000Z",
    "package": {
      "id": 1,
      "name": "Premium Package",
      "price": 99.99,
      "duration_days": 30
    }
  }
}
```

**Error Responses:**

Duplicate pending request (409):
```json
{
  "status": "error",
  "code": 409,
  "message": "You already have a pending request for this package",
  "errors": {
    "package": ["A pending request already exists"]
  }
}
```

Already has package (409):
```json
{
  "status": "error",
  "code": 409,
  "message": "You already have this package assigned",
  "errors": {
    "package": ["This package is already assigned to you"]
  }
}
```

---

### 14.19 View My Package Requests (User)
**Description:** عرض طلباتي للباقات - المستخدم يرى جميع طلباته  
**Endpoint:** `GET /api/v1/user/package-requests`  
**Auth Required:** Yes

```bash
curl -X GET http://localhost:8000/api/v1/user/package-requests?per_page=20 \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Response:**
```json
{
  "status": "success",
  "message": "Package requests retrieved successfully",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 15,
        "user_id": 42,
        "package_id": 1,
        "status": "pending",
        "user_message": "I would like to subscribe to this package",
        "admin_notes": null,
        "reviewed_by": null,
        "reviewed_at": null,
        "created_at": "2026-02-02T20:45:00.000000Z",
        "updated_at": "2026-02-02T20:45:00.000000Z",
        "package": {
          "id": 1,
          "name": "Premium Package",
          "price": 99.99
        },
        "reviewer": null
      },
      {
        "id": 12,
        "user_id": 42,
        "package_id": 3,
        "status": "approved",
        "user_message": "Need basic package to start",
        "admin_notes": "Approved for trial period",
        "reviewed_by": 5,
        "reviewed_at": "2026-02-01T15:30:00.000000Z",
        "created_at": "2026-02-01T14:20:00.000000Z",
        "updated_at": "2026-02-01T15:30:00.000000Z",
        "package": {
          "id": 3,
          "name": "Basic Package",
          "price": 29.99
        },
        "reviewer": {
          "id": 5,
          "name": "Admin User",
          "email": "admin@example.com"
        }
      }
    ],
    "per_page": 20,
    "total": 2
  }
}
```

---

### 14.20 List All Package Requests (Admin)
**Description:** عرض جميع طلبات الباقات - المدير يرى جميع الطلبات مع إمكانية الفلترة  
**Endpoint:** `GET /api/v1/admin/package-requests`  
**Auth Required:** Yes (Admin)

```bash
curl -X GET "http://localhost:8000/api/v1/admin/package-requests?status=pending&per_page=20" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Query Parameters:**
- `status`: pending, approved, rejected
- `user_id`: Filter by specific user
- `package_id`: Filter by specific package
- `per_page`: Items per page

**Response:**
```json
{
  "status": "success",
  "message": "Package requests retrieved successfully",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 15,
        "user_id": 42,
        "package_id": 1,
        "status": "pending",
        "user_message": "I would like to subscribe to this package for my showroom",
        "admin_notes": null,
        "reviewed_by": null,
        "reviewed_at": null,
        "created_at": "2026-02-02T20:45:00.000000Z",
        "updated_at": "2026-02-02T20:45:00.000000Z",
        "user": {
          "id": 42,
          "name": "John Showroom",
          "email": "john@showroom.com",
          "account_type": "seller"
        },
        "package": {
          "id": 1,
          "name": "Premium Package",
          "price": 99.99,
          "duration_days": 30
        }
      }
    ],
    "per_page": 20,
    "total": 5
  }
}
```

---

### 14.21 View Package Request Details (Admin)
**Description:** عرض تفاصيل طلب باقة معين  
**Endpoint:** `GET /api/v1/admin/package-requests/{id}`  
**Auth Required:** Yes (Admin)

```bash
curl -X GET http://localhost:8000/api/v1/admin/package-requests/15 \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Response:**
```json
{
  "status": "success",
  "message": "Package request retrieved successfully",
  "data": {
    "id": 15,
    "user_id": 42,
    "package_id": 1,
    "status": "pending",
    "user_message": "I would like to subscribe to this package for my showroom business",
    "admin_notes": null,
    "reviewed_by": null,
    "reviewed_at": null,
    "created_at": "2026-02-02T20:45:00.000000Z",
    "updated_at": "2026-02-02T20:45:00.000000Z",
    "user": {
      "id": 42,
      "name": "John Showroom",
      "email": "john@showroom.com",
      "phone": "+1234567890",
      "account_type": "seller"
    },
    "package": {
      "id": 1,
      "name": "Premium Package",
      "description": "Full access package with all features",
      "price": 99.99,
      "duration_days": 30,
      "active": true
    }
  }
}
```

---

### 14.22 Review Package Request (Admin)
**Description:** مراجعة طلب باقة - المدير يوافق أو يرفض الطلب  
**Endpoint:** `PATCH /api/v1/admin/package-requests/{id}/review`  
**Auth Required:** Yes (Admin)

```bash
curl -X PATCH http://localhost:8000/api/v1/admin/package-requests/15/review \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "status": "approved",
    "admin_notes": "Approved after verifying business documents"
  }'
```

**Request Body:**
```json
{
  "status": "approved",
  "admin_notes": "Optional notes explaining the decision"
}
```

**Response (Approved):**
```json
{
  "status": "success",
  "message": "Package request reviewed successfully",
  "data": {
    "id": 15,
    "user_id": 42,
    "package_id": 1,
    "status": "approved",
    "user_message": "I would like to subscribe to this package",
    "admin_notes": "Approved after verifying business documents",
    "reviewed_by": 5,
    "reviewed_at": "2026-02-02T21:00:00.000000Z",
    "created_at": "2026-02-02T20:45:00.000000Z",
    "updated_at": "2026-02-02T21:00:00.000000Z",
    "user": {
      "id": 42,
      "name": "John Showroom",
      "email": "john@showroom.com",
      "package_id": 1
    },
    "package": {
      "id": 1,
      "name": "Premium Package",
      "price": 99.99
    },
    "reviewer": {
      "id": 5,
      "name": "Admin User",
      "email": "admin@example.com"
    }
  }
}
```

**Note:** When approved, the package is automatically assigned to the user (user.package_id is updated).

**Error Response (Already Reviewed):**
```json
{
  "status": "error",
  "code": 409,
  "message": "This request has already been reviewed",
  "errors": {
    "request": ["Request status is approved"]
  }
}
```

---

### 14.23 Approve Package Request (Admin)
**Description:** الموافقة على طلب باقة - طريقة مختصرة للموافقة  
**Endpoint:** `POST /api/v1/admin/package-requests/{id}/approve`  
**Auth Required:** Yes (Admin)

```bash
curl -X POST http://localhost:8000/api/v1/admin/package-requests/15/approve \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "admin_notes": "Approved"
  }'
```

**Request Body:**
```json
{
  "admin_notes": "Optional approval notes"
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Package request approved successfully",
  "data": {
    "id": 15,
    "status": "approved",
    "reviewed_by": 5,
    "reviewed_at": "2026-02-02T21:00:00.000000Z",
    "user": {
      "id": 42,
      "package_id": 1
    }
  }
}
```

---

### 14.24 Reject Package Request (Admin)
**Description:** رفض طلب باقة - طريقة مختصرة للرفض  
**Endpoint:** `POST /api/v1/admin/package-requests/{id}/reject`  
**Auth Required:** Yes (Admin)

```bash
curl -X POST http://localhost:8000/api/v1/admin/package-requests/15/reject \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "admin_notes": "Package not suitable for individual accounts"
  }'
```

**Request Body:**
```json
{
  "admin_notes": "Optional rejection reason"
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Package request rejected successfully",
  "data": {
    "id": 15,
    "status": "rejected",
    "admin_notes": "Package not suitable for individual accounts",
    "reviewed_by": 5,
    "reviewed_at": "2026-02-02T21:00:00.000000Z"
  }
}
```

---

## Package Request System Workflow

### User Flow:
1. **Browse Packages** - `GET /api/v1/packages` (public)
2. **Request Package** - `POST /api/v1/packages/{id}/request`
3. **Check Status** - `GET /api/v1/user/package-requests`
4. **Wait for Admin Review**

### Admin Flow:
1. **View Pending Requests** - `GET /api/v1/admin/package-requests?status=pending`
2. **Review Request Details** - `GET /api/v1/admin/package-requests/{id}`
3. **Approve or Reject:**
   - Option A: `PATCH /api/v1/admin/package-requests/{id}/review`
   - Option B: `POST /api/v1/admin/package-requests/{id}/approve`
   - Option C: `POST /api/v1/admin/package-requests/{id}/reject`

### Status Flow:
- **pending** → Initial state when user submits request
- **approved** → Admin approves → Package automatically assigned to user
- **rejected** → Admin rejects → User notified with reason

### Validations:
- ✅ User cannot submit duplicate pending requests for same package
- ✅ User cannot request a package they already have
- ✅ Admin cannot review the same request twice
- ✅ Approval automatically assigns package to user

---

## Package Features System Overview

### Two-Step Package Creation Process:

**Step 1: Create Package (Basic Info)**
```bash
POST /api/v1/packages
{
  "name": "Premium Package",
  "price": 99.99,
  "duration_days": 30,
  "active": true
}
```

**Step 2: Configure Features (Detailed Permissions)**
```bash
POST /api/v1/packages/{id}/features
{
  "normal_ads_allowed": true,
  "normal_ads_limit": 20,
  "unique_ads_allowed": true,
  "unique_ads_limit": 5,
  "grants_seller_status": true,
  "can_push_to_facebook": true
}
```

### Feature Categories:

**1. Ad Type Permissions:**
- Normal ads (regular listings)
- Unique ads (featured/highlighted listings)
- Caishha ads (rentals)
- FindIt ads (private search requests)
- Auction ads (bidding system)

**2. Role Features:**
- Grant seller status
- Auto-verify seller (skips verification process)
- Grant marketer status
- Grant verified badge

**3. Ad Capabilities:**
- Push to Facebook
- Auto-republish ads
- Use banner in ads
- Use background color
- Bulk upload ads

**4. Additional Features:**
- Images per ad limit
- Videos per ad limit
- Ad duration (days)
- Maximum ad duration
- Extend ads ability
- Priority support
- Featured in search results

### Default Behavior (No Package):
- Can publish up to 5 normal ads
- No access to unique, caishha, findit, or auction ads
- No special features or capabilities
- Basic support only

---

## 15. Notifications

### 15.1 List My Notifications
**Description:** عرض إشعاراتي  
**Endpoint:** `GET /api/v1/notifications`  
**Auth Required:** Yes

```bash
curl -X GET "http://localhost:8000/api/v1/notifications?unread_only=true" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Query Parameters:**
- `unread_only`: true/false
- `page`, `limit`: Pagination

---

### 15.2 Get Notification Details
**Description:** عرض تفاصيل إشعار  
**Endpoint:** `GET /api/v1/notifications/{id}`  
**Auth Required:** Yes

```bash
curl -X GET http://localhost:8000/api/v1/notifications/abc-123 \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 15.3 Mark as Read
**Description:** تعليم إشعار كمقروء  
**Endpoint:** `PATCH /api/v1/notifications/{id}/read`  
**Auth Required:** Yes

```bash
curl -X PATCH http://localhost:8000/api/v1/notifications/abc-123/read \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 15.4 Mark All as Read
**Description:** تعليم جميع الإشعارات كمقروءة  
**Endpoint:** `POST /api/v1/notifications/read-all`  
**Auth Required:** Yes

```bash
curl -X POST http://localhost:8000/api/v1/notifications/read-all \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 15.5 Delete Notification
**Description:** حذف إشعار  
**Endpoint:** `DELETE /api/v1/notifications/{id}`  
**Auth Required:** Yes

```bash
curl -X DELETE http://localhost:8000/api/v1/notifications/abc-123 \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 15.6 Send Notification (Admin)
**Description:** إرسال إشعار لمستخدم أو مجموعة  
**Endpoint:** `POST /api/v1/notifications/send`  
**Auth Required:** Yes (Admin)

```bash
curl -X POST http://localhost:8000/api/v1/notifications/send \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "title": "System Update",
    "body": "New features are now available!",
    "user_ids": [1, 2, 3],
    "channel": "database"
  }'
```

**Options:**
- `user_ids`: Array of specific user IDs
- `role`: Send to all users with a specific role
- `all`: true to send to all users
- `channel`: database, mail, push

---

## 16. Favorites

### 16.1 List My Favorites
**Description:** عرض إعلاناتي المفضلة  
**Endpoint:** `GET /api/v1/favorites`  
**Auth Required:** Yes

```bash
curl -X GET http://localhost:8000/api/v1/favorites \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 16.2 Get Favorites Count
**Description:** عدد المفضلات  
**Endpoint:** `GET /api/v1/favorites/count`  
**Auth Required:** Yes

```bash
curl -X GET http://localhost:8000/api/v1/favorites/count \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 16.3 Check if Ad is Favorited
**Description:** التحقق إذا كان إعلان في المفضلة  
**Endpoint:** `GET /api/v1/favorites/check/{ad}`  
**Auth Required:** Yes

```bash
curl -X GET http://localhost:8000/api/v1/favorites/check/1 \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 16.4 Add to Favorites
**Description:** إضافة إعلان للمفضلة  
**Endpoint:** `POST /api/v1/favorites/{ad}`  
**Auth Required:** Yes

```bash
curl -X POST http://localhost:8000/api/v1/favorites/1 \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 16.5 Toggle Favorite
**Description:** تبديل حالة المفضلة  
**Endpoint:** `POST /api/v1/favorites/toggle/{ad}`  
**Auth Required:** Yes

```bash
curl -X POST http://localhost:8000/api/v1/favorites/toggle/1 \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 16.6 Remove Favorite by ID
**Description:** إزالة مفضلة بالمعرف  
**Endpoint:** `DELETE /api/v1/favorites/{favorite}`  
**Auth Required:** Yes

```bash
curl -X DELETE http://localhost:8000/api/v1/favorites/1 \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 16.7 Remove Favorite by Ad ID
**Description:** إزالة مفضلة بمعرف الإعلان  
**Endpoint:** `DELETE /api/v1/favorites/ad/{ad}`  
**Auth Required:** Yes

```bash
curl -X DELETE http://localhost:8000/api/v1/favorites/ad/1 \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

## 17. Saved Searches

### 17.1 List My Saved Searches
**Description:** عرض عمليات البحث المحفوظة  
**Endpoint:** `GET /api/v1/saved-searches`  
**Auth Required:** Yes

```bash
curl -X GET http://localhost:8000/api/v1/saved-searches \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 17.2 Create Saved Search
**Description:** حفظ عملية بحث  
**Endpoint:** `POST /api/v1/saved-searches`  
**Auth Required:** Yes

```bash
curl -X POST http://localhost:8000/api/v1/saved-searches \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Toyota under 20k",
    "query_params": {
      "brand_id": 1,
      "max_price": 20000,
      "min_year": 2020
    },
    "notify_on_match": true
  }'
```

---

### 17.3 Get Saved Search Details
**Description:** عرض تفاصيل بحث محفوظ  
**Endpoint:** `GET /api/v1/saved-searches/{id}`  
**Auth Required:** Yes

```bash
curl -X GET http://localhost:8000/api/v1/saved-searches/1 \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 17.4 Update Saved Search
**Description:** تعديل بحث محفوظ  
**Endpoint:** `PUT /api/v1/saved-searches/{id}`  
**Auth Required:** Yes

```bash
curl -X PUT http://localhost:8000/api/v1/saved-searches/1 \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Toyota under 25k",
    "query_params": {
      "brand_id": 1,
      "max_price": 25000
    }
  }'
```

---

### 17.5 Delete Saved Search
**Description:** حذف بحث محفوظ  
**Endpoint:** `DELETE /api/v1/saved-searches/{id}`  
**Auth Required:** Yes

```bash
curl -X DELETE http://localhost:8000/api/v1/saved-searches/1 \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

## 18. Blogs

### 18.1 List Published Blogs (Public)
**Description:** عرض المدونات المنشورة  
**Endpoint:** `GET /api/v1/blogs`  
**Auth Required:** No

```bash
curl -X GET "http://localhost:8000/api/v1/blogs?search=cars&sort=date_desc" \
  -H "Accept: application/json"
```

**Query Parameters:**
- `search`: Search in title/content
- `sort`: date_asc, date_desc
- `page`, `limit`: Pagination

---

### 18.2 Get Blog Details (Public)
**Description:** عرض تفاصيل مدونة  
**Endpoint:** `GET /api/v1/blogs/{id}`  
**Auth Required:** No

```bash
curl -X GET http://localhost:8000/api/v1/blogs/1 \
  -H "Accept: application/json"
```

---

### 18.3 Admin List All Blogs
**Description:** عرض جميع المدونات (للأدمن)  
**Endpoint:** `GET /api/v1/admin/blogs`  
**Auth Required:** Yes (Admin)

```bash
curl -X GET "http://localhost:8000/api/v1/admin/blogs?status=draft" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Query Parameters:**
- `status`: published, draft

---

### 18.4 Admin Get Blog Details
**Description:** عرض أي مدونة (للأدمن)  
**Endpoint:** `GET /api/v1/admin/blogs/{id}`  
**Auth Required:** Yes (Admin)

```bash
curl -X GET http://localhost:8000/api/v1/admin/blogs/1 \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 18.5 Create Blog (Admin)
**Description:** إنشاء مدونة جديدة  
**Endpoint:** `POST /api/v1/admin/blogs`  
**Auth Required:** Yes (Admin)

```bash
curl -X POST http://localhost:8000/api/v1/admin/blogs \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "title": "10 Tips for Buying a Used Car",
    "slug": "10-tips-buying-used-car",
    "body": "Full article content here...",
    "excerpt": "Learn the essential tips...",
    "status": "published",
    "featured_image_id": 1
  }'
```

---

### 18.6 Update Blog (Admin)
**Description:** تعديل مدونة  
**Endpoint:** `PUT /api/v1/admin/blogs/{id}`  
**Auth Required:** Yes (Admin)

```bash
curl -X PUT http://localhost:8000/api/v1/admin/blogs/1 \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "title": "Updated Title"
  }'
```

---

### 18.7 Delete Blog (Admin)
**Description:** حذف مدونة  
**Endpoint:** `DELETE /api/v1/admin/blogs/{id}`  
**Auth Required:** Yes (Admin)

```bash
curl -X DELETE http://localhost:8000/api/v1/admin/blogs/1 \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

## 19. Specifications

### 19.1 List Specifications (Admin)
**Description:** عرض المواصفات  
**Endpoint:** `GET /api/v1/admin/specifications`  
**Auth Required:** Yes (Admin)

```bash
curl -X GET "http://localhost:8000/api/v1/admin/specifications?type=select" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Query Parameters:**
- `type`: text, number, select, boolean
- `search`: Search by name

---

### 19.2 Get Specification Details (Admin)
**Description:** عرض تفاصيل مواصفة  
**Endpoint:** `GET /api/v1/admin/specifications/{id}`  
**Auth Required:** Yes (Admin)

```bash
curl -X GET http://localhost:8000/api/v1/admin/specifications/1 \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 19.3 Create Specification (Admin)
**Description:** إنشاء مواصفة جديدة  
**Endpoint:** `POST /api/v1/admin/specifications`  
**Auth Required:** Yes (Admin)

```bash
curl -X POST http://localhost:8000/api/v1/admin/specifications \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name_en": "Fuel Type",
    "name_ar": "نوع الوقود",
    "type": "select",
    "options": ["Petrol", "Diesel", "Electric", "Hybrid"],
    "is_required": true
  }'
```

---

### 19.4 Update Specification (Admin)
**Description:** تعديل مواصفة  
**Endpoint:** `PUT /api/v1/admin/specifications/{id}`  
**Auth Required:** Yes (Admin)

```bash
curl -X PUT http://localhost:8000/api/v1/admin/specifications/1 \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "is_required": false
  }'
```

---

### 19.5 Delete Specification (Admin)
**Description:** حذف مواصفة  
**Endpoint:** `DELETE /api/v1/admin/specifications/{id}`  
**Auth Required:** Yes (Admin)

```bash
curl -X DELETE http://localhost:8000/api/v1/admin/specifications/1 \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

## 20. Categories

### 20.1 List Categories (Admin)
**Description:** عرض الفئات  
**Endpoint:** `GET /api/v1/admin/categories`  
**Auth Required:** Yes (Admin)

```bash
curl -X GET "http://localhost:8000/api/v1/admin/categories?status=active" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 20.2 Get Category Details (Admin)
**Description:** عرض تفاصيل فئة  
**Endpoint:** `GET /api/v1/admin/categories/{id}`  
**Auth Required:** Yes (Admin)

```bash
curl -X GET http://localhost:8000/api/v1/admin/categories/1 \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 20.3 Create Category (Admin)
**Description:** إنشاء فئة جديدة  
**Endpoint:** `POST /api/v1/admin/categories`  
**Auth Required:** Yes (Admin)

```bash
curl -X POST http://localhost:8000/api/v1/admin/categories \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name_en": "Sedans",
    "name_ar": "سيدان",
    "parent_id": null,
    "status": "active"
  }'
```

---

### 20.4 Update Category (Admin)
**Description:** تعديل فئة  
**Endpoint:** `PUT /api/v1/admin/categories/{id}`  
**Auth Required:** Yes (Admin)

```bash
curl -X PUT http://localhost:8000/api/v1/admin/categories/1 \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name_en": "Luxury Sedans"
  }'
```

---

### 20.5 Delete Category (Admin)
**Description:** حذف فئة  
**Endpoint:** `DELETE /api/v1/admin/categories/{id}`  
**Auth Required:** Yes (Admin)

```bash
curl -X DELETE http://localhost:8000/api/v1/admin/categories/1 \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 20.6 Get Category Specifications (Admin)
**Description:** عرض المواصفات المرتبطة بالفئة - يعرض جميع المواصفات المُسندة للفئة مع ترتيبها  
**Endpoint:** `GET /api/v1/admin/categories/{id}/specifications`  
**Auth Required:** Yes (Admin)

```bash
curl -X GET http://localhost:8000/api/v1/admin/categories/1/specifications \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Response:**
```json
{
  "status": "success",
  "message": "Category specifications retrieved successfully",
  "data": [
    {
      "id": 1,
      "name_en": "Brand",
      "name_ar": "الماركة",
      "type": "select",
      "values": ["Toyota", "Honda", "BMW"],
      "image_id": null,
      "order": 0,
      "created_at": "2026-01-15T10:30:00.000000Z",
      "updated_at": "2026-01-15T10:30:00.000000Z"
    },
    {
      "id": 3,
      "name_en": "Color",
      "name_ar": "اللون",
      "type": "select",
      "values": ["Red", "Blue", "Black", "White"],
      "image_id": null,
      "order": 1,
      "created_at": "2026-01-15T10:30:00.000000Z",
      "updated_at": "2026-01-15T10:30:00.000000Z"
    },
    {
      "id": 4,
      "name_en": "Year",
      "name_ar": "السنة",
      "type": "number",
      "values": null,
      "image_id": null,
      "order": 2,
      "created_at": "2026-01-15T10:30:00.000000Z",
      "updated_at": "2026-01-15T10:30:00.000000Z"
    }
  ]
}
```

---

### 20.7 Assign Specifications to Category (Admin)
**Description:** إسناد مواصفات للفئة - يستبدل جميع المواصفات الحالية بالقائمة المُرسلة (مثال: لإسناد المواصفات 1، 3، 4 فقط دون 2)  
**Endpoint:** `POST /api/v1/admin/categories/{id}/specifications/assign`  
**Auth Required:** Yes (Admin)

```bash
curl -X POST http://localhost:8000/api/v1/admin/categories/1/specifications/assign \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "specification_ids": [1, 3, 4]
  }'
```

**Request Body:**
```json
{
  "specification_ids": [1, 3, 4]
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Specifications assigned to category successfully",
  "data": {
    "id": 1,
    "name_en": "Cars",
    "name_ar": "سيارات",
    "status": "active",
    "specs_group_id": null,
    "specifications": [
      {
        "id": 1,
        "name_en": "Brand",
        "name_ar": "الماركة",
        "type": "select",
        "order": 0
      },
      {
        "id": 3,
        "name_en": "Color",
        "name_ar": "اللون",
        "type": "select",
        "order": 1
      },
      {
        "id": 4,
        "name_en": "Year",
        "name_ar": "السنة",
        "type": "number",
        "order": 2
      }
    ],
    "specifications_count": 3,
    "created_at": "2026-01-10T08:00:00.000000Z",
    "updated_at": "2026-02-02T20:15:00.000000Z"
  }
}
```

**Validation:**
- `specification_ids` is required and must be an array
- Each ID must exist in the `specifications` table
- Order is automatically assigned based on array index

**Note:** This endpoint **replaces** all existing specifications. If the category had specifications [1, 2, 5] and you send [1, 3, 4], the final result will be [1, 3, 4] only.

---

### 20.8 Attach Single Specification to Category (Admin)
**Description:** إضافة مواصفة واحدة للفئة - يضيف مواصفة دون التأثير على المواصفات الموجودة  
**Endpoint:** `POST /api/v1/admin/categories/{id}/specifications/attach`  
**Auth Required:** Yes (Admin)

```bash
curl -X POST http://localhost:8000/api/v1/admin/categories/1/specifications/attach \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "specification_id": 5,
    "order": 3
  }'
```

**Request Body:**
```json
{
  "specification_id": 5,
  "order": 3
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Specification attached to category successfully",
  "data": {
    "id": 1,
    "name_en": "Cars",
    "name_ar": "سيارات",
    "specifications": [
      {
        "id": 1,
        "name_en": "Brand",
        "name_ar": "الماركة",
        "type": "select",
        "order": 0
      },
      {
        "id": 3,
        "name_en": "Color",
        "name_ar": "اللون",
        "type": "select",
        "order": 1
      },
      {
        "id": 4,
        "name_en": "Year",
        "name_ar": "السنة",
        "type": "number",
        "order": 2
      },
      {
        "id": 5,
        "name_en": "Fuel Type",
        "name_ar": "نوع الوقود",
        "type": "select",
        "order": 3
      }
    ],
    "specifications_count": 4
  }
}
```

**Validation:**
- `specification_id` is required and must exist
- `order` is optional (defaults to current count)
- Returns 409 error if specification is already attached to the category

---

### 20.9 Remove Specification from Category (Admin)
**Description:** إزالة مواصفة من الفئة - يفصل المواصفة عن الفئة دون حذفها من النظام  
**Endpoint:** `DELETE /api/v1/admin/categories/{category_id}/specifications/{specification_id}`  
**Auth Required:** Yes (Admin)

```bash
curl -X DELETE http://localhost:8000/api/v1/admin/categories/1/specifications/3 \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Response:**
```json
{
  "status": "success",
  "message": "Specification detached from category successfully",
  "data": {
    "id": 1,
    "name_en": "Cars",
    "name_ar": "سيارات",
    "specifications": [
      {
        "id": 1,
        "name_en": "Brand",
        "name_ar": "الماركة",
        "type": "select",
        "order": 0
      },
      {
        "id": 4,
        "name_en": "Year",
        "name_ar": "السنة",
        "type": "number",
        "order": 2
      }
    ],
    "specifications_count": 2
  }
}
```

**Error Response (404):**
```json
{
  "status": "error",
  "code": 404,
  "message": "Specification not attached to this category",
  "errors": {}
}
```

---

## Category-Specification System Overview

### Database Structure:
A many-to-many relationship exists between `categories` and `specifications` through the `category_specification` pivot table.

**Pivot Table Fields:**
- `category_id` - Foreign key to categories
- `specification_id` - Foreign key to specifications
- `order` - Integer for ordering specifications (0-based)
- Unique constraint on (category_id, specification_id)

### Workflow Example:

**Step 1: Create Category**
```bash
POST /api/v1/admin/categories
{
  "name_en": "Luxury Cars",
  "name_ar": "سيارات فاخرة",
  "status": "active"
}
```

**Step 2: Assign Specifications** (e.g., Brand, Color, Year, Engine)
```bash
POST /api/v1/admin/categories/1/specifications/assign
{
  "specification_ids": [1, 3, 4, 8]
}
```

**Step 3: View Category with Specifications**
```bash
GET /api/v1/admin/categories/1
```
Response includes `specifications` array with all assigned specs in order.

### Use Cases:

1. **Replace All Specifications:** Use `/assign` endpoint
   - Example: Change from [1, 2, 3] to [1, 3, 4]

2. **Add One Specification:** Use `/attach` endpoint
   - Example: Add specification 5 to existing list

3. **Remove One Specification:** Use DELETE endpoint
   - Example: Remove specification 2 from category

4. **View Category Specifications:** GET category or specifications endpoint
   - Both return the same data

---

## 21. Sliders

### 21.1 List Sliders (Public)
**Description:** عرض السلايدرات النشطة  
**Endpoint:** `GET /api/v1/sliders`  
**Auth Required:** No

```bash
curl -X GET "http://localhost:8000/api/v1/sliders?category_id=1" \
  -H "Accept: application/json"
```

**Query Parameters:**
- `category_id`: Filter by category
- `include_inactive`: true (Admin only, with auth header)

---

### 21.2 Get Slider Details (Public)
**Description:** عرض تفاصيل سلايدر  
**Endpoint:** `GET /api/v1/sliders/{id}`  
**Auth Required:** No

```bash
curl -X GET http://localhost:8000/api/v1/sliders/1 \
  -H "Accept: application/json"
```

---

### 21.3 Create Slider (Admin)
**Description:** إنشاء سلايدر جديد  
**Endpoint:** `POST /api/v1/admin/sliders`  
**Auth Required:** Yes (Admin)

```bash
curl -X POST http://localhost:8000/api/v1/admin/sliders \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Homepage Banner",
    "image_id": 1,
    "category_id": 1,
    "value": "https://example.com/promo",
    "status": "active"
  }'
```

---

### 21.4 Update Slider (Admin)
**Description:** تعديل سلايدر  
**Endpoint:** `PUT /api/v1/admin/sliders/{id}`  
**Auth Required:** Yes (Admin)

```bash
curl -X PUT http://localhost:8000/api/v1/admin/sliders/1 \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Updated Banner"
  }'
```

---

### 21.5 Delete Slider (Admin)
**Description:** حذف سلايدر  
**Endpoint:** `DELETE /api/v1/admin/sliders/{id}`  
**Auth Required:** Yes (Admin)

```bash
curl -X DELETE http://localhost:8000/api/v1/admin/sliders/1 \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 21.6 Activate Slider (Admin)
**Description:** تفعيل سلايدر  
**Endpoint:** `POST /api/v1/admin/sliders/{id}/activate`  
**Auth Required:** Yes (Admin)

```bash
curl -X POST http://localhost:8000/api/v1/admin/sliders/1/activate \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 21.7 Deactivate Slider (Admin)
**Description:** إلغاء تفعيل سلايدر  
**Endpoint:** `POST /api/v1/admin/sliders/{id}/deactivate`  
**Auth Required:** Yes (Admin)

```bash
curl -X POST http://localhost:8000/api/v1/admin/sliders/1/deactivate \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

## 22. Seller Stats & Analytics

### 22.1 Seller Dashboard
**Description:** لوحة تحكم البائع  
**Endpoint:** `GET /api/v1/seller/dashboard`  
**Auth Required:** Yes

```bash
curl -X GET "http://localhost:8000/api/v1/seller/dashboard?date_from=2026-01-01&date_to=2026-01-31" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Response includes:**
- Total views, contacts, clicks
- Active ads count, draft ads count
- Top 5 performing ads

---

### 22.2 Total Views
**Description:** إجمالي المشاهدات  
**Endpoint:** `GET /api/v1/seller/stats/views`  
**Auth Required:** Yes

```bash
curl -X GET http://localhost:8000/api/v1/seller/stats/views \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 22.3 Total Contacts
**Description:** إجمالي التواصلات  
**Endpoint:** `GET /api/v1/seller/stats/contacts`  
**Auth Required:** Yes

```bash
curl -X GET http://localhost:8000/api/v1/seller/stats/contacts \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 22.4 Total Clicks
**Description:** إجمالي النقرات  
**Endpoint:** `GET /api/v1/seller/stats/clicks`  
**Auth Required:** Yes

```bash
curl -X GET http://localhost:8000/api/v1/seller/stats/clicks \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 22.5 Ad Views
**Description:** مشاهدات إعلان معين  
**Endpoint:** `GET /api/v1/seller/ads/{ad}/views`  
**Auth Required:** Yes (Ad Owner)

```bash
curl -X GET http://localhost:8000/api/v1/seller/ads/1/views \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 22.6 Ad Contacts
**Description:** تواصلات إعلان معين  
**Endpoint:** `GET /api/v1/seller/ads/{ad}/contacts`  
**Auth Required:** Yes (Ad Owner)

```bash
curl -X GET http://localhost:8000/api/v1/seller/ads/1/contacts \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 22.7 Ad Clicks
**Description:** نقرات إعلان معين  
**Endpoint:** `GET /api/v1/seller/ads/{ad}/clicks`  
**Auth Required:** Yes (Ad Owner)

```bash
curl -X GET http://localhost:8000/api/v1/seller/ads/1/clicks \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 22.8 Increment Ad Views
**Description:** زيادة عداد مشاهدات إعلان  
**Endpoint:** `POST /api/v1/seller/ads/{ad}/views`  
**Auth Required:** Yes

```bash
curl -X POST http://localhost:8000/api/v1/seller/ads/1/views \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Note:** View is not counted if the viewer is the ad owner.

---

### 22.9 Increment Ad Contacts
**Description:** زيادة عداد تواصلات إعلان  
**Endpoint:** `POST /api/v1/seller/ads/{ad}/contacts`  
**Auth Required:** Yes

```bash
curl -X POST http://localhost:8000/api/v1/seller/ads/1/contacts \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 22.10 Increment Ad Clicks
**Description:** زيادة عداد نقرات إعلان  
**Endpoint:** `POST /api/v1/seller/ads/{ad}/clicks`  
**Auth Required:** Yes

```bash
curl -X POST http://localhost:8000/api/v1/seller/ads/1/clicks \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

## 23. Admin Stats & Analytics

### 23.1 Admin Dashboard
**Description:** لوحة تحكم الأدمن العامة  
**Endpoint:** `GET /api/v1/admin/stats/dashboard`  
**Auth Required:** Yes (Admin)

```bash
curl -X GET http://localhost:8000/api/v1/admin/stats/dashboard \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Response includes:**
- Total users count
- Total ads count
- Total views and contacts
- Ads breakdown by type (normal, unique, caishha, auction)

---

### 23.2 Ad Views (Admin)
**Description:** مشاهدات أي إعلان  
**Endpoint:** `GET /api/v1/admin/stats/ads/{ad}/views`  
**Auth Required:** Yes (Admin)

```bash
curl -X GET http://localhost:8000/api/v1/admin/stats/ads/1/views \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 23.3 Ad Clicks (Admin)
**Description:** نقرات أي إعلان  
**Endpoint:** `GET /api/v1/admin/stats/ads/{ad}/clicks`  
**Auth Required:** Yes (Admin)

```bash
curl -X GET http://localhost:8000/api/v1/admin/stats/ads/1/clicks \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 23.4 Dealer Stats
**Description:** إحصائيات تاجر/معرض  
**Endpoint:** `GET /api/v1/admin/stats/dealer/{user}`  
**Auth Required:** Yes (Admin)

```bash
curl -X GET http://localhost:8000/api/v1/admin/stats/dealer/5 \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Response includes:**
- Total ads, active ads
- Total views, contacts
- Ads breakdown by type

---

### 23.5 User Stats
**Description:** إحصائيات مستخدم  
**Endpoint:** `GET /api/v1/admin/stats/user/{user}`  
**Auth Required:** Yes (Admin)

```bash
curl -X GET http://localhost:8000/api/v1/admin/stats/user/5 \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 23.6 Ads by Type
**Description:** عدد الإعلانات حسب النوع  
**Endpoint:** `GET /api/v1/admin/stats/ads/{type}`  
**Auth Required:** Yes (Admin)

```bash
curl -X GET http://localhost:8000/api/v1/admin/stats/ads/normal \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Types:** `normal`, `unique`, `caishha`, `auction`

---

## 24. Caishha Settings

### 24.1 Get Caishha Settings (Admin)
**Description:** عرض إعدادات كيشها  
**Endpoint:** `GET /api/v1/caishha-settings`  
**Auth Required:** Yes (Admin)

```bash
curl -X GET http://localhost:8000/api/v1/caishha-settings \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 24.2 Update All Settings (Admin)
**Description:** تحديث جميع إعدادات كيشها  
**Endpoint:** `PUT /api/v1/caishha-settings`  
**Auth Required:** Yes (Admin)

```bash
curl -X PUT http://localhost:8000/api/v1/caishha-settings \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "max_offers_per_ad": 10,
    "offer_expiry_days": 7
  }'
```

---

### 24.3 Update Single Setting (Admin)
**Description:** تحديث إعداد واحد  
**Endpoint:** `PUT /api/v1/caishha-settings/{key}`  
**Auth Required:** Yes (Admin)

```bash
curl -X PUT http://localhost:8000/api/v1/caishha-settings/max_offers_per_ad \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "value": 15
  }'
```

---

### 24.4 Get Setting Presets (Admin)
**Description:** عرض الإعدادات الافتراضية  
**Endpoint:** `GET /api/v1/caishha-settings/presets`  
**Auth Required:** Yes (Admin)

```bash
curl -X GET http://localhost:8000/api/v1/caishha-settings/presets \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

## 25. Page Content Management

### 25.1 Get All Page Contents (Admin)
**Description:** عرض جميع محتويات الصفحات (من نحن، سياسة الخصوصية، الشروط والأحكام)  
**Endpoint:** `GET /api/v1/admin/pages`  
**Auth Required:** Yes (Admin)

```bash
curl -X GET http://localhost:8000/api/v1/admin/pages \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Response:**
```json
{
  "status": "success",
  "message": "Page contents retrieved successfully",
  "data": [
    {
      "id": 1,
      "page_key": "about_us",
      "title_en": "About Us",
      "title_ar": "من نحن",
      "body_en": "Welcome to our platform. We are dedicated to providing the best service.",
      "body_ar": "مرحباً بكم في منصتنا. نحن ملتزمون بتقديم أفضل خدمة.",
      "created_at": "2026-02-07T19:57:47.000000Z",
      "updated_at": "2026-02-07T19:57:47.000000Z"
    },
    {
      "id": 2,
      "page_key": "privacy_policy",
      "title_en": "Privacy Policy",
      "title_ar": "سياسة الخصوصية",
      "body_en": "Your privacy is important to us...",
      "body_ar": "خصوصيتك مهمة بالنسبة لنا...",
      "created_at": "2026-02-07T19:57:47.000000Z",
      "updated_at": "2026-02-07T19:57:47.000000Z"
    },
    {
      "id": 3,
      "page_key": "terms_conditions",
      "title_en": "Terms and Conditions",
      "title_ar": "الشروط والأحكام",
      "body_en": "By using our platform, you agree to the following terms and conditions.",
      "body_ar": "باستخدامك لمنصتنا، فإنك توافق على الشروط والأحكام التالية.",
      "created_at": "2026-02-07T19:57:47.000000Z",
      "updated_at": "2026-02-07T19:57:47.000000Z"
    }
  ]
}
```

---

### 25.2 Get Single Page Content (Admin)
**Description:** عرض محتوى صفحة معينة للإدارة  
**Endpoint:** `GET /api/v1/admin/pages/{pageKey}`  
**Auth Required:** Yes (Admin)  
**Valid Page Keys:** `about_us`, `privacy_policy`, `terms_conditions`

```bash
curl -X GET http://localhost:8000/api/v1/admin/pages/about_us \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Response:**
```json
{
  "status": "success",
  "message": "Page content retrieved successfully",
  "data": {
    "id": 1,
    "page_key": "about_us",
    "title_en": "About Us",
    "title_ar": "من نحن",
    "body_en": "Welcome to our platform. We are dedicated to providing the best service.",
    "body_ar": "مرحباً بكم في منصتنا. نحن ملتزمون بتقديم أفضل خدمة.",
    "created_at": "2026-02-07T19:57:47.000000Z",
    "updated_at": "2026-02-07T19:57:47.000000Z"
  }
}
```

---

### 25.3 Update Page Content (Admin)
**Description:** تحديث محتوى صفحة معينة (العنوان والمحتوى بالعربية والإنجليزية)  
**Endpoint:** `PUT /api/v1/admin/pages/{pageKey}`  
**Auth Required:** Yes (Admin)

```bash
curl -X PUT http://localhost:8000/api/v1/admin/pages/about_us \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "title_en": "About Us - Updated",
    "title_ar": "من نحن - محدث",
    "body_en": "This is the updated English content for the About Us page.",
    "body_ar": "هذا هو المحتوى العربي المحدث لصفحة من نحن."
  }'
```

**Request Body Parameters:**
- `title_en` (optional): English title
- `title_ar` (optional): Arabic title
- `body_en` (optional): English body content
- `body_ar` (optional): Arabic body content

**Response:**
```json
{
  "status": "success",
  "message": "Page content updated successfully",
  "data": {
    "id": 1,
    "page_key": "about_us",
    "title_en": "About Us - Updated",
    "title_ar": "من نحن - محدث",
    "body_en": "This is the updated English content for the About Us page.",
    "body_ar": "هذا هو المحتوى العربي المحدث لصفحة من نحن.",
    "created_at": "2026-02-07T19:57:47.000000Z",
    "updated_at": "2026-02-07T20:15:30.000000Z"
  }
}
```

---

### 25.4 Get All Public Pages (Public)
**Description:** عرض جميع محتويات الصفحات (بدون مصادقة)  
**Endpoint:** `GET /api/v1/pages`  
**Auth Required:** No

```bash
curl -X GET http://localhost:8000/api/v1/pages \
  -H "Accept: application/json"
```

**Response:**
```json
{
  "status": "success",
  "message": "Page contents retrieved successfully",
  "data": {
    "about_us": {
      "id": 1,
      "page_key": "about_us",
      "title_en": "About Us",
      "title_ar": "من نحن",
      "body_en": "Welcome to our platform...",
      "body_ar": "مرحباً بكم في منصتنا..."
    },
    "privacy_policy": {
      "id": 2,
      "page_key": "privacy_policy",
      "title_en": "Privacy Policy",
      "title_ar": "سياسة الخصوصية",
      "body_en": "Your privacy is important...",
      "body_ar": "خصوصيتك مهمة..."
    },
    "terms_conditions": {
      "id": 3,
      "page_key": "terms_conditions",
      "title_en": "Terms and Conditions",
      "title_ar": "الشروط والأحكام",
      "body_en": "By using our platform...",
      "body_ar": "باستخدامك لمنصتنا..."
    }
  }
}
```

---

### 25.5 Get Single Public Page (Public)
**Description:** عرض محتوى صفحة معينة (بدون مصادقة)  
**Endpoint:** `GET /api/v1/pages/{pageKey}`  
**Auth Required:** No  
**Valid Page Keys:** `about_us`, `privacy_policy`, `terms_conditions`

```bash
curl -X GET http://localhost:8000/api/v1/pages/privacy_policy \
  -H "Accept: application/json"
```

**Response:**
```json
{
  "status": "success",
  "message": "Page content retrieved successfully",
  "data": {
    "page_key": "privacy_policy",
    "title_en": "Privacy Policy",
    "title_ar": "سياسة الخصوصية",
    "body_en": "Your privacy is important to us. This policy describes how we collect, use, and protect your data.",
    "body_ar": "خصوصيتك مهمة بالنسبة لنا. توضح هذه السياسة كيف نجمع بياناتك ونستخدمها ونحميها."
  }
}
```

---

## 26. Company Settings

### 26.1 Get All Company Settings (Admin)
**Description:** عرض جميع إعدادات الشركة (معلومات الاتصال، روابط وسائل التواصل، روابط التطبيقات)  
**Endpoint:** `GET /api/v1/admin/company-settings`  
**Auth Required:** Yes (Admin)

```bash
curl -X GET http://localhost:8000/api/v1/admin/company-settings \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Response:**
```json
{
  "status": "success",
  "message": "Company settings retrieved successfully",
  "data": {
    "contact": {
      "phone": {
        "key": "phone",
        "value": "+1234567890",
        "is_active": true,
        "description": "Company phone number"
      },
      "email": {
        "key": "email",
        "value": "contact@company.com",
        "is_active": true,
        "description": "Company email address"
      },
      "location": {
        "key": "location",
        "value": "123 Main St, City, Country",
        "is_active": true,
        "description": "Company physical location/address"
      }
    },
    "social_media": {
      "facebook_link": {
        "key": "facebook_link",
        "value": "https://facebook.com/yourpage",
        "is_active": true,
        "description": "Facebook page URL"
      },
      "instagram_link": {
        "key": "instagram_link",
        "value": "https://instagram.com/yourpage",
        "is_active": true,
        "description": "Instagram profile URL"
      },
      "twitter_link": {
        "key": "twitter_link",
        "value": "https://twitter.com/yourpage",
        "is_active": false,
        "description": "Twitter/X profile URL"
      },
      "youtube_link": {
        "key": "youtube_link",
        "value": "https://youtube.com/@yourpage",
        "is_active": true,
        "description": "YouTube channel URL"
      },
      "telegram_link": {
        "key": "telegram_link",
        "value": "",
        "is_active": false,
        "description": "Telegram channel/group URL"
      },
      "whatsapp_link": {
        "key": "whatsapp_link",
        "value": "https://wa.me/1234567890",
        "is_active": true,
        "description": "WhatsApp contact link"
      },
      "tiktok_link": {
        "key": "tiktok_link",
        "value": "",
        "is_active": false,
        "description": "TikTok profile URL"
      }
    },
    "app_link": {
      "android_app_link": {
        "key": "android_app_link",
        "value": "https://play.google.com/store/apps/details?id=com.yourapp",
        "is_active": true,
        "description": "Android app download link (Google Play Store)"
      },
      "ios_app_link": {
        "key": "ios_app_link",
        "value": "https://apps.apple.com/app/yourapp/id123456789",
        "is_active": true,
        "description": "iOS app download link (Apple App Store)"
      }
    }
  }
}
```

---

### 26.2 Get Company Settings by Type (Admin)
**Description:** عرض إعدادات الشركة حسب النوع  
**Endpoint:** `GET /api/v1/admin/company-settings/type/{type}`  
**Auth Required:** Yes (Admin)  
**Valid Types:** `contact`, `social_media`, `app_link`

```bash
curl -X GET http://localhost:8000/api/v1/admin/company-settings/type/social_media \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Response:**
```json
{
  "status": "success",
  "message": "Company social_media settings retrieved successfully",
  "data": [
    {
      "id": 4,
      "key": "facebook_link",
      "value": "https://facebook.com/yourpage",
      "is_active": true,
      "type": "social_media",
      "description": "Facebook page URL",
      "created_at": "2026-02-07T19:57:47.000000Z",
      "updated_at": "2026-02-07T19:57:47.000000Z"
    },
    {
      "id": 5,
      "key": "instagram_link",
      "value": "https://instagram.com/yourpage",
      "is_active": true,
      "type": "social_media",
      "description": "Instagram profile URL",
      "created_at": "2026-02-07T19:57:47.000000Z",
      "updated_at": "2026-02-07T19:57:47.000000Z"
    }
  ]
}
```

---

### 26.3 Update Single Company Setting (Admin)
**Description:** تحديث إعداد واحد من إعدادات الشركة  
**Endpoint:** `PUT /api/v1/admin/company-settings/{key}`  
**Auth Required:** Yes (Admin)  
**Valid Keys:** `phone`, `email`, `location`, `facebook_link`, `instagram_link`, `twitter_link`, `youtube_link`, `telegram_link`, `whatsapp_link`, `tiktok_link`, `android_app_link`, `ios_app_link`

```bash
curl -X PUT http://localhost:8000/api/v1/admin/company-settings/email \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "value": "support@company.com",
    "is_active": true
  }'
```

**Request Body Parameters:**
- `value` (optional): New value for the setting
- `is_active` (optional): Active status (true/false)

**Response:**
```json
{
  "status": "success",
  "message": "Setting updated successfully",
  "data": {
    "id": 2,
    "key": "email",
    "value": "support@company.com",
    "is_active": true,
    "type": "contact",
    "description": "Company email address",
    "created_at": "2026-02-07T19:57:47.000000Z",
    "updated_at": "2026-02-07T20:30:15.000000Z"
  }
}
```

---

### 26.4 Bulk Update Company Settings (Admin)
**Description:** تحديث عدة إعدادات دفعة واحدة  
**Endpoint:** `PUT /api/v1/admin/company-settings`  
**Auth Required:** Yes (Admin)

```bash
curl -X PUT http://localhost:8000/api/v1/admin/company-settings \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "settings": [
      {
        "key": "phone",
        "value": "+966123456789",
        "is_active": true
      },
      {
        "key": "facebook_link",
        "value": "https://facebook.com/newpage",
        "is_active": true
      },
      {
        "key": "telegram_link",
        "value": "https://t.me/yourgroup",
        "is_active": true
      }
    ]
  }'
```

**Request Body:**
- `settings` (required): Array of settings to update
  - `key` (required): Setting key
  - `value` (optional): New value
  - `is_active` (optional): Active status

**Response:**
```json
{
  "status": "success",
  "message": "All settings updated successfully",
  "data": {
    "updated_count": 3,
    "updated_keys": [
      "phone",
      "facebook_link",
      "telegram_link"
    ]
  }
}
```

**Partial Success Response:**
```json
{
  "status": "partial",
  "message": "Some settings could not be updated",
  "data": {
    "updated": ["phone", "facebook_link"],
    "errors": {
      "telegram_link": "Invalid URL format"
    }
  }
}
```

---

### 26.5 Toggle Active Status (Admin)
**Description:** تبديل حالة التفعيل لإعداد معين (تفعيل/تعطيل)  
**Endpoint:** `POST /api/v1/admin/company-settings/{key}/toggle-active`  
**Auth Required:** Yes (Admin)

```bash
curl -X POST http://localhost:8000/api/v1/admin/company-settings/facebook_link/toggle-active \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Response:**
```json
{
  "status": "success",
  "message": "Setting active status toggled successfully",
  "data": {
    "id": 4,
    "key": "facebook_link",
    "value": "https://facebook.com/yourpage",
    "is_active": false,
    "type": "social_media",
    "description": "Facebook page URL",
    "created_at": "2026-02-07T19:57:47.000000Z",
    "updated_at": "2026-02-07T20:45:20.000000Z"
  }
}
```

---

### 26.6 Get Active Company Info (Public)
**Description:** عرض معلومات الشركة المفعلة فقط (بدون مصادقة)  
**Endpoint:** `GET /api/v1/company-info`  
**Auth Required:** No

```bash
curl -X GET http://localhost:8000/api/v1/company-info \
  -H "Accept: application/json"
```

**Response:**
```json
{
  "status": "success",
  "message": "Company information retrieved successfully",
  "data": {
    "contact": {
      "phone": {
        "key": "phone",
        "value": "+966123456789"
      },
      "email": {
        "key": "email",
        "value": "support@company.com"
      },
      "location": {
        "key": "location",
        "value": "123 Main St, City, Country"
      }
    },
    "social_media": {
      "facebook_link": {
        "key": "facebook_link",
        "value": "https://facebook.com/yourpage"
      },
      "instagram_link": {
        "key": "instagram_link",
        "value": "https://instagram.com/yourpage"
      },
      "youtube_link": {
        "key": "youtube_link",
        "value": "https://youtube.com/@yourpage"
      },
      "whatsapp_link": {
        "key": "whatsapp_link",
        "value": "https://wa.me/1234567890"
      }
    },
    "app_link": {
      "android_app_link": {
        "key": "android_app_link",
        "value": "https://play.google.com/store/apps/details?id=com.yourapp"
      },
      "ios_app_link": {
        "key": "ios_app_link",
        "value": "https://apps.apple.com/app/yourapp/id123456789"
      }
    }
  }
}
```

**Note:** This endpoint only returns settings where `is_active = true`.

---

## Common Response Formats

### Success Response
```json
{
  "status": "success",
  "message": "Operation successful",
  "data": { ... }
}
```

### Paginated Response
```json
{
  "status": "success",
  "message": "Data retrieved successfully",
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "last_page": 10,
    "per_page": 15,
    "total": 150
  },
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  }
}
```

### Error Response
```json
{
  "status": "error",
  "code": 422,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

---

## HTTP Status Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | Created |
| 204 | No Content |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 422 | Validation Error |
| 429 | Too Many Requests |
| 500 | Server Error |

---

## 27. Admin Audit Logs

### 27.1 List Audit Logs
**Description:** عرض سجلات التدقيق الإداري مع الفلترة والبحث  
**Endpoint:** `GET /api/v1/admin/audit-logs`  
**Auth Required:** Yes (Admin or Super Admin only)

```bash
curl -X GET "http://localhost:8000/api/v1/admin/audit-logs?start_date=2026-02-01&severity=warning&per_page=50" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Query Parameters (all optional):**
- `start_date` - Filter from date (ISO 8601 format)
- `end_date` - Filter until date (ISO 8601 format)
- `actor_id` - Filter by user ID who performed the action
- `actor_role` - Filter by role (admin, super_admin)
- `action_type` - Filter by action (e.g., user.created, package.updated)
- `resource_type` - Filter by resource type (User, Package, Ad)
- `resource_id` - Filter by specific resource ID
- `severity` - Minimum severity level (debug, info, notice, warning, error, critical, alert, emergency)
- `correlation_id` - Filter by correlation ID (trace related events)
- `page` - Page number (default: 1)
- `per_page` - Results per page (default: 50, max: 500)
- `sort` - Sort field (default: timestamp)
- `sort_direction` - Sort order (asc/desc, default: desc)
- `format` - Response format (json/csv, default: json)

**Response:**
```json
{
  "success": true,
  "message": "Audit logs retrieved successfully",
  "data": [
    {
      "id": 1,
      "timestamp": "2026-02-09T10:30:00Z",
      "actor_id": 5,
      "actor_name": "John Admin",
      "actor_role": "admin",
      "action_type": "user.deleted",
      "resource_type": "User",
      "resource_id": "123",
      "ip_address": "192.168.1.10",
      "user_agent": "Mozilla/5.0...",
      "correlation_id": "550e8400-e29b-41d4-a716-446655440000",
      "severity": "warning",
      "details": {
        "deleted_user": {
          "email": "user@example.com",
          "name": "John Doe"
        }
      },
      "actor": {
        "id": 5,
        "name": "John Admin",
        "email": "admin@example.com"
      }
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 50,
    "total": 150,
    "last_page": 3,
    "from": 1,
    "to": 50
  }
}
```

---

### 27.2 Export Audit Logs as CSV
**Description:** تصدير سجلات التدقيق كملف CSV  
**Endpoint:** `GET /api/v1/admin/audit-logs?format=csv`  
**Auth Required:** Yes (Admin or Super Admin only)

```bash
curl -X GET "http://localhost:8000/api/v1/admin/audit-logs?format=csv&start_date=2026-02-01&severity=warning" \
  -H "Authorization: Bearer {token}" \
  -o audit_logs.csv
```

**Note:** This endpoint streams a CSV file for download. Use the same query parameters as the list endpoint.

---

### 27.3 Get Audit Log Statistics
**Description:** عرض إحصائيات سجلات التدقيق  
**Endpoint:** `GET /api/v1/admin/audit-logs/stats`  
**Auth Required:** Yes (Admin or Super Admin only)

```bash
curl -X GET http://localhost:8000/api/v1/admin/audit-logs/stats \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Response:**
```json
{
  "success": true,
  "message": "Audit statistics retrieved successfully",
  "data": {
    "total_logs": 1523,
    "logs_today": 45,
    "logs_this_week": 312,
    "logs_this_month": 1200,
    "critical_logs": 15,
    "by_action_type": {
      "user.updated": 450,
      "package.assigned": 320,
      "user.created": 280
    },
    "by_severity": {
      "info": 1200,
      "notice": 150,
      "warning": 200,
      "error": 100,
      "critical": 15
    },
    "top_actors": [
      {
        "actor_id": 5,
        "actor_name": "John Admin",
        "count": 523
      }
    ]
  }
}
```

---

### 27.4 Get Single Audit Log Entry
**Description:** عرض تفاصيل سجل تدقيق محدد  
**Endpoint:** `GET /api/v1/admin/audit-logs/{id}`  
**Auth Required:** Yes (Admin or Super Admin only)

```bash
curl -X GET http://localhost:8000/api/v1/admin/audit-logs/1 \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Response:**
```json
{
  "success": true,
  "message": "Audit log retrieved successfully",
  "data": {
    "id": 1,
    "timestamp": "2026-02-09T10:30:00Z",
    "actor_id": 5,
    "actor_name": "John Admin",
    "actor_role": "admin",
    "action_type": "user.deleted",
    "resource_type": "User",
    "resource_id": "123",
    "ip_address": "192.168.1.10",
    "user_agent": "Mozilla/5.0...",
    "correlation_id": "550e8400-e29b-41d4-a716-446655440000",
    "severity": "warning",
    "details": {
      "deleted_user": {
        "email": "user@example.com",
        "name": "John Doe",
        "roles": ["user"]
      },
      "deleted_by": {
        "id": 5,
        "name": "John Admin"
      }
    },
    "actor": {
      "id": 5,
      "name": "John Admin",
      "email": "admin@example.com"
    }
  }
}
```

---

### Common Action Types

| Action Type | Description |
|-------------|-------------|
| `user.created` | User account created |
| `user.updated` | User account updated |
| `user.deleted` | User account deleted |
| `user.verification_approved` | User verification approved |
| `user.verification_rejected` | User verification rejected |
| `package.created` | Package created |
| `package.updated` | Package updated |
| `package.deleted` | Package deleted |
| `package.assigned` | Package assigned to user |
| `package.revoked` | Package revoked from user |
| `ad.published` | Ad published |
| `ad.unpublished` | Ad unpublished |
| `ad.deleted` | Ad deleted |
| `system.config_changed` | System configuration changed |
| `system.error.*` | System errors |

### Severity Levels

| Level | Description | Use Case |
|-------|-------------|----------|
| `debug` | Diagnostic information | Detailed debugging |
| `info` | Normal operations | Standard actions |
| `notice` | Important events | Package assignments, verifications |
| `warning` | Destructive actions | Deletions, status changes |
| `error` | Errors | Non-critical failures |
| `critical` | Critical errors | Requires investigation |
| `alert` | Security events | Immediate attention needed |
| `emergency` | System failures | System-wide issues |

---

## HTTP Status Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | Created |
| 204 | No Content |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 422 | Validation Error |
| 429 | Too Many Requests |
| 500 | Server Error |

---

## Rate Limiting

Some endpoints have rate limiting:
- Reviews: 5 per minute
- Reports: 3 per minute
- Login: 5 attempts per minute

---

## Notes

1. Replace `{token}` with your actual Bearer token
2. Replace `http://localhost:8000` with your actual API URL
3. All dates are in ISO 8601 format (e.g., `2026-02-01T10:00:00Z`)
4. IDs in curly braces `{id}` should be replaced with actual numeric IDs
5. Query parameters are optional unless otherwise specified
