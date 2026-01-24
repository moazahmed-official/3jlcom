# FindItRequest

Fields:
- id (int)
- requester_id (int)
- brand_id (int|null)
- model_id (int|null)
- min_price (decimal|null)
- max_price (decimal|null)
- min_year (int|null)
- max_year (int|null)
- fuel_type (string|null)
- transmission (string|null)
- city_id (int)
- country_id (int)
- comments (text|null)
- status (enum: active|closed)
- created_at (datetime)

Relations:
- belongsTo: User, Brand, CarModel, City
- hasMany: Responses (dealer replies)

Example JSON:
```json
{
  "id": 8901,
  "requester_id": 200,
  "brand_id": 12,
  "min_year": 2018,
  "max_year": 2023,
  "min_price": 12000,
  "max_price": 18000,
  "city_id": 3,
  "country_id": 1,
  "status": "active"
}
```

API Notes:
- Endpoints: `POST /api/user/findit`, `GET /api/user/findit`, `GET /api/user/findit/{id}/similar`.
- Notify matching dealers; allow optional model_id.