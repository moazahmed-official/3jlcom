# Blog

Fields:
- id (int)
- title (string)
- category_id (int|null)
- image (url|null)
- body (text)
- status (enum: draft|published|archived)
- created_at (datetime)
- published_at (datetime|null)

Relations:
- belongsTo: Category

Example JSON:
```json
{
  "id": 45,
  "title": "Tips for Buying a Used Car",
  "category_id": 4,
  "status": "published",
  "published_at": "2026-01-20T10:00:00Z"
}
```

API Notes:
- Endpoints: `GET /api/blogs`, `POST /api/admin/blogs`, `POST /api/admin/blogs/{id}/publish`.
- Public read endpoints are unauthenticated; admin endpoints for CRUD and publish controls.