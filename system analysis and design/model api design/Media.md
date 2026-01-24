# Media

Fields:
- id (int)
- ad_id (int|null)
- model_type (string|null)
- model_id (int|null)
- url (string)
- type (enum: image|video|360)
- thumbnail_url (string|null)
- meta (json|null)
- created_at (datetime)

Relations:
- belongsTo: Ad (polymorphic attachable)

Example JSON:
```json
{
  "id": 3012,
  "ad_id": 5678,
  "url": "https://cdn.smartcars.local/user-ads/hyundai1.jpg",
  "type": "image"
}
```

API Notes:
- Included in ad create/update payloads; use background worker to upload to CDN and generate thumbnails.
- Support signed URLs for uploads and moderation checks.