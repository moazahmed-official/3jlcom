# Review

Fields:
- id (int)
- title (string|null)
- body (text)
- stars (int 1-5)
- user_id (int)
- seller_id (int|null)
- ad_id (int|null)
- created_at (datetime)

Relations:
- belongsTo: User, Seller, Ad

Example JSON:
```json
{
  "id": 120,
  "title": "Excellent service",
  "body": "Fast response and honest inspection.",
  "stars": 5,
  "user_id": 200,
  "seller_id": 45
}
```

API Notes:
- Endpoints: `POST /api/user/reviews`, `GET /api/admin/reviews`, `DELETE /api/admin/reviews/{id}`.
- Reviews tied to sellers or ads; implement moderation and abuse detection.