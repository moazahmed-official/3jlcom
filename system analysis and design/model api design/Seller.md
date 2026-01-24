# Seller

Fields:
- id (int)
- user_id (int)
- company_name (string|null)
- verified (bool)
- badge (enum|null)
- profile_image (url|null)
- contact_count (int)
- views_count (int)
- link_clicks_count (int)
- created_at (datetime)
- updated_at (datetime)

Relations:
- belongsTo: User
- hasMany: Ad, VerificationRequest

Example JSON:
```json
{
  "id": 45,
  "user_id": 157,
  "company_name": "Showroom Pro",
  "verified": true,
  "badge": "trusted",
  "views_count": 1245
}
```

API Notes:
- Endpoints: `POST /api/seller/verify/request`, `GET /api/seller/dashboard`, `GET /api/seller/ads`.
- Seller is a profile extension of `User`; support bulk uploads and analytics.