# Ad

Fields:
- id (int)
- seller_id (int)
- ad_type (enum: normal|unique|caishha|findit|auction)
- title (string)
- description (text)
- category_id (int)
- city_id (int)
- country_id (int)
- status (enum: draft|pending|published|expired|removed)
- views_count (int)
- contact_phone (string|null)
- whatsapp_number (string|null)
- media_count (int)
- period_days (int)
- price_cash (decimal|null)
- installment_id (int|null)
- is_pushed_facebook (bool)
- created_at (datetime)
- updated_at (datetime)

Relations:
- belongsTo: Seller, Category, City, Country
- hasOne: Car, Installment
- hasMany: Media, CaishhaOffer, Bid, ViewStat, Report, Favorite

Example JSON:
```json
{
  "id": 5678,
  "seller_id": 45,
  "ad_type": "normal",
  "title": "2020 Hyundai Tucson GLX",
  "category_id": 2,
  "city_id": 3,
  "country_id": 1,
  "status": "published",
  "price_cash": 18500,
  "period_days": 30,
  "created_at": "2026-01-24T14:15:00Z"
}
```

API Notes:
- Polymorphic by `ad_type`. Recommend base `ads` table + subtype tables (unique_ads, caishha_ads, auction_ads) for type-specific fields.
- Common endpoints: `POST /api/user/normal_ad`, `GET /api/admin/ads`, `PUT /api/admin/ads/{id}`.