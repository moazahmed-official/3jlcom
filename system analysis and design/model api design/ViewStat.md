# ViewStat

Fields:
- id (int)
- target_type (string)
- target_id (int)
- count (int)
- last_viewed_at (datetime)

Relations:
- polymorphicTarget: Ad, Seller, Page

Example JSON:
```json
{
  "id": 600,
  "target_type": "ad",
  "target_id": 5678,
  "count": 34,
  "last_viewed_at": "2026-01-24T16:00:00Z"
}
```

API Notes:
- Endpoints: `POST /api/seller/ads/{id}/views`, `GET /api/seller/stats/views`.
- Keep aggregated counts for performance; optionally store event logs for deeper analytics.