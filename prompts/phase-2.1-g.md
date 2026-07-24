# Daily Samvad — Version 2.1

## Phase 2.1-G: Cache Architecture, Full-Page Caching and Invalidation

You are working on the existing Daily Samvad Laravel application.

The application currently uses:

* Laravel 13
* Filament 5
* Livewire 4
* MySQL
* Spatie Laravel Permission
* Permission-driven RBAC established in Phase 2.1-B
* Editorial workflow completed in Phase 2.1-C
* Dynamic role dashboards completed in Phase 2.1-D
* Dashboard UI/UX redesign completed in Phase 2.1-E
* Redis foundation and safe activation completed in Phase 2.1-F

Phase 2.1-F established the Redis infrastructure, client, secure connectivity, prefixes, logical database allocation, cache-store verification, lock verification and operational runbooks.

This phase must now design and implement a safe, measurable and production-ready cache architecture for Daily Samvad.

---

# 1. Primary Objective

Implement a layered caching strategy that improves public-site and dashboard performance without serving stale, unauthorized or incorrect content.

The implementation must cover:

* Cache architecture
* Cache key standards
* Cache TTL standards
* Cache invalidation
* Public page caching
* Full-page response caching where safe
* Fragment and query-result caching
* Dashboard metric caching
* Cache locking
* Cache warming
* Cache observability
* Safe bypass rules
* Cache failure handling
* Automated tests
* Operational documentation

The phase must preserve correctness before maximizing hit rate.

---

# 2. Core Principle

Never cache data merely because it is expensive.

A cache entry may be introduced only when all of the following are understood:

* Source of truth
* Authorization boundary
* Cache key
* Cache scope
* TTL
* Invalidation trigger
* Failure behavior
* Stampede risk
* Deployment behavior
* Test strategy

If invalidation cannot be defined safely, do not cache the item permanently.

---

# 3. Existing Baseline Audit

Before changing anything, audit:

* Current default cache store
* Redis cache-store configuration
* Existing `Cache::` usage
* Existing cache helpers
* Existing custom cache services
* Existing response-cache middleware or package
* Existing route middleware
* Public routes
* Authenticated routes
* Filament routes
* API routes
* Search routes
* Article routes
* Homepage routes
* Category routes
* Tag routes
* Author routes
* Date archives
* Sitemap routes
* Robots route
* Feed routes
* Google News routes
* Preview routes
* Login, signup and password-reset routes
* Existing cache invalidation
* Existing model observers
* Existing workflow events
* Existing publication events
* Existing SEO update flows
* Existing media replacement flows
* Existing navigation/menu configuration
* Current query count and response-time baseline
* Current production cache behavior
* Existing tests related to caching

Document the existing state before implementation.

---

# 4. Protected Boundaries

Do not disturb:

* Existing users
* Existing passwords
* Existing role assignments
* Existing permissions
* Existing policies
* Existing editorial workflow
* Existing workflow-history records
* Reviewer assignments
* Scheduled publishing
* Imported WordPress posts
* Imported WordPress media
* Featured-media mappings
* SEO metadata
* Public routes
* Public URLs
* Slugs
* Legacy redirects
* Publication dates
* Canonical URLs
* Structured data
* Sitemaps
* Robots rules
* Google News behavior
* Existing session behavior
* Existing queue behavior
* Existing production deployment configuration
* `.env` secrets
* Existing database records

Do not run destructive commands.

Prohibited commands include:

```bash
php artisan migrate:fresh
php artisan migrate:refresh
php artisan migrate:reset
php artisan db:wipe
git reset --hard
git clean -fd
composer update
npm update
redis-cli FLUSHALL
redis-cli FLUSHDB
```

Do not delete unknown Redis keys.

Do not clear all production cache keys without a documented reason and controlled rollout.

---

# 5. Scope of This Phase

This phase includes:

* Redis-backed application caching
* Cache key architecture
* Cache versioning
* Cache invalidation services
* Public response caching
* Full-page caching for safe anonymous routes
* Fragment caching
* Query-result caching
* Dashboard aggregate caching
* Cache locks
* Stampede prevention
* Cache warming
* Stale-while-revalidate where appropriate
* Cache bypass rules
* Cache diagnostics
* Cache metrics
* Cache-related tests
* Production cache runbooks

This phase does not include:

* Redis queue activation
* Laravel Horizon
* Queue-worker optimization
* Search-engine replacement
* Analytics event collection
* Image conversion pipeline
* Public frontend redesign
* News-Man integration
* CDN configuration changes
* Nginx FastCGI cache unless explicitly audited and approved
* Multi-server cache-cluster architecture
* Redis Cluster
* Redis Sentinel

---

# 6. Cache Layers

Design a layered cache architecture.

Recommended layers:

```text
Layer 1:
Per-request memoization

Layer 2:
Query-result and domain-data cache

Layer 3:
Fragment/component cache

Layer 4:
Full-page application response cache

Layer 5:
Browser and CDN cache headers
```

Do not use all layers for every route.

Document which layer applies to which data.

---

# 7. Source of Truth

The database remains the source of truth.

Redis must not become the permanent source of truth for:

* Posts
* Users
* Roles
* Permissions
* Workflow events
* Reviewer assignments
* SEO metadata
* Media metadata
* Analytics records
* Queue jobs
* Sessions unless explicitly configured elsewhere
* Audit records

Cache loss must not cause permanent data loss.

---

# 8. Cache Key Standard

Create a centralized cache-key standard.

Recommended format:

```text
domain:resource:scope:identifier:variant:version
```

Examples:

```text
public:homepage:default:v1
public:post:123:page:v1
public:category:45:page:1:v1
public:tag:56:page:2:v1
public:author:12:page:1:v1
dashboard:editor:user:25:metrics:v1
seo:post:123:summary:v1
navigation:public:header:v1
```

The application and environment prefix must be added centrally.

Do not include:

* Passwords
* Emails
* Tokens
* Session IDs
* Personal data
* Raw URLs containing secrets
* Unbounded arbitrary query strings

---

# 9. Central Cache Key Builder

Create a reusable cache-key builder or value object.

Possible location:

```text
app/Support/CacheKey.php
```

or:

```text
app/Services/Cache/CacheKeyFactory.php
```

Requirements:

* Deterministic keys
* Environment-safe prefixing
* Stable ordering of parameters
* Version support
* Pagination support
* Locale support where applicable
* Device variation only where necessary
* Safe normalization
* Test coverage

Avoid hand-written cache-key strings scattered across controllers and widgets.

---

# 10. Cache Versioning

Support cache-key versioning.

Examples:

```text
homepage:v1
post-page:v2
dashboard-metrics:v1
```

Version changes should allow safe invalidation after structural changes.

Do not use deployment timestamps as the only cache version unless intentionally documented.

---

# 11. TTL Standards

Define standard TTL categories.

Recommended baseline:

```text
Very short:
30–60 seconds

Short:
2–5 minutes

Medium:
10–30 minutes

Long:
1–6 hours

Very long:
12–24 hours

Permanent:
Only where complete invalidation exists
```

Document the TTL used for every cache family.

TTL should reflect:

* Data-change frequency
* Staleness tolerance
* Invalidation reliability
* Rebuild cost
* User impact

---

# 12. Cache Invalidation Principles

Prefer event-driven invalidation.

Use TTL as a safety net, not the only invalidation method for critical content.

Invalidation must be:

* Centralized
* Predictable
* Testable
* Idempotent
* Permission-neutral
* Safe under concurrency
* Limited to related keys

Avoid broad `Cache::flush()` calls.

---

# 13. Cache Invalidation Service

Create a central invalidation service.

Possible location:

```text
app/Services/Cache/CacheInvalidationService.php
```

Possible methods:

```php
invalidatePost(Post $post): void
invalidateHomepage(): void
invalidateCategory(Category $category): void
invalidateTag(Tag $tag): void
invalidateAuthor(User $author): void
invalidateNavigation(): void
invalidateSeo(Post $post): void
invalidateDashboardForUser(User $user): void
invalidateEditorialDashboards(): void
```

Adapt to existing project architecture.

Do not place duplicated invalidation logic in controllers, observers, jobs and Filament actions.

---

# 14. Invalidation Event Map

Define invalidation triggers.

At minimum audit and handle:

```text
Post created
Post updated
Post submitted
Post approved
Post scheduled
Post rescheduled
Post published
Post unpublished
Post archived
Post restored
Post deleted
Post author changed
Post category changed
Post tags changed
Post featured media changed
SEO metadata changed
Category updated
Tag updated
Author profile updated
Media replaced
Navigation changed
Homepage configuration changed
Scheduled publication executed
```

Do not invalidate public-page caches for draft-only changes unless required.

---

# 15. Editorial Workflow Integration

Integrate cache invalidation with the central editorial workflow service.

Publishing must invalidate:

* Article page
* Homepage
* Relevant category archives
* Relevant tag archives
* Relevant author archive
* Date archive
* Sitemap
* Google News sitemap/feed
* RSS/Atom feeds where applicable
* Relevant dashboard metrics

Archiving or unpublishing must invalidate the same public surfaces.

Do not duplicate publication invalidation outside the workflow domain layer unnecessarily.

---

# 16. Model Observers

Use model observers only when appropriate.

Observers may be useful for:

* Category updates
* Tag updates
* Media updates
* Author-profile updates

Do not use observers to silently alter editorial status.

Avoid double invalidation when both service events and observers fire.

Document the final event flow.

---

# 17. Full-Page Caching Eligibility

Only cache safe anonymous GET/HEAD responses.

Possible eligible routes:

```text
Homepage
Published article pages
Category archives
Tag archives
Author archives
Date archives
Static public pages
Public feeds
Sitemap responses
Google News sitemap
```

Eligibility must be verified route by route.

---

# 18. Full-Page Cache Exclusions

Never full-page cache:

```text
Login
Signup
Password reset
Email verification
Filament
Admin routes
Authenticated dashboards
Preview routes
Draft previews
Workflow actions
User profile
Account pages
Search results with sensitive or highly variable query strings
POST, PUT, PATCH or DELETE requests
Routes with CSRF tokens
Routes with flash messages
Personalized pages
Private content
Authorization-dependent responses
Error responses unless explicitly safe
```

Also bypass full-page cache when:

* User is authenticated
* Session contains personalization
* Preview token is present
* Debug mode requires bypass
* Request contains unsupported query parameters
* Response sets private cookies
* Response is not successful
* Response is streamed
* Response is a file download
* Response contains user-specific data

---

# 19. Response Cache Middleware

Implement or configure safe response-cache middleware.

Possible approaches:

```text
Custom middleware
Existing verified package
Laravel cache-backed response service
```

Do not add a dependency unless necessary.

If adding a package:

* Confirm Laravel 13 compatibility
* Pin an appropriate version
* Use targeted Composer installation
* Do not run `composer update`
* Audit package behavior and maintenance status
* Document the decision

A custom implementation is acceptable if it remains small, tested and maintainable.

---

# 20. Full-Page Cache Key Variation

Full-page cache keys may vary by:

```text
Normalized path
Pagination
Locale
Selected safe query parameters
Content encoding where necessary
```

Do not vary unnecessarily by:

* User-Agent
* Every cookie
* Tracking parameters
* UTM parameters
* Random query parameter order

Normalize or ignore known tracking parameters where safe.

Document accepted query parameters.

---

# 21. Cacheable Response Criteria

A public response may be cached only when:

* Request method is GET or HEAD
* User is unauthenticated
* Route is explicitly cacheable
* Response status is 200
* Response is not private
* No sensitive cookies are set
* No preview context exists
* Content is published
* Response body is below a reasonable size
* Cache key is deterministic
* Invalidation is defined

---

# 22. Cache-Control Headers

Set appropriate HTTP headers.

Possible headers:

```text
Cache-Control
ETag
Last-Modified
Vary
Expires
```

Use conservative public caching where the application owns invalidation.

Do not mark personalized content as public.

Do not create conflicting application and web-server cache policies.

---

# 23. ETag Support

Consider ETags for public content.

Requirements:

* Deterministic value
* Efficient generation
* Support `If-None-Match`
* Return `304 Not Modified` when appropriate
* Avoid hashing very large responses repeatedly without caching
* Invalidate when content changes

Do not add ETags where they provide no measurable value.

---

# 24. Last-Modified Support

For article pages and archives, consider:

```text
Last-Modified
If-Modified-Since
```

The timestamp must represent relevant content changes.

Do not use current time as `Last-Modified`.

---

# 25. Fragment Caching

Use fragment caching for expensive shared components.

Possible candidates:

```text
Header navigation
Footer navigation
Breaking-news ticker
Trending posts
Popular categories
Sidebar widgets
Related posts
Latest posts block
Homepage sections
Google News readiness summary
```

Each fragment must define:

* Key
* TTL
* Invalidation
* Scope
* Empty state
* Failure behavior

---

# 26. Blade Component Caching

Where practical, encapsulate cached fragments in services or components.

Avoid embedding complex cache logic directly in Blade templates.

Do not cache CSRF tokens, authenticated user names or personalized menus.

---

# 27. Query-Result Caching

Cache expensive query results where useful.

Potential candidates:

```text
Homepage article groups
Trending posts
Category counts
Tag counts
Published post totals
Related-post IDs
Latest-post IDs
Public navigation data
Dashboard aggregates
SEO health totals
Media-health totals
```

Cache identifiers or compact DTOs where possible rather than full Eloquent models.

---

# 28. Eloquent Model Caching Risks

Avoid caching live Eloquent models for long periods when:

* Relationships may become stale
* Serialization is large
* Model casts may change
* Authorization depends on fresh state
* Soft-delete state matters

Prefer arrays, DTOs or IDs.

---

# 29. Dashboard Metric Caching

Cache expensive dashboard aggregates introduced in Phases 2.1-D and 2.1-E.

Requirements:

* Permission-aware cache family
* User or scope-specific keys
* Short TTL
* Invalidation after relevant workflow events
* No leakage across roles or users
* No hidden authorization bypass
* Graceful fallback to database queries
* Query-count measurement

Examples:

```text
Reporter own-post metrics
Reviewer assigned-review metrics
Editor review-queue metrics
Admin operational totals
SEO issue totals
Media issue totals
Analytics summaries where verified data exists
```

---

# 30. Dashboard Cache Scope

Dashboard keys must include the relevant scope.

Examples:

```text
dashboard:reporter:user:25:metrics:v1
dashboard:reviewer:user:42:assigned:v1
dashboard:editor:global:review-queue:v1
```

Never use one global cache entry for role-restricted data unless the underlying data is identical and authorization-safe.

---

# 31. Cache Stampede Protection

Use locks around expensive cache rebuilds.

Recommended pattern:

```php
Cache::lock($lockKey, $seconds)->block($waitSeconds, function () {
    // Rebuild cache safely.
});
```

Support graceful behavior when lock acquisition fails.

Do not allow dozens of concurrent requests to rebuild the same key.

---

# 32. Stale-While-Revalidate

Consider stale-while-revalidate for non-critical public data.

Possible candidates:

```text
Trending posts
Popular categories
Homepage secondary sections
Dashboard non-urgent aggregates
```

Do not serve stale data for:

* Publication status
* Access control
* Scheduled publishing
* Corrections
* User permissions
* Security-sensitive state
* Breaking legal corrections
* Private content

---

# 33. Cache Warming

Implement controlled cache warming for high-value keys.

Possible warm targets:

```text
Homepage
Top category pages
Latest published articles
Public navigation
Sitemap
Google News sitemap
Common dashboard metrics
```

Do not warm the entire post archive indiscriminately.

---

# 34. Cache Warming Command

Consider an Artisan command:

```bash
php artisan cache:warm
```

Possible options:

```text
--homepage
--navigation
--categories
--posts
--sitemaps
--dashboards
--all-safe
--limit=
```

Requirements:

* Safe defaults
* Bounded work
* Locking
* Progress output
* Failure reporting
* Non-zero exit on critical failure
* No secret exposure
* Testability

---

# 35. Cache Invalidation Command

Consider an operational command:

```bash
php artisan cache:invalidate
```

Possible options:

```text
--post=
--category=
--tag=
--author=
--homepage
--navigation
--public
```

Do not provide a hidden equivalent of `Cache::flush()` by default.

Require confirmation for broad invalidation where interactive execution is possible.

---

# 36. Deployment Cache Strategy

Document deployment behavior.

Recommended sequence:

```text
1. Deploy code
2. Verify Redis health
3. Rebuild Laravel configuration cache where controlled
4. Run database migrations if any safe additive migration exists
5. Invalidate versioned application cache families if required
6. Warm critical public keys
7. Verify homepage and article pages
8. Verify authenticated routes bypass cache
9. Monitor errors and hit rate
```

Do not clear every Redis key on every deployment.

---

# 37. Cache Bypass Mechanism

Provide a safe operational bypass.

Possible mechanisms:

```text
Environment feature flag
Authorized debug query parameter
Internal middleware toggle
Route-specific configuration
```

Requirements:

* Disabled for public users by default
* Permission-protected if query-based
* Logged where appropriate
* Does not leak uncached private data
* Easy rollback

Do not expose a public `?no-cache=1` bypass without authorization.

---

# 38. Feature Flags

Use configuration-driven feature flags for staged rollout.

Possible flags:

```text
CACHE_PUBLIC_RESPONSES
CACHE_PUBLIC_FRAGMENTS
CACHE_DASHBOARD_METRICS
CACHE_WARMING_ENABLED
CACHE_ETAG_ENABLED
```

Do not add real production values to source control.

Document defaults and rollout order.

---

# 39. Failure Handling

Define behavior when Redis is unavailable.

Recommended principles:

```text
Public response cache:
Fall back to uncached rendering

Fragment cache:
Fall back to live query/rendering

Dashboard metric cache:
Fall back to authorized database query

Atomic publication lock:
Do not silently bypass

Critical workflow lock:
Fail safely and log
```

Do not turn an optional cache outage into a total website outage.

Do not silently bypass correctness-critical locks.

---

# 40. Cache Logging

Log important cache failures.

Useful context:

```text
Cache family
Key hash or safe key
Operation
Exception class
Fallback used
Request route
Environment
```

Do not log:

* Cached payloads
* Private editorial content
* Tokens
* Sessions
* Credentials
* Full personal data

---

# 41. Cache Metrics

Add lightweight cache observability.

Possible metrics:

```text
Hit
Miss
Write
Forget
Lock acquired
Lock failed
Rebuild time
Payload size
Fallback count
Warm success
Warm failure
```

Do not implement a heavy analytics platform in this phase.

Use logs, counters or an internal diagnostic service as appropriate.

---

# 42. Cache Health Command

Extend or create a cache health command.

Suggested command:

```bash
php artisan cache:health
```

Recommended checks:

```text
Default cache store
Redis connectivity
Write/read/delete
TTL
Lock
Namespace
Feature flags
Public cache readiness
Dashboard cache readiness
```

Do not expose secrets.

---

# 43. Cache Inspection

Provide safe inspection tools.

Possible command:

```bash
php artisan cache:inspect
```

Requirements:

* List only application-owned key families
* Avoid dumping sensitive values
* Support counts and TTL summaries
* Avoid scanning all Redis keys synchronously in production
* Use cursor-based scanning where needed
* Restrict destructive actions

---

# 44. Redis Key Ownership

All keys introduced in this phase must be clearly application-owned.

Use prefixes such as:

```text
dailysamvad:production:cache:
```

Never delete keys outside the owned prefix.

---

# 45. Pagination Caching

Archive caches must include page number.

Also include relevant filters and sorting.

Example:

```text
public:category:45:page:2:v1
```

Do not serve page 1 data for page 2.

---

# 46. Query Parameter Normalization

Normalize safe query parameters.

Possible steps:

* Sort parameter keys
* Remove tracking parameters
* Reject unsupported parameters from cache key generation
* Normalize blank values
* Normalize pagination
* Preserve search semantics where caching search is explicitly enabled

Do not cache arbitrary high-cardinality query combinations.

---

# 47. Search Caching

Do not broadly cache search results in this phase unless the existing search implementation clearly benefits and privacy is preserved.

If search caching is introduced:

* Use short TTL
* Normalize query
* Limit key length
* Exclude authenticated/private contexts
* Prevent cache poisoning
* Bound result count
* Test Unicode and Punjabi/Hindi queries
* Document invalidation limits

Search architecture replacement belongs to a later phase.

---

# 48. Sitemap Caching

Cache sitemap responses safely.

Requirements:

* Invalidate on publication, archive or SEO change
* Preserve correct XML headers
* Avoid stale removed URLs
* Support segmented sitemaps where existing
* Support Google News freshness requirements
* Test response encoding
* Test route compatibility

---

# 49. Feed Caching

Cache RSS/Atom/news feeds where appropriate.

Requirements:

* Short TTL
* Invalidate on publish/archive
* Preserve feed headers
* Preserve date ordering
* Avoid serving unpublished content
* Support conditional GET where practical

---

# 50. Homepage Caching

Homepage caching must account for:

* Latest posts
* Breaking news
* Featured sections
* Categories
* Advertisements
* Navigation
* Scheduled publication
* Manual homepage changes
* Time-sensitive blocks

Prefer a short TTL plus event-driven invalidation.

Do not cache user-specific content in the public homepage response.

---

# 51. Article Page Caching

Article-page cache must account for:

* Post content
* Title
* Featured image
* Author
* Categories
* Tags
* SEO metadata
* Structured data
* Related posts
* Publication/update timestamps
* Ads
* Corrections
* Archive/unpublish state

Invalidation must occur when any public-facing article data changes.

---

# 52. Archive Caching

Cache:

```text
Category archives
Tag archives
Author archives
Date archives
```

Requirements:

* Pagination-aware
* Publication-aware
* Invalidate relevant first pages when new posts publish
* Invalidate affected archive pages when posts move categories/tags
* Avoid rebuilding all pages unnecessarily
* Preserve canonical URLs

---

# 53. Related Posts Cache

If related posts are expensive, cache only IDs and scores.

Invalidate when:

* Post taxonomy changes
* Post publishes or archives
* Relevant algorithm version changes

Use bounded result sets.

---

# 54. Navigation Cache

Public navigation may be cached longer when invalidation is reliable.

Invalidate when:

* Menu configuration changes
* Category label/slug changes
* Visibility changes
* Navigation settings change

Do not cache staff navigation globally across users.

---

# 55. Advertisement Cache Boundaries

Audit advertisement behavior.

Do not cache:

* Personalized ads
* User-targeted ad state
* Impression tokens
* Rotation state requiring real-time variation

Static ad configuration may be cached separately if safe.

Document the decision.

---

# 56. Localization

If the application supports multiple languages or locales, include locale in relevant keys.

Do not serve Punjabi content from an English cache key or vice versa.

Audit actual locale behavior before adding unnecessary variants.

---

# 57. Authentication and Cookies

Verify anonymous cached responses do not set or reuse:

* Session cookies
* Authentication cookies
* CSRF tokens
* Personalized preferences
* Notification state

Do not cache responses containing user-specific cookies.

---

# 58. Security Requirements

Protect against:

* Cache poisoning
* Authorization leakage
* Host-header key pollution
* Query-string explosion
* Header-based key explosion
* Sensitive response caching
* Stale private content
* Cross-environment collisions
* Unbounded Redis growth

Use an approved host/domain list in cache-key generation where host variation matters.

---

# 59. Cache Poisoning Prevention

Only use trusted normalized inputs in cache keys.

Do not cache responses that depend on:

* Arbitrary request headers
* Unvalidated host
* Unvalidated locale
* Unbounded query strings
* Preview tokens
* Authorization headers

---

# 60. Payload Size Limits

Define reasonable maximum payload sizes.

Avoid caching:

* Large binary media
* File downloads
* Huge serialized model graphs
* Unbounded HTML responses
* Large analytics datasets

Log or skip oversized entries safely.

---

# 61. Compression

Do not add application-level cache compression unless:

* Payload size is proven significant
* CPU tradeoff is measured
* Serializer compatibility is verified
* Operational complexity is justified

HTTP compression remains a web-server concern.

---

# 62. Tags and Cache Groups

Use cache tags only if the chosen Redis store and Laravel configuration support them reliably.

If tags are used:

```text
post:{id}
category:{id}
tag:{id}
author:{id}
homepage
navigation
sitemap
```

Document the strategy.

Do not rely on tags if tests show inconsistent behavior.

A versioned-key index strategy is acceptable.

---

# 63. Cache Indexes

If maintaining sets of keys for invalidation, ensure indexes are:

* Namespaced
* Bounded
* Expiring where appropriate
* Updated atomically
* Safe under failure
* Cleaned up

Do not create permanent unbounded key registries.

---

# 64. Performance Baseline

Measure before and after.

At minimum measure representative routes:

```text
Homepage
Published article
Category archive
Tag archive
Author archive
Sitemap
Editor dashboard
Reporter dashboard
```

Record:

* Response time
* Database query count
* Cache hit response time
* Cache miss response time
* Redis operations
* Payload size
* Memory impact where practical

Do not claim improvement without measurement.

---

# 65. Cache Hit Verification

Verify:

* First request produces miss
* Second request produces hit
* Cached response matches original
* Invalidation causes a new miss
* Rebuilt response reflects updated data
* Unauthorized requests never hit public/private mixed cache

---

# 66. Concurrency Verification

Where practical, test concurrent cache misses.

Verify:

* One rebuild occurs
* Other requests wait briefly or use safe stale value
* No duplicate expensive computation storm
* Lock release occurs on exception
* Lock expiry recovers

---

# 67. Automated Tests

Create focused tests.

## 67.1 Cache Configuration Tests

Verify:

* Redis cache store exists
* Feature flags have safe defaults
* Environment prefix is configured
* Test environment uses isolated prefix
* Session and queue drivers remain unchanged unless explicitly intended

## 67.2 Cache Key Tests

Verify:

* Deterministic keys
* Parameter normalization
* Pagination variation
* Locale variation
* Version variation
* Environment isolation
* No sensitive values in keys

## 67.3 Full-Page Cache Tests

Verify:

* Anonymous public GET is cached
* Authenticated request bypasses
* Preview bypasses
* Non-GET bypasses
* Error response is not cached
* Private response is not cached
* Unsupported query parameters bypass
* Cache hit returns correct body
* Cache headers are correct

## 67.4 Invalidation Tests

Verify:

* Publish invalidates article and related public caches
* Update invalidates article cache
* Archive removes public cached response
* Category change invalidates relevant archives
* Tag change invalidates relevant archives
* Author change invalidates author archive
* SEO update invalidates article/sitemap where required
* Featured media change invalidates article cache
* Scheduled publication invalidates required keys

## 67.5 Dashboard Cache Tests

Verify:

* Reporter cache is user-scoped
* Reviewer cache is assignment-scoped
* Editor cache is permission-scoped
* Unauthorized users cannot access cached metrics
* Relevant workflow events invalidate metrics
* Redis failure falls back safely

## 67.6 Stampede Tests

Verify:

* Lock prevents duplicate rebuild
* Lock failure is handled
* Expired lock recovers
* Exception releases lock or allows recovery

## 67.7 Cache Warming Tests

Verify:

* Warm command creates expected keys
* Bounded limits work
* Invalid options fail clearly
* Locks prevent duplicate warmers
* Failures are reported honestly

## 67.8 Security Tests

Verify:

* Host header cannot poison key
* Tracking parameters do not multiply keys unnecessarily
* Auth cookies bypass public cache
* Preview tokens bypass cache
* Private pages are never cached
* Subscriber/staff content does not leak

## 67.9 Regression Tests

Verify:

* Editorial workflow remains unchanged
* Scheduled publishing remains unchanged
* Public routes remain unchanged
* Legacy redirects remain unchanged
* SEO metadata remains unchanged
* Structured data remains unchanged
* Media mappings remain unchanged
* Filament authorization remains unchanged
* Login, signup and logout remain functional
* Search remains functional
* Sitemaps remain valid

---

# 68. Test Environment

Redis integration tests must use:

* Dedicated test prefix
* Dedicated test database where available
* Temporary keys
* Safe cleanup of known keys only

Never run:

```bash
redis-cli FLUSHALL
redis-cli FLUSHDB
```

Tests must not touch production keys.

---

# 69. Test Execution

Run focused tests first.

Suggested commands:

```bash
php artisan test --filter=Cache
php artisan test --filter=ResponseCache
php artisan test --filter=CacheInvalidation
php artisan test --filter=CacheWarm
php artisan test --filter=DashboardCache
```

Adapt to the actual test structure.

Run health commands:

```bash
php artisan redis:health
php artisan cache:health
```

Run representative performance verification.

Then run:

```bash
php artisan test
```

Clearly distinguish:

```text
passed
failed
skipped
environmental failure
pre-existing failure
new regression
```

Do not report cache integration tests as passed if Redis was unavailable.

---

# 70. Operational Commands

Possible commands:

```bash
php artisan cache:health
php artisan cache:warm --homepage
php artisan cache:warm --navigation
php artisan cache:warm --sitemaps
php artisan cache:invalidate --post=123
php artisan cache:invalidate --homepage
php artisan cache:inspect
```

Adapt names to implementation.

Do not create unsafe broad defaults.

---

# 71. Production Rollout Strategy

Use staged rollout.

Recommended order:

```text
Stage 1:
Cache key builder and invalidation service

Stage 2:
Query-result caching

Stage 3:
Dashboard metric caching

Stage 4:
Fragment caching

Stage 5:
Public full-page caching on one low-risk route

Stage 6:
Article pages

Stage 7:
Archive pages

Stage 8:
Homepage

Stage 9:
Sitemaps and feeds

Stage 10:
Cache warming
```

Verify each stage before expanding.

---

# 72. Rollback Plan

Document rollback for:

* Incorrect cached content
* Authorization leakage
* Redis outage
* Excessive memory use
* Invalidation failure
* Cache poisoning
* Response-header conflict
* Deployment regression

Possible rollback actions:

```text
Disable feature flag
Bypass response cache
Revert cache middleware
Restore live query path
Invalidate application-owned key family
Revert application commit
```

Do not use `FLUSHALL` or delete unrelated keys.

---

# 73. Monitoring Checklist

Document monitoring for:

```text
Cache hit rate
Cache miss rate
Redis memory
Evictions
Expired keys
Key growth
Lock failures
Rebuild duration
Fallback count
Error rate
Stale-content reports
Response times
Query counts
Redis latency
```

---

# 74. Documentation Deliverables

Create or update:

```text
docs/version-2.1/phase-2.1-g-cache-architecture.md
docs/version-2.1/cache-key-standard.md
docs/version-2.1/cache-ttl-standard.md
docs/version-2.1/cache-invalidation-map.md
docs/version-2.1/public-response-cache-map.md
docs/version-2.1/dashboard-cache-scope-map.md
docs/version-2.1/cache-warming-runbook.md
docs/version-2.1/cache-health-and-monitoring.md
docs/version-2.1/cache-production-rollout.md
docs/version-2.1/cache-rollback-plan.md
docs/version-2.1/cache-performance-baseline.md
docs/version-2.1/cache-security-checklist.md
```

Documentation must include:

* Existing cache audit
* Final architecture
* Cache layers
* Cacheable routes
* Excluded routes
* Key formats
* TTLs
* Invalidation triggers
* Workflow integration
* Dashboard cache scopes
* Stampede strategy
* Warming strategy
* Feature flags
* Failure behavior
* Security controls
* Performance measurements
* Production rollout
* Rollback procedure
* Monitoring
* Test results
* Known limitations
* Deferred items

Do not include secrets or personal data.

---

# 75. Completion Criteria

Phase 2.1-G is complete only when:

* Cache architecture is documented.
* Redis-backed cache is used safely.
* Cache keys are centralized and deterministic.
* Environment isolation is verified.
* TTL standards are defined.
* Invalidation is event-driven and tested.
* Publication and archive events invalidate public surfaces.
* Full-page caching is limited to safe anonymous routes.
* Authenticated and private routes always bypass public cache.
* Dashboard metrics are cached without data leakage.
* Cache stampede protection exists.
* Cache warming exists for selected safe targets.
* Cache health diagnostics exist.
* Cache metrics are observable.
* Failure fallback is safe.
* No correctness-critical lock is silently bypassed.
* No unknown Redis keys are deleted.
* Performance is measured before and after.
* Focused tests are executed.
* Full test-suite result is reported honestly.
* Existing workflow, SEO, media and URLs remain compatible.
* Required documentation is complete.

---

# 76. Deferred Items

Do not implement in this phase:

* Redis queue activation
* Laravel Horizon
* Queue-worker optimization
* Search-engine replacement
* Analytics event collection
* Image conversion
* Image optimization
* Public frontend redesign
* CDN changes
* Nginx FastCGI cache unless separately approved
* Multi-server cache coordination
* Redis Cluster
* Redis Sentinel
* News-Man integration

---

# 77. Required Completion Report Format

Return the completion report using this exact structure:

## 1. Executive Summary

## 2. Existing Cache Audit

## 3. Final Cache Architecture

## 4. Cache Layer Map

## 5. Redis Cache Configuration

## 6. Cache Key Standard

## 7. Cache Versioning

## 8. TTL Standard

## 9. Cache Invalidation Architecture

## 10. Editorial Workflow Integration

## 11. Public Response Cache Eligibility

## 12. Public Response Cache Exclusions

## 13. Full-Page Cache Implementation

## 14. Fragment Cache Implementation

## 15. Query-Result Cache Implementation

## 16. Dashboard Metric Cache Implementation

## 17. Cache Scope and Authorization

## 18. Stampede Protection

## 19. Stale-While-Revalidate

## 20. Cache Warming

## 21. Cache Bypass and Feature Flags

## 22. HTTP Cache Headers

## 23. ETag and Last-Modified Support

## 24. Homepage Cache

## 25. Article Page Cache

## 26. Archive Cache

## 27. Sitemap and Feed Cache

## 28. Cache Failure Handling

## 29. Cache Health Diagnostics

## 30. Cache Logging and Monitoring

## 31. Cache Security Verification

## 32. Performance Baseline

## 33. Performance Comparison

## 34. Automated Tests Added or Updated

## 35. Focused Cache Test Results

## 36. Security Test Results

## 37. Regression Test Results

## 38. Full Test-Suite Result

## 39. Production Rollout Procedure

## 40. Rollback Procedure

## 41. Backward-Compatibility Verification

## 42. Documentation Created

## 43. Files Created or Modified

## 44. Commands Executed

## 45. Risks and Open Questions

## 46. Deferred Items

## 47. Final Phase Decision

The final phase decision must be one of:

```text
COMPLETE
COMPLETE WITH CONDITIONS
INCOMPLETE
```

Explain the decision using verified evidence.

---

# 78. Strict Rules

* Audit before implementing cache.
* Preserve the database as source of truth.
* Use Redis only as cache and locking infrastructure in this phase.
* Centralize cache keys.
* Centralize invalidation.
* Do not use `Cache::flush()` in normal application behavior.
* Do not use `FLUSHALL`.
* Do not use `FLUSHDB`.
* Do not delete unknown Redis keys.
* Never cache authenticated private responses as public.
* Never cache previews.
* Never cache CSRF tokens.
* Never cache user-specific staff pages globally.
* Do not bypass permissions through cached data.
* Do not silently serve stale workflow or authorization state.
* Do not implement broad caching without invalidation.
* Do not claim performance improvements without measurements.
* Do not activate Redis queues.
* Do not install Horizon.
* Do not alter editorial workflow.
* Do not alter roles or permissions.
* Do not modify imported content.
* Do not change slugs or public URLs.
* Do not alter SEO metadata incorrectly.
* Do not alter featured-media mappings.
* Do not modify `.env` without deployment authority.
* Do not run destructive database commands.
* Do not upgrade unrelated dependencies.
* Do not claim tests passed unless executed successfully.
* Clearly report skipped and environmental tests.
* Preserve backward compatibility.
