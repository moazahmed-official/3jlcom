# Feature

Fields:
- id (int)
- name (string)
- description (string|null)
- limits (json|null)
- toggles (json|null)
- created_at (datetime)

Relations:
- usedBy: Subscription

Example JSON:
```json
{
  "id": 7,
  "name": "Auto Republish",
  "limits": {"max_reposts_per_month": 5},
  "toggles": {"auto_republish": true}
}
```

API Notes:
- Endpoints: `GET /api/admin/features`, `POST /api/admin/features/{id}/assign`.
- Feature flags control platform capabilities per package.