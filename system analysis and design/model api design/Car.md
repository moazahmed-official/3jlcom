# Car

Fields:
- id (int)
- ad_id (int|null)
- brand_id (int)
- model_id (int)
- year (int)
- color (string|null)
- body_type (string|null)
- fuel_type (string|null)
- transmission (string|null)
- owners_count (int|null)
- mileage (int|null)
- battery_range (int|null)
- battery_capacity (decimal|null)
- is_customs_cleared (bool)
- address (string|null)
- created_at (datetime)
- updated_at (datetime)

Relations:
- belongsTo: Brand, CarModel, Ad

Example JSON:
```json
{
  "id": 891,
  "brand_id": 12,
  "model_id": 156,
  "year": 2019,
  "fuel_type": "Petrol",
  "mileage": 45000,
  "is_customs_cleared": true
}
```

API Notes:
- Car details are usually nested inside ad payloads under `car_details`.
- Brands/models maintained separately via admin endpoints.