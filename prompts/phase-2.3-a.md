# Phase 2.3A — Firebase & Browser Push Notification Foundation

## Project

Daily Samvad — WordPress to Laravel Migration / NewsMan CMS Foundation

Current stack includes:

- Laravel 12
- PHP 8.3+
- Blade frontend
- Livewire where already applicable
- Filament admin
- Vite
- Redis / queues available
- Existing role-based publishing workflow
- Existing media/image system
- Existing caching architecture
- Production deployment on `dailysamvad.com`

This phase begins **Version 2.3 — Push Notification System**.

---

# Objective

Implement the safe browser-side and Firebase foundation required for future push notifications.

This phase must establish:

1. Firebase Web SDK integration
2. Firebase Cloud Messaging browser client foundation
3. environment-based Firebase configuration
4. browser capability detection
5. service-worker registration
6. notification permission flow
7. FCM token retrieval foundation
8. frontend notification opt-in UI
9. clean JavaScript architecture
10. graceful unsupported-browser handling
11. safe development behavior
12. basic automated tests where practical
13. documentation for Firebase Console configuration

This is **foundation only**.

Do not implement the complete subscription database or notification delivery engine yet.

---

# Important Scope Boundary

Phase 2.3A must NOT implement functionality assigned to later phases.

Later phases are:

- 2.3B — Subscription Management
- 2.3C — Laravel Push Engine
- 2.3D — Post Publish Automation
- 2.3E — Filament Notification Panel
- 2.3F — Topics & Category Preferences
- 2.3G — Analytics & Click Tracking
- 2.3H — Queue, Security & Rate Limiting
- 2.3I — Testing & Production Deployment

Do not prematurely implement those features.

---

# Critical Development Rule

Before modifying code:

1. inspect the existing project architecture;
2. inspect current Vite configuration;
3. inspect `resources/js`;
4. inspect frontend layouts;
5. inspect existing Blade components;
6. inspect environment/config conventions;
7. inspect existing service workers or PWA files;
8. inspect existing tests;
9. inspect caching middleware/services;
10. identify any existing Firebase-related package or code.

Do not blindly replace existing architecture.

Reuse existing project patterns wherever reasonable.

---

# Protected Existing Functionality

The following must continue to work unchanged:

- homepage
- article pages
- categories
- tags
- search
- author pages
- date archives
- advertisements
- image optimization
- responsive images
- OG images
- YouTube playlist player
- SEO
- sitemap
- robots
- authentication
- Filament
- reporter/editor/reviewer publishing workflow
- Redis
- caching
- queues
- analytics already present
- imported WordPress media
- imported posts
- existing production routes

Do not modify unrelated systems merely to accommodate push notifications.

---

# Architecture Goal

Target architecture for this phase:

```text
Browser
   │
   ├── Feature Detection
   │
   ├── Notification Permission UI
   │
   ├── Firebase App
   │
   ├── Firebase Messaging
   │
   └── Service Worker
          │
          ▼
     FCM Registration Token
```

At the end of this phase it should be possible to obtain an FCM token in a supported browser.

Persistence of that token into our application database belongs to Phase 2.3B.

---

# Step 1 — Audit Current Frontend Setup

Inspect:

```text
package.json
vite.config.*
resources/js/
resources/views/
resources/views/layouts/
resources/views/components/
public/
config/
.env.example
tests/
```

Search for:

```text
firebase
messaging
service-worker
serviceWorker
Notification
PushManager
manifest
pwa
```

If an existing service worker exists, do not create a competing registration without first understanding it.

Prefer integrating with an existing worker when technically appropriate.

Document the decision in the completion report.

---

# Step 2 — Install Firebase SDK

Use the current modular Firebase JavaScript SDK through npm.

Expected dependency:

```bash
npm install firebase
```

Do not use legacy namespaced Firebase APIs.

Avoid global:

```javascript
firebase.initializeApp(...)
```

Prefer modular imports such as:

```javascript
import { initializeApp } from 'firebase/app';
import {
    getMessaging,
    getToken,
    isSupported,
} from 'firebase/messaging';
```

Use only required modules.

Do not load the entire Firebase bundle unnecessarily.

---

# Step 3 — Laravel Configuration

Create a dedicated configuration file where appropriate:

```text
config/firebase.php
```

Suggested structure:

```php
<?php

return [

    'web' => [

        'api_key' => env('FIREBASE_WEB_API_KEY'),

        'auth_domain' => env('FIREBASE_WEB_AUTH_DOMAIN'),

        'project_id' => env('FIREBASE_PROJECT_ID'),

        'storage_bucket' => env('FIREBASE_STORAGE_BUCKET'),

        'messaging_sender_id' => env('FIREBASE_MESSAGING_SENDER_ID'),

        'app_id' => env('FIREBASE_WEB_APP_ID'),

        'measurement_id' => env('FIREBASE_MEASUREMENT_ID'),

        'vapid_key' => env('FIREBASE_VAPID_KEY'),

    ],

];
```

Adjust naming to match existing project conventions if necessary.

Do not expose Firebase server credentials in browser configuration.

---

# Step 4 — Environment Variables

Add placeholders to `.env.example`.

Example:

```dotenv
FIREBASE_WEB_API_KEY=
FIREBASE_WEB_AUTH_DOMAIN=
FIREBASE_PROJECT_ID=
FIREBASE_STORAGE_BUCKET=
FIREBASE_MESSAGING_SENDER_ID=
FIREBASE_WEB_APP_ID=
FIREBASE_MEASUREMENT_ID=
FIREBASE_VAPID_KEY=
```

Do not put actual production credentials into Git.

Do not commit:

- Firebase service-account JSON
- private keys
- OAuth secrets
- server credentials
- sensitive credentials

Remember that Firebase Web configuration and server credentials are different concepts.

---

# Step 5 — Expose Browser-Safe Configuration

Create a clean mechanism to provide only browser-safe Firebase configuration to JavaScript.

Follow the architecture already used by this project where practical.

Possible approaches include:

- Blade data attributes
- JSON script configuration
- Vite environment variables
- a dedicated browser-safe config object

Prefer one central source.

Avoid duplicating Firebase configuration in multiple JavaScript files.

Example desired browser object:

```javascript
{
    apiKey: '...',
    authDomain: '...',
    projectId: '...',
    storageBucket: '...',
    messagingSenderId: '...',
    appId: '...'
}
```

VAPID public key should also be available to the messaging client.

---

# Step 6 — Create Firebase Client Module

Create a dedicated module such as:

```text
resources/js/push/firebase.js
```

or another project-consistent path.

Responsibilities:

- initialize Firebase once;
- expose Firebase app safely;
- detect missing configuration;
- avoid initialization crashes;
- avoid duplicate Firebase app initialization.

Suggested conceptual API:

```javascript
export function getFirebaseApp()
```

Do not initialize Firebase repeatedly from individual Blade components.

---

# Step 7 — Create Messaging Client

Create a dedicated module such as:

```text
resources/js/push/messaging.js
```

Responsibilities should include:

```text
isPushSupported()
getMessagingClient()
registerMessagingServiceWorker()
requestNotificationPermission()
getMessagingToken()
```

Keep these responsibilities isolated.

Do not create one giant `app.js` implementation.

---

# Step 8 — Browser Capability Detection

Push notifications must fail gracefully.

Detect support for relevant browser APIs.

At minimum consider:

```javascript
'Notification' in window
'serviceWorker' in navigator
```

Use Firebase messaging support detection where appropriate.

Potential states:

```text
supported
unsupported
permission-default
permission-granted
permission-denied
configuration-missing
service-worker-error
token-error
```

Expose these states cleanly to the frontend.

---

# Step 9 — Notification Permission UX

Critical requirement:

**Do not request browser notification permission automatically on initial page load.**

Forbidden behavior:

```javascript
window.onload = () => Notification.requestPermission();
```

Do not trigger browser permission without user interaction.

Instead show a user-controlled CTA.

Example:

```text
🔔 Get Daily Samvad News Alerts
```

or:

```text
Latest News Alerts Enable Karein
```

User interaction flow:

```text
Visitor sees CTA
      ↓
Clicks Enable
      ↓
Browser permission requested
      ↓
Granted / Denied
      ↓
UI updates accordingly
```

---

# Step 10 — Reusable Notification Opt-in Component

Create a reusable frontend component consistent with the existing application.

Possible example:

```text
resources/views/components/frontend/push-notification-opt-in.blade.php
```

Use the project's existing component naming conventions instead if different.

Suggested UI states:

### Default

```text
🔔 Latest News Alerts
Daily Samvad ki important khabrein turant paayen.

[Enable Notifications]
```

### Permission Granted

```text
✓ News notifications enabled
```

### Permission Denied

```text
Notifications browser settings mein blocked hain.
```

### Unsupported

Either hide the CTA or show a subtle unsupported message.

Do not display technical Firebase errors to normal visitors.

---

# Step 11 — Styling

Reuse the project's existing:

- Tailwind classes
- design tokens
- spacing system
- typography
- button styles
- responsive rules

Do not create an isolated visual design system.

The component should work on:

- desktop
- tablet
- mobile

Avoid layout shift.

Avoid intrusive full-screen permission modals.

A compact banner/card/button implementation is preferred.

---

# Step 12 — CTA Placement

Determine a sensible initial location by inspecting the current frontend.

Good candidates:

```text
article page
sidebar
footer
header utility area
```

For Phase 2.3A, implement conservatively.

Do not inject the opt-in component everywhere.

Prefer one or two sensible placements where it does not disrupt the existing homepage/article layout.

Explain chosen placement in completion report.

---

# Step 13 — Service Worker

Implement Firebase messaging service worker support.

Expected browser-accessible path should typically resolve to something similar to:

```text
/firebase-messaging-sw.js
```

Ensure it is served from an appropriate scope.

The worker must not break:

- Laravel routing
- Vite assets
- existing service worker
- caching
- Livewire
- frontend navigation

If an existing application service worker exists, evaluate whether Firebase messaging should be integrated into it.

Do not register multiple workers with conflicting scopes without justification.

---

# Step 14 — Service Worker Firebase Configuration

The service worker requires browser-safe Firebase configuration.

Do not insert private server credentials.

If configuration must be generated into a public file, design the implementation so that deployment remains predictable.

Avoid hardcoding environment-specific production values in tracked source code.

If Laravel-generated configuration is required, document exactly how production deployment generates it.

Prefer the simplest secure architecture compatible with the existing stack.

---

# Step 15 — Service Worker Registration

Create a dedicated registration function.

Example conceptual behavior:

```javascript
const registration =
    await navigator.serviceWorker.register('/firebase-messaging-sw.js');
```

Do not assume registration succeeds.

Handle:

```text
success
registration failure
unsupported browser
wrong scope
HTTPS requirement
development environment
```

Return the registration object to token retrieval logic.

---

# Step 16 — FCM Token Retrieval

After:

1. browser support confirmed;
2. user permission granted;
3. service worker registered;

retrieve the FCM registration token.

Conceptually:

```javascript
const token = await getToken(messaging, {
    vapidKey,
    serviceWorkerRegistration: registration,
});
```

The exact implementation should follow the installed Firebase SDK API.

Do not store the token permanently in this phase.

For Phase 2.3A the goal is simply to prove that the browser can obtain it.

---

# Step 17 — Never Log Production Token Publicly

During development, token debugging may be needed.

Avoid:

```javascript
console.log(token);
```

in production code unless protected behind development mode.

Prefer:

```javascript
if (import.meta.env.DEV) {
    console.debug(...);
}
```

Even during development, avoid unnecessary exposure.

No FCM token should appear in normal page HTML.

---

# Step 18 — Token Result Contract

Create a predictable result shape.

For example:

```javascript
{
    status: 'granted',
    token: '...',
}
```

or:

```javascript
{
    status: 'denied',
    token: null,
}
```

Possible statuses:

```text
granted
denied
unsupported
configuration-missing
registration-failed
token-failed
```

Phase 2.3B will consume this contract.

Design it accordingly.

---

# Step 19 — Permission State Handling

Handle all browser permission states:

```text
Notification.permission === 'default'
Notification.permission === 'granted'
Notification.permission === 'denied'
```

Rules:

### default

Show opt-in CTA.

### granted

Do not repeatedly request permission.

Attempt token initialization when appropriate.

### denied

Do not repeatedly show aggressive prompts.

Show subtle guidance if the component is visible.

---

# Step 20 — User Rejection Respect

Once a user denies browser notifications:

- do not continuously request again;
- do not open repeated custom modals;
- do not attempt to bypass browser permissions.

Respect the browser permission state.

---

# Step 21 — Local UI Dismissal

The custom Daily Samvad notification CTA may optionally support dismissal.

If implemented, use a lightweight client-side flag such as localStorage.

Example concept:

```text
daily_samvad_push_prompt_dismissed
```

Do not confuse dismissal with browser notification permission.

They are separate states.

The implementation should allow future UX changes.

---

# Step 22 — Foreground Messaging Foundation

Prepare a clean place for future foreground message handling.

For example:

```javascript
export function registerForegroundMessageHandler(...)
```

However:

Do not implement the complete production notification display system in this phase if it belongs to Phase 2.3C.

Only create foundation if necessary.

Avoid speculative code.

---

# Step 23 — Background Notification Foundation

The service worker may contain the minimum required setup for receiving future messages.

Do not build advanced features yet such as:

- category routing
- click analytics
- custom notification actions
- notification grouping
- notification deduplication
- campaign attribution

Those belong to later phases.

---

# Step 24 — Click Behavior Preparation

Basic notification click compatibility may be prepared if required by Firebase/browser behavior.

Future desired behavior:

```text
Push notification
      ↓
User clicks
      ↓
Article URL opens
```

Do not implement click analytics.

That belongs to Phase 2.3G.

---

# Step 25 — HTTPS Awareness

Push notifications on production should assume a secure origin.

Add a clear development/runtime guard so failures are understandable.

Do not produce fatal frontend errors if FCM is unavailable due to environment.

Local development should remain usable.

---

# Step 26 — Production Cache Compatibility

Daily Samvad currently uses a caching stack.

Push scripts and service worker integration must not cause stale configuration problems.

Inspect existing:

- Laravel cache headers
- Nginx assumptions where represented in repository
- Varnish-related configuration/docs
- Cloudflare-related configuration/docs

Do not make server configuration changes in this phase unless repository configuration clearly requires it.

Document any production cache rule that will later be required for:

```text
/firebase-messaging-sw.js
```

The service worker must not become permanently stale due to aggressive caching.

---

# Step 27 — Vite Integration

Integrate Firebase with the existing Vite build.

Ensure:

```bash
npm run build
```

works.

Do not create CDN Firebase imports in Blade if the app already uses npm/Vite.

Keep JavaScript tree-shakeable.

Avoid unnecessary bundle growth.

---

# Step 28 — Main JavaScript Integration

If existing:

```text
resources/js/app.js
```

is the frontend entry point, import only the push bootstrap/init module from there.

Example architecture:

```text
resources/js/app.js
        ↓
resources/js/push/index.js
        ↓
firebase.js
messaging.js
ui.js
```

Do not fill `app.js` with Firebase implementation details.

---

# Step 29 — Push Bootstrap

Create a lightweight bootstrap module.

Suggested responsibility:

```javascript
initializePushNotifications()
```

It should:

1. inspect capability;
2. inspect current permission;
3. bind CTA events;
4. initialize UI state;
5. avoid requesting permission automatically.

---

# Step 30 — Avoid Unnecessary Requests

Do not fetch a new FCM token continuously on every page interaction.

Create a sensible foundation for later token lifecycle management.

Persistent token handling will be implemented in 2.3B.

At this stage, minimize unnecessary messaging API calls.

---

# Step 31 — Error Handling

JavaScript errors must not break the website.

Wrap Firebase-specific operations appropriately.

A failed push setup must never break:

- menus
- article rendering
- ads
- Livewire
- sliders
- other JavaScript

Push notification functionality is an enhancement.

The website must remain fully usable without it.

---

# Step 32 — Logging

Development logging can use clear prefixes such as:

```text
[Push]
[Firebase Messaging]
```

Production console noise should be minimized.

Never expose secrets.

---

# Step 33 — Accessibility

Opt-in UI must be accessible.

Requirements:

- semantic button
- keyboard accessible
- meaningful text
- visible focus state
- proper disabled state while processing
- status messages understandable without icons
- no icon-only control without accessible label

---

# Step 34 — Processing State

When permission/token setup is running:

```text
Enable Notifications
        ↓
Enabling...
```

Disable repeated clicks.

Avoid duplicate concurrent `getToken()` calls.

---

# Step 35 — Configuration Missing State

If Firebase environment configuration has not yet been entered:

- application must still load;
- frontend should not crash;
- opt-in may remain hidden or disabled;
- developer warning may be emitted in development.

This allows code deployment before Firebase Console configuration is completed.

---

# Step 36 — Laravel Helper / Config Exposure

If a Blade helper, View Composer, service, or config transformer is required, keep it small.

Do not create a large backend notification service yet.

Backend sending belongs to Phase 2.3C.

---

# Step 37 — CSP Awareness

Inspect whether the project currently defines Content Security Policy headers.

If CSP exists:

- ensure Firebase-related requirements are compatible;
- change only what is required;
- do not weaken CSP globally without justification.

Document changes.

---

# Step 38 — Firebase Console Setup Documentation

Create documentation such as:

```text
docs/push-notifications/firebase-setup.md
```

or the project's existing documentation location.

Document the manual Firebase Console steps required from the project owner.

Include:

1. create/select Firebase project;
2. register Web application;
3. locate Firebase Web configuration;
4. enable Cloud Messaging as required;
5. create/configure Web Push certificate / VAPID public key;
6. copy required values to Laravel `.env`;
7. clear Laravel config cache;
8. rebuild Vite assets if needed;
9. verify service worker path;
10. test browser permission;
11. confirm token generation.

Do not place actual credentials in documentation.

---

# Step 39 — Example Configuration

Documentation may show placeholders:

```dotenv
FIREBASE_WEB_API_KEY=your-web-api-key
FIREBASE_WEB_AUTH_DOMAIN=your-project.firebaseapp.com
FIREBASE_PROJECT_ID=your-project-id
FIREBASE_STORAGE_BUCKET=your-storage-bucket
FIREBASE_MESSAGING_SENDER_ID=your-sender-id
FIREBASE_WEB_APP_ID=your-app-id
FIREBASE_MEASUREMENT_ID=
FIREBASE_VAPID_KEY=your-public-vapid-key
```

Clearly indicate these are examples.

---

# Step 40 — Do Not Add Firebase Admin SDK Yet

Phase 2.3A is browser foundation.

Do not install or configure Firebase Admin server credentials unless absolutely required for something within this phase.

Server-side FCM sending will be implemented later.

---

# Step 41 — No Service Account JSON

Do not create:

```text
firebase-service-account.json
service-account.json
google-services.json
```

for web client messaging foundation.

If any credential file already exists, do not expose or modify it unnecessarily.

---

# Step 42 — Git Safety

Verify that no secret file becomes tracked.

Inspect `.gitignore`.

Add appropriate patterns if genuinely needed.

Never commit real `.env`.

---

# Step 43 — Tests

Add focused tests where meaningful.

Potential Laravel tests:

### FirebaseConfigTest

Verify browser Firebase configuration infrastructure behaves correctly.

### PushComponentRenderTest

Verify the opt-in component can render without Firebase credentials.

### PageRegressionTest

Verify key frontend routes still render.

Do not create brittle tests that depend on contacting Firebase.

---

# Step 44 — JavaScript Testing

If the project already has a JavaScript test framework, add appropriate unit tests for pure logic such as:

- permission-state mapping;
- unsupported-browser handling;
- config validation.

Do not add an entire new JS test framework just for this phase unless clearly justified.

---

# Step 45 — Manual Test Matrix

Document manual validation for:

```text
Chrome desktop
Edge desktop
Chrome Android
Safari where available
Unsupported/private environments
```

Test:

```text
CTA visible
CTA click
permission default
permission granted
permission denied
service-worker registration
FCM token generation
page reload
no duplicate prompt
no JS crashes
```

---

# Step 46 — Browser Permission Reset Testing

Document how developers can reset notification permission during testing using browser site settings.

Do not attempt to reset browser permission programmatically.

---

# Step 47 — Build Validation

Run:

```bash
npm install
npm run build
```

or use the project-standard package command if different.

Then run:

```bash
php artisan optimize:clear
```

during local verification if appropriate.

Do not blindly run production deployment commands against the developer machine.

---

# Step 48 — Laravel Validation

Run relevant:

```bash
php artisan test
```

If full test suite is too large, run targeted tests first and then the full suite if practical.

Also run:

```bash
php artisan route:list
```

only if useful for regression verification.

---

# Step 49 — Code Formatting

Run the project's existing formatter.

For example, if Laravel Pint is configured:

```bash
./vendor/bin/pint
```

Do not introduce an unrelated formatter.

---

# Step 50 — No Database Migration

Phase 2.3A should normally require:

```text
ZERO database migrations
```

Do not create:

```text
push_subscriptions
push_topics
push_notifications
```

yet.

Those belong to later phases.

If you determine a database change is absolutely required, stop and clearly justify it before implementing because it would indicate scope leakage.

---

# Step 51 — No Notification Sending API

Do not create:

```text
FirebasePushService
SendPushNotificationJob
SendPostPushNotification
NotificationCampaign
```

yet.

These belong to subsequent phases.

---

# Step 52 — No Publish Event Integration

Do not modify:

```text
PostObserver
PostPublished event
editor workflow
review workflow
publish action
```

to send notifications.

That belongs to Phase 2.3D.

---

# Step 53 — No Filament Panel

Do not create:

```text
PushNotificationResource
NotificationCampaignResource
PushDashboard
```

in this phase.

That belongs to Phase 2.3E.

---

# Step 54 — No Topics

Do not implement:

```text
Punjab
Politics
Sports
Business
Entertainment
Breaking News
```

subscription preferences yet.

Those belong to Phase 2.3F.

---

# Step 55 — No Analytics

Do not implement:

```text
delivered
opened
clicked
CTR
campaign reports
```

yet.

Those belong to Phase 2.3G.

---

# Step 56 — Performance

Push foundation must have minimal impact on page speed.

Goals:

- no render-blocking Firebase scripts;
- no unnecessary Firebase initialization before needed;
- no permission popup on load;
- no synchronous remote calls blocking page rendering.

Prefer deferred/lazy initialization where sensible.

---

# Step 57 — Progressive Enhancement

The architecture must follow:

```text
Website works
     +
Push notifications enhance it
```

not:

```text
Push fails
     =
Website fails
```

This principle is mandatory.

---

# Step 58 — Future NewsMan Reusability

Although implementation is for Daily Samvad, avoid unnecessarily hardcoding:

```text
dailysamvad.com
Daily Samvad
```

inside core Firebase logic.

Brand-specific text can remain in Blade/UI.

Core push JavaScript should be reusable for future NewsMan CMS installations.

---

# Step 59 — Expected File Shape

Actual files should follow existing architecture, but a likely implementation may resemble:

```text
config/
└── firebase.php

resources/
├── js/
│   └── push/
│       ├── index.js
│       ├── firebase.js
│       ├── messaging.js
│       └── ui.js
│
└── views/
    └── components/
        └── frontend/
            └── push-notification-opt-in.blade.php

public/
└── firebase-messaging-sw.js

docs/
└── push-notifications/
    └── firebase-setup.md

tests/
└── Feature/
    └── Push/
        └── ...
```

This is guidance, not a command to ignore the project's existing structure.

---

# Step 60 — Definition of Done

Phase 2.3A is complete only when all applicable conditions are true:

- Firebase npm SDK installed
- modular SDK used
- Firebase web configuration centralized
- `.env.example` updated
- no real credentials committed
- browser support detection implemented
- service worker registers successfully
- permission only requested after explicit user action
- denied state handled
- granted state handled
- unsupported state handled
- missing config handled
- FCM token can be obtained in a configured supported browser
- token is not yet persisted to database
- reusable frontend opt-in component exists
- frontend remains functional without push support
- service worker does not conflict with existing worker architecture
- Vite production build succeeds
- Laravel tests remain green
- relevant new tests pass
- Firebase Console setup documentation exists
- no Phase 2.3B+ scope implemented

---

# Production Safety Requirements

Before finishing, verify:

```bash
git status
git diff --stat
```

Inspect all changed files.

Verify that Git does NOT contain:

```text
.env
Firebase private keys
service-account credentials
FCM server credentials
private JSON credential files
```

Never commit secrets.

---

# Required Validation Commands

Run the appropriate subset of:

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
npm install
```

```bash
npm run build
```

```bash
./vendor/bin/pint
```

Use equivalent project-standard commands where necessary.

If any command fails due to a pre-existing project issue, identify it clearly instead of hiding the failure.

---

# Manual Browser Verification

After Firebase configuration is supplied, verify:

## Case 1 — Fresh Visitor

```text
Notification.permission = default
```

Expected:

```text
CTA visible
No browser permission popup automatically
```

Click CTA.

Expected:

```text
browser permission prompt appears
```

---

## Case 2 — Permission Granted

Expected:

```text
service worker registered
Firebase Messaging initialized
FCM registration token obtained
UI shows enabled state
```

---

## Case 3 — Permission Denied

Expected:

```text
website still works
no repeated browser prompt
UI handles denied state gracefully
```

---

## Case 4 — Unsupported Browser

Expected:

```text
no JavaScript crash
website operates normally
CTA hidden or unsupported state shown
```

---

## Case 5 — Firebase Config Missing

Expected:

```text
website loads normally
push feature stays inactive
no fatal JavaScript errors
```

---

# Completion Report Required

At the end, provide a structured completion report with:

## 1. Phase Summary

Explain what was implemented.

## 2. Architecture

Describe Firebase initialization, messaging client, service worker and UI flow.

## 3. Files Created

List every new file.

## 4. Files Modified

List every modified file and why.

## 5. Dependencies

List package additions or changes.

## 6. Environment Variables

List all required Firebase variables without exposing actual secret values.

## 7. Browser Flow

Explain:

```text
CTA
→ permission
→ service worker
→ Firebase Messaging
→ FCM token
```

## 8. Service Worker

Explain:

- path
- scope
- registration mechanism
- any interaction with an existing worker

## 9. Tests

Report:

```text
tests added
tests executed
passed
failed
```

## 10. Build Results

Report:

```text
npm build
Laravel tests
Pint
```

## 11. Manual Firebase Configuration Still Required

Clearly identify anything the project owner must configure in Firebase Console.

## 12. Security Review

Confirm that no private Firebase/server credential was committed.

## 13. Scope Review

Explicitly confirm that Phase 2.3B–2.3I features were NOT prematurely implemented.

## 14. Risks / Follow-ups

Mention any browser, service-worker, HTTPS, caching or deployment considerations discovered.

## 15. Final Status

Return exactly one:

```text
PHASE 2.3A COMPLETE
```

or:

```text
PHASE 2.3A BLOCKED
```

If blocked, clearly state the blocking issue.

---

# Final Instruction

Implement Phase 2.3A completely.

Do not merely analyze the repository.

Do not stop after creating documentation.

Inspect the existing code first, make the required changes, add appropriate tests, run validation/build commands, and provide the required completion report.

Stay strictly within **Phase 2.3A — Firebase & Browser Push Notification Foundation**.