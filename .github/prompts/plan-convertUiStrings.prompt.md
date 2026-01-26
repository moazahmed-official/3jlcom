TL;DR — Replace hard‑coded UI text with translation keys, populate `lang/en.json` and `lang/ar.json`, inject translations for inline JS, then rebuild assets and verify pages. This lets Filament translation manager control strings and keeps frontend/server locales in sync.

Steps
1. Run a repository scan to collect hard-coded strings (grep for quoted text in Blade/JS).
2. Prioritize and edit high-impact files: `resources/views/layouts/app.blade.php`, `resources/views/landing.blade.php`, `resources/views/auth/login.blade.php`, `resources/views/student/subscriptions/index.blade.php`, `resources/views/admin/curriculum/_scripts.blade.php`.
3. Replace text in Blade with `__('namespace.key')` or `@lang('namespace.key')` and add matching keys to `lang/en.json` and `lang/ar.json`. Use nested keys (e.g., `auth.login`, `footer.product`).
4. For inline JavaScript strings, inject `window.trans` in `resources/views/layouts/app.blade.php` (server-rendered translations) and reference `window.trans` from scripts.
5. Clear caches and rebuild: run `php artisan view:clear`, `php artisan cache:clear`, `composer dump-autoload` (if adding seeders), and `npm run build`.
6. QA: visit changed pages, toggle locale via UI dropdown, verify translations and RTL layout for Arabic; open Filament translation manager to confirm keys appear.

Further Considerations
1. Dynamic/server messages (validation, controllers) should use the same JSON keys or `resources/lang/{locale}/validation.php` for consistency.
2. For bulk changes, prefer an automated script for safe replacements then manual review — Option A: automated grep+sed with a review branch / Option B: manual per-file edits.
3. Want me to (A) generate a prioritized list of all files/strings to convert, or (B) apply automated replacements for the top 20 files now?

Notes
- This plan is registered in the workspace TODOs so we can track progress.
- After you pick option A or B I will proceed and apply edits, then run the build and report results.
