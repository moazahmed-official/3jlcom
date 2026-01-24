# Caishha

- POST /caishha — Create a Caishha ad (offers-first). Body: `CaishhaCreate`.
- GET /caishha — List Caishha ads. Query: `page`.
- GET /caishha/{adId} — Get details.
- PUT /caishha/{adId} — Update.
- DELETE /caishha/{adId} — Delete.
- POST /caishha/{adId}/offers — Create an offer on a Caishha ad. Body: `CaishhaOfferCreate`.

Schemas: `CaishhaCreate`, `CaishhaOfferCreate` in [api-catalog/openapi.bundle.yaml](api-catalog/openapi.bundle.yaml).
