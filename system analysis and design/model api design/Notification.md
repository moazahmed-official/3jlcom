# Notification

Fields:
- id (int)
- user_id (int)
- type (string)
- payload (json)
- is_seen (bool)
- created_at (datetime)

Relations:
- belongsTo: User

Example JSON:
```json
{
  "id": 4002,
  "user_id": 200,
  "type": "offer_received",
  "payload": {"ad_id":3456,"offer_id":1289},
  "is_seen": false
}
```

API Notes:
- Endpoints: `GET /api/user/notifications`, `PUT /api/user/notifications/{id}`.
- Payload should be flexible JSON; map push channels (FCM/APNs/SMS) server-side.