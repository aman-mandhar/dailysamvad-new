# Staging deployment and rollback rehearsal

Use this plan only on an isolated staging host. It does not authorize DNS changes, production document-root replacement, live WordPress writes, destructive database commands, or destructive media synchronization.

## Evidence record

Complete these fields before deployment:

```text
Repository URL:
Branch:
Commit hash:
Staging hostname:
Deployment user:
Web-server user/group:
Release root:
Current symlink:
Shared .env path:
Shared storage path:
Document root:
PHP binary/version:
Composer binary/version:
Node/npm versions:
Database host/name (do not record passwords):
Database charset/collation:
Media source and expected size/count:
Backup location outside public root:
```

Deployment must stop if Git source integrity, database isolation, backup integrity, or the staging host cannot be proven.

## Staging protection

Use `APP_ENV=staging`, `APP_DEBUG=false`, a staging-specific key and database, HTTPS, secure cookies, and a staging URL. Apply Basic authentication or an IP allowlist and also send `X-Robots-Tag: noindex, nofollow`. Do not submit staging robots or sitemaps to search engines.

Review the actual Nginx or Apache virtual host before enabling it. The root must be the release's `public` directory. Deny hidden files, repository metadata, environment files, source/configuration files, dumps, backups, logs, and PHP execution beneath `/storage`.

## Backup gate

Before migrations or media copying, create database, storage/media, `.env`, virtual-host, and relevant WordPress backups outside public roots. For each backup record timestamp, byte size, SHA-256 checksum, listing/readability result, and non-production restore-test result. A file's existence alone is not verification.

## Release deployment

Prefer immutable releases with shared environment and storage:

```text
/var/www/dailysamvad-laravel/
|-- current -> releases/<release-id>
|-- releases/
`-- shared/
    |-- .env
    `-- storage/
```

For a reviewed release, install from lockfiles with `composer install --no-dev --optimize-autoloader`, `npm ci`, and `npm run build`. Do not use update commands. Link shared `.env` and storage, grant only the web/deployment users required access to `storage` and `bootstrap/cache`, and never use recursive `777` permissions.

Run only safe Laravel operations:

```bash
php artisan down --render="errors::503"
php artisan migrate --force
php artisan storage:link
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan up
```

Verify maintenance mode is cleared. Restart supervised queue workers only if the deployed application actually dispatches queued work.

## Data and media verification

Record row counts for posts by status, categories, tags, authors, category/post and post/tag relationships, and media references. Compare them with the approved dump/import report. Verify foreign keys and representative Unicode content.

For media, record source and destination file counts and sizes. Review `rsync -avhn` output before an actual non-destructive synchronization. Never add `--delete` without separate explicit authorization. Verify the storage symlink, missing references, HTTP status, image dimensions, and server-level upload execution denial.

## Redirect matrix template

Do not activate bulk redirects until representative evidence and loop checks are reviewed.

| Old WordPress pattern | Laravel target | Proposed status | Query handling | Slash/host handling | Evidence/status |
|---|---|---:|---|---|---|
| `/{wordpress-post-path}/` | `/news/{preserved-slug}` or verified target | 301 | Preserve approved tracking parameters | One hop from HTTP/`www` | Pending staging data |
| `/category/{slug}/` | `/category/{slug}` | 301 | Preserve safe query parameters | Remove trailing slash once | Pending |
| `/tag/{slug}/` | `/tag/{slug}` | 301 | Preserve safe query parameters | Remove trailing slash once | Pending |
| `/author/{username}/` | `/author/{username}` | 301 | Preserve safe query parameters | Remove trailing slash once | Pending |
| Date archives | `/archive/{year}/{month?}/{day?}` | 301 | Preserve pagination where mapped | Avoid archive loops | Pending |
| Feed variants | `/feed.xml` | 301 | N/A | One canonical feed URL | Pending |
| Attachment URLs | Verified media or article URL | Case-by-case | Preserve only required parameters | Never send missing media home | Pending |

Missing content must return 404 or an evidence-backed relevant redirect, never a blanket homepage redirect.

## Smoke and measurement record

Test homepage, articles, all archive types, search variants, pagination, feed, robots, sitemap, `/up`, 404, 419, 429 without load testing, maintenance 503, and Filament authentication/editorial workflows. Record HTTP status, canonical, robots, H1 count, schemas, assets, media, ads, response duration, HTML size, and logs.

Use a real browser at every required viewport and record console/network errors, mixed content, overflow, layout shift, missing fonts, and failed resources. Compare representative recent, old, media-rich, embedded, Unicode, long-title, taxonomy, author, search, and date content with WordPress. Separate migration defects from frontend defects.

## Rollback rehearsal

Perform only on staging after backups have passed restore checks.

1. Record the active release ID, database state, media state, and start time.
2. Enter maintenance mode with the 503 view.
3. Switch `current` atomically to the previously verified release; do not reset or clean a working tree.
4. If a migration is incompatible, restore the verified pre-rehearsal staging database backup. Do not apply blanket migration rollback commands.
5. Restore media only from the verified archive when the rehearsal intentionally changed staging media.
6. Rebuild configuration, route, event, and view caches using the restored release and shared environment.
7. Restart a supervised queue worker only if one is in use.
8. Validate virtual-host/document-root and certificate behavior; DNS should not change during staging rehearsal.
9. Exit maintenance mode and run health, homepage, article, category, search, admin, media, redirect, robots, and sitemap smoke tests.
10. Inspect redacted application, web-server, PHP-FPM, and database logs.

Record duration, every command, operator, release IDs, backup checksums, problems, data-integrity result, and final staging state. A successful rehearsal requires intact data/media, healthy smoke tests, and no unexplained critical log entries.

## Abort thresholds

Rollback rather than continue when the source commit is unknown, backup restore is unproven, database isolation is uncertain, migrations fail, APP_DEBUG is enabled, HTTPS or secure cookies fail, the document root exposes repository files, article/admin routes fail, media is broadly missing, canonicals use the wrong host, sitemap XML is invalid, or critical errors repeat in logs.
