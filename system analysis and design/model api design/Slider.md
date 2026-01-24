# Slider

Fields:
- id (int)
- name (string)
- image (url)
- category_id (int|null)
- value (string|null)
- order (int)
- status (enum: active|inactive)
- created_at (datetime)

Relations:
- belongsTo: Category|null

Example JSON:
```json
{
  "id": 1,
  "name": "Winter Sale Banner",
  "image": "https://cdn.smartcars.local/sliders/winter-sale.jpg",
  "order": 1,
  "status": "active"
}
```

API Notes:
- Endpoints: `GET /api/sliders/home`, `POST /api/admin/sliders`.
- Cache sliders for homepage performance.