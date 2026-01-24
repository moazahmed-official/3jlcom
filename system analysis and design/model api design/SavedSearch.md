# SavedSearch

Fields:
- id (int)
- user_id (int)
- query_params (json)
- name (string|null)
- created_at (datetime)

Relations:
- belongsTo: User

Example JSON:
```json
{
  "id": 88,
  "user_id": 200,
  "name": "Toyota under 18k",
  "query_params": {"brand_id":12,"max_price":18000,"city_id":3}
}
```

API Notes:
- Endpoints: `POST /api/user/save-search`, `GET /api/user/save-search`, `DELETE /api/user/save-search/{id}`.
- Trigger notifications when matching results are published.