# API Catalog

This document lists the platform APIs (Admin, User, Seller, Marketer, Public) with HTTP methods, routes and short Arabic descriptions.

---

## Admin APIs

### 1. Authentication / Admin Account
- Admin Login — POST /api/admin/auth/login — تسجيل دخول الادمن
- Admin Logout — POST /api/admin/auth/logout — تسجيل خروج الادمن
- Change Password — PUT /api/admin/auth/password — تغيير كلمة مرور الادمن
- Profile — GET /api/admin/profile — جلب بيانات الحساب الشخصي للادمن
- Update Profile — PUT /api/admin/profile — تعديل بيانات الحساب الشخصي للادمن

### 2. User Management (All Users)
- List Users — GET /api/admin/users — جلب كل المستخدمين
- Get User Details — GET /api/admin/users/{id} — جلب بيانات مستخدم محدد
- Create User — POST /api/admin/users — إنشاء مستخدم جديد
- Update User — PUT /api/admin/users/{id} — تعديل بيانات مستخدم
- Delete User — DELETE /api/admin/users/{id} — حذف مستخدم
- Assign Role — POST /api/admin/users/{id}/role — تعيين أو تغيير Role لمستخدم
- Verify Seller / Showroom — POST /api/admin/users/{id}/verify — تفعيل / تحقق من تاجر أو معرض

### 3. Ads Management (All Types)
- Create Ad — POST /api/admin/ads — إنشاء إعلان عام (all ad types)
- List All Ads — GET /api/admin/ads — عرض كل الإعلانات (Normal, Caishha, FindIt, Auction)
- Get Ad Details — GET /api/admin/ads/{id} — تفاصيل الإعلان
- Update Ad — PUT /api/admin/ads/{id} — تعديل الإعلان
- Delete Ad — DELETE /api/admin/ads/{id} — حذف الإعلان
- Auto Republish Ad — POST /api/admin/ads/{id}/auto-republish — تفعيل النشر التلقائي
- Push Ad to Facebook — POST /api/admin/ads/{id}/push-fb — نشر الإعلان على Facebook

### 4. Unique Ads (Admin)
- Create Unique Ad — POST /api/admin/unique_ad
- List Unique Ads — GET /api/admin/unique_ad
- Get Unique Ad — GET /api/admin/unique_ad/{id}
- Update Unique Ad — PUT /api/admin/unique_ad/{id}
- Delete Unique Ad — DELETE /api/admin/unique_ad/{id}
- Promote/Feature Unique Ad — POST /api/admin/unique_ad/{id} — جعل الإعلان مميز / سوبر مميز
- Auto Republish Unique Ad — POST /api/admin/unique_ad/{id}/auto-republish
- Push Unique Ad to Facebook — POST /api/admin/unique_ad/{id}/push-fb
- Approve Ad Verification Request — POST /api/admin/unique_ad/verify

### 5. Caishha (Admin)
- Create Caishha — POST /api/admin/caishha — انشاء اعلان كيشها
- List Caishha Ads — GET /api/admin/caishha — عرض الكل
- Get Caishha Details — GET /api/admin/caishha/{id} — عرض تفاصيل اعلان معين
- Edit Caishha — PUT /api/admin/caishha/{id} — تعديل كيشها
- Delete Caishha — DELETE /api/admin/caishha/{id} — حذف إعلان كيشها
- List Offers — GET /api/admin/caishha/{id}/offers — عرض كل العروض على إعلان كيشها

### 6. FindIt (Admin)
- Create FindIt — POST /api/admin/findit — إنشاء طلب لاقيها (or POST /api/admin/findit/{id} as per flow)
- List FindIt — GET /api/admin/findit — عرض الكل
- Get FindIt — GET /api/admin/findit/{id}
- Update FindIt — PUT /api/admin/findit/{id}
- Delete FindIt — DELETE /api/admin/findit/{id}

### 7. Auction (Admin)
- Create Auction — POST /api/admin/auction
- Edit Auction — PUT /api/admin/auction/{id}
- List Auctions — GET /api/admin/auction
- Get Auction Details — GET /api/admin/auction/{id}
- Close Auction — POST /api/admin/auction/{id}/close — إغلاق المزاد يدوياً أو تلقائياً
- Auction Offers — GET /api/admin/auction/{id}/offers — عرض كل عروض المزاد
- Delete Auction — DELETE /api/admin/auction/{id}
- Delete Auction Offer — DELETE /api/admin/offers/{id}

### 8. Brands & Cars Management
- List Brands — GET /api/admin/cars/brands
- Create Brand — POST /api/admin/cars/brands
- Update Brand — PUT /api/admin/cars/brands/{id}
- Delete Brand — DELETE /api/admin/cars/brands/{id}
- List Models — GET /api/admin/cars/models
- Create Model — POST /api/admin/cars/models
- Update Model — PUT /api/admin/cars/models/{id}
- Delete Model — DELETE /api/admin/cars/models/{id}

### 9. Reports & Reviews Management
- List Reports — GET /api/admin/reports
- Get Report Details — GET /api/admin/reports/{id}
- Delete Report — DELETE /api/admin/reports/{id}
- List Reviews — GET /api/admin/reviews
- Get Review Details — GET /api/admin/reviews/{id}
- Delete Review — DELETE /api/admin/reviews/{id}

### 10. Subscriptions & Features Management
- List Subscriptions — GET /api/admin/subscriptions
- Create Subscription — POST /api/admin/subscriptions
- Update Subscription — PUT /api/admin/subscriptions/{id}
- Delete Subscription — DELETE /api/admin/subscriptions/{id}
- Approve Subscription Request — POST /api/admin/subscription/{request_id}
- Assign Subscription — POST /api/admin/subscriptions/{id}/assign
- Expire Subscription for user — PUT /api/admin/subscription/{id}/expire
- List Features — GET /api/admin/features
- Assign Feature — POST /api/admin/features/{id}/assign

### 11. Notifications Management
- List Notifications — GET /api/admin/notifications
- Edit Notification — PUT /api/admin/notifications/{id}
- Delete Notification — DELETE /api/admin/notifications/{id}
- Send Notification — POST /api/admin/notifications/send — إرسال إشعار لمستخدم أو مجموعة
- Push Ad Notification — POST /api/admin/notifications/ad/{ad_id}/send — إرسال إشعار عند إضافة / تعديل إعلان

### 12. Analytics / Stats
- Ad Views Count — GET /api/admin/stats/ads/{id}/views
- Ad Clicks Count — GET /api/admin/stats/ads/{id}/clicks
- Dealer Stats — GET /api/admin/stats/dealer/{id}
- User Stats — GET /api/admin/stats/user/{id}
- Number of Each Ad Type — GET /api/admin/stats/ads/{type}

### 13. Blogs Management (Admin)
- Create Blog — POST /api/admin/blogs
- List Blogs — GET /api/admin/blogs
- Get Blog — GET /api/admin/blogs/{id}
- Update Blog — PUT /api/admin/blogs/{id}
- Delete Blog — DELETE /api/admin/blogs/{id}
- Publish Blog — POST /api/admin/blogs/{id}/publish
- Unpublish Blog — POST /api/admin/blogs/{id}/unpublish
- Upload Blog Image — POST /api/admin/blogs/{id}/image

### 14. Specifications (المواصفات) & Categories
- List Specifications — GET /api/admin/specifications
- Create Specification — POST /api/admin/specifications
- Get Specification — GET /api/admin/specifications/{id}
- Update Specification — PUT /api/admin/specifications/{id}
- Delete Specification — DELETE /api/admin/specifications/{id}

- List Categories — GET /api/admin/categories
- Create Category — POST /api/admin/categories
- Get Category — GET /api/admin/categories/{id}
- Update Category — PUT /api/admin/categories/{id}
- Delete Category — DELETE /api/admin/categories/{id}

### 15. Sliders Management (Admin)
- List Sliders — GET /api/admin/sliders
- Get Slider — GET /api/admin/sliders/{id}
- Create Slider — POST /api/admin/sliders
- Update Slider — PUT /api/admin/sliders/{id}
- Delete Slider — DELETE /api/admin/sliders/{id}
- Activate Slider — POST /api/admin/sliders/{id}/activate
- Deactivate Slider — POST /api/admin/sliders/{id}/deactivate
- Change Order — PUT /api/admin/sliders/{id}/order
- Upload Slider Image — POST /api/admin/sliders/{id}/image

---

## User APIs

### Auth / User Account
- Login — POST /api/user/auth/login
- Logout — POST /api/user/auth/logout
- Register Account — POST /api/user/auth/register
- OTP Verification — PUT /api/user/verification
- Reset Password — PUT /api/user/reset
- Profile — GET /api/user/profile
- Edit Profile — PUT /api/user/profile

### Normal Ads (User)
- Create Ad — POST /api/user/normal_ad
- Update Ad — PUT /api/user/normal_ad/{id}
- Delete Ad — DELETE /api/user/normal_ad/{id}
- List Ads — GET /api/user/normal_ad
- Show Ad Details — GET /api/user/normal_ad/{id}

### Unique Ads (User)
- Create Ad — POST /api/user/unique_ad
- Update Ad — PUT /api/user/unique_ad/{id}
- Delete Ad — DELETE /api/user/unique_ad/{id}
- List Ads — GET /api/user/unique_ad
- Show Ad Details — GET /api/user/unique_ad/{id}
- Request Ad Verification — POST /api/user/unique_ad/verify

### Caishha Ads (User)
- Create Ad — POST /api/user/caishha
- Update Ad — PUT /api/user/caishha/{id}
- Delete Ad — DELETE /api/user/caishha/{id}
- List Ads — GET /api/user/caishha
- Show Ad Details — GET /api/user/caishha/{id}
- Create Offer — POST /api/user/caishha/{id}/offers
- Update Offer — PUT /api/user/caishha/offers/{id}
- Delete Offer — DELETE /api/user/caishha/offers/{id}
- List Offers — GET /api/user/caishha/{id}/offers
- Show Offer Details — GET /api/user/caishha/offers/{id}

### Find It Ads (User)
- Create — POST /api/user/findit
- Update — PUT /api/user/findit/{id}
- Delete — DELETE /api/user/findit/{id}
- List — GET /api/user/findit
- Details — GET /api/user/findit/{id}
- Notify on Similar Ads — GET /api/user/findit/{id}/similar

### Auction Ads (User)
- Create — POST /api/user/auction
- Delete — DELETE /api/user/auction/{id}
- List — GET /api/user/auction
- Details — GET /api/user/auction/{id}
- Create Offer (Bid) — POST /api/user/auction/{id}/offers
- Delete Offer — DELETE /api/user/auction/offers/{id}

### Reports & Reviews (User)
- Create Report — POST /api/user/reports
- Delete Report — DELETE /api/user/reports/{id}
- Create Review — POST /api/user/reviews
- Update Review — PUT /api/user/reviews/{id}
- Delete Review — DELETE /api/user/reviews/{id}

Note: include `user_id` or `ad_id` when creating/deleting reports or reviews.

### Favorites (User)
- Assign Fav — POST /api/user/fav-ads/{ad-id}
- List Favs — GET /api/user/fav-ads
- Remove Fav — DELETE /api/user/fav-ads/{fav-id}

### Notifications (User)
- List — GET /api/user/notifications
- Show — GET /api/user/notifications/{id}
- Mark As Read — PUT /api/user/notifications/{id}
- Hide Notification — PUT /api/user/notifications/{id}/hide

### Save Search
- Create — POST /api/user/save-search
- Update — PUT /api/user/save-search/{id}
- Delete — DELETE /api/user/save-search/{id}
- List — GET /api/user/save-search
- Show — GET /api/user/save-search/{id}

### Subscriptions (User)
- Request a Subscription — POST /api/user/subs/{sub-id}
- List Available Subs — GET /api/user/subs
- Sub Details — GET /api/user/subs/{id}
- My Sub — GET /api/user/my-sub
- Request Renew — POST /api/user/subs/request
- Request Stats — GET /api/user/subs/request/{id}

---

## Seller / Showroom APIs
- Inherit all normal User APIs.

### Verification & Badge
- Request Verification — POST /api/seller/verify/request — طلب توثيق الحساب
- Get Verification Status — GET /api/seller/verify/status
- Badge Create — POST /api/seller/badge
- Badge Update — PUT /api/seller/badge
- Get Badge Info — GET /api/seller/badge

### Views & Analytics
- Total Views — GET /api/seller/stats/views
- Ad Views — GET /api/seller/ads/{id}/views

### Contacts
- Total Contacts — GET /api/seller/stats/contacts
- Ad Contacts — GET /api/seller/ads/{id}/contacts
- Increment View Count — POST /api/seller/ads/{id}/views
- Increment Contact Count — POST /api/seller/ads/{id}/contacts

### Link Clicks
- Total Link Clicks — GET /api/seller/stats/clicks
- Ad Link Clicks — GET /api/seller/ads/{id}/clicks
- Increment Click — POST /api/seller/ads/{id}/clicks

### Dashboard
- Dashboard Stats — GET /api/seller/dashboard — ملخص شامل (Views – Contacts – Ads – Clicks)

---

## Marketer APIs
- Inherit all normal User APIs.
- Additional: send extra attributes on ads (marker flag), endpoints as per seller APIs but with marketer-specific flags for offered/requested ads.

---

## Public Blogs & Blog Categories
- List Published Blogs — GET /api/blogs
- Get Blog Details — GET /api/blogs/{id}
- List by Category — GET /api/blogs/category/{id}
- Search Blogs — GET /api/blogs/search
- Latest Blogs — GET /api/blogs/latest

Admin Blogs endpoints are listed in Admin section.

---

## Notes and Next Steps
- This catalog is a canonical list — implement request/response schemas, authentication (sanctum/passport), validation rules, and error codes next.
- I can convert this into OpenAPI (Swagger) spec for the backend. Would you like that generated now?
