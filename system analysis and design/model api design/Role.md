# Role

Fields:
- id (int)
- name (string)
- slug (string)
- permissions (json)
- created_at (datetime)
- updated_at (datetime)

Relations:
- hasMany: User

Example JSON:
```json
{
  "id": 1,
  "name": "Dealer",
  "slug": "dealer",
  "permissions": {"ads:create": true, "ads:moderate": false}
}
```

API Notes:
- Used by RBAC middleware; assign via `POST /api/admin/users/{id}/role`. 
- Permissions stored as JSON for flexibility.