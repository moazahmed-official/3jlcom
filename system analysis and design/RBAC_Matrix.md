RBAC Matrix — high-level mapping of roles to permissions

Roles:
- individual (user)
- dealer
- showroom
- marketer
- moderator
- country_manager
- admin
- super_admin

Legend: CRUD = create/read/update/delete; X = allowed

Resources and role permissions (high-level)

- Users
  - admin, super_admin: CRUD (manage users, assign roles, verify)
  - country_manager: Read, Update (scoped to country)
  - moderator: Read
  - individual/dealer/showroom/marketer: Read/Update self only

- Auth
  - public: register, login, verify OTP
  - authenticated: logout, reset password

- Normal Ads (`normal-ads`)
  - individual/dealer/showroom: CRUD own ads, republish action
  - admin/country_manager: Read/Update/Delete any ad, moderation actions
  - moderator: Read, moderation actions (approve/decline)

- Unique Ads (`unique-ads`)
  - individual/dealer/showroom: CRUD own unique ads, request verification
  - admin: Read/Update/Delete, feature/promote, approve verification
  - moderator: Read, moderation actions

- Caishha (`caishha`)
  - individual/dealer/showroom: Create Caishha, manage own offers
  - dealers/showrooms: submit offers during dealer window
  - admin: full CRUD and control over offer windows
  - moderator: review offers/ad content

- Auctions (`auctions`) & Bids
  - individual/dealer/showroom: Create auction (if allowed), place bids, withdraw own bids
  - admin: CRUD, force-close auctions, view all bids
  - moderator: view bids, moderate content

- Offers & Bids (`offers`, `bids`)
  - offer owner: create/update/delete own offers (within rules)
  - ad owner: view incoming offers for own ad
  - admin: CRUD any offer (moderation/safety)

- Brands & Models
  - admin: CRUD
  - country_manager: Read/Update (country-scoped)
  - public: Read brands/models

- Subscriptions / Packages
  - admin: CRUD packages, assign packages to users
  - user: request/purchase package, view own packages
  - country_manager: manage country-specific package settings

- Media
  - authenticated users: upload media for own ads/profiles
  - admin: delete/replace any media

- Notifications
  - admin: send notifications to users or groups
  - user: read own notifications, mark read/hide

- Reports & Reviews
  - user: create reports/reviews, update/delete own
  - admin/moderator: read, delete, act on reports/reviews

- Blogs & Sliders
  - admin: CRUD, publish/unpublish
  - public: read published blogs/sliders

- Saved Searches & Favorites
  - user: CRUD own saved searches & favorites

Implementation notes
- Enforce RBAC in middleware and log admin actions (audit trail required).
- Permissions should be expressed as granular scopes (e.g., `ads.create`, `ads.manage.any`, `users.assign_roles`).
- Country scoping: country_manager and moderators are scoped to `country_id`; middleware must enforce scope.

Next: attach this RBAC matrix to each OpenAPI spec as `x-permissions` entries per operation (optional but recommended).