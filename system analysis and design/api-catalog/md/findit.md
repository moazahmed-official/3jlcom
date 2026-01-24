# Findit (ad type)

- POST /findit — Create a Findit ad/request. Auth: Bearer. Schema: `FinditCreate`.
- GET /findit — List Findit requests. Query: `page`, `limit`.
- GET /findit/{id} — Get Findit request details.
- PUT /findit/{id} — Update Findit request (owner/admin). Schema: `FinditUpdate`.
- DELETE /findit/{id} — Delete Findit request (owner/admin).
- POST /findit/{id}/offers — Submit an offer for a Findit request. Schema: `FinditOfferCreate`.
- GET /findit/{id}/offers — List offers for a Findit request.

RBAC: operations include `x-permissions` metadata in the OpenAPI fragments to indicate roles and scopes for enforcement.

Schemas: `FinditCreate`, `FinditUpdate`, `FinditOfferCreate` in [api-catalog/openapi.bundle.yaml](api-catalog/openapi.bundle.yaml).
