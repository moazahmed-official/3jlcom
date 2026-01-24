# Software Entity Listing

This document defines the primary software entities, their fields, types, functions and high-level relations for the Smart Car Ads Platform.

Note on notation:
- Attribute = stored data field
- Function = system action / behaviour
- Entity = top-level object persisted in DB
- Relations are shown as 1->M, M->M, etc.

## Users
- Name: `users` (Entity)
	- Attributes: `id`, `full_name`, `password_hash`, `mobile_number`, `email`, `profile_image`, `country_id`, `city_id`, `role_id`, `verification_otp`, `is_verified`, `created_at`, `updated_at`
	- Functions: `register()`, `login()`, `verifyOTP()`, `check_token()`, `save_search()`, `subscribe()`
	- Relations: `role` (1->1), `ads` (1->M), `favorite_cars` (M->M with `cars`), `subscriptions` (M->M with `subscriptions`), `notifications` (1->M), `reviews` (1->M), `reports` (1->M)

## Admin
- Name: `admins` (Entity)
	- Attributes: `id`, `name`, `password_hash`, `mobile_number`, `email`, `profile_image`, `role_level`, `created_at`, `updated_at`
	- Functions: `manageUsers()`, `manageAds()`, `manageSubscriptions()`, `manageSellers()`, `manageMarketers()`, `manageFields()`, `manageCharacteristics()`, `manageNotifications()`, `manageSystemSettings()`, `login()`

## Roles
- Name: `roles` (Entity)
	- Attributes: `id`, `name` (e.g., Normal user, Seller/Showroom, Marketer, Moderator, CountryManager, SuperAdmin), `permissions` (JSON)

## Marketer
- Name: `marketers` (Entity / Role capabilities)
	- Functions/Features: `offerCarForSale()`, `showMatchingFindItAds()`, `createSimilarAdFromFindIt()`, `showSimilarAds()`, `autoCompareOffers()`

## Seller / Showroom
- Name: `sellers` (Entity)
	- Attributes: `id`, `company_name`, `verified` (bool), `badge` (enum), `profile_image`, `contact_count`, `views_count`, `link_clicks_count`, `created_at`, `updated_at`
	- Functions: `bulkUploadAds()`, `requestBadge()`, `viewReports()`

## Ads (Generic)
- Name: `ads` (Entity - base table for all ad types)
	- Common Attributes: `id`, `seller_id` (user or dealer), `title`, `description`, `category_id`, `city_id`, `country_id`, `status` (draft/pending/published/expired/removed), `views_count`, `contact_phone`, `whatsapp_number`, `media_count`, `period_days`, `is_pushed_facebook` (bool), `created_at`, `updated_at`
	- Functions: `publish()`, `archive()`, `autoRepublish()`

### Normal Ads
- Stored as `ads` with `type = 'normal'`
	- Additional Attributes: `price_cash` (nullable), `installment_id` (nullable, FK), `start_time`, `update_time`

### Unique Ads
- Stored as `ads` with `type = 'unique'`
	- Additional Attributes: `banner_image`, `banner_color`, `is_auto_republished` (bool), `is_verified_ad` (bool)

### Caishha Ads (كيشها)
- Stored as `ads` with `type = 'caishha'`
	- Attributes: same car details except price, `offers_window_period` (seconds/days), `offers_count`, `sellers_visibility_period`
	- Relations: `offers` (1->M to `caishha_offers`)

### FindIt Ads (لاقيها)
- Stored as `findit_requests` (separate entity)
	- Attributes: `id`, `requester_id`, `brand_id`, `model_id`, `min_price`, `max_price`, `min_year`, `max_year`, `city_id`, `country_id`, `created_at`, `updated_at`
	- Functions: `notifyMatchingDealers()`, `showSimilarCars()`

### Auction Ads
- Stored as `auctions` (Entity)
	- Attributes: `id`, `ad_id` (FK to `ads` base), `start_price`, `last_price`, `start_time`, `end_time`, `auto_close` (bool), `winner_user_id` (nullable), `is_last_price_visible` (bool)
	- Relations: `bids` (1->M), `offers` (if needed)
	- Functions: `placeBid()`, `autoClose()`, `hideLastPrice()`

## Cars (Car Details)
- Name: `cars` (Entity)
	- Attributes: `id`, `brand_id`, `model_id`, `year`, `color`, `body_type`, `fuel_type`, `owners_count`, `price`, `is_customs_cleared` (مجمركة, default true), `battery_range`, `battery_capacity`, `address`, `created_at`, `updated_at`
	- Relations: `ads` (1->M), `brand` (FK), `model` (FK)

## Installments
- Name: `installments` (Entity)
	- Attributes: `id`, `original_price`, `fees`, `deposit_amount`, `installment_amount`, `period_months`, `apr`, `created_at`, `updated_at`

## Reports
- Name: `reports` (Entity)
	- Attributes: `id`, `title`, `reason`, `reported_by_user_id`, `target_type` (ad/user/dealer), `target_id`, `status`, `created_at`, `updated_at`

## Reviews
- Name: `reviews` (Entity)
	- Attributes: `id`, `title`, `body`, `stars` (1-5), `user_id`, `seller_id` (or ad_id), `created_at`, `updated_at`

## Caishha Offers
- Name: `caishha_offers` (Entity)
	- Attributes: `id`, `ad_id`, `user_id` (offerer), `price`, `comment`, `created_at`, `updated_at`

## Subscriptions
- Name: `subscriptions` (Entity)
	- Attributes: `id`, `name`, `description`, `available_for_roles` (JSON), `features` (FK to `features` or JSON), `price`, `duration_days`, `status` (active/inactive), `expired_at`
	- Functions: `warnBeforeExpiry()`

## Features
- Name: `features` (Entity)
	- Attributes: `id`, `name`, `description`, `limits` (JSON - e.g., normal_ads_limit, unique_ads_limit, caishha_limit, findit_limit, ai_videos_limit), `toggles` (JSON - e.g., auto_republish, auto_publish_facebook, ai_video_creation, verified_ads_web/app), `created_at`, `updated_at`
	- Notes: Admin can activate any Ad Type within any Subscription and limit characteristics per activation.

## Categories (التصنيفات)
- Name: `categories` (Entity)
	- Attributes: `id`, `name_en`, `name_ar`, `status`, `specs_group_id`

## Specifications (المواصفات)
- Name: `specifications` (Entity)
	- Attributes: `id`, `name_en`, `name_ar`, `type` (enum: text/number/select/boolean), `values` (JSON or related table), `image` (nullable)

## Blogs
- Name: `blogs` (Entity)
	- Attributes: `id`, `title`, `category_id`, `image`, `body`, `status`, `created_at`, `updated_at`

## Sliders
- Name: `sliders` (Entity)
	- Attributes: `id`, `name`, `image`, `category_id` (nullable), `value` (ordering or link), `status`

## Notifications
- Name: `notifications` (Entity)
	- Attributes: `id`, `user_id`, `type`, `payload` (JSON), `is_seen` (bool), `created_at`

## Views & Stats
- Name: `views` (Entity)
	- Attributes: `id`, `target_type` (ad/dealer/page), `target_id`, `count` (aggregated), `last_viewed_at`

## Favorites / Saved Searches
- Favorites: join `favorites` table linking `user_id` <-> `ad_id` (M->M via table)
- Saved Searches: `saved_searches` entity with `id`, `user_id`, `query_params` (JSON), `created_at`

---

This listing is designed to be implementation-ready: each entity above should map to a database table (or document collection), with indices on frequently queried fields (country_id, city_id, brand_id, model_id, price, created_at). Relationships indicated should be enforced via foreign keys and appropriate cascade rules.

If you'd like, I can now convert these entities into a normalized ER diagram, a Laravel migration set, or a JSON schema for API validation. Which would you prefer next?

