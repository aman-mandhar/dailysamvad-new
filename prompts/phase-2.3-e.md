# Phase 2.3E — Filament Push Notification Panel

## Project

Daily Samvad — WordPress to Laravel Migration / NewsMan CMS Foundation

Current stack:

- Laravel 12
- PHP 8.3+
- Filament admin
- Blade frontend
- Livewire where already applicable
- Redis
- Laravel queues
- Existing role/permission system
- Existing Reporter → Reviewer/Editor → Publish workflow
- Existing media/image system
- Existing caching architecture
- Phase 2.3A — Firebase & Browser Push Foundation
- Phase 2.3B — Push Subscription Management
- Phase 2.3C — Laravel Push Notification Engine
- Phase 2.3D — Post Publish Automation

This phase continues:

# Version 2.3 — Push Notification System

---

# Phase Objective

Build a secure and practical Filament admin interface for manually creating and sending push notifications using the existing reusable push notification engine.

The panel must allow authorized users to:

```text
Open Filament
    ↓
Create Push Notification
    ↓
Enter Title / Message / Image / URL
    ↓
Preview
    ↓
Send
    ↓
Existing PushNotificationService
    ↓
Existing Queue
    ↓
Active Push Subscribers
```

This phase must NOT duplicate Firebase transport or push delivery logic already implemented in Phase 2.3C.

---

# Primary Goals

Implement:

1. Filament Push Notification management area;
2. manual notification composer;
3. notification preview;
4. safe send action;
5. active subscriber count visibility;
6. manual notification persistence where needed;
7. draft/sent status lifecycle;
8. duplicate-send prevention;
9. role/permission restrictions;
10. queued sending through existing engine;
11. optional image and URL support;
12. safe manual notification workflow;
13. subscriber summary widgets where useful;
14. automated tests;
15. documentation.

---

# Strict Scope Boundary

Phase 2.3E is:

# Manual Push Notification Management via Filament

Do NOT implement:

- category targeting;
- topic targeting;
- FCM topic messaging;
- subscriber preference center;
- category subscription UI;
- detailed delivery analytics;
- click analytics;
- CTR;
- campaign analytics dashboard;
- advanced queue throttling/rate limiting;
- complex recurring scheduling;
- A/B testing;
- segmentation engine.

Those belong to:

- 2.3F — Topics & Category Preferences
- 2.3G — Analytics & Click Tracking
- 2.3H — Queue, Security & Rate Limiting
- 2.3I — Testing & Production Deployment

---

# Critical First Step — Audit Existing Filament Architecture

Before changing anything, inspect:

```text
app/Filament/
app/Filament/Resources/
app/Filament/Pages/
app/Filament/Widgets/
app/Policies/
app/Models/
app/Services/Push/
app/Jobs/Push/
database/migrations/
routes/
tests/
```

Also inspect the actual implementations completed in:

```text
Phase 2.3B
Phase 2.3C
Phase 2.3D
```

Do not assume earlier prompt file names match final implementation exactly.

---

# Audit Existing Roles and Permissions

Inspect existing role architecture.

Known roles may include:

```text
super-admin
admin
editor
reviewer
reporter
seo-manager
media-manager
analytics-manager
contributor
subscriber
```

Use the project's actual current roles and permission model.

Do not hardcode access rules blindly.

---

# Protected Existing Functionality

Do not break:

- Filament dashboards;
- Post resource;
- User resource;
- Media resource;
- Category resource;
- Tag resource;
- role permissions;
- post publishing workflow;
- Phase 2.3D auto-publish notifications;
- Firebase delivery engine;
- subscription management;
- Redis queues;
- homepage;
- article pages;
- ads;
- SEO;
- media;
- cache invalidation;
- imported WordPress content.

---

# Architecture Goal

Expected architecture:

```text
Filament
   ↓
PushNotification Resource/Page
   ↓
Manual Push Form
   ↓
ManualPushService / PushMessage Factory
   ↓
Existing PushNotificationService
   ↓
Existing Queue
   ↓
Active PushSubscription records
   ↓
FCM
```

Filament must remain an interface layer.

Do not put Firebase-specific HTTP code in Filament actions.

---

# Step 1 — Decide Persistence Model

Manual notifications need enough persistence to safely prevent duplicate sending and provide operational visibility.

Inspect whether Phase 2.3C or previous work already introduced an appropriate notification/campaign model.

Reuse it if appropriate.

If none exists, create a minimal model such as:

```text
PushNotification
```

or:

```text
PushCampaign
```

Prefer the simplest name consistent with the project.

---

# Step 2 — Minimal Manual Notification Table

If required, create a safe additive migration.

Suggested table:

```text
push_notifications
```

Possible fields:

```text
id
created_by nullable
title
body
image_url nullable
target_url nullable
status
sent_at nullable
queued_at nullable
created_at
updated_at
```

Possible additional operational fields:

```text
recipient_count nullable
failed_at nullable
failure_message nullable
```

Only add these if genuinely useful.

Do NOT add full analytics schema yet.

---

# Step 3 — Status Lifecycle

Use a clear lifecycle.

Suggested statuses:

```text
draft
queued
sent
failed
```

Do not create dozens of states.

Use an enum if project conventions already use enums.

Otherwise a validated string is acceptable.

---

# Step 4 — Draft Semantics

A draft notification:

```text
status = draft
```

must not dispatch anything.

Users should be able to:

```text
create
edit
preview
save
```

without sending.

---

# Step 5 — Queued Semantics

When send is confirmed:

```text
draft
→ queued
```

The notification is locked from duplicate manual sending.

---

# Step 6 — Sent Semantics

Define `sent` carefully.

Recommended:

```text
broadcast fan-out successfully initiated
```

or:

```text
all intended jobs successfully queued
```

Do not pretend `sent` means every browser delivered the notification.

Actual delivery analytics belong to 2.3G.

Document the meaning.

---

# Step 7 — Failed Semantics

Use `failed` only for campaign-level failure to initiate dispatch.

Do not mark entire notification failed merely because one subscriber token fails.

Individual token errors remain handled by Phase 2.3C.

---

# Step 8 — Creator Relationship

If model has:

```text
created_by
```

associate it with authenticated admin user.

Use server-side authenticated user ID.

Never accept creator identity from client input.

Use safe foreign key behavior such as:

```text
nullable
nullOnDelete()
```

if consistent.

---

# Step 9 — PushNotification Model

Create model if needed.

Expected concerns:

```text
casts
creator relationship
status helpers
scopeDraft
scopeSent
```

Only add helpers actually used.

---

# Step 10 — No Subscriber IDs Stored in Campaign

Do not store thousands of subscriber IDs in JSON.

Recipients should be resolved at send time using:

```text
PushSubscription::active()
```

or existing delivery query architecture.

---

# Step 11 — Targeting for Phase 2.3E

For this phase, manual notification target is:

# All Active Subscribers

Do not implement category/topic filtering yet.

UI may display:

```text
Target: All Active Subscribers
```

as read-only information.

---

# Step 12 — Active Subscriber Count

Display current active subscriber count before sending.

For example:

```text
Active Subscribers: 2,438
```

Use efficient database count.

Do not load all subscriber records.

---

# Step 13 — Notification Form

Required fields:

```text
title
body
```

Optional:

```text
image_url
target_url
```

Use current PushMessage capabilities.

---

# Step 14 — Title Validation

Title:

```text
required
string
reasonable maximum
```

Do not allow empty title.

---

# Step 15 — Body Validation

Body:

```text
required
string
reasonable maximum
```

Keep push notifications concise.

Do not accept full article HTML.

---

# Step 16 — Plain Text Body

Manual notification body should be plain text.

If Filament textarea accepts text, do not use rich HTML editor.

Avoid:

```text
TinyMCE
TipTap HTML
RichEditor
```

unless there is a compelling existing requirement.

Push message is not an article.

---

# Step 17 — Image URL

Allow optional image URL.

Validate as a URL.

Do not require image.

---

# Step 18 — Media Library Integration

Inspect existing Filament Media resource.

If easy and architecture-consistent, allow selection from existing media library.

But do NOT rebuild media management.

Possible implementation:

```text
select existing Media
→ derive public URL
```

or:

```text
direct image URL
```

Choose the simplest clean approach.

---

# Step 19 — Existing Media Only

Do not add another raw file upload subsystem just for push notifications if existing Media Library can be reused.

---

# Step 20 — Image Preview

If an image URL exists, show a small preview in form/preview panel.

Gracefully handle broken image URLs.

---

# Step 21 — Target URL

Allow optional destination URL.

Examples:

```text
article URL
homepage
category page
special page
```

Validate URL safely.

---

# Step 22 — Internal URL Convenience

If target is internal, use absolute URL.

Do not rely on relative `/slug` unless existing push engine safely converts it.

---

# Step 23 — External URL

Decide whether external URLs are allowed.

Recommended:

Allow valid HTTPS URLs.

Do not introduce arbitrary dangerous URI schemes.

Block:

```text
javascript:
data:
file:
```

---

# Step 24 — Article Selector

Optional improvement:

Allow admin to select an existing Post.

If selected:

```text
title ← post title
body ← excerpt/summary
image ← featured image
url ← canonical post URL
```

Use the existing `PostPushMessageFactory` from Phase 2.3D if possible.

This is strongly preferred if it can be implemented cleanly.

---

# Step 25 — Manual Override

If Post selector pre-fills fields, allow admin to edit:

```text
title
body
image
url
```

before sending.

Do not force the Post's exact original title.

---

# Step 26 — No Automatic Post Trigger Duplication

Selecting an already published Post manually must NOT invoke Phase 2.3D's auto-publish logic.

This is a separate manual notification.

---

# Step 27 — Preview Section

Before sending, show a notification preview.

Suggested conceptual UI:

```text
┌────────────────────────────┐
│ Daily Samvad               │
│ पंजाब में बड़ा फैसला       │
│ पूरी खबर पढ़ें...           │
│ [image]                    │
└────────────────────────────┘
```

Preview is approximate.

Do not claim exact browser rendering.

---

# Step 28 — Preview Content

Preview should show:

```text
title
body
image if present
destination URL
target audience
subscriber count
```

---

# Step 29 — Mobile-Like Preview

A simple compact card is enough.

Do not build a complex browser emulator.

---

# Step 30 — Send Action

Create explicit Filament action:

```text
Send Notification
```

Do not send automatically when the form is saved.

---

# Step 31 — Confirmation Modal

Sending to all active subscribers is a potentially large action.

Require confirmation.

Modal should clearly display:

```text
Title
Active subscriber count
Target: All Active Subscribers
```

---

# Step 32 — Confirmation Text

Example:

```text
This notification will be queued for all active push subscribers.
```

Do not misrepresent delivery guarantee.

---

# Step 33 — Production Safety

Require deliberate action to send.

Do not place send button next to save with ambiguous icons.

Use clear label.

---

# Step 34 — Draft Save

Default action should be:

```text
Save Draft
```

or standard Filament save.

Sending remains separate.

---

# Step 35 — Duplicate Send Prevention

Once:

```text
status = queued
```

or:

```text
status = sent
```

normal send action should be unavailable.

Prevent both UI and backend duplicate sends.

---

# Step 36 — Backend Idempotency

Do not rely only on hiding Filament button.

The service must atomically claim:

```text
draft
→ queued
```

before dispatch.

Two simultaneous clicks must not create two broadcasts.

---

# Step 37 — Atomic Status Claim

Use an atomic database update where appropriate.

Concept:

```text
WHERE status = draft
UPDATE status = queued
```

Only one request should win.

---

# Step 38 — ManualPushNotificationService

Create application service if useful, such as:

```text
ManualPushNotificationService
```

Responsibilities:

```text
validate send state
claim draft
build PushMessage
dispatch to existing PushNotificationService
update campaign status
```

Do not put orchestration inside Filament Resource action.

---

# Step 39 — Reuse PushMessage

Use the existing `PushMessage` class from Phase 2.3C.

Do not create:

```text
FilamentPushMessage
AdminPushMessage
```

duplicates.

---

# Step 40 — Reuse PushNotificationService

All actual delivery must pass through:

```text
PushNotificationService
```

or equivalent Phase 2.3C application service.

Do not call FirebaseMessagingClient directly from Filament.

---

# Step 41 — Queue Fan-Out

Manual notification sending must remain asynchronous.

Expected:

```text
Filament Send
   ↓
Campaign claimed
   ↓
Queue fan-out
   ↓
Return admin response quickly
```

Do not wait for thousands of FCM calls.

---

# Step 42 — Send Response UX

After queue dispatch:

```text
Notification queued successfully.
```

Use Filament notification/toast.

Do not say:

```text
Delivered successfully to all subscribers
```

because actual delivery is not yet known.

---

# Step 43 — Zero Subscribers

If active subscriber count is zero:

Preferred:

Disable or prevent Send action.

Show:

```text
No active push subscribers are available.
```

Do not mark notification sent.

---

# Step 44 — Push Engine Misconfiguration

If server FCM configuration is missing:

Do not crash Filament page.

Send action should fail gracefully with useful admin feedback.

---

# Step 45 — Queue Dispatch Failure

If queue dispatch fails before broadcast initiation:

```text
status → failed
```

or return to draft depending on chosen lifecycle.

Choose one deterministic policy.

Document it.

---

# Step 46 — Retry Failed Manual Notification

If status is:

```text
failed
```

allow authorized user to retry where safe.

Retry must use the same idempotency rules.

---

# Step 47 — Do Not Retry Sent Notification

A `sent` notification must not be resendable through standard retry.

Future explicit "duplicate campaign" functionality can handle that.

Do not build it now.

---

# Step 48 — Clone/Duplicate Draft

Optional:

Allow duplication of an old notification into a new draft.

This is useful but not mandatory.

Do not make original sent record editable into another send.

---

# Step 49 — Sent Record Immutability

After queued/sent:

Disable editing of message content, or strongly restrict it.

The stored notification should represent what was actually queued.

---

# Step 50 — Draft Editable

Drafts remain editable.

---

# Step 51 — Filament Resource

Likely create:

```text
PushNotificationResource
```

Use existing Filament version/API conventions.

Do not copy outdated Filament syntax from internet examples.

Inspect current resources in repository.

---

# Step 52 — Navigation

Suggested navigation group:

```text
Push Notifications
```

or existing appropriate group such as:

```text
Content
Marketing
System
```

Prefer consistency with current admin UI.

---

# Step 53 — Navigation Icon

Use existing Heroicons package already used by Filament.

Choose a bell/notification icon.

Do not add new icon library.

---

# Step 54 — Resource Label

Suggested:

```text
Push Notifications
```

Singular:

```text
Push Notification
```

---

# Step 55 — Table Columns

Useful columns:

```text
title
status
created_by
created_at
queued_at
sent_at
recipient_count
```

Only show fields that exist.

---

# Step 56 — Table Search

Allow search by:

```text
title
body
```

if efficient.

---

# Step 57 — Table Filters

Useful filters:

```text
status
created_by
created_at
```

Do not create excessive filters.

---

# Step 58 — Sorting

Default:

```text
latest created first
```

---

# Step 59 — Status Badge

Show clear status badge for:

```text
draft
queued
sent
failed
```

Use existing Filament color conventions.

---

# Step 60 — Subscriber Count Widget

If useful, create a small widget showing:

```text
Total subscriptions
Active subscriptions
Inactive subscriptions
```

This is operational summary only.

Do not implement analytics.

---

# Step 61 — Do Not Show Delivery Rate

Do not show:

```text
Delivered
Opened
CTR
```

yet.

Those require Phase 2.3G.

---

# Step 62 — Permission Model

Create appropriate permission(s).

Possible:

```text
view push notifications
create push notifications
send push notifications
delete push notifications
```

Follow existing permission naming conventions exactly.

---

# Step 63 — Send Permission

Sending must have a separate high-trust permission.

Do not assume anyone who can view drafts can send to all subscribers.

---

# Step 64 — Recommended Roles

Inspect actual project policy first.

Likely allowed to send:

```text
super-admin
admin
editor
```

Maybe editor depending on current editorial policy.

Do not grant reporter/contributor automatically.

---

# Step 65 — Super Admin

Respect existing super-admin bypass mechanism if one exists.

Do not implement another one.

---

# Step 66 — Reporter Restriction

Reporter should not automatically gain access to mass push notification sending.

---

# Step 67 — Reviewer Restriction

Reviewer should not automatically gain send permission unless existing business rules explicitly allow it.

---

# Step 68 — SEO / Analytics Roles

Do not infer send access merely from these roles.

Use explicit permissions.

---

# Step 69 — Policy / Filament Access

Implement backend authorization.

Do not rely only on navigation visibility.

Direct resource URL must still be protected.

---

# Step 70 — Delete Policy

Draft notifications can potentially be deleted.

Queued/sent notifications should preferably remain for audit/operational history.

Prevent deleting sent records unless super-admin/business rules explicitly require it.

---

# Step 71 — No Hard Analytics Requirement

Persistence in this phase is operational record keeping, not analytics.

---

# Step 72 — Audit Fields

If project already has:

```text
created_by
updated_by
audit logs
```

reuse them.

Do not build a new audit system.

---

# Step 73 — Post Selector Query

If implementing Post selector:

Only include publicly relevant posts.

Use efficient searchable/select approach.

Do not load 13,000+ posts into browser at once.

Use server-side search.

---

# Step 74 — Large Dataset Safety

Daily Samvad contains many posts.

Never use:

```php
Post::all()
```

for a Filament select.

Use searchable/relationship query with limits.

---

# Step 75 — Post Pre-fill Action

When a Post is selected, fill:

```text
title
body
image
url
```

through existing Post push-message factory.

Avoid duplicating excerpt/image URL logic.

---

# Step 76 — Livewire Reactivity

Use Filament reactive form features where appropriate.

Do not add custom JavaScript unless necessary.

---

# Step 77 — Character Counters

Useful for:

```text
title
body
```

if Filament supports it cleanly.

Do not make exact browser push size claims.

---

# Step 78 — URL Preview

Show clickable admin-safe preview.

Use:

```text
target="_blank"
rel="noopener noreferrer"
```

where relevant.

---

# Step 79 — Image URL Security

Do not fetch arbitrary remote image contents server-side merely for preview.

Browser can render URL.

Avoid SSRF risk.

---

# Step 80 — No Remote Metadata Fetch

Do not request external URL metadata, OpenGraph, or screenshots.

Not needed.

---

# Step 81 — HTML Escaping

Filament preview must escape notification text.

Do not render body as raw HTML.

---

# Step 82 — Unicode

Support:

```text
Hindi
Punjabi
English
emoji
```

correctly.

---

# Step 83 — Validation Errors

Display normal Filament validation errors.

Do not expose stack traces or Firebase internals.

---

# Step 84 — Notification Record Factory

Add model factory if tests need it.

Example:

```text
PushNotificationFactory
```

Use fake data.

---

# Step 85 — No Production Seeder

Do not seed fake notification campaigns into production.

---

# Step 86 — Tests: Access Control

Test unauthorized roles cannot access resource/send action.

At minimum test:

```text
reporter cannot send
```

if consistent with permission policy.

---

# Step 87 — Tests: Authorized Access

Verify authorized role can:

```text
view
create
edit draft
```

and send when permission exists.

---

# Step 88 — Tests: Create Draft

Create manual notification.

Expected:

```text
status = draft
no queue dispatch
```

---

# Step 89 — Tests: Edit Draft

Expected:

```text
fields update
no send
```

---

# Step 90 — Tests: Send Draft

Expected:

```text
draft → queued/sent lifecycle
PushNotificationService called
queue dispatch initiated once
```

---

# Step 91 — Tests: Duplicate Click

Call Send twice.

Expected:

```text
one broadcast initiation
```

---

# Step 92 — Tests: Sent Notification

Attempt send again.

Expected:

```text
blocked
```

---

# Step 93 — Tests: Zero Subscribers

Expected:

```text
send prevented or safely no-op
```

based on selected UX.

---

# Step 94 — Tests: Image Optional

Message sends without image.

---

# Step 95 — Tests: URL Optional

Message sends without target URL.

---

# Step 96 — Tests: Invalid URL

Reject unsafe/invalid URL.

---

# Step 97 — Tests: JavaScript Scheme

Reject:

```text
javascript:alert(1)
```

---

# Step 98 — Tests: Post Pre-fill

If Post selector implemented:

Verify:

```text
title
body
image
url
```

are derived correctly.

---

# Step 99 — Tests: Subscriber Count

Count must use only active subscriptions.

---

# Step 100 — Tests: Queue Failure

Simulate dispatch failure.

Verify:

```text
notification not falsely marked sent
```

---

# Step 101 — Tests: Push Service Failure

Push infrastructure error must remain an admin-side failure only.

Do not affect unrelated Filament functionality.

---

# Step 102 — Tests Must Not Contact Firebase

Use mocks/fakes.

---

# Step 103 — Filament Test Style

Inspect current project test patterns.

Use existing Filament/Livewire test conventions.

Do not introduce a second testing style unnecessarily.

---

# Step 104 — Database Migration Safety

Any new table must be additive.

Do not modify existing Post, Media, User or PushSubscription data destructively.

---

# Step 105 — No migrate:fresh

Never run:

```text
php artisan migrate:fresh
```

against current development/production-like dataset.

---

# Step 106 — Migration Rollback

New migration rollback must remove only this phase's table/changes.

---

# Step 107 — No Notification Delivery Logs Table Yet

Do not create per-recipient records such as:

```text
push_deliveries
push_notification_logs
push_clicks
```

in this phase.

Those belong to analytics phase.

---

# Step 108 — Recipient Count

If notification record has:

```text
recipient_count
```

store the number of active subscriptions targeted at dispatch initiation.

This is operational metadata, not delivery analytics.

---

# Step 109 — Count Meaning

Document:

```text
recipient_count = number of active subscriptions selected for fan-out
```

It does NOT mean delivered.

---

# Step 110 — Status Update Timing

Recommended flow:

```text
begin transaction
    ↓
atomically claim draft
    ↓
capture recipient_count
commit
    ↓
dispatch queue fan-out
```

But inspect Phase 2.3C orchestration.

Do not create inconsistent state.

---

# Step 111 — Dispatch Failure After Claim

If queue dispatch immediately throws:

Update status safely to:

```text
failed
```

where possible.

---

# Step 112 — Large Audience

Sending to 100k subscribers must not cause Filament request to iterate 100k records.

Use existing scalable fan-out method.

---

# Step 113 — No Synchronous HTTP Calls

Filament send action must never perform FCM HTTP request per subscriber.

---

# Step 114 — Queue Name

Reuse Phase 2.3C push queue.

---

# Step 115 — Queue Health Awareness

Optionally show a small warning/documentation note if queue configuration is required.

Do not build queue monitoring UI.

---

# Step 116 — Phase 2.3D Separation

Automatic post notifications and manual notifications must use the same delivery engine but remain separate business flows.

Do not rewrite Phase 2.3D.

---

# Step 117 — Manual Notification and Auto Push

Manual notification may reuse content from a Post even if automatic notification already went out.

This is intentionally possible, but requires deliberate admin send action.

---

# Step 118 — No Auto Duplicate Detection Across Manual vs Auto

Do not prevent manual editorial resend just because `push_notified_at` exists on a Post.

Manual sending is intentional.

---

# Step 119 — But Prevent Accidental Same Manual Record Re-send

The `PushNotification` record itself must be idempotent.

---

# Step 120 — Dashboard Placement

If adding widget, place it only where appropriate for users with permission.

Do not show push management cards to subscribers/reporters unnecessarily.

---

# Step 121 — Sidebar Navigation Visibility

Hide resource navigation when user lacks view permission.

Still enforce policy backend.

---

# Step 122 — Filament Dashboard

Do not redesign dashboards.

Add only small integration if beneficial.

---

# Step 123 — Existing UI Tokens

Reuse current admin theme/UI conventions.

Do not introduce a parallel design system.

---

# Step 124 — Mobile Filament

Form/table should remain usable on narrow screens.

Do not create fixed-width preview that breaks responsive layout.

---

# Step 125 — Preview Accuracy Disclaimer

If displaying a preview, optional subtle text:

```text
Actual appearance may vary by browser/device.
```

This is acceptable.

---

# Step 126 — Send Confirmation Details

Confirmation should show enough information to prevent mistakes.

Recommended:

```text
Title
Target: All Active Subscribers
Subscriber Count
```

---

# Step 127 — Cancel

User must be able to cancel Send without changing status.

---

# Step 128 — Double Submission Protection

Use both:

```text
Filament action processing state
+
backend atomic status claim
```

---

# Step 129 — No Scheduling Yet

Do not implement:

```text
send_at
schedule picker
cron-based push campaign scheduler
```

unless absolutely required by existing architecture.

Manual scheduling belongs outside Phase 2.3E scope.

---

# Step 130 — No Recurring Push

Do not implement recurring campaigns.

---

# Step 131 — No Topic Dropdown

Do not add fake placeholder target options like:

```text
Punjab
Sports
Politics
```

until actual 2.3F targeting exists.

For now show:

```text
All Active Subscribers
```

---

# Step 132 — No User Segments

Do not add:

```text
Android
Desktop
Logged-in users
Guests
```

targeting yet.

---

# Step 133 — No Geographic Targeting

Do not implement location targeting.

---

# Step 134 — No Marketing Automation

Do not introduce drip campaigns.

---

# Step 135 — Documentation

Create/update:

```text
docs/push-notifications/filament-panel.md
```

or project-consistent path.

---

# Step 136 — Documentation Contents

Document:

1. permissions;
2. create draft;
3. Post pre-fill if implemented;
4. preview;
5. send confirmation;
6. queue behavior;
7. status lifecycle;
8. duplicate-send protection;
9. recipient count meaning;
10. failure handling;
11. safe production usage.

---

# Step 137 — Operator Workflow

Document recommended editorial flow:

```text
Open Push Notifications
→ Create
→ Write/select Post
→ Preview
→ Save Draft
→ Review
→ Send Notification
→ Confirm
→ Notification Queued
```

---

# Step 138 — Production Safety Note

Manual notification sends cannot be recalled once FCM delivery fan-out begins.

Make this clear in documentation.

---

# Step 139 — Environment Requirement

No new Firebase credentials should be needed beyond Phase 2.3C.

Do not duplicate service-account configuration.

---

# Step 140 — Permission Seeder

If roles/permissions are seeded centrally, update the canonical seeder safely.

Do not create a competing seeder.

---

# Step 141 — Existing Production Roles

Seeder must be idempotent.

Do not delete or reset existing role assignments.

---

# Step 142 — Permission Names

Use existing naming conventions.

Examples only:

```text
push-notifications.view
push-notifications.create
push-notifications.send
push-notifications.delete
```

or:

```text
view_push_notifications
send_push_notifications
```

Inspect project first.

---

# Step 143 — Permission Assignment

Do not grant permissions to every role automatically.

Use explicit intended roles.

---

# Step 144 — Existing Super Admin Bypass

Respect existing `Gate::before` or equivalent.

---

# Step 145 — Policy Tests

Test permission enforcement independently of Filament UI where practical.

---

# Step 146 — Resource URL Access

Unauthorized user entering resource URL directly must receive appropriate denial.

---

# Step 147 — No Public Routes

Push notification management must remain inside authenticated admin/Filament routes.

Do not create public manual-send API.

---

# Step 148 — CSRF/Auth

Use Filament/Laravel normal authentication protection.

Do not bypass middleware.

---

# Step 149 — Mass Assignment

Do not allow status/creator/recipient_count to be client-controlled freely.

These fields should be managed server-side where appropriate.

---

# Step 150 — Status Tampering

Admin form should not expose editable raw status field if business logic controls it.

---

# Step 151 — Sent At

Set:

```text
sent_at
```

or equivalent only according to defined lifecycle semantics.

Document exact meaning.

---

# Step 152 — Queued At

If used:

```text
queued_at = broadcast orchestration successfully queued
```

or equivalent.

Be consistent.

---

# Step 153 — Failure Message

If storing failure message:

Do not store raw OAuth tokens, FCM tokens, credentials or giant exception dumps.

Keep sanitized operational reason.

---

# Step 154 — User Feedback

Examples:

Success:

```text
Push notification queued for 1,248 active subscribers.
```

Failure:

```text
Push notification could not be queued. Check the push configuration and queue worker.
```

Do not expose internal stack trace.

---

# Step 155 — Empty Message Protection

Both title and body must be meaningful.

Whitespace-only values should fail validation.

---

# Step 156 — Trim Input

Normalize leading/trailing whitespace where appropriate.

---

# Step 157 — Body Line Breaks

Allow basic line breaks if FCM/browser supports them, but preview should remain readable.

---

# Step 158 — Image Failure

Broken image must not prevent sending title/body notification unless push engine requires otherwise.

---

# Step 159 — URL Failure

Invalid URL must be caught before queue dispatch.

---

# Step 160 — Post Deleted Before Send

If a draft was pre-filled from a Post and then that Post is deleted:

Manual notification record should retain its copied:

```text
title
body
image
URL
```

It must not depend on the Post still existing at send time.

---

# Step 161 — Snapshot Principle

Manual notification record should store final message content as a snapshot.

Do not dynamically recalculate from Post after Send is confirmed.

---

# Step 162 — Exact Message at Send

This ensures queued notifications represent what admin reviewed.

---

# Step 163 — Post Relationship Optional

A nullable:

```text
post_id
```

may be useful for reference.

Only add if needed.

Use:

```text
nullOnDelete()
```

so notification record survives deleted Post.

---

# Step 164 — No Post Requirement

Manual notifications must work without Post.

Examples:

```text
Site maintenance
Special announcement
Breaking alert
Homepage promotion
```

---

# Step 165 — Test Standalone Notification

Create manual push with no Post.

Expected:

```text
valid
```

---

# Step 166 — Test Post-Based Notification

If Post selector exists:

Expected:

```text
pre-fill works
manual editing remains possible
```

---

# Step 167 — Test Sent Immutability

After send:

attempt edit.

Expected:

```text
blocked or fields read-only
```

depending on chosen architecture.

---

# Step 168 — Test Failed Retry

If retry feature implemented:

```text
failed
→ retry
→ queued
```

safely.

---

# Step 169 — No Duplicate Jobs

Retry should only occur if original broadcast initiation definitely failed.

Do not retry ambiguous already-dispatched campaigns blindly.

---

# Step 170 — Audit Current Queue Fan-Out Contract

Before implementing send service, inspect exactly what Phase 2.3C provides.

Possible:

```text
sendToSubscriptions(query, message)
dispatchToSubscriptions(query, message)
broadcast(message)
```

Use actual API.

Do not invent parallel dispatch code.

---

# Step 171 — Audit Phase 2.3D Message Factory

Reuse `PostPushMessageFactory` where Post pre-fill is implemented.

Do not duplicate:

```text
excerpt cleanup
image URL resolution
canonical URL generation
```

---

# Step 172 — No Firebase HTTP in Filament Tests

All delivery behavior mocked/faked.

---

# Step 173 — Targeted Test Commands

Run relevant tests.

Likely:

```bash
php artisan test tests/Feature/Push
```

and Filament-specific test paths if present.

---

# Step 174 — Full Test Suite

Run:

```bash
php artisan test
```

where practical.

---

# Step 175 — Pint

Run:

```bash
./vendor/bin/pint
```

---

# Step 176 — Migrations

If migration created:

```bash
php artisan migrate
php artisan migrate:status
```

on safe development DB.

---

# Step 177 — No Destructive Migration

Never run:

```bash
php artisan migrate:fresh
```

on current dataset.

---

# Step 178 — Config Clear

Run:

```bash
php artisan optimize:clear
```

where appropriate.

---

# Step 179 — Frontend Build

Filament/PHP-only work may not require Vite changes.

If frontend/admin assets change, run:

```bash
npm run build
```

---

# Step 180 — Role Seeder Validation

If permissions seeder changed, run appropriate targeted seeding in a safe environment.

Do not wipe roles.

---

# Step 181 — Git Review

Before completion:

```bash
git status --short
git diff --stat
```

Inspect changes.

---

# Step 182 — Secret Review

Ensure no:

```text
.env
Firebase private key
service-account JSON
OAuth access token
production FCM token
database dump
```

entered Git.

---

# Step 183 — Definition of Done

Phase 2.3E is complete only when:

- Filament Push Notification management exists;
- access is permission-protected;
- unauthorized users cannot access send functionality;
- draft notification can be created;
- title/body validation works;
- image is optional;
- URL is optional and safe;
- Post pre-fill is implemented if practical;
- preview exists;
- active subscriber count is visible;
- target clearly says All Active Subscribers;
- Send requires explicit confirmation;
- Send does not happen on normal form save;
- duplicate send is prevented;
- backend idempotency exists;
- existing Phase 2.3C engine is reused;
- existing queue fan-out is reused;
- manual send remains asynchronous;
- zero-subscriber state is handled;
- send failure is handled gracefully;
- sent records cannot accidentally resend;
- Post-based and standalone notifications both work;
- message content is stored as a send-time snapshot;
- tests use fakes/mocks;
- no real Firebase calls occur in tests;
- migrations are safe;
- existing Filament resources still work;
- Phase 2.3D auto-publish remains unchanged;
- documentation exists;
- no Phase 2.3F+ functionality is implemented.

---

# Expected File Shape

Actual structure should follow existing project architecture.

Potential additions:

```text
app/
├── Filament/
│   └── Resources/
│       └── PushNotificationResource/
│           ├── PushNotificationResource.php
│           └── Pages/
│               ├── ListPushNotifications.php
│               ├── CreatePushNotification.php
│               ├── EditPushNotification.php
│               └── ViewPushNotification.php
│
├── Models/
│   └── PushNotification.php
│
├── Policies/
│   └── PushNotificationPolicy.php
│
└── Services/
    └── Push/
        └── ManualPushNotificationService.php

database/
├── factories/
│   └── PushNotificationFactory.php
└── migrations/
    └── xxxx_create_push_notifications_table.php

docs/
└── push-notifications/
    └── filament-panel.md

tests/
└── Feature/
    └── Push/
        └── FilamentPushNotificationTest.php
```

Follow the actual installed Filament version's resource structure.

Do not force these paths if the project uses a different organization.

---

# Required Completion Report

At completion provide:

## 1. Phase Summary

Explain what Filament push management was implemented.

## 2. Existing Architecture Audit

Explain how Phase 2.3C and Phase 2.3D services were reused.

## 3. Database

If a manual notification table was created, report:

```text
table
columns
indexes
foreign keys
status fields
```

## 4. Status Lifecycle

Explain:

```text
draft
queued
sent
failed
```

and exact semantics.

## 5. Filament Resource

Report:

```text
navigation
form
table
preview
actions
```

## 6. Manual Notification Flow

Explain:

```text
Create
→ Save Draft
→ Preview
→ Send
→ Confirm
→ Queue
→ PushNotificationService
```

## 7. Post Pre-fill

State whether Post selector was implemented.

If yes, explain how existing Post push factory is reused.

## 8. Recipient Targeting

Confirm Phase 2.3E targets:

```text
All Active Subscribers
```

only.

## 9. Subscriber Count

Explain how count is obtained and what it represents.

## 10. Duplicate Send Protection

Explain backend idempotency.

## 11. Authorization

List new permission names and roles granted access.

## 12. Files Created

List every new file.

## 13. Files Modified

List every modified file and why.

## 14. Tests

Report:

```text
tests created
tests executed
passed
failed
```

## 15. Validation

Report:

```text
php artisan test
Pint
migration status
npm run build if applicable
```

## 16. Failure Handling

Explain:

```text
zero subscribers
queue dispatch failure
FCM configuration failure
invalid input
```

## 17. Security Review

Confirm:

- no public send route;
- authorization enforced;
- creator cannot be client-injected;
- unsafe URL schemes rejected;
- no raw FCM tokens exposed;
- no Firebase private credentials exposed.

## 18. Existing Auto-Publish Compatibility

Confirm Phase 2.3D still works and was not duplicated.

## 19. Scope Verification

Explicitly confirm these were NOT implemented:

```text
category targeting
topic targeting
subscriber preferences
FCM topics
click analytics
delivery analytics
CTR
advanced rate limiting
complex scheduling
A/B testing
```

## 20. Phase 2.3F Readiness

Explain how the current manual notification target can later be replaced/extended by category/topic audience selection.

## 21. Final Status

Finish with exactly one:

`PHASE 2.3E COMPLETE`

or:

`PHASE 2.3E BLOCKED`

If blocked, explain the exact blocking issue.

---

# Final Instruction

Fully implement:

# Phase 2.3E — Filament Push Notification Panel

Do not merely audit or describe the work.

Inspect the actual completed Phase 2.3B–2.3D architecture first.

Reuse the existing PushMessage, PushNotificationService, queue fan-out, subscription scopes and Post push-message factory.

Do not implement another Firebase client.

The final manual workflow must be:

```text
Authorized Filament User
        ↓
Create Push Notification
        ↓
Save Draft
        ↓
Preview
        ↓
Explicit Send
        ↓
Confirmation
        ↓
Atomic Duplicate Protection
        ↓
Existing PushNotificationService
        ↓
Existing Push Queue
        ↓
All Active Subscribers
```

Sending must be deliberate, asynchronous, permission-protected and duplicate-safe.

Do not start Phase 2.3F.

Do not implement topics, category preferences, analytics, CTR or advanced rate limiting.

Run all relevant tests, migrations and formatting.

Fix any regressions introduced by this phase.

Provide the complete required report.

End exactly with:

`PHASE 2.3E COMPLETE`

or:

`PHASE 2.3E BLOCKED`