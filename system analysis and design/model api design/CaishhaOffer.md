# CaishhaOffer

Fields:
- id (int)
- ad_id (int)
- user_id (int)
- price (decimal)
- comment (text|null)
- status (enum: pending|accepted|rejected)
- created_at (datetime)

Relations:
- belongsTo: Ad, User

Example JSON:
```json
{
  "id": 1289,
  "ad_id": 3456,
  "user_id": 45,
  "price": 38000,
  "status": "pending"
}
```

API Notes:
- Endpoints: `POST /api/user/caishha/{id}/offers`, `GET /api/admin/caishha/{id}/offers`.
- Business rules: enforce offer window and prevent duplicate offers by same user.