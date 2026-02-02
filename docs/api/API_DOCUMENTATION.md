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
