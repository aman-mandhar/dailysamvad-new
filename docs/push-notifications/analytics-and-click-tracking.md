# Push analytics and click tracking

Phase 2.3G uses one analytics path for manual campaigns and automatic Post notifications:

`PushNotification → unique audience → PushNotificationDelivery → existing queue/FCM transport → FCM Accepted or Failed → opaque click URL → aggregate analytics`

## Notification identity

Manual drafts continue using their existing `PushNotification` record. Automatic publication creates one immutable snapshot with `source_type=post` and `source_id=<post id>` using `PostPushMessageFactory`. The unique source key and existing `posts.push_notified_at` claim prevent duplicate automatic campaign identities.

Existing pre-2.3G notifications remain `source_type=manual` and may have no delivery rows. Their analytics summary correctly shows zeros; historical metrics are never fabricated.

## Delivery lifecycle

Fan-out chunks the existing lazy audience query. For each unique subscription it creates one `PushNotificationDelivery`, snapshots only the SHA-256 token hash, and queues the existing delivery job with the delivery ID. The database unique constraint prevents duplicate notification/subscription rows.

Statuses mean:

- `queued`: delivery row and queue job created.
- `attempting`: an actual FCM request is in progress; attempt count was atomically incremented.
- `accepted`: Firebase HTTP v1 returned success and an FCM message ID.
- `failed`: the attempt failed; `retryable`, safe error code, and category explain whether the existing job will retry.

Retries update the same delivery row and increment `attempt_count`. Invalid tokens retain the Phase 2.3C deactivation behavior. Authentication, quota, server, and network failures do not deactivate subscriptions.

**FCM Accepted does not guarantee browser/device display.** It only means Firebase accepted the HTTP v1 request.

Stored failure categories are `invalid_token`, `authentication`, `quota`, `server`, `network`, `invalid_request`, `subscription`, and `unknown`. No request headers, OAuth tokens, credentials, raw FCM tokens, or exception dumps are stored.

## Click tracking

Each delivery has a random UUID `public_id`. The queue job replaces the notification click URL with:

```text
/push/click/{public_id}
```

The route contains no user, subscription, token, hash, email, IP, or sequential delivery identifier. The endpoint atomically increments `click_count`, sets the first click once, updates the last click, and redirects only to the parent notification's stored target snapshot. A missing or unsafe target falls back to the homepage. Client redirect parameters are ignored.

The click endpoint is guest-accessible and requires no cookie. Responses are private/no-store. CDN and reverse-proxy configuration must bypass full-page caching for `/push/click/*`. The existing service worker already has one same-origin `notificationclick` handler, and tracked payloads use the same URL contract, so no competing handler was added.

Clicks represent tracking endpoint hits; this phase does not attempt bot detection or claim notification “opens.”

## Metrics

`PushAnalyticsService` uses database aggregates rather than loading deliveries:

- recipients and queued rows;
- attempted sends;
- FCM Accepted;
- failed/retryable failures;
- unique clicked deliveries;
- total click hits;
- grouped failure categories.

Primary CTR is:

```text
Unique clicked deliveries / FCM Accepted deliveries × 100
```

When FCM Accepted is zero, CTR is `0.00%`.

Filament shows aggregate columns and a permission-protected analytics modal. It never displays tokens or subscriber identifiers. The separate `view push analytics` permission is assigned to administrators and analytics managers; super-admin uses the existing bypass. Reporters and editors are not granted analytics access by default.

## Scale and retention

Audience and delivery creation remain chunked. Public UUID, notification/status, click, category, retryability, and time indexes support redirect and aggregate queries. Filament uses aggregate subqueries and pagination.

Delivery rows can grow rapidly. A future operational phase may retain detailed rows for 90–180 days and archive aggregates, but Phase 2.3G performs no destructive pruning.

Deployment should apply migrations, restart queue workers so they deserialize the updated job, clear caches, then verify one controlled manual send, click, and automatic Post notification.
