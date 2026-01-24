# Sliders

- GET /sliders — List sliders (filters: `location`, `status`).
- POST /sliders — Create slider (admin). Schema: `SliderCreate`.
- GET /sliders/{sliderId} — Get slider details.
- PUT /sliders/{sliderId} — Update slider (admin). Schema: `SliderUpdate`.
- DELETE /sliders/{sliderId} — Delete slider (admin).
- POST /sliders/{sliderId}/actions/activate — Activate slider (admin).
- POST /sliders/{sliderId}/actions/deactivate — Deactivate slider (admin).

Schemas: `SliderCreate`, `SliderUpdate`, `SliderResponse` in [api-catalog/openapi.bundle.yaml](api-catalog/openapi.bundle.yaml).
