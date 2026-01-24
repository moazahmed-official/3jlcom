# Normal Ads

- POST /normal-ads — Create a normal ad. Body: `NormalAdCreate`.
- GET /normal-ads — List normal ads. Query: `page`, `limit`, `brand_id`, `model_id`.
- GET /normal-ads/{adId} — Get ad details.
- PUT /normal-ads/{adId} — Update ad. Body: `NormalAdUpdate`.
- DELETE /normal-ads/{adId} — Delete ad.
- POST /normal-ads/{adId}/actions/republish — Republish an ad.

Media: upload via `/media` and include `media_ids` in ad bodies.

Schemas: `NormalAdCreate`, `NormalAdUpdate` in [api-catalog/openapi.bundle.yaml](api-catalog/openapi.bundle.yaml).
