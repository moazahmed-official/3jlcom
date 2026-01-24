# Report

Fields:
- id (int)
- title (string|null)
- reason (text)
- reported_by_user_id (int)
- target_type (enum: ad|user|dealer)
- target_id (int)
- status (enum: pending|reviewed|actioned)
- created_at (datetime)

Relations:
- belongsTo: User
- polymorphicTarget: Ad, User, Seller

Example JSON:
```json
{
  "id": 9001,
  "reason": "Misleading description",
  "reported_by_user_id": 200,
  "target_type": "ad",
  "target_id": 5678,
  "status": "pending"
}
```

API Notes:
- Endpoints: `POST /api/user/reports`, `GET /api/admin/reports`, `DELETE /api/admin/reports/{id}`.
- Include audit trail and moderator assignment for processing.