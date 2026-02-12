# Frontend API Hooks Reference

This file lists the backend endpoints, HTTP methods, request shapes and example responses frontend should use when wiring the admin UI described in `FRONTEND_ADMIN_INTEGRATION_PLAN.md`.

Auth: Bearer token (user must be authenticated). Admin-only endpoints require admin role; 403 responses must be handled.

1) List Upgrade Requests
- Method: GET
- Path: /admin/upgrade-requests
- Query params: `status` (pending|approved|rejected), `page`, `per_page`, `search`
- Response: { data: [ { id, user_id, ad_id, requested_type, message, status, created_at } ], meta }

2) Approve / Reject Upgrade Request
- Method: POST
- Path: /admin/upgrade-requests/{id}/approve
- Body: { unique_ad_type_id: int, notes?: string }
- Response: { success: true, request: { id, status: 'approved', processed_by, processed_at } }

- Method: POST
- Path: /admin/upgrade-requests/{id}/reject
- Body: { notes?: string }
- Response: { success: true, request: { id, status: 'rejected', processed_by, processed_at } }

3) Package visibility (read/update)
- Method: GET
- Path: /admin/packages/{id}/visibility
- Response: { id, visibility_type: 'public'|'role_based'|'user_specific', allowed_roles: ["role1"], user_access_count: int }

- Method: PUT
- Path: /admin/packages/{id}/visibility
- Body: { visibility_type: 'public'|'role_based'|'user_specific', allowed_roles?: [string] }
- Response: { success: true, package: { id, visibility_type, allowed_roles } }

4) Grant / Revoke user access to a package
- Method: POST
- Path: /admin/packages/{id}/grant-access
- Body: { user_ids: [int] }
- Response: { success: true, granted: [userId] }

- Method: POST
- Path: /admin/packages/{id}/revoke-access
- Body: { user_ids: [int] }
- Response: { success: true, revoked: [userId] }

5) Users with access
- Method: GET
- Path: /admin/packages/{id}/users-with-access
- Query: `page`, `per_page`, `search`
- Response: { data: [ { id, name, email } ], meta }

6) Packages listing (visibility-aware)
- Method: GET
- Path: /packages
- Query: `visibility_type` (admin-only filter), `page`, `per_page`, `search`
- Response: { data: [ { id, name, visibility_type, is_visible_to_user }, ... ], meta }

7) My packages (user active subscriptions)
- Method: GET
- Path: /packages/my-packages
- Response: { data: [ { id, package_id, starts_at, ends_at, credits: { feature_key: remaining } } ] }

8) Check capability / pre-check (recommended)
- Method: POST
- Path: /packages/check-capability
- Body: { user_id?: int, package_id?: int, feature: string, ad_id?: int }
- Response: { allowed: bool, reason?: string, remaining_credits?: int }

9) Convert Ad Type
- Method: POST
- Path: /ads/{ad}/convert
- Body: { target_type: 'unique'|'findit'|'auction'|'standard', unique_ad_type_id?: int }
- Notes: Admins bypass credit checks; otherwise server validates package, credits, or pending upgrade rules.
- Response: { success: true, ad: { id, type, updated_at }, deduction: { source: 'package'|'unique_type', feature: 'convert', remaining: int } }

10) Ad conversion history
- Method: GET
- Path: /ads/{ad}/conversion-history
- Response: { data: [ { id, ad_id, from_type, to_type, performed_by, created_at } ], meta }

11) My feature credits
- Method: GET
- Path: /features/my-credits
- Response: { data: [ { feature_key, name, remaining, source: 'package'|'type', package_id? } ] }

12) Use a feature / consume credits
- Method: POST
- Path: /features/{feature_key}/use
- Body: { ad_id?: int, amount?: int }
- Response: { success: true, consumed: int, remaining: int, source: 'package'|'type' }

13) Feature usage history
- Method: GET
- Path: /features/usage-history
- Query: `feature_key`, `user_id`, `page`, `per_page`
- Response: { data: [ { id, user_id, feature_key, amount, source, related_id, created_at } ], meta }

14) Roles & users for UI selects
- Method: GET
- Path: /roles
- Response: { data: [ { id, name, label } ] }

- Method: GET
- Path: /users
- Query: `q` (autocomplete), `page`, `per_page`
- Response: { data: [ { id, name, email } ], meta }

15) Admin package export (if available)
- Method: GET
- Path: /admin/packages/{id}/export
- Response: CSV or JSON depending on `Accept` header. If backend not implemented, frontend should implement CSV fallback from fetched data.

Errors
- Common errors: 401 (unauthenticated), 403 (forbidden), 422 (validation), 400 (bad request), 500 (server error).
- Frontend should surface `message` from error body and show friendly modals for 403/422.

Usage notes
- For conversion UI, call `/packages/check-capability` first to show immediate availability and remaining credits.
- For role-based UI, fetch `/roles` once and cache in memory.
- For user-autocomplete, debounce input (300ms) and call `/users?q=...`.

Add this file to cross-link from `FRONTEND_ADMIN_INTEGRATION_PLAN.md` and implement the hooks under `src/api/*` as described in that plan.
