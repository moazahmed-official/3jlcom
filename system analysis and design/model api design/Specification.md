# Specification

Fields:
- id (int)
- name_en (string)
- name_ar (string)
- type (enum: text|number|select|boolean|date)
- values (json|null)
- image (url|null)
- created_at (datetime)
- updated_at (datetime)

Relations:
- belongsTo: SpecificationGroup or Category
- usedBy: Ad (via dynamic fields)

Example JSON:
```json
{
  "id": 21,
  "name_en": "Fuel Type",
  "type": "select",
  "values": ["Petrol", "Diesel", "Electric"]
}
```

API Notes:
- Endpoints: `GET /api/admin/specifications`, `POST /api/admin/specifications`.
- Dynamic field definitions should be versioned per country/category.