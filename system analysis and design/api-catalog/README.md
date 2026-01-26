# API Catalog — README

Location: `system analysis and design/api-catalog/`

Overview
- This folder contains the OpenAPI specs and endpoint guides for the project's REST API.
- API version: v1

Base URL (local)
- `http://localhost/v1`

Authentication (login)
- Endpoint: `POST /v1/auth/login`
- Body (JSON): `{ "phone": "+201001234567", "password": "secret-password" }`
- Returns a `Bearer` token in `data.token`.

Quick curl (login)
```bash
curl -X POST 'http://localhost/v1/auth/login' \
  -H 'Content-Type: application/json' \
  -d '{"phone":"+201001234567","password":"secret-password"}'
```

Logout
```bash
curl -X POST 'http://localhost/v1/auth/logout' \
  -H 'Authorization: Bearer <token>'
```

Running the auth tests
```bash
php artisan test --filter=AuthLoginTest
```

See the `openapi/` folder for per-endpoint documentation (curl examples and request/response schemas).
# API Catalog - usage and CI

This folder contains the OpenAPI fragments and two entrypoints:

- `openapi.yaml` — merged entrypoint referencing per-resource fragments (source-of-truth).
- `openapi.bundle.yaml` — fully dereferenced single-file OpenAPI spec for tools that cannot resolve external $ref.

Recommended CI steps (example using `swagger-cli` and `openapi-generator`):

1. Install tools (CI runner):

```bash
# install via npm
npm install -g @apidevtools/swagger-cli openapi-generator-cli
```

2. Validate the merged spec (resolves external refs):

```bash
# validates and resolves refs
swagger-cli validate openapi.yaml
```

3. Produce a bundled / dereferenced file (if you prefer to generate artifacts from fragments):

```bash
swagger-cli bundle api-catalog/openapi.yaml --outfile api-catalog/openapi.bundle.yaml --type yaml
```

4. (Optional) Generate SDK or server stubs from the bundle:

```bash
openapi-generator-cli generate -i api-catalog/openapi.bundle.yaml -g typescript-axios -o generated/clients/web
```

CI job example (GitHub Actions snippet):

```yaml
name: OpenAPI CI
on: [push]
jobs:
  openapi:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Setup Node
        uses: actions/setup-node@v4
        with:
          node-version: '18'
      - name: Install tools
        run: npm install -g @apidevtools/swagger-cli openapi-generator-cli
      - name: Validate fragments and merged spec
        run: |
          swagger-cli validate api-catalog/openapi.yaml
      - name: Bundle dereferenced spec
        run: |
          swagger-cli bundle api-catalog/openapi.yaml --outfile api-catalog/openapi.bundle.yaml --type yaml
      - name: Upload bundle artifact
        uses: actions/upload-artifact@v4
        with:
          name: openapi-bundle
          path: api-catalog/openapi.bundle.yaml
```

Notes:
- Keep fragments under `api-catalog/openapi/` as the source-of-truth.
- Use `openapi.yaml` in local editing and CI validation. Produce `openapi.bundle.yaml` in CI for downstream consumers.
- If you add operation-level `x-permissions`, keep them in fragments so the merged/dereferenced bundle preserves RBAC metadata.
