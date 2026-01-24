# Category

Fields:
- id (int)
- name_en (string)
- name_ar (string)
- status (enum: active|inactive)
- specs_group_id (int|null)
- created_at (datetime)
- updated_at (datetime)

Relations:
- hasMany: Ad, SpecificationGroup

Example JSON:
```json
{
  "id": 2,
  "name_en": "Cars",
  "name_ar": "سيارات",
  "status": "active"
}
```

API Notes:
- Controls dynamic fields per category; admin endpoints: `GET /api/admin/categories`, `POST /api/admin/categories`.  