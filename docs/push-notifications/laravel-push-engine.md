# Laravel push notification engine

Phase 2.3C delivers stored browser subscriptions through Firebase Cloud Messaging HTTP v1. It does not connect push delivery to publishing, campaigns, topics, or analytics.

## Private server configuration

Configure these server-only environment variables:

```dotenv
FIREBASE_MESSAGING_PROJECT_ID=
FIREBASE_SERVICE_ACCOUNT_PATH=
FIREBASE_MESSAGING_TIMEOUT=10
FIREBASE_MESSAGING_CONNECT_TIMEOUT=5
FIREBASE_OAUTH_EXPIRY_MARGIN=300
FIREBASE_PUSH_QUEUE=push
FIREBASE_PUSH_DEFAULT_ICON=
```

FIREBASE_SERVICE_ACCOUNT_PATH must point to a readable service-account JSON file outside the public web root. In production, prefer a protected deployment secret mount or an application-private shared directory. Never place the file under public, resources, source control, or any Vite input. The optional default icon must be an absolute public URL.

The google/auth library signs the service-account assertion and obtains an OAuth token scoped only to firebase.messaging. Laravel's configured cache stores only the short-lived access token, under a project/account-derived hash key, for the returned lifetime minus the configured safety margin. Private keys and credential JSON are never cached.

## Delivery architecture

```text
PushSubscription
  -> PushMessage
  -> PushNotificationService
  -> SendPushNotificationJob
  -> FirebaseMessagingClient
  -> FirebaseAccessTokenProvider
  -> FCM HTTP v1
```

PushMessage supports title, body, absolute image URL, absolute click URL, absolute icon URL, and a bounded scalar data map. Data values are normalized to strings for FCM. The click URL is included both in message data for the existing service-worker contract and in webpush.fcm_options.link.

PushDeliveryResult exposes success, FCM message ID, HTTP status, safe error code/message, invalid-token classification, and retryability. It never contains the registration token, OAuth bearer token, request headers, or raw Firebase response.

An UNREGISTERED response marks the corresponding subscription inactive with permission state invalid. INVALID_ARGUMENT alone does not invalidate a token. Authentication, permission, quota, network, and server failures leave subscriptions active. Network, quota, and server failures are retryable.

## Queue operation

PushNotificationService::queueToActiveSubscriptions() uses chunkById() and dispatches one SendPushNotificationJob per active subscription instead of loading the audience into memory. Each job contains only a subscription ID and serialized PushMessage; credentials, OAuth tokens, and FCM registration tokens are resolved at execution time.

Jobs use the push queue by default, four attempts, a 30-second worker timeout, and backoff delays of 60, 300, and 900 seconds. Missing or subsequently inactive subscriptions are safe no-ops.

```bash
php artisan queue:work --queue=push --tries=4
```

## Safe manual testing

Validate configuration and OAuth without sending:

```bash
php artisan push:test --check-config
```

Send to exactly one explicitly selected active subscription:

```bash
php artisan push:test --subscription=123
php artisan push:test --subscription=123 --title="Test title" --body="Test body" --url="https://example.com/news"
```

Production requires --force. The command has no broadcast mode, does not accept raw tokens, and never prints token or credential material.

## Automated tests

```bash
php artisan test tests/Feature/Push
```

Tests replace OAuth through AccessTokenProvider, use Laravel HTTP fakes for every FCM request, and prevent stray HTTP calls. Real Firebase delivery requires deployment credentials and an explicitly selected development/test subscription.
