# Daily Samvad: Setup, Migration, and Command Handbook

This is the operational guide for the Laravel replacement of the local WordPress site at `http://localhost/dailysamvad-old`.

Run commands from the Laravel project root:

```powershell
cd F:\MYWEB\laragon\www\dailysamvad-new
```

> Never commit `.env`, database passwords, Firebase credentials, SQL backups, or production user data. Back up both databases and both media directories before a live or final migration.

## 1. Requirements

- PHP 8.3 or newer
- MySQL 8
- Composer 2
- Node.js and npm
- PHP extensions: Ctype, cURL, DOM/XML, Fileinfo, Filter, Intl, Mbstring, OpenSSL, PDO MySQL, Session, Tokenizer, and XMLWriter
- For production: HTTPS, a process supervisor, cron, and optionally Redis

Verify the tools:

```powershell
php --version
composer --version
node --version
npm --version
mysql --version
```

## 2. First-time Laravel setup

Install dependencies and create the local environment file:

```powershell
composer install
npm ci
Copy-Item .env.example .env
php artisan key:generate
```

After configuring `.env`, initialize the Laravel database and assets:

```powershell
php artisan migrate
php artisan db:seed
php artisan storage:link
npm run build
php artisan about
php artisan migrate:status
```

Start all local development processes:

```powershell
composer run dev
```

Or run them separately:

```powershell
php artisan serve
npm run dev
php artisan queue:work --tries=3
php artisan schedule:work
```

The admin panel is available at `/admin` after an authorized user has been created and assigned a role.

## 3. `.env` settings

Copy `.env.example` and edit `.env`. The following is a safe template; replace placeholders locally and never paste real secrets into documentation or Git.

### Application

```dotenv
APP_NAME="Daily Samvad"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost/dailysamvad-new
ASSET_URL=
APP_TIMEZONE=UTC
APP_DISPLAY_TIMEZONE=Asia/Kolkata
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
```

`APP_TIMEZONE` is the storage/application timezone. `APP_DISPLAY_TIMEZONE` controls editorial display. Decide and test this carefully before importing scheduled WordPress posts.

Production values must include:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-production-domain.example
SESSION_SECURE_COOKIE=true
SEO_ALLOW_INDEXING=true
```

Do not enable indexing on local or staging environments.

### Laravel MySQL database

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dailysamvad_new
DB_USERNAME=YOUR_LARAVEL_DB_USER
DB_PASSWORD=YOUR_LARAVEL_DB_PASSWORD
```

Create the empty target database using an authorized MySQL account:

```powershell
mysql -u root -p -e "CREATE DATABASE dailysamvad_new CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate
php artisan migrate:status
```

Do not run `migrate:fresh`, `db:wipe`, `migrate:reset`, or a blanket rollback against a database containing imported or production data.

### WordPress source database

The importer reads WordPress using a separate connection. It does not require replacing Laravel's `DB_*` settings.

```dotenv
WORDPRESS_DB_CONNECTION=mysql
WORDPRESS_DB_HOST=127.0.0.1
WORDPRESS_DB_PORT=3306
WORDPRESS_DB_DATABASE=YOUR_WORDPRESS_DATABASE
WORDPRESS_DB_USERNAME=YOUR_WORDPRESS_READ_USER
WORDPRESS_DB_PASSWORD=YOUR_WORDPRESS_DB_PASSWORD
WORDPRESS_DB_PREFIX=wp_
WORDPRESS_SITE_URL=http://localhost/dailysamvad-old
```

Use a read-only WordPress database account when possible. Change `WORDPRESS_DB_PREFIX` if the old site does not use `wp_`.

### WordPress uploads and importer

```dotenv
WORDPRESS_UPLOADS_DISK=
WORDPRESS_UPLOADS_PATH=ABSOLUTE_PATH_TO_OLD_WP_CONTENT_UPLOADS
IMPORT_MEDIA_DISK=public
IMPORT_MEDIA_PATH=wordpress/uploads
IMPORT_CHUNK_SIZE=500
IMPORT_PILOT_LIMIT=100
IMPORT_PILOT_ORDER=latest
IMPORT_DEFAULT_LANGUAGE=hi
IMPORT_DRY_RUN=false
IMPORT_RESUME=false
IMPORT_REPORT_DISK=local
IMPORT_REPORT_PATH=imports/reports
IMPORT_REDIRECT_DISK=local
IMPORT_REDIRECT_PATH=imports/redirects
```

When `WORDPRESS_UPLOADS_DISK` is blank, the importer builds a local filesystem rooted at `WORDPRESS_UPLOADS_PATH`; use the absolute path to the old site's `wp-content/uploads` directory. Only set `WORDPRESS_UPLOADS_DISK` when a named disk with the correct root exists in `config/filesystems.php`. Imported originals are stored beneath `storage/app/public/wordpress/uploads` by default. Do not rename imported files during the first migration.

### Storage, cache, sessions, queues, and Redis

Safe local defaults:

```dotenv
FILESYSTEM_DISK=local
MEDIA_DISK=public
MEDIA_LIBRARY_PATH=media/library
MEDIA_MAX_UPLOAD_KILOBYTES=10240
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
QUEUE_ARCHITECTURE_ENABLED=false
CACHE_ARCHITECTURE_ENABLED=false
```

Optional staged Redis configuration:

```dotenv
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
REDIS_QUEUE_DB=3
REDIS_PREFIX=dailysamvad:production:
CACHE_STORE=redis
CACHE_ARCHITECTURE_ENABLED=true
CACHE_ARCHITECTURE_STORE=redis
QUEUE_CONNECTION=redis
QUEUE_ARCHITECTURE_ENABLED=true
REDIS_QUEUE=default
```

Validate Redis and queue readiness before enabling them:

```powershell
php artisan redis:health
php artisan queue:health
php artisan cache:architecture inspect
```

### SEO

```dotenv
ORGANIZATION_NAME="Daily Samvad"
WEBSITE_NAME="Daily Samvad"
SEO_DEFAULT_SOCIAL_IMAGE=/images/seo/default-social.png
SEO_LOCALE_COUNTRY=IN
SEO_SITE_DESCRIPTION="Daily Samvad description"
SEO_SITEMAPS_ENABLED=true
SEO_NEWS_SITEMAP_ENABLED=true
SEO_IMAGE_SITEMAP_ENABLED=true
SEO_NEWS_PUBLICATION_NAME="Daily Samvad"
SEO_NEWS_LANGUAGE=pa
SEO_ALLOW_INDEXING=false
INDEXNOW_ENABLED=false
INDEXNOW_KEY=
```

Only set `SEO_ALLOW_INDEXING=true` and `INDEXNOW_ENABLED=true` after production URLs, redirects, robots rules, and sitemaps have been validated.

### Image optimization

```dotenv
IMAGE_OPTIMIZATION_ENABLED=false
MEDIA_DISK=public
```

Keep optimization disabled until original media has been imported and audited. Processing uses the `media` queue unless `--sync` is supplied.

### Analytics

```dotenv
ANALYTICS_ENABLED=true
ANALYTICS_BEACON_ENABLED=true
GA_MEASUREMENT_ID=
```

### Firebase push notifications

```dotenv
FIREBASE_WEB_API_KEY=
FIREBASE_WEB_AUTH_DOMAIN=
FIREBASE_PROJECT_ID=
FIREBASE_STORAGE_BUCKET=
FIREBASE_MESSAGING_SENDER_ID=
FIREBASE_WEB_APP_ID=
FIREBASE_MEASUREMENT_ID=
FIREBASE_VAPID_KEY=
FIREBASE_MESSAGING_PROJECT_ID=
FIREBASE_SERVICE_ACCOUNT_PATH=
FIREBASE_PUSH_QUEUE=push
PUSH_SENDING_ENABLED=false
PUSH_AUTO_PUBLISH_ENABLED=false
```

The web values are browser-visible. The service-account file is secret and must remain outside Git and outside the public web root. Enable sending only after configuration checks pass.

### YouTube and organization settings

```dotenv
YOUTUBE_PLAYLIST_ID=
YOUTUBE_API_KEY=
YOUTUBE_PLAYER_AUTOPLAY=true
YOUTUBE_PLAYER_MUTED=true
YOUTUBE_PLAYER_LOOP=true
ORGANIZATION_EMAIL=
ORGANIZATION_PHONE=
ORGANIZATION_FACEBOOK_URL=
ORGANIZATION_INSTAGRAM_URL=
ORGANIZATION_YOUTUBE_URL=
ORGANIZATION_WHATSAPP_URL=
```

After changing `.env`, clear cached configuration:

```powershell
php artisan optimize:clear
php artisan config:show app
php artisan about
```

## 4. Migrating the old WordPress UI/UX to Laravel

UI migration is a design and implementation process; it is not performed by the database importer.

### Step 1: Preserve a visual reference

1. Keep `http://localhost/dailysamvad-old` running.
2. Capture desktop, tablet, and mobile screenshots of:
   - Header and navigation
   - Homepage and each category section
   - Article page
   - Category, tag, author, date, and search archives
   - Footer, advertisements, errors, and E-paper
3. Record WordPress menu order, colors, fonts, spacing, breakpoints, advertisements, and widget behavior.
4. Do not copy plugin-generated markup blindly. Recreate the behavior with Blade components and the application's query/services layer.

Useful routes for comparison:

```text
Old: http://localhost/dailysamvad-old
New: http://localhost/dailysamvad-new
Preview: http://localhost/dailysamvad-new/frontend/foundation-preview
Admin: http://localhost/dailysamvad-new/admin
```

### Step 2: Map WordPress areas to Laravel files

- Global layout: `resources/views/layouts/frontend.blade.php`
- Header/navigation/footer: `resources/views/components/frontend/`
- Homepage: `resources/views/home.blade.php`
- News cards and sections: `resources/views/components/news/`
- Article: `resources/views/posts/show.blade.php` and `resources/views/components/news/article/`
- Archives: `resources/views/archives/index.blade.php` and `resources/views/components/news/archive/`
- CSS tokens and components: `resources/css/frontend/`
- JavaScript behavior: `resources/js/frontend/`
- Page data/query composition: `app/Queries/`
- Homepage configuration: `config/homepage.php`
- Navigation/sidebar configuration: `config/frontend.php` and `config/sidebar.php`

### Step 3: Run the two sites and implement one component at a time

```powershell
composer run dev
```

Recommended sequence:

1. Design tokens, typography, colors, and containers
2. Header, logo, menus, mobile navigation, and ticker
3. Homepage hero and category layouts
4. News cards and responsive images
5. Article content, metadata, sharing, author, related news, and ads
6. Archive and search pages
7. Footer and policy links
8. Error pages, accessibility, and responsive behavior

After each change:

```powershell
npm run build
npm run test:js
php artisan test --filter=Frontend
.\vendor\bin\pint --test
```

Inspect registered public routes:

```powershell
php artisan route:list --except-vendor
```

Do not change post slugs or URL structure merely to simplify the new UI.

## 5. Migrating the WordPress database into Laravel

This importer maps WordPress data into Laravel's normalized schema. Do not import the WordPress SQL dump directly over the Laravel database.

### Step 1: Back up and validate both systems

Examples using safe placeholder filenames:

```powershell
mysqldump -u YOUR_USER -p YOUR_WORDPRESS_DATABASE > wordpress_before_migration.sql
mysqldump -u YOUR_USER -p dailysamvad_new > laravel_before_migration.sql
php artisan migrate:status
```

Store real backups outside the public web root and preferably outside the repository.

### Step 2: Confirm connections

```powershell
php artisan about
php artisan db:show
php artisan import:wordpress --dry-run --only=users --limit=10
```

If the dry run cannot connect, verify the `WORDPRESS_DB_*` values, table prefix, MySQL permissions, and that the old database is running.

### Step 3: Pilot import in dependency order

```powershell
php artisan import:wordpress --dry-run --only=users --limit=100
php artisan import:wordpress --dry-run --only=categories --limit=100
php artisan import:wordpress --dry-run --only=tags --limit=100
php artisan import:wordpress --dry-run --only=posts --status=publish --limit=100 --order=latest
```

Review the totals and logs before writing anything. Then run the live pilot:

```powershell
php artisan import:wordpress --only=users --limit=100
php artisan import:wordpress --only=categories --limit=100
php artisan import:wordpress --only=tags --limit=100
php artisan import:wordpress --only=posts --status=publish --limit=100 --order=latest
```

### Step 4: Full import

Run each importer independently so failures and counts are easy to audit:

```powershell
php artisan import:wordpress --only=users --chunk=500
php artisan import:wordpress --only=categories --chunk=500
php artisan import:wordpress --only=tags --chunk=500
php artisan import:wordpress --only=posts --status=publish --chunk=500 --order=oldest
```

Import other supported WordPress statuses only if required:

```powershell
php artisan import:wordpress --only=posts --status=draft --chunk=500 --order=oldest
php artisan import:wordpress --only=posts --status=pending --chunk=500 --order=oldest
php artisan import:wordpress --only=posts --status=future --chunk=500 --order=oldest
php artisan import:wordpress --only=posts --status=private --chunk=500 --order=oldest
```

The importer skips trash, revisions, attachments as posts, menu items, auto-drafts, and unknown statuses.

Resume after a safely diagnosed interruption:

```powershell
php artisan import:wordpress --only=posts --status=publish --chunk=500 --resume
```

Import specific WordPress records for investigation:

```powershell
php artisan import:wordpress --only=posts --ids=123 --ids=456
php artisan import:wordpress --only=media --ids=789
```

The importer is designed to be idempotent. Rerunning it updates or skips mapped records instead of intentionally duplicating them. Always review the counters despite that protection.

### Step 5: Verify content and relationships

```powershell
php artisan import:verify --limit=100
php artisan import:verify --format=csv --format=json --format=apache --format=nginx --format=laravel
```

Reports are stored under `storage/app/imports/reports`; redirect exports are stored under `storage/app/imports/redirects` by default.

Check representative content manually:

- Recent and old articles
- Hindi, Punjabi, and English text
- Original publication dates
- Authors
- Primary and secondary categories
- Tags
- Featured images and inline content images
- Draft and scheduled status
- Legacy URLs and canonical URLs

## 6. Migrating SEO

SEO import priority is:

1. Yoast SEO
2. Rank Math
3. Generated fallback metadata

Existing non-empty Laravel SEO values are preserved.

### Step-by-step SEO migration

First ensure posts exist, then dry-run and execute SEO import:

```powershell
php artisan import:wordpress --dry-run --only=seo --chunk=500
php artisan import:wordpress --only=seo --chunk=500
```

Verify imported metadata and export redirects:

```powershell
php artisan import:verify --format=csv --format=json --format=apache --format=nginx --format=laravel
```

Validate and warm local sitemap caches:

```powershell
php artisan seo:sitemaps:clear
php artisan seo:sitemaps:validate
php artisan seo:sitemaps:warm
```

Inspect these endpoints:

```text
/robots.txt
/sitemap.xml
/news-sitemap.xml
/image-sitemap.xml
/feed.xml
```

Before production indexing:

1. Review generated redirects for conflicts and loops.
2. Configure the reviewed redirect rules in Apache/Nginx or Laravel.
3. Confirm every important old URL resolves to the correct new article.
4. Confirm canonical URLs use the final HTTPS host.
5. Confirm Open Graph, Twitter Card, and JSON-LD values.
6. Set `SEO_ALLOW_INDEXING=true` only after the production site is ready.
7. Enable IndexNow only after setting a valid key and testing the key endpoint.

IndexNow and sitemap commands:

```powershell
php artisan seo:sitemaps:validate
php artisan seo:sitemaps:warm
php artisan seo:sitemaps:clear
```

## 7. Migrating WordPress images and media

### Step 1: Configure the source

For a normal local migration, leave `WORDPRESS_UPLOADS_DISK` blank and set `WORDPRESS_UPLOADS_PATH` to the absolute path of the old `wp-content/uploads` directory. If a correctly rooted named filesystem disk has been added to `config/filesystems.php`, set `WORDPRESS_UPLOADS_DISK` to that disk instead. Keep the original year/month hierarchy and filenames.

Confirm the public Laravel storage link:

```powershell
php artisan storage:link
```

### Step 2: Dry-run and import

```powershell
php artisan import:wordpress --dry-run --only=media --chunk=250
php artisan import:wordpress --only=media --chunk=250
```

For a pilot or targeted repair:

```powershell
php artisan import:wordpress --only=media --limit=100
php artisan import:wordpress --only=media --ids=123 --ids=456
php artisan import:wordpress --only=media --resume --chunk=250
```

### Step 3: Audit originals and references

These are read-only:

```powershell
php artisan media:audit
php artisan media:audit --chunk=250 --no-storage-scan
php artisan media:audit --fail-on-errors
php artisan media:report-orphans --dry-run
php artisan images:audit
php artisan images:audit --json
```

Media audit reports are written below `storage/app/media-audits`.

### Step 4: Generate optimized derivatives when ready

Enable `IMAGE_OPTIMIZATION_ENABLED=true`, ensure the `media` queue is running, and start with a bounded sample:

```powershell
php artisan images:process --limit=10 --sync
php artisan images:process --id=MEDIA_ID --sync
php artisan images:process --limit=100
php artisan queue:work --queue=media --tries=3
```

`--force` permits reprocessing; use it only when the replacement is intentional:

```powershell
php artisan images:process --id=MEDIA_ID --sync --force
```

Derivative cleanup is dry-run by default:

```powershell
php artisan images:cleanup
```

The following deletes only derivatives the command considers verified orphans. Back up storage and review the dry run first:

```powershell
php artisan images:cleanup --apply
```

Never rename or reorganize imported `wordpress/uploads` originals during the initial migration. Back up the database and `storage/app/public` together.

## 8. Users, roles, and admin access

Seed the defined roles and permissions:

```powershell
php artisan db:seed
php artisan permission:show
php artisan permission:cache-reset
```

Assign an existing role without removing a user's other roles:

```powershell
php artisan app:assign-role user@example.com admin
php artisan app:assign-role 123 editor
```

Use exact existing role names. The application includes super-admin, admin, editor, reviewer, reporter, contributor, SEO, media, analytics, and subscriber-oriented permissions.

## 9. Queues and scheduled publishing

Queued features include analytics, image processing, IndexNow, and push delivery. Scheduled posts are checked every minute.

Local workers:

```powershell
php artisan queue:work --tries=3
php artisan queue:work --queue=push,media,default --tries=3
php artisan schedule:work
```

Production cron must run Laravel's scheduler every minute:

```cron
* * * * * cd /path/to/dailysamvad-new && php artisan schedule:run >> /dev/null 2>&1
```

Run queue workers under Supervisor/systemd in production. After deploying new code:

```powershell
php artisan queue:restart
php artisan schedule:list
php artisan queue:health
```

Failure inspection and recovery:

```powershell
php artisan queue:failed
php artisan queue:retry JOB_ID
php artisan queue:retry all
php artisan queue:prune-failed --hours=168
```

`queue:clear`, `queue:flush`, and `queue:forget` delete queued or failed-job records. Do not run them without reviewing the exact impact.

## 10. Cache and Redis commands

```powershell
php artisan redis:health
php artisan cache:architecture inspect
php artisan cache:architecture warm
php artisan cache:architecture invalidate KEY
php artisan cache:clear
```

Framework cache commands:

```powershell
php artisan optimize
php artisan optimize:clear
php artisan config:cache
php artisan config:clear
php artisan route:cache
php artisan route:clear
php artisan view:cache
php artisan view:clear
```

Use a unique Redis/cache prefix for every environment.

## 11. Search commands

The current search engine is database-backed.

```powershell
php artisan search:audit
php artisan search:health
php artisan search:reindex
```

`search:reindex` is currently a bounded, non-destructive index-readiness audit rather than an external search-engine rebuild.

## 12. Analytics commands

```powershell
php artisan analytics:health
php artisan analytics:audit
php artisan analytics:aggregate --dry-run
php artisan analytics:aggregate
php artisan analytics:aggregate --date=2026-08-21
php artisan analytics:reconcile
php artisan analytics:prune
```

`analytics:prune` is a dry run. The following applies retention deletion and must be reviewed first:

```powershell
php artisan analytics:prune --apply
```

## 13. Push-notification commands

Start with sending disabled:

```dotenv
PUSH_SENDING_ENABLED=false
PUSH_AUTO_PUBLISH_ENABLED=false
```

Validate configuration and synchronize topics:

```powershell
php artisan push:health
php artisan push:sync-topics
php artisan push:test --check-config
```

After explicitly enabling push and selecting a test subscription:

```powershell
php artisan push:test --subscription=SUBSCRIPTION_ID
```

Maintenance:

```powershell
php artisan push:recover-stuck
php artisan push:prune-subscriptions
```

Review `php artisan help COMMAND` before applying maintenance options. Never test against the entire production audience.

## 14. Testing, formatting, and frontend builds

```powershell
php artisan test
php artisan test --filter=PostResourceTest
npm run test:js
npm run build
.\vendor\bin\pint
composer validate
composer audit
npm audit --omit=dev
```

Useful diagnostics:

```powershell
php artisan about
php artisan route:list --except-vendor
php artisan migrate:status
php artisan event:list
php artisan schedule:list
php artisan config:show app
php artisan pail
```

## 15. Production deployment

Before deployment, back up and verify the Laravel database, WordPress database, Laravel public storage, and WordPress uploads.

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan down --render="errors::503"
php artisan migrate --force
php artisan storage:link
php artisan optimize
php artisan queue:restart
php artisan up
```

Production requirements:

- Web-server document root points only to `public/`.
- `.env`, SQL files, logs, Git metadata, source code, and backups are not publicly accessible.
- `storage` and `bootstrap/cache` are writable without using `777`.
- PHP execution is denied beneath uploaded-media paths.
- `APP_DEBUG=false` and HTTPS secure cookies are enabled.
- Scheduler cron runs once per minute.
- Queue workers run under a supervisor for every enabled queued feature.
- `storage/app/public` persists between releases.

Post-deployment checks:

```powershell
php artisan about
php artisan migrate:status
php artisan schedule:list
php artisan queue:health
php artisan seo:sitemaps:validate
php artisan push:health
```

Then test `/up`, `/`, a recent and old article, category, tag, author, search, `/admin`, `/robots.txt`, `/sitemap.xml`, `/news-sitemap.xml`, `/image-sitemap.xml`, and `/feed.xml`.

## 16. Recommended final WordPress cutover sequence

1. Complete a full rehearsal on staging.
2. Record the last imported WordPress ID/time and verify reports.
3. Back up WordPress DB/uploads and Laravel DB/storage.
4. Freeze WordPress publishing or announce a controlled maintenance window.
5. Run final taxonomy/user synchronization.
6. Run the final incremental post import with the reviewed status/order/offset or IDs.
7. Run the final media import.
8. Run SEO import and redirect verification.
9. Audit media, URLs, dates, authors, taxonomies, and languages.
10. Build/cache the reviewed Laravel release.
11. Point the web server to Laravel's `public/` directory.
12. Apply reviewed redirects and confirm HTTPS/canonical host behavior.
13. Enable production indexing only after smoke tests pass.
14. Monitor logs, queues, scheduler, analytics, images, and sitemaps.
15. Keep WordPress and its backups intact until the Laravel launch is formally accepted.

Example final commands must be adapted to the recorded checkpoint; do not copy an arbitrary offset into production:

```powershell
php artisan import:wordpress --only=users --resume --chunk=500
php artisan import:wordpress --only=categories --resume --chunk=500
php artisan import:wordpress --only=tags --resume --chunk=500
php artisan import:wordpress --only=posts --status=publish --resume --chunk=500 --order=oldest
php artisan import:wordpress --only=media --resume --chunk=250
php artisan import:wordpress --only=seo --resume --chunk=500
php artisan import:verify --format=csv --format=json --format=apache --format=nginx --format=laravel
php artisan media:audit --fail-on-errors
php artisan seo:sitemaps:validate
php artisan seo:sitemaps:warm
```

## 17. Commands requiring special caution

These commands may delete data, discard queued work, or alter production behavior. They are not part of normal operation:

```text
php artisan db:wipe
php artisan migrate:fresh
php artisan migrate:reset
php artisan migrate:refresh
php artisan migrate:rollback
php artisan queue:clear
php artisan queue:flush
php artisan cache:clear
php artisan images:cleanup --apply
php artisan analytics:prune --apply
```

Never run them on staging or production without a verified backup, an exact impact review, and explicit approval.

## 18. Getting help for any command

```powershell
php artisan list --raw
php artisan help import:wordpress
php artisan help import:verify
php artisan help media:audit
php artisan help images:process
php artisan help push:test
```

Project-specific detailed references are also available under `docs/`, especially `importer.md`, `MEDIA-OPERATIONS.md`, `DEPLOYMENT.md`, `LAUNCH-CHECKLIST.md`, `MEDIA-ARCHITECTURE.md`, and the `docs/version-2.1/` runbooks.
