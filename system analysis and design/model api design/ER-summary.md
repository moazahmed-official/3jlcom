# ER Summary

This document summarizes primary tables, core relations, and recommended indexes for the Smart Car Ads platform.

## Core Tables
- users (PK id)
- roles (PK id)
- sellers (PK id, FK user_id)
- ads (PK id, FK seller_id, FK category_id, FK city_id)
- cars (PK id, FK ad_id, FK brand_id, FK model_id)
- brands (PK id)
- car_models (PK id, FK brand_id)
- media (PK id, polymorphic attachments)
- subscriptions (PK id)
- subscription_user (pivot: user_id, subscription_id, expires_at)
- features (PK id)
- installments (PK id, FK ad_id)
- reports (PK id)
- reviews (PK id)
- caishha_offers (PK id)
- auctions (PK id)
- bids (PK id)
- favorites (PK id, user_id, ad_id)
- saved_searches (PK id)
- notifications (PK id)
- transactions (PK id)
- view_stats (PK id, target_type, target_id)
- blogs (PK id)
- sliders (PK id)

## Key Relations
- users 1 - * ads (via sellers)
- users 1 - * reports
- users 1 - * reviews
- users 1 - * notifications
- sellers 1 - * ads
- ads 1 - 1 car
- ads 1 - * media
- ads 1 - * caishha_offers
- auctions 1 - * bids
- users * - * subscriptions (via pivot)

## Recommended Indexes
- users: index on (mobile_number), (email)
- ads: index on (seller_id), (status), (ad_type), (category_id), (city_id), (created_at)
- cars: index on (brand_id), (model_id), (year)
- media: index on (model_type, model_id)
- favorites: unique(user_id, ad_id)
- bids: index on (auction_id, amount desc)
- transactions: index on (user_id, transaction_ref)

## Soft Deletes and Auditing
- Use `softDeletes` on `ads`, `users`, `blogs` to retain history.
- Add `created_by`, `updated_by` for admin operations where applicable.

## Subtyping Strategy for Ads
- Base `ads` table for common fields.
- Subtables: `unique_ads`, `caishha_ads`, `auction_ads` with FK `ad_id` and type-specific columns.
- Use database transactions when creating base + subtype rows to avoid inconsistencies.

## Notes
- Keep country-specific configs (currency, tax) in a settings table keyed by `country_id`.
- For analytics, maintain aggregated tables (daily_ad_views) updated via background jobs to avoid heavy queries on OLTP.


Created from workspace documentation: `API Catalog.md`, `API designer and documenter.md`, `Software Entity Listing.md`.