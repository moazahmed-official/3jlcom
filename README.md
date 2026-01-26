<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="300" alt="Laravel Logo"></a></p>

# 3jlcom

A Laravel-based classifieds/ads platform prototype used for API design, marketplace flows and integration experiments.

> [!note]
> This repository is an application scaffold and working reference for features such as multi-type ads (normal, unique, caishha), media handling, packages/subscriptions, and admin tooling.

## Quick links

- Docs: `system analysis and design/api-catalog/README.md` and `docs/README.md`
- Migrations: `database/migrations/`
- Seeders: `database/seeders/`
- Models: `app/Models/`

## Features

- Multiple ad types split into type-specific tables (`normal_ads`, `unique_ads`, `caishha_ads`)
- Media management and ad media linking
- Roles and packages/subscriptions
- API-first design with OpenAPI specs (see `system analysis and design/api-catalog/openapi.bundle.yaml`)

## Getting started (development)

1. Install dependencies

```bash
composer install
npm install
```

2. Copy environment and set secrets

```bash
# Linux / macOS
cp .env.example .env
# Windows (PowerShell)
copy .env.example .env
php artisan key:generate
```

3. Run migrations and seed sample data

```bash
php artisan migrate:fresh --seed
```

4. Start dev server

```bash
php artisan serve
# or use Valet / Sail depending on your setup
```

## Running tests

Run the test suite with:

```bash
php artisan test
# or
./vendor/bin/phpunit
```

## Project structure (high level)

- `app/Models/` — Eloquent models (Ads, Media, Users, type-specific models)
- `database/migrations/` — schema and migration scripts
- `database/seeders/` — seed data used in development
- `routes/` — API and web route definitions
- `system analysis and design/` — design docs, OpenAPI specs, and API catalog
- `docs/` — writing guidelines, ADR template and project docs

## Architecture notes

- Ads are normalized: common fields live on `ads` while price and type-specific attributes live in type tables (`normal_ads`, etc.). See `database/migrations/2026_01_26_000034_split_ads_into_type_tables.php` for migration logic that performs this split.
- API is described in OpenAPI yaml files under `system analysis and design/api-catalog/openapi/`.

## Troubleshooting

> [!warning]
> If seeding fails with a missing column error for `price_cash`, the fix is to ensure seeders insert type-specific data into the correct type table. The project already contains migrations that move `price_cash` into `normal_ads`.

Common commands:

```bash
# Recreate database and seed
php artisan migrate:fresh --seed

# Run only seeder
php artisan db:seed --class=SampleDataSeeder
```

## Documentation and ADRs

Project docs and API design are stored under:

- `docs/` — writing guidelines and ADR template
- `system analysis and design/` — API catalog, OpenAPI specs, and architecture documents

## Next steps you might want to run

```bash
# Start dev server and watch frontend assets
npm run dev
php artisan serve
```

---

If you'd like, I can also:

- Add an example ADR in `docs/` using the ADR template
- Convert the `SampleDataSeeder` to use Eloquent relations for clarity
- Expand `docs/technical-writing-guidelines.md` with examples

Tell me which of the above you'd like me to do next.
