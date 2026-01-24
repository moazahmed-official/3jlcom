# CarModel

Fields:
- id (int)
- brand_id (int)
- name (string)
- year_from (int|null)
- year_to (int|null)
- created_at (datetime)
- updated_at (datetime)

Relations:
- belongsTo: Brand
- hasMany: Car

Example JSON:
```json
{
  "id": 156,
  "brand_id": 12,
  "name": "Corolla",
  "year_from": 2000,
  "year_to": null
}
```

API Notes:
- Endpoints: `GET /api/admin/cars/models`, `POST /api/admin/cars/models`.
- Allow null model_id in `FindIt` requests.