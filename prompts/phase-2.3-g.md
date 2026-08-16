# Phase 2.3G — Push Analytics & Click Tracking

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
- Existing authentication and role system
- Existing Reporter → Reviewer/Editor → Publish workflow
- Existing media/image system
- Existing caching architecture
- Phase 2.3A — Firebase & Browser Push Foundation
- Phase 2.3B — Push Subscription Management
- Phase 2.3C — Laravel Push Notification Engine
- Phase 2.3D — Post Publish Automation
- Phase 2.3E — Filament Push Notification Panel
- Phase 2.3F — Topics & Category Preferences

This phase continues:

# Version 2.3 — Push Notification System

---

# Phase Objective

Implement a production-ready analytics and tracking layer for push notifications.

The system must be able to answer questions such as:

```text
How many subscribers were targeted?
How many delivery attempts were made?
How many sends succeeded?
How many failed permanently?
How many failed temporarily?
How many notifications were clicked?
What was the click-through rate?
Which manual notification performed best?
Which automatic post notification received the most clicks?
```

The analytics system must remain compatible with:

- automatic Post notifications;
- manual Filament notifications;
- topic/category targeting;
- guest subscriptions;
- authenticated subscriptions;
- Redis queue delivery;
- Firebase HTTP v1 delivery.

---

# Important Analytics Semantics

Do not falsely claim browser-level delivery information that Firebase does not reliably provide to this architecture.

Clearly distinguish:

```text
Targeted
Queued
Send Attempted
FCM Accepted
FCM Rejected
Clicked
```

Do NOT label:

```text
FCM accepted
```

as:

```text
Delivered to device
```

unless a reliable delivery receipt exists.

For this phase, use accurate terminology.

Recommended:

```text
FCM Accepted
```

or:

```text
Sent Successfully
```

instead of:

```text
Delivered
```

when only Firebase HTTP acceptance is known.

---

# Core Architecture

Target architecture:

```text
Push Campaign / Automatic Post Push
            ↓
Notification Tracking Record
            ↓
Audience Resolver
            ↓
Unique PushSubscriptions
            ↓
Queue Jobs
            ↓
Firebase HTTP v1
            ↓
Per-recipient Delivery Attempt
            ↓
FCM Accepted / Failed
            ↓
Tracking URL
            ↓
Notification Click
            ↓
Analytics Aggregation
            ↓
Filament Dashboard
```

---

# Primary Goals

Implement:

1. notification-level analytics identity;
2. per-recipient delivery records;
3. queued/attempted/accepted/failed tracking;
4. FCM error classification persistence;
5. click tracking;
6. unique click counting;
7. total click counting;
8. CTR calculation;
9. automatic Post notification analytics;
10. manual Filament notification analytics;
11. topic-targeted notification analytics;
12. analytics summary services;
13. Filament notification analytics UI;
14. subscriber-level operational visibility where appropriate;
15. privacy-safe tracking;
16. efficient database indexes;
17. retention/cleanup preparation;
18. automated tests;
19. documentation.

---

# Strict Scope Boundary

Phase 2.3G is:

# Push Analytics + Click Tracking

Do NOT implement:

- advanced queue throttling;
- complex rate-limit algorithms;
- worker autoscaling;
- exponential global campaign throttling;
- security hardening beyond what analytics endpoints require;
- geographic analytics;
- browser fingerprinting;
- behavioral profiling;
- AI targeting;
- A/B testing;
- recurring campaigns;
- revenue attribution;
- conversion tracking.

Those belong outside this phase or later roadmap.

Phase 2.3H will handle:

- Queue
- Security
- Rate Limiting

Phase 2.3I will handle:

- Production Testing
- Deployment
- Final Audit

---

# Critical First Step — Audit Existing Implementation

Before modifying anything, inspect:

```text
app/Models/PushSubscription.php
app/Models/PushNotification.php
app/Models/PushTopic.php
app/Services/Push/
app/Jobs/Push/
app/Filament/
app/Events/
app/Listeners/
database/migrations/
routes/
resources/js/push/
public/firebase-messaging-sw.js
tests/Feature/Push/
```

Inspect actual completed Phase 2.3A–2.3F code.

Do not assume previous prompt file names exactly match implementation.

---

# Audit Delivery Result Contract

Inspect the actual Phase 2.3C delivery result object.

Identify fields equivalent to:

```text
success
message_id
http_status
error_code
error_message
token_invalid
retryable
```

Reuse these fields.

Do not create a second incompatible result system.

---

# Audit Manual Notification Model

Inspect Phase 2.3E.

Identify:

```text
PushNotification
status
recipient_count
queued_at
sent_at
created_by
targeting
```

Extend it safely rather than replacing it.

---

# Audit Automatic Post Push

Inspect Phase 2.3D.

Determine how an automatic Post notification is uniquely represented.

If automatic pushes currently do not persist a campaign/notification record, Phase 2.3G should introduce a clean tracking entity so both manual and automatic pushes can share analytics.

---

# Critical Design Goal

Manual and automatic notification analytics should converge on one reusable analytics model.

Avoid two parallel systems:

```text
manual_push_logs
automatic_post_push_logs
```

Prefer a shared notification/campaign identity.

---

# Step 1 — Notification Tracking Entity

Evaluate whether existing:

```text
push_notifications
```

can represent both:

```text
manual
automatic_post
```

If yes, extend it.

This is preferred.

Possible additional fields:

```text
source_type
source_id nullable
notification_type
target_type
recipient_count
queued_count
attempted_count
accepted_count
failed_count
clicked_count
unique_clicked_count
```

Do not blindly add all counters if they can be efficiently derived.

Choose a balanced design.

---

# Step 2 — Notification Source Type

Suggested values:

```text
manual
post
system
```

or equivalent.

Use enum only if consistent with project architecture.

---

# Step 3 — Automatic Post Notification Record

When Phase 2.3D dispatches an automatic Post push, ensure a tracking record exists.

Example:

```text
source_type = post
source_id = post.id
```

This record represents that one automatic publication notification.

Do not create duplicate records when the same Post is edited.

---

# Step 4 — Manual Notification Record

Existing Phase 2.3E record remains the campaign/tracking identity.

Do not create another duplicate analytics record.

---

# Step 5 — Delivery Records

Create a table such as:

```text
push_notification_deliveries
```

or project-consistent equivalent.

Suggested fields:

```text
id
push_notification_id
push_subscription_id nullable
subscription_token_hash nullable
status
attempt_count
fcm_message_id nullable
http_status nullable
error_code nullable
error_category nullable
retryable boolean
queued_at nullable
attempted_at nullable
accepted_at nullable
failed_at nullable
last_attempted_at nullable
created_at
updated_at
```

Use only fields needed by actual architecture.

---

# Step 6 — Do Not Store Raw Token Again

Delivery analytics must NOT duplicate raw FCM registration tokens.

Use:

```text
push_subscription_id
```

and if necessary:

```text
token_hash
```

for historical correlation.

Do not store raw token in delivery table.

---

# Step 7 — Subscription Deletion Safety

A PushSubscription may later be deleted.

Historical delivery records should preferably remain.

Use:

```text
push_subscription_id nullable
nullOnDelete()
```

or equivalent.

If a token hash snapshot is useful, store only a safe hash.

---

# Step 8 — Unique Delivery Constraint

One notification should normally have only one delivery record per target subscription.

Add unique constraint:

```text
UNIQUE(push_notification_id, push_subscription_id)
```

where compatible.

This provides strong duplicate protection.

---

# Step 9 — Token Rotation Caveat

If subscription identity changes during fan-out due to token rotation, inspect Phase 2.3B behavior.

Use stable PushSubscription ID where possible.

Do not double-send merely because token changed.

---

# Step 10 — Delivery Statuses

Use clear statuses.

Suggested:

```text
queued
attempting
accepted
failed
skipped
```

Potentially:

```text
retry_pending
```

only if useful.

Do not create excessive state complexity.

---

# Step 11 — Meaning of Accepted

Define:

```text
accepted
```

as:

```text
Firebase HTTP v1 accepted the message and returned success/message ID.
```

It does NOT prove browser display.

---

# Step 12 — Failed Status

Failure can include:

```text
invalid_token
invalid_request
auth
quota
server
network
unknown
```

Store error category separately if useful.

---

# Step 13 — Retryable Flag

Persist whether failure was classified retryable by Phase 2.3C.

This aids analytics and Phase 2.3H.

---

# Step 14 — Attempt Count

Each actual FCM request increments:

```text
attempt_count
```

Retries should not create duplicate delivery records.

They update the same record.

---

# Step 15 — Queue Record Creation Strategy

Determine when delivery records are created.

Recommended:

At fan-out:

```text
notification audience resolved
→ delivery row created/upserted
→ delivery job queued
```

This allows accurate queued count.

Do not create millions of records synchronously in a web request.

---

# Step 16 — Scalable Delivery Record Fan-Out

Use chunking.

Do not:

```php
$subscriptions->get()->each(...)
```

for huge audiences.

Integrate delivery-record creation into the existing chunked queue dispatch architecture.

---

# Step 17 — Unique Job Identity

Each queued job should reference:

```text
delivery ID
```

where practical.

This is cleaner than passing raw subscription ID + notification data independently.

---

# Step 18 — Delivery Job Flow

Target:

```text
PushNotificationDelivery
      ↓
queue job
      ↓
load notification snapshot
      ↓
load current subscription
      ↓
send through PushNotificationService/Firebase client
      ↓
update delivery status
```

Do not duplicate FCM transport.

---

# Step 19 — Message Snapshot

Analytics records must represent the exact notification sent.

The parent PushNotification should contain the final:

```text
title
body
image
URL
targeting
```

snapshot.

Do not rebuild message from mutable Post data at send time if a snapshot already exists.

---

# Step 20 — Automatic Post Snapshot

For automatic Post notifications, create the PushNotification snapshot at publication time using existing PostPushMessageFactory.

This ensures later Post edits do not change analytics record content.

---

# Step 21 — Automatic Post Idempotency

Reuse Phase 2.3D idempotency.

One intended automatic notification should correspond to one PushNotification analytics record.

---

# Step 22 — Manual Idempotency

Reuse Phase 2.3E duplicate-send protection.

One manual notification record should not create multiple delivery sets accidentally.

---

# Step 23 — Topic Target Snapshot

Phase 2.3F targeting should remain represented on the parent notification.

Do not store the full matching topic list repeatedly on every delivery row.

---

# Step 24 — Audience Count

`recipient_count` should mean:

```text
unique active subscriptions selected for fan-out
```

Keep this semantic.

---

# Step 25 — Queued Count

Queued count can be:

```text
number of delivery rows successfully queued
```

If derived efficiently, do not necessarily store denormalized count.

---

# Step 26 — Accepted Count

Count delivery rows:

```text
status = accepted
```

or maintain cached counter if scale requires it.

Prefer correctness first.

---

# Step 27 — Failed Count

Count final failed rows.

Clarify whether transient retries still pending are included.

Recommended:

```text
failed_count
=
final/non-pending failures
```

---

# Step 28 — Click Tracking Architecture

Every notification that has a destination URL should use a tracking redirect.

Target:

```text
Push Notification
     ↓
Tracking URL
     ↓
Laravel click endpoint
     ↓
record click
     ↓
redirect to original target URL
```

---

# Step 29 — Tracking URL

Do NOT place the raw target URL directly in notification payload when click analytics is enabled.

Instead use:

```text
https://dailysamvad.com/push/click/{tracking-id}
```

or equivalent safe route.

---

# Step 30 — Opaque Tracking Identifier

Do not expose sequential database IDs alone if avoidable.

Use a public opaque tracking identifier such as:

```text
UUID
ULID
random signed token
```

Choose project-consistent approach.

---

# Step 31 — Tracking Identity Granularity

For accurate unique recipient click analytics, ideally tracking should identify:

```text
notification delivery
```

not only:

```text
notification campaign
```

This allows:

```text
which delivery/subscription clicked
```

without exposing identity.

---

# Step 32 — Delivery Public ID

Consider adding:

```text
public_id
```

to `push_notification_deliveries`.

Use:

```text
UUID/ULID
```

unique index.

The click URL references this public ID.

---

# Step 33 — Privacy

Do not expose:

```text
user ID
subscription ID
FCM token
email
IP
```

inside click URL.

Opaque identifier only.

---

# Step 34 — Click Endpoint

Create a route such as:

```text
GET /push/click/{publicId}
```

or equivalent.

This endpoint should:

1. resolve delivery;
2. record click;
3. redirect safely to the original destination.

---

# Step 35 — Redirect Safety

Do not trust arbitrary destination URL from click request.

Target URL must come from stored notification snapshot.

Never accept:

```text
?redirect=https://evil.example
```

as authoritative.

Prevent open redirect vulnerabilities.

---

# Step 36 — Missing Target URL

If notification has no destination URL:

Either:

```text
redirect to homepage
```

or avoid generating tracking click link.

Choose predictable behavior.

---

# Step 37 — Click Record Table

Decide whether click counters on delivery are sufficient.

Recommended for richer analytics:

Create:

```text
push_notification_clicks
```

Suggested fields:

```text
id
push_notification_id
push_notification_delivery_id nullable
clicked_at
ip_hash nullable
user_agent_hash nullable
created_at
```

Do NOT store unnecessary raw personal data.

---

# Step 38 — Simpler Alternative

If detailed repeated click history is unnecessary, delivery row can contain:

```text
first_clicked_at
last_clicked_at
click_count
```

This may be enough.

Evaluate requirements.

---

# Step 39 — Recommended Balanced Design

Recommended:

On delivery row:

```text
first_clicked_at
last_clicked_at
click_count
```

This supports:

```text
unique clicks
total clicks
CTR
```

without creating a very large clicks table.

Use a separate clicks table only if existing analytics needs detailed click event history.

---

# Step 40 — Unique Click

A delivery counts as uniquely clicked when:

```text
first_clicked_at IS NOT NULL
```

---

# Step 41 — Total Clicks

Every tracking endpoint visit increments:

```text
click_count
```

and updates:

```text
last_clicked_at
```

---

# Step 42 — CTR Definition

Define:

```text
unique CTR
=
unique clicked deliveries / accepted deliveries × 100
```

This is recommended.

Do not use recipient count as denominator if many sends failed.

---

# Step 43 — Alternative Target CTR

You may also expose:

```text
target CTR
=
unique clicked / recipients targeted
```

but label it clearly.

Primary metric should preferably be:

```text
Click-through Rate
=
Unique Clicks / FCM Accepted
```

---

# Step 44 — Division by Zero

If accepted count = 0:

CTR should be:

```text
0
```

or:

```text
N/A
```

Choose consistent UI behavior.

Do not divide by zero.

---

# Step 45 — Click Before Accepted State Edge Case

Analytics should tolerate unusual race/inconsistent state.

Do not crash if click arrives while delivery status data is delayed.

Record click if delivery identifier is valid.

---

# Step 46 — Duplicate Clicks

Same delivery clicked five times:

```text
total clicks += 5
unique clicks = 1
```

---

# Step 47 — Browser Open vs Click

Do not claim notification was "opened" merely because tracking URL was clicked.

Use:

```text
Clicked
```

terminology.

---

# Step 48 — Foreground Notification

If foreground notification uses an in-page UI and user clicks it, route through same tracking URL.

Inspect Phase 2.3A foreground behavior.

Reuse tracking contract.

---

# Step 49 — Service Worker Click

Update:

```text
firebase-messaging-sw.js
```

only if required.

Ensure notification click opens tracking URL rather than direct target URL when analytics identity exists.

---

# Step 50 — Existing Click Logic

Do not create two competing `notificationclick` handlers.

Inspect current service worker first.

---

# Step 51 — Tracking Payload

Push message data may include:

```text
tracking_url
```

or equivalent.

Do not place private analytics identifiers in user-visible text.

---

# Step 52 — Destination URL Snapshot

Parent notification must retain original:

```text
target_url
```

Tracking redirect resolves from this.

---

# Step 53 — No Query Injection

Do not blindly concatenate arbitrary query parameters from browser.

---

# Step 54 — Click Endpoint Caching

The click endpoint must not be cached.

It must execute for every click.

Set appropriate response/cache behavior where necessary.

---

# Step 55 — Varnish / Cloudflare Awareness

Document that:

```text
/push/click/*
```

must bypass full-page caching/CDN caching.

Do not globally disable caching.

---

# Step 56 — Bot/Scanner Clicks

Email-like security scanners are less common for push, but automated requests may still occur.

Do not over-engineer bot detection in this phase.

Document that clicks represent endpoint hits.

---

# Step 57 — IP Storage

Do NOT store raw IP unless current privacy architecture requires it.

If useful for abuse/duplicate analysis, use:

```text
hash
```

with rotating/salted strategy.

But click counting should work without IP.

---

# Step 58 — User Agent

Do not store full raw UA merely for analytics unless necessary.

This phase does not need browser analytics.

---

# Step 59 — No Fingerprinting

Never fingerprint push users.

---

# Step 60 — Delivery Error Persistence

Persist safe error fields:

```text
error_code
error_category
http_status
retryable
```

Do not store entire raw Firebase response blobs by default.

---

# Step 61 — Error Message

If storing message:

sanitize and limit length.

Never store:

```text
OAuth token
private key
raw request headers
```

---

# Step 62 — Invalid Token Analytics

If FCM returns permanent invalid token:

```text
delivery → failed
error_category → invalid_token
subscription → inactive
```

Reuse Phase 2.3C lifecycle.

---

# Step 63 — Retry Analytics

Transient retry:

```text
attempt_count increments
delivery remains pending/retryable
```

On eventual success:

```text
status = accepted
```

Do not count all intermediate failures as final failed deliveries.

---

# Step 64 — Queue Failure Before FCM

If a job permanently fails before successful Firebase call:

delivery should eventually be identifiable as failed/pending stale.

Do not implement complex queue reconciliation yet if it belongs to 2.3H.

But create enough fields to support it.

---

# Step 65 — Failed Job Hook

Use Laravel job failure hooks where appropriate:

```php
failed(Throwable $exception)
```

to mark delivery state safely.

Do not expose sensitive exception internals.

---

# Step 66 — Manual Notification Status

Phase 2.3E's parent status:

```text
queued
sent
failed
```

should be revisited carefully.

Do not change meaning without migration/documentation.

Recommended parent lifecycle:

```text
draft
queued
processing
completed
failed
```

only if truly needed.

Avoid unnecessary status churn.

---

# Step 67 — Backward Compatibility

Existing notification records must continue to render.

If adding status values, provide safe defaults and migration compatibility.

---

# Step 68 — Parent Completion

A notification can be considered fan-out complete when:

```text
all recipient delivery jobs have reached a terminal state
```

but maintaining this exactly may require aggregation.

Do not build overly complex orchestration if not needed.

You can keep parent:

```text
sent
```

meaning fan-out initiated.

Analytics rows show final state.

Document clearly.

---

# Step 69 — Analytics Service

Create a reusable service such as:

```text
PushAnalyticsService
```

Responsibilities:

```text
summaryForNotification()
subscriberStats()
recentPerformance()
topNotifications()
```

Only implement methods needed by Filament UI.

---

# Step 70 — Notification Summary DTO

Consider returning structured metrics:

```text
recipient_count
queued_count
attempted_count
accepted_count
failed_count
unique_clicks
total_clicks
ctr
```

Do not spread aggregation SQL throughout Filament pages.

---

# Step 71 — Efficient Aggregation

Use database aggregate queries.

Do not load all deliveries into PHP.

---

# Step 72 — Indexes

Likely indexes:

```text
push_notification_id
push_subscription_id
status
accepted_at
failed_at
first_clicked_at
public_id unique
```

Avoid excessive indexes.

---

# Step 73 — Scale

System should remain practical for:

```text
100k+ subscribers
millions of delivery rows over time
```

Use pagination and aggregate queries.

---

# Step 74 — Analytics Retention Preparation

Do not automatically delete analytics yet.

Phase 2.3H or future maintenance can add cleanup.

Document expected growth.

---

# Step 75 — Filament Notification Table Metrics

Extend Phase 2.3E table with compact metrics where useful:

```text
Recipients
Accepted
Failed
Clicks
CTR
```

Avoid expensive N+1 aggregate queries.

Use efficient counts/subqueries or precomputed summary.

---

# Step 76 — Notification Detail Page

Create/extend a View page showing:

```text
Title
Body
Source
Target
Created By
Created At
Queued At
Recipient Count
FCM Accepted
Failed
Unique Clicks
Total Clicks
CTR
```

---

# Step 77 — Manual vs Automatic Badge

Show source:

```text
Manual
Post
System
```

---

# Step 78 — Post Link

For automatic Post notifications:

show related Post link in admin if Post still exists.

Use safe relation.

---

# Step 79 — Delivery Breakdown

Show error categories:

```text
Invalid Token
Authentication
Quota
Server
Network
Other
```

This is valuable operational information.

---

# Step 80 — No Raw Error Dumps

Do not expose raw Firebase payloads in Filament.

---

# Step 81 — Delivery Records Table

Optional per-notification relation manager/table can show:

```text
status
attempts
FCM message ID shortened if needed
error category
accepted at
failed at
clicked
```

Do NOT display raw FCM token.

---

# Step 82 — Subscriber Identity

For authenticated subscriptions, showing user name/email in admin may be possible if current privacy/business rules allow.

But do not make it required.

A safe default is:

```text
Subscription #ID
Device/browser metadata
Status
```

Avoid exposing unnecessary personal details.

---

# Step 83 — Search Delivery Records

Allow search by:

```text
subscription ID
```

and possibly user relation if existing admin access permits.

Do not search raw token.

---

# Step 84 — Analytics Dashboard Widget

Create useful high-level widget(s) if consistent with current dashboard.

Suggested:

```text
Push Subscribers
Notifications Sent
FCM Accepted
Unique Clicks
Average CTR
```

Do not overload dashboard.

---

# Step 85 — Time Range

If practical, support:

```text
7 days
30 days
```

or simple current-period metrics.

Do not build a full BI suite.

---

# Step 86 — Push Notification Analytics Page

If cleaner than many widgets, create a dedicated Filament analytics page.

Use existing project patterns.

---

# Step 87 — Suggested Analytics Cards

Possible:

```text
Active Subscribers
Notifications
Recipients Targeted
FCM Accepted
Failures
Unique Clicks
Average CTR
```

---

# Step 88 — Recent Performance Table

Show recent notifications with:

```text
title
source
target
recipients
accepted
failed
clicks
CTR
```

---

# Step 89 — Top Notifications

Optional:

Top 5 by unique clicks or CTR.

Avoid misleading small-sample rankings.

If using CTR ranking, require a minimum accepted count or document limitation.

---

# Step 90 — No Delivery Claim

UI labels must say:

```text
FCM Accepted
```

not:

```text
Delivered
```

unless actual delivery confirmation is available.

This terminology requirement is mandatory.

---

# Step 91 — Automatic Post Analytics

Every future auto-publish notification should appear in analytics.

Do not require a separate manual campaign record.

---

# Step 92 — Existing Historical Automatic Pushes

Do not attempt to reconstruct analytics for old automatic pushes that occurred before Phase 2.3G unless records already exist.

No fabricated metrics.

---

# Step 93 — Existing Manual Push Records

Existing Phase 2.3E notification records may have no delivery rows.

Display:

```text
No delivery analytics available
```

or zero with clear distinction.

Do not invent historical data.

---

# Step 94 — Analytics Start Boundary

Document that reliable delivery analytics begin after Phase 2.3G deployment.

---

# Step 95 — Click Tracking for Existing Notifications

Old notifications already sent cannot be retroactively tracked.

Document this.

---

# Step 96 — Event / Service Integration

Integrate analytics at the application/queue layer.

Do not inject tracking SQL into Firebase client.

Transport should remain mostly provider-focused.

---

# Step 97 — Preferred Separation

```text
PushNotificationService
        ↓
Delivery Orchestrator
        ↓
Delivery Record
        ↓
FirebaseMessagingClient
```

or equivalent existing architecture.

---

# Step 98 — FCM Client Result

Existing result continues to report technical send outcome.

Analytics layer persists it.

---

# Step 99 — Delivery Orchestrator

Create/extend a service responsible for:

```text
claim delivery
attempt send
persist result
handle subscription invalidation
```

Do not duplicate Phase 2.3C logic.

---

# Step 100 — Queue Job Update

Existing queue job should use delivery ID or create/update analytics record safely.

Avoid creating another parallel job class unless necessary.

---

# Step 101 — Idempotent Job Handling

Queue retry must not create additional delivery rows.

Same delivery ID, same record.

---

# Step 102 — Duplicate Worker Execution

If same job runs twice:

backend uniqueness/state must minimize duplicate FCM sends where practical.

Do not rely solely on worker behavior.

Full distributed idempotency hardening may continue in 2.3H.

---

# Step 103 — Claim State

Before sending, atomically transition:

```text
queued → attempting
```

where appropriate.

If already accepted, job should no-op.

---

# Step 104 — Retry State

A retryable failure should allow controlled subsequent attempt.

Do not mark accepted message back to queued.

---

# Step 105 — Permanent Failure

Once permanent:

```text
failed
retryable = false
```

normal job retry should stop.

---

# Step 106 — Click Tracking URL Construction

Create dedicated service/helper.

Example:

```text
PushTrackingUrlGenerator
```

Do not build URLs manually in five different places.

---

# Step 107 — Absolute URL

Tracking URL must be absolute.

Use application route helpers.

---

# Step 108 — HTTPS

Production tracking URL must use HTTPS.

Do not hardcode domain.

---

# Step 109 — Route Signature

Consider Laravel signed URLs only if they fit the design.

A random/opaque public ID is often sufficient.

Do not make URLs excessively long.

---

# Step 110 — Guessing Resistance

Tracking IDs must be difficult to enumerate.

Sequential integer-only URLs are not preferred.

---

# Step 111 — Expiration

Do not expire click links too aggressively.

Users may click old notifications days later.

Recommended:

No short expiry.

---

# Step 112 — Deleted Notification

If parent notification is deleted—which should normally be restricted—tracking route should fail safely.

Prefer preserving sent records.

---

# Step 113 — Archived Post

If destination Post becomes unavailable:

redirect according to existing frontend behavior or homepage fallback.

Do not produce unsafe redirect.

---

# Step 114 — HTTP Redirect Code

Use normal redirect behavior.

302 is acceptable.

Do not over-engineer.

---

# Step 115 — Click Recording Transaction

Increment click counter atomically.

Avoid lost increments during concurrent clicks.

Use database atomic increment/update.

---

# Step 116 — First Click

Set:

```text
first_clicked_at
```

only if null.

---

# Step 117 — Last Click

Always update:

```text
last_clicked_at
```

---

# Step 118 — Total Count

Increment:

```text
click_count
```

atomically.

---

# Step 119 — Unique Click Aggregation

Unique clicks = deliveries where:

```text
first_clicked_at IS NOT NULL
```

---

# Step 120 — No Login Requirement

Click tracking must work for guest subscribers.

---

# Step 121 — No Cookie Requirement

Tracking should work even if user is not authenticated.

The opaque delivery ID identifies the send.

---

# Step 122 — No Cross-User Exposure

Tracking endpoint should redirect only.

Do not show delivery analytics publicly.

---

# Step 123 — Response Timing

Record click efficiently and redirect quickly.

Do not perform heavy analytics aggregation during click request.

---

# Step 124 — Queue Click Recording?

Synchronous atomic DB increment is acceptable.

Do not queue click tracking unless performance tests show need.

---

# Step 125 — Full-Page Cache

Tracking route must bypass full-page cache.

Document it.

---

# Step 126 — CSRF

Click route is GET and does not require CSRF.

This is expected because it originates from browser notification.

But it must be limited to safe click-record + redirect behavior.

---

# Step 127 — No State Mutation Beyond Analytics

Click GET route should not change user preferences, accounts, or other business data.

Only analytics counters/timestamps.

---

# Step 128 — Security

Do not allow arbitrary target URLs through route parameters.

Stored target only.

---

# Step 129 — Tests: Migration

Test schema/factories as needed.

---

# Step 130 — Tests: Delivery Creation

When notification fan-out targets N subscriptions:

Expected:

```text
N unique delivery rows
```

---

# Step 131 — Tests: Duplicate Audience

Subscriber matches multiple topics.

Expected:

```text
one delivery row
```

---

# Step 132 — Tests: Queue Dispatch

Each delivery should create expected job once.

---

# Step 133 — Tests: FCM Success

Fake accepted FCM response.

Expected:

```text
status = accepted
fcm_message_id stored
accepted_at set
attempt_count incremented
```

---

# Step 134 — Tests: Permanent Failure

Fake UNREGISTERED.

Expected:

```text
delivery failed
retryable false
error_category invalid_token
subscription inactive
```

---

# Step 135 — Tests: Retryable Failure

Fake server/network error.

Expected:

```text
attempt_count increment
retryable true
subscription remains active
```

---

# Step 136 — Tests: Retry Then Success

First attempt fails retryably.

Second succeeds.

Expected:

```text
same delivery row
attempt_count = 2
status = accepted
```

---

# Step 137 — Tests: Auth Failure

Expected:

```text
delivery failure/retry classification according to engine
subscription remains active
```

---

# Step 138 — Tests: Raw Token Not Stored

Delivery record must not contain raw FCM token column/value.

---

# Step 139 — Tests: Tracking URL

Generated URL contains opaque public identifier.

No token/user ID.

---

# Step 140 — Tests: Click Redirect

GET tracking URL:

Expected:

```text
records click
redirects to stored target URL
```

---

# Step 141 — Tests: First Click

Expected:

```text
click_count = 1
first_clicked_at set
last_clicked_at set
```

---

# Step 142 — Tests: Repeat Click

Click same link again.

Expected:

```text
click_count = 2
first_clicked_at unchanged
last_clicked_at updated
unique clicks still 1
```

---

# Step 143 — Tests: Invalid Tracking ID

Expected:

```text
safe 404 or fallback
```

No information leakage.

---

# Step 144 — Tests: No Target URL

Expected safe fallback according to chosen policy.

---

# Step 145 — Tests: Open Redirect Protection

Client must not be able to replace stored target with arbitrary URL.

---

# Step 146 — Tests: Analytics Summary

Given:

```text
100 recipients
90 accepted
10 failed
18 unique clicks
25 total clicks
```

Expected:

```text
CTR = 20%
```

if denominator is accepted count.

---

# Step 147 — Tests: Zero Accepted

CTR returns safe zero/N/A.

---

# Step 148 — Tests: Manual Analytics

Manual PushNotification summary works.

---

# Step 149 — Tests: Automatic Post Analytics

Automatic Post notification produces tracking record and metrics.

---

# Step 150 — Tests: Existing Records

Old notification without delivery rows renders analytics safely.

---

# Step 151 — Tests: Filament Authorization

Only existing authorized roles can see push analytics as appropriate.

Reuse Phase 2.3E permissions where possible.

---

# Step 152 — Analytics Permission

If project needs separate permission:

```text
view push analytics
```

or equivalent.

Follow existing naming conventions.

Do not grant reporters by default.

---

# Step 153 — Filament Tests

Use existing Filament test style.

Test:

```text
analytics page accessible to authorized user
unauthorized denied
metrics render
```

---

# Step 154 — No Real Firebase Calls

All tests remain offline.

Use existing Phase 2.3C fakes.

---

# Step 155 — Factories

Add/update:

```text
PushNotificationDeliveryFactory
```

if project test patterns use factories.

---

# Step 156 — No Fake Production Seeder

Do not seed analytics data into production.

---

# Step 157 — Database Migration Safety

All migrations additive.

Do not destructively alter existing:

```text
posts
users
categories
push_subscriptions
push_topics
push_notifications
```

---

# Step 158 — No migrate:fresh

Never run:

```bash
php artisan migrate:fresh
```

against current dataset.

---

# Step 159 — Existing Notifications Migration

If existing `push_notifications` table requires new nullable fields:

use safe defaults.

Do not rewrite all rows unnecessarily.

---

# Step 160 — Delivery Table Growth

Document that delivery rows can grow quickly.

Example:

```text
10,000 subscribers
×
100 notifications
=
1,000,000 delivery rows
```

This is expected.

Do not panic and denormalize prematurely.

---

# Step 161 — Future Retention

Document potential retention policy for 2.3H/future:

```text
keep 90/180 days
archive aggregates
prune detailed deliveries
```

Do not implement destructive pruning now unless necessary.

---

# Step 162 — Query Performance

Use pagination.

Never load all delivery rows in Filament.

---

# Step 163 — Aggregate Performance

Avoid per-row aggregate queries in notification list.

Prefer:

```text
withCount
subqueries
aggregated columns
```

according to actual Laravel architecture.

---

# Step 164 — N+1 Prevention

Eager-load only required relationships.

---

# Step 165 — Dashboard Time Queries

Use indexed timestamps.

---

# Step 166 — Tracking Endpoint Performance

Primary index:

```text
public_id unique
```

Lookup should be fast.

---

# Step 167 — Click Atomicity

Use DB-native increment.

Do not:

```php
$delivery->click_count++;
$delivery->save();
```

if concurrent clicks can lose increments.

---

# Step 168 — Analytics Counters on Parent

If you add denormalized parent counters for performance:

update them carefully.

But deriving from delivery rows is acceptable initially.

Do not create hard-to-maintain duplicate truth without need.

---

# Step 169 — Source of Truth

Delivery rows should be authoritative for:

```text
accepted
failed
clicked
```

Parent recipient_count remains audience snapshot.

---

# Step 170 — Analytics Cache

If expensive dashboard metrics need caching, use short cache TTL and proper invalidation.

But do not overcomplicate.

---

# Step 171 — Existing Redis

Use Laravel cache abstraction if needed.

Do not hardcode Redis commands.

---

# Step 172 — No Personalized Analytics Publicly

Analytics stays admin-only.

---

# Step 173 — No Subscriber Analytics Portal

Do not build user-facing "your notification history" in this phase.

---

# Step 174 — Notification List Columns

Suggested final columns:

```text
Title
Source
Target
Recipients
FCM Accepted
Failed
Unique Clicks
CTR
Status
Created At
```

---

# Step 175 — Notification Detail Metrics

Show:

```text
Recipients targeted
Jobs queued
FCM accepted
Permanent failures
Retryable/final failures
Unique clicks
Total clicks
CTR
```

Use accurate labels.

---

# Step 176 — Failure Breakdown

Show counts by category.

No raw tokens.

---

# Step 177 — Link to Related Post

Automatic Post notifications can link to:

```text
Post edit/view
```

if authorized.

---

# Step 178 — Navigation

Use:

```text
Push Notifications
```

existing Filament area.

Analytics may be:

```text
Analytics
```

subpage or tab.

Do not create confusing duplicate navigation.

---

# Step 179 — Dashboard Roles

Likely:

```text
super-admin
admin
editor
analytics-manager
```

may view analytics depending on project permissions.

Inspect actual permission model.

Do not blindly assign.

---

# Step 180 — Send vs View Analytics Permission

Viewing analytics does not automatically imply permission to send notifications.

Keep permissions separable if current architecture supports it.

---

# Step 181 — Reporter

Reporter should not automatically view subscriber-level operational analytics unless explicitly allowed.

---

# Step 182 — Privacy in Filament

Do not display:

```text
raw FCM token
OAuth token
private Firebase data
```

anywhere.

---

# Step 183 — CSV Export

Do not implement export unless already trivial and requested.

Avoid exposing subscriber data.

---

# Step 184 — Analytics Documentation

Create/update:

```text
docs/push-notifications/analytics-and-click-tracking.md
```

---

# Step 185 — Documentation Contents

Document:

1. analytics architecture;
2. notification tracking entity;
3. delivery statuses;
4. FCM Accepted semantics;
5. error categories;
6. retry behavior;
7. click tracking;
8. tracking URL;
9. CTR formula;
10. automatic Post analytics;
11. manual analytics;
12. topic-targeted analytics;
13. privacy;
14. caching requirements;
15. DB growth;
16. future retention strategy.

---

# Step 186 — Operator Documentation

Explain how to inspect:

```text
recent notifications
delivery failures
invalid tokens
click rate
```

through Filament.

---

# Step 187 — Developer Tinker Examples

Safe examples:

```php
PushNotification::latest()->first();
```

and appropriate analytics service call.

Do not print raw tokens.

---

# Step 188 — Deployment Documentation

Recommended sequence:

```text
1. deploy migrations
2. restart queue workers
3. clear config/cache as required
4. send one controlled manual notification
5. verify delivery row creation
6. verify FCM accepted status
7. click notification
8. verify click count
9. verify Filament analytics
10. publish controlled Post
11. verify automatic analytics
```

---

# Step 189 — CDN Cache Rule

Document bypass for:

```text
/push/click/*
```

where needed.

---

# Step 190 — Service Worker Cache

Tracking URL should not be intercepted incorrectly by service-worker caching.

Inspect existing worker.

---

# Step 191 — Click Offline Behavior

Do not build offline click queueing.

If user is offline, browser navigation naturally waits/fails according to browser.

---

# Step 192 — Monitoring Preparation

Expose enough information for Phase 2.3H to monitor:

```text
retryable failures
quota failures
queue backlog implications
```

Do not implement full monitoring now.

---

# Step 193 — Rate Limit Preparation

Store:

```text
error_category = quota
```

where applicable.

Phase 2.3H can use this.

---

# Step 194 — Security Preparation

Opaque tracking IDs + stored redirects should give 2.3H a safe base.

---

# Step 195 — Required Validation

Run safe migrations:

```bash
php artisan migrate
php artisan migrate:status
```

Then targeted tests:

```bash
php artisan test tests/Feature/Push
```

Then:

```bash
php artisan test
```

Formatter:

```bash
./vendor/bin/pint
```

Frontend/service-worker changes expected, so run:

```bash
npm run build
```

Run existing JavaScript tests if configured.

---

# Step 196 — No Destructive Commands

Do NOT run:

```bash
php artisan migrate:fresh
```

Do not delete production-like data.

---

# Step 197 — Queue Validation

If delivery job changed, validate queue serialization.

Use queue fakes/tests.

---

# Step 198 — Secret Safety

Before completion:

```bash
git status --short
git diff --stat
```

Inspect changed files.

Ensure no:

```text
.env
Firebase private key
service-account JSON
OAuth bearer token
production FCM token
database dump
```

is introduced.

---

# Definition of Done

Phase 2.3G is complete only when:

- a unified tracking identity exists for manual and automatic notifications;
- automatic Post pushes have analytics records;
- manual notifications reuse existing records;
- per-recipient delivery records exist;
- one notification/subscription pair cannot duplicate delivery analytics;
- delivery jobs update attempt counts;
- FCM success stores accepted state/message ID;
- permanent failures are tracked;
- retryable failures are tracked;
- invalid tokens still deactivate subscriptions correctly;
- raw FCM tokens are not duplicated into analytics tables;
- notification click links use opaque tracking identifiers;
- click endpoint records clicks and redirects safely;
- open redirect is impossible;
- repeated clicks increment total count;
- unique click remains one per delivery;
- first/last click timestamps are tracked;
- CTR is calculated accurately;
- UI uses "FCM Accepted" or equivalent accurate wording;
- UI does not falsely claim verified delivery;
- manual notification analytics work;
- automatic Post analytics work;
- topic-targeted analytics work;
- existing historical notifications without analytics remain safe;
- Filament analytics UI exists;
- authorized access is enforced;
- analytics queries are efficient;
- delivery table is indexed appropriately;
- full-page/CDN cache does not cache tracking endpoint;
- automated tests make no real Firebase calls;
- migrations are additive and safe;
- documentation exists;
- Phase 2.3H queue/rate-limit work is not prematurely implemented.

---

# Expected File Shape

Actual project architecture should be followed.

Potential additions:

```text
app/
├── Models/
│   └── PushNotificationDelivery.php
│
├── Services/
│   └── Push/
│       ├── PushAnalyticsService.php
│       ├── PushTrackingUrlGenerator.php
│       └── PushDeliveryService.php
│
├── Http/
│   └── Controllers/
│       └── PushNotificationClickController.php
│
└── Filament/
    └── ...
```

Possible migration:

```text
create_push_notification_deliveries_table
```

Possible safe alteration:

```text
add_tracking_fields_to_push_notifications
```

Only create fields actually required.

---

# Required Completion Report

At completion provide:

## 1. Phase Summary

Explain analytics and click tracking implemented.

## 2. Existing Architecture Audit

Explain how Phase 2.3C–2.3F were reused.

## 3. Database

Report:

```text
tables
columns
indexes
unique constraints
foreign keys
```

## 4. Notification Tracking Identity

Explain how manual and automatic notifications share analytics architecture.

## 5. Delivery Lifecycle

Explain:

```text
queued
attempted
accepted
failed
retry
```

semantics.

## 6. FCM Accepted Semantics

Explicitly state:

```text
FCM Accepted does not guarantee device display.
```

## 7. Error Tracking

Explain stored categories and retryable behavior.

## 8. Click Tracking

Explain:

```text
notification
→ tracking URL
→ analytics record
→ redirect
```

## 9. Tracking Security

Confirm:

- opaque ID;
- no raw token;
- no user ID in URL;
- no open redirect.

## 10. Click Metrics

Explain:

```text
unique clicks
total clicks
first clicked
last clicked
```

## 11. CTR Formula

State exact formula.

## 12. Automatic Post Analytics

Explain integration.

## 13. Manual Notification Analytics

Explain integration.

## 14. Topic Targeting Analytics

Explain how unique audience maps to delivery records.

## 15. Filament UI

Report:

```text
list metrics
detail metrics
dashboard/widgets
failure breakdown
```

## 16. Authorization

List analytics permissions/roles.

## 17. Files Created

List every new file.

## 18. Files Modified

List every changed file and why.

## 19. Tests

Report:

```text
tests created
tests executed
passed
failed
```

## 20. Validation

Report:

```text
migrate
migrate:status
php artisan test
Pint
npm run build
JavaScript tests
```

## 21. Performance Review

Explain:

```text
chunking
indexes
aggregate queries
N+1 protection
```

## 22. Cache Review

Confirm click tracking route is not cached.

## 23. Privacy Review

Confirm no fingerprinting/raw token analytics.

## 24. Historical Data

Explain behavior for notifications sent before Phase 2.3G.

## 25. Phase 2.3H Readiness

Explain what queue/security/rate-limit information is now available for hardening.

## 26. Scope Verification

Explicitly confirm these were NOT implemented:

```text
advanced throttling
queue autoscaling
A/B testing
geographic analytics
behavioral profiling
AI targeting
conversion/revenue attribution
```

## 27. Final Status

Finish exactly with one:

`PHASE 2.3G COMPLETE`

or:

`PHASE 2.3G BLOCKED`

---

# Final Instruction

Fully implement:

# Phase 2.3G — Push Analytics & Click Tracking

Do not merely audit the repository.

Inspect and reuse the actual completed Phase 2.3A–2.3F implementation.

Do not build another Firebase transport.

Do not build separate analytics systems for manual and automatic notifications.

The final architecture should converge on:

```text
PushNotification
      ↓
Unique Audience
      ↓
PushNotificationDelivery
      ↓
Existing Queue / Firebase Engine
      ↓
FCM Accepted or Failed
      ↓
Opaque Tracking URL
      ↓
Click
      ↓
Analytics
      ↓
Filament
```

Use precise terminology.

Do NOT label Firebase acceptance as verified browser delivery.

Protect click redirects from open-redirect vulnerabilities.

Do not expose raw tokens or user identities in tracking URLs.

Run all relevant migrations, tests, build and formatting.

Fix regressions introduced by this phase.

Do not begin Phase 2.3H.

Provide the full required completion report.

End exactly with:

`PHASE 2.3G COMPLETE`

or:

`PHASE 2.3G BLOCKED`