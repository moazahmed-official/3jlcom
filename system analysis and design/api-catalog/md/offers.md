# Offers

- POST /offers — Create an offer. Body: `OfferCreate`.
- GET /offers — List offers. Query: `page`.
- GET /offers/{offerId} — Get offer details.
- PUT /offers/{offerId} — Update offer. Body: `OfferUpdate`.
- DELETE /offers/{offerId} — Delete offer.

Schemas: `OfferCreate`, `OfferUpdate` in [api-catalog/openapi.bundle.yaml](api-catalog/openapi.bundle.yaml).
