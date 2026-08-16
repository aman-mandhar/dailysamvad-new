# Phase 2.3C — Laravel Push Notification Engine

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
- Existing media and image optimization
- Existing caching architecture
- Phase 2.3A — Firebase & Browser Push Foundation
- Phase 2.3B — Push Subscription Management

This phase continues:

# Version 2.3 — Push Notification System

---

# Phase Objective

Implement the reusable Laravel server-side push notification delivery engine.

Previous phases established:

```text
Browser
    ↓
Notification Permission
    ↓
Firebase Messaging
    ↓
FCM Registration Token
    ↓
Laravel
    ↓
push_subscriptions
```

Phase 2.3C must implement:

```text
Laravel
    ↓
Push Message
    ↓
Firebase HTTP v1 Authentication
    ↓
FCM HTTP v1 API
    ↓
Subscriber Token
    ↓
Browser Push Notification
```

At the end of this phase Laravel must be capable of sending a real test notification to one or more active push subscriptions.

---

# Core Requirement

Build a standalone push delivery engine.

The engine must NOT depend on:

- Post publishing
- Filament
- categories
- breaking-news logic
- campaigns
- analytics

Those systems will call this engine later.

The engine should be reusable by future NewsMan installations.

---

# Official FCM Transport Requirement

Use Firebase Cloud Messaging HTTP v1.

Do NOT implement the deprecated legacy server-key FCM API.

Expected endpoint architecture:

```text
POST
https://fcm.googleapis.com/v1/projects/{PROJECT_ID}/messages:send
```

Authentication must use an OAuth 2.0 bearer access token associated with an authorized Google/Firebase service account. citeturn502193search1turn502193search19

---

# Scope

Implement:

1. server-side Firebase configuration;
2. secure service-account credential handling;
3. OAuth access-token generation;
4. access-token caching;
5. FCM HTTP v1 client;
6. push message data object / DTO;
7. single-device delivery;
8. multi-subscription delivery;
9. Laravel queue job foundation;
10. invalid-token detection;
11. automatic invalid subscription deactivation;
12. transient vs permanent error classification;
13. retry-safe architecture;
14. structured delivery results;
15. development test command;
16. test doubles / HTTP fakes;
17. automated tests;
18. documentation.

---

# Strict Scope Boundary

Do NOT implement Phase 2.3D or later features.

Future phases:

- 2.3D — Post Publish Automation
- 2.3E — Filament Notification Panel
- 2.3F — Topics & Category Preferences
- 2.3G — Analytics & Click Tracking
- 2.3H — Queue, Security & Rate Limiting
- 2.3I — Testing & Production Deployment

---

# Do NOT Implement Yet

Do not implement:

- automatic notification when a post publishes;
- PostObserver notification code;
- PostPublished listener;
- Reporter/Editor workflow changes;
- breaking-news automatic notifications;
- category subscriptions;
- topic subscriptions;
- Filament push resource;
- push campaign builder;
- scheduling UI;
- click analytics;
- delivery analytics dashboard;
- CTR reporting;
- notification history system;
- campaign tables;
- full production rate-limiting system;
- topic messaging.

Phase 2.3C is the delivery engine only.

---

# Critical First Step — Audit Existing Implementation

Before changing any file, inspect:

```text
config/
app/Models/PushSubscription.php
app/Services/Push/
app/Http/
app/Jobs/
routes/
database/
resources/js/push/
tests/
composer.json
composer.lock
.env.example
.gitignore
```

Inspect the actual Phase 2.3A and Phase 2.3B implementation.

Do not assume file paths from previous prompts exactly match implementation.

Reuse established project conventions.

---

# Protected Existing Functionality

Do not break:

- homepage;
- articles;
- categories;
- tags;
- search;
- archives;
- authors;
- advertisements;
- YouTube playlist;
- authentication;
- Filament;
- role permissions;
- publishing workflow;
- image processing;
- SEO;
- sitemap;
- robots;
- analytics;
- Redis;
- queues;
- cache invalidation;
- imported WordPress content;
- existing FCM browser subscription flow.

Push delivery failure must never break normal website behavior.

---

# Architecture Target

Target service architecture:

```text
Application Feature
       ↓
PushMessage
       ↓
PushNotificationService
       ↓
FirebaseMessagingClient
       ↓
FirebaseAccessTokenProvider
       ↓
FCM HTTP v1
```

For multiple subscribers:

```text
PushNotificationService
       ↓
Active PushSubscription collection
       ↓
Queue / delivery jobs
       ↓
FirebaseMessagingClient
       ↓
FCM
```

---

# Design Principle

Keep these concerns separate:

```text
Authentication
Transport
Message construction
Subscription selection
Queue execution
Error classification
Subscription lifecycle
```

Do not build one giant Firebase service class.

---

# Step 1 — Review Existing Firebase Config

Phase 2.3A likely introduced:

```text
config/firebase.php
```

Inspect it.

Extend it carefully for server-side FCM configuration.

Do not mix public browser configuration with private server credentials conceptually.

Suggested configuration sections:

```php
return [

    'web' => [
        // existing browser-safe config
    ],

    'messaging' => [
        // server-side FCM config
    ],

];
```

Follow existing implementation if a better structure already exists.

---

# Step 2 — Server Environment Variables

Add required placeholders to:

```text
.env.example
```

Possible configuration:

```dotenv
FIREBASE_PROJECT_ID=
FIREBASE_SERVICE_ACCOUNT_PATH=
```

or an equivalent secure implementation.

Do not commit actual service account credentials.

---

# Step 3 — Credential Strategy

Prefer a filesystem path to a private service-account credential JSON file.

Example:

```dotenv
FIREBASE_SERVICE_ACCOUNT_PATH=/secure/path/firebase-service-account.json
```

The actual location must NOT be publicly accessible.

Never place a private credential file inside:

```text
public/
resources/js/
resources/views/
```

Do not expose service-account JSON to Vite.

---

# Step 4 — Do Not Commit Credential JSON

Update `.gitignore` where appropriate.

Ensure files such as:

```text
firebase-service-account.json
service-account.json
firebase-admin.json
```

cannot accidentally enter Git if those naming conventions are used.

Do not over-broaden `.gitignore` unnecessarily.

---

# Step 5 — Secure Deployment Location

Document a recommended production location outside the public web root.

For this project's production architecture, the credential could live in an application-private or shared secure directory.

Do not hardcode a developer-specific Windows or production server path in application code.

Use environment configuration.

---

# Step 6 — Credential Validation

At runtime, server-side messaging configuration should validate:

```text
project ID exists
credential path configured
file exists
file readable
credential JSON valid
required fields available
```

Failures must produce controlled exceptions.

Do not allow PHP warnings from `file_get_contents()` to leak as user-facing errors.

---

# Step 7 — Credential Data

Use only required service-account fields.

Typical required values include data necessary for OAuth authentication.

Never log:

```text
private_key
private_key_id
client_email credential contents
raw JSON
```

---

# Step 8 — Authentication Approach

Implement short-lived OAuth 2.0 access-token authentication for FCM HTTP v1.

Do not use:

```text
FCM_SERVER_KEY
Authorization: key=...
legacy FCM endpoint
```

Firebase's HTTP v1 API uses short-lived OAuth 2.0 bearer tokens. citeturn502193search1turn502193search8

---

# Step 9 — Dependency Audit

Before installing a new Composer package, inspect current dependencies.

Prefer:

- an already-installed Google authentication library if available;
- otherwise a small, maintained official/appropriate dependency.

Do not install an enormous Firebase PHP framework merely to send HTTP v1 messages unless clearly justified.

Do not blindly add Firebase Admin SDK if direct HTTP v1 integration fits existing Laravel architecture better.

---

# Step 10 — Google Authentication Dependency

If a Google OAuth library is needed, choose a maintained dependency compatible with PHP 8.3 and Laravel 12.

Record the reason in completion report.

Do not invent custom JWT cryptography if a reliable existing authentication library can handle service-account OAuth securely.

---

# Step 11 — FirebaseAccessTokenProvider

Create a focused service, for example:

```text
app/Services/Push/FirebaseAccessTokenProvider.php
```

Responsibilities:

```text
read secure credentials
obtain OAuth access token
cache token
return valid bearer token
```

It must NOT send FCM messages.

---

# Step 12 — Access Token Caching

OAuth access tokens are short-lived.

Avoid generating a new access token for every FCM message.

Cache access tokens using Laravel cache.

Suggested conceptual cache key:

```text
firebase:fcm:access-token:{project}
```

Do not store the service-account private key in cache.

---

# Step 13 — Token Expiry Safety Margin

If OAuth response contains an expiration time, cache the token for slightly less than its validity period.

Leave a reasonable safety margin.

Do not continue using a token after expected expiration.

---

# Step 14 — Cache Driver Compatibility

The implementation should work with existing Laravel cache configuration.

Production uses Redis.

Tests may use array cache.

Do not make the provider Redis-specific.

---

# Step 15 — Authentication Failure

Create meaningful domain exceptions.

Examples:

```text
FirebaseConfigurationException
FirebaseAuthenticationException
```

or project-consistent equivalents.

Do not throw generic exceptions everywhere.

---

# Step 16 — FirebaseMessagingClient

Create a transport-focused service such as:

```text
app/Services/Push/FirebaseMessagingClient.php
```

Responsibilities:

```text
receive prepared message
obtain OAuth token
send HTTP request
parse response
return structured result
```

Do not select subscribers here.

---

# Step 17 — Laravel HTTP Client

Prefer Laravel's HTTP client for FCM requests unless project conventions require otherwise.

This enables:

```text
timeouts
retry control
HTTP fakes
structured error handling
```

Avoid raw cURL scattered through services.

---

# Step 18 — FCM Endpoint

Build endpoint using configured project ID.

Conceptual endpoint:

```text
https://fcm.googleapis.com/v1/projects/{projectId}/messages:send
```

Do not accept project ID from user input.

---

# Step 19 — Authorization Header

Requests should use:

```text
Authorization: Bearer {oauth-access-token}
```

Do not use the old:

```text
Authorization: key={server-key}
```

pattern.

---

# Step 20 — Request Headers

Use appropriate headers such as:

```text
Accept: application/json
Content-Type: application/json
Authorization: Bearer ...
```

Use Laravel HTTP client idiomatically.

---

# Step 21 — Timeout

Set a finite timeout.

Do not allow FCM calls to hang indefinitely.

Choose a sensible value appropriate for queue workers.

Document it in config if configurable.

---

# Step 22 — Push Message Object

Create a dedicated immutable or strongly structured message object / DTO.

Possible name:

```text
PushMessage
```

Possible fields:

```text
title
body
image
url
icon
data
```

Keep the object generic.

Do not tie it directly to Post model.

---

# Step 23 — Minimum Message Requirements

At minimum support:

```text
title
body
```

Optional:

```text
image
url
icon
data
```

Do not require an article.

---

# Step 24 — Message Validation

Validate reasonable bounds before sending.

Prevent:

```text
empty title
empty body where required
invalid URL
arbitrarily huge data payload
```

Do not hardcode overly brittle limits without reason.

FCM supports notification and data message payloads; keep request construction explicit and controlled. citeturn502193search1turn502193search14

---

# Step 25 — FCM Message Payload

For a device token, construct:

```json
{
  "message": {
    "token": "FCM_TOKEN",
    "notification": {
      "title": "Title",
      "body": "Message"
    }
  }
}
```

Extend with web-push-specific data where needed.

---

# Step 26 — Webpush Configuration

Since this phase targets browser subscribers, support appropriate:

```text
webpush
```

payload options.

Potential configuration:

```text
notification icon
notification image
click link
```

Do not over-engineer advanced Web Push actions yet.

---

# Step 27 — Click URL

When a message includes a URL, structure it so the existing service worker/browser can eventually open the desired page.

Reuse the Phase 2.3A service-worker contract.

Do not implement analytics tracking parameters yet.

---

# Step 28 — Notification Icon

Allow optional icon.

Use a safe application default where configuration provides one.

Do not hardcode an asset path that does not exist.

---

# Step 29 — Notification Image

Support optional image URL.

The image should be an absolute publicly reachable URL.

Do not make FCM server-side engine responsible for image resizing.

Existing image system remains responsible for image generation.

---

# Step 30 — Data Payload

Support controlled custom key/value data.

Values sent through FCM data payload should be normalized appropriately.

Do not serialize arbitrary application objects into push payloads.

---

# Step 31 — Reserved Internal Data

If useful, reserve generic fields such as:

```text
url
type
entity_id
```

But do not create campaign analytics fields yet.

Keep them future-ready.

---

# Step 32 — PushDeliveryResult

Create a structured result object.

Suggested fields:

```text
success
message_id
http_status
error_code
error_message
token_invalid
retryable
```

Do not return raw HTTP response throughout the application.

---

# Step 33 — Successful Response

FCM HTTP v1 successful sends return a message identifier. citeturn502193search1

Capture it in delivery result.

Do not create notification analytics records yet.

---

# Step 34 — Error Parsing

Parse FCM errors defensively.

Consider:

```text
HTTP status
top-level error status
error details
FCM-specific errorCode
```

Do not rely solely on human-readable message text.

---

# Step 35 — Error Classification

Distinguish:

```text
permanent token error
invalid request
authentication/configuration error
quota/rate error
temporary server error
network error
```

Do not treat every failure equally.

---

# Step 36 — UNREGISTERED Handling

FCM `UNREGISTERED` indicates a registration token is no longer usable.

When a known subscription receives this permanent token failure:

```text
mark subscription inactive
set unsubscribed/invalidated timestamp as appropriate
```

Do not keep retrying that token indefinitely. citeturn502193search3turn502193search22

---

# Step 37 — Invalid Token Handling

Firebase documentation recommends removing or disabling registrations that are known to be invalid or expired based on FCM responses. citeturn502193search22

Implement this lifecycle without hard-deleting the subscription by default unless existing Phase 2.3B architecture specifies otherwise.

Prefer:

```text
is_active = false
```

and lifecycle timestamp/state.

---

# Step 38 — INVALID_ARGUMENT Caveat

Do not automatically deactivate a token merely because HTTP 400 `INVALID_ARGUMENT` occurred.

That status may represent malformed message payloads as well as token-related issues. citeturn502193search3

Only classify a token as invalid when the response clearly identifies registration-token invalidity.

---

# Step 39 — Authentication Errors

Authentication/config failures should NOT deactivate subscriber tokens.

Examples:

```text
invalid service account
expired/malformed authorization
wrong project configuration
permission problem
```

These are infrastructure failures.

---

# Step 40 — Quota Errors

Quota errors should not invalidate tokens.

Classify them as retryable where appropriate.

Actual large-scale throttling strategy belongs primarily to Phase 2.3H.

Firebase documents quota/token-bucket behavior for HTTP v1 sending, so keep the engine compatible with later throttling. citeturn502193search12

---

# Step 41 — Server Errors

HTTP 5xx failures should generally be considered transient.

Do not deactivate the subscription.

Allow queue retry architecture to handle them.

---

# Step 42 — Network Failure

Connection timeout / DNS / transport failure:

```text
retryable = true
```

Do not mark subscriber token invalid.

---

# Step 43 — PushNotificationService

Create application-level orchestration service such as:

```text
app/Services/Push/PushNotificationService.php
```

Responsibilities:

```text
sendToSubscription()
sendToSubscriptions()
coordinate transport
handle invalid token lifecycle
return delivery summaries
```

Do not handle OAuth internals here.

---

# Step 44 — Single Subscription Delivery

Implement:

```php
sendToSubscription(
    PushSubscription $subscription,
    PushMessage $message
)
```

or project-equivalent API.

Reject inactive subscriptions without calling FCM.

---

# Step 45 — Only Active Subscriptions

Do not send to:

```text
is_active = false
```

subscriptions.

Use the Phase 2.3B active scope if available.

---

# Step 46 — Missing Token

If a corrupt subscription lacks a usable token:

- do not send;
- return controlled failure;
- optionally deactivate if appropriate.

Do not crash the worker.

---

# Step 47 — Multi-Subscription Delivery

Support sending one `PushMessage` to a collection/query of subscriptions.

Do not assume all subscribers fit into memory.

Avoid:

```php
PushSubscription::active()->get();
```

for a potentially huge production audience.

Use chunking or queue fan-out architecture.

---

# Step 48 — Chunking

If a synchronous orchestrator needs to process subscriptions, use:

```text
chunkById
lazyById
```

or project-appropriate strategy.

Do not load 100,000 subscribers at once.

---

# Step 49 — Important HTTP v1 Consideration

Do not assume one HTTP v1 device send request can magically contain an arbitrary array of registration tokens.

Design per-token delivery cleanly.

Future topic messaging and larger broadcast strategy will be addressed in later phases.

---

# Step 50 — Queue Foundation

Create a queue job for individual or controlled-batch delivery.

Possible naming:

```text
SendPushNotificationJob
```

or:

```text
SendPushToSubscription
```

Use project naming conventions.

---

# Step 51 — Job Payload Safety

Do not serialize raw service-account credentials into jobs.

Do not pass access tokens into jobs.

Jobs should resolve services from the container when executed.

---

# Step 52 — Job Data

A job may contain:

```text
subscription ID
serializable PushMessage payload
```

Avoid serializing unnecessary Eloquent graphs.

---

# Step 53 — Missing Subscription During Job

If subscription was deleted or deactivated before the worker runs:

```text
exit successfully
```

Do not throw useless failures.

---

# Step 54 — Queue Name

Use an explicit queue if the project already organizes queues.

Possible:

```text
push
notifications
```

Inspect existing conventions before choosing.

Do not create incompatible Supervisor assumptions.

---

# Step 55 — Queue Connection

Do not hardcode Redis connection in job code.

Use Laravel queue configuration.

Production may use Redis; tests may use sync/fake.

---

# Step 56 — Job Retries

Set reasonable retry behavior for transient failures.

Do not retry permanent invalid-token errors.

Do not create an aggressive retry storm.

Advanced production tuning belongs to 2.3H.

---

# Step 57 — Backoff

Provide sensible backoff for retryable delivery errors.

Consider increasing delays.

Do not retry every second.

---

# Step 58 — Retry Classification

The job/service must know whether failure is:

```text
retryable
permanent
```

Do not throw on permanent token failures just to force job retries.

---

# Step 59 — Authentication Failure Retry

Authentication failures due to temporary OAuth retrieval issues may be retried.

Clearly invalid configuration should fail visibly rather than retry forever.

---

# Step 60 — FCM Access Token Refresh

If an FCM request fails specifically because cached OAuth authorization is invalid/expired, the client may:

```text
forget cached token
obtain a fresh token
retry once
```

Do not create infinite authentication loops.

---

# Step 61 — Test Notification Artisan Command

Create a development/admin command for manual engine verification.

Suggested:

```bash
php artisan push:test
```

or project-consistent equivalent.

---

# Step 62 — Command Purpose

The command should allow sending a test notification to a known subscription.

Possible options:

```text
--subscription=
--token-hash=
--user=
--title=
--body=
--url=
```

Keep it safe and practical.

---

# Step 63 — Do Not Require Raw Token CLI

Prefer identifying subscription by database ID or safe identifier.

Avoid putting a full FCM token in shell history.

---

# Step 64 — Default Test Message

If title/body options are omitted, use a clearly labeled test notification such as:

```text
Daily Samvad Push Test
Push notification engine is working.
```

This is fine for development.

Core service must remain brand-neutral.

---

# Step 65 — Command Confirmation

For production environment, require deliberate confirmation or `--force` before sending a manual test.

Do not accidentally broadcast anything.

---

# Step 66 — No Broadcast Command by Default

`push:test` must send to one explicitly selected subscription unless there is a clearly safe development-only option.

Do not make:

```bash
php artisan push:test
```

send to everyone.

---

# Step 67 — Command Output

Show:

```text
subscription ID
success/failure
FCM message ID if successful
error classification
```

Do NOT print:

```text
full raw FCM token
private key
access token
service account JSON
```

---

# Step 68 — Logging

Use structured logs for failures.

Safe fields:

```text
subscription_id
token_hash prefix
HTTP status
FCM error code
retryable
```

Never log:

```text
FCM raw token
OAuth bearer token
service-account private key
credential JSON
```

---

# Step 69 — Sensitive Error Responses

Be careful with HTTP exceptions that may include request headers.

Do not dump full Laravel HTTP request/response objects containing:

```text
Authorization: Bearer ...
```

into logs.

---

# Step 70 — Configuration Validation Command

If useful, allow:

```bash
php artisan push:test --check-config
```

or equivalent.

It can validate:

```text
project configured
credential file readable
OAuth access token obtainable
```

without sending a notification.

Implement only if it stays simple.

---

# Step 71 — Service Container

Register services cleanly if bindings are required.

Use existing Laravel service/provider conventions.

Do not build unnecessary custom providers if auto-resolution is sufficient.

---

# Step 72 — Interfaces

Introduce interfaces only where they materially improve:

```text
testability
transport abstraction
future provider swapping
```

Do not create abstraction for abstraction's sake.

---

# Step 73 — Suggested Interface

A useful interface could be:

```text
PushTransport
```

implemented by:

```text
FirebaseMessagingClient
```

But only do this if it results in cleaner testing/design.

---

# Step 74 — Future Provider Independence

The application-level service should ideally not require callers to understand Firebase-specific request details.

This supports future providers without rewriting post-publish business logic.

---

# Step 75 — Notification DTO Independence

`PushMessage` must not contain Firebase credential/configuration concerns.

It describes the notification, not the provider.

---

# Step 76 — Testing: Configuration Failure

Test missing:

```text
FIREBASE_PROJECT_ID
```

and/or missing credentials.

Expected:

```text
controlled configuration exception
no HTTP request
```

---

# Step 77 — Testing: OAuth Provider

Mock/fake authentication where practical.

Do not call Google OAuth servers during automated tests.

---

# Step 78 — Testing: FCM Success

Using Laravel HTTP fake:

Return successful FCM response.

Verify:

```text
correct endpoint
Authorization header present
correct message token
title
body
webpush fields
```

Do not assert actual secret bearer token in failure messages.

---

# Step 79 — Testing: Message ID

Verify successful delivery result captures returned message ID.

---

# Step 80 — Testing: UNREGISTERED

Fake an FCM `UNREGISTERED` response.

Verify:

```text
delivery result permanent failure
subscription deactivated
no retry requested
```

---

# Step 81 — Testing: Invalid Payload

Fake appropriate invalid request response.

Verify subscription is NOT automatically deactivated unless token invalidity is explicitly established.

---

# Step 82 — Testing: Authentication Error

Fake authorization failure.

Verify:

```text
subscription remains active
```

---

# Step 83 — Testing: FCM 500

Fake FCM server error.

Verify:

```text
retryable
subscription remains active
```

---

# Step 84 — Testing: Timeout / Connection Error

Simulate connection exception.

Verify:

```text
retryable
subscription remains active
```

---

# Step 85 — Testing: Inactive Subscription

Attempt send to:

```text
is_active = false
```

Expected:

```text
no FCM request
```

---

# Step 86 — Testing: Job

Use:

```text
Queue::fake()
```

where appropriate.

Verify delivery job can be dispatched.

Also directly test job handling with mocked service if practical.

---

# Step 87 — Testing: Missing Subscription During Job

Delete/deactivate subscription before job handles.

Expected:

```text
safe no-op
```

---

# Step 88 — Testing: Token Exposure

Ensure application response/log-oriented objects do not unnecessarily serialize full raw tokens.

Do not write brittle tests around logs unless beneficial.

---

# Step 89 — HTTP Fake Requirement

Automated test suite must not make real Firebase API calls.

All FCM HTTP interactions must be fakeable.

---

# Step 90 — OAuth Fakeability

Access-token provider should also be replaceable/mockable during tests.

Avoid static/global code that cannot be mocked.

---

# Step 91 — Message URL Test

Verify optional article/general URL gets placed in expected Web Push payload contract.

---

# Step 92 — Image Test

Verify optional image field can be included without requiring it.

---

# Step 93 — Basic Message Test

Verify notification with only:

```text
title
body
```

is valid.

---

# Step 94 — Data Payload Test

Verify controlled data can be added.

Do not allow nested arbitrary objects if FCM contract requires scalar/string values.

Normalize appropriately.

---

# Step 95 — Database Changes

Phase 2.3C should normally require:

```text
ZERO new notification-history tables
```

Do not create campaign/log/analytics tables.

---

# Step 96 — Existing Subscription Table Changes

Only modify `push_subscriptions` schema if a truly necessary lifecycle field is missing.

Before creating another migration, inspect Phase 2.3B.

Possible required addition might be something equivalent to:

```text
invalidated_at
last_delivery_error
```

But avoid schema changes unless required for the engine.

Analytics/logging belongs later.

---

# Step 97 — Prefer Existing Lifecycle Fields

If Phase 2.3B already has:

```text
is_active
unsubscribed_at
```

use them for invalid-token deactivation where semantically acceptable.

Document decision.

---

# Step 98 — No Delivery Counter Yet

Do not add:

```text
sent_count
delivered_count
click_count
```

to subscription table.

Analytics belongs to Phase 2.3G.

---

# Step 99 — No Campaign Model

Do not create:

```text
NotificationCampaign
PushCampaign
PushNotificationHistory
```

in this phase.

---

# Step 100 — No Post Dependency

Do not type-hint:

```php
Post $post
```

inside the generic push delivery engine.

Post → PushMessage conversion belongs to 2.3D.

---

# Step 101 — No Publish Observer

Do NOT modify:

```text
PostObserver
Post model events
publishing service
editor approval
review actions
Filament PostResource
```

to trigger pushes.

---

# Step 102 — No Topic Sending

Although FCM HTTP v1 supports topic targets, do not implement topics yet.

Phase 2.3F owns categories and topics. citeturn502193search1

---

# Step 103 — No Direct Browser Send

Server credentials must never be exposed to the browser.

Browser JS should NOT call:

```text
fcm.googleapis.com/v1/projects/.../messages:send
```

directly.

All send operations originate from trusted Laravel server code.

---

# Step 104 — Do Not Reuse Web API Key as Server Credential

Firebase Web API configuration from Phase 2.3A is not the server authorization mechanism for FCM HTTP v1 sending.

Keep server authentication separate.

---

# Step 105 — Documentation

Create/update:

```text
docs/push-notifications/laravel-push-engine.md
```

Use actual project documentation conventions if different.

---

# Step 106 — Documentation Contents

Document:

1. architecture;
2. FCM HTTP v1 transport;
3. server credentials;
4. OAuth access-token flow;
5. environment variables;
6. production credential placement;
7. test command;
8. queue architecture;
9. invalid-token handling;
10. retry behavior;
11. troubleshooting.

---

# Step 107 — Firebase Console Manual Setup

Document manual configuration required.

At minimum describe:

```text
Firebase project
Cloud Messaging API availability
service account
credential configuration
correct project ID
```

Do not include actual private credential content.

---

# Step 108 — Cloud Messaging API

Ensure documentation tells developer how to verify that FCM sending through HTTP v1 is enabled/available for the project.

Firebase's current server documentation uses the Cloud Messaging HTTP v1 API. citeturn502193search16turn502193search31

---

# Step 109 — Production Credential Instructions

Clearly explain that server credential configuration is separate from the browser Firebase configuration used in Phase 2.3A.

---

# Step 110 — Production Queue Documentation

Document that real bulk sending should be executed through Laravel workers.

Do not instruct developers to perform large subscriber sends synchronously from an HTTP request.

---

# Step 111 — Redis Compatibility

Ensure queue jobs work with existing Redis queue configuration.

Do not change Redis/global queue configuration unless necessary.

---

# Step 112 — Supervisor Awareness

Inspect existing deployment docs/configuration.

If push queue requires Supervisor worker configuration later, document the requirement.

Do not blindly edit live server Supervisor configuration in this phase.

---

# Step 113 — Queue Worker Example

Documentation may include a project-appropriate example such as:

```bash
php artisan queue:work --queue=push,default
```

only if it matches actual queue naming.

Do not assume this exact command without inspecting project conventions.

---

# Step 114 — Queue Restart Awareness

If code is deployed to long-running workers, document:

```bash
php artisan queue:restart
```

where appropriate.

---

# Step 115 — Manual Verification Flow

After real credentials are configured:

```text
1. Browser subscribes
2. Verify push_subscriptions row
3. Find subscription ID
4. Run push:test against that ID
5. Laravel obtains OAuth token
6. Laravel calls FCM HTTP v1
7. FCM returns message ID
8. Browser receives notification
```

---

# Step 116 — Manual Test Example

Provide actual syntax based on implemented command.

Example only:

```bash
php artisan push:test --subscription=1
```

Do not expose raw FCM token.

---

# Step 117 — Foreground Testing

Test while Daily Samvad is open.

Confirm Phase 2.3A foreground behavior remains functional.

If foreground display requires later work, document exact behavior.

Do not scope-creep unnecessarily.

---

# Step 118 — Background Testing

Test with browser tab:

```text
backgrounded
```

and if supported:

```text
closed
```

Verify service worker receives standard notification.

Document browser-dependent behavior.

---

# Step 119 — Click Testing

If a test message includes:

```text
url
```

verify click behavior follows existing Phase 2.3A service-worker implementation.

Do not implement click analytics.

---

# Step 120 — HTTPS Requirement Awareness

Production push testing must occur on the configured secure site.

Do not weaken HTTPS requirements.

---

# Step 121 — Error Message Quality

Artisan command and logs should distinguish:

```text
configuration error
authentication error
FCM rejection
invalid subscription
temporary FCM error
network error
```

Do not return only:

```text
Push failed
```

for everything.

---

# Step 122 — Exception Hygiene

Custom exceptions should not include:

```text
private keys
OAuth bearer tokens
raw credentials
```

in exception messages.

---

# Step 123 — HTTP Response Hygiene

If FCM returns a response containing diagnostic content, store/return only what is required.

Avoid dumping entire raw responses into production logs.

---

# Step 124 — Retry Header Awareness

If FCM provides server guidance such as retry timing, design result/exception architecture so later rate-limit work can use it.

Do not fully implement advanced throttling here.

---

# Step 125 — Scale Awareness

The engine must not assume only dozens of subscribers.

It should be structurally safe for:

```text
1,000
10,000
100,000+
```

subscriptions.

Do not synchronously loop every subscriber in a web controller.

---

# Step 126 — Queue Fan-Out Foundation

Provide a reusable method that can dispatch delivery jobs for a subscription query/collection without loading everything at once.

Keep it generic.

---

# Step 127 — No Massive Job Payload

For large sends, jobs should reference subscription identifiers and compact message data.

Do not put thousands of full Eloquent models into one serialized job.

---

# Step 128 — No Duplicate Send Protection Yet

Campaign-level idempotency/deduplication will be designed later.

Do not create an entire campaign delivery ledger now.

However each individual job should avoid obvious duplicate work caused by its own code.

---

# Step 129 — Failed Jobs

Use Laravel queue failure behavior.

Do not create custom failed-job infrastructure unless project already uses it.

---

# Step 130 — Service Testability

Every important service should be testable without:

```text
real Firebase credentials
real FCM token
internet access
```

---

# Step 131 — Static Analysis / Formatting

Use existing project tooling.

If Pint exists:

```bash
./vendor/bin/pint
```

Run existing static analysis if project already uses it.

Do not introduce unrelated tooling.

---

# Step 132 — Composer Validation

If composer dependencies change, run:

```bash
composer validate
```

Ensure lockfile is updated correctly.

---

# Step 133 — Laravel Tests

Run targeted push tests first.

Then run:

```bash
php artisan test
```

where practical.

Do not hide pre-existing failures.

Differentiate:

```text
new regression
pre-existing failure
environment failure
```

---

# Step 134 — Frontend Build

This phase is primarily backend.

If no JS files change, frontend build may not be technically required.

However run:

```bash
npm run build
```

if project validation conventions require it or if service-worker/browser integration was touched.

---

# Step 135 — Cache Validation

Run:

```bash
php artisan optimize:clear
```

in appropriate development context if configuration changed.

Verify configuration can be cached:

```bash
php artisan config:cache
```

if safe for the development environment.

Then clear as appropriate.

---

# Step 136 — Queue Serialization Test

Ensure `PushMessage` / job payload serializes correctly with configured queue connection.

---

# Step 137 — Database Safety

Do NOT run:

```bash
php artisan migrate:fresh
```

against existing project data.

No destructive DB reset.

---

# Step 138 — Git Safety

Before completion run:

```bash
git status
git diff --stat
```

Inspect every changed file.

---

# Step 139 — Secret Scan

Ensure Git does not contain:

```text
service account private key
firebase credential JSON
OAuth bearer token
FCM raw production token
.env
database dump
```

---

# Step 140 — Private Key Pattern Check

Where practical search changed files for:

```text
BEGIN PRIVATE KEY
private_key
```

Any credential-like value found must be investigated.

Configuration code/reference names are fine.

Actual key material is not.

---

# Step 141 — Web Credential Separation

Confirm browser build output contains no server credential fields.

Vite must never bundle:

```text
private_key
service account JSON
OAuth access token
```

---

# Step 142 — Definition of Done

Phase 2.3C is complete only when:

- server Firebase configuration exists;
- service-account credential strategy is documented;
- no real server credential is committed;
- OAuth access-token provider works;
- access token is cached safely;
- HTTP v1 messaging client exists;
- no deprecated server-key API is used;
- PushMessage abstraction exists;
- basic notification payload works;
- optional image works;
- optional URL works;
- optional data payload works;
- structured delivery result exists;
- single active subscription send works;
- inactive subscription is skipped;
- multi-subscription orchestration foundation exists;
- queue delivery job exists;
- transient failures can retry;
- permanent token failure does not retry forever;
- UNREGISTERED token is deactivated;
- authentication failure does not deactivate subscriber;
- server failure does not deactivate subscriber;
- test notification Artisan command exists;
- test command cannot broadcast accidentally;
- automated tests use fakes;
- test suite does not contact Firebase;
- logs do not expose secrets/tokens;
- documentation exists;
- existing browser subscription flow still works;
- no publish automation has been implemented;
- no Filament push panel has been implemented;
- no topics have been implemented;
- no analytics have been implemented.

---

# Expected Architecture

The final architecture should approximately resemble:

```text
app/
├── Console/
│   └── Commands/
│       └── PushTestCommand.php
│
├── Data/
│   └── Push/
│       ├── PushMessage.php
│       └── PushDeliveryResult.php
│
├── Exceptions/
│   └── Push/
│       ├── FirebaseConfigurationException.php
│       ├── FirebaseAuthenticationException.php
│       └── PushDeliveryException.php
│
├── Jobs/
│   └── Push/
│       └── SendPushNotificationJob.php
│
└── Services/
    └── Push/
        ├── FirebaseAccessTokenProvider.php
        ├── FirebaseMessagingClient.php
        └── PushNotificationService.php
```

This is guidance only.

Follow existing project namespaces and conventions.

---

# Expected Configuration Shape

Possible conceptual structure:

```php
'web' => [
    // Phase 2.3A browser-safe settings
],

'messaging' => [
    'project_id' => env('FIREBASE_PROJECT_ID'),
    'service_account_path' => env('FIREBASE_SERVICE_ACCOUNT_PATH'),
    'timeout' => 10,
],
```

Do not overwrite working Phase 2.3A configuration unnecessarily.

---

# Required Test Coverage Summary

At minimum cover:

```text
FCM success
OAuth/auth mock
message payload
inactive subscription
UNREGISTERED token
invalid message error
authentication failure
HTTP 5xx
network/timeout failure
queue dispatch
job execution
missing/deactivated subscription
configuration missing
test command safety
```

---

# Required Validation Commands

Use the applicable project commands:

```bash
composer validate
```

```bash
php artisan optimize:clear
```

```bash
php artisan test
```

```bash
./vendor/bin/pint
```

If Composer dependencies changed:

```bash
composer install
```

or appropriate dependency installation command.

If frontend files were affected:

```bash
npm run build
```

Do not perform production deployment from this phase prompt.

---

# Completion Report Required

At completion provide a structured report.

## 1. Phase Summary

Explain what was implemented.

---

## 2. Existing Architecture Audit

Explain what was found from Phases 2.3A and 2.3B and how this phase reused them.

---

## 3. FCM Authentication

Report:

```text
credential strategy
OAuth mechanism
access-token caching
cache lifetime strategy
```

Do not expose credential values.

---

## 4. FCM Transport

Report:

```text
HTTP v1 endpoint architecture
HTTP client
timeout
error parsing
```

---

## 5. Push Message Contract

Describe supported fields:

```text
title
body
image
url
icon
data
```

---

## 6. Delivery Result

Describe:

```text
success
message ID
error code
retryable classification
invalid-token classification
```

---

## 7. Subscription Lifecycle

Explain what happens for:

```text
active token
inactive token
UNREGISTERED token
temporary FCM failure
authentication failure
```

---

## 8. Queue Architecture

Report:

```text
job name
queue name
retry strategy
backoff
job payload
```

---

## 9. Test Command

Provide exact command usage.

For example:

```bash
php artisan push:test --subscription=123
```

Use the actual implementation syntax.

---

## 10. Files Created

List every created file.

---

## 11. Files Modified

List every modified file and why.

---

## 12. Composer Changes

List any new dependency and why it was required.

---

## 13. Environment Variables

List required environment variable names only.

Do not expose actual values.

---

## 14. Security Review

Explicitly confirm:

- service account file not committed;
- private key not committed;
- OAuth access token not logged;
- raw FCM tokens not logged;
- server credential not exposed to Vite/browser;
- legacy FCM server key not used.

---

## 15. Tests

Report:

```text
tests added
tests executed
tests passed
tests failed
```

---

## 16. Validation Results

Report:

```text
composer validate
php artisan test
Pint
npm run build if applicable
```

---

## 17. Real Firebase Test

State whether a real device notification was tested.

If credentials were unavailable, state:

```text
Engine implemented and automated tests pass;
real Firebase delivery requires production/development service-account configuration.
```

Do NOT mark the phase blocked merely because private production credentials were intentionally unavailable during coding if the engine can be correctly implemented and tested with fakes.

---

## 18. Scope Verification

Explicitly confirm these were NOT implemented:

```text
Post publish automation
Breaking news auto-push
Filament notification panel
Category/topic preferences
Campaign management
Click analytics
Delivery analytics dashboard
```

---

## 19. Phase 2.3D Readiness

Explain the clean application API that Phase 2.3D should call.

For example:

```text
Post Published
     ↓
create PushMessage
     ↓
PushNotificationService
```

Do not implement that integration yet.

---

## 20. Risks / Follow-ups

Mention any:

```text
Firebase configuration issue
queue configuration requirement
worker deployment requirement
service-worker limitation
credential deployment requirement
FCM quota consideration
```

---

## 21. Final Status

Finish with exactly one:

```text
PHASE 2.3C COMPLETE
```

or:

```text
PHASE 2.3C BLOCKED
```

If blocked, state the exact blocking issue.

---

# Final Instruction

Fully implement:

# Phase 2.3C — Laravel Push Notification Engine

Do not merely audit the repository.

Inspect the actual Phase 2.3A and Phase 2.3B implementations first.

Then make the required code changes.

The final working architecture must be:

```text
PushSubscription
       +
PushMessage
       ↓
PushNotificationService
       ↓
Queue Job
       ↓
FirebaseMessagingClient
       ↓
OAuth 2.0
       ↓
FCM HTTP v1
       ↓
Browser
```

Build the engine so Phase 2.3D can later connect Post publishing to it without rewriting the transport layer.

Do NOT begin Phase 2.3D.

Do NOT integrate notifications into Post publishing yet.

Do NOT build the Filament notification panel yet.

Do NOT implement topics, campaigns, or analytics.

Run all relevant tests and validation commands.

Fix regressions introduced by this phase.

Provide the full completion report.

End with exactly:

`PHASE 2.3C COMPLETE`

or:

`PHASE 2.3C BLOCKED`