# نموذج التكنولوجيا المستخدمة / Technology Stack

تم اختيار التكنولوجيا الأساسية للمشروع كما يلي (اختصار وملاحظات تنفيذية):

- Back End: Laravel (PHP 8.1+; use Sanctum/Passport for API auth; queues with Redis)
- Front End: Laravel Blade (server-rendered views for SEO + progressive enhancement)
- Mobile Application: React Native (TypeScript recommended)
- Database: MySQL (8.x)
- Hosting: KVM4 (VM-based hosting; use Docker-compose or systemd deployments)
- Stores: App Store / Google Play (mobile builds and store listings managed per country)
- SMS Provider: per-country provider(s) (configure provider per country in admin; fallbacks recommended)
- Other APIs: Google Maps (geocoding, places), Carfax (or local car-history provider)

Recommended supporting services and notes:
- Search: Elasticsearch or Algolia for fast, faceted search and autocomplete (index per country)
- CDN & Media: CDN (Cloudflare/Akamai) + signed URLs for media; server-side image/video optimization
- Caching: Redis for sessions, queues, and cache
- Queues & Workers: Laravel Queues with Horizon for background jobs (imports, notifications, AI tasks)
- Media Storage: Object storage (S3-compatible) with lifecycle rules for optimization
- Payments: PCI-compliant gateway(s) per country; store minimal card data via tokenization
- Observability: Centralized logs (ELK or Grafana+Loki), error tracking (Sentry)
- AI Integrations: external AI APIs for image checks and description suggestions; isolate in microservice or queued workers

Operational notes:
- OTP/SMS must be configurable per-country; provide rate limits and retry rules.
- Admin should be able to configure API keys and provider priorities per country (SMS, Car History, Maps).
- For multi-country support, keep country configuration in DB (currency, city lists, provider mappings).
- Plan for migrations and zero-downtime deploys (rolling releases on KVM or container-based Blue/Green).

If you want, I can now:
- generate a `composer.json` + basic Laravel setup checklist,
- create Docker-compose and example env files for `KVM4` deployment,
- or produce a recommended provider list (SMS per country, CDN, search provider).
