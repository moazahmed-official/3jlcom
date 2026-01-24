# Brand

Fields:
- id (int)
- name (string)
- country_id (int|null)
- logo (url|null)
- created_at (datetime)
- updated_at (datetime)

Relations:
- hasMany: CarModel, Car

Example JSON:
```json
{
  "id": 12,
  "name": "Toyota",
  "logo": "https://cdn.smartcars.local/brands/toyota.png"
}
```

API Notes:
- Endpoints: `GET /api/admin/cars/brands`, `POST /api/admin/cars/brands`.
- Consider popularity ranking for search weighting.