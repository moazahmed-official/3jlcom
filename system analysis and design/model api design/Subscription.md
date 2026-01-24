# Subscription

Fields:
- id (int)
- name (string)
- description (string|null)
- available_for_roles (json)
- features (json)
- price (decimal)
- duration_days (int)
- status (enum: active|inactive)
- expired_at (datetime|null)
- created_at (datetime)

Relations:
- manyToMany: User (assigned subscriptions)

Example JSON:
```json
{
  "id": 3,
  "name": "Dealer Basic",
  "features": {"normal_ads_limit": 50, "featured_ads_limit": 5},
  "price": 120.00,
  "duration_days": 30
}
```

API Notes:
- Endpoints: `GET /api/admin/subscriptions`, `POST /api/admin/subscriptions`, `POST /api/user/subs/{sub-id}`.
- Enforce quotas server-side and emit warnings before expiry.