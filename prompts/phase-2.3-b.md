# Phase 2.3B — Push Subscription Management

## Project

Daily Samvad — WordPress to Laravel Migration / NewsMan CMS Foundation

Current stack:

- Laravel 12
- PHP 8.3+
- Blade frontend
- Livewire where already applicable
- Filament admin
- Vite
- Redis / queues available
- Existing authentication and role system
- Existing reporter → reviewer/editor → publish workflow
- Existing media/image optimization
- Existing caching architecture
- Phase 2.3A — Firebase & Browser Push Foundation completed or expected to be completed first

This phase continues:

# Version 2.3 — Push Notification System

---

# Phase Objective

Implement the application-side subscription management layer for browser push notifications.

Phase 2.3A established:

```text
Browser
→ Notification permission
→ Service Worker
→ Firebase Messaging
→ FCM Registration Token
```

Phase 2.3B must extend that flow to:

```text
Browser
→ FCM Token
→ Laravel API
→ Push Subscription Manager
→ Database
```

This phase must provide a robust and reusable subscription lifecycle.

---

# Primary Goals

Implement:

1. push subscription database schema;
2. Laravel model(s);
3. browser token registration endpoint;
4. token refresh/update handling;
5. token deduplication;
6. anonymous visitor subscriptions;
7. authenticated user association;
8. device/browser metadata;
9. subscription activation/deactivation;
10. unsubscribe support;
11. token lifecycle management;
12. frontend → backend synchronization;
13. validation and security;
14. cleanup foundation;
15. automated tests;
16. reusable NewsMan architecture.

---

# Scope Boundary

This phase is specifically for **subscription management**.

Do NOT implement functionality assigned to later phases.

Later phases:

- 2.3C — Laravel Push Engine
- 2.3D — Post Publish Automation
- 2.3E — Filament Notification Panel
- 2.3F — Topics & Category Preferences
- 2.3G — Analytics & Click Tracking
- 2.3H — Queue, Security & Rate Limiting
- 2.3I — Testing & Production Deployment

---

# Do Not Implement Yet

Do NOT implement:

- FCM server-side notification sending;
- Firebase Admin SDK;
- post publish notifications;
- breaking news auto-push;
- notification campaigns;
- Filament notification dashboard;
- category subscriptions;
- topic subscriptions;
- notification analytics;
- delivery tracking;
- click tracking;
- campaign scheduling;
- queue jobs for sending notifications.

Those belong to later phases.

---

# Critical Development Rule

Before modifying code, inspect:

```text
app/
config/
database/migrations/
database/factories/
resources/js/push/
resources/views/components/
routes/
tests/
```

Also inspect all files created in Phase 2.3A.

Do not assume Phase 2.3A file names blindly.

Use the actual existing architecture.

---

# Protected Existing Functionality

Do not break:

- frontend routes;
- homepage;
- article pages;
- category pages;
- tags;
- search;
- author pages;
- archives;
- authentication;
- Filament;
- publishing workflow;
- SEO;
- images;
- ads;
- YouTube player;
- Redis;
- Laravel caching;
- queues;
- imported WordPress data;
- existing migrations;
- existing tests.

---

# Target Architecture

Expected lifecycle:

```text
Visitor
   ↓
Push CTA
   ↓
Browser permission granted
   ↓
Firebase getToken()
   ↓
FCM Token
   ↓
POST /push/subscriptions
   ↓
Laravel validation
   ↓
PushSubscriptionService
   ↓
push_subscriptions table
```

Existing token:

```text
Same browser/token
   ↓
Registration request
   ↓
Existing row found
   ↓
Metadata / last_seen updated
   ↓
No duplicate row
```

Unsubscribe:

```text
Visitor disables notifications
   ↓
DELETE / push unsubscribe endpoint
   ↓
Subscription marked inactive
```

---

# Step 1 — Audit Phase 2.3A

Inspect the actual Phase 2.3A implementation.

Identify:

- function returning FCM token;
- push bootstrap module;
- UI state handling;
- service-worker registration;
- permission flow;
- browser configuration mechanism;
- any token result contract.

Do not duplicate the token-generation logic.

Phase 2.3B must extend it.

---

# Step 2 — Database Migration

Create a migration for:

```text
push_subscriptions
```

Suggested columns:

```text
id
user_id nullable
token
device_uuid nullable
browser nullable
browser_version nullable
platform nullable
device_type nullable
language nullable
timezone nullable
ip_address nullable
user_agent nullable
is_active
permission_status
last_seen_at nullable
last_registered_at nullable
unsubscribed_at nullable
created_at
updated_at
```

Use appropriate Laravel/MySQL column types.

---

# Step 3 — Token Storage

The FCM token must be able to hold Firebase registration token length safely.

Do not use an arbitrarily short `varchar`.

Use an appropriate `text` or sufficiently sized indexed strategy.

Remember:

A full text field cannot always be indexed normally depending on MySQL configuration.

Choose a robust schema.

---

# Step 4 — Token Uniqueness

There must not be multiple active records for the exact same FCM token unnecessarily.

Implement deduplication.

Possible strategy:

```text
token_hash
```

Add a cryptographic token hash where useful.

For example:

```text
token_hash CHAR(64)
```

using SHA-256.

Then enforce:

```text
UNIQUE(token_hash)
```

This avoids indexing a potentially long raw token.

The original token may still be stored because it will be required later for FCM delivery.

---

# Step 5 — Suggested Schema

A robust schema may resemble:

```text
push_subscriptions

id
user_id nullable indexed
token text
token_hash char(64) unique
device_uuid nullable indexed
browser nullable
browser_version nullable
platform nullable
device_type nullable
language nullable
timezone nullable
permission_status
is_active boolean default true
last_seen_at nullable
last_registered_at nullable
unsubscribed_at nullable
created_at
updated_at
```

Add IP/user-agent fields only if consistent with project privacy architecture.

Do not collect excessive data without purpose.

---

# Step 6 — Foreign Key

If users table exists:

```text
user_id
```

should be nullable.

Reason:

Anonymous visitors must be able to subscribe.

When authenticated:

```text
push subscription
→ user_id
```

can associate with the authenticated account.

Foreign key deletion behavior should be safe.

Prefer:

```text
nullOnDelete()
```

unless existing project conventions dictate differently.

Push subscription should not destroy user deletion workflows.

---

# Step 7 — Anonymous Subscription Support

This is mandatory.

Most news portal visitors will not log in.

Therefore:

```text
user_id = null
```

must be valid.

Do not require authentication for push registration.

---

# Step 8 — Device Identifier

Implement an optional browser-generated device UUID.

Example client-side key:

```text
daily_samvad_device_uuid
```

or a more reusable name such as:

```text
newsman_device_uuid
```

Prefer NewsMan-reusable naming for core functionality.

Generate once using:

```javascript
crypto.randomUUID()
```

where supported.

Provide safe fallback if necessary.

Store in localStorage.

---

# Step 9 — Do Not Fingerprint Users

Device UUID must be an application-generated random identifier.

Do NOT create a fingerprint from:

- canvas;
- fonts;
- hardware;
- installed plugins;
- screen properties;
- browser fingerprinting libraries.

Keep it privacy-friendly.

---

# Step 10 — Device Metadata

Collect lightweight metadata only where useful.

Possible metadata:

```text
browser
platform
device_type
language
timezone
```

Do not over-engineer user-agent parsing.

If an existing device/browser utility exists, reuse it.

Avoid adding a heavy dependency only to identify Chrome vs Edge.

---

# Step 11 — PushSubscription Model

Create:

```text
app/Models/PushSubscription.php
```

or project-consistent equivalent.

Expected concerns:

- fillable/guarded;
- casts;
- relationship to user;
- scopes;
- helpers where useful.

Suggested casts:

```php
'is_active' => 'boolean',
'last_seen_at' => 'datetime',
'last_registered_at' => 'datetime',
'unsubscribed_at' => 'datetime',
```

---

# Step 12 — User Relationship

Add relationship to User model if appropriate:

```php
public function pushSubscriptions()
{
    return $this->hasMany(PushSubscription::class);
}
```

Do not modify unrelated User model behavior.

---

# Step 13 — Useful Model Scopes

Consider scopes such as:

```php
scopeActive()
scopeForUser()
```

Do not create speculative scopes that are not used.

---

# Step 14 — Subscription Service

Create a dedicated service such as:

```text
app/Services/Push/PushSubscriptionService.php
```

Use existing project service namespace conventions.

Responsibilities:

```text
register()
updateExisting()
unsubscribe()
associateUser()
touch()
```

Do not put core subscription business logic directly in controllers.

---

# Step 15 — Registration Contract

The service should accept structured data.

Conceptually:

```php
register(
    string $token,
    ?User $user,
    array $metadata = []
)
```

Actual design should follow project conventions.

---

# Step 16 — Token Hashing

Generate hash server-side:

```php
hash('sha256', $token)
```

Do not trust a token hash provided by browser.

Server must derive it.

---

# Step 17 — Idempotent Registration

Registration must be idempotent.

Repeated request with same token:

```text
should NOT create another row
```

Instead:

```text
find token_hash
→ update existing subscription
→ reactivate if necessary
→ update last_seen_at
→ update last_registered_at
```

---

# Step 18 — Reactivation

If previously unsubscribed token returns and browser permission is granted again:

```text
is_active = true
unsubscribed_at = null
permission_status = granted
```

unless there is a security reason not to.

---

# Step 19 — User Association

Scenario:

```text
Anonymous visitor subscribes
→ subscription has user_id = null
→ visitor later logs in
```

On next push sync:

```text
same device/token
→ associate subscription with authenticated user
```

Do not create duplicate row.

---

# Step 20 — Account Switching

Consider:

```text
User A logs out
User B logs in
same browser
same FCM token
```

The system should have deterministic behavior.

Recommended:

The subscription belongs to the currently authenticated account when synchronized.

Document the chosen behavior.

Do not create multiple rows with identical token.

---

# Step 21 — Registration Endpoint

Create an API/web endpoint consistent with existing Laravel architecture.

Example:

```text
POST /push/subscriptions
```

or:

```text
POST /api/push/subscriptions
```

Choose based on the project's routing style.

Do not create a public API namespace unnecessarily if the project primarily uses web routes.

---

# Step 22 — Unsubscribe Endpoint

Implement:

```text
DELETE /push/subscriptions
```

or equivalent.

Possible payload:

```json
{
  "token": "..."
}
```

or device identifier if securely appropriate.

Prefer token-based exact subscription identification.

---

# Step 23 — CSRF Protection

If using Laravel web routes:

Maintain CSRF protection.

Do not disable CSRF globally.

Frontend request must send appropriate CSRF token.

If API architecture is used, follow the project's existing API security convention.

---

# Step 24 — Form Requests

Use dedicated request validation where appropriate.

Suggested:

```text
StorePushSubscriptionRequest
DeletePushSubscriptionRequest
```

Do not place a large validation array directly inside controllers if existing architecture uses Form Requests.

---

# Step 25 — Registration Validation

Validate:

```text
token
device_uuid
browser
browser_version
platform
device_type
language
timezone
permission_status
```

Do not trust arbitrary long strings.

Set sensible max lengths.

---

# Step 26 — Token Validation

FCM token must:

- be required for registration;
- be a string;
- have sensible minimum/maximum bounds.

Do not attempt to infer an exact Firebase token regex unless guaranteed.

Firebase token formats can evolve.

Avoid brittle validation.

---

# Step 27 — Permission Status

Allowed values may include:

```text
granted
denied
default
```

For persisted active subscriptions, normally:

```text
granted
```

will be expected.

Use a consistent representation.

Consider an enum only if consistent with the project.

Do not introduce excessive architecture for three values.

---

# Step 28 — Controller

Create a thin controller such as:

```text
PushSubscriptionController
```

Responsibilities:

```text
validate
call service
return response
```

Do not implement business logic inside controller.

---

# Step 29 — Response Contract

Use consistent JSON responses.

Successful registration example:

```json
{
  "success": true,
  "status": "subscribed"
}
```

Existing subscription:

```json
{
  "success": true,
  "status": "updated"
}
```

Unsubscribed:

```json
{
  "success": true,
  "status": "unsubscribed"
}
```

Do not expose raw FCM token back unnecessarily.

---

# Step 30 — HTTP Status Codes

Use appropriate response codes.

Examples:

```text
200 updated
201 newly created
422 validation failure
```

Follow existing project conventions.

---

# Step 31 — Frontend Subscription Client

Create/extend a module such as:

```text
resources/js/push/subscriptions.js
```

Responsibilities:

```text
registerSubscription()
unsubscribeSubscription()
syncSubscription()
```

Do not embed fetch requests inside Blade templates.

---

# Step 32 — Connect Phase 2.3A Token Flow

Current Phase 2.3A flow:

```text
getMessagingToken()
```

must now continue:

```text
token
↓
registerSubscription(token)
```

Only after successful token retrieval.

---

# Step 33 — Registration Payload

Payload may resemble:

```javascript
{
    token,
    device_uuid,
    browser,
    browser_version,
    platform,
    device_type,
    language,
    timezone,
    permission_status: 'granted'
}
```

Do not include unnecessary information.

---

# Step 34 — CSRF Client Handling

If using web routes, use existing Laravel CSRF mechanism.

Possible source:

```html
<meta name="csrf-token" content="...">
```

Do not duplicate CSRF infrastructure if already present.

---

# Step 35 — No Token in URL

Never send FCM token as:

```text
GET /push/subscribe?token=...
```

Use POST body.

Tokens should not appear in URLs, access logs, analytics or referrer headers.

---

# Step 36 — Local Subscription State

Store lightweight local state if useful.

Example:

```text
newsman_push_registered
```

However:

Do NOT rely solely on localStorage as source of truth.

Database remains authoritative.

FCM token synchronization should still be possible.

---

# Step 37 — Token Refresh / Rotation

FCM tokens can change.

Architecture must support:

```text
old token
→ new token
```

Do not assume token is permanent.

If browser produces a different token:

```text
register new token
```

and maintain clean subscription state.

---

# Step 38 — Device UUID + Token Rotation

Using stable device UUID enables future cleanup.

Possible logic:

```text
same device_uuid
different token
```

Then:

- new token becomes current;
- old active token for that device may be deactivated.

Be conservative.

Do not accidentally disable valid multiple browser profiles/devices.

---

# Step 39 — Multiple Devices

One user may have:

```text
desktop Chrome
office laptop Edge
Android Chrome
```

All should be valid subscriptions.

Therefore:

```text
User
hasMany
PushSubscription
```

Do not enforce one token per user.

---

# Step 40 — Multiple Browsers on Same Device

Chrome and Edge may produce separate tokens.

Allow them.

Do not enforce one subscription per physical device.

---

# Step 41 — Last Seen

Each successful subscription sync may update:

```text
last_seen_at
```

This will later help cleanup inactive subscriptions.

---

# Step 42 — Last Registered

Maintain:

```text
last_registered_at
```

to distinguish browser activity from initial creation if useful.

Avoid over-complicating timestamps.

---

# Step 43 — Unsubscribe Behavior

When user disables Daily Samvad notifications from the UI:

Backend should preferably:

```text
is_active = false
unsubscribed_at = now()
```

Do not immediately hard-delete by default.

Historical/lifecycle information may be useful later.

No analytics should be implemented yet.

---

# Step 44 — Browser Permission Denied

If browser permission becomes:

```text
denied
```

and app can identify an existing token safely, subscription may be marked inactive during sync.

Do not continuously call backend if token is unavailable.

---

# Step 45 — Firebase deleteToken

Inspect Phase 2.3A Firebase client.

If technically appropriate, use Firebase:

```javascript
deleteToken(...)
```

during explicit unsubscribe.

However ensure the server subscription is deactivated correctly.

Expected flow:

```text
User clicks Disable
↓
server deactivate
↓
Firebase token deletion where appropriate
↓
UI updates
```

Order should be resilient to failure.

---

# Step 46 — Browser Permission Limitation

Remember:

Calling Firebase `deleteToken()` does NOT reset browser notification permission.

Do not imply:

```text
Notification.permission
```

can be programmatically returned to `default`.

Document this distinction.

---

# Step 47 — UI Update

Extend Phase 2.3A opt-in component.

After backend registration succeeds:

```text
✓ News notifications enabled
```

If backend save fails:

```text
Unable to enable notifications right now.
Please try again.
```

Do not show raw API/Firebase errors.

---

# Step 48 — Disable Notifications UI

Add a lightweight disable/unsubscribe action where appropriate.

Example:

```text
Notifications Enabled
[Disable]
```

Do not build a full preference center yet.

Category preferences belong to 2.3F.

---

# Step 49 — State Model

Frontend should distinguish:

```text
permission granted + server subscribed
permission granted + server sync failed
permission denied
unsupported
not configured
```

Do not represent all granted permission as successfully subscribed.

---

# Step 50 — Avoid Blocking Page Load

Subscription sync must not block page rendering.

Do not wait for push synchronization before displaying content.

Push remains progressive enhancement.

---

# Step 51 — Authentication Sync

When an authenticated user loads a page and has an existing browser token:

Sync subscription to user account.

Do not trigger excessively on every navigation if avoidable.

Use sensible lifecycle behavior.

---

# Step 52 — Guest Sync

Guest subscriptions must work without login/session requirements beyond normal Laravel web security.

---

# Step 53 — Race Conditions

Two simultaneous registration requests with same token must not create duplicates.

Database unique constraint must enforce correctness.

Service should gracefully handle duplicate insert race.

Do not rely only on:

```php
firstOrCreate()
```

without considering uniqueness exceptions and concurrent requests.

---

# Step 54 — Transaction

Use transaction only where useful.

Do not wrap trivial operations unnecessarily.

If token rotation modifies multiple rows, transaction may be appropriate.

---

# Step 55 — Security

Do not expose a route that allows arbitrary users to enumerate subscriptions.

There should be:

```text
register
unsubscribe
```

only.

No public:

```text
GET /push/subscriptions
```

listing.

---

# Step 56 — Mass Assignment

Protect model appropriately.

Do not mass-assign user_id from client payload.

User association must come from:

```php
$request->user()
```

not from browser-provided user ID.

---

# Step 57 — Trust Boundaries

Browser may provide:

```text
device_uuid
browser
platform
language
timezone
```

These are informational only.

Never trust them for authorization.

---

# Step 58 — IP Address

If IP address is stored:

Get it server-side from Laravel request.

Do not trust client-provided IP address.

If existing privacy architecture does not store IP, omit it.

---

# Step 59 — User Agent

If stored:

Use:

```php
$request->userAgent()
```

Do not accept arbitrary user-agent payload as authoritative.

---

# Step 60 — Logging

Avoid logging raw FCM tokens.

Forbidden:

```php
Log::info('Token: '.$token);
```

If needed for debugging:

log shortened hash only.

Example:

```text
token_hash_prefix
```

Never expose complete token in production logs.

---

# Step 61 — Model Factory

Create a factory if the project uses factories for model tests.

Example:

```text
PushSubscriptionFactory
```

Use realistic but fake token values.

---

# Step 62 — Seeder

Do NOT create production push subscriber seeding unless required.

Tests should use factories.

---

# Step 63 — Database Indexes

Consider indexes for:

```text
token_hash unique
user_id
device_uuid
is_active
last_seen_at
```

Avoid excessive indexes.

Optimize for expected future queries.

---

# Step 64 — Active Scope

Future notification delivery will need:

```php
PushSubscription::active()
```

Implement cleanly if useful.

---

# Step 65 — Soft Deletes

Do not add SoftDeletes automatically.

Subscription lifecycle already uses:

```text
is_active
unsubscribed_at
```

Add soft deletes only if existing architecture strongly requires it.

---

# Step 66 — Privacy

Store only metadata necessary for:

- delivering notifications;
- managing token lifecycle;
- debugging compatibility;
- future device management.

Do not collect unnecessary personal data.

---

# Step 67 — Migration Safety

Database already contains production data.

Migration must:

- be additive;
- not alter posts;
- not alter media;
- not alter user records destructively;
- not lock large existing tables unnecessarily.

This migration primarily creates a new table.

---

# Step 68 — Migration Rollback

Ensure:

```bash
php artisan migrate:rollback
```

would safely remove only the push subscription table created by this phase.

Do not drop unrelated tables.

---

# Step 69 — Tests: Registration

Create test:

```text
guest can register push subscription
```

Verify:

```text
database row created
user_id null
is_active true
token_hash generated
```

---

# Step 70 — Tests: Authenticated Registration

Test:

```text
authenticated user can register subscription
```

Verify:

```text
user_id assigned from authenticated user
```

---

# Step 71 — Tests: Idempotency

Register same token twice.

Expected:

```text
one database row
```

Metadata/timestamps may update.

---

# Step 72 — Tests: Reactivation

Create inactive subscription.

Register same token again.

Expected:

```text
is_active true
unsubscribed_at null
```

---

# Step 73 — Tests: Unsubscribe

Register token.

Call unsubscribe.

Expected:

```text
is_active false
unsubscribed_at not null
```

---

# Step 74 — Tests: Invalid Token

Missing/invalid token request must return validation error.

No database row created.

---

# Step 75 — Tests: User ID Injection

Send:

```json
{
  "user_id": 999
}
```

from guest request.

Ensure it cannot attach subscription to arbitrary user.

---

# Step 76 — Tests: Duplicate Race Safety

Where practical, verify unique token_hash handling.

At minimum database uniqueness must exist.

---

# Step 77 — Tests: Existing User Association

Anonymous subscription exists.

Authenticated user registers same token.

Expected:

```text
same row
user_id updated
```

---

# Step 78 — Tests: Multiple Devices

Same user with two different tokens.

Expected:

```text
2 active subscriptions
```

---

# Step 79 — Tests: Frontend Regression

Existing key pages must still render.

Push API should not affect main site rendering.

---

# Step 80 — API Naming

Avoid Daily Samvad-specific backend class names unless presentation-specific.

Prefer:

```text
PushSubscription
PushSubscriptionService
PushSubscriptionController
```

over:

```text
DailySamvadPushSubscriber
```

This keeps NewsMan reuse possible.

---

# Step 81 — NewsMan Reusability

Core architecture should be reusable across future NewsMan installations.

Brand text may remain Daily Samvad-specific in Blade components.

Backend subscription engine should not care which news portal is running.

---

# Step 82 — No Topic Column Hack

Do not add columns like:

```text
sports
politics
punjab
business
```

to `push_subscriptions`.

Topic/category subscriptions belong to relational architecture in 2.3F.

---

# Step 83 — No Notification History

Do not create:

```text
push_notifications
notification_logs
notification_campaigns
```

yet.

---

# Step 84 — No Firebase Sending Credentials

Do not introduce:

```text
FIREBASE_SERVICE_ACCOUNT
FCM_SERVER_KEY
Firebase Admin SDK
OAuth service account
```

yet.

Phase 2.3C handles sending.

---

# Step 85 — No Queue Jobs

Do not create:

```text
SendPushNotificationJob
SendPostPushNotification
```

yet.

---

# Step 86 — No Post Observer Changes

Do not alter publishing logic.

This phase ends at subscription storage.

---

# Step 87 — No Filament Resource Yet

Do not build the admin subscriber management panel unless absolutely required by current architecture.

Full admin notification system belongs to Phase 2.3E.

---

# Step 88 — Basic Admin Visibility

If debugging requires database visibility, rely on DB/tests.

Do not scope-creep into Filament resources.

---

# Step 89 — Cleanup Foundation

Create service method or query foundation that could later identify stale subscriptions.

Example concept:

```text
inactive subscriptions
old last_seen_at
```

Do not create scheduled cleanup jobs yet unless already required.

Phase 2.3H can handle production cleanup policies.

---

# Step 90 — Error Handling

Backend failure must not break frontend.

Frontend registration request should handle:

```text
network failure
419 CSRF
422 validation
500 server error
```

Display a friendly retry state.

---

# Step 91 — Retry Behavior

Do not implement aggressive retry loops.

A later page load or user action may retry synchronization.

Avoid flooding backend.

---

# Step 92 — Endpoint Rate Consideration

Do not add advanced rate limiting yet if reserved for 2.3H.

However use standard Laravel protections and avoid obviously unrestricted abuse patterns.

Document any future rate-limit requirement.

---

# Step 93 — Caching

Subscription registration endpoints must not be cached.

Ensure project-level full-page cache does not cache POST/DELETE endpoints.

Do not modify unrelated cache behavior.

---

# Step 94 — Cloudflare / Varnish Awareness

POST/DELETE registration calls should bypass page cache naturally.

Document if existing Varnish or Cloudflare configuration presents any issue.

Do not make speculative server changes.

---

# Step 95 — Documentation

Extend push notification docs.

Create/update:

```text
docs/push-notifications/subscription-management.md
```

Explain:

- schema;
- guest subscription;
- authenticated subscription;
- token hashing;
- device UUID;
- token rotation;
- unsubscribe lifecycle;
- API routes;
- testing.

---

# Step 96 — Developer Debug Guide

Document how to inspect local subscription state:

```bash
php artisan tinker
```

Example conceptual query:

```php
App\Models\PushSubscription::count();
```

and:

```php
App\Models\PushSubscription::active()->count();
```

Avoid including production secrets.

---

# Step 97 — Validation Commands

Run:

```bash
php artisan migrate
```

on appropriate development/test database.

Then:

```bash
php artisan test
```

Run targeted push tests separately if useful.

Also:

```bash
npm run build
```

because frontend JavaScript changes are expected.

---

# Step 98 — Code Formatting

Run existing formatter.

Example:

```bash
./vendor/bin/pint
```

---

# Step 99 — Migration Inspection

Before completion verify:

```bash
php artisan migrate:status
```

New migration should show applied in development environment.

Do not run destructive commands such as:

```bash
php artisan migrate:fresh
```

against environments containing imported production data unless clearly isolated test DB.

---

# Step 100 — Git Safety

Run:

```bash
git status
git diff --stat
```

Inspect changes.

Ensure no:

```text
.env
Firebase private credentials
production FCM tokens
private JSON
database dumps
```

are committed.

---

# Definition of Done

Phase 2.3B is complete when:

- `push_subscriptions` migration exists;
- schema supports anonymous and authenticated subscriptions;
- raw FCM token stored safely for future delivery;
- server-generated token hash exists;
- token deduplication implemented;
- PushSubscription model exists;
- User relationship exists where appropriate;
- PushSubscriptionService exists;
- registration endpoint works;
- unsubscribe endpoint works;
- CSRF/security protections remain intact;
- client registration integrates with Phase 2.3A;
- browser-generated device UUID works;
- repeated registration is idempotent;
- inactive token can reactivate;
- authenticated user can claim existing anonymous subscription;
- multiple devices per user work;
- unsubscribe marks subscription inactive;
- token is never placed in URLs;
- token is not exposed in normal logs;
- tests cover primary lifecycle;
- frontend build succeeds;
- documentation exists;
- no server-side notification delivery implemented;
- no publish automation implemented;
- no Filament notification panel implemented;
- no topics/categories implemented;
- no notification analytics implemented.

---

# Expected File Shape

Actual paths should follow existing application conventions.

Likely additions:

```text
app/
├── Http/
│   ├── Controllers/
│   │   └── PushSubscriptionController.php
│   └── Requests/
│       └── Push/
│           ├── StorePushSubscriptionRequest.php
│           └── DeletePushSubscriptionRequest.php
│
├── Models/
│   └── PushSubscription.php
│
└── Services/
    └── Push/
        └── PushSubscriptionService.php

database/
├── factories/
│   └── PushSubscriptionFactory.php
└── migrations/
    └── xxxx_xx_xx_create_push_subscriptions_table.php

resources/js/push/
├── existing Phase 2.3A files
└── subscriptions.js

routes/
└── appropriate existing route file

tests/
└── Feature/
    └── Push/
        └── PushSubscriptionTest.php

docs/
└── push-notifications/
    └── subscription-management.md
```

Do not force this structure if project conventions differ.

---

# Required Completion Report

At completion provide:

## 1. Phase Summary

Describe what was implemented.

## 2. Database

Report:

- migration name;
- columns;
- indexes;
- unique constraints;
- user foreign key behavior.

## 3. Subscription Lifecycle

Explain:

```text
FCM token
→ API
→ service
→ database
→ update/reactivate/unsubscribe
```

## 4. Files Created

List every new file.

## 5. Files Modified

List every modified file and purpose.

## 6. Routes

List new subscription routes and HTTP methods.

## 7. Security

Confirm:

- client cannot inject user ID;
- token does not appear in URL;
- raw token is not logged;
- CSRF remains enabled;
- hash generated server-side.

## 8. Guest Behavior

Explain anonymous visitor support.

## 9. Authenticated Behavior

Explain user association and account switching behavior.

## 10. Token Deduplication

Explain token hash and duplicate handling.

## 11. Device Lifecycle

Explain:

- device UUID;
- multiple devices;
- token rotation;
- reactivation.

## 12. Frontend Integration

Explain how Phase 2.3A token generation now connects to Laravel.

## 13. Tests

Report:

```text
tests created
tests executed
passed
failed
```

## 14. Build Results

Report:

```text
npm run build
php artisan test
Pint
```

## 15. Migration Results

Report:

```text
migration applied
migration rollback safety
```

## 16. Scope Verification

Explicitly confirm that these were NOT implemented:

```text
server FCM sending
post publish automation
Filament push panel
topics/categories
analytics
campaign system
```

## 17. Risks / Follow-ups

Mention anything Phase 2.3C should know.

## 18. Final Status

Finish with exactly one:

```text
PHASE 2.3B COMPLETE
```

or:

```text
PHASE 2.3B BLOCKED
```

If blocked, explain the exact blocking issue.

---

# Final Instruction

Fully implement **Phase 2.3B — Push Subscription Management**.

Do not merely audit the project.

Inspect the actual Phase 2.3A implementation first.

Then implement the subscription database and complete browser-to-Laravel synchronization lifecycle.

Run migrations, tests, frontend build and formatting.

Fix issues introduced by this phase.

Do not proceed into Phase 2.3C or later functionality.

The final result must provide this working chain:

```text
Browser Permission
       ↓
Firebase Messaging
       ↓
FCM Token
       ↓
Laravel Registration Endpoint
       ↓
PushSubscriptionService
       ↓
push_subscriptions
       ↓
Update / Reactivate / Unsubscribe
```