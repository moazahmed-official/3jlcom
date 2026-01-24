**Final API Catalog**

**Purpose**
- **OpenAPI entry (`openapi.yaml`)**: the project-level, single entrypoint that references the fragment files in `api-catalog/openapi/`. It provides a consolidated surface for tooling (docs portals, generators) without duplicating the fragment sources.

**Structure & Ownership**
- **Fragment files**: each resource owns a fragment in `api-catalog/openapi/` (examples: `auth.yaml`, `users.yaml`, `normal_ad.yaml`, `media.yaml`, etc.). Each fragment declares its paths, local `components` and examples.
- **Merged bundle**: `api-catalog/openapi.yaml` contains path-level $ref pointers into the fragments (JSON Pointer style). Fragments remain authoritative — edit the fragment, not the merged file.
- **Servers/versioning**: All fragments declare the same server base `/api/v1`. The merged file also exposes `/api/v1` as the canonical server.

**x-permissions (RBAC)**
- Operation-level `x-permissions` entries are included in fragments to document required roles/scopes. These are documentation artifacts and should be enforced by your backend RBAC middleware. Example: an operation may include

- roles: ["admin","moderator"]
- scopes: ["reviews.read.any"]

Consume this field to generate permission matrices or to wire tests that assert RBAC behavior.

**How teams should consume this documentation**
- **Backend**: treat fragments as the source of truth. Workflow: edit fragment -> validate (OpenAPI linter) -> run codegen (optional) -> implement handlers following contracts. Prefer server-stub generation (openapi-generator) for fast bootstrapping.
- **Frontend**: generate SDKs or API clients from `api-catalog/openapi.yaml` (or use the resolved/bundled file if your tooling requires single-file schemas). Use example request/response shapes in fragments for QA fixtures and mock servers.
- **QA / Testers**: use the OpenAPI examples and schema validations to drive contract tests. Automate validation of responses against schemas and use the `x-permissions` matrix to verify access control tests.

**Recommended workflow & CI checks**
- Edit fragment(s) in `api-catalog/openapi/` only. Commit with a descriptive message.
- CI steps (recommended):

```bash
# Validate OpenAPI fragments and merged file
swagger-cli validate api-catalog/openapi.yaml

# Produce a fully-resolved bundle (if needed for tools)
swagger-cli bundle api-catalog/openapi.yaml -o api-catalog/openapi.bundle.yaml --dereference

# (Optional) Generate client SDK
openapi-generator-cli generate -i api-catalog/openapi.bundle.yaml -g typescript-axios -o ./clients/frontend
```

- Note: Tools that cannot resolve external $ref must use a dereferenced bundle (`openapi.bundle.yaml`). Keep the bundle as a CI artifact — do not edit it directly.

**Docs & portal publishing**
- Publish `openapi.yaml` (or the dereferenced bundle) to your API docs portal (Swagger UI, Redoc, Stoplight). Link to individual fragment sources in the repo for developers.

**Versioning & releases**
- Bump the `info.version` and tag a release when schema-breaking changes occur. Maintain a deprecation schedule and add `deprecated: true` and release notes for removed endpoints.

**Where files live (quick links)**
- OpenAPI fragments: [api-catalog/openapi/](api-catalog/openapi/)
- Merged entrypoint: [api-catalog/openapi.yaml](api-catalog/openapi.yaml)
- Migration map: [api-catalog/Migration_Map.md](api-catalog/Migration_Map.md)
- RBAC matrix: [RBAC_Matrix.md](RBAC_Matrix.md)

If you'd like, I can now produce a fully-dereferenced `openapi.bundle.yaml` as a CI artifact and add a short CI step in a `README.md` showing the exact commands for your environment. 
