# Daily Samvad — Version 2.1

## Phase 2.1-K: Analytics Architecture, Post Views, Editorial Insights and Privacy-Safe Reporting

You are working on the existing Daily Samvad Laravel application.

The application currently uses:

* Laravel 13
* Filament 5
* Livewire 4
* MySQL
* Spatie Laravel Permission
* Redis foundation from Phase 2.1-F
* Cache architecture from Phase 2.1-G
* Queue architecture from Phase 2.1-H
* Image optimization from Phase 2.1-I
* Search improvements from Phase 2.1-J
* Imported WordPress posts, users, media, categories, tags and SEO metadata
* Role-based Filament dashboards
* Reporter → Reviewer → Editor → Publish workflow
* Existing or partially implemented post visit-count fields
* Existing public article routes and archive pages

Phase 2.1-K must implement a reliable, privacy-conscious and production-ready analytics foundation.

The implementation must provide useful insights without:

* Slowing public article requests
* Inflating page views through bots or refreshes
* Exposing private user information
* Breaking page caching
* Breaking full-page caching
* Breaking Redis
* Breaking queues
* Breaking editorial workflow
* Changing public URLs
* Modifying imported content
* Introducing invasive third-party tracking
* Turning raw analytics into an uncontrolled high-volume database

---

# 1. Primary Objective

Implement a complete analytics architecture covering:

* Post views
* Unique or deduplicated visits
* Visitor identity strategy
* Session and anonymous visitor handling
* Bot filtering
* Internal traffic filtering
* Cached-page analytics compatibility
* Queue-based analytics ingestion
* Redis buffering and counters
* Database aggregation
* Daily post metrics
* Category metrics
* Tag metrics
* Author metrics
* Reporter metrics
* Editorial workflow metrics
* Search analytics readiness
* Referrer and traffic-source reporting
* Device and browser classification
* Geographic-data decision
* Role-based analytics dashboards
* Post-level analytics
* Performance safeguards
* Data-retention policy
* Privacy and security controls
* Monitoring
* Repair and reconciliation commands
* Automated tests
* Production rollout
* Rollback documentation

The database must remain the durable source of truth for long-term analytics.

Redis may be used for:

* Temporary counters
* Deduplication
* Rate limiting
* Queue transport
* Short-lived visitor state
* Aggregation buffers

Redis must not be the only durable analytics store.

---

# 2. Core Principles

Analytics must be:

* Privacy-conscious
* First-party
* Accurate enough for editorial use
* Resistant to obvious inflation
* Non-blocking
* Cache-compatible
* Queue-compatible
* Incrementally scalable
* Observable
* Repairable
* Permission-aware
* Backward-compatible

Public article rendering must remain successful even if:

* Redis is unavailable
* Queue workers are unavailable
* Analytics storage fails
* Analytics jobs fail
* A bot sends malformed headers
* Visitor cookies are disabled

Analytics failure must not prevent article delivery.

---

# 3. Existing-State Audit

Before modifying anything, audit:

* Existing `posts.visit_count`
* Existing `posts.visitor_id`
* Existing post-view columns
* Existing analytics tables
* Existing visitor tables
* Existing session storage
* Existing cookies
* Existing middleware
* Existing article controllers
* Existing article query classes
* Existing Blade or Livewire article rendering
* Existing JavaScript tracking
* Existing Google Analytics or third-party scripts
* Existing server logs
* Existing access logs
* Existing Cloudflare analytics
* Existing bot protection
* Existing user-agent parsing
* Existing IP handling
* Existing proxy configuration
* Existing trusted-proxy settings
* Existing full-page cache behavior
* Existing response cache middleware
* Existing Redis keys
* Existing queue jobs
* Existing scheduler commands
* Existing Filament widgets
* Existing dashboard metrics
* Existing role permissions
* Existing analytics-manager role
* Existing `manage analytics` permission
* Existing author and reporter relationships
* Existing category and tag relationships
* Existing published scopes
* Existing search analytics events from Phase 2.1-J
* Existing performance tests
* Existing analytics-related tests
* Current post count
* Current traffic level where available
* Current database size
* Current Redis memory
* Current queue throughput
* Existing database indexes
* Existing data-retention policies

Document the existing state before implementation.

---

# 4. Protected Boundaries

Do not disturb:

* Existing users
* Existing roles
* Existing permissions
* Existing policies
* Editorial workflow
* Reviewer assignments
* Workflow history
* Scheduled publishing
* Imported posts
* Imported media
* Categories
* Tags
* Authors
* SEO metadata
* Search index
* Post slugs
* Public URLs
* Legacy redirects
* Canonical URLs
* OpenGraph
* Structured data
* Google News behavior
* Sitemaps
* Feeds
* Existing Redis prefixes
* Existing cache architecture
* Existing queue architecture
* Existing image pipeline
* Existing search architecture
* Existing production secrets

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

Do not replace existing visit counts without reconciliation.

Do not reset historical counters.

Do not expose raw IP addresses in dashboards.

---

# 5. Scope of This Phase

This phase includes:

* First-party post-view tracking
* Deduplicated visit tracking
* Visitor identification
* Bot filtering
* Redis-backed temporary counters
* Queue-based ingestion
* Durable analytics aggregation
* Daily analytics tables
* Post-level analytics
* Author and reporter analytics
* Category and tag analytics
* Referrer classification
* Device classification
* Editorial workflow metrics
* Role-based analytics dashboards
* Analytics commands
* Health monitoring
* Performance testing
* Privacy controls
* Data-retention documentation
* Automated tests

This phase does not include:

* Advertising attribution
* Cross-site user tracking
* Fingerprinting
* Sale of user data
* Exact GPS tracking
* Invasive profiling
* Third-party marketing pixels
* Full customer-data platform
* AI recommendations
* AI audience prediction
* Paid analytics SaaS migration
* Google Analytics replacement across unrelated sites
* Full business-intelligence warehouse
* Real-time WebSocket analytics
* News-Man analytics
* Revenue analytics unless data already exists
* Newsletter analytics unless data already exists

---

# 6. Analytics Event Model

Define a stable analytics event contract.

Possible event:

```text
post_view
```

Possible fields:

```text
event_id
event_type
post_id
occurred_at
visitor_key
session_key
user_id
referrer_type
referrer_host
utm_source
utm_medium
utm_campaign
device_type
browser_family
operating_system
country_code
is_bot
is_internal
request_id
```

Store only fields justified by reporting needs.

Do not store:

* Passwords
* Authorization headers
* Session contents
* Full cookies
* Full raw request bodies
* Exact GPS coordinates
* Sensitive form data
* Full IP addresses indefinitely
* Complete raw user-agent strings indefinitely unless justified

---

# 7. Event ID and Idempotency

Every accepted analytics event must have a unique identifier.

Possible formats:

* UUID
* ULID

Use the event ID to prevent duplicate processing.

A retried queue job must not create duplicate durable analytics rows or duplicate aggregates.

Add an appropriate unique constraint where practical.

---

# 8. Visitor Identity Strategy

Use a privacy-safe anonymous visitor key.

Possible strategy:

```text
Random first-party cookie identifier
```

Requirements:

* Random, non-sequential value
* No personally identifying data embedded
* First-party only
* Configurable lifetime
* Secure and SameSite-aware cookie settings
* No dependency on authentication
* No cross-domain tracking
* Rotatable
* Optional for users blocking cookies

Do not use deterministic browser fingerprinting.

Do not build visitor identity from:

* Canvas fingerprint
* Installed fonts
* Screen hardware details
* Audio fingerprint
* Exact IP plus user agent
* Persistent cross-site identifier

---

# 9. Visitor Cookie

Possible cookie name:

```text
ds_visitor
```

Use centralized configuration.

Requirements:

* HTTP-only unless client-side tracking requires otherwise
* Secure in HTTPS production
* SameSite=Lax or stricter where compatible
* Appropriate domain
* Appropriate path
* Bounded expiration
* Random identifier
* No personal data

If JavaScript must read the visitor ID, document why HTTP-only cannot be used.

Prefer server-side handling where practical.

---

# 10. Session Identity

A session-level key may be used for short-term deduplication.

Possible sources:

* Laravel session ID hash
* Separate anonymous session cookie
* Redis-issued session key

Do not store raw Laravel session IDs in analytics tables.

Hash or transform internal identifiers before analytics use.

---

# 11. Authenticated User Handling

Authenticated users may be linked to analytics only when necessary for internal reporting.

Public analytics should not require `user_id`.

Possible policy:

* Store anonymous visitor key for public readers.
* Store user ID only for authorized internal sessions where justified.
* Exclude staff preview traffic from public readership metrics by default.
* Do not show individual-reader histories in standard dashboards.

Do not create surveillance-style user browsing profiles.

---

# 12. IP Address Policy

Use IP addresses only for limited operational purposes such as:

* Bot protection
* Rate limiting
* Abuse prevention
* Short-lived deduplication

Preferred approach:

* Normalize trusted proxy headers safely.
* Hash IP with an application-specific salt.
* Use a rotating or time-bounded hash.
* Store only truncated or hashed values.
* Avoid permanent raw-IP storage.

Do not expose IP values in Filament dashboards.

Document retention.

---

# 13. Trusted Proxy Handling

Audit:

* Cloudflare
* Nginx
* Load balancer
* Laravel trusted proxies
* `X-Forwarded-For`
* `CF-Connecting-IP`
* Direct server access

Do not trust arbitrary client-supplied proxy headers.

Use the framework’s trusted-proxy configuration.

Incorrect proxy trust can collapse all visitors into one IP or allow spoofing.

---

# 14. Page View Definition

Define a page view clearly.

A page view may be counted when:

* A valid public article page is requested.
* The post is published and publicly visible.
* The response is not an admin preview.
* The request is not identified as a known bot.
* The request is not a health check.
* The request is not a prefetch or unsupported automated request where identifiable.
* The analytics event passes rate and deduplication checks.

Document whether repeat refreshes count as raw views.

---

# 15. Unique View Definition

Define a deduplicated view.

Suggested default:

```text
One view per visitor per post within a configurable time window.
```

Possible window:

```text
30 minutes
1 hour
6 hours
24 hours
```

Choose based on editorial needs.

Recommended metrics:

* Raw views
* Deduplicated views
* Estimated unique visitors

Do not label a weak estimate as a guaranteed unique human.

---

# 16. Deduplication Strategy

Possible Redis key:

```text
analytics:dedupe:{post_id}:{visitor_hash}
```

Use:

* Environment prefix
* Analytics namespace
* Hashed visitor identifier
* Bounded TTL
* No raw personal data

Requirements:

* Atomic set-if-not-exists
* TTL
* Fail-open or fail-soft behavior
* Clear Redis outage handling

Do not create unbounded permanent deduplication keys.

---

# 17. Bot Filtering

Implement conservative bot detection.

Signals may include:

* Known crawler user agents
* Empty or malformed user agent
* Request rate
* Known monitoring agents
* Head requests
* Prefetch headers
* Link preview bots
* Search engine crawlers
* Social preview crawlers

Classify bots separately where useful.

Do not block search-engine crawlers merely for analytics.

Bot traffic may be excluded from readership metrics while article delivery remains unchanged.

---

# 18. Bot Classification

Possible classifications:

```text
human
search_crawler
social_preview
monitoring
known_bot
suspected_bot
unknown
```

Use a maintainable user-agent parser or a conservative internal classifier.

Do not introduce a heavy dependency without justification.

Document false-positive risks.

---

# 19. Internal Traffic Filtering

Exclude or classify:

* Admin users
* Editors
* Reporters
* Reviewers
* SEO staff
* Health checks
* Monitoring services
* Local development
* Staging
* Known office IP ranges only if safely configured
* Article preview routes

Do not hardcode personal IP addresses into source control.

Use configuration and documented environment controls.

---

# 20. Full-Page Cache Compatibility

Analytics must work when article responses are served from full-page cache.

Server-side controller-only increments are insufficient if cached responses bypass controller execution.

Evaluate options:

```text
Lightweight JavaScript beacon
Dedicated first-party analytics endpoint
Edge/server log ingestion
Cache middleware hook
Hybrid approach
```

Default recommended approach:

```text
A lightweight first-party beacon sent after a successful article page render.
```

The beacon must:

* Be non-blocking
* Use first-party endpoint
* Carry no sensitive data
* Respect CSRF strategy appropriately
* Be rate-limited
* Validate post identity
* Support cached pages
* Fail silently for readers
* Avoid duplicate firing during Livewire navigation
* Avoid counting preloads as views

---

# 21. Analytics Beacon Endpoint

Possible endpoint:

```text
POST /analytics/post-view
```

or:

```text
POST /api/analytics/post-view
```

Requirements:

* First-party
* Rate-limited
* Accepts minimal payload
* Validates published post
* Validates event ID
* Derives server-side context
* Does not trust client-provided user ID
* Does not trust client-provided IP
* Does not accept arbitrary event types
* Returns minimal response
* Does not expose analytics totals
* Does not reveal whether private posts exist

Use an appropriate CSRF or stateless signed approach.

Do not weaken application-wide CSRF protection.

---

# 22. Beacon Payload

Suggested client payload:

```json
{
  "event_id": "ULID",
  "post_id": 123
}
```

Optional:

```text
page_url
referrer
```

Prefer deriving trusted context on the server.

Do not send:

* Full browser fingerprint
* User email
* Role
* Session content
* Authorization token
* Full article content

---

# 23. Livewire Navigation Handling

If Livewire navigation is used:

* Fire once per completed article navigation.
* Avoid double-counting initial load.
* Avoid duplicate listeners.
* Remove or reuse listeners safely.
* Verify browser back/forward behavior.
* Verify cached navigation.

Do not attach repeated global event handlers on every render.

---

# 24. Redis Counter Architecture

Use Redis for short-lived or buffered counters where beneficial.

Possible keys:

```text
analytics:post:{post_id}:raw:{date}
analytics:post:{post_id}:unique:{date}
analytics:author:{author_id}:{date}
analytics:category:{category_id}:{date}
```

Requirements:

* Environment prefix
* Analytics namespace
* Bounded TTL
* Atomic increments
* Predictable cardinality
* No user-supplied text in keys
* No raw visitor IDs in reporting keys
* Flush-to-database strategy

Do not use Redis hashes with uncontrolled growth without expiration or aggregation.

---

# 25. Durable Database Architecture

Choose an additive schema.

Possible tables:

```text
analytics_events
post_daily_metrics
author_daily_metrics
category_daily_metrics
tag_daily_metrics
search_daily_metrics
analytics_rollups
```

Avoid storing every raw event forever unless justified.

Recommended architecture:

```text
Short-lived raw or event-level records
+
Durable daily aggregates
```

For moderate traffic, direct daily aggregation may be sufficient.

Document the decision.

---

# 26. Post Daily Metrics

Possible fields:

```text
id
post_id
metric_date
raw_views
deduplicated_views
estimated_unique_visitors
bot_views
internal_views
search_views
social_views
direct_views
referral_views
mobile_views
desktop_views
tablet_views
created_at
updated_at
```

Use a unique constraint:

```text
post_id + metric_date
```

Use additive migrations.

---

# 27. Author Daily Metrics

Possible fields:

```text
author_id
metric_date
published_posts
raw_views
deduplicated_views
estimated_unique_visitors
search_views
social_views
```

Do not duplicate author attribution if a post’s author changes without documenting attribution rules.

Define whether metrics follow:

* Current author
* Author at event time
* Original author

Recommended:

```text
Attribute view to the post author recorded at event time.
```

---

# 28. Category and Tag Metrics

Aggregate metrics by post relationships.

Define behavior for posts with multiple categories or tags.

Possible policy:

* Count the full view for each related category.
* Count only the primary category.
* Use fractional attribution.

Choose and document one approach.

Prefer primary category for headline reporting where available.

Avoid uncontrolled write amplification across hundreds of tags.

---

# 29. Workflow Analytics

Track editorial workflow events using existing workflow history.

Possible metrics:

* Drafts created
* Submitted for review
* Assigned to reviewer
* Approved
* Returned for correction
* Rejected
* Scheduled
* Published
* Average draft-to-review time
* Average review duration
* Average approval-to-publication time
* Correction count
* Reporter output
* Reviewer workload
* Editor publishing workload

Do not duplicate workflow truth unnecessarily.

Derive metrics from existing workflow-history records where possible.

---

# 30. Editorial Productivity Metrics

Possible role-scoped metrics:

## Reporter

* Posts drafted
* Posts submitted
* Posts returned
* Posts approved
* Posts published
* Total views on own published posts
* Average views per post

## Reviewer

* Posts reviewed
* Pending assignments
* Approval rate
* Correction-return rate
* Average review time

## Editor

* Posts published
* Posts scheduled
* Review queue size
* Average publication delay
* Publication volume by day

Do not use metrics for punitive employee scoring without governance.

Present them as operational insights.

---

# 31. Search Analytics Integration

Use Phase 2.1-J event contracts.

Possible search metrics:

* Search count
* Zero-result searches
* Result clicks
* Average result rank clicked
* Search latency
* Popular query terms
* Search-to-article conversion

Do not store private editorial queries in public analytics.

Hash, aggregate or redact queries where appropriate.

Do not implement invasive user-level search histories.

---

# 32. Referrer Classification

Classify referrers into bounded groups:

```text
direct
search
social
internal
referral
unknown
```

Possible search sources:

```text
Google
Bing
DuckDuckGo
Other search
```

Possible social sources:

```text
Facebook
X/Twitter
WhatsApp
Instagram
YouTube
LinkedIn
Telegram
Other social
```

Store host or classification, not full sensitive URLs where unnecessary.

Strip query strings from external referrer URLs unless explicitly required.

---

# 33. UTM Parameters

Support bounded first-party attribution:

```text
utm_source
utm_medium
utm_campaign
```

Requirements:

* Length limits
* Character normalization
* No arbitrary huge values
* No secrets
* No uncontrolled dimension explosion
* Retention policy

Do not trust UTMs as verified identity.

---

# 34. Device Classification

Classify into broad groups:

```text
mobile
desktop
tablet
bot
unknown
```

Optional:

* Browser family
* Operating-system family

Do not store excessive hardware fingerprint details.

---

# 35. Geographic Analytics

Geographic analytics is optional.

Do not automatically install IP geolocation databases or external APIs.

If country-level reporting is considered:

* Document source
* Licensing
* Update process
* Privacy impact
* Accuracy limitations
* Proxy and VPN limitations

Do not store precise location.

If not implemented, report it as deferred.

---

# 36. Analytics Ingestion Job

Possible job:

```text
ProcessAnalyticsEvent
```

Requirements:

* Implements `ShouldQueue`
* Uses named analytics queue or approved existing queue
* Accepts scalar event ID or compact DTO
* Is idempotent
* Has bounded retries
* Has bounded timeout
* Uses safe backoff
* Handles missing post
* Handles unpublished post
* Handles duplicate event
* Handles Redis outage
* Handles database outage
* Records failures safely
* Does not block article delivery

Do not serialize request objects.

---

# 37. Queue Selection

Possible queue:

```text
analytics
```

Before adding it:

* Update queue topology
* Ensure a worker consumes it
* Document priority
* Verify Supervisor/systemd
* Verify retry_after
* Verify deployment restart

If a dedicated worker is not justified, use an existing low-priority queue safely.

Do not dispatch jobs to an unconsumed queue.

---

# 38. Dispatch Strategy

Possible flow:

```text
1. Receive beacon
2. Validate event
3. Classify request
4. Perform deduplication
5. Store minimal accepted event or Redis buffer
6. Dispatch processing after response or asynchronously
7. Update daily aggregates
8. Reconcile post counter
```

Response to the browser should remain fast.

Do not perform expensive aggregation synchronously.

---

# 39. After-Response Processing

Laravel `dispatchAfterResponse()` may be considered for lightweight dispatch preparation.

Do not use it as a substitute for a supervised queue worker for expensive processing.

Verify behavior under PHP-FPM.

---

# 40. Post Visit Count Compatibility

Audit existing `posts.visit_count`.

Define its role:

```text
Fast lifetime display counter
```

Possible update strategy:

* Increment asynchronously.
* Reconcile from durable daily metrics.
* Cache current values.
* Avoid write on every page request where traffic is high.

Do not remove or reset the column.

Do not allow it to diverge silently from durable analytics without reconciliation.

---

# 41. Counter Reconciliation

Create a command:

```bash
php artisan analytics:reconcile
```

Possible behavior:

* Compare post lifetime counter with daily aggregate sum.
* Report mismatches.
* Repair only with explicit `--fix`.
* Support `--post=`.
* Support `--limit=`.
* Support `--dry-run`.
* Use transactions.
* Produce summary.

Default must be read-only or dry-run.

---

# 42. Analytics Aggregation Command

Create:

```bash
php artisan analytics:aggregate
```

Possible options:

```text
--date=
--from=
--to=
--post=
--missing
--rebuild
--dry-run
--limit=
```

Requirements:

* Idempotent
* Bounded
* Safe defaults
* No duplicate totals
* Resume-friendly
* Clear summary
* Non-zero exit on critical failure

---

# 43. Analytics Audit Command

Create:

```bash
php artisan analytics:audit
```

Check:

* Analytics schema
* Redis connectivity
* Queue availability
* Worker availability where observable
* Event backlog
* Failed jobs
* Duplicate events
* Missing daily aggregates
* Counter mismatches
* Orphaned metrics
* Future-dated metrics
* Bot-rate anomalies
* Storage growth
* Retention status

Do not output raw visitor identifiers.

---

# 44. Analytics Health Command

Create:

```bash
php artisan analytics:health
```

Possible checks:

* Database reachable
* Redis reachable
* Queue configured
* Analytics worker active
* Recent event processed
* Aggregate freshness
* No critical backlog
* No duplicate event integrity failure
* Post counter reconciliation status

Return:

```text
healthy
degraded
unhealthy
```

Use meaningful process exit codes.

---

# 45. Scheduled Aggregation

Use Laravel scheduler for:

* Flushing Redis counters
* Daily rollups
* Retention cleanup
* Counter reconciliation
* Health monitoring

Requirements:

* `withoutOverlapping`
* `onOneServer` where supported
* Lock expiry
* Idempotency
* Appropriate frequency
* Production cron verification

Do not schedule duplicate aggregation jobs on multiple servers without locking.

---

# 46. Retention Policy

Define retention by data type.

Example:

```text
Raw accepted events: 7–30 days
Hashed deduplication keys: minutes or hours
Daily aggregates: long-term
Failed-event diagnostics: bounded retention
Raw user-agent strings: not stored or short-term only
Raw referrer URLs: not stored or sanitized
```

Use configurable values.

Do not retain raw visitor-level data indefinitely without justification.

---

# 47. Cleanup Command

Create a safe retention command:

```bash
php artisan analytics:prune
```

Requirements:

* Dry-run default
* Date cutoff displayed
* Counts displayed
* Deletes only analytics-owned records
* Never deletes aggregate data unintentionally
* Never deletes posts or users
* Supports explicit `--force`
* Uses bounded chunks
* Logs summary

---

# 48. Analytics Configuration

Create centralized configuration.

Possible file:

```text
config/analytics.php
```

Possible keys:

```text
enabled
beacon_enabled
queue
dedupe_window
visitor_cookie_name
visitor_cookie_lifetime
raw_event_retention_days
bot_filtering
internal_traffic_filtering
redis_buffering
daily_aggregation
search_analytics
device_classification
geographic_analytics
dashboard_cache_ttl
```

Use safe defaults.

Do not commit secrets or office IP ranges.

---

# 49. Feature Flags

Possible flags:

```text
ANALYTICS_ENABLED
ANALYTICS_BEACON_ENABLED
ANALYTICS_REDIS_BUFFER_ENABLED
ANALYTICS_BOT_FILTER_ENABLED
ANALYTICS_SEARCH_TRACKING_ENABLED
ANALYTICS_REFERRER_TRACKING_ENABLED
ANALYTICS_DEVICE_TRACKING_ENABLED
ANALYTICS_GEO_ENABLED
```

The application must remain functional when analytics is disabled.

---

# 50. Dashboard Architecture

Use Phase 2.1-D and 2.1-E dashboard architecture.

Possible analytics pages:

```text
Analytics Overview
Post Analytics
Author Analytics
Reporter Analytics
Reviewer Analytics
Editor Analytics
Category Analytics
Traffic Sources
Search Analytics
Analytics Health
```

Do not expose every page to every role.

---

# 51. Super Admin Dashboard

May include:

* Total views
* Estimated unique visitors
* Published-post count
* Top posts
* Traffic sources
* Device breakdown
* Search performance
* Workflow throughput
* Analytics system health
* Queue backlog
* Data freshness

Keep widgets bounded and cached.

---

# 52. Admin Dashboard

May include:

* Site-wide traffic
* Top posts
* Author performance
* Category performance
* Traffic sources
* Publishing volume
* Operational health according to permissions

Do not expose system-level controls without permission.

---

# 53. Analytics Manager Dashboard

May include:

* Traffic overview
* Date-range filters
* Top content
* Author metrics
* Category metrics
* Referrers
* Devices
* Search metrics
* Data quality
* Export where authorized
* Health and freshness

Use the existing or canonical `analytics-manager` role if available.

If missing, align with Phase 2.1-B rather than creating an inconsistent role.

---

# 54. Editor Dashboard Analytics

May include:

* Published today
* Scheduled posts
* Top current stories
* Review-to-publish time
* Category performance
* Reporter output
* Pending editorial workload

Do not expose private user browsing data.

---

# 55. Reporter Dashboard Analytics

A reporter should generally see:

* Views on own posts
* Own top posts
* Own publishing count
* Own average views
* Own review outcomes
* Own correction rate
* Recent performance trend

Do not expose another reporter’s private draft analytics unless authorized.

---

# 56. Reviewer Dashboard Analytics

A reviewer may see:

* Assigned reviews
* Completed reviews
* Average review time
* Approval rate
* Correction-return count
* Published performance of reviewed stories where useful

Avoid turning metrics into punitive rankings.

---

# 57. SEO Manager Analytics

May include:

* Search-origin traffic
* Top organic landing pages
* Zero-result search trends
* Posts missing SEO fields
* Performance by category
* Google News-ready content metrics where data exists

Do not claim external search-engine ranking data unless imported from a verified source.

---

# 58. Post-Level Analytics

On a post resource or analytics page, show:

* Lifetime views
* Daily trend
* Deduplicated views
* Traffic sources
* Device type
* Search-origin views
* Publication date
* Author
* Category
* Data freshness
* Analytics status

Use permission checks.

Do not expose raw visitor lists.

---

# 59. Date-Range Filtering

Support bounded ranges.

Suggested presets:

```text
Today
Yesterday
Last 7 days
Last 30 days
This month
Previous month
Custom range
```

Set a reasonable maximum range for detailed reports.

Long ranges should use daily or monthly aggregates.

Do not query raw event tables for large ranges unnecessarily.

---

# 60. Dashboard Caching

Use Phase 2.1-G.

Cache:

* Aggregate KPI cards
* Top-post lists
* Category rankings
* Author summaries
* Traffic-source summaries
* Device summaries

Cache keys must include:

* Role scope
* User scope where applicable
* Date range
* Filters
* Analytics schema/version
* Environment

Do not cache private role-scoped dashboards globally.

---

# 61. Dashboard Query Performance

Requirements:

* Use aggregate tables.
* Avoid scanning raw events.
* Avoid N+1.
* Use bounded date ranges.
* Use proper indexes.
* Paginate large tables.
* Cache expensive summaries.
* Measure query plans.

Do not calculate all-time metrics from raw events on each dashboard load.

---

# 62. Analytics Exports

CSV export may be added only where justified.

Requirements:

* Permission-protected
* Bounded date range
* Queue large exports
* Exclude private visitor identifiers
* Escape spreadsheet formula injection
* Record export request
* Expire generated files
* Use safe storage path

Do not expose raw visitor-level exports by default.

---

# 63. Privacy Notice

Audit whether public privacy documentation must mention first-party analytics.

Document:

* What is collected
* Why it is collected
* How long it is kept
* Whether cookies are used
* Whether third parties receive data
* How internal staff use reports

Do not provide legal claims beyond verified implementation.

---

# 64. Consent Decision

Determine whether consent is required based on actual collection and applicable policy.

Do not assume that all first-party analytics is automatically exempt.

Do not implement a consent banner unless the project requires it.

Document the decision and any legal-review requirement.

---

# 65. Security Controls

Protect analytics endpoints from:

* Event spoofing
* Event replay
* Invalid post IDs
* Private-post enumeration
* Excessive request rates
* Oversized payloads
* Invalid content types
* CSRF bypass
* SQL injection
* Redis key injection
* Header spoofing
* Referrer injection
* UTM dimension explosion

Validate all input.

---

# 66. Rate Limiting

Apply analytics-specific rate limiting.

Consider:

* Visitor key
* IP hash
* Post ID
* Endpoint
* Time window

The limit must reduce abuse without losing normal readership.

Do not use raw client-provided IP headers.

---

# 67. Replay Protection

Use event ID uniqueness and bounded replay windows.

Duplicate event submissions should:

* Return a safe success or duplicate response.
* Not create additional counts.
* Not leak internal status.

---

# 68. Data Integrity Constraints

Use database constraints where practical:

* Unique event ID
* Unique post/date aggregate
* Foreign keys where safe
* Non-negative counters
* Valid metric date
* Indexed post ID
* Indexed metric date

Do not rely solely on application code for critical uniqueness.

---

# 69. Index Strategy

Audit query patterns before adding indexes.

Possible indexes:

```text
analytics_events.event_id
analytics_events.post_id
analytics_events.occurred_at
analytics_events.event_type
post_daily_metrics.post_id + metric_date
author_daily_metrics.author_id + metric_date
category_daily_metrics.category_id + metric_date
```

Use `EXPLAIN`.

Avoid redundant indexes.

---

# 70. Raw Event Storage Decision

Choose one:

## Option A: Aggregate-First

* Validate event
* Increment Redis or database aggregate
* Store minimal/no raw event
* Lowest storage cost

## Option B: Short-Lived Event Log

* Store accepted events temporarily
* Aggregate asynchronously
* Prune after retention period
* Better repairability

Choose based on traffic and audit needs.

Document the trade-off.

---

# 71. Failure Handling

Handle:

```text
Redis unavailable
Queue unavailable
Worker stopped
Database unavailable
Duplicate event
Invalid post
Unpublished post
Malformed user agent
Missing visitor cookie
Clock skew
Aggregation failure
Deadlock
Timeout
Retention failure
```

Article delivery must remain unaffected.

---

# 72. Graceful Degradation

If Redis is unavailable:

* Accept or drop analytics according to documented policy.
* Do not fail the article page.
* Optionally write directly to a bounded database path.
* Log a rate-limited warning.
* Avoid repeated expensive connection attempts.

If queue is unavailable:

* Do not run expensive processing synchronously by default.
* Return success to reader where safe.
* Track dropped or delayed events where possible.

Document data-loss trade-offs.

---

# 73. Logging

Log analytics operational context safely:

```text
Event ID
Post ID
Event type
Processing status
Duration
Queue
Duplicate flag
Bot classification
Internal flag
Error class
```

Do not log:

* Raw IP
* Full cookies
* Full session IDs
* Authorization tokens
* Full referrer URLs with sensitive query strings
* Raw private search terms
* User passwords

---

# 74. Monitoring

Track:

* Events received
* Events accepted
* Events rejected
* Duplicate events
* Bot events
* Internal events
* Queue backlog
* Processing failures
* Average processing time
* Aggregate freshness
* Redis memory use
* Raw-event table growth
* Prune status
* Counter mismatches

Use lightweight monitoring.

---

# 75. Analytics Data Freshness

Expose data freshness in dashboards.

Possible labels:

```text
Live estimate
Updated 2 minutes ago
Finalized through yesterday
Delayed
```

Do not present buffered estimates as finalized totals without indication.

---

# 76. Performance Baseline

Before implementation, measure:

* Article response time
* Cached article response time
* Database queries per article view
* Redis operations
* Queue dispatch time
* Dashboard query time
* Dashboard query count
* Database table sizes
* Existing post-counter write behavior

Use representative pages.

---

# 77. Performance Targets

Suggested targets:

```text
Analytics beacon response: under 150 ms under normal conditions
Article response overhead: negligible
No synchronous aggregate writes on cached article delivery
Dashboard KPI query: under 300 ms warm
No unbounded raw-event scan
No article failure when analytics infrastructure is degraded
```

Adapt to actual VPS performance.

Do not claim success without measurement.

---

# 78. Automated Tests

Create focused tests.

## 78.1 Configuration Tests

Verify:

* Safe defaults
* Analytics may be disabled
* Queue name is valid
* Dedupe TTL is bounded
* Retention values are bounded
* Cookie settings are safe
* Geographic tracking is disabled by default unless implemented

## 78.2 Beacon Endpoint Tests

Verify:

* Published post accepted
* Draft rejected without leaking existence
* Future scheduled post rejected
* Invalid post rejected safely
* Invalid payload rejected
* Oversized payload rejected
* Duplicate event is not counted twice
* Rate limit works
* Endpoint returns minimal response
* Article page remains independent

## 78.3 Visitor Tests

Verify:

* Random visitor key generated
* Existing visitor key reused
* Invalid cookie rotated
* Cookie contains no personal data
* Secure settings apply in production
* Cookie-disabled requests still work
* Authenticated staff may be excluded

## 78.4 Deduplication Tests

Verify:

* Same visitor/post within window counts once as deduplicated view
* Raw view policy is respected
* Different post counts separately
* Different visitor counts separately
* View after TTL may count again
* Redis failure follows documented fallback
* Keys use safe namespaces

## 78.5 Bot Tests

Verify:

* Search crawler classified
* Social preview classified
* Monitoring user agent classified
* Normal browser not classified as known bot
* Bot view does not inflate human metric
* Article remains accessible

## 78.6 Queue Tests

Verify:

* Job uses correct queue
* Job is idempotent
* Duplicate execution does not double count
* Retry is bounded
* Backoff is configured
* Timeout exists
* Database failure is recoverable
* Publication or article delivery is not dependent on analytics processing

## 78.7 Aggregation Tests

Verify:

* Daily post metrics created
* Existing row incremented atomically
* Author attribution works
* Category attribution follows documented rule
* Negative counters cannot occur
* Duplicate event does not double aggregate
* Rebuild is idempotent
* Date boundaries use configured timezone

## 78.8 Reconciliation Tests

Verify:

* Mismatch detected
* Dry-run does not write
* `--fix` repairs expected counter
* Specific post targeting works
* Unrelated posts remain unchanged
* Existing historical counter is preserved unless explicitly repaired

## 78.9 Workflow Metrics Tests

Verify:

* Submission counted
* Review assignment counted
* Approval counted
* Correction return counted
* Rejection counted
* Scheduled publication counted
* Publication counted
* Duplicate workflow event does not double count
* Duration calculations are correct

## 78.10 Dashboard Authorization Tests

Verify:

* Super Admin sees authorized analytics
* Admin sees permitted analytics
* Analytics Manager sees analytics pages
* Editor sees editorial metrics
* Reporter sees own metrics only
* Reviewer sees permitted review metrics
* Subscriber cannot access staff analytics
* Unauthorized export denied
* Raw visitor data unavailable

## 78.11 Dashboard Query Tests

Verify:

* Date range works
* Role scope works
* User scope works
* Cache key isolation works
* No private cross-user cache leakage
* Empty state works
* Large ranges use aggregate tables
* N+1 is prevented

## 78.12 Privacy and Security Tests

Verify:

* Raw IP is not persisted
* Raw session ID is not persisted
* Cookie contains no personal data
* Header spoofing is handled safely
* SQL injection payloads are harmless
* Redis key injection is prevented
* UTM values are bounded
* Referrer query strings are stripped or sanitized
* Event replay does not inflate counts
* Private posts are not enumerable

## 78.13 Command Tests

Verify:

* Audit command
* Health command
* Aggregate dry-run
* Aggregate date range
* Reconcile dry-run
* Reconcile fix
* Prune dry-run
* Prune force
* Commands use bounded chunks
* Critical failures return non-zero code

## 78.14 Cache Compatibility Tests

Verify:

* Cached article still sends analytics beacon
* Beacon fires once
* Full-page cache remains cacheable
* Analytics endpoint is excluded from full-page caching
* Analytics response is not cached publicly
* No CSRF token leakage into shared cache
* Livewire navigation does not double-count

## 78.15 Regression Tests

Verify:

* Homepage
* Article page
* Category archive
* Tag archive
* Author archive
* Search
* Login
* Filament
* Role dashboards
* Editorial workflow
* Scheduled publishing
* Redis
* Cache
* Queue
* Images
* Search
* SEO
* Sitemaps
* Feeds
* Legacy redirects
* WordPress importer

---

# 79. Real Traffic Verification

Verify using controlled requests:

```text
Normal browser article view
Repeated refresh
Different browser session
Authenticated admin view
Reporter preview
Known bot user agent
Social preview user agent
Cached article response
Livewire navigation
Redis outage simulation
Worker stopped simulation
Duplicate event replay
```

Do not generate uncontrolled production traffic.

---

# 80. Historical Data Migration

If historical `posts.visit_count` exists:

* Preserve it.
* Record it as legacy lifetime count.
* Define whether new analytics adds to it.
* Avoid pretending historical daily detail exists.
* Do not backfill invented daily distributions.
* Add reconciliation documentation.

Possible model:

```text
lifetime_views = legacy_baseline + new_aggregated_views
```

Document exact behavior.

---

# 81. Timezone Handling

Use the application’s configured timezone consistently.

Daily metrics must align with:

```text
Asia/Kolkata
```

unless the application config states otherwise.

Store timestamps consistently.

Test midnight boundaries.

Do not mix UTC dates and local reporting dates silently.

---

# 82. Monthly Rollups

Monthly rollups may be added if daily queries become expensive.

Do not add them prematurely without query evidence.

If implemented:

* Build from daily metrics.
* Make rebuild idempotent.
* Retain daily data.
* Document freshness.

---

# 83. Data Exports and Formula Injection

When exporting CSV, escape values beginning with:

```text
=
+
-
@
```

where spreadsheet formula injection is possible.

Do not export unsanitized referrers or campaign values.

---

# 84. Production Rollout Strategy

Use staged rollout.

Recommended stages:

```text
Stage 1:
Audit existing counters and traffic

Stage 2:
Add schema and configuration

Stage 3:
Add disabled beacon endpoint

Stage 4:
Enable tracking for controlled staff testing

Stage 5:
Enable public tracking for a small sample of posts

Stage 6:
Verify Redis deduplication

Stage 7:
Verify queue processing and aggregation

Stage 8:
Enable post-level analytics

Stage 9:
Enable role-scoped dashboard widgets

Stage 10:
Enable site-wide reporting

Stage 11:
Enable retention and reconciliation schedules
```

Do not enable site-wide tracking without verifying workers and storage behavior.

---

# 85. Deployment Procedure

Recommended sequence:

```text
1. Back up database
2. Verify Redis health
3. Verify queue health
4. Verify worker supervision
5. Deploy code
6. Run additive migrations
7. Clear and rebuild configuration cache
8. Verify analytics feature flags
9. Run analytics audit
10. Test beacon on one post
11. Verify Redis dedupe key
12. Verify real queue processing
13. Verify durable aggregate
14. Verify post counter behavior
15. Verify role-based dashboards
16. Verify cached article tracking
17. Monitor storage and queue backlog
18. Expand rollout gradually
```

---

# 86. Rollback Plan

Document rollback for:

* Inflated counts
* Duplicate events
* Queue backlog
* Redis memory growth
* Database write pressure
* Article performance regression
* Cookie issue
* Privacy issue
* Dashboard authorization bug
* Incorrect author attribution
* Broken cached-page tracking
* Worker failure
* Migration issue

Possible rollback:

```text
Disable analytics feature flag
Disable beacon rendering
Stop analytics worker
Disable scheduled aggregation
Preserve collected data
Revert dashboard widgets
Revert application commit
Roll back additive migrations only after review
Restore previous visit-count behavior
```

Do not delete collected data automatically during rollback.

---

# 87. Documentation Deliverables

Create or update:

```text
docs/version-2.1/phase-2.1-k-analytics.md
docs/version-2.1/analytics-existing-state-audit.md
docs/version-2.1/analytics-event-contract.md
docs/version-2.1/analytics-visitor-identity.md
docs/version-2.1/analytics-privacy-and-retention.md
docs/version-2.1/analytics-bot-filtering.md
docs/version-2.1/analytics-cache-compatibility.md
docs/version-2.1/analytics-redis-architecture.md
docs/version-2.1/analytics-database-architecture.md
docs/version-2.1/analytics-queue-runbook.md
docs/version-2.1/analytics-aggregation-strategy.md
docs/version-2.1/analytics-dashboard-permissions.md
docs/version-2.1/analytics-workflow-metrics.md
docs/version-2.1/analytics-search-integration.md
docs/version-2.1/analytics-health-monitoring.md
docs/version-2.1/analytics-performance-baseline.md
docs/version-2.1/analytics-performance-comparison.md
docs/version-2.1/analytics-production-rollout.md
docs/version-2.1/analytics-rollback-plan.md
```

Documentation must include:

* Existing analytics audit
* Existing visit-count behavior
* Event contract
* Visitor strategy
* Cookie behavior
* IP policy
* Bot classification
* Internal traffic policy
* Full-page cache compatibility
* Redis keys
* Database schema
* Aggregation
* Queue behavior
* Retry and timeout
* Retention
* Reconciliation
* Dashboard role scopes
* Workflow metrics
* Search analytics readiness
* Security controls
* Privacy considerations
* Operational commands
* Performance results
* Production rollout
* Rollback
* Test results
* Known limitations
* Deferred items

Do not include secrets, raw visitor IDs or private IP data.

---

# 88. Completion Criteria

Phase 2.1-K is complete only when:

* Existing analytics and visit-count behavior is audited.
* Existing post counters remain preserved.
* Analytics event contract is defined.
* Anonymous visitor strategy is implemented safely.
* No browser fingerprinting is used.
* IP handling is privacy-safe.
* Bot filtering is implemented or documented with evidence.
* Internal staff traffic is classified or excluded.
* Cached article pages can generate analytics events.
* Beacon endpoint is validated and rate-limited.
* Duplicate events do not inflate metrics.
* Deduplication has a bounded TTL.
* Redis keys are namespaced.
* Queue jobs are idempotent.
* Queue workers are verified or limitations are reported.
* Daily post metrics exist.
* Author metrics exist where required.
* Category or primary-category metrics exist where required.
* Workflow metrics use existing workflow data.
* Role-based analytics dashboards respect authorization.
* Reporter analytics is limited to authorized content.
* Raw visitor lists are not exposed.
* Dashboard queries use aggregate tables.
* Analytics caches are role- and date-scoped.
* Audit, health, aggregate, reconcile and prune commands exist as required.
* Pruning defaults to dry-run.
* Retention policy is documented.
* Performance is measured.
* Article delivery survives analytics failures.
* Full-page cache compatibility is tested.
* Real event processing is verified.
* Focused tests are executed.
* Full regression results are reported honestly.
* Documentation is complete.

---

# 89. Deferred Items

Do not implement in this phase unless explicitly approved:

* Cross-site tracking
* Browser fingerprinting
* Exact location tracking
* User-level behavioral profiles
* Advertising conversion attribution
* Revenue attribution
* Heatmaps
* Session replay
* Third-party marketing pixels
* Google Analytics migration
* Matomo deployment
* Plausible deployment
* Real-time WebSocket dashboard
* AI content recommendations
* AI audience prediction
* Personalized news feeds
* External BI warehouse
* BigQuery integration
* Data sale or sharing
* Newsletter analytics
* News-Man analytics
* Mobile-app analytics

---

# 90. Required Completion Report Format

Return the completion report using this exact structure:

## 1. Executive Summary

## 2. Existing Analytics Audit

## 3. Existing Post View Count Behavior

## 4. Existing Visitor Data Assessment

## 5. Analytics Architecture Decision

## 6. Analytics Event Contract

## 7. Event Idempotency

## 8. Visitor Identity Strategy

## 9. Visitor Cookie Implementation

## 10. Session Identity Handling

## 11. Authenticated User Handling

## 12. IP Address and Proxy Policy

## 13. Page View Definition

## 14. Unique View Definition

## 15. Deduplication Architecture

## 16. Bot Detection and Classification

## 17. Internal Traffic Filtering

## 18. Full-Page Cache Compatibility

## 19. Analytics Beacon Endpoint

## 20. Livewire Navigation Handling

## 21. Redis Counter Architecture

## 22. Durable Database Architecture

## 23. Raw Event Storage Decision

## 24. Post Daily Metrics

## 25. Author Daily Metrics

## 26. Category and Tag Attribution

## 27. Existing Visit Count Compatibility

## 28. Counter Reconciliation

## 29. Workflow Analytics

## 30. Reporter Metrics

## 31. Reviewer Metrics

## 32. Editor Metrics

## 33. SEO and Search Analytics Readiness

## 34. Referrer Classification

## 35. UTM Attribution

## 36. Device Classification

## 37. Geographic Analytics Decision

## 38. Analytics Queue Architecture

## 39. Analytics Processing Job

## 40. Queue Retry, Backoff and Timeout

## 41. Aggregation Architecture

## 42. Scheduled Analytics Tasks

## 43. Retention Policy

## 44. Pruning Architecture

## 45. Analytics Configuration and Feature Flags

## 46. Super Admin Analytics Dashboard

## 47. Admin Analytics Dashboard

## 48. Analytics Manager Dashboard

## 49. Editor Analytics Dashboard

## 50. Reporter Analytics Dashboard

## 51. Reviewer Analytics Dashboard

## 52. SEO Manager Analytics Dashboard

## 53. Post-Level Analytics

## 54. Date-Range Filtering

## 55. Dashboard Caching

## 56. Dashboard Query Performance

## 57. Export Decision and Security

## 58. Privacy and Consent Assessment

## 59. Security Controls

## 60. Rate Limiting and Replay Protection

## 61. Data Integrity Constraints

## 62. Database Index Changes

## 63. Failure and Degradation Handling

## 64. Analytics Logging

## 65. Analytics Monitoring

## 66. Analytics Data Freshness

## 67. Analytics Audit Command

## 68. Analytics Health Command

## 69. Analytics Aggregate Command

## 70. Analytics Reconcile Command

## 71. Analytics Prune Command

## 72. Historical Visit Count Migration

## 73. Timezone Handling

## 74. Performance Baseline

## 75. Performance Comparison

## 76. Real Event Processing Verification

## 77. Cached-Page Tracking Verification

## 78. Bot and Duplicate Verification

## 79. Automated Tests Added or Updated

## 80. Beacon Endpoint Test Results

## 81. Visitor and Deduplication Test Results

## 82. Queue and Aggregation Test Results

## 83. Workflow Metrics Test Results

## 84. Dashboard Authorization Test Results

## 85. Privacy and Security Test Results

## 86. Cache Compatibility Test Results

## 87. Regression Test Results

## 88. Full Test-Suite Result

## 89. Production Rollout Procedure

## 90. Rollback Procedure

## 91. Backward-Compatibility Verification

## 92. Documentation Created

## 93. Files Created or Modified

## 94. Commands Executed

## 95. Risks and Open Questions

## 96. Deferred Items

## 97. Final Phase Decision

The final phase decision must be exactly one of:

```text
COMPLETE
COMPLETE WITH CONDITIONS
INCOMPLETE
```

Explain the decision using verified tracking, deduplication, queue, aggregation, authorization, privacy, performance and test evidence.

---

# 91. Strict Rules

* Audit existing analytics before implementation.
* Preserve existing post visit counts.
* Do not reset historical counters.
* Do not invent historical daily analytics.
* Keep article delivery independent from analytics.
* Do not slow cached article responses with synchronous writes.
* Do not use browser fingerprinting.
* Do not implement cross-site tracking.
* Do not store raw IP addresses indefinitely.
* Do not trust arbitrary proxy headers.
* Do not expose raw visitor identifiers.
* Do not expose private user browsing histories.
* Do not expose drafts through analytics endpoints.
* Do not expose whether a private post exists.
* Validate every analytics event.
* Bound payload size.
* Rate-limit the beacon endpoint.
* Prevent duplicate events.
* Use namespaced Redis keys.
* Use bounded Redis TTLs.
* Do not create unbounded Redis keys.
* Do not use `Cache::flush()`.
* Do not use `FLUSHALL`.
* Do not use `FLUSHDB`.
* Do not dispatch jobs to an unconsumed queue.
* Make analytics jobs idempotent.
* Use bounded retries.
* Use bounded timeouts.
* Use safe backoff.
* Do not serialize request objects.
* Do not calculate dashboards from raw events repeatedly.
* Use aggregate tables.
* Scope dashboard cache by role, user and date range.
* Do not leak one reporter’s private analytics to another.
* Do not expose analytics pages to subscribers.
* Do not export visitor-level data by default.
* Pruning must default to dry-run.
* Reconciliation must default to dry-run.
* Do not delete posts, users or imported records.
* Do not modify editorial workflow rules.
* Do not modify roles or permissions inconsistently.
* Do not change slugs or public URLs.
* Do not break SEO, search, images, cache, queues or Google News.
* Do not modify `.env` without deployment authority.
* Do not upgrade unrelated dependencies.
* Do not run destructive database commands.
* Do not claim unique-human accuracy without qualification.
* Do not claim performance improvement without measurements.
* Do not claim real queue processing using only fakes.
* Clearly report dropped events, skipped tests and environmental failures.
* Preserve backward compatibility.
