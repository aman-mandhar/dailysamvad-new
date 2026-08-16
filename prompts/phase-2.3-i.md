# Phase 2.3I — Testing, Production Deployment & Final Audit

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
- Firebase Cloud Messaging HTTP v1
- Browser Service Worker
- Push Subscription Management
- Manual Push Notifications
- Automatic Post Publish Push
- Topic/Category Preferences
- Analytics & Click Tracking
- Queue/Security/Rate Limiting Hardening

Completed phases:

- Phase 2.3A — Firebase & Browser Push Foundation
- Phase 2.3B — Push Subscription Management
- Phase 2.3C — Laravel Push Engine
- Phase 2.3D — Post Publish Automation
- Phase 2.3E — Filament Push Notification Panel
- Phase 2.3F — Topics & Category Preferences
- Phase 2.3G — Analytics & Click Tracking
- Phase 2.3H — Queue, Security & Rate Limiting

This phase completes:

# Version 2.3 — Push Notification System

---

# Phase Objective

Perform the final integration, validation, deployment preparation, production configuration, controlled live verification, performance audit, security audit, and go-live readiness review for the entire push notification system.

This phase must answer:

```text
Does browser subscription work?
Does Laravel store subscriptions correctly?
Can Laravel securely authenticate to Firebase?
Can real FCM messages reach a browser?
Does the service worker behave correctly?
Does manual notification sending work?
Does automatic Post publish push work?
Do topic preferences work?
Does analytics track FCM acceptance and clicks correctly?
Does queue processing remain stable?
Are duplicate sends prevented?
Are stale tokens handled?
Are push routes secure?
Do Nginx/Varnish/Cloudflare caches behave correctly?
Can the system be safely enabled in production?
```

---

# Core Rule

Phase 2.3I is primarily:

# Verification + Deployment + Audit

Do not introduce unrelated new features.

If bugs are discovered, fix only what is necessary to make Version 2.3 production-ready.

---

# Strict Scope Boundary

Do NOT implement:

- A/B testing;
- AI notification targeting;
- behavioral targeting;
- geographic targeting;
- recurring marketing campaigns;
- revenue attribution;
- conversion tracking;
- native Android application;
- native iOS application;
- unrelated Filament redesign;
- unrelated SEO changes;
- unrelated cache redesign;
- unrelated infrastructure migration.

---

# Critical First Step — Audit Current Repository

Before running production or deployment work, inspect the actual completed Phase 2.3A–2.3H implementation.

Inspect:

```text
app/Models/Push*
app/Services/Push/
app/Jobs/Push/
app/Http/Controllers/*Push*
app/Http/Requests/Push/
app/Filament/
app/Console/
config/
routes/
database/migrations/
resources/js/push/
resources/views/components/frontend/
public/firebase-messaging-sw.js
docs/push-notifications/
tests/Feature/Push/
tests/JavaScript/
```

Also inspect:

```text
.env.example
.gitignore
composer.json
composer.lock
package.json
package-lock.json
vite.config.*
config/queue.php
config/cache.php
```

---

# Step 1 — Establish Final Architecture

Document the actual implemented architecture.

Expected conceptual flow:

```text
Visitor Browser
     ↓
Push Permission
     ↓
Firebase Messaging
     ↓
FCM Token
     ↓
Laravel PushSubscription
     ↓
Topic Preferences
     ↓
PushAudienceResolver
     ↓
PushNotification
     ↓
PushNotificationDelivery
     ↓
Push Queue
     ↓
Firebase HTTP v1
     ↓
FCM Accepted / Failed
     ↓
Browser Notification
     ↓
Tracking URL
     ↓
Click Analytics
```

Do not describe hypothetical architecture.

Document only what actually exists.

---

# Step 2 — Verify Phase Completion Boundaries

Review that each phase actually delivered its intended scope.

### 2.3A

Verify:

```text
Firebase browser config
service worker
permission UI
FCM token generation
```

### 2.3B

Verify:

```text
subscription persistence
token hashing
guest/auth lifecycle
unsubscribe
```

### 2.3C

Verify:

```text
server credentials
OAuth
HTTP v1
PushMessage
queue delivery engine
```

### 2.3D

Verify:

```text
real publish transition
automatic push
idempotency
import protection
```

### 2.3E

Verify:

```text
Filament manual push
draft
preview
send confirmation
permission protection
```

### 2.3F

Verify:

```text
topics
preferences
audience resolver
manual targeting
post targeting
```

### 2.3G

Verify:

```text
delivery records
FCM accepted/failure analytics
click tracking
CTR
Filament analytics
```

### 2.3H

Verify:

```text
rate limits
queue hardening
retries
backoff
duplicate protection
cleanup
health tooling
global switch
```

---

# Step 3 — Environment Variable Audit

Build one final list of all required push-related environment variables.

Examples may include:

```dotenv
FIREBASE_WEB_API_KEY=
FIREBASE_WEB_AUTH_DOMAIN=
FIREBASE_PROJECT_ID=
FIREBASE_STORAGE_BUCKET=
FIREBASE_MESSAGING_SENDER_ID=
FIREBASE_WEB_APP_ID=
FIREBASE_MEASUREMENT_ID=
FIREBASE_VAPID_KEY=

FIREBASE_SERVICE_ACCOUNT_PATH=

PUSH_SENDING_ENABLED=
PUSH_AUTO_PUBLISH_ENABLED=
PUSH_FANOUT_CHUNK_SIZE=
PUSH_INACTIVE_RETENTION_DAYS=
```

Use actual implemented names only.

Remove obsolete or duplicate configuration keys if found.

---

# Step 4 — Browser vs Server Credential Audit

Confirm strict separation.

Browser may receive only browser-safe Firebase configuration.

Server-only:

```text
service-account credentials
private key
OAuth token
```

must never be exposed to:

```text
Blade
JavaScript
Vite bundle
public directory
browser network response
```

---

# Step 5 — Secret Scan

Inspect repository carefully.

Search changed/tracked files for suspicious values/patterns:

```text
BEGIN PRIVATE KEY
private_key
service-account
oauth
Bearer
FCM token
```

Configuration key names are fine.

Actual secret values are not.

---

# Step 6 — Git Audit

Run:

```bash
git status --short
git diff --stat
git diff
```

Inspect all changed files.

Confirm no:

```text
.env
service-account JSON
Firebase private key
OAuth token
production FCM token
database dump
```

is tracked.

---

# Step 7 — Dependency Audit

Run:

```bash
composer validate
```

Review new Composer dependency if Phase 2.3C added one.

Also inspect:

```bash
npm audit
```

if project policy allows.

Do not automatically perform risky dependency upgrades unrelated to this phase.

---

# Step 8 — Laravel Test Suite

Run targeted tests first:

```bash
php artisan test tests/Feature/Push
```

All push tests must pass.

Then run:

```bash
php artisan test
```

where practical.

Differentiate:

```text
push regression
existing unrelated test failure
environment limitation
```

---

# Step 9 — JavaScript Tests

Run actual configured JS tests.

Inspect `package.json`.

Use the real command.

Possible example:

```bash
npm test
```

or:

```bash
npm run test
```

Do not invent a command if none exists.

---

# Step 10 — Production Frontend Build

Run:

```bash
npm run build
```

Must succeed.

Check Vite output for:

- Firebase import issues;
- service-worker references;
- missing assets;
- module errors.

---

# Step 11 — Code Formatting

Run:

```bash
./vendor/bin/pint
```

or project-standard formatter.

---

# Step 12 — Migration Audit

Run:

```bash
php artisan migrate:status
```

Ensure all Phase 2.3 migrations are present and ordered correctly.

Do NOT use:

```bash
php artisan migrate:fresh
```

---

# Step 13 — Migration Safety Review

Review every Phase 2.3 migration.

Confirm:

- additive;
- safe rollback;
- no destructive Post/User/Media data changes;
- no huge backfill causing production risk;
- indexes appropriate.

---

# Step 14 — Production Migration Plan

Document exact safe production command:

```bash
php artisan migrate --force
```

Do not execute against production unless production execution is explicitly part of the current working environment.

---

# Step 15 — Route Audit

Run:

```bash
php artisan route:list
```

Identify all push-related routes.

Create an explicit audit table in completion report with:

```text
route
method
auth
CSRF
rate limiter
cache behavior
purpose
```

---

# Step 16 — Public Route Review

Expected public surface should be limited.

Potential examples:

```text
subscription registration
unsubscribe
preferences
click tracking
```

There must be no public mass-send endpoint.

---

# Step 17 — Filament Authorization Audit

Verify actual permissions for:

```text
view push notifications
create draft
send notification
view analytics
topic configuration
```

if applicable.

Verify direct URL authorization, not just navigation visibility.

---

# Step 18 — Role Matrix

Document actual access for roles.

Example structure:

```text
super-admin
admin
editor
reviewer
reporter
seo-manager
analytics-manager
...
```

Use real project behavior.

Do not invent permissions.

---

# Step 19 — Push Sending Global Switch

Verify actual behavior with:

```text
PUSH_SENDING_ENABLED=false
```

Expected:

```text
No outbound FCM send
subscription registration works
preferences work
analytics UI works
website works
```

---

# Step 20 — Automatic Push Switch

Verify:

```text
PUSH_AUTO_PUBLISH_ENABLED=false
```

Expected:

```text
manual push may still work
push:test may work if permitted
automatic Post push disabled
```

---

# Step 21 — Safe Initial Production State

Recommended production rollout state:

```text
PUSH_SENDING_ENABLED=false
PUSH_AUTO_PUBLISH_ENABLED=false
```

until real browser/device test is completed.

Document exact rollout.

---

# Step 22 — Firebase Project Setup Checklist

Create final Firebase setup documentation using actual implemented configuration.

Include:

1. create/select Firebase project;
2. register Web App;
3. capture browser config;
4. configure Web Push VAPID key;
5. enable/verify Cloud Messaging API;
6. create service account credential if required;
7. install credential securely on server;
8. configure `.env`;
9. clear/config cache;
10. test OAuth authentication.

---

# Step 23 — Service Account Production Placement

Document an actual safe pattern for this server.

Credentials must:

- remain outside public web root;
- be readable by Laravel runtime user;
- not be committed to Git.

Do not output a real credential path unless known from actual server configuration.

Use a recommended placeholder path if production path is not yet established.

---

# Step 24 — File Permissions

Document safe server permission principle:

```text
application user → read
web public → no exposure
other users → restricted where practical
```

Do not use world-readable permissions casually.

---

# Step 25 — Firebase Configuration Health

Run actual implemented command if available:

```bash
php artisan push:health
```

Record output without secrets.

If `push:health` does not exist, use the actual implemented config/test mechanism.

---

# Step 26 — Real OAuth Test

With development/staging/production credentials configured, verify Laravel can obtain a valid OAuth token.

Do not print token.

Verify only successful authentication.

---

# Step 27 — Push Test Subscriber

Create one controlled browser subscriber.

Preferred:

- development/staging device;
- admin/developer browser;
- not broad audience.

Verify row exists in:

```text
push_subscriptions
```

---

# Step 28 — Subscription Database Verification

Inspect safely.

Possible:

```bash
php artisan tinker
```

Then use safe queries such as:

```php
App\Models\PushSubscription::count();
App\Models\PushSubscription::active()->count();
```

Do not dump raw token.

---

# Step 29 — Test Subscription Metadata

Verify:

```text
active
token hash
device UUID
last seen
permission
```

as applicable.

Do not expose raw token in completion report.

---

# Step 30 — Preference Verification

For controlled browser:

1. open preferences;
2. select topics;
3. save;
4. reload;
5. verify saved state;
6. ensure cached pages do not leak another visitor's preferences.

---

# Step 31 — Guest Preference Test

Verify without login.

---

# Step 32 — Authenticated Preference Test

Verify current device works after login.

---

# Step 33 — Token Rotation Test

If practical:

- delete/recreate token or reset site data;
- verify lifecycle;
- confirm preference preservation where architecture supports it.

Do not force destructive browser state if not practical.

---

# Step 34 — Unsupported Browser Test

Verify push UI fails gracefully.

No website JS failure.

---

# Step 35 — Permission Denied Test

Verify:

```text
Notification.permission = denied
```

does not produce repeated aggressive prompts.

---

# Step 36 — Permission Granted Test

Verify browser registration remains stable after reload.

---

# Step 37 — Service Worker URL Test

Production endpoint must resolve:

```text
/firebase-messaging-sw.js
```

Verify:

- HTTP 200;
- correct MIME;
- no redirect loop;
- correct service-worker scope.

---

# Step 38 — Service Worker Cache Headers

Audit response headers.

Service worker must not be cached indefinitely by:

- browser;
- Varnish;
- Cloudflare.

Use a reasonable update-friendly caching policy.

Do not set huge immutable cache.

---

# Step 39 — Service Worker Versioning

If current implementation uses a version strategy, verify it.

If not, do not introduce complexity unless stale worker behavior is discovered.

---

# Step 40 — Vite Asset Check

Production built push scripts must resolve without 404.

---

# Step 41 — Browser Console Audit

During controlled test inspect:

- service-worker errors;
- Firebase errors;
- permission errors;
- fetch/CSRF errors;
- mixed-content errors.

Fix Phase 2.3-related problems.

---

# Step 42 — Network Audit

Inspect requests for:

```text
push subscription
preferences
service worker
Firebase messaging
tracking redirect
```

Ensure no secrets appear in URLs.

---

# Step 43 — Manual Real Push Test

Use the safe implemented command.

Example only:

```bash
php artisan push:test --subscription=ID
```

Use actual syntax.

Expected:

```text
Laravel
→ OAuth
→ Firebase HTTP v1
→ FCM Accepted
→ browser notification
```

---

# Step 44 — Do Not Broadcast Test

Never use a production all-subscriber broadcast as the first live test.

---

# Step 45 — Background Notification Test

Verify controlled test when:

```text
tab backgrounded
```

---

# Step 46 — Closed Browser/Window Test

Where browser/platform supports it, verify behavior.

Document limitations accurately.

---

# Step 47 — Foreground Notification Test

Verify application behavior while site is open.

If foreground presentation differs from background, document it.

---

# Step 48 — Notification Content Test

Verify:

```text
title
body
image
icon
```

as supported by actual implementation/browser.

---

# Step 49 — Click URL Test

Click controlled notification.

Expected:

```text
tracking endpoint
→ click recorded
→ canonical destination opens
```

---

# Step 50 — Click Analytics Test

Verify:

```text
first_clicked_at
last_clicked_at
click_count
```

or actual equivalent.

---

# Step 51 — Repeat Click Test

Click same controlled notification again.

Verify:

```text
total clicks increments
unique clicks remains one
```

---

# Step 52 — CTR Test

Verify Filament analytics denominator uses actual implemented formula.

Recommended:

```text
Unique Clicks / FCM Accepted
```

---

# Step 53 — Terminology Audit

Search admin UI/docs for misleading:

```text
Delivered
```

Replace with:

```text
FCM Accepted
```

where actual delivery receipt is not known.

Do not misrepresent metrics.

---

# Step 54 — Manual Filament Notification Test

With sending still tightly controlled:

1. create draft;
2. preview;
3. verify active recipient count;
4. verify target;
5. send to controlled audience if current targeting allows;
6. verify queue;
7. verify analytics.

Do not send broad production campaign accidentally.

---

# Step 55 — Controlled Topic Test

Create/use two test subscriptions if practical.

Example:

```text
Device A → Sports
Device B → Punjab
```

Send selected-topic manual notification.

Verify only expected device receives it.

---

# Step 56 — Multi-Topic Deduplication Test

Controlled subscriber matching two selected topics must receive one notification.

---

# Step 57 — Breaking News Test

If breaking topic exists:

verify exact documented semantics.

---

# Step 58 — Auto-Publish Controlled Test

Only after manual push test succeeds.

Enable:

```text
PUSH_AUTO_PUBLISH_ENABLED=true
```

in controlled environment.

Publish one controlled test Post.

Verify:

```text
Post becomes published
→ one PushNotification
→ expected audience
→ one delivery per matching subscription
→ FCM accepted
→ browser receives
```

---

# Step 59 — Published Edit Test

Edit test Post title/body.

Verify no second automatic notification.

---

# Step 60 — Republish Test

If safe in controlled environment:

```text
published
→ draft
→ published
```

Verify documented idempotency behavior.

Recommended:

no second automatic push.

---

# Step 61 — Scheduled Post Test

If scheduling exists:

- schedule future Post;
- verify no early push;
- allow controlled actual publication;
- verify one push at actual publish time.

---

# Step 62 — Import Safety Audit

Inspect importer and import tests.

Verify historical/imported published posts cannot generate automatic push.

Do not perform large production import solely for this test.

---

# Step 63 — Zero Subscriber Test

Verify no exception when audience is empty.

---

# Step 64 — Inactive Subscriber Test

Verify inactive subscription is excluded.

---

# Step 65 — Invalid Token Test

Use automated/fake test rather than deliberately corrupting live token where practical.

Verify invalid-token lifecycle remains correct.

---

# Step 66 — Queue Worker Audit

Inspect actual production queue deployment.

Determine:

```text
queue connection
push queue name
worker command
Supervisor/systemd configuration
worker count
timeout
memory
tries
```

---

# Step 67 — Dedicated Push Worker

If Phase 2.3H uses dedicated queue:

verify production worker processes it.

Example conceptual command:

```bash
php artisan queue:work --queue=push --sleep=1 --tries=...
```

Use actual implemented configuration.

---

# Step 68 — Do Not Guess Worker Count

Assess actual server resources.

Do not blindly deploy 10 workers on small VPS.

Recommend conservative worker count based on current environment.

---

# Step 69 — Queue Restart

Production deployment should include:

```bash
php artisan queue:restart
```

after code/config deployment where applicable.

---

# Step 70 — Queue Health

Verify:

```text
jobs processed
no backlog
no repeated failures
```

using current project tools.

---

# Step 71 — Failed Jobs

Inspect:

```bash
php artisan queue:failed
```

or current Laravel equivalent.

Investigate push failures.

Do not delete failed jobs blindly.

---

# Step 72 — Retry Safety

Retry one controlled retryable failed job if available.

Ensure no duplicate accepted send.

---

# Step 73 — Duplicate Job Audit

Automated tests should prove accepted delivery no-ops on duplicate execution.

Verify code path again.

---

# Step 74 — Stuck Recovery Command

Run implemented dry run:

```bash
php artisan push:recover-stuck --dry-run
```

or actual syntax.

Verify output.

---

# Step 75 — Subscription Prune Command

Run:

```bash
php artisan push:prune-subscriptions --dry-run
```

or actual syntax.

Verify no active subscription is selected incorrectly.

---

# Step 76 — Health Command

Run:

```bash
php artisan push:health
```

if implemented.

Production-ready result should identify no critical blocking issue.

---

# Step 77 — Rate Limit Verification

Verify subscription endpoint limiter.

Do not stress production irresponsibly.

Automated tests may be primary evidence.

---

# Step 78 — Preference Rate Limit Verification

Same.

---

# Step 79 — Manual Send Rate Limit

Verify backend protection exists.

---

# Step 80 — Click Rate Limit

Verify it does not block normal repeated clicks.

---

# Step 81 — Nginx Audit

Inspect actual server config documentation/config if available.

Push-specific requirements:

```text
/firebase-messaging-sw.js
/push/click/*
/push/subscriptions
/push/preferences
```

Do not alter unrelated routing.

---

# Step 82 — Service Worker Nginx Rule

Ensure service-worker file is served as a static JS file where appropriate.

No accidental Laravel 404/HTML response.

---

# Step 83 — Varnish Audit

Daily Samvad may use Varnish.

Ensure:

```text
POST
PUT
DELETE
```

push routes are not cached.

Ensure:

```text
/push/click/*
```

bypasses full-page cache.

---

# Step 84 — Cloudflare Audit

Ensure push mutation/click routes are not served stale.

Do not globally disable Cloudflare.

---

# Step 85 — Cloudflare Service Worker Cache

Ensure `/firebase-messaging-sw.js` can update reliably.

Avoid immutable one-year browser/CDN cache.

---

# Step 86 — Cache Headers

Document recommended headers for:

```text
firebase-messaging-sw.js
push click route
push API mutations
```

Use actual server architecture.

---

# Step 87 — Full-Page Cache Personalization Audit

Topic preferences must remain device-specific.

Verify page cache does not embed one visitor's selected preferences.

---

# Step 88 — CSRF Production Verification

Verify browser subscription/preference writes work through production CSRF/session setup.

---

# Step 89 — HTTPS Verification

Browser push requires secure production context.

Verify canonical production site uses HTTPS.

---

# Step 90 — APP_URL Audit

Ensure canonical tracking/post URLs generate correct production domain.

---

# Step 91 — Mixed Content Audit

No notification image/icon URL should use HTTP on HTTPS site.

---

# Step 92 — Notification Image Public Access

Verify test image is publicly reachable without auth.

---

# Step 93 — Default Icon Access

Verify icon URL returns 200.

---

# Step 94 — Image 404 Fallback

Missing image must not stop basic notification.

---

# Step 95 — Database Performance Audit

Review push indexes.

Use actual schema.

Confirm indexes support:

```text
token hash lookup
active subscriptions
topic pivots
notification deliveries
tracking public IDs
status queries
stale recovery
```

---

# Step 96 — Audience Query Audit

Search for dangerous patterns:

```php
PushSubscription::active()->get()
Post::all()
```

inside large push operations.

Fix any remaining unbounded loads.

---

# Step 97 — Chunking Audit

Verify actual fan-out uses bounded chunks.

---

# Step 98 — Memory Audit

A large notification must not serialize giant subscriber arrays into a single queue job.

---

# Step 99 — Transaction Audit

Ensure FCM HTTP calls are not performed while long DB transactions/row locks are held.

---

# Step 100 — Queue Payload Audit

Inspect queued job class.

Confirm it does not serialize:

```text
raw FCM token
OAuth token
service credentials
huge subscriber arrays
```

where architecture intends safer IDs.

---

# Step 101 — Logging Audit

Search push code for:

```text
Log::
logger(
dump(
dd(
console.log
console.debug
```

Review each usage.

No production secret/raw-token logging.

---

# Step 102 — Browser Logging Audit

Production build should not print FCM token.

---

# Step 103 — Error Message Audit

Normal users should not see Firebase internals.

Filament admins may see safe operational classifications only.

---

# Step 104 — Security Route Audit

Verify no route such as:

```text
/public/send-push
/api/push/broadcast
```

exists without strong auth.

---

# Step 105 — Open Redirect Test

Verify:

```text
/push/click/{id}?redirect=https://evil.example
```

cannot override stored target.

---

# Step 106 — Tracking Enumeration Audit

Public tracking ID must remain opaque/high entropy.

---

# Step 107 — Mass Assignment Audit

Review protected lifecycle fields in:

```text
PushSubscription
PushTopic
PushNotification
PushNotificationDelivery
```

---

# Step 108 — Role Escalation Audit

Verify lower roles cannot manipulate push status/creator/recipient counts manually through Filament mass assignment.

---

# Step 109 — Permission Seeder Audit

Ensure permission seeding is idempotent.

Do not reset current roles/users.

---

# Step 110 — Queue Failure Test

Simulate queue dispatch failure in automated test.

Post publishing must still succeed.

---

# Step 111 — Firebase Outage Test

Automated fake:

```text
FCM 500
```

Verify retry behavior.

---

# Step 112 — Quota Test

Automated fake:

```text
quota/rate response
```

Verify safe retry classification.

---

# Step 113 — Auth Failure Test

Verify subscribers remain active.

---

# Step 114 — Invalid Token Test

Verify subscription deactivates only for known permanent token failure.

---

# Step 115 — Global Disable Test

Automated test must prove no FCM request occurs.

---

# Step 116 — Cache Clear / Config Cache

Verify production config lifecycle:

```bash
php artisan optimize:clear
php artisan config:cache
```

and project-standard optimization.

---

# Step 117 — Laravel Optimization

Determine current deployment pattern.

Likely:

```bash
php artisan optimize
```

where appropriate.

Do not change current deployment conventions unnecessarily.

---

# Step 118 — Production Deployment Script

Create/update deployment documentation with exact ordered commands.

Possible conceptual sequence:

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
php artisan queue:restart
```

Use actual project conventions and known server constraints.

Do not blindly use `npm ci` if project deployment uses another method.

---

# Step 119 — Ownership/Permission Awareness

Daily Samvad previously had build-directory permission issues.

Deployment documentation must warn:

Do not run asset build as a user that creates files owned by root or another account.

Ensure application deploy user retains ownership of writable/build paths.

Do not perform recursive destructive ownership commands without verifying server policy.

---

# Step 120 — Storage Permissions

Verify push feature does not require new publicly writable credential paths.

Firebase credential file should NOT be in public writable storage.

---

# Step 121 — Rollback Plan

Document code rollback.

Example conceptual:

```text
disable PUSH_SENDING_ENABLED
restart config/workers
rollback application release/git commit if required
```

---

# Step 122 — Migration Rollback Warning

Do not recommend rolling back analytics/subscription tables casually after live data begins accumulating.

Prefer forward-fix after production use.

---

# Step 123 — Emergency Disable

Operational emergency stop must be documented first.

Expected:

```text
PUSH_SENDING_ENABLED=false
```

plus required config-cache/worker refresh.

---

# Step 124 — Rollback Without Data Loss

Push can be disabled while keeping:

```text
subscriptions
preferences
notifications
analytics
```

intact.

---

# Step 125 — First Production Enable Sequence

Recommended:

```text
1. Deploy with sending OFF
2. Verify service worker
3. Verify subscription
4. Verify OAuth
5. Verify queue worker
6. Enable global sending
7. Send one targeted test
8. Verify click analytics
9. Keep auto-publish OFF
10. Test manual Filament push
11. Enable auto-publish
12. Publish one controlled article
13. Monitor
```

---

# Step 126 — No Broad Initial Broadcast

Mandatory.

---

# Step 127 — Production Subscriber Growth Test

After enablement:

verify new subscribers are appearing normally.

---

# Step 128 — Existing Subscriber Compatibility

Verify existing subscriptions created before topic preferences still behave according to documented legacy rule.

---

# Step 129 — Topic Sync Production

Run actual topic sync command if Phase 2.3F implemented one:

```bash
php artisan push:sync-topics
```

Review output before enabling targeted notifications.

---

# Step 130 — Topic Sync Idempotency

Run second time in development/staging or safe production dry context.

Expected no duplicates.

---

# Step 131 — Category Rename Safety

Review implementation/test.

No production destructive rename test required.

---

# Step 132 — Filament Manual Panel Audit

Verify:

```text
draft
preview
recipient count
target type
topics
confirmation
send
status
analytics
```

---

# Step 133 — Manual Send Record Immutability

Queued/sent records must not be editable into a different message.

---

# Step 134 — Manual Send Re-trigger

Verify send action unavailable/blocked after send.

---

# Step 135 — Analytics Audit

Verify no expensive N+1 on PushNotification list.

---

# Step 136 — Delivery Table Pagination

Verify no full delivery-table load.

---

# Step 137 — Analytics Historical Records

Old notifications with no delivery analytics should render safely.

---

# Step 138 — Analytics Retention

Review Phase 2.3H decision.

Do not delete delivery analytics unless explicit retention was implemented and approved.

---

# Step 139 — Database Growth Estimate

Create a rough operational estimate from architecture.

Example:

```text
20,000 subscribers × 20 pushes/day
= 400,000 delivery records/day
```

Use actual counts only if available.

Do not fabricate production subscriber counts.

Explain that analytics retention will need monitoring.

---

# Step 140 — Monitoring Recommendations

Without adding new infrastructure, document what operator should monitor:

```text
active subscriptions
queued deliveries
stuck deliveries
failed jobs
quota failures
invalid-token rate
FCM accepted ratio
CTR
DB growth
Redis memory
queue backlog
```

---

# Step 141 — Queue Worker Monitoring

Document commands appropriate to actual environment.

---

# Step 142 — Redis Memory Awareness

Large push fan-out can create temporary queue growth.

Do not create enormous bursts unnecessarily.

---

# Step 143 — Rate Limit Audit

List actual configured limits.

Verify they are reasonable.

Do not disclose sensitive infrastructure data unnecessarily.

---

# Step 144 — Abuse Test

Automated tests are sufficient.

Do not intentionally hammer live production endpoints.

---

# Step 145 — Subscriber Cleanup Dry Run

Production-ready documentation must instruct dry-run first.

---

# Step 146 — Stuck Recovery Dry Run

Same.

---

# Step 147 — Cron/Scheduler Audit

If push cleanup/recovery is scheduled, verify actual scheduler.

Run:

```bash
php artisan schedule:list
```

if appropriate.

---

# Step 148 — Scheduler Production Requirement

Document cron requirement only if current project scheduler needs it.

---

# Step 149 — Scheduled Post Requirement

If scheduled Post publishing depends on scheduler, confirm same production cron covers it.

---

# Step 150 — PWA / Existing Worker Compatibility

If an older service worker/PWA exists, verify no scope conflict.

Do not leave competing root service workers.

---

# Step 151 — Browser Cache Reset Guide

Document how operators can troubleshoot stale service worker:

- browser DevTools;
- unregister service worker;
- clear site data;
- reload.

Do not ask normal end users to do this as normal workflow.

---

# Step 152 — Browser Matrix

Perform/document validation for available browsers.

At minimum target:

```text
Chrome desktop
Edge desktop
Chrome Android
Safari where available
```

Document untested browsers honestly.

---

# Step 153 — Device Matrix

At minimum:

```text
desktop
Android phone
```

where available.

Do not claim iPhone/Safari verification if not actually tested.

---

# Step 154 — Browser Support Report

Use actual results:

```text
Passed
Failed
Not Tested
Unsupported
```

---

# Step 155 — Notification Permission UX Audit

Verify no automatic permission prompt on page load.

Mandatory.

---

# Step 156 — Rejection UX Audit

Verify denied users are not nagged aggressively.

---

# Step 157 — Accessibility Audit

Verify:

- notification button keyboard accessible;
- preferences checkboxes labeled;
- processing states clear;
- focus states usable.

---

# Step 158 — Mobile Layout Audit

Verify opt-in/preferences do not overflow.

---

# Step 159 — CLS/Page Performance Audit

Push UI must not create major layout shift.

---

# Step 160 — Performance Comparison

Compare pages with push JS enabled vs architecture baseline where practical.

Do not create a full synthetic benchmarking system.

---

# Step 161 — Lazy Initialization Review

Firebase should not unnecessarily block initial rendering.

---

# Step 162 — Bundle Audit

Inspect bundle size impact from Firebase.

Do not optimize prematurely if acceptable, but report notable impact.

---

# Step 163 — Tree-Shaking Audit

Ensure modular Firebase imports remain.

No legacy full SDK.

---

# Step 164 — Browser Config Missing Test

Verify deployment without Firebase config does not crash website.

---

# Step 165 — Server Config Missing Test

Verify push sending fails safely while site stays functional.

---

# Step 166 — Credential Unreadable Test

Use automated/config test.

Do not break real production credentials.

---

# Step 167 — Redis Missing Test

Use test/mocked environment where practical.

Verify no sync mass-send fallback.

---

# Step 168 — Queue Disabled Test

Verify Post publishing remains successful.

---

# Step 169 — DB Error Boundaries

Do not mask serious DB failures generally.

Push-specific errors should remain isolated where intended.

---

# Step 170 — Production Logs

After controlled test, inspect application logs for push errors.

No secret leakage.

---

# Step 171 — Browser Network Privacy Audit

Confirm raw FCM token may legitimately be sent to the application's subscription registration endpoint, but it must not appear in:

```text
URL
query string
analytics URL
console logs
public HTML
```

---

# Step 172 — Service Account Exposure Test

Search built assets for:

```text
private_key
client_email
service-account
```

No private credential material should be present.

---

# Step 173 — Build Artifact Audit

Inspect:

```text
public/build/
```

for accidental private values where practical.

Browser-safe Firebase Web configuration is expected.

Server credentials are not.

---

# Step 174 — .env.example Audit

Ensure all required keys documented.

No real values.

---

# Step 175 — Documentation Audit

Review:

```text
docs/push-notifications/
```

Ensure each phase documentation agrees with final actual architecture.

Remove/update obsolete instructions.

---

# Step 176 — Single Production Runbook

Create:

```text
docs/push-notifications/production-runbook.md
```

or project-consistent equivalent.

This should become the main operator document.

---

# Step 177 — Production Runbook Sections

Include:

1. architecture summary;
2. environment variables;
3. Firebase Console setup;
4. service account placement;
5. deployment commands;
6. migrations;
7. queue worker;
8. global switches;
9. service-worker verification;
10. push health check;
11. one-device test;
12. manual test;
13. auto-publish test;
14. topic test;
15. analytics test;
16. cache/CDN rules;
17. cleanup/recovery commands;
18. troubleshooting;
19. emergency disable;
20. rollback;
21. monitoring checklist.

---

# Step 178 — No Secrets in Runbook

Mandatory.

---

# Step 179 — Final Test Checklist

Create a reusable checklist in documentation.

Include:

```text
Browser subscription
DB persistence
FCM test
Background push
Click redirect
Analytics
Manual Filament
Topic targeting
Auto publish
Duplicate protection
Queue retry
Rate limits
Global switch
Cleanup dry run
Health check
```

---

# Step 180 — Production Validation Commands

Document actual safe commands.

Likely examples:

```bash
php artisan migrate:status
php artisan push:health
php artisan queue:failed
php artisan schedule:list
php artisan route:list
```

Only include commands that exist/apply.

---

# Step 181 — Deployment Validation

After deploy run:

```bash
php artisan optimize:clear
php artisan optimize
php artisan queue:restart
```

according to existing deployment architecture.

---

# Step 182 — Cache Purge

Document whether Varnish/Cloudflare cache purge is required for:

```text
service worker
frontend push JS
```

Do not purge entire production cache unless necessary.

---

# Step 183 — Service Worker Immediate Update

If deployment changes worker code, document how update is picked up.

---

# Step 184 — No-cache vs Short Cache

Use a sensible service-worker caching policy.

Do not necessarily force zero-cache if current versioning makes short cache sufficient.

---

# Step 185 — Production URL Verification

Verify:

```text
https://dailysamvad.com/firebase-messaging-sw.js
```

or actual production host/path.

Do not hardcode domain in core code.

---

# Step 186 — Click Route Production Test

Verify HTTPS tracking URL.

---

# Step 187 — Article Redirect Test

Notification opens canonical article.

---

# Step 188 — Legacy Redirect Compatibility

If article slug/path changes, existing redirect system should handle it.

Do not redesign redirects.

---

# Step 189 — SEO Isolation

Push click tracking route should not be indexed as content.

Add appropriate behavior if needed.

Do not pollute sitemap.

---

# Step 190 — Robots/Sitemap Audit

Ensure `/push/click/*` is not in sitemap.

No need for large robots redesign.

---

# Step 191 — Search Engine Behavior

Tracking redirect route should not produce indexable content.

---

# Step 192 — Analytics Security

Analytics pages remain admin-only.

---

# Step 193 — Notification Delete Policy

Sent/tracked notification records should not be casually deletable if analytics depends on them.

Audit current policy.

---

# Step 194 — Delivery Relationship Integrity

Verify deleting subscriber does not erase historical deliveries unexpectedly.

---

# Step 195 — Prune Test

Run automated tests verifying analytics preserved.

---

# Step 196 — Production Prune Default

Do not enable destructive cleanup immediately without dry-run review.

---

# Step 197 — Final Security Audit

Explicitly review:

```text
credentials
routes
CSRF
rate limiting
authorization
open redirects
mass assignment
queue payloads
logs
tracking IDs
cache bypass
```

---

# Step 198 — Final Performance Audit

Explicitly review:

```text
chunking
queries
indexes
N+1
queue payload size
worker behavior
Redis pressure
delivery table growth
```

---

# Step 199 — Final Reliability Audit

Explicitly review:

```text
idempotency
retry
backoff
stuck recovery
global disable
invalid tokens
queue failure isolation
```

---

# Step 200 — Final UX Audit

Explicitly review:

```text
permission CTA
preferences
Filament send confirmation
notification preview
safe status labels
browser error states
```

---

# Step 201 — Final Documentation Audit

All push docs should align.

No outdated commands.

---

# Step 202 — No Production Broadcast Requirement

Phase can be considered technically complete without broadcasting to real public subscribers if a controlled test device proves the full path.

Do not risk real users for certification.

---

# Step 203 — Real Test Evidence

Record whether these were actually tested:

```text
OAuth
FCM HTTP v1
browser receipt
background receipt
click redirect
analytics update
manual push
auto push
topic targeting
```

Use:

```text
PASS
FAIL
NOT TESTED
```

Do not fabricate PASS.

---

# Step 204 — Blocker Policy

Do not mark Phase 2.3I blocked merely because a browser/device is unavailable.

Mark:

```text
NOT TESTED
```

and clearly state remaining production validation.

But if core system cannot build, migrate, queue, authenticate, or send with configured credentials, that may be a blocker.

---

# Step 205 — Final Database Counts

Where useful report safe counts:

```text
PushSubscription total
active subscriptions
PushTopic count
PushNotification count
PushNotificationDelivery count
```

Do not print raw subscriber/token data.

---

# Step 206 — Final Health Status

Categorize:

```text
CODE READY
CONFIG READY
QUEUE READY
FIREBASE READY
BROWSER VERIFIED
PRODUCTION ENABLED
```

Use actual state.

---

# Step 207 — Production Enable Decision

Do not enable broad automatic push blindly.

If controlled tests pass, completion report should recommend the exact next switch sequence.

---

# Step 208 — Commit Recommendation

After full validation, recommend Git commit.

Do not auto-commit unless explicitly instructed by the user/workflow.

---

# Step 209 — Commit Scope

Suggested commit scope:

```text
feat: complete push notification system v2.3
```

Only suggest if all Phase 2.3 changes are intentionally part of one commit.

If project prefers phase-by-phase commits, respect existing history.

---

# Step 210 — Tag Recommendation

Optionally recommend a Git tag after production verification:

```text
v2.3
```

Do not create tag unless explicitly requested.

---

# Required Validation Commands

Run the applicable commands:

```bash
composer validate
```

```bash
php artisan migrate:status
```

```bash
php artisan route:list
```

```bash
php artisan schedule:list
```

if applicable.

```bash
php artisan test tests/Feature/Push
```

```bash
php artisan test
```

```bash
./vendor/bin/pint
```

```bash
npm run build
```

Run JavaScript tests if configured.

Run actual push operational commands if they exist:

```bash
php artisan push:health
php artisan push:recover-stuck --dry-run
php artisan push:prune-subscriptions --dry-run
```

Use actual syntax.

---

# Definition of Done

Phase 2.3I is complete only when:

- Phase 2.3A–2.3H code has been audited;
- push-specific migrations are valid;
- targeted push tests pass;
- full Laravel tests have been run where practical;
- frontend build passes;
- formatting passes;
- push routes are audited;
- authorization is audited;
- rate limits are audited;
- secret exposure audit passes;
- browser/server Firebase credentials are clearly separated;
- service-account deployment is documented;
- push queue production worker requirement is documented;
- service worker is verified or clearly marked not tested;
- subscription flow is verified or clearly marked not tested;
- real FCM test status is recorded honestly;
- click tracking status is recorded;
- analytics status is recorded;
- manual Filament push status is recorded;
- topic-targeting status is recorded;
- auto-publish status is recorded;
- duplicate prevention is verified;
- queue retry/backoff is verified;
- global disable switch is verified;
- cleanup/recovery commands are verified if implemented;
- cache/CDN bypass requirements are documented;
- production runbook exists;
- emergency disable procedure exists;
- rollback procedure exists;
- no broad accidental production broadcast was performed;
- no real credentials were committed;
- no raw FCM tokens were exposed;
- remaining untested items are explicitly listed;
- the system has a clear production enable sequence.

---

# Required Completion Report

At completion provide the following sections.

## 1. Final Phase Summary

Explain what was validated/fixed.

## 2. Version 2.3 Architecture

Document the final real architecture.

## 3. Phase Audit

Report status of:

```text
2.3A
2.3B
2.3C
2.3D
2.3E
2.3F
2.3G
2.3H
```

## 4. Files Created

List files created in Phase 2.3I.

## 5. Files Modified

List files modified and why.

## 6. Environment Variables

Provide final required variable names only.

## 7. Secret Audit

Confirm no credentials/tokens committed.

## 8. Database Audit

Report:

```text
migrations
indexes
constraints
migration status
```

## 9. Test Results

Report:

```text
Push tests
Full Laravel tests
JavaScript tests
```

with exact pass/fail counts where available.

## 10. Build Results

Report:

```text
npm build
Composer validation
Pint
```

## 11. Route Security

Report all push-related routes and protections.

## 12. Permission Audit

Report actual role/permission behavior.

## 13. Queue Audit

Report:

```text
connection
queue name
fan-out
chunk size
tries
backoff
timeout
worker requirement
```

## 14. Operational Commands

List actual implemented push commands.

## 15. Firebase Setup Status

Report:

```text
Browser Firebase config
VAPID
Service account
OAuth
HTTP v1
```

using:

```text
READY
CONFIGURED
NOT CONFIGURED
NOT TESTED
FAILED
```

as appropriate.

## 16. Browser Test Matrix

For each tested browser/device report:

```text
Permission
Subscription
Background Push
Foreground Push
Click
```

## 17. Manual Push Test

Report actual status.

## 18. Automatic Post Push Test

Report actual status.

## 19. Topic Preference Test

Report actual status.

## 20. Analytics Test

Report:

```text
FCM Accepted
Failure
Unique Click
Total Click
CTR
```

## 21. Service Worker Audit

Report:

```text
URL
scope
cache headers
update behavior
```

## 22. Cache/CDN Audit

Report requirements for:

```text
Nginx
Varnish
Cloudflare
full-page cache
```

## 23. Security Audit

Report:

```text
CSRF
rate limits
open redirect
tracking IDs
mass assignment
logs
credentials
public broadcast routes
```

## 24. Performance Audit

Report:

```text
chunking
memory
indexes
N+1
queue payload
DB growth
```

## 25. Reliability Audit

Report:

```text
duplicate protection
retry
backoff
stuck recovery
global disable
invalid tokens
```

## 26. Cleanup Audit

Report:

```text
subscription pruning
dry-run
analytics preservation
```

## 27. Production Runbook

Provide path to the final runbook.

## 28. Production Deployment Commands

Provide final ordered command sequence appropriate to this project.

Do not include secrets.

## 29. Production Enable Sequence

Document exact safe switches/testing order.

## 30. Emergency Disable

Provide exact safe procedure.

## 31. Rollback

Provide safe code/config rollback procedure.

## 32. Remaining Risks

List real remaining risks only.

## 33. Untested Items

Clearly list anything not actually tested.

## 34. Final Readiness

Return one of:

```text
READY FOR CONTROLLED PRODUCTION ENABLEMENT
```

```text
READY WITH MANUAL PRODUCTION VALIDATION REQUIRED
```

```text
NOT READY FOR PRODUCTION
```

## 35. Version Status

Finish exactly with one:

```text
PHASE 2.3I COMPLETE
```

or:

```text
PHASE 2.3I BLOCKED
```

---

# Final Instruction

Fully execute:

# Phase 2.3I — Testing, Production Deployment & Final Audit

Do not merely review the prompt.

Audit the actual completed Phase 2.3A–2.3H repository implementation.

Fix bugs that prevent production readiness, but do not add unrelated features.

Run all safe automated tests, build steps, formatting and audits.

Do not run destructive database commands.

Do not expose or commit Firebase credentials.

Do not perform an uncontrolled broadcast to real subscribers.

Use one or a small number of controlled test subscriptions for real FCM verification.

Accurately distinguish:

```text
FCM Accepted
```

from:

```text
Verified Browser Display
```

Do not fabricate test results.

If production server access/configuration is not available, fully complete code-level validation and produce exact remaining production steps.

The final production architecture must be:

```text
Browser
   ↓
Firebase Web Messaging
   ↓
PushSubscription
   ↓
Topic Preferences
   ↓
PushAudienceResolver
   ↓
PushNotification
   ↓
PushNotificationDelivery
   ↓
Safe Push Queue
   ↓
Firebase HTTP v1
   ↓
FCM Accepted / Failed
   ↓
Browser
   ↓
Opaque Tracking URL
   ↓
Click Analytics
   ↓
Filament
```

with:

```text
Security
Rate Limits
Idempotency
Retry / Backoff
Global Disable
Cleanup
Recovery
Monitoring Readiness
Production Runbook
```

End the entire Version 2.3 implementation with a truthful readiness assessment.

Do not begin Version 2.4 or any unrelated phase.

Finish exactly with:

`PHASE 2.3I COMPLETE`

or:

`PHASE 2.3I BLOCKED`