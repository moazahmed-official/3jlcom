# Unique Ads

- POST /unique-ads — Create unique ad. Body: `UniqueAdCreate`.
- GET /unique-ads — List unique ads (owner/admin).
- GET /unique-ads/{adId} — Get ad details.
- PUT /unique-ads/{adId} — Update ad. Body: `UniqueAdUpdate`.
- DELETE /unique-ads/{adId} — Delete ad.
- POST /unique-ads/{adId}/actions/feature — Promote/feature an ad.

Schemas: `UniqueAdCreate`, `UniqueAdUpdate` in [api-catalog/openapi.bundle.yaml](api-catalog/openapi.bundle.yaml).
