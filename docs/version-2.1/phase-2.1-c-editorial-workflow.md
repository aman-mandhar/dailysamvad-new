# Phase 2.1-C editorial workflow

The pre-change audit found six states, generic editable status/timestamps, incomplete rejection/corrections/assignment metadata, no audit stream or notification, and no automatic publisher. Phase 2.1-C adds two states and an authoritative transactional service while preserving existing post IDs, authors, slugs, dates, WordPress identities, SEO, media references, routes, and redirects.

Filament status, schedule, and publication timestamps are display-only; dedicated authorized actions call the service. The table's bulk publish/archive operations also use it. The service validates state/input, locks the post, writes metadata and history atomically, and sends notifications after commit. Existing published/imported posts are not rewritten and null historical metadata remains supported.

Database work is additive: nullable workflow metadata plus `post_workflow_events`; no backfill. Large production tables should schedule the indexed-column migration in a normal maintenance window. Rollback removes only Phase C fields/history and cannot reconstruct deleted Phase C audit events, so back up before rollback.

Focused verification: `php artisan test tests/Feature/EditorialWorkflowTest.php`. Full verification: `php artisan test`. Deferred: dashboards, Redis/cache, analytics, search, image optimization, News-Man, email delivery, and production worker configuration. Remaining operational risk is ensuring cron and a database queue worker are continuously supervised.
