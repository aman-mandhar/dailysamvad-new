# Phase 2.3H — Queue, Security & Rate Limiting

## Project

Daily Samvad — WordPress to Laravel Migration / NewsMan CMS Foundation

Current stack:

- Laravel 12
- PHP 8.3+
- Blade frontend
- Livewire where already applicable
- Filament admin
- Vite
- Redis
- Laravel queues
- Existing role/permission system
- Existing Reporter → Reviewer/Editor → Publish workflow
- Existing media/image system
- Existing caching architecture
- Phase 2.3A — Firebase & Browser Push Foundation
- Phase 2.3B — Push Subscription Management
- Phase 2.3C — Laravel Push Engine
- Phase 2.3D — Post Publish Automation
- Phase 2.3E — Filament Push Notification Panel
- Phase 2.3F — Topics & Category Preferences
- Phase 2.3G — Analytics & Click Tracking

This phase continues:

# Version 2.3 — Push Notification System

---

# Phase Objective

Harden the push notification system for reliable production use at scale.

Current architecture approximately provides:

```text
PushSubscription
        ↓
Topic Preferences
        ↓
Audience Resolver
        ↓
PushNotification
        ↓
PushNotificationDelivery
        ↓
Laravel Queue
        ↓
Firebase HTTP v1
        ↓
FCM Accepted / Failed
        ↓
Click Tracking
        ↓
Analytics
```

Phase 2.3H must make this architecture safe under:

- high subscriber volume;
- duplicate worker execution;
- Firebase quota pressure;
- temporary Firebase outages;
- network failures;
- malformed requests;
- repeated subscription requests;
- abusive public endpoints;
- queue backlog;
- stale FCM tokens;
- manual-send mistakes;
- production worker restarts;
- cache and Redis outages where practical.

---

# Primary Goals

Implement:

1. production queue hardening;
2. notification-specific queue configuration;
3. safe worker concurrency strategy;
4. bounded retries;
5. exponential or progressive backoff;
6. retry classification enforcement;
7. FCM quota/rate-limit handling;
8. Laravel rate limiting;
9. subscription endpoint abuse protection;
10. preference endpoint abuse protection;
11. click endpoint abuse safeguards;
12. manual campaign send safeguards;
13. duplicate-job protection;
14. duplicate-delivery protection;
15. queue overlap protection;
16. stale subscription cleanup;
17. invalid token cleanup;
18. dead/stuck delivery recovery strategy;
19. operational Artisan commands;
20. production-safe logging;
21. monitoring readiness;
22. tests;
23. documentation.

---

# Strict Scope Boundary

Phase 2.3H is:

# Queue + Security + Rate Limiting + Production Hardening

Do NOT implement:

- new analytics features;
- A/B testing;
- AI targeting;
- behavioral profiling;
- geographic targeting;
- paid campaign billing;
- conversion attribution;
- mobile app implementation;
- major infrastructure redesign;
- new notification content features;
- recurring marketing automation.

Phase 2.3I will handle:

# Testing + Production Deployment + Final Audit

---

# Critical First Step — Audit Existing Push System

Before modifying code inspect the real implementation created by Phase 2.3A–2.3G.

At minimum inspect:

```text
app/Jobs/Push/
app/Services/Push/
app/Models/PushSubscription.php
app/Models/PushNotification.php
app/Models/PushNotificationDelivery.php
app/Http/Controllers/
app/Http/Requests/Push/
app/Filament/
config/
routes/
database/migrations/
docs/push-notifications/
tests/Feature/Push/
```

Also inspect:

```text
config/queue.php
config/cache.php
config/database.php
app/Console/
routes/console.php
bootstrap/app.php
```

and any:

```text
Supervisor
systemd
deployment
queue worker
Redis
Horizon
```

configuration already represented in the repository.

Do not assume Horizon exists.

Do not install it simply because Redis is available.

---

# Protected Existing Functionality

Do not break:

- browser push registration;
- subscription lifecycle;
- Firebase HTTP v1 delivery;
- Post auto-push;
- manual Filament push;
- topic preferences;
- audience targeting;
- analytics;
- click tracking;
- post publishing;
- cache invalidation;
- user roles;
- Filament resources;
- queues used by other project features;
- image processing queues;
- WordPress importer;
- SEO;
- frontend.

---

# Core Reliability Principle

Push notification infrastructure is a side-effect system.

Failures must not block:

```text
Post publishing
User browsing
Admin login
Normal frontend rendering
```

---

# Step 1 — Audit Current Queue Contract

Determine:

- queue connection used by push jobs;
- queue name;
- retries;
- backoff;
- timeout;
- job payload;
- uniqueness protections;
- failed-job behavior;
- Redis availability;
- existing queue-worker deployment.

Do not blindly create another queue architecture.

---

# Step 2 — Dedicated Push Queue

If not already present, use a clear queue name such as:

```text
push
```

or existing project-equivalent.

Push delivery jobs must not overwhelm critical application jobs.

Avoid mixing massive broadcasts blindly into:

```text
default
```

if production already uses default queue for important work.

---

# Step 3 — Do Not Hardcode Queue Driver

Jobs must continue using Laravel queue configuration.

Do not hardcode:

```text
redis
```

inside business logic.

Production may use Redis.

Tests may use sync/fake.

---

# Step 4 — Queue Worker Isolation

Document recommended worker separation:

```text
default queue workers
push queue workers
image/media queue workers
```

where architecture supports it.

Do not alter unrelated worker deployment automatically.

---

# Step 5 — Job Timeout

Set a sensible timeout on push-delivery jobs.

The job must not hang forever when Firebase/network stalls.

Coordinate with the HTTP timeout already defined in Phase 2.3C.

Job timeout must be greater than individual HTTP timeout.

---

# Step 6 — Retry Count

Use bounded retry count.

Do not allow infinite retries.

Choose a practical value based on existing classification architecture.

Example concept:

```text
tries = 4 or 5
```

Actual value should be justified.

---

# Step 7 — Progressive Backoff

Use increasing delays.

Example concept:

```text
30 seconds
2 minutes
5 minutes
15 minutes
```

or equivalent.

Do not retry every second.

---

# Step 8 — Retry Classification

Only retry failures classified as retryable.

Typical retryable categories:

```text
network
timeout
Firebase 5xx
temporary quota/rate pressure
temporary auth refresh issue
```

Typical permanent categories:

```text
UNREGISTERED
clearly invalid registration token
invalid permanent message request
inactive subscription
missing subscription
```

Reuse Phase 2.3C/G classifications.

Do not create conflicting error logic.

---

# Step 9 — Never Retry Invalid Token

Permanent invalid token:

```text
delivery → failed
subscription → inactive
job → terminal
```

No further retries.

---

# Step 10 — Auth Failure Safety

Do not disable subscriber because Firebase server credentials are invalid.

Infrastructure auth failures belong to system configuration.

---

# Step 11 — Retry-After Support

Inspect Firebase response handling.

If API returns retry timing guidance:

```text
Retry-After
```

or equivalent server-side throttling signal, make retry architecture capable of honoring it where practical.

Do not create unsafe immediate retries.

---

# Step 12 — Quota Failure

Quota pressure should be classified separately.

Example:

```text
error_category = quota
retryable = true
```

Use controlled backoff.

---

# Step 13 — Campaign-Level Fan-Out Throttling

Do not enqueue uncontrolled millions of jobs instantly if architecture can avoid it.

Inspect current fan-out.

Implement reasonable chunked dispatch.

---

# Step 14 — Audience Chunk Size

Make chunk size configurable.

Example:

```php
'fanout_chunk_size' => 500,
```

Actual value should be chosen based on architecture and tests.

Do not scatter hardcoded values.

---

# Step 15 — Queue Dispatch Chunking

Expected pattern:

```text
Audience Builder
      ↓
chunkById
      ↓
Delivery records
      ↓
Queue jobs
```

Do not:

```php
PushSubscription::active()->get()
```

for large audiences.

---

# Step 16 — Avoid Massive Transactions

Do not create 100,000 delivery records inside one DB transaction.

Use bounded chunks.

---

# Step 17 — Queue Flood Protection

Dispatching a notification to a very large audience should not exhaust:

- PHP memory;
- DB connection pool;
- Redis memory;
- request timeout.

Filament/manual sends must return quickly.

---

# Step 18 — Fan-Out Orchestrator

If current architecture performs fan-out synchronously from Filament or Post event, consider a parent orchestration job.

Example:

```text
QueuePushNotificationFanout
```

that:

```text
loads notification
resolves audience
creates deliveries in chunks
dispatches child jobs
```

Only implement if it materially improves current architecture.

Do not add redundant queue layers without need.

---

# Step 19 — Fan-Out Job Idempotency

A parent fan-out job retried twice must not create duplicate recipient deliveries.

Use DB unique constraint from Phase 2.3G.

---

# Step 20 — Delivery Unique Constraint

Verify database enforces equivalent of:

```text
UNIQUE(push_notification_id, push_subscription_id)
```

or another stable unique delivery key.

This is mandatory for duplicate safety.

---

# Step 21 — Safe Upsert

Use:

```text
upsert
insertOrIgnore
firstOrCreate
```

appropriately.

Do not rely only on application-level check.

Database remains final duplicate-protection layer.

---

# Step 22 — Duplicate Worker Execution

A queue job may theoretically execute more than once.

If delivery status is already:

```text
accepted
```

job should no-op.

Do not send FCM again.

---

# Step 23 — Atomic Delivery Claim

Use atomic state transition where practical.

Example:

```text
queued/retry_pending
→
attempting
```

Only one worker should successfully claim a delivery.

---

# Step 24 — Attempting Lock Timeout

A worker may crash after setting:

```text
attempting
```

Do not leave delivery stuck forever.

Add/maintain:

```text
last_attempted_at
```

and define stale-attempt recovery.

---

# Step 25 — Stale Attempt Recovery

Implement an Artisan command or service to identify:

```text
status = attempting
last_attempted_at older than threshold
```

and safely mark retryable or requeue.

Do not automatically resend accepted deliveries.

---

# Step 26 — Example Recovery Command

Possible:

```bash
php artisan push:recover-stuck
```

Use project naming conventions.

---

# Step 27 — Recovery Dry Run

Operational commands that mutate many records should support:

```text
--dry-run
```

where useful.

---

# Step 28 — Recovery Limit

Support reasonable:

```text
--limit=
```

to avoid runaway operations.

---

# Step 29 — Failed Jobs

Inspect Laravel failed-job setup.

Ensure push job failures remain visible through normal Laravel mechanisms.

Do not create a duplicate failed-job framework.

---

# Step 30 — Job failed() Hook

If job permanently fails because retries exhausted:

update corresponding delivery safely.

Possible:

```text
status = failed
error_category = queue_exhausted
```

Do not expose sensitive exception details.

---

# Step 31 — Delivery Failure Accuracy

Do not overwrite an already accepted delivery with failed state from a late duplicate job.

Use conditional updates.

---

# Step 32 — Job Serialization

Keep job payload small.

Prefer:

```text
delivery ID
```

over:

```text
full PushSubscription model
full PushNotification model
raw FCM token
huge arrays
```

---

# Step 33 — No Credentials in Queue Payload

Never serialize:

```text
service-account JSON
OAuth access token
Firebase private key
```

into queue jobs.

---

# Step 34 — No Raw Token in Failed Jobs

Where possible, job payload should not include raw FCM token.

Resolve token from PushSubscription at execution time.

This reduces token exposure in serialized jobs / failed_jobs.

---

# Step 35 — Subscription Endpoint Rate Limiting

Public subscription endpoints from Phase 2.3B must be protected.

Examples:

```text
POST /push/subscriptions
DELETE /push/subscriptions
```

Use Laravel rate limiter.

---

# Step 36 — Registration Rate Limit

Set a reasonable limit that allows real browsers but prevents abuse.

Do not block normal token refresh unnecessarily.

Use a combination such as:

```text
IP
device UUID
session
```

only where appropriate.

Do not use device UUID as trusted identity.

---

# Step 37 — No Fingerprinting

Do not add fingerprinting to improve rate limits.

---

# Step 38 — Preference Endpoint Rate Limiting

Protect:

```text
GET/PUT /push/preferences
```

from abusive repeated requests.

GET should allow normal UI interaction.

PUT should have stricter mutation limit.

---

# Step 39 — Click Endpoint Rate Limiting

Click tracking GET requests must remain user-friendly.

Do not use a tiny limit that prevents legitimate repeated clicks.

Apply lightweight abuse protection only.

---

# Step 40 — Click Counting Integrity

Do not attempt to guarantee human-only clicks.

CTR represents tracking endpoint clicks.

Document this.

---

# Step 41 — Manual Send Rate Limit

Authorized Filament send action should also have a server-side safety limit.

Do not rely only on button confirmation.

---

# Step 42 — Mass Send Cooldown

Consider a short configurable cooldown for manual campaigns.

Example concept:

```text
same operator cannot initiate dozens of mass campaigns per minute
```

Do not prevent legitimate editorial breaking-news operations.

Use sensible configuration.

---

# Step 43 — Auto-Publish Exclusion

Manual-send rate limiting must not block automatic Post publish notifications.

Separate rate-limit contexts.

---

# Step 44 — Global Emergency Disable

Provide a configuration switch such as:

```dotenv
PUSH_SENDING_ENABLED=true
```

or equivalent config.

This should disable actual outbound FCM delivery while preserving:

- subscriptions;
- preference management;
- admin visibility;
- analytics browsing.

---

# Step 45 — Auto Publish Switch

Preserve existing:

```text
PUSH_AUTO_PUBLISH_ENABLED
```

or equivalent.

Do not merge these semantics.

---

# Step 46 — Two-Level Control

Desired behavior:

```text
PUSH_SENDING_ENABLED=false
→ no outbound FCM sends anywhere

PUSH_AUTO_PUBLISH_ENABLED=false
→ manual/test sending can still work
→ automatic Post push disabled
```

---

# Step 47 — Safe Default

For production deployment docs, recommend outbound sending remains disabled until final Phase 2.3I verification if not already live.

Do not change existing deployment unexpectedly.

---

# Step 48 — Maintenance Mode Awareness

If Laravel is in maintenance mode, queued push jobs may still run depending on worker configuration.

Document desired behavior.

Do not blindly suppress emergency editorial sends.

---

# Step 49 — Subscription Endpoint Validation

Review all Push Form Requests.

Ensure sensible limits for:

```text
token
device_uuid
browser
platform
timezone
language
```

Prevent oversized payload abuse.

---

# Step 50 — JSON Body Limits

Application-level validation must reject absurdly large metadata strings.

Do not rely solely on Nginx limits.

---

# Step 51 — Topic Preference Validation

Limit number of topic IDs accepted in one request to a sensible maximum based on actual topic count.

Avoid payload containing millions of IDs.

---

# Step 52 — Manual Notification Validation

Review Filament/manual push limits.

Protect against:

```text
massive title
massive body
huge custom data payload
unsafe URL
```

Reuse existing PushMessage validation.

---

# Step 53 — URL Security

Reconfirm manual notification target URLs cannot use:

```text
javascript:
data:
file:
```

or unsafe schemes.

---

# Step 54 — Tracking Redirect Security

Reconfirm click endpoint redirects only to stored trusted notification target.

Do not accept redirect destination from query string.

---

# Step 55 — Open Redirect Tests

Retain/add explicit tests.

---

# Step 56 — Public ID Enumeration

Delivery tracking public IDs must remain high entropy.

Do not replace them with sequential IDs.

---

# Step 57 — Click Route Leakage

404 response for invalid tracking ID should not disclose whether a user/subscription exists.

---

# Step 58 — CSRF

Keep CSRF on mutation endpoints when using Laravel web routes.

Do not disable CSRF globally.

---

# Step 59 — GET Click Route

GET tracking endpoint may mutate only analytics counters/timestamps and redirect.

Do not attach any privileged actions.

---

# Step 60 — Authorization

Manual push send remains permission-protected.

Analytics remains permission-protected.

Topic/admin configuration remains permission-protected.

Do not weaken Phase 2.3E/G policies.

---

# Step 61 — Mass Assignment Review

Review:

```text
PushSubscription
PushNotification
PushNotificationDelivery
PushTopic
```

models.

Ensure protected fields cannot be client-controlled unintentionally.

---

# Step 62 — Status Field Protection

Client/UI must not arbitrarily set:

```text
accepted
failed
queued
sent
recipient_count
created_by
```

Business services own lifecycle fields.

---

# Step 63 — Logging Security

Never log:

```text
raw FCM token
OAuth bearer token
Firebase private key
service-account JSON
Authorization headers
```

---

# Step 64 — Safe Log Context

Allowed examples:

```text
notification_id
delivery_id
subscription_id
token_hash prefix
error category
HTTP status
attempt count
```

---

# Step 65 — Error Log Sampling

High-volume FCM failures can flood logs.

Avoid logging identical expected invalid-token failure at noisy error level for every token if current logging architecture can summarize safely.

Do not hide unexpected failures.

---

# Step 66 — Invalid Token Cleanup

Permanent invalid token response already deactivates subscription.

Now implement cleanup strategy for old inactive subscriptions.

---

# Step 67 — Stale Subscription Definition

Use configurable thresholds.

Possible stale candidates:

```text
inactive for N days
last_seen_at older than N days
unsubscribed_at older than N days
```

Do not delete active subscriptions just because they are old without strong reason.

---

# Step 68 — Cleanup Command

Create an operational command such as:

```bash
php artisan push:prune-subscriptions
```

or equivalent.

---

# Step 69 — Cleanup Dry Run

Support:

```bash
php artisan push:prune-subscriptions --dry-run
```

This is strongly recommended.

---

# Step 70 — Cleanup Age

Make threshold configurable.

Example:

```text
PUSH_INACTIVE_RETENTION_DAYS
```

or config default.

Do not scatter retention number.

---

# Step 71 — Soft Lifecycle Preservation

If analytics delivery rows reference subscriptions with:

```text
nullOnDelete()
```

then hard deletion of stale subscription may be acceptable after retention.

But inspect actual DB relationships.

---

# Step 72 — Preference Cleanup

If subscription is deleted, topic pivots should cascade safely.

---

# Step 73 — Do Not Delete Historical Analytics

Pruning subscription must not delete:

```text
PushNotification
PushNotificationDelivery
click analytics
```

history unintentionally.

---

# Step 74 — Delivery Retention Preparation

Phase 2.3G documented large table growth.

Implement optional delivery pruning only if required by specification and safe.

Preferred:

Prepare configuration/docs in 2.3H, but avoid deleting analytics before production retention policy is decided.

---

# Step 75 — Optional Delivery Prune Command

If implemented:

```bash
php artisan push:prune-deliveries --dry-run
```

must preserve parent analytics or aggregate data appropriately.

Do not destroy metrics blindly.

---

# Step 76 — Better Default

Unless existing architecture already has aggregates:

Do NOT hard-delete delivery analytics yet.

Document future retention.

---

# Step 77 — Scheduler Integration

If cleanup commands are scheduled, inspect Laravel scheduler conventions.

Do not add duplicate schedulers.

---

# Step 78 — Conservative Cleanup Schedule

Inactive-subscription cleanup might run:

```text
daily
```

or:

```text
weekly
```

depending on scale.

Do not run every minute.

---

# Step 79 — Condition-Based Cleanup

Use bounded batches.

Avoid giant delete transaction.

---

# Step 80 — Cleanup Batch Size

Configurable batch size.

Example:

```text
500
1000
```

depending on architecture.

---

# Step 81 — DB Index Audit

Review indexes supporting:

```text
push_subscriptions.is_active
push_subscriptions.last_seen_at
push_subscriptions.unsubscribed_at

push_notification_deliveries.status
push_notification_deliveries.last_attempted_at
push_notification_deliveries.push_notification_id
push_notification_deliveries.push_subscription_id
```

Add only genuinely needed indexes.

---

# Step 82 — Index Migration Safety

Use additive migrations.

Do not rebuild giant tables unnecessarily if avoidable.

---

# Step 83 — Rate Limiter Configuration

Use Laravel's native RateLimiter architecture.

Keep limiter definitions centralized.

Possible names:

```text
push-subscribe
push-preferences
push-click
push-manual-send
```

Use existing project conventions.

---

# Step 84 — Trusted Proxies

If rate limiting uses IP address, inspect current proxy/Cloudflare/Varnish setup.

Do not blindly trust arbitrary forwarded headers.

Use Laravel's configured trusted proxy behavior.

---

# Step 85 — Cloudflare Awareness

Production sits behind caching/proxy layers.

Document that public IP-based rate limiting depends on correct trusted-proxy configuration.

Do not modify infrastructure headers blindly.

---

# Step 86 — Full-Page Cache

Ensure mutation endpoints and tracking endpoints bypass page cache.

At minimum:

```text
POST /push/*
PUT /push/*
DELETE /push/*
GET /push/click/*
```

should not be cached as normal content.

---

# Step 87 — Varnish Awareness

Document required bypass behavior.

Do not globally disable Varnish.

---

# Step 88 — Cloudflare Cache Awareness

Do not globally bypass entire site.

Only push mutation/tracking endpoints require special care.

---

# Step 89 — Service Worker Cache

Ensure service worker does not cache click tracking responses.

---

# Step 90 — Redis Failure

Queue relies on configured backend.

Do not implement complicated automatic fallback to synchronous FCM delivery.

If Redis is unavailable:

```text
Post publishing still succeeds
Push dispatch may fail/recover operationally
```

This is safer than synchronous mass send.

---

# Step 91 — Never Fall Back to Sync Broadcast

Do not respond to queue outage by performing thousands of FCM calls inside HTTP request.

Mandatory.

---

# Step 92 — Manual Queue Health Check

Before manual mass send, optionally perform a lightweight configuration/queue availability check if a reliable project mechanism exists.

Do not create fragile Redis-specific ping logic unless necessary.

---

# Step 93 — Admin Warning

If dispatch fails immediately:

show:

```text
Push notification could not be queued.
```

Do not claim success.

---

# Step 94 — Queue Monitoring

Do not build a complete monitoring dashboard.

But provide operational commands/docs to inspect:

```text
failed jobs
stuck deliveries
retryable failures
queue worker status
```

---

# Step 95 — Push Health Command

Consider:

```bash
php artisan push:health
```

This is recommended if it can be implemented cleanly.

---

# Step 96 — Health Command Checks

Possible safe checks:

```text
push config enabled
Firebase project configured
credential file readable
queue configured
cache configured
active subscription count
queued delivery count
attempting/stuck count
failed/retryable count
```

Do not send an actual notification unless explicitly requested.

---

# Step 97 — Health Exit Code

Return useful non-zero exit status for serious misconfiguration.

Do not expose secrets.

---

# Step 98 — Health Command Production Safety

No raw token output.

No OAuth token output.

No credential path contents.

---

# Step 99 — Manual Test Command

Preserve Phase 2.3C `push:test`.

Ensure it respects:

```text
PUSH_SENDING_ENABLED
```

unless explicit force behavior is intentionally designed.

---

# Step 100 — Force Option

If `--force` exists, it should not bypass security accidentally.

Document exact behavior.

---

# Step 101 — Broadcast Safety

No CLI command without explicit target should broadcast to all subscribers.

Maintain this mandatory rule.

---

# Step 102 — Campaign Send Lock

Use a lock or atomic campaign status transition so two processes cannot fan out same manual notification simultaneously.

Phase 2.3E already introduced duplicate protection.

Audit and harden it.

---

# Step 103 — Auto-Publish Lock

Phase 2.3D idempotency must remain durable.

Do not rely solely on Redis lock.

---

# Step 104 — Post Push Marker

If `push_notified_at` or equivalent exists:

preserve it.

Do not allow queue retry to create a second campaign record.

---

# Step 105 — Notification-Level Unique Key

Consider a stable idempotency key for automatic notifications.

Example concept:

```text
post:{post_id}:publish
```

Do not introduce if existing durable model constraint already covers this.

---

# Step 106 — Manual Notification Unique Identity

Existing PushNotification record itself serves as campaign identity.

---

# Step 107 — Batch Dispatch Failure

If parent fan-out job fails midway:

existing unique delivery constraints should allow safe retry from beginning without duplicate rows.

This is important.

---

# Step 108 — Requeue Missing Deliveries

If current fan-out architecture can identify intended recipient count vs created delivery rows, provide recovery path.

Do not over-engineer recipient snapshot system.

---

# Step 109 — Topic Audience Drift

If parent fan-out retries later, topic preferences may have changed.

Choose semantics.

Recommended:

Once delivery rows are generated, they define queued audience.

If fan-out failed before a chunk was created, retry may resolve current audience.

Document this limitation.

Do not build huge audience snapshots just for perfect historical consistency.

---

# Step 110 — Security Headers

Do not globally alter CSP/security headers unless push endpoints require it.

Firebase browser CSP concerns belong to earlier phase.

---

# Step 111 — Firebase Credential Permissions

Document that service-account credential file should be readable only by the application/server user where practical.

Do not expose via web root.

---

# Step 112 — Credential Path Check

Health/config validation should fail safely if credential file is:

```text
missing
unreadable
invalid
```

No PHP warning leakage.

---

# Step 113 — OAuth Token Cache

Review Phase 2.3C OAuth cache.

Ensure:

- short-lived;
- safety margin;
- no private key cached;
- cache-key namespaced;
- expired token refresh safe under concurrency.

---

# Step 114 — OAuth Refresh Stampede

If many workers simultaneously detect expired token, avoid unnecessary OAuth refresh storm where practical.

Possible short Laravel cache lock around refresh.

Do not make lock failure block forever.

---

# Step 115 — OAuth Cache Lock

Use Laravel Cache lock abstraction if supported by current cache driver.

Do not issue raw Redis locking commands.

---

# Step 116 — Lock Timeout

Keep OAuth refresh lock short.

Other workers may retry/read newly cached token.

---

# Step 117 — FCM HTTP Timeout

Audit timeout and connection timeout.

Ensure finite bounds.

---

# Step 118 — HTTP Retry Layer

Avoid duplicate retries at both:

```text
Laravel HTTP client
+
queue job
```

without understanding interaction.

Prefer one controlled small transport retry or queue-level retry.

Document the final strategy.

---

# Step 119 — Recommended Retry Layer

Prefer:

```text
HTTP client
→ minimal/no automatic retry

Queue job
→ classified controlled retry/backoff
```

unless current architecture already safely handles transport retry.

This makes delivery analytics accurate.

---

# Step 120 — Attempt Count Accuracy

One logical FCM HTTP request should correspond to one recorded attempt.

Do not hide multiple transport retries inside a single attempt unless documented.

---

# Step 121 — Rate-Limit Response Analytics

Ensure quota/rate-limit errors remain visible in Phase 2.3G analytics.

Do not swallow them.

---

# Step 122 — Admin Analytics

No new major analytics features required.

But existing failure breakdown should continue showing:

```text
quota
network
server
invalid_token
auth
```

---

# Step 123 — Configuration

Create/extend push hardening config.

Suggested conceptual configuration:

```php
'delivery' => [
    'enabled' => true,
    'queue' => 'push',
    'fanout_chunk_size' => 500,
    'tries' => 5,
    'timeout' => 30,
],

'cleanup' => [
    'inactive_subscription_days' => 90,
    'batch_size' => 500,
],

'rate_limits' => [
    // centralized values
],
```

Follow project conventions.

---

# Step 124 — Environment Variables

Only add env variables that truly need environment-level control.

Possible:

```dotenv
PUSH_SENDING_ENABLED=false
PUSH_FANOUT_CHUNK_SIZE=500
PUSH_INACTIVE_RETENTION_DAYS=90
```

Do not expose every constant through `.env` unnecessarily.

---

# Step 125 — Config Cache

Configuration must work with:

```bash
php artisan config:cache
```

Do not call `env()` outside config files.

---

# Step 126 — Queue After Deployment

Document:

```bash
php artisan queue:restart
```

after new push job code deployment.

---

# Step 127 — Supervisor Documentation

Inspect actual server architecture.

Document a production worker example, but do not assume exact process manager config if repository does not contain it.

Possible conceptual worker:

```text
php artisan queue:work --queue=push,default
```

Only use actual queue order after inspection.

---

# Step 128 — Worker Count

Do not prescribe extreme worker count blindly.

Daily Samvad server resources must be considered during production deployment.

Phase 2.3I can validate final worker count.

---

# Step 129 — Worker Memory

Document practical worker settings such as:

```text
--memory
--timeout
--tries
```

only based on existing architecture.

---

# Step 130 — Worker Recycling

Long-running workers should be restartable/recycled using Laravel conventions.

Do not create custom daemon.

---

# Step 131 — Queue Priority

If using:

```text
push,default
```

remember Laravel processes first queue with priority.

Document actual intended priority.

Do not accidentally starve normal jobs.

---

# Step 132 — Recommended Separation

For large push traffic, dedicated push worker is preferred.

---

# Step 133 — Tests: Rate Limit Registration

Repeated subscription calls beyond configured limit:

Expected:

```text
429
```

while normal requests succeed.

---

# Step 134 — Tests: Preference Rate Limit

Mutation limit enforced.

---

# Step 135 — Tests: Click Rate Limit

Ensure legitimate click route remains usable and abuse threshold behaves as designed.

---

# Step 136 — Tests: Manual Send Permission

Unauthorized direct action remains blocked.

---

# Step 137 — Tests: Manual Send Duplicate

Two concurrent/near-simultaneous send attempts:

Expected:

```text
one fan-out
```

---

# Step 138 — Tests: Auto Publish Duplicate

Same publication event:

```text
one automatic campaign
```

---

# Step 139 — Tests: Fan-Out Retry

Parent fan-out executes twice.

Expected:

```text
one delivery row per subscription
```

---

# Step 140 — Tests: Delivery Duplicate Job

Same delivery job handles twice after accepted.

Expected:

```text
no second FCM call
```

---

# Step 141 — Tests: Retryable Failure

Retryable result causes controlled retry.

---

# Step 142 — Tests: Permanent Failure

Permanent invalid token:

```text
no retry
```

---

# Step 143 — Tests: Backoff

Verify configured backoff contract.

Do not depend on sleeping in tests.

---

# Step 144 — Tests: Stuck Recovery

Create stale `attempting` delivery.

Run recovery service/command.

Expected safe requeue/state change.

---

# Step 145 — Tests: Fresh Attempting Delivery

Must NOT be recovered prematurely.

---

# Step 146 — Tests: Already Accepted

Recovery command must not touch it.

---

# Step 147 — Tests: Subscription Prune Dry Run

Expected:

```text
reports candidates
deletes none
```

---

# Step 148 — Tests: Subscription Prune

Old inactive subscription removed.

Active subscription retained.

---

# Step 149 — Tests: Analytics Preservation

Deleting stale subscription must not delete historical delivery analytics unexpectedly.

---

# Step 150 — Tests: Preference Cascade

Subscription deletion cleans preference pivot safely.

---

# Step 151 — Tests: Sending Disabled

With:

```text
PUSH_SENDING_ENABLED=false
```

expected:

```text
no FCM call
```

but site functionality remains normal.

---

# Step 152 — Tests: Auto Push Disabled

Maintain Phase 2.3D behavior.

---

# Step 153 — Tests: Manual vs Global Switch

If:

```text
global sending enabled
auto publish disabled
```

manual test/manual campaign should still work according to existing permissions.

---

# Step 154 — Tests: Queue Outage Behavior

Where practical mock dispatch failure.

Post publishing must still succeed.

Manual action should report failure safely.

---

# Step 155 — Tests: OAuth Refresh Lock

If concurrency lock implemented, test token provider can still obtain cached/new access token without duplicate unsafe behavior.

---

# Step 156 — Tests: No Secret Logging

Ensure newly introduced commands do not print:

```text
FCM token
OAuth token
private key
```

---

# Step 157 — Tests: Health Command

Validate healthy and misconfigured states.

---

# Step 158 — Tests Must Stay Offline

No real Google/Firebase requests.

---

# Step 159 — Performance Test Foundation

Create focused tests/benchmarks where practical for:

```text
audience chunking
delivery upsert
recipient count query
```

Do not implement massive synthetic production benchmark if project has no tooling.

---

# Step 160 — 100k-Subscriber Safety Review

Audit code for accidental:

```php
->get()
->all()
toArray()
```

on full active subscription audience.

Fix where necessary.

---

# Step 161 — Memory Safety

Queue fan-out must process bounded chunks.

---

# Step 162 — Query Safety

Use:

```text
chunkById
lazyById
cursor
```

where appropriate.

---

# Step 163 — N+1 Audit

Review push fan-out and analytics operations.

Avoid loading related topic/user data per subscription.

---

# Step 164 — DB Connection Pressure

Avoid one transaction/connection being held across network calls.

FCM HTTP call should not occur inside a long DB transaction.

---

# Step 165 — Delivery Claim Transaction

Keep state transitions small.

Commit before external HTTP where practical, while retaining idempotency.

---

# Step 166 — Network Call and Lock

Do not hold a database row lock during slow FCM HTTP request unless absolutely necessary.

Use atomic state claim.

---

# Step 167 — Queue Batching

Do not require Laravel Bus batches unless existing architecture benefits.

Do not add complexity solely for status display.

---

# Step 168 — Horizon Decision

If Laravel Horizon is not already installed:

Do NOT install it automatically in this phase unless there is a strong project-specific reason.

Normal Redis queue workers are sufficient.

Document Horizon as optional future tooling if relevant.

---

# Step 169 — Monitoring Readiness

Provide enough safe metrics/commands to later monitor:

```text
queued deliveries
attempting deliveries
retryable failures
permanent failures
stale attempts
active subscriptions
```

---

# Step 170 — No Prometheus Requirement

Do not introduce Prometheus/Grafana unless already part of project.

---

# Step 171 — Documentation

Create/update:

```text
docs/push-notifications/queue-security-rate-limiting.md
```

or project-equivalent.

---

# Step 172 — Documentation Contents

Document:

1. push queue architecture;
2. queue name;
3. worker strategy;
4. retries;
5. backoff;
6. timeouts;
7. FCM quota handling;
8. duplicate protection;
9. rate limits;
10. security controls;
11. global sending switch;
12. subscription cleanup;
13. stuck-delivery recovery;
14. health command;
15. worker deployment;
16. cache/proxy rules;
17. operational troubleshooting.

---

# Step 173 — Production Operations Section

Include safe commands such as actual implemented equivalents of:

```bash
php artisan push:health
php artisan push:recover-stuck --dry-run
php artisan push:prune-subscriptions --dry-run
php artisan queue:restart
```

Do not document commands that were not actually implemented.

---

# Step 174 — Failed Jobs Docs

Document Laravel project's actual failed-job inspection/retry mechanism.

Do not invent custom commands if standard Laravel commands suffice.

---

# Step 175 — Emergency Stop Procedure

Document:

```text
Set PUSH_SENDING_ENABLED=false
clear/cache config appropriately
restart workers if required
```

based on actual config implementation.

---

# Step 176 — Emergency Stop Must Not Delete Data

Stopping push sends must not remove:

```text
subscriptions
preferences
analytics
campaigns
```

---

# Step 177 — Resume Procedure

Document safe re-enable process.

Do not automatically replay ambiguous old campaigns.

---

# Step 178 — Pending Jobs on Disable

Define behavior.

Recommended:

Jobs should check global sending state at execution time.

If disabled:

```text
do not send
```

Choose whether they:

```text
release
fail
skip
```

carefully.

Recommended production-safe behavior:

Do not destroy them silently.

Use controlled retry/paused classification if architecture supports it.

Avoid infinite loop.

---

# Step 179 — Simpler Global Disable Policy

If queue pausing is too complex:

when sending disabled, new broadcasts are prevented and existing jobs no-op with a clearly documented safe state.

But ensure this does not falsely mark FCM Accepted.

Document exact semantics.

---

# Step 180 — Do Not Lie in Analytics

Global-disabled jobs must not become:

```text
accepted
```

or:

```text
sent successfully
```

---

# Step 181 — Security Audit

Review all push routes.

Expected public surface should be limited to necessary endpoints:

```text
subscription registration
subscription unsubscribe
preferences
click tracking
```

Manual sending remains admin-only.

---

# Step 182 — No Public Broadcast Endpoint

There must be no unauthenticated route that accepts:

```text
title
body
audience
```

and sends notifications.

Mandatory.

---

# Step 183 — Route Enumeration

Inspect:

```bash
php artisan route:list
```

and explicitly review push-related routes.

---

# Step 184 — HTTP Method Review

Use correct methods:

```text
POST register
DELETE unsubscribe
PUT/PATCH preferences
GET click
```

Avoid state-changing GET except click analytics counter.

---

# Step 185 — CORS

Do not open push API to arbitrary origins unless existing architecture explicitly requires cross-origin use.

Same-site Daily Samvad browser flow should use current origin.

---

# Step 186 — Security Middleware

Preserve:

```text
CSRF
auth where required
Filament auth
rate limiting
```

---

# Step 187 — Database Foreign Keys

Review cascade/null behavior.

Pruning subscriptions must not cascade delete campaigns/analytics unexpectedly.

---

# Step 188 — Database Constraints

Ensure:

```text
token_hash unique
subscription-topic pair unique
notification-subscription delivery unique
tracking public ID unique
```

or project-equivalent guarantees remain.

---

# Step 189 — No Table Locking Migration

Indexes/migrations must be as safe and additive as practical.

Do not rewrite large tables unnecessarily.

---

# Step 190 — No migrate:fresh

Never run:

```bash
php artisan migrate:fresh
```

against this project dataset.

---

# Step 191 — Migration Validation

Use:

```bash
php artisan migrate
php artisan migrate:status
```

only on appropriate development database.

---

# Step 192 — Config Validation

Run:

```bash
php artisan optimize:clear
```

and verify:

```bash
php artisan config:cache
```

where safe.

---

# Step 193 — Queue Validation

Use queue fakes/tests.

Where suitable verify job serialization with actual configured queue locally without sending FCM.

---

# Step 194 — Frontend Build

Phase 2.3H should normally require few frontend changes.

If JS/service worker is modified:

```bash
npm run build
```

must pass.

---

# Step 195 — JavaScript Tests

Run existing push JS tests if frontend code changes.

---

# Step 196 — Full Laravel Tests

Run targeted:

```bash
php artisan test tests/Feature/Push
```

Then:

```bash
php artisan test
```

where practical.

---

# Step 197 — Formatting

Run:

```bash
./vendor/bin/pint
```

or existing formatter.

---

# Step 198 — Static Analysis

Run existing static analysis only if already configured.

Do not introduce unrelated tools.

---

# Step 199 — Git Review

Before completion:

```bash
git status --short
git diff --stat
```

Inspect all changed/untracked files.

---

# Step 200 — Secret Review

Ensure no:

```text
.env
Firebase private key
service-account JSON
OAuth bearer token
raw production FCM token
database dump
```

is introduced.

---

# Step 201 — Private Key Scan

Where practical inspect changed files for accidental:

```text
BEGIN PRIVATE KEY
```

Actual credentials must never be committed.

---

# Step 202 — Queue Payload Review

Inspect failed-job/serialized job structure conceptually.

Confirm it contains no credentials and preferably no raw token.

---

# Step 203 — Definition of Done

Phase 2.3H is complete only when:

- push delivery uses a controlled queue;
- large audiences are chunked;
- no full subscriber audience is loaded into memory;
- retries are bounded;
- progressive backoff exists;
- permanent failures do not retry;
- transient failures can retry;
- quota errors are classified;
- delivery attempts remain accurately tracked;
- duplicate fan-out is prevented;
- duplicate delivery rows are prevented by DB constraints;
- accepted delivery jobs cannot send twice;
- stale attempting deliveries have a recovery path;
- public mutation endpoints are rate-limited;
- manual mass sending has backend safeguards;
- push sending has a global emergency disable;
- automatic Post push switch remains separate;
- invalid/stale subscriptions have a cleanup strategy;
- cleanup supports safe dry-run where appropriate;
- historical analytics survive subscription pruning;
- OAuth credential refresh remains secure;
- logging does not expose credentials/tokens;
- click redirect security remains intact;
- push routes have been audited;
- no public broadcast endpoint exists;
- queue failure does not block Post publishing;
- no sync mass-send fallback exists;
- production worker guidance exists;
- health/operational tooling exists where implemented;
- tests stay offline;
- migrations are safe;
- documentation exists;
- Phase 2.3I production deployment is ready to begin.

---

# Expected Architecture

Approximate final architecture:

```text
Post / Filament
      ↓
PushNotification
      ↓
Audience Resolver
      ↓
Fan-Out Orchestrator
      ↓
Chunked Delivery Creation
      ↓
PushNotificationDelivery
      ↓
Atomic Delivery Claim
      ↓
Push Queue
      ↓
Firebase HTTP v1
      ↓
Accepted / Retryable / Permanent Failure
```

Safety controls:

```text
Rate Limiter
Global Push Switch
Idempotency
DB Unique Constraints
Backoff
Timeouts
Cleanup
Recovery Commands
Health Checks
```

---

# Expected Files

Actual implementation should follow project conventions.

Potential additions or changes may include:

```text
app/
├── Console/
│   └── Commands/
│       ├── PushHealthCommand.php
│       ├── RecoverStuckPushDeliveriesCommand.php
│       └── PrunePushSubscriptionsCommand.php
│
├── Jobs/
│   └── Push/
│       ├── QueuePushNotificationFanout.php
│       └── existing delivery job
│
├── Services/
│   └── Push/
│       ├── PushDeliveryRecoveryService.php
│       └── PushSubscriptionCleanupService.php
│
└── Providers/
    └── existing limiter/provider location
```

Potential config:

```text
config/firebase.php
config/push.php
```

Only create a dedicated `config/push.php` if it improves clarity and does not duplicate existing config structure.

---

# Required Completion Report

At completion provide:

## 1. Phase Summary

Explain production hardening completed.

## 2. Existing Queue Audit

Report original queue architecture and changes made.

## 3. Queue Architecture

Explain:

```text
queue name
fan-out
chunking
worker behavior
```

## 4. Retry Strategy

Report:

```text
tries
backoff
timeout
retryable categories
permanent categories
```

## 5. FCM Quota Handling

Explain quota/rate-pressure behavior.

## 6. Duplicate Protection

Explain protection at:

```text
campaign level
fan-out level
delivery level
worker level
database level
```

## 7. Stuck Delivery Recovery

Provide actual command/service behavior.

## 8. Global Sending Switch

Explain exact semantics.

## 9. Auto-Publish Switch

Confirm it remains separate.

## 10. Rate Limits

List implemented limiter names and configured behavior.

Do not expose sensitive implementation details unnecessarily.

## 11. Route Security Audit

List push route types and middleware protections.

## 12. Manual Send Security

Explain confirmation, permission, rate limit and idempotency.

## 13. Subscription Cleanup

Explain stale/inactive criteria and pruning command.

## 14. Dry Run

Show exact supported commands/options.

## 15. Analytics Preservation

Confirm cleanup does not delete historical notification analytics.

## 16. OAuth Security

Explain access-token cache and refresh concurrency protection.

## 17. Logging Review

Confirm no raw token/private credentials logged.

## 18. Operational Commands

List all actual commands implemented.

## 19. Health Check

Explain checks performed if `push:health` exists.

## 20. Worker Deployment

Provide recommended worker command/config based on actual project.

## 21. Files Created

List all new files.

## 22. Files Modified

List every modified file and purpose.

## 23. Database Changes

Report migrations/indexes if added.

## 24. Tests

Report:

```text
tests added
tests run
passed
failed
```

## 25. Validation

Report:

```text
migrate
migrate:status
php artisan test
Pint
npm build if applicable
config cache
```

## 26. Performance Review

Explain:

```text
chunking
memory safety
N+1 audit
transaction boundaries
DB/index review
```

## 27. Security Review

Confirm:

- no public broadcast endpoint;
- CSRF remains enabled where required;
- admin authorization enforced;
- rate limits exist;
- unsafe redirects blocked;
- credentials remain private;
- no raw tokens exposed.

## 28. Cache / Proxy Review

Explain required bypasses for push mutation/click endpoints.

## 29. Known Limitations

List anything intentionally left for Phase 2.3I.

## 30. Scope Verification

Confirm these were NOT implemented:

```text
A/B testing
AI targeting
geographic targeting
behavioral profiling
conversion tracking
revenue attribution
new analytics features unrelated to hardening
```

## 31. Phase 2.3I Readiness

State exactly what production deployment/testing work remains.

## 32. Final Status

Finish exactly with one:

`PHASE 2.3H COMPLETE`

or:

`PHASE 2.3H BLOCKED`

---

# Final Instruction

Fully implement:

# Phase 2.3H — Queue, Security & Rate Limiting

Do not merely audit the repository.

Inspect and reuse the actual Phase 2.3A–2.3G implementation.

Do not build another push engine.

Do not redesign existing analytics.

Harden the existing architecture for safe production scale.

The final flow must remain:

```text
Post / Filament
      ↓
PushNotification
      ↓
Audience Resolver
      ↓
Safe Chunked Fan-Out
      ↓
Unique Delivery
      ↓
Controlled Queue
      ↓
Bounded Retry + Backoff
      ↓
Firebase HTTP v1
      ↓
Accepted / Retryable / Permanent Failure
```

with:

```text
Rate Limits
Security
Global Emergency Disable
Idempotency
Stuck-Job Recovery
Subscription Cleanup
Safe Logging
Operational Health Checks
```

Do not perform large synchronous FCM broadcasts.

Do not allow queue outage to block Post publishing.

Do not expose credentials or tokens.

Do not begin Phase 2.3I.

Run all relevant migrations, tests, formatting and build commands.

Fix regressions introduced by this phase.

Provide the full completion report.

End exactly with:

`PHASE 2.3H COMPLETE`

or:

`PHASE 2.3H BLOCKED`