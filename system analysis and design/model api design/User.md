# User

Fields:
- id (int)
- full_name (string)
- password_hash (string)
- mobile_number (string)
- email (string|null)
- profile_image (url|null)
- country_id (int)
- city_id (int|null)
- role_id (int)
- verification_otp (string|null)
- is_verified (bool)
- status (enum: active|blocked|suspended)
- created_at (datetime)
- updated_at (datetime)

Relations:
- belongsTo: Role, Country, City
- hasMany: Ad, Report, Review, Notification, SavedSearch
- manyToMany: Favorite <-> Ad

Example JSON:
```json
{
  "id": 200,
  "full_name": "Mohammed Sharqi",
  "mobile_number": "+962791122334",
  "email": "mohammed@example.com",
  "country_id": 1,
  "city_id": 3,
  "role_id": 2,
  "is_verified": false,
  "created_at": "2026-01-24T13:00:00Z"
}
```

API Notes:
- Common endpoints: `POST /api/user/auth/register`, `POST /api/user/auth/login`, `GET /api/user/profile`, `PUT /api/user/profile`, `GET /api/admin/users`, `POST /api/admin/users`
- OTP verification required for registration; role-driven behavior (dealer/showroom/marketer).