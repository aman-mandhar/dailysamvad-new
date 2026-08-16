# Phase 2.3D — Post Publish Automation

## Project

Daily Samvad — WordPress to Laravel Migration / NewsMan CMS Foundation

Current stack includes:

- Laravel 12
- PHP 8.3+
- Blade frontend
- Livewire where already applicable
- Filament admin
- Vite
- Redis
- Laravel queues
- Existing authentication and role system
- Existing Reporter → Reviewer/Editor → Publish workflow
- Existing media/image system
- Existing caching architecture
- Phase 2.3A — Firebase & Browser Push Foundation
- Phase 2.3B — Push Subscription Management
- Phase 2.3C — Laravel Push Notification Engine

This phase continues:

# Version 2.3 — Push Notification System

---

# Phase Objective

Connect the existing post publishing workflow to the reusable push notification engine created in Phase 2.3C.

This phase must ensure:

```text
Draft
  ↓
Review
  ↓
Editor Approval
  ↓
Published
  ↓
Post Published Event
  ↓
Push Message Factory
  ↓
Push Notification Dispatch
  ↓
Queue
  ↓
Active Subscribers
```

The most important requirement is:

# Push notifications must be sent only when a post actually becomes published.

Saving or editing an already published post must not automatically create duplicate notifications.

---

# Primary Goals

Implement:

1. reliable post publish transition detection;
2. a dedicated post-published domain event where appropriate;
3. push automation listener/service integration;
4. conversion of Post data into generic `PushMessage`;
5. notification title/body/image/URL generation;
6. queued dispatch to active subscribers;
7. duplicate-notification prevention;
8. scheduled/future post compatibility;
9. publish workflow compatibility;
10. breaking-news preparation without implementing full breaking-news campaign logic;
11. configurable automatic push behavior;
12. safe failure handling;
13. cache/workflow regression protection;
14. automated tests;
15. documentation.

---

# Strict Scope Boundary

This phase is only:

# Post Publish → Push Engine Automation

Do NOT implement:

- Filament push campaign panel;
- manual campaign composer;
- category/topic preferences;
- topic-targeted sending;
- push analytics dashboard;
- click tracking;
- delivery reporting;
- CTR;
- advanced production rate limiting;
- campaign scheduling UI.

Future phases:

- 2.3E — Filament Notification Panel
- 2.3F — Topics & Category Preferences
- 2.3G — Analytics & Click Tracking
- 2.3H — Queue, Security & Rate Limiting
- 2.3I — Testing & Production Deployment

---

# Critical First Step — Audit Current Publishing Architecture

Before modifying code, inspect the actual implementation of:

```text
app/Models/Post.php
app/Observers/
app/Events/
app/Listeners/
app/Services/
app/Actions/
app/Filament/
app/Policies/
routes/
tests/
```

Search for:

```text
published
published_at
status
publish
pending
review
approved
scheduled
future
PostObserver
saving
saved
updated
created
```

Also inspect the Reporter → Reviewer/Editor → Publish workflow from earlier phases.

Do not introduce a second competing publish workflow.

---

# Audit Phase 2.3C

Inspect:

- `PushMessage`
- `PushNotificationService`
- queue job(s)
- multi-subscription dispatch API
- delivery result architecture
- active subscription scope
- Firebase transport
- test command

Phase 2.3D must call the existing engine.

Do not duplicate Firebase transport code.

---

# Protected Existing Functionality

Do not break:

- reporter workflow;
- reviewer workflow;
- editor workflow;
- existing post status transitions;
- scheduled posts;
- SEO;
- homepage updates;
- cache invalidation;
- categories;
- tags;
- search;
- author pages;
- media;
- advertisements;
- analytics;
- imported WordPress posts;
- Filament PostResource;
- role permissions;
- queues;
- Redis.

---

# Core Architecture

Target architecture:

```text
Post
  ↓
Publish transition detected
  ↓
PostPublished event
  ↓
SendPostPublishedPush listener
  ↓
PostPushMessageFactory
  ↓
PushMessage
  ↓
PushNotificationService
  ↓
Queue
  ↓
Active subscriptions
```

The exact names may differ based on existing project conventions.

---

# Step 1 — Define “Published”

Do not assume blindly that:

```text
status = published
```

is the only publishing condition.

Inspect the actual Post model and workflow.

Determine the authoritative publish state.

Possible factors:

```text
status
published_at
approval status
visibility
scheduled/future state
```

Document the final rule.

---

# Step 2 — Transition Detection

Push notification must be triggered only on transition:

```text
not published
→
published
```

Examples that SHOULD send:

```text
draft → published
pending → published
review → published
scheduled → published
```

depending on actual application workflow.

---

# Step 3 — No Duplicate on Edit

The following must NOT send another automatic push:

```text
published
→ edit title
→ save
```

or:

```text
published
→ edit body
→ save
```

or:

```text
published
→ change SEO
→ save
```

---

# Step 4 — Existing Published Imported Posts

Imported historical posts already marked published must not suddenly trigger notifications during:

- migration;
- backfill;
- import;
- normal deployment;
- cache rebuilding;
- model hydration.

Push automation should only act on actual application publish transitions.

---

# Step 5 — Observer vs Service vs Event

Inspect existing architecture before choosing.

Preferred design:

```text
publishing business logic
→ PostPublished event
→ push listener
```

Avoid putting complex push orchestration directly in:

```php
Post::saved(...)
```

or a large observer callback.

---

# Step 6 — Reuse Existing Domain Event

If project already has a domain event equivalent to:

```text
PostPublished
```

reuse it.

Do not create a duplicate event with overlapping meaning.

---

# Step 7 — Create PostPublished Event if Needed

If no suitable event exists, create one.

Suggested responsibilities:

- carry post identity;
- represent actual publication;
- remain generic enough for future listeners.

Do not include Firebase logic inside the event.

---

# Step 8 — Event Dispatch Timing

Dispatch the publish event only after a successful persistence transaction.

If publish occurs inside a database transaction, ensure push dispatch cannot happen before commit.

Use Laravel's after-commit facilities where appropriate.

This is important.

A rolled-back publish must never send a push.

---

# Step 9 — Queue After Commit

If queue jobs are dispatched from the listener, ensure they are safe with DB transaction timing.

Prefer:

```text
publish DB commit
→ event/listener
→ queue job
```

not:

```text
queue job
→ DB rollback
```

---

# Step 10 — PostPushMessageFactory

Create a dedicated converter/service such as:

```text
app/Services/Push/PostPushMessageFactory.php
```

or project-consistent equivalent.

Responsibilities:

```text
Post
→ title
→ body
→ image
→ URL
→ PushMessage
```

Do not add Post-specific logic into `FirebaseMessagingClient`.

---

# Step 11 — Push Title

Default notification title should come from the Post title.

Apply safe trimming if required.

Do not mutate the stored post title.

---

# Step 12 — Push Body

Generate a short notification body.

Possible sources, in preferred order if available:

```text
excerpt
meta description
SEO description
plain-text content excerpt
```

Inspect actual Post model fields first.

Do not assume `excerpt` exists.

---

# Step 13 — Body Cleanup

Push body must not contain:

- HTML tags;
- Blade markup;
- script/style text;
- excessive whitespace;
- raw entities where avoidable.

Use safe plain-text generation.

---

# Step 14 — Body Length

Keep the notification body concise.

Do not send full article text.

Use a sensible character limit configurable where appropriate.

Avoid brittle browser-specific exact limits.

---

# Step 15 — Post URL

Generate the canonical frontend URL using existing route architecture.

Do not manually concatenate:

```text
https://dailysamvad.com/...
```

if Laravel route helpers already provide the correct URL.

Use canonical current routes.

---

# Step 16 — Absolute URL

FCM click URL should be absolute.

Verify production route generation respects:

```text
APP_URL
```

or equivalent application URL configuration.

---

# Step 17 — Featured Image

If Post has a featured image/media relation, use the existing media/image system.

Do not bypass Media models or image URL helpers.

---

# Step 18 — Push Image Variant

Prefer an existing public image variant suitable for notification use.

Do not create another image-processing pipeline in this phase.

If no specific push variant exists, use the safest existing optimized image URL.

---

# Step 19 — Missing Image

If post has no image:

- send notification without image; or
- use configured default push image if Phase 2.3C already supports it.

Do not fail the entire notification.

---

# Step 20 — Notification Icon

Use the generic application/default icon configuration already supported by the push engine.

Do not hardcode individual post images as icons unless existing design requires it.

---

# Step 21 — Generic Push Data

Include useful generic metadata where supported:

```text
type = post
entity_id = post ID
url = canonical URL
```

Do not implement analytics campaign IDs yet.

---

# Step 22 — Configuration

Add push automation configuration.

Example conceptual structure:

```php
'auto_publish' => [
    'enabled' => env('PUSH_AUTO_PUBLISH_ENABLED', true),
],
```

Use existing config conventions.

---

# Step 23 — Environment Variable

Add to `.env.example` if used:

```dotenv
PUSH_AUTO_PUBLISH_ENABLED=true
```

Do not put environment calls directly throughout services.

Use config.

---

# Step 24 — Master Disable Switch

There must be a clean way to disable automatic post push without disabling the entire push engine.

Expected:

```text
Push subscriptions still work
Push test command still works
Manual future notifications can work
Auto-publish push disabled
```

---

# Step 25 — Environment Safety

Consider local/testing environments.

Do not accidentally send real notifications during automated tests.

Tests must fake/mock dispatch.

---

# Step 26 — Local Development

If Firebase credentials happen to exist locally, publishing test posts should not unexpectedly notify production users.

Use existing environment/config strategy.

Do not introduce dangerous defaults.

---

# Step 27 — Listener

Create a listener such as:

```text
SendPostPublishedPushNotification
```

or equivalent.

Responsibilities:

```text
check auto-push config
build PushMessage
dispatch through PushNotificationService
```

Keep it thin.

---

# Step 28 — Listener Should Queue?

Choose architecture based on Phase 2.3C.

If `PushNotificationService` already dispatches queue jobs for recipients, avoid double-queueing unnecessary layers.

Possible:

```text
event listener
→ orchestrator
→ queue fan-out
```

is acceptable.

Avoid:

```text
queued listener
→ dispatches queue job
→ dispatches another queue job
```

without purpose.

---

# Step 29 — Active Subscribers Only

Automatic publish push must target:

```text
PushSubscription::active()
```

or equivalent existing Phase 2.3B/2.3C query.

Do not send to inactive subscriptions.

---

# Step 30 — No User Login Requirement

Automatic push must work for:

```text
guest subscriptions
authenticated subscriptions
```

Both are legitimate recipients.

---

# Step 31 — No Category Filtering Yet

For Phase 2.3D, default automatic post notification target is:

```text
all active subscriptions
```

unless an existing safe setting already defines otherwise.

Do not implement category preferences yet.

That belongs to 2.3F.

---

# Step 32 — Breaking News Flag

Inspect whether Post already has a field/flag equivalent to:

```text
breaking
is_breaking
breaking_news
```

Do not create a new breaking-news architecture unless required.

---

# Step 33 — Breaking News Scope

If a breaking flag exists, the message factory may expose:

```text
type = breaking_news
```

or adjust presentation minimally.

However:

Do NOT implement:

- breaking-only subscribers;
- special FCM topics;
- repeated breaking campaigns;
- priority scheduling.

Those belong later.

---

# Step 34 — Duplicate Notification Protection

This is critical.

A post should receive at most one automatic publish notification for the same publication event.

Do not rely solely on “model was changed” checks if multiple workflow entry points can fire.

---

# Step 35 — Determine Existing Idempotency Mechanism

Inspect whether project has:

- audit logs;
- workflow transition records;
- post publication timestamps;
- event IDs.

Reuse existing reliable signal where possible.

---

# Step 36 — Automatic Push Marker

If necessary, add a lightweight marker to Post or a dedicated safe mechanism.

Possible field:

```text
push_notified_at
```

But do NOT add it automatically without first evaluating existing architecture.

A migration is acceptable only if it materially improves idempotency.

---

# Step 37 — Alternative Idempotency

Possible alternatives:

```text
published_at transition
cache lock
unique job key
event idempotency
```

Choose the simplest reliable design.

Document the choice.

---

# Step 38 — Preferred Persistence

For production reliability, persistent idempotency is preferable to cache-only idempotency.

Redis cache can be cleared.

Do not make notification uniqueness depend solely on volatile cache if a durable signal is required.

---

# Step 39 — push_notified_at Semantics

If implemented:

```text
null
→ notification not successfully scheduled/sent
```

and:

```text
timestamp
→ automatic publish notification already dispatched
```

Define whether this means:

```text
queue dispatch accepted
```

or:

```text
actual FCM success
```

Prefer a clear, practical meaning.

---

# Step 40 — Do Not Wait for Every Device Success

Do not wait for all FCM recipients to succeed before marking a publication event as processed.

That would make idempotency dependent on individual subscriber failures.

Mark based on successful automation dispatch/fan-out initiation.

---

# Step 41 — Failure Before Dispatch

If message construction or queue orchestration fails before any delivery is dispatched:

Do not mark the post as successfully push-notified.

Allow controlled retry.

---

# Step 42 — Partial Delivery Failure

If fan-out begins and some individual tokens fail:

Do not trigger an entire duplicate broadcast again automatically.

Individual queue retry logic from 2.3C should handle retryable failures.

---

# Step 43 — Locking

Guard against two editor requests or concurrent jobs both handling the same publication transition.

Use an appropriate database or cache lock strategy if needed.

Do not over-engineer if persistent marker + atomic update is sufficient.

---

# Step 44 — Atomic Idempotency

Where feasible, perform an atomic transition such as:

```text
push_notified_at IS NULL
→ claim automation
```

to prevent concurrency duplication.

Implement carefully.

---

# Step 45 — Publishing through Filament

Verify the actual Filament Post publish action triggers the same canonical publish automation.

Do not add separate push code directly to Filament if domain-level publishing can cover it.

---

# Step 46 — Publishing through Other Code Paths

Check:

```text
Artisan commands
API endpoints
future scheduler
importer
manual model update
```

Determine which should trigger automatic push.

---

# Step 47 — Importer Exclusion

WordPress import/backfill must NOT notify subscribers for thousands of historical posts.

Inspect importer code.

Add a safe exclusion if the domain event could accidentally fire during import.

---

# Step 48 — Seeder Exclusion

Database seeders/tests should not trigger real push delivery.

---

# Step 49 — Scheduled Posts

Inspect whether Daily Samvad supports:

```text
status = future
published_at > now()
```

or scheduled publishing.

If it does, automatic push should occur when the post truly becomes publicly published, not when it is scheduled.

---

# Step 50 — Scheduled Publish Command

If an existing scheduler/command transitions future posts to published, ensure it uses the canonical publishing flow.

Do not create another scheduler if one already exists.

---

# Step 51 — No Early Push

This must never happen:

```text
Editor schedules post for 8 PM
4 PM → push notification sent
```

Push should happen at actual publication.

---

# Step 52 — Unpublish / Republish

Define behavior explicitly.

Example:

```text
published
→ draft
→ published again
```

Recommended default:

Do NOT automatically send another push if `push_notified_at` already exists.

Avoid duplicate alerts.

---

# Step 53 — Republish Override

Do not build a republish notification override UI yet.

Manual push from Phase 2.3E can handle future editorial needs.

---

# Step 54 — Published Post Status Correction

Changing a published post to another non-public status and back due to accidental workflow correction should not flood users.

Persistent idempotency should protect this.

---

# Step 55 — New Post Only vs Republished

Document the chosen rule.

Preferred:

```text
one automatic notification per post
```

unless explicit later manual notification is requested.

---

# Step 56 — Post Without Subscribers

If no active subscriptions exist:

```text
publish succeeds
push automation safely no-ops
```

Do not fail publication.

---

# Step 57 — Push Engine Failure

If Firebase infrastructure is misconfigured:

```text
post must still publish
```

Push automation failure must not roll back editorial publishing.

---

# Step 58 — Failure Logging

Log push automation failures with safe context:

```text
post_id
event
error classification
```

Do not log:

```text
FCM tokens
OAuth tokens
private credentials
```

---

# Step 59 — Editor UX

Do not make an editor wait for thousands of push sends.

Post publish request must remain fast.

Use queue fan-out.

---

# Step 60 — Queue Availability

If queue dispatch is asynchronous in production, ensure the automation uses existing queue architecture.

Do not force synchronous Firebase calls from Post save.

---

# Step 61 — Queue Down Scenario

If queue backend is temporarily unavailable:

- publication should still succeed;
- failure should be visible in logs/reporting;
- automation should have a recoverable path where practical.

Do not build complex queue recovery UI yet.

---

# Step 62 — Queue Name

Reuse Phase 2.3C queue naming.

Do not create another notification queue.

---

# Step 63 — Notification Priority

Do not implement advanced FCM priority tuning unless Phase 2.3C already provides a generic setting and it is needed.

---

# Step 64 — Post Title Changes After Dispatch

Once automatic push is queued, later post title edits should not alter already queued notification unexpectedly unless architecture intentionally loads Post at send time.

Prefer immutable message payload when dispatch occurs.

---

# Step 65 — Job Payload Consistency

If queue jobs receive `PushMessage`, ensure title/body/URL represent publication-time content.

Avoid loading Post later and unintentionally sending a changed headline.

---

# Step 66 — URL Stability

Use final canonical URL at publication time.

If slug changes later, existing Laravel redirect architecture should handle old links if applicable.

Do not redesign redirects here.

---

# Step 67 — Message Factory Reusability

`PostPushMessageFactory` should be deterministic and independently testable.

Do not perform FCM calls inside it.

---

# Step 68 — Suggested Factory API

Conceptually:

```php
$message = $factory->fromPost($post);
```

Actual naming should follow project conventions.

---

# Step 69 — Configurable Body Length

If useful:

```php
'auto_publish' => [
    'body_length' => 160,
]
```

or equivalent.

Do not scatter numeric constants.

---

# Step 70 — Configurable Image

Consider:

```php
'auto_publish' => [
    'include_image' => true,
]
```

only if this meaningfully improves operational flexibility.

Avoid excessive config.

---

# Step 71 — Configurable Default Enabled

For production safety during deployment, consider whether default should be:

```text
false until explicitly enabled
```

This may be safer than instantly pushing during deployment.

Recommended:

```dotenv
PUSH_AUTO_PUBLISH_ENABLED=false
```

in `.env.example` or safe deployment docs.

The application owner explicitly enables it after verification.

---

# Step 72 — Safe Rollout

This is strongly recommended.

Deployment sequence:

```text
deploy code
→ auto publish disabled
→ test push:test
→ verify queue
→ publish controlled test article if needed
→ enable auto push
```

Do not accidentally notify all subscribers during initial engine deployment.

---

# Step 73 — Config Cache

If new env/config is introduced, verify:

```bash
php artisan config:cache
```

compatibility.

---

# Step 74 — Post Model Pollution

Keep Post model changes minimal.

Do not add large push-related methods directly into Post if a service/factory can handle it.

---

# Step 75 — Observer Pollution

If existing PostObserver already handles cache/SEO/workflow tasks, avoid filling it with delivery logic.

Prefer dispatching a domain event.

---

# Step 76 — Event Listener Registration

Follow Laravel 12/project event discovery conventions.

Do not duplicate event registration if auto-discovery is enabled.

Inspect existing structure.

---

# Step 77 — ShouldQueue

If listener itself queues, use Laravel queue contracts correctly.

But avoid redundant nested queueing.

Document the final architecture.

---

# Step 78 — AfterCommit

Use Laravel's appropriate after-commit behavior where needed.

This is critical for production correctness.

---

# Step 79 — Event Serialization

If event is queued, ensure Post serialization is safe.

Prefer IDs or `SerializesModels` according to existing architecture.

---

# Step 80 — Tests: Draft Save

Create test:

```text
draft post saved
→ zero push dispatch
```

---

# Step 81 — Tests: Pending Save

Create test:

```text
pending/review post saved
→ zero push dispatch
```

based on actual workflow statuses.

---

# Step 82 — Tests: First Publish

Create test:

```text
draft
→ published
```

Expected:

```text
PostPublished event
PushMessage created
push dispatch called once
```

---

# Step 83 — Tests: Published Edit

Create test:

```text
published post title edited
```

Expected:

```text
zero additional automatic push
```

---

# Step 84 — Tests: Published Body Edit

Same expectation:

```text
zero additional automatic push
```

---

# Step 85 — Tests: SEO Edit

If SEO fields are separate:

```text
zero additional push
```

---

# Step 86 — Tests: Republish

Test:

```text
published
→ draft
→ published
```

Expected according to chosen policy.

Recommended:

```text
no second automatic push
```

---

# Step 87 — Tests: No Subscribers

Publish with zero active subscriptions.

Expected:

```text
post published successfully
no exception
```

---

# Step 88 — Tests: Push Disabled

With:

```text
PUSH_AUTO_PUBLISH_ENABLED=false
```

or config equivalent:

Expected:

```text
post publishes
no push dispatch
```

---

# Step 89 — Tests: Push Enabled

With config enabled:

Expected:

```text
dispatch occurs once
```

---

# Step 90 — Tests: Imported Post

Simulate/import path where applicable.

Expected:

```text
historical imported published post
→ no automatic push
```

---

# Step 91 — Tests: Scheduled Post

If scheduling exists:

```text
schedule post
→ no push now
```

When actual scheduled publishing occurs:

```text
one push
```

---

# Step 92 — Tests: Message Title

Verify Post title maps correctly.

---

# Step 93 — Tests: Message Body

Verify excerpt/body fallback rules.

---

# Step 94 — Tests: HTML Cleanup

Input:

```html
<p>Hello <strong>Punjab</strong></p>
```

Expected body:

```text
Hello Punjab
```

---

# Step 95 — Tests: URL

Verify generated URL matches real frontend route.

---

# Step 96 — Tests: Image

Verify featured image URL is included when available.

---

# Step 97 — Tests: Missing Image

Verify push message remains valid.

---

# Step 98 — Tests: Duplicate Concurrent Handling

Where practical test two attempts to process the same post.

Expected:

```text
one automatic broadcast initiation
```

---

# Step 99 — Tests: Failure Does Not Break Publish

Mock push automation failure.

Expected:

```text
Post remains published
```

---

# Step 100 — Events / Queue Fakes

Use:

```php
Event::fake()
Queue::fake()
```

or service mocks as appropriate.

Do not call real Firebase.

---

# Step 101 — Do Not Test Through Real FCM

All automated tests must remain offline.

---

# Step 102 — Database Migration

Phase 2.3D may require one safe additive migration only if persistent idempotency requires it.

Example:

```text
add_push_notified_at_to_posts_table
```

Do not create unrelated schema.

---

# Step 103 — Production Data Safety

Posts table contains large imported production dataset.

If adding a nullable column:

```text
push_notified_at nullable
```

ensure migration is safe and additive.

Do not rewrite all post records.

---

# Step 104 — Historical Posts

If a new nullable `push_notified_at` is added, historical published posts will also be null.

That must NOT mean they should all automatically send.

Trigger logic must rely on future publish transition, not a backfill scan.

---

# Step 105 — No Backfill Push

Do not create a command that sends pushes for all old posts.

---

# Step 106 — Optional Backfill Marker

If operationally useful, a future separate command could mark existing posts as already-notified.

But do not add one unless necessary.

Transition-based logic should make it unnecessary.

---

# Step 107 — Public Visibility

Ensure the Post is actually publicly accessible before push automation.

Do not alert users to:

```text
private
draft
pending
future
```

content.

---

# Step 108 — Soft Deletes

Deleted/trashed posts must never auto-push.

---

# Step 109 — Authorization Separation

Push automation occurs after successful authorized publish action.

Do not create a new authorization bypass.

---

# Step 110 — Manual Database Update Caveat

If someone directly updates MySQL status field outside Laravel:

automatic push is not guaranteed and should not be depended on.

Document this if relevant.

---

# Step 111 — Artisan/Tinker Caveat

If developers manipulate status via Tinker, clarify whether model events trigger automation.

Use safe test environments.

---

# Step 112 — Cache Invalidation Order

Inspect existing cache invalidation.

Preferred logical ordering:

```text
post committed
→ cache invalidation
→ publish event
→ push dispatch
```

But follow existing architecture.

Push must not interfere with homepage freshness.

---

# Step 113 — Homepage Cache Issue Protection

Do not alter cache TTL or invalidate unrelated caches in this phase.

---

# Step 114 — Notification URL Public Availability

Push should only contain URL expected to resolve publicly.

No admin URLs.

---

# Step 115 — Localization

Use Post's stored title/excerpt as-is.

Do not add translation system.

---

# Step 116 — Unicode

Ensure Hindi/Punjabi/English UTF-8 content works.

Do not apply ASCII-only sanitization.

---

# Step 117 — Emoji

Do not strip normal Unicode/emoji unless necessary.

---

# Step 118 — HTML Entity Decoding

If content includes entities:

```text
&amp;
&quot;
```

convert safely for notification text where appropriate.

---

# Step 119 — Script Removal

Never derive message body by unsafe naive regex if existing HTML-to-text helpers exist.

Reuse safe helpers.

---

# Step 120 — Exception Handling

The listener/orchestrator must catch expected push-specific infrastructure errors where necessary.

Do not swallow all exceptions silently.

Log safely.

---

# Step 121 — Fatal Code Error

Programming errors should remain visible in logs/tests.

Do not blanket:

```php
catch (Throwable) {}
```

without logging.

---

# Step 122 — Publication Independence

Push is a side effect.

Publication is the primary business transaction.

A push outage must not prevent news publishing.

---

# Step 123 — Operational Logging

Useful safe log fields:

```text
post_id
post_status
push automation enabled
broadcast dispatched
```

Avoid noisy success logs for every subscriber.

---

# Step 124 — No Per-Subscriber Logs Here

Individual FCM delivery logging belongs to transport/analytics architecture.

---

# Step 125 — Metrics

Do not implement dashboards or counters yet.

---

# Step 126 — Documentation

Create/update:

```text
docs/push-notifications/post-publish-automation.md
```

or project-equivalent location.

---

# Step 127 — Documentation Content

Document:

1. authoritative publish transition;
2. event/listener architecture;
3. message generation;
4. subscriber targeting;
5. idempotency;
6. queue flow;
7. scheduled post behavior;
8. importer behavior;
9. failure behavior;
10. enable/disable config.

---

# Step 128 — Deployment Procedure

Document safe enable sequence.

Example:

```text
1. deploy 2.3D code
2. keep auto-publish disabled
3. verify migrations
4. verify queue worker
5. run push:test
6. verify browser receives test
7. enable auto publish
8. publish controlled article
9. verify one push only
```

---

# Step 129 — Environment Variables

Document new variables, if any.

Example:

```dotenv
PUSH_AUTO_PUBLISH_ENABLED=false
```

Do not include secrets.

---

# Step 130 — Queue Worker

Confirm the same Phase 2.3C push queue is processed.

Do not create another Supervisor program unnecessarily.

---

# Step 131 — Production Queue Restart

Document:

```bash
php artisan queue:restart
```

after deployment where applicable.

---

# Step 132 — Validation

Run targeted tests.

Example:

```bash
php artisan test tests/Feature/Push
```

Use actual test paths.

---

# Step 133 — Full Test Suite

Run:

```bash
php artisan test
```

where practical.

---

# Step 134 — Formatting

Run existing formatter:

```bash
./vendor/bin/pint
```

---

# Step 135 — Migration Status

If migration added:

```bash
php artisan migrate:status
```

Verify it is correct.

Do not use:

```bash
php artisan migrate:fresh
```

---

# Step 136 — Frontend Build

If no frontend files change, build may not be necessary.

If any UI/service-worker/frontend config is touched, run:

```bash
npm run build
```

---

# Step 137 — Config Validation

Run:

```bash
php artisan optimize:clear
```

Then verify config caching if appropriate.

---

# Step 138 — Git Review

Before completion:

```bash
git status --short
git diff --stat
```

Inspect changed files.

---

# Step 139 — Secret Safety

Verify no:

```text
.env
Firebase private key
service account JSON
OAuth token
production FCM token
```

was added.

---

# Step 140 — Definition of Done

Phase 2.3D is complete only when:

- actual publish transition is clearly defined;
- automatic push occurs only on true publication;
- draft/pending/review saves do not send;
- published edits do not send duplicates;
- republish behavior is defined and tested;
- Post publish event or equivalent canonical trigger exists;
- automation occurs after DB commit where needed;
- Post → PushMessage factory exists;
- title mapping works;
- body mapping works;
- URL mapping works;
- image mapping works;
- Unicode content works;
- all active subscribers can be targeted through 2.3C engine;
- inactive subscriptions are excluded;
- queue architecture is reused;
- publishing remains fast;
- push failures do not prevent publishing;
- importer does not broadcast historical content;
- scheduled posts do not notify early;
- auto-publish push has a config switch;
- deployment can keep automation disabled until verified;
- idempotency prevents duplicate automatic broadcasts;
- automated tests pass;
- no real Firebase calls occur in tests;
- documentation exists;
- no Phase 2.3E+ functionality is implemented.

---

# Expected Architecture

Approximate structure:

```text
app/
├── Events/
│   └── PostPublished.php
│
├── Listeners/
│   └── SendPostPublishedPushNotification.php
│
└── Services/
    └── Push/
        └── PostPushMessageFactory.php
```

Possible migration:

```text
database/migrations/
└── xxxx_add_push_notified_at_to_posts_table.php
```

Only if persistent idempotency requires it.

Follow existing architecture instead of forcing these paths.

---

# Required Completion Report

At completion provide:

## 1. Phase Summary

Explain the automation implemented.

## 2. Publish Detection

Explain the authoritative rule for a post becoming published.

## 3. Existing Workflow Integration

Explain how Reporter/Reviewer/Editor publishing flow connects to the automation.

## 4. Event Architecture

Report:

```text
event
listener
after-commit behavior
```

## 5. Push Message Factory

Explain:

```text
title
body
URL
image
data
```

## 6. Subscriber Targeting

Confirm that Phase 2.3D targets active subscriptions only.

## 7. Idempotency

Explain exactly how duplicate automatic pushes are prevented.

## 8. Republish Behavior

Explain:

```text
published → draft → published
```

behavior.

## 9. Scheduled Posts

Explain whether/how scheduled publishing is supported.

## 10. Import Safety

Confirm WordPress import/backfill does not broadcast historical posts.

## 11. Failure Behavior

Explain what happens if:

```text
Firebase unavailable
queue unavailable
message construction fails
```

and confirm publishing remains functional.

## 12. Configuration

List any new environment/config variables.

## 13. Files Created

List every created file.

## 14. Files Modified

List every modified file and why.

## 15. Database

If migration was added, explain it.

If no migration was necessary, state that clearly.

## 16. Tests

Report:

```text
tests created
tests executed
passed
failed
```

## 17. Validation

Report:

```text
php artisan test
Pint
migrate:status if applicable
npm run build if applicable
```

## 18. Deployment Safety

Explain the recommended process for enabling automatic push in production.

## 19. Scope Verification

Explicitly confirm these were NOT implemented:

```text
Filament campaign panel
manual campaign management
category/topic subscriptions
FCM topics
notification analytics
click tracking
CTR dashboard
advanced rate limiting
```

## 20. Phase 2.3E Readiness

Explain how Filament manual notifications can later reuse the same `PushNotificationService`.

## 21. Final Status

Finish with exactly one:

```text
PHASE 2.3D COMPLETE
```

or:

```text
PHASE 2.3D BLOCKED
```

---

# Final Instruction

Fully implement:

# Phase 2.3D — Post Publish Automation

Do not merely audit the repository.

Inspect the actual existing post publishing workflow and completed Phase 2.3A–2.3C implementation first.

Do not duplicate existing events/services.

Make the minimum clean changes necessary to provide:

```text
Post truly becomes published
        ↓
one automatic publication event
        ↓
PostPushMessageFactory
        ↓
PushMessage
        ↓
Phase 2.3C PushNotificationService
        ↓
Queue
        ↓
Active subscribers
```

Automatic notification must occur only once per intended publication.

Editing an already published post must not send another notification.

Importing historical posts must not send notifications.

Scheduling a future post must not notify before its true publication time.

Push failures must never prevent a post from being published.

Do not begin Phase 2.3E.

Do not build Filament campaigns, topic preferences, analytics, or rate-limit systems.

Run all relevant tests and validation commands.

Fix regressions introduced by this phase.

Provide the full required completion report.

End with exactly one:

`PHASE 2.3D COMPLETE`

or

`PHASE 2.3D BLOCKED`