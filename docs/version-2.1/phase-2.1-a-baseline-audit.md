# Phase 2.1-A Baseline Audit

Audit date: 2026-07-24. Scope: read-only Version 2.1 baseline. Detailed role, dashboard, workflow, performance, risk, and readiness evidence is in the companion documents in this directory.

## Executive baseline

Daily Samvad is a Laravel 13 monolith using Blade/Livewire for public and administrative interaction, Filament for administration, Eloquent models/policies, dedicated public query objects, SEO services, a filesystem-aware media layer, and a protected WordPress import subsystem. The live local dataset is small (100 posts, 84 media, 5 users), but the architecture already includes editorial permissions, subscriber accounts, database cache/session/queue tables, sitemap caching, a queued IndexNow job, and dormant analytics storage.

Readiness is **READY WITH CONDITIONS** because the full test run stops on an HTTP 419 failure, Redis has no available client, scheduled posts have no publisher schedule, and several historical staff roles cannot access Filament.

## Git and repository

- Branch from `.git/HEAD`: `main`.
- Local and `origin/main` ref: `7b7b42bd9e3caaaac02ef3f41991e0d4851f8c25`; therefore stored refs are equal (0 ahead/0 behind relative to the last fetch).
- Reflog commit message: `fixed seo and media`.
- Origin: `https://github.com/aman-mandhar/dailysamvad-new.git`.
- No Version 2.1 branch ref exists under readable local/remote refs.
- Existing roadmap/audit-adjacent documents include `docs/ROADMAP.md`, `ARCHITECTURE.md`, `DATABASE.md`, `DEPLOYMENT.md`, media/import/launch/staging documents, and this Phase 2.1 prompt.
- Git CLI status was blocked by repository ownership safety for the audit identity. Exact pre-documentation modified/untracked state could not be independently certified. The audit changed only the seven required documentation files.

## Framework and packages

| Component | Verified version/status |
|---|---|
| PHP | 8.3.16 CLI |
| Laravel | 13.20.0 |
| Filament | 5.6.8 |
| Livewire | 4.3.3 |
| Spatie Permission | 8.3.0 |
| Composer | 2.9.2 |
| Node.js / npm | 22.17.1 / 10.9.2 installed; project declares no engines |
| Frontend | Vite 8, Tailwind CSS 4, `laravel-vite-plugin` 3.1, `@tailwindcss/vite` 4 |
| Redis | Laravel configuration exists; `ext-redis` absent; Predis not installed |
| Scout/search engine | Not installed |
| Image processing | GD/EXIF PHP extensions available; no Intervention/Image or optimizer/media-library package installed |
| Queue packages | Laravel core only; no Horizon/Supervisor repository configuration found |
| Analytics/caching packages | No dedicated analytics or response/full-page cache package found |

## Authentication

- Public registration, login/logout, forgot-password, and reset-password controllers/routes exist with Form Requests and throttling.
- Email verification is not enabled: `User` does not implement `MustVerifyEmail`, and no verification routes were found.
- Filament has a separate `/admin/login` page using the same web guard/user model; password reset/registration are not enabled in panel configuration.
- `User` uses `HasRoles`, `Notifiable`, `HasFactory`, and implements `FilamentUser`. Panel access requires `is_active` and `access admin panel`.
- Public authenticated account routes also require `EnsureUserIsActive`.
- `DashboardRedirector` directs staff with panel access to Filament and subscribers to the public dashboard.
- Two login surfaces exist by design; no duplicate credential store was found. Their destination behavior differs.

## Roles, dashboard, and workflow

See `current-role-permission-matrix.md`, `current-dashboard-map.md`, and `current-editorial-workflow.md`. Key findings: 9 roles/32 permissions exist; three historical manager/reviewer roles are unreachable in Filament; workflow validation is centralized but rejection, audit history, notifications, reviewer assignment, and automatic scheduled publication are incomplete.

## Cache and Redis

- Actual local runtime: database cache, database session, database queue.
- Cache prefix is environment-derived; Redis database prefix defaults to slugged application name plus `-database-`.
- Tables `cache`, `cache_locks`, and `sessions` exist; counts were 1 cache entry and 1 session.
- Application caching is limited to sitemap/robots XML through `SitemapCache`/`SitemapManager`. A versioned `seo:sitemaps:*` key family supports targeted invalidation from post/taxonomy observers.
- No fragment cache, full-page cache, response-cache package, general cache warming, or explicit cache fallback service was found. Sitemap warming/clearing/validation console commands exist.
- Redis connections for default/cache/queue are configured but unusable in the audited PHP runtime because no Redis client is available. No Redis connection was attempted.

## Queues

- Database queue is active; `jobs`, `job_batches`, and `failed_jobs` migrations/tables exist. Counts: 0 jobs, 0 failed jobs.
- Only `SubmitIndexNowUrls` implements `ShouldQueue`; it uses the default queue, 3 tries, 15-second timeout, and 60/300-second backoff.
- No unique jobs, batches in application code, job middleware, named queue assignment, Horizon, Supervisor config, or scheduled jobs were found.
- IndexNow is feature-flagged and disabled in `.env.example`. Imports, sitemap generation/warming, media upload, responsive derivative inspection, and workflow transitions run synchronously.
- Future classification: IndexNow → `seo`; due-post publisher → `publishing`; media transformation → `media`; importer work → `imports`; notifications → `notifications`; analytics aggregation → `analytics`; security/publication recovery → `critical`. These are recommendations only.

## Search

- Public search uses `SearchController` → `SearchRequest` → `ArchivePageQuery::forSearch` → shared archive Blade view.
- Input is trimmed, stripped/squished, validated to a configured 200-character maximum, and SQL wildcard characters are escaped.
- Published posts are searched using case/collation-dependent `%term%` `LIKE` predicates across title, excerpt, content, meta title, and meta description; results paginate 12 and eagerly load primary category/media.
- No admin-specific search service beyond Filament table searches, no full-text index, suggestions, query analytics, Scout, Meilisearch, Typesense, or explicit multilingual stemming/tokenization exists.
- Search feature tests cover Unicode, escaping, empty/no-result state, pagination, and exclusion of unpublished posts.

## Analytics

- Posts have a raw `views_count`; dashboards/sidebar consume it, but no application increment path was found.
- `post_visits` can store authenticated/UUID visitor, session, IP, user-agent, referrer/UTM fields, device/browser/platform, location, and timestamps. It has post/time and visitor/time indexes.
- There is no event collector, JavaScript tracker, cookie/UUID creator, referral parser, bot filter, consent/retention/anonymization service, scheduled aggregation, category/author/search analytics, or analytics dashboard.
- Counts: 0 post visits and 0 notifications/bookmarks. Current analytics therefore depend on dormant tables/raw counters, not an active service or third party.

## Media and images

- `media.old_wp_id` is unique; posts link with nullable `featured_media_id` and retain normalized `featured_image`, alt, and caption fields.
- Media stores disk/path/original reference, filename, MIME, size, dimensions, checksum, alt/caption/rights, uploader, missing state, and metadata. Paths have a `(disk,path)` index; checksum and missing state are indexed.
- Public and WordPress-import paths remain protected. The importer is idempotent and resolves `_thumbnail_id` through `media.old_wp_id` to Laravel media ID/path.
- Uploads support JPEG/PNG/WebP, bounded size, UUID filenames, SHA-256 duplicate detection, and original width/height. Existing-file/media integrity commands and tests exist.
- Responsive rendering consumes derivative metadata when present and supplies `srcset`, sizes, dimensions, async decoding, lazy loading by default, eager/high priority for above-the-fold hero/article imagery, and a textual fallback.
- No active thumbnail/conversion/compression/WebP conversion/AVIF/queue processor exists. Imported files were not inspected or changed.

## Frontend performance

- Query objects select columns and eager-load displayed relationships, reducing obvious N+1 risks.
- Homepage performs multiple bounded content/taxonomy/sidebar queries with overlapping published datasets and no general cache.
- Archive pages paginate; article related/navigation queries are bounded.
- Search wildcard scans are the clearest scaling risk. Dashboard aggregates and repeated homepage queries are secondary risks.
- Vite has one app CSS and JS entry; built application assets are 77,451-byte CSS and 4,904-byte JS. Fonts are locally built. No third-party analytics script was found.
- Advertisement images lazy-load; trusted article HTML restricts unsafe content/hosts. External embed performance was not measured.

## Database

Relevant tables include users/password resets/sessions, permission tables, posts/taxonomy pivots, media, post visits, bookmarks, notifications, cache/locks, jobs/batches/failed jobs, and import/domain tables. All migrations report as ran.

Read-only counts: posts 100; media 84; users 5; roles 9; permissions 32; post visits 0; jobs 0; failed jobs 0; cache 1; sessions 1; notifications 0; bookmarks 0.

Confirmed useful indexes include post `(status,published_at)` and `(status,scheduled_at)`, unique WordPress IDs, media `(disk,path)`/checksum/missing, session activity/user, and post-visit time composites. No full-text search index exists. Likely future indexes must be justified with measured queries; none were added.

## Tests

`php artisan test --stop-on-failure` ran for 91.388 seconds: 16 tests passed (40 assertions), then `SubscriberDashboardTest::test_profile_updates_only_allowed_fields_and_preserves_sensitive_account_data` failed because the response was HTTP 419 rather than a redirect. Runtime reports config/events/routes/views cached, a relevant environment fact; caches were not cleared because that is prohibited. Focused importer suites had already passed 28 tests/129 assertions in 84.612 seconds.

The full suite status is therefore failing/incomplete, not green. No test was modified during this audit.

## Risks and readiness

See `version-2.1-risk-register.md` and `version-2.1-implementation-readiness.md`. Final decision: **READY WITH CONDITIONS**.
