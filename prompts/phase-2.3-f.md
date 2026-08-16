# Phase 2.3F — Topics & Category Preferences

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
- Phase 2.3D — Post Publish Automation
- Phase 2.3E — Filament Push Notification Panel

This phase continues:

# Version 2.3 — Push Notification System

---

# Phase Objective

Implement subscriber notification preferences and reusable audience targeting based on news topics/categories.

Current architecture supports:

```text
Subscriber
   ↓
FCM subscription
   ↓
PushSubscription
```

and manual/automatic push currently targets:

```text
All Active Subscribers
```

Phase 2.3F must extend this to:

```text
Subscriber
   ↓
Choose Interests
   ↓
Punjab
Politics
Sports
Business
Entertainment
Breaking News
etc.
   ↓
Preference Storage
   ↓
Audience Resolver
   ↓
PushNotificationService
   ↓
Selected Active Subscribers
```

The architecture must remain reusable for future NewsMan installations.

---

# Primary Goals

Implement:

1. reusable push topic/category architecture;
2. subscriber preference persistence;
3. default subscription behavior;
4. category-based preferences;
5. special system topics such as Breaking News;
6. frontend notification preference UI;
7. guest preference support;
8. authenticated user support;
9. synchronization with existing PushSubscription records;
10. reusable audience resolver;
11. automatic post targeting integration;
12. manual Filament targeting integration;
13. efficient database querying;
14. duplicate-safe recipient resolution;
15. optional FCM topic synchronization only if it cleanly fits current architecture;
16. validation and security;
17. automated tests;
18. documentation.

---

# Strict Scope Boundary

Phase 2.3F is:

# Topics + Category Preferences + Audience Targeting

Do NOT implement:

- click analytics;
- notification open tracking;
- CTR;
- delivery analytics dashboard;
- advanced campaign analytics;
- production throttling;
- sophisticated rate limiting;
- A/B testing;
- geographic targeting;
- behavioral segmentation;
- AI notification targeting;
- recurring campaigns.

Future phases:

- 2.3G — Analytics & Click Tracking
- 2.3H — Queue, Security & Rate Limiting
- 2.3I — Testing & Production Deployment

---

# Critical First Step — Audit Existing Project

Before changing code, inspect:

```text
app/Models/Category.php
app/Models/Post.php
app/Models/PushSubscription.php
app/Models/PushNotification.php
app/Services/Push/
app/Filament/
resources/js/push/
resources/views/
routes/
database/migrations/
tests/Feature/Push/
```

Also inspect the actual implementations from Phase 2.3A through Phase 2.3E.

Do not assume previous prompt file names exactly match implementation.

---

# Audit Existing Taxonomy Architecture

Inspect:

```text
categories
post/category relationships
category slug
category active/public state
parent/child categories
imported WordPress categories
```

Do not create a second category system.

Push preferences must reuse the existing Category model.

---

# Protected Existing Functionality

Do not break:

- categories;
- posts;
- tags;
- imported WordPress taxonomy;
- homepage;
- article pages;
- SEO;
- publishing workflow;
- Phase 2.3D auto-push;
- Phase 2.3E manual push panel;
- PushSubscription lifecycle;
- Redis queues;
- media;
- ads;
- Filament permissions;
- caching.

---

# Architecture Principle

Do not hardcode category names directly into push engine logic.

Bad:

```php
if ($category === 'sports') { ... }
```

Preferred:

```text
Category
   ↓
Push Topic Mapping
   ↓
Preference
   ↓
Audience Resolver
```

---

# Target Architecture

```text
PushSubscription
      │
      ├── Topic: breaking-news
      ├── Category: Punjab
      ├── Category: Sports
      └── Category: Business

            ↓

PushAudienceResolver

            ↓

Active matching subscriptions

            ↓

PushNotificationService
```

---

# Step 1 — Decide Preference Data Model

Inspect existing schema first.

Preferred architecture should avoid columns like:

```text
sports = true
politics = true
business = true
```

on `push_subscriptions`.

Use relational records.

---

# Step 2 — Push Topics

Create a reusable topic abstraction if necessary.

Possible table:

```text
push_topics
```

Suggested fields:

```text
id
name
slug
type
category_id nullable
is_active
is_default
sort_order
created_at
updated_at
```

Possible types:

```text
category
system
```

Examples:

```text
Punjab
Sports
Politics
Business
Entertainment
Breaking News
```

---

# Step 3 — Category-Backed Topics

Category topics should reference the existing Category model.

Example:

```text
push_topics.category_id
```

Do not duplicate category names/content unnecessarily.

A topic may have:

```text
name
slug
```

for independent display/configuration, but category remains source of taxonomy truth.

---

# Step 4 — System Topics

Support non-category topics such as:

```text
breaking-news
all-news
important-alerts
```

Do not force every topic to belong to a Category.

For this phase, keep system topics minimal.

Recommended:

```text
breaking-news
```

and possibly:

```text
all-news
```

only if architecture needs it.

---

# Step 5 — Preference Pivot

Create relational preference storage.

Possible table:

```text
push_subscription_topic
```

or:

```text
push_subscription_topics
```

Suggested fields:

```text
push_subscription_id
push_topic_id
created_at
```

Use suitable unique constraint:

```text
UNIQUE(push_subscription_id, push_topic_id)
```

---

# Step 6 — No Duplicate Preferences

A subscriber must not have duplicate rows for the same topic.

Database constraint should guarantee this.

---

# Step 7 — Cascade Behavior

If PushSubscription is removed:

its preferences may cascade safely.

If PushTopic is removed:

its preference rows may cascade.

Use existing database conventions.

---

# Step 8 — Category Deletion

If Category is deleted, decide safe behavior for the linked PushTopic.

Preferred:

```text
category_id → null
```

or deactivate topic rather than causing unrelated subscription failure.

Inspect Category deletion rules first.

---

# Step 9 — Topic Model

Create:

```text
PushTopic
```

if no equivalent exists.

Relationships:

```text
PushTopic
belongsTo Category nullable

PushTopic
belongsToMany PushSubscription
```

---

# Step 10 — PushSubscription Relationship

Add:

```php
public function topics()
```

or equivalent.

Do not alter existing FCM lifecycle behavior.

---

# Step 11 — Category Relationship

Where useful:

```text
Category
hasOne/hasMany PushTopic
```

but add only if this improves application logic.

Do not pollute Category model excessively.

---

# Step 12 — Topic Source Strategy

Determine how Category topics are created.

Preferred options:

### Option A

Automatically create PushTopic when an eligible Category needs notification targeting.

### Option B

Create topics through an idempotent sync command/service.

Recommended:

Use a reusable sync service rather than coupling taxonomy creation directly to every Category save unless current architecture strongly supports observers.

---

# Step 13 — Topic Sync Service

Create something like:

```text
PushTopicSyncService
```

Responsibilities:

```text
existing public categories
→ matching push category topics
→ update names/slugs
→ deactivate obsolete mapping safely
```

---

# Step 14 — Topic Sync Artisan Command

If useful, create:

```bash
php artisan push:sync-topics
```

This is strongly recommended.

It should:

- inspect categories;
- create missing category topics;
- update mappings;
- not delete subscriber preferences unnecessarily;
- be idempotent.

---

# Step 15 — Imported Categories

Existing WordPress-imported categories must be supported.

Running topic sync should safely create corresponding push topics.

Do not modify imported Category IDs/slugs.

---

# Step 16 — Which Categories Become Topics?

Do not automatically expose every technical/internal category if the system contains hidden categories.

Inspect fields such as:

```text
is_active
status
visibility
```

Use only appropriate public categories.

If no such fields exist, use all current normal categories unless project conventions indicate otherwise.

---

# Step 17 — Default Topics

Define default opt-in behavior carefully.

Recommended baseline:

```text
Breaking News = enabled by default after notification opt-in
General/All News = optional
Category topics = user choice
```

However do not silently subscribe users to dozens of categories.

---

# Step 18 — Existing Subscribers Migration

Existing PushSubscriptions currently have no topic preferences.

Do NOT suddenly make them receive zero notifications.

Backward compatibility is critical.

Choose and document a compatibility rule.

Recommended:

```text
No topic preferences
=
legacy/all-news recipient
```

until user explicitly configures preferences.

---

# Step 19 — Legacy Audience Rule

This is important.

Existing users who subscribed before Phase 2.3F should continue receiving normal news pushes.

Possible rule:

```text
preferences_configured = false
→ receive default/all notifications
```

Once user saves preferences:

```text
preferences_configured = true
→ honor selected topics
```

---

# Step 20 — Preference Configuration State

To distinguish:

```text
never configured
```

from:

```text
configured with zero selected categories
```

you may need a field on PushSubscription such as:

```text
preferences_configured_at
```

or:

```text
preferences_configured boolean
```

Choose a safe approach.

---

# Step 21 — Safe Additive Migration

If needed, add:

```text
preferences_configured_at nullable
```

to `push_subscriptions`.

This allows:

```text
null
→ legacy/default behavior

timestamp
→ explicit user preferences exist
```

Prefer this over ambiguous empty pivot interpretation.

---

# Step 22 — Preference Semantics

Define explicitly:

### No configured preference

Subscriber receives default/global notifications.

### Configured preferences

Subscriber receives only selected relevant topic notifications, plus mandatory system behavior if defined.

### Zero selected topics

Decide whether this means:

```text
no category notifications
```

while Breaking News can remain separately selectable.

Do not silently revert to all-news after explicit zero selection.

---

# Step 23 — Browser Preference UI

Extend the existing push notification frontend.

After notification permission/subscription is active, offer:

```text
Choose News Alerts
```

or equivalent.

---

# Step 24 — UI Example

```text
🔔 Notification Preferences

☑ Breaking News
☑ Punjab
☐ National
☑ Sports
☐ Entertainment
☑ Business

[Save Preferences]
```

Use actual available categories dynamically.

---

# Step 25 — Dynamic Categories

Never hardcode frontend category list.

Fetch/render active PushTopics.

---

# Step 26 — Preference UI Location

Inspect existing frontend.

Good locations may include:

```text
push opt-in component
footer notification area
dedicated preferences modal/card
account page if logged in
```

Choose a compact implementation.

Do not redesign the website.

---

# Step 27 — Guest Support

Guest subscriber must be able to manage preferences.

Use existing browser/device/Firebase subscription identity from Phase 2.3B.

Do not require user login.

---

# Step 28 — Authenticated Support

Logged-in users should also manage the preferences of their current browser subscription.

Do not automatically apply one device's preferences to all devices unless explicitly designed.

Recommended:

Preferences belong to `PushSubscription`.

---

# Step 29 — Per-Device Preferences

Default architecture:

```text
one PushSubscription
=
one browser/device token
=
its own preferences
```

This supports:

```text
phone → Breaking + Punjab
desktop → All categories
```

without conflict.

---

# Step 30 — Future Account-Wide Settings

Do not build account-wide preference synchronization yet.

Keep per-subscription behavior.

---

# Step 31 — Preference API

Create routes consistent with current push architecture.

Possible:

```text
GET /push/preferences
PUT /push/preferences
```

or equivalent.

Use current project route conventions.

---

# Step 32 — Subscription Identification

Do not let users edit arbitrary subscription IDs.

Identify current subscription using existing:

```text
FCM token
token hash
device UUID
authenticated relationship
```

architecture securely.

---

# Step 33 — Do Not Put Token in URL

Never:

```text
/push/preferences/{fcm-token}
```

Use request body/secure state.

---

# Step 34 — Preference Request Validation

Validate:

```text
topic IDs
array shape
topic existence
active topics
```

Do not accept arbitrary strings as topic IDs.

---

# Step 35 — Ignore Client User ID

As in Phase 2.3B:

do not trust:

```text
user_id
```

from browser.

---

# Step 36 — Preference Service

Create:

```text
PushPreferenceService
```

or equivalent.

Responsibilities:

```text
resolve current subscription
validate allowed topics
sync preferences
mark preferences configured
return normalized preference state
```

---

# Step 37 — Database Transaction

Use transaction when syncing topics and marking configuration state.

Expected:

```text
sync topic pivot
+
preferences_configured_at
```

should remain consistent.

---

# Step 38 — Sync Semantics

Use:

```text
sync()
```

or equivalent safely.

Do not create duplicate pivot rows.

---

# Step 39 — Active Topics Only

Users should not newly subscribe to inactive topics.

If a previously selected topic becomes inactive, audience resolver should ignore it.

---

# Step 40 — Breaking News Topic

Create/ensure a system topic:

```text
breaking-news
```

if existing Post model supports a breaking-news flag.

---

# Step 41 — Existing Breaking News Field

Inspect actual Post model for:

```text
is_breaking
breaking
breaking_news
```

Reuse the real field.

Do not create a duplicate breaking-news field.

---

# Step 42 — Post Topic Resolution

Create a resolver/factory that determines topics relevant to a Post.

Possible service:

```text
PostPushTopicResolver
```

Input:

```text
Post
```

Output:

```text
collection of PushTopic IDs/slugs
```

---

# Step 43 — Category Mapping

For published Post:

```text
Post categories
→ matching PushTopics
```

If Post belongs to multiple categories:

all matching topic subscribers may qualify.

---

# Step 44 — Duplicate Recipient Prevention

A subscriber selecting:

```text
Punjab
Sports
Breaking News
```

must still receive only one push for a Post matching all three.

Recipient query must deduplicate subscriptions.

---

# Step 45 — Audience Resolver

Create reusable:

```text
PushAudienceResolver
```

or equivalent.

Responsibilities:

```text
all active
topic-based
post-based
manual selected topics
legacy subscribers
```

Do not put targeting SQL inside Filament or listeners.

---

# Step 46 — Generic Audience Contract

The push engine should accept an audience/query abstraction where practical.

Possible use:

```text
$audienceResolver->forPost($post)
```

and:

```text
$audienceResolver->forTopics($topics)
```

---

# Step 47 — All Active Audience

Preserve existing ability:

```text
forAllActive()
```

or equivalent.

Needed by manual alerts and future system announcements.

---

# Step 48 — Legacy Subscribers

For category-targeted normal post notification, determine whether legacy subscribers without configured preferences still receive it.

Recommended:

Yes.

This preserves behavior until the user explicitly configures preferences.

---

# Step 49 — Explicit Preferences

Once `preferences_configured_at` exists:

match selected Post topics.

Do not include them in unrelated categories.

---

# Step 50 — Breaking News Behavior

Recommended:

A breaking post should target:

```text
legacy subscribers
+
explicit subscribers to Breaking News
+
optionally category subscribers if notification is also treated as category news
```

Choose clear semantics and document.

---

# Step 51 — Avoid Double Broadcast

Do not run:

```text
broadcast to breaking topic
+
broadcast to Punjab topic
```

as separate independent campaigns if that can duplicate recipients.

Resolve audience once, then deduplicate.

---

# Step 52 — Phase 2.3D Integration

Modify automatic post-publish flow.

Current:

```text
Post Published
→ All Active Subscribers
```

New:

```text
Post Published
→ Resolve Post Topics
→ Resolve Audience
→ Existing PushNotificationService
```

---

# Step 53 — No Transport Changes

Do not change Firebase HTTP v1 delivery transport unless necessary.

Targeting belongs before delivery.

---

# Step 54 — Auto-Publish Fallback

If Post has no mapped category topic:

Fallback safely.

Recommended:

```text
legacy/default audience
```

rather than silently notifying nobody.

Document exact behavior.

---

# Step 55 — Manual Filament Targeting

Extend Phase 2.3E manual composer.

Currently:

```text
Target: All Active Subscribers
```

Phase 2.3F should support:

```text
All Active Subscribers
Selected Topics
```

---

# Step 56 — Manual Target Selector

Example:

```text
Target Audience

(•) All Active Subscribers
( ) Selected Topics
```

If selected topics:

```text
☑ Breaking News
☑ Punjab
☐ Sports
```

---

# Step 57 — No User-Level Segments

Do not add:

```text
Guest
Logged-in
Android
Chrome
location
```

targeting.

Only topics/categories.

---

# Step 58 — Manual Topic Validation

At least one topic required when:

```text
Selected Topics
```

is chosen.

---

# Step 59 — Campaign Snapshot

Manual PushNotification record must store selected targeting as a snapshot.

Do not depend solely on current UI state.

Possible:

```text
target_type
```

values:

```text
all
topics
```

---

# Step 60 — Manual Campaign Topic Pivot

If manual notification needs topic snapshot persistence, create a pivot such as:

```text
push_notification_topic
```

only if it is the cleanest design.

---

# Step 61 — Topic Snapshot vs Foreign Key

Using topic IDs is acceptable.

If topic renamed later, historical campaign may show current name unless snapshot stored.

Full historical analytics is not required yet.

Do not over-engineer.

---

# Step 62 — Recipient Count

For manual selected topics:

```text
recipient_count
```

must reflect unique matching active subscriptions at dispatch initiation.

---

# Step 63 — Preview

Filament preview should show:

```text
Target: Punjab, Sports
Estimated Active Subscribers: 1,284
```

Use current DB count.

Do not claim exact delivery count.

---

# Step 64 — Efficient Counts

Use SQL count with joins/subqueries.

Do not load matching subscribers into PHP.

---

# Step 65 — Efficient Delivery Query

Audience resolver should return query/lazy source.

Do not:

```php
->get()
```

100k subscriptions before queue fan-out.

---

# Step 66 — Indexing

Add appropriate indexes to preference tables.

Likely:

```text
push_subscription_id
push_topic_id
unique pair
```

Potential topic fields:

```text
slug unique
category_id indexed
type
is_active
```

---

# Step 67 — Query Performance

Post-topic audience queries should remain practical at large subscriber counts.

Avoid N+1.

---

# Step 68 — NewsMan Reusability

Do not hardcode Daily Samvad categories.

Core architecture must work when another NewsMan installation has different categories.

---

# Step 69 — Display Labels

Brand-specific UI strings may use Daily Samvad.

Topic data must remain dynamic.

---

# Step 70 — FCM Native Topics — Important Decision

Evaluate whether to synchronize local preferences with Firebase native FCM topics.

Do not assume native FCM topics are automatically the best architecture.

The local database must remain the source of truth for preferences.

---

# Step 71 — Recommended Architecture

Recommended for Phase 2.3F:

```text
Laravel DB preferences
=
authoritative source

Laravel Audience Resolver
=
recipient targeting

FCM individual tokens
=
delivery
```

This gives:

- exact control;
- compatibility with analytics later;
- simpler deactivation;
- easier multi-topic deduplication;
- easier subscriber auditing.

---

# Step 72 — Native FCM Topic Synchronization

Only implement native Firebase topic subscribe/unsubscribe if:

1. Phase 2.3C infrastructure already supports it cleanly;
2. it does not require major new SDK architecture;
3. it does not compromise local source-of-truth semantics.

Otherwise explicitly defer native FCM topics.

---

# Step 73 — Do Not Block Phase

Phase 2.3F must NOT be marked blocked merely because native FCM topics are intentionally deferred.

The requirement is functional topic/category targeting.

Laravel-level targeting is valid.

---

# Step 74 — FCM Topic Naming

If native FCM topics are implemented:

Generate safe provider topic keys independent of user-facing names.

Example:

```text
category_12
breaking_news
```

Do not directly use arbitrary Unicode category labels as provider identifiers.

---

# Step 75 — Provider Topic Mapping

Store provider topic key where needed.

Do not use it as application identity.

---

# Step 76 — Native Topic Failure

If Firebase topic synchronization fails:

do not lose local preferences.

Local DB remains authoritative.

---

# Step 77 — Preference Save UX

On successful save:

```text
✓ Notification preferences saved
```

Use existing frontend style.

---

# Step 78 — Failure UX

If preference save fails:

```text
Could not save notification preferences. Please try again.
```

Do not show database/Firebase internals.

---

# Step 79 — Loading State

Disable Save during request.

Avoid duplicate submissions.

---

# Step 80 — Existing Permission Denied

If browser notification permission is denied:

Do not show active topic checkboxes as if they can function.

Show guidance to enable notifications first.

---

# Step 81 — Push Not Configured

If Firebase config missing:

preferences UI should gracefully hide/disable.

---

# Step 82 — Subscription Missing

If permission is granted but backend PushSubscription sync has not succeeded:

attempt normal subscription synchronization before saving preferences.

Do not create orphan preference rows.

---

# Step 83 — Current Preference Fetch

When preference UI opens:

fetch current topics and selected state.

Avoid exposing raw token.

---

# Step 84 — Response Contract

Possible GET response:

```json
{
  "configured": true,
  "topics": [
    {
      "id": 1,
      "name": "Punjab",
      "selected": true
    }
  ]
}
```

Use actual application conventions.

---

# Step 85 — Do Not Expose Sensitive Subscription Data

Preference endpoint should not return:

```text
FCM token
token hash
IP
user agent
internal metadata
```

---

# Step 86 — Public Topic Listing

Topic names/slugs are non-sensitive.

Return only fields required by UI.

---

# Step 87 — CSRF

If using web routes:

keep normal CSRF protection.

Do not globally exclude preference routes.

---

# Step 88 — Rate Limits

Advanced rate limiting belongs to 2.3H.

However normal application protections should remain.

Do not add aggressive polling.

---

# Step 89 — Preference Cache

Do not cache user/device-specific preference response globally.

Avoid full-page cache contamination.

---

# Step 90 — Full-Page Cache Awareness

If push UI appears on cached pages, client-side preference state must remain device-specific.

Do not render personalized topic selection into globally cached HTML.

---

# Step 91 — Recommended Cached Page Strategy

Render generic component shell.

Fetch device-specific preferences through uncached endpoint after page load.

Or use equivalent existing safe approach.

---

# Step 92 — Do Not Leak Preferences

One visitor's selected topics must never appear to another visitor due to page caching.

This is critical.

---

# Step 93 — Service Worker Independence

Preference UI does not need service-worker rewrite unless native FCM topics require something unusual.

Avoid unnecessary changes.

---

# Step 94 — Post Categories

If Post belongs to categories through pivot, ensure eager/query logic uses actual relationship.

Do not infer from URL slug.

---

# Step 95 — Parent Categories

Determine whether selecting parent category should receive child-category posts.

Do not assume.

Recommended initial behavior:

```text
exact category topic matching
```

unless existing taxonomy semantics clearly require hierarchy.

---

# Step 96 — Hierarchical Future Expansion

Document parent/child recursive targeting as future enhancement if not implemented.

Avoid unexpected broad targeting.

---

# Step 97 — Multi-Category Posts

A Post in Punjab + Politics should match subscribers to either.

Use OR semantics.

---

# Step 98 — Subscriber With Multiple Matching Topics

Deliver once.

---

# Step 99 — No Preference Subscriber

Legacy/default behavior applies.

---

# Step 100 — Explicit Unsubscribed Categories

If user selects only Sports:

Punjab-only post should not be sent to that subscriber unless it is covered by a selected system topic such as Breaking News.

---

# Step 101 — Breaking Post

Define exact combination clearly.

Recommended:

```text
Breaking News subscriber receives breaking post
Category subscriber may also receive it if category matches
Legacy subscriber receives it
Unique final subscription set
```

---

# Step 102 — Manual All Audience

Manual:

```text
All Active Subscribers
```

should truly target all active subscriptions regardless of preferences.

This is useful for:

```text
critical site announcement
major breaking alert
system message
```

---

# Step 103 — Manual Selected Topics

Only matching explicit-topic subscribers plus any deliberate legacy behavior.

Decide whether legacy subscribers receive targeted manual topic campaigns.

Recommended:

No.

Reason:

Manual targeted campaign should honor explicit targeting.

Document this difference.

---

# Step 104 — Automatic Legacy Compatibility

Automatic category Post pushes can include legacy subscribers for backward compatibility.

Manual selected-topic campaigns should target actual selected-topic audience.

This distinction is recommended.

---

# Step 105 — Topic Activation

Admin should have a way to control whether a topic appears to subscribers.

Do not necessarily build a full new management resource if categories already control this.

---

# Step 106 — System Topic Management

Breaking News may need configuration.

Possible:

```text
config or seeded PushTopic
```

Do not require admin CRUD unless beneficial.

---

# Step 107 — Topic Seeder

If system topics need seeding:

make it idempotent.

Do not wipe user preference data.

---

# Step 108 — Category Topic Sync Safety

Sync command must not delete preference rows simply because category name changed.

Keep identity through category_id/topic ID.

---

# Step 109 — Category Rename

If:

```text
Sports
→ खेल
```

topic mapping should update display name without losing preferences.

---

# Step 110 — Category Slug Change

Do not use slug as sole relationship identity.

Prefer stable category_id.

---

# Step 111 — Category Deactivation

Deactivate corresponding PushTopic.

Do not silently delete user preference row unless necessary.

Audience resolver ignores inactive topics.

---

# Step 112 — Category Reactivation

Existing topic can reactivate, preserving preference links.

---

# Step 113 — Topic Deletion

Avoid hard deleting unless category itself permanently removed and policy requires cleanup.

Deactivation is safer.

---

# Step 114 — Preference Analytics

Do NOT build:

```text
most popular topic
subscriber trends
preference charts
```

yet.

Those can come later.

---

# Step 115 — Minimal Filament Topic Visibility

If operationally useful, Phase 2.3E panel may display target topic names.

Do not build a large analytics screen.

---

# Step 116 — Optional Topic Management Resource

Only create a PushTopic Filament resource if categories/system topics cannot be safely managed otherwise.

If created:

restrict it to administrative configuration.

Do not expose destructive operations casually.

---

# Step 117 — Prefer Category Reuse

For category topics, avoid manual duplicate administration.

---

# Step 118 — Automatic Push Message Content

Phase 2.3D message content stays unchanged.

Phase 2.3F changes audience selection, not PostPushMessageFactory content.

---

# Step 119 — Manual Message Content

Phase 2.3E manual message composer remains intact.

Phase 2.3F extends target selection only.

---

# Step 120 — No Firebase Transport Duplication

Never create another:

```text
FirebaseMessagingClient
```

for topic targeting.

---

# Step 121 — Tests: Topic Sync

Test:

```text
existing categories
→ push topics created
```

---

# Step 122 — Tests: Topic Sync Idempotency

Run twice.

Expected:

```text
same number of topics
no duplicates
```

---

# Step 123 — Tests: Category Rename

Preference survives topic display update.

---

# Step 124 — Tests: Guest Preference Save

Guest PushSubscription can save topics.

---

# Step 125 — Tests: Authenticated Preference Save

Authenticated current subscription can save topics.

---

# Step 126 — Tests: Invalid Topic ID

Reject.

---

# Step 127 — Tests: Inactive Topic

Cannot newly subscribe.

---

# Step 128 — Tests: Duplicate Topic IDs

Input duplicates.

Database ends with one pivot per topic.

---

# Step 129 — Tests: Preferences Configured State

Before first save:

```text
legacy/default behavior
```

After save:

```text
explicit behavior
```

---

# Step 130 — Tests: Zero Explicit Topics

Saving empty list should mark preferences configured.

Do not revert to legacy-all behavior.

---

# Step 131 — Tests: Legacy Audience

Existing PushSubscription with no configured preferences:

normal published post → included.

---

# Step 132 — Tests: Selected Matching Category

Subscriber selects Sports.

Sports Post → included.

---

# Step 133 — Tests: Non-Matching Category

Sports subscriber.

Punjab-only Post → excluded.

---

# Step 134 — Tests: Multi-Category Post

Punjab + Politics.

Subscriber to either → included.

---

# Step 135 — Tests: Duplicate Matching Topics

Subscriber selects Punjab + Politics.

Post has both.

Expected:

```text
one recipient
```

---

# Step 136 — Tests: Breaking News

Verify selected Breaking News subscriber receives breaking Post.

---

# Step 137 — Tests: Non-Breaking

Breaking-only subscriber should not receive ordinary unrelated Post.

---

# Step 138 — Tests: Auto Publish Integration

Phase 2.3D first publish now resolves correct audience.

Do not call real Firebase.

---

# Step 139 — Tests: Manual All

Filament manual target:

```text
all
```

includes all active subscriptions.

---

# Step 140 — Tests: Manual Selected Topics

Includes only matching subscriptions.

---

# Step 141 — Tests: Manual Topic Deduplication

One matching subscriber receives one job.

---

# Step 142 — Tests: Recipient Count

Manual preview count = unique active matching subscriptions.

---

# Step 143 — Tests: Inactive Subscription

Never included regardless of topic.

---

# Step 144 — Tests: Inactive Topic

Should not create current audience match.

---

# Step 145 — Tests: Preference Endpoint Security

Cannot manipulate another subscription by arbitrary ID/user ID.

---

# Step 146 — Tests: No Token Exposure

Response does not contain FCM token.

---

# Step 147 — Tests: Cache Isolation

Where practical ensure preference endpoints are not globally cached.

---

# Step 148 — Tests Must Stay Offline

No real Firebase/Google request.

---

# Step 149 — Factory Updates

Add/update:

```text
PushTopicFactory
```

and pivot helpers where useful.

---

# Step 150 — No Production Fake Seeder

Only system topics/category sync should affect production.

---

# Step 151 — Migration Safety

All migrations must be additive.

Do not destructively alter:

```text
posts
categories
users
push_subscriptions
push_notifications
```

---

# Step 152 — No migrate:fresh

Never run:

```bash
php artisan migrate:fresh
```

on current project data.

---

# Step 153 — Large Existing Dataset

Category sync must handle existing taxonomy efficiently.

---

# Step 154 — No Subscriber Backfill Explosion

Do not generate topic rows for every subscriber automatically unless necessary.

Legacy behavior avoids huge unnecessary backfill.

---

# Step 155 — Preference Row Creation

Create pivot rows only when user explicitly configures preferences.

---

# Step 156 — Topic Count

Usually low relative to subscriptions.

No need for complex caching unless existing architecture benefits.

---

# Step 157 — Audience Query Indexes

Confirm database query plan can use topic/subscription indexes effectively.

Do not add excessive indexes.

---

# Step 158 — Queue Scalability

Audience Resolver should plug into Phase 2.3C lazy/chunked queue fan-out.

---

# Step 159 — No Arrays of 100k IDs

Do not resolve matching audience into a giant PHP ID array.

---

# Step 160 — Query / Lazy Contract

Prefer something like:

```text
Builder
LazyCollection
chunked callback
```

compatible with existing delivery engine.

---

# Step 161 — Concurrency

Saving preferences while a campaign is being queued may produce normal race behavior.

Do not over-engineer transaction isolation.

Audience is resolved at dispatch time.

Document if necessary.

---

# Step 162 — Manual Campaign Snapshot Audience

Recipient list itself does not need to be permanently snapshotted yet.

`recipient_count` is operational.

Per-recipient history belongs to 2.3G.

---

# Step 163 — User Revokes Permission

Inactive subscription remains excluded automatically.

No special topic cleanup required.

---

# Step 164 — Reactivation

When subscription reactivates, existing preferences may remain.

Recommended:

preserve selected topics.

---

# Step 165 — New FCM Token Same Device

Inspect Phase 2.3B token rotation behavior.

If a PushSubscription record is replaced rather than updated, preserve preferences where appropriate.

Do not lose user choices during token refresh.

---

# Step 166 — Token Rotation Requirement

This is important.

Same logical device:

```text
old token
→ new FCM token
```

should retain preferences if Phase 2.3B identifies the device through device UUID.

Implement safe preference transfer if required.

---

# Step 167 — Account Switching

Preferences belong to subscription/device, not user.

Account switch should not unexpectedly reset them unless Phase 2.3B creates a new subscription.

Document current behavior.

---

# Step 168 — Logout

Guest mode on same browser should retain preferences associated with that subscription.

---

# Step 169 — Privacy

Topic choices are lightweight preferences.

Do not collect additional personal data.

---

# Step 170 — Logs

Do not log complete:

```text
FCM token
topic preference payload tied to sensitive metadata
```

Normal safe debugging can use subscription ID/topic IDs.

---

# Step 171 — No Topic Name in Credential Logic

Keep provider authentication separate.

---

# Step 172 — Frontend JavaScript Module

Likely add:

```text
resources/js/push/preferences.js
```

Responsibilities:

```text
fetchTopics()
loadPreferences()
savePreferences()
renderPreferenceState()
```

Reuse existing push client architecture.

---

# Step 173 — Do Not Put Everything in app.js

Keep modular architecture.

---

# Step 174 — Frontend Error Isolation

Preference failure must not break push opt-in or website JavaScript.

---

# Step 175 — Accessibility

Topic checkboxes:

- real checkboxes;
- visible labels;
- keyboard accessible;
- focus states;
- accessible save status.

---

# Step 176 — Mobile UI

Preferences must fit narrow screen without horizontal scroll.

---

# Step 177 — Collapse UI

If many categories exist, a collapsible preference card/modal is acceptable.

Do not show 50 checkboxes permanently in footer.

---

# Step 178 — Topic Limit

If taxonomy has very many categories, decide a practical display strategy.

Possible:

```text
top-level categories only
```

or:

```text
explicitly push-enabled categories
```

Inspect actual taxonomy.

Do not arbitrarily display every child category if UX becomes unusable.

---

# Step 179 — Push-Enabled Flag

If needed, `push_topics.is_active` provides control without altering Category table.

---

# Step 180 — Sort Order

Topics should have deterministic display order.

Use:

```text
sort_order
name
```

or project-consistent logic.

---

# Step 181 — System Topic First

Breaking News may appear first.

---

# Step 182 — Topic Labels

Use Category's current display name.

Support Hindi/Punjabi/English Unicode.

---

# Step 183 — No Translation Engine

Do not add translation infrastructure.

---

# Step 184 — Filament Target UX

Use searchable checkbox/select component according to existing Filament version.

Do not load huge data unnecessarily.

Topic set should normally be small.

---

# Step 185 — Target Type Persistence

If PushNotification model gets:

```text
target_type
```

validate allowed values:

```text
all
topics
```

---

# Step 186 — Existing Drafts

Existing Phase 2.3E drafts without target_type should remain valid.

Use safe migration defaults:

```text
all
```

---

# Step 187 — Existing Sent Notifications

Do not alter historical send meaning.

---

# Step 188 — Status Lifecycle

Topic targeting must not change:

```text
draft
queued
sent
failed
```

semantics.

---

# Step 189 — Duplicate Send Protection

Phase 2.3E idempotency must remain intact.

---

# Step 190 — Audience Count Before Send

Recalculate recipient count at confirmation/send time.

Do not trust a stale count displayed minutes earlier.

---

# Step 191 — Preview Count

Preview count is informational.

Send-time count is authoritative for `recipient_count`.

---

# Step 192 — Zero Selected Audience

If selected topics resolve to zero active subscribers:

prevent send or clearly confirm zero-target no-op.

Recommended:

prevent send.

---

# Step 193 — Topic With No Subscribers

Do not error when displaying it.

Count = 0.

---

# Step 194 — Auto Publish No Matching Explicit Subscribers

Legacy/default users may still receive according to compatibility rule.

If no recipients at all:

safe no-op.

Post publication succeeds.

---

# Step 195 — No Push Failure Effect on Publishing

Maintain Phase 2.3D side-effect isolation.

---

# Step 196 — Documentation

Create/update:

```text
docs/push-notifications/topics-and-preferences.md
```

---

# Step 197 — Documentation Contents

Document:

1. PushTopic architecture;
2. Category mapping;
3. system topics;
4. preference pivot;
5. legacy subscriber behavior;
6. explicit preferences;
7. per-device behavior;
8. topic sync command;
9. automatic Post targeting;
10. manual Filament targeting;
11. deduplication;
12. token rotation preference preservation;
13. cache/privacy considerations;
14. FCM native-topic decision.

---

# Step 198 — Operator Docs

Document:

```bash
php artisan push:sync-topics
```

if implemented.

Explain when it should run.

---

# Step 199 — Deployment Workflow

Recommended:

```text
1. deploy migrations
2. run topic sync
3. inspect generated topics
4. run tests
5. verify frontend preference UI
6. test one guest subscription
7. test one logged-in subscription
8. verify targeted manual notification
9. verify category Post auto-push
```

---

# Step 200 — No Existing Subscriber Disruption

This is mandatory.

After deployment, existing PushSubscriptions must continue receiving notifications under documented legacy/default behavior.

---

# Step 201 — Required Validation Commands

Run applicable commands:

```bash
php artisan migrate
```

only for safe pending migrations.

Then:

```bash
php artisan migrate:status
```

If sync command exists:

```bash
php artisan push:sync-topics
```

Then:

```bash
php artisan test tests/Feature/Push
```

and:

```bash
php artisan test
```

Run formatter:

```bash
./vendor/bin/pint
```

Since frontend JavaScript/UI changes are expected:

```bash
npm run build
```

Run existing JavaScript tests if configured.

---

# Step 202 — No Destructive Commands

Do NOT run:

```bash
php artisan migrate:fresh
```

Do not delete subscriber data.

---

# Step 203 — Git Review

Before completion:

```bash
git status --short
git diff --stat
```

Inspect all changes.

---

# Step 204 — Secret Safety

Ensure no:

```text
.env
Firebase private key
service-account JSON
OAuth access token
production FCM token
database dump
```

is introduced.

---

# Definition of Done

Phase 2.3F is complete only when:

- reusable PushTopic architecture exists;
- category topics map to existing Category records;
- at least one system topic such as Breaking News is supported where appropriate;
- subscription-topic relational preferences exist;
- duplicate preferences are impossible;
- guest subscribers can save preferences;
- authenticated subscribers can save preferences;
- preferences remain per subscription/device;
- preference configuration state distinguishes legacy/default behavior from explicit settings;
- existing subscribers are not silently cut off;
- topic sync architecture is idempotent;
- Post categories can resolve to push topics;
- audience resolver exists;
- multiple matching topics produce one recipient;
- inactive subscriptions are excluded;
- inactive topics are excluded;
- Phase 2.3D auto-publish uses targeted audience resolution;
- Posts without mapped topics have a safe fallback;
- Phase 2.3E manual panel supports All vs Selected Topics;
- manual selected-topic campaigns show recipient count;
- target selection is persisted safely;
- manual duplicate-send protection still works;
- no transport duplication exists;
- no real Firebase request occurs in automated tests;
- preference UI works without exposing tokens;
- full-page cache cannot leak one visitor's preferences to another;
- token rotation preserves preferences where architecture allows;
- migrations are safe;
- documentation exists;
- no Phase 2.3G+ analytics/rate-limiting work is implemented.

---

# Expected Architecture

Approximate structure:

```text
app/
├── Models/
│   └── PushTopic.php
│
├── Services/
│   └── Push/
│       ├── PushTopicSyncService.php
│       ├── PushPreferenceService.php
│       ├── PushAudienceResolver.php
│       └── PostPushTopicResolver.php
│
├── Http/
│   ├── Controllers/
│   │   └── PushPreferenceController.php
│   └── Requests/
│       └── Push/
│           └── UpdatePushPreferencesRequest.php
│
└── Console/
    └── Commands/
        └── SyncPushTopicsCommand.php
```

Possible migrations:

```text
create_push_topics_table
create_push_subscription_topic_table
add_preferences_configured_at_to_push_subscriptions
add_targeting_fields_to_push_notifications
create_push_notification_topic_table
```

Only create what the actual architecture requires.

Do not force unnecessary tables.

---

# Required Completion Report

At completion provide:

## 1. Phase Summary

Explain the topic/preference system implemented.

## 2. Existing Architecture Audit

Explain how Categories, PushSubscriptions, Phase 2.3D and Phase 2.3E were reused.

## 3. Database

Report all new/modified tables, columns, indexes and foreign keys.

## 4. PushTopic Architecture

Explain:

```text
category topics
system topics
active state
category mapping
```

## 5. Legacy Subscriber Behavior

Explain exactly what happens to subscriptions created before Phase 2.3F.

## 6. Preference Lifecycle

Explain:

```text
never configured
explicit selection
zero selected
reactivation
token rotation
```

## 7. Frontend UI

Explain how users manage notification preferences.

## 8. Guest Support

Confirm login is not required.

## 9. Authenticated Support

Explain per-device behavior.

## 10. Topic Sync

Provide exact command if implemented.

Report created/updated topic behavior.

## 11. Automatic Post Targeting

Explain:

```text
Post
→ categories/system topics
→ audience resolver
→ unique active subscriptions
```

## 12. Breaking News

Explain exact behavior.

## 13. Manual Filament Targeting

Explain:

```text
All Active Subscribers
Selected Topics
```

and recipient counting.

## 14. Audience Deduplication

Explain how duplicate recipient sends are prevented.

## 15. FCM Native Topics Decision

State whether native FCM topic subscription was implemented.

If deferred, explain that Laravel DB remains source of truth and individual-token delivery is retained.

This is not a blocker.

## 16. Files Created

List every new file.

## 17. Files Modified

List every modified file and purpose.

## 18. Tests

Report:

```text
tests created
tests executed
passed
failed
```

## 19. Build Results

Report:

```text
php artisan test
Pint
npm run build
JavaScript tests if applicable
```

## 20. Migration Results

Report safe migration status.

## 21. Cache / Privacy Review

Confirm one visitor's preferences cannot leak through full-page caching.

## 22. Security Review

Confirm:

- raw FCM token not exposed;
- arbitrary subscription modification blocked;
- client user_id ignored;
- CSRF/auth conventions retained;
- no private Firebase credential exposed.

## 23. Scope Verification

Explicitly confirm these were NOT implemented:

```text
click analytics
open analytics
CTR dashboard
delivery analytics
advanced rate limiting
A/B testing
geographic targeting
behavioral segmentation
```

## 24. Phase 2.3G Readiness

Explain how current PushNotification, audience and subscription models can support click/delivery analytics next.

## 25. Final Status

Finish with exactly one:

`PHASE 2.3F COMPLETE`

or

`PHASE 2.3F BLOCKED`

If blocked, identify the exact blocking issue.

---

# Final Instruction

Fully implement:

# Phase 2.3F — Topics & Category Preferences

Do not merely audit the repository.

Inspect and reuse the actual Phase 2.3A–2.3E implementation.

Do not build a second taxonomy system.

Existing Laravel Categories must remain the source for category-based notification interests.

The final target architecture must be:

```text
Subscriber
     ↓
PushSubscription
     ↓
Topic Preferences
     ↓
PushAudienceResolver
     ↓
Unique Active Subscriptions
     ↓
Existing PushNotificationService
```

Automatic Post flow must become:

```text
Post Published
     ↓
Resolve Post Category/System Topics
     ↓
Resolve Matching Audience
     ↓
Existing Push Engine
```

Manual Filament flow must support:

```text
All Active Subscribers
OR
Selected Topics
```

Existing subscribers must not suddenly stop receiving notifications simply because they have not yet configured preferences.

Keep local Laravel database preferences as the authoritative source unless native FCM topic synchronization can be added cleanly without destabilizing the architecture.

Do not begin Phase 2.3G.

Do not implement analytics, CTR, advanced rate limiting, A/B testing, geographic targeting or behavioral segmentation.

Run all relevant migrations, tests, frontend build and formatting.

Fix regressions introduced by this phase.

Provide the full required completion report.

End exactly with:

`PHASE 2.3F COMPLETE`

or:

`PHASE 2.3F BLOCKED`