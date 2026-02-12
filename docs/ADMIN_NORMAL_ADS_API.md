1. Metadata endpoints (public/admin)

GET /api/v1/countries

- Request: GET `/api/v1/countries`
  - Query params: `per_page` (int, optional) — if provided the endpoint returns a paginated envelope; otherwise returns the full list.
  - Example: `GET /api/v1/countries?per_page=20&page=1`

- Explanation: Returns the list of countries. When `per_page` is present the response contains pagination metadata (`page`, `per_page`, `total`, `items`). Without `per_page` the `data` is a plain array of country objects.

- Request example (curl):

```bash
curl -X GET "http://localhost:8000/api/v1/countries?per_page=20&page=1"
```

- Response (non-paginated, 200):

```json
{
  "status": "success",
  "message": "Countries retrieved successfully",
  "data": [
    { "id": 1, "name_en": "United Arab Emirates", "name_ar": "الإمارات", "code": "AE", "phone_code": "+971" },
    { "id": 2, "name_en": "Saudi Arabia", "name_ar": "السعودية", "code": "SA", "phone_code": "+966" }
  ]
}
```

- Response (paginated, 200):

```json
{
  "status": "success",
  "message": "Countries retrieved successfully",
  "data": {
    "page": 1,
    "per_page": 20,
    "total": 2,
    "items": [
      { "id": 1, "name_en": "United Arab Emirates", "name_ar": "الإمارات", "code": "AE", "phone_code": "+971" },
      { "id": 2, "name_en": "Saudi Arabia", "name_ar": "السعودية", "code": "SA", "phone_code": "+966" }
    ]
  }
}
```

Field notes:
- `id`: integer country id.
- `name_en` / `name_ar`: localized names.
- `code`: ISO country code.
- `phone_code`: international dialing prefix.

GET /api/v1/countries/{country}/cities

- Request: GET `/api/v1/countries/{country}/cities`
  - Path param: `country` (int) — route-model bound to `Country`.
  - Query params: `per_page` (int, optional) — same pagination behaviour as countries.
  - Example: `GET /api/v1/countries/1/cities?per_page=50&page=1`

- Explanation: Returns cities for the specified country. When `per_page` is present the response contains pagination metadata; otherwise `data` is an array of city objects.

- Request example (curl):

```bash
curl -X GET "http://localhost:8000/api/v1/countries/1/cities?per_page=50&page=1"
```

- Response (non-paginated, 200):

```json
{
  "status": "success",
  "message": "Cities retrieved successfully",
  "data": [
    { "id": 10, "country_id": 1, "name_en": "Dubai", "name_ar": "دبي" },
    { "id": 11, "country_id": 1, "name_en": "Abu Dhabi", "name_ar": "أبوظبي" }
  ]
}
```

- Response (paginated, 200):

```json
{
  "status": "success",
  "message": "Cities retrieved successfully",
  "data": {
    "page": 1,
    "per_page": 50,
    "total": 2,
    "items": [
      { "id": 10, "country_id": 1, "name_en": "Dubai", "name_ar": "دبي" },
      { "id": 11, "country_id": 1, "name_en": "Abu Dhabi", "name_ar": "أبوظبي" }
    ]
  }
}
```

Field notes:
- `id`: integer city id.
- `country_id`: parent country id.
- `name_en` / `name_ar`: localized city names.

2. Normal Ads (admin) — available routes and shapes

- 8. Export (CSV) — NOT IMPLEMENTED

- `GET /api/normal-ads/admin/export` is not present by default. Options:
  - Implement server-side CSV export that returns `Content-Type: text/csv` and `Content-Disposition: attachment; filename="normal-ads-export.csv"`, or
  - Frontend generate CSV client-side from `GET /api/normal-ads/admin` results.
8. Export (CSV)

- `GET /api/normal-ads/admin/export` is implemented. It accepts the same filters as the admin listing and returns a CSV with header `Content-Type: text/csv` and `Content-Disposition: attachment; filename="normal-ads-export-YYYY-MM-DD.csv"`.

# Admin Normal Ads API (Corrected)

Summary
- Canonical API prefix for this repo: `/api/v1`. Use `VITE_API_BASE_URL=http://localhost:8000/api/v1` in Vite env. The backend returns Sanctum plain-text tokens via `POST /api/v1/auth/login` (Bearer scheme).

Canonical response envelope
- Success: `{ "status": "success", "message": "...", "data": ... }`
- Paginated success: `{ "status":"success","message":"...","data": { "page":1, "per_page":20, "total":100, "items": [ ... ] } }`
- Error: `{ "status":"error","code":HTTP_CODE,"message":"...","errors":{ ... } }`

- Notes
- Routes in this codebase are registered under `routes/api.php` using a `/api/v1` prefix group. Some controllers' doc comments mention `/api` — prefer the actual registered routes. Adjust frontend env to `VITE_API_BASE_URL=http://localhost:8000/api/v1`.
- Admin endpoints require an admin Bearer token. Many actions allow owner OR admin.
- Backend expects `ad_ids` for bulk actions (not `ids`). Export route is not implemented by default.

1. Metadata endpoints (public/admin)

- GET /api/brands
  - Explain: Brands list (paginated).
  - Query: `page`, `per_page` (or `limit`).
  - Response (200):
    ```json
    {
      "status":"success",
      "message":"Brands retrieved successfully",
      "data":{ "page":1, "per_page":20, "total":2, "items":[ {"id":1,"name_en":"Toyota","name_ar":"تويوتا","image_url":"..."} ] }
    }
    ```

- GET /api/brands/{brand}/models
  - Explain: Models for a brand (paginated).
  - Response similar to brands paginated envelope with `items` array.

- GET /api/categories (RECOMMENDED)
  - NOTE: `CategoryController` endpoints are currently admin-only under `/api/admin/categories`. If frontend needs public categories, add `GET /api/categories` or call admin route with admin token.
  - Suggested response shape (if added): `{ status, message, data: [ {id,name_en,name_ar,slug,parent_id} ] }`

- GET /api/countries and GET /api/countries/{id}/cities (MISSING)
  - These endpoints are not present in `routes/api.php`. Implement these server-side if frontend requires them. Suggested shapes:
    - GET /api/countries -> `{ status, message, data: [ { id, name_en, name_ar, code, phone_code } ] }`
    - GET /api/countries/{id}/cities -> `{ status, message, data: [ { id, country_id, name_en, name_ar } ] }`

2. Normal Ads (admin) — available routes and shapes

- List (admin)
  - GET /api/normal-ads/admin
  - Explain: Admin listing for all normal ads (paginated). Filters supported in controller: `page`, `limit`, `status`, `user_id`, `brand_id`, `model_id`, `city_id`, `country_id`, `search`, `sort_by`, `sort_direction`.
  - Response (200): paginated envelope with `data.items` containing `NormalAdResource` objects.

- Global stats
  - GET /api/normal-ads/stats
  - Explain: Admin-only aggregate counts.
  - Response (200): `{ status:"success", message:"Global statistics retrieved successfully", data: { total_ads, published_ads, draft_ads, pending_ads, expired_ads, removed_ads, total_views, ads_today, ads_this_week, ads_this_month } }

- Get single ad
  - GET /api/normal-ads/{id}
  - Response: `{ status:"success", data: { /* NormalAdResource */ } }` (increments views for non-owner requests)

- Create ad
  - POST /api/normal-ads
  - Explain: Create ad; admin may include `user_id` to create on behalf of another user (backend verifies admin). Include `media_ids` array referencing uploaded media records.
  - Request JSON (typical): `{ "title":string, "description":string, "category_id":int, "city_id":int, "country_id":int, "brand_id"?, "model_id"?, "year"?, "price_cash"?, "media_ids": [int], "user_id"?:int }`
  - Response (201): `{ status:"success", message:"Ad created successfully", data: { /* NormalAdResource */ } }`

- Update ad
  - PUT /api/normal-ads/{id}
  - Request: subset of create fields (validated by `UpdateNormalAdRequest`)
  - Response: updated `NormalAdResource` envelope

- Delete ad
  - DELETE /api/normal-ads/{id}
  - Response: `{ status:"success", message:"Ad deleted successfully" }`

3. Ad lifecycle actions

All follow: POST /api/normal-ads/{id}/actions/{action}
- publish: POST /api/normal-ads/{id}/actions/publish
- unpublish: POST /api/normal-ads/{id}/actions/unpublish
- republish: POST /api/normal-ads/{id}/actions/republish
- expire: POST /api/normal-ads/{id}/actions/expire
- archive: POST /api/normal-ads/{id}/actions/archive
- restore: POST /api/normal-ads/{id}/actions/restore

Response: standard success envelope; usually includes the updated resource or message.

4. Bulk actions (admin)

- POST /api/normal-ads/actions/bulk
  - Controller expects body:
    ```json
    { "action": "publish|unpublish|expire|archive|restore|delete", "ad_ids": [1,2,3] }
    ```
  - Response: `{ status:"success", message:"Bulk {action} completed successfully", data: { action: string, updated_count: int } }`
  - Note: frontend may currently send `ids`; update frontend to send `ad_ids` or extend backend to accept both.

5. Convert to unique
- POST /api/normal-ads/{id}/actions/convert-to-unique
- Body optional: `{ "banner_image_id"?:int, "banner_color"?:string, "is_auto_republished"?:bool }`

6. Favorites & Contact
- POST /api/normal-ads/{id}/favorite
- DELETE /api/normal-ads/{id}/favorite
- POST /api/normal-ads/{id}/contact  (returns seller contact info; tracking TODO)

7. Media upload

- POST /api/media
  - Multipart form-data: `file` (image/video), optional `purpose`, optional `related_resource`, optional `related_id`.
  - Response (201): `{ status:"success", message:"Media uploaded successfully", data: { id, filename, path, url, type, status, thumbnail_url, user_id, created_at } }`
  - Use returned `id` values in ad `media_ids`.

8. Export (CSV) — NOT IMPLEMENTED

- `GET /api/normal-ads/admin/export` is not present by default. Options:
  - Implement server-side CSV export that returns `Content-Type: text/csv` and `Content-Disposition: attachment; filename="normal-ads-export.csv"`, or
  - Frontend generate CSV client-side from `GET /api/normal-ads/admin` results.

9. Errors & HTTP codes
- Validation (422), Not Found (404), Unauthorized (401), Forbidden (403), Server (500) use `{ status:"error", code:..., message:..., errors:{...} }`.

10. Quick sample responses

- `GET /api/brands` sample (paginated):
  ```json
  {
    "status":"success",
    "message":"Brands retrieved successfully",
    "data":{ "page":1, "per_page":20, "total":2, "items": [ { "id":1, "name_en":"Toyota", "image_url":"http://localhost:8000/storage/brands/.." } ] }
  }
  ```

- `GET /api/normal-ads/admin?page=1&limit=1` sample:
  ```json
  {
    "status":"success",
    "message":"Data retrieved successfully",
    "data":{
      "page":1,
      "per_page":1,
      "total":123,
      "items":[ { "id":555, "title":"2024 Toyota Camry SE", "status":"published", "normalAd":{ "price_cash":85000 }, "brand":{ "id":1, "name_en":"Toyota" }, "media":[ { "id":10, "url":"http://localhost:8000/storage/..." } ], "views_count":150 } ]
    }
  }
  ```

Implementation notes for backend team (quick):
- Add public `GET /api/countries` + `GET /api/countries/{id}/cities` or expose admin routes to frontend with admin token.
- Add public `GET /api/categories` or enable frontend to call `GET /api/admin/categories` using admin token.
- Add `GET /api/normal-ads/admin/export` if server-side CSV is required.
- Consider accepting `ids` in bulk endpoint in addition to `ad_ids` for smoother frontend integration.

Next steps
- If you want, I can generate TypeScript hook stubs under `src/api/admin/` for: list, get, create, update, bulkAction, publish/unpublish, stats, and media upload. I can also add the missing routes (countries/cities/categories/export) as small controllers if you want me to implement them.
