# Daily Samvad production deployment

This runbook covers the Laravel application only. The web-server document root must point to the project's `public` directory. Never expose the repository root.

## Requirements

- PHP 8.3 or newer with Ctype, cURL, DOM/XML, Fileinfo, Filter, Intl, Mbstring, OpenSSL, PDO MySQL, Session, Tokenizer, and XMLWriter.
- MySQL 8.
- Composer 2.
- A current Node.js LTS release and npm compatible with the committed lockfile.
- HTTPS in production.
- Write access for the web and CLI users to `storage` and `bootstrap/cache`.

## Environment

Create `.env` on the server; do not commit it. At minimum configure `APP_NAME`, `APP_ENV=production`, `APP_KEY`, `APP_DEBUG=false`, `APP_URL=https://dailysamvad.in`, `APP_TIMEZONE`, locale values, database credentials, cache/session/queue drivers, filesystem disk, logging, mail, organization details, and any enabled advertisement or import settings.

Set `SESSION_SECURE_COOKIE=true` on HTTPS. Use a unique `CACHE_PREFIX` when a cache backend is shared. A daily log channel with an appropriate production log level is recommended. `ASSET_URL` is optional and should only be set when assets are deliberately served from another trusted origin.

## Before deployment

1. Take and verify a database backup.
2. Confirm persistent `storage/app/public` media is backed up and not part of a disposable release directory.
3. Review pending migrations and their rollback implications.
4. Confirm `.env`, storage, and database backups will not be overwritten by the release.

## Release workflow

Run from the project root, stopping immediately if any command fails:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan down --render="errors::503"
php artisan migrate --force
php artisan storage:link
php artisan optimize
php artisan up
```

`php artisan optimize` builds configuration, event, route, and view caches. If running the cache steps separately, use:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

If a supervised queue worker is active for a queued feature, run `php artisan queue:restart` after the release. No scheduled commands are currently registered, so no scheduler cron is required. Add the standard once-per-minute scheduler cron only when scheduled tasks are introduced.

Do not run import commands as part of a routine web release. Do not use destructive Git cleanup, replace `.env`, reset the database, or delete persistent storage.

## Rollback

Prefer switching the web server back to the previous immutable release and restoring compatible cached configuration. Database rollback must be evaluated migration by migration; never run a blanket rollback against production. Restore a verified backup when a data migration cannot be safely reversed.

If deployment fails while maintenance mode is active, correct or roll back the release before running `php artisan up`.

## Web-server and storage safety

- Point Apache/Nginx only at `public` and disable directory listing.
- Preserve Laravel's front-controller rules.
- Configure Nginx or the hosting platform to deny execution of PHP-like files below `/storage`; the repository's storage `.htaccess` provides the equivalent defense for Apache when overrides are enabled.
- Run `php artisan storage:link` once per persistent release layout and verify `/storage` resolves uploaded media.
- Deny public access to `.env`, logs, SQL dumps, backups, source maps not intended for production, and repository metadata.
- Configure trusted proxies/hosts at the hosting layer once the load-balancer and canonical host topology is known.

## Queue, cache, and logs

The application supports database-backed cache, sessions, and queues. Ensure their tables exist when those drivers are selected. Redis may be selected later without application changes. Do not start a worker unless a deployed feature dispatches jobs; when required, use a process supervisor and the `queue:work` options appropriate to the hosting environment.

Rotate and retain logs according to operational policy. Never log credentials, environment contents, or imported source passwords.

## Post-deployment checks

- `GET /up` returns HTTP 200 without diagnostic details.
- Homepage, an article, category, tag, author, date archive, search, and empty search load successfully.
- `/robots.txt`, `/sitemap.xml`, and `/feed.xml` return the expected content types and production-domain URLs.
- `/admin` redirects to authentication and does not expose debug output.
- A missing URL returns the branded 404 page.
- Static assets load from the Vite manifest with no browser-console errors.
- Featured images, archive images, advertisements, pagination, and mobile navigation render correctly.
- `APP_DEBUG` is false and no `public/hot` file exists.
