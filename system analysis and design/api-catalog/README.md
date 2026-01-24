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
