# Version 2.1 Risk Register

Audit date: 2026-07-24. “Blocks” means blocks the affected implementation phase, not this documentation audit.

| ID | Severity | Finding and evidence | Impact / data risk | Recommended phase | Blocks? |
|---|---|---|---|---|---|
| R1 | High | Full test suite stops at `SubscriberDashboardTest` with HTTP 419; runtime reports config/routes/events/views cached | Regression signal is incomplete; changes could ship without full coverage. No observed data loss | Test-baseline remediation before all feature phases | Yes |
| R2 | High | Scheduled status exists, but `routes/console.php` has no due-post publisher | Scheduled news can remain unpublished indefinitely; editorial timing risk | Workflow/queue phase | Yes for scheduled-publishing work |
| R3 | High | Redis client defaults to phpredis, but `ext-redis` and Predis are absent | Switching cache/session/queue to Redis would fail at runtime | Redis readiness phase | Yes for Redis activation |
| R4 | Medium | Reviewer, SEO manager, and media manager roles lack `access admin panel`; SEO manager also lacks `manage seo` | Named roles cannot use their intended administrative capabilities | RBAC/dashboard phase | Yes for role-specific dashboards |
| R5 | Medium | Reject/corrections permissions and rejected status exist, but workflow transitions/actions do not | Editorial correction loop is incomplete | Editorial workflow phase | Yes for workflow expansion |
| R6 | Medium | Workflow metadata fields exist but are not populated; no history table or notifications | Weak auditability/accountability; no direct data corruption | Editorial workflow phase | No |
| R7 | Medium | Search uses `%term%` LIKE across title, excerpt, content, meta title/description; no Scout/full-text index | Search latency and database load will scale poorly | Search phase | No at current 100-post volume |
| R8 | Medium | `post_visits` stores IP, user agent, referrer, location fields but no collection/privacy/retention/bot-filter service exists | Future privacy and retention exposure if collection is enabled ad hoc | Analytics/privacy phase | Yes for analytics collection |
| R9 | Medium | Dashboard uses 4–8 uncached aggregate queries per role render | Admin latency/load grows with table size | Dashboard/cache phase | No |
| R10 | Medium | Git CLI ownership protection prevents working-tree/ahead-behind verification under the audit identity | Exact repository dirtiness cannot be independently certified by this run | Repository hygiene before implementation | Yes for release certification |
| R11 | Low | Runtime local environment has debug enabled and indexing disabled | Appropriate locally, unsafe if copied to production; no evidence production uses it | Deployment readiness | No |
| R12 | Low | Media supports JPEG/PNG/WebP upload; no AVIF and no active compression/conversion package | Larger media and inconsistent responsive derivative coverage | Media optimization phase | No |
| R13 | Low | Database cache/session/queue share MySQL; one cache and one session record currently | Contention/cleanup risks at scale | Cache/queue phase | No |
| R14 | Informational | Existing query objects eagerly load displayed relationships and images implement lazy/eager priorities | Useful protected baseline | Preserve across phases | No |

Affected files include `app/Support/PostWorkflow.php`, `routes/console.php`, `config/database.php`, `config/cache.php`, `config/queue.php`, `database/seeders/RolesAndPermissionsSeeder.php`, `app/Queries/ArchivePageQuery.php`, `database/migrations/2026_07_20_000400_create_post_visits_table.php`, and `app/Filament/Widgets/*`.
