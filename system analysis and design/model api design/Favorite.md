# Favorite

Fields:
- id (int)
- user_id (int)
- ad_id (int)
- created_at (datetime)

Relations:
- belongsTo: User, Ad

Example JSON:
```json
{
  "id": 5000,
  "user_id": 200,
  "ad_id": 5678
}
```

API Notes:
- Endpoints: `POST /api/user/fav-ads/{ad-id}`, `GET /api/user/fav-ads`, `DELETE /api/user/fav-ads/{fav-id}`.
- Use composite unique index on (`user_id`, `ad_id`) to prevent duplicates.