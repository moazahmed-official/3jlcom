# Auction

Fields:
- id (int)
- ad_id (int)
- start_price (decimal)
- last_price (decimal|null)
- start_time (datetime)
- end_time (datetime)
- auto_close (bool)
- winner_user_id (int|null)
- is_last_price_visible (bool)
- created_at (datetime)

Relations:
- belongsTo: Ad
- hasMany: Bid

Example JSON:
```json
{
  "id": 401,
  "ad_id": 7890,
  "start_price": 15000,
  "last_price": 16500,
  "start_time": "2026-02-01T10:00:00Z",
  "end_time": "2026-02-05T18:00:00Z",
  "auto_close": true
}
```

API Notes:
- Endpoints: `POST /api/user/auction`, `POST /api/user/auction/{id}/offers`, `GET /api/admin/auction/{id}/offers`, `POST /api/admin/auction/{id}/close`.
- Support anti-sniping extension and optionally hidden bidders.