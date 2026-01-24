Normalized API Rules

Base path
- All public APIs use `/api/v1/` as the base path.

Naming
- Use plural resource names: `users`, `ads`, `brands`, `models`, `packages`, `reviews`, `reports`, `notifications`, `media`, `saved-searches`.
- Use kebab-case for multi-word paths: `find-requests`, `saved-searches`.
- No underscores in paths.

IDs
- Use integer IDs for all resources, exposed as `id` in responses.

HTTP methods
- GET: read/list
- POST: create or invoke async server-side jobs
- PUT: full replace (rare)
- PATCH: partial update or state change
- DELETE: delete

Pagination & sorting
- Use `page` and `limit` query params.
- Defaults: `page=1`, `limit=20`, `max limit=100`.
- Sorting: `sort_by`, `sort_order` (`asc`/`desc`).
- Responses that return lists include a `pagination` object.

Query filters
- Use predictable filter names: `brand_id`, `model_id`, `price_min`, `price_max`, `city_id`, `status`, `ad_type`.

Authentication & RBAC
- Use `Authorization: Bearer {token}` header.
- Endpoints are role-agnostic; enforce permissions via RBAC middleware.
- Document required permission scopes per endpoint in the catalog.

Response envelope
- Standard envelope for all responses:
{
  "success": true|false,
  "message": "string|null",
  "data": object|null,
  "errors": object|null
}

Errors
- Use HTTP status codes correctly (400, 401, 403, 404, 409, 422, 429, 500).
- Validation errors: 422 with `errors` keyed by field -> array of messages.
- Include `error_code` for programmatic handling and `trace_id` for 5xx.

Media uploads
- Users upload media to the server via `POST /api/v1/media` as multipart/form-data.
- Server returns stored URL(s) and a `media_id` to reference in other resources.
- Server handles resizing/transcoding asynchronously; media `status` reflects processing state.

Versioning & deprecation
- Path versioning (`/api/v1/`).
- Deprecate old endpoints in a documented schedule; provide mappings and short-lived redirects if needed.

Rate limiting
- Defaults: anonymous 60 req/min; authenticated 300 req/min.
- Stricter limits for write and media endpoints.

Audit & admin actions
- Admin-level actions are enforced via RBAC; do not create role-prefixed endpoints unless audit separation is required (flag per endpoint).