Migration Map (legacy -> normalized)

This file maps legacy endpoints from the platform documentation into the normalized `/api/v1/` routes. Ambiguous items are flagged for manual review.

Core mappings

- Authentication
	- `/api/user/auth/login` -> `/api/v1/auth/login`
	- `/api/admin/auth/login` -> `/api/v1/auth/login`
	- `/api/user/auth/register` -> `/api/v1/auth/register`
	- `/api/user/verification` -> `/api/v1/auth/verify`
	- `/api/user/auth/logout` -> `/api/v1/auth/logout`
	- `/api/admin/auth/logout` -> `/api/v1/auth/logout`
	- `/api/user/reset` -> `/api/v1/auth/password/reset`

- Users
	- `/api/admin/users` -> `/api/v1/users`  (admin-scoped via RBAC)
	- `/api/admin/users/{id}` -> `/api/v1/users/{userId}`
	- `/api/admin/users/{id}/role` -> `/api/v1/users/{userId}/roles`
	- `/api/admin/users/{id}/verify` -> `/api/v1/users/{userId}/verify`
	- `/api/user/profile` (GET/PUT) -> `/api/v1/users/{userId}` (self-scoped)

- Ads (kept per type)
	- `/api/user/normal_ad` -> `/api/v1/normal-ads`
	- `/api/user/normal_ad/{id}` -> `/api/v1/normal-ads/{adId}`
	- `/api/user/unique_ad` -> `/api/v1/unique-ads`
	- `/api/user/unique_ad/{id}` -> `/api/v1/unique-ads/{adId}`
	- `/api/admin/unique_ad` -> `/api/v1/unique-ads` (admin-scoped)
	- `/api/user/caishha` -> `/api/v1/caishha`
	- `/api/user/caishha/{id}` -> `/api/v1/caishha/{adId}`
	- `/api/admin/caishha` -> `/api/v1/caishha` (admin-scoped)
	- `/api/user/auction` -> `/api/v1/auctions`
	- `/api/admin/auction` -> `/api/v1/auctions` (admin-scoped)

- Offers / Bids
	- `/api/user/caishha/{id}/offers` -> `/api/v1/caishha/{adId}/offers`
	- `/api/user/caishha/offers/{id}` -> `/api/v1/offers/{offerId}`
	- `/api/admin/caishha/{id}/offers` -> `/api/v1/caishha/{adId}/offers` (admin view)
	- `/api/user/auction/{id}/offers` -> `/api/v1/auctions/{auctionId}/bids`
	- `/api/admin/offers/{id}` -> `/api/v1/offers/{offerId}`

- FindIt
	- `/api/user/findit` -> `/api/v1/find-requests`
	- `/api/admin/findit` -> `/api/v1/find-requests` (admin-scoped)

- Brands & Models
	- `/api/admin/cars/brands` -> `/api/v1/brands`
	- `/api/admin/cars/models` -> `/api/v1/brands/{brandId}/models` or `/api/v1/models`

- Reports & Reviews
	- `/api/user/reports` -> `/api/v1/reports`
	- `/api/admin/reports` -> `/api/v1/reports` (admin view)
	- `/api/user/reviews` -> `/api/v1/reviews`
	- `/api/admin/reviews` -> `/api/v1/reviews` (admin view)

- Subscriptions & Packages
	- `/api/admin/subscriptions` -> `/api/v1/packages`
	- `/api/admin/subscriptions/{id}` -> `/api/v1/packages/{id}`
	- `/api/admin/subscriptions/{id}/assign` -> `/api/v1/packages/{id}/assign`
	- `/api/user/subs` -> `/api/v1/packages` (public list)
	- `/api/user/my-sub` -> `/api/v1/users/{userId}/packages` (user packages)

- Notifications
	- `/api/admin/notifications` -> `/api/v1/notifications` (admin sends via `/notifications/send`)
	- `/api/user/notifications` -> `/api/v1/notifications` (user inbox)
	- `/api/admin/notifications/ad/{ad_id}/send` -> `/api/v1/notifications/send?ad_id={adId}`

- Blogs & Sliders
	- `/api/admin/blogs` -> `/api/v1/blogs` (admin-scoped)
	- `/api/blogs` -> `/api/v1/blogs` (public)
	- `/api/admin/blogs/{id}/publish` -> `PATCH /api/v1/blogs/{id}` (status)
	- `/api/admin/sliders` -> `/api/v1/sliders`
	- `/api/sliders/home` -> `/api/v1/sliders?location=home&status=active`

- Media
	- `/api/admin/blogs/{id}/image` -> `POST /api/v1/media` with related_resource=blog, related_id={id}
	- `/api/admin/sliders/{id}/image` -> `POST /api/v1/media` with related_resource=slider, related_id={id}

Notes & Ambiguities

- Action endpoints: Several legacy endpoints embed verbs (publish, activate, expire). Normalize to either `PATCH /resource/{id}` with explicit `status` changes, or `POST /resource/{id}/actions/{name}` for async actions. Examples to review:
	- `/api/admin/unique_ad/{id}` (promote) -> consider `POST /unique-ads/{id}/actions/feature` or `PATCH /unique-ads/{id}` with `{ "is_featured": true }`.
	- `/api/admin/subscription/{id}/expire` (singular path) -> `POST /packages/{id}/actions/expire` or `PATCH /packages/{id}` with status.

- Admin vs RBAC: All admin functionality is mapped into normalized endpoints and should be gated by RBAC. If audit separation or distinct routing is required for specific admin actions, those endpoints should be duplicated under `/api/v1/admin/` and marked in this map.

Next steps

- Finalize CSV export of this map.
- Mark any remaining ambiguous mappings and set them for a manual review pass (list below).

Ambiguities needing manual decisions:
- `/api/admin/unique_ad/{id}` (promote/feature semantics and payload)
- `/api/admin/subscription/{request_id}` (approve request semantics and path)
- Any legacy endpoints that accept file uploads in non-standard ways — map to `/media` and confirm processing behavior.

If you want, I can now export this map as `Migration_Map.csv` and open a manual review checklist file for ambiguous items.