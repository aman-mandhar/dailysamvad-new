# Push queue, security, and rate limiting

## Architecture

Push campaigns and automatic Post notifications resolve an audience in bounded `chunkById` batches. Each unique notification/subscription pair creates one delivery record and queues a small `SendPushNotificationJob` payload containing database identifiers only. Workers load the current subscription and immutable notification snapshot, atomically claim the delivery, then call the existing Firebase HTTP v1 transport.

The queue name comes from `FIREBASE_PUSH_QUEUE` (`push` by default); the connection continues to come from Laravel's queue configuration. Never use the `sync` driver for production broadcasts. Run separate supervised workers for `default`, `push`, and media/image queues where those workloads are enabled. A representative push worker is:

```bash
php artisan queue:work --queue=push --tries=4 --timeout=30 --max-jobs=250 --max-time=1800 --memory=192
```

The worker timeout must remain greater than the Firebase HTTP timeout. Restart workers after deploying job or configuration changes:

```bash
php artisan queue:restart
```

## Retries and quota pressure

Delivery jobs use bounded attempts and progressive backoff configured by `PUSH_JOB_TRIES`, `PUSH_JOB_TIMEOUT`, and `PUSH_JOB_BACKOFF`. Network errors, Firebase 5xx responses, and quota/resource-exhaustion responses are retryable. Invalid/unregistered tokens and invalid requests are terminal. Invalid tokens deactivate only their own subscription. Laravel records exhausted jobs in its configured `failed_jobs` table; inspect with `queue:failed` and retry only after fixing the cause.

The Firebase client performs no hidden general HTTP retries. It makes one authentication-refresh retry for a 401 response; queue-level retries handle classified transient delivery failures.

## Duplicate and concurrency controls

Campaign services claim manual and automatic notifications atomically. Fan-out uses the database unique constraint on `(push_notification_id, push_subscription_id)`. Delivery workers atomically transition only `queued` or retryable `failed` rows to `attempting`; duplicate workers no-op. An accepted row is never resent or overwritten by a late failure. Database state transitions complete before the external Firebase request, so locks are not held across network calls.

## Emergency outbound switch

`PUSH_SENDING_ENABLED=false` blocks all actual Firebase sends at execution time. Registration, unsubscribe, preferences, Filament records, and analytics remain available. Disabled sends are never marked FCM Accepted. `PUSH_AUTO_PUBLISH_ENABLED` remains independent: disabling it stops automatic Post campaigns while manual/test sends remain possible when global sending is enabled.

For an emergency stop, set `PUSH_SENDING_ENABLED=false`, refresh cached configuration, and restart queue workers. Re-enable only after verification; do not blindly replay ambiguous historical campaigns.

## Public endpoint limits and cache rules

Named Laravel limiters protect subscription registration/unsubscribe, preference reads/writes, and click tracking. Manual sends have a separate operator cooldown and retain policy authorization plus confirmation. CSRF remains active for browser mutation routes. No public broadcast endpoint exists.

Do not cache mutation responses or `/push/click/*` in Nginx, Varnish, Cloudflare, or another full-page cache. The click controller also emits no-store response headers, uses an opaque delivery UUID, and redirects only to the stored HTTP/HTTPS target. Click counts represent endpoint hits, not verified human opens.

## Recovery, cleanup, and health

Safe operational commands:

```bash
php artisan push:health
php artisan push:recover-stuck --dry-run --limit=500
php artisan push:recover-stuck --limit=500
php artisan push:prune-subscriptions --dry-run --limit=500
php artisan push:prune-subscriptions --limit=500
```

Recovery considers only `attempting` rows older than `PUSH_STUCK_AFTER_MINUTES`; fresh and accepted deliveries are untouched. Cleanup considers only inactive subscriptions older than `PUSH_INACTIVE_RETENTION_DAYS`. Active subscriptions are never deleted merely because they are old. Delivery history survives through the nullable foreign key and token-hash snapshot; topic pivots cascade with subscription deletion.

`push:health` does not send. It reports the global switch, configuration availability, Laravel queue/cache settings, active subscription count, queued/attempting/stuck deliveries, and retryable/final failures without exposing tokens or credentials.

## OAuth and logging safety

OAuth access tokens use a project/client-namespaced cache key, expiry safety margin, and Laravel cache lock to reduce refresh stampedes. Private keys and service-account JSON are never cached. Queue payloads and operational output contain no registration token, bearer token, private key, or authorization header. Safe logging is limited to delivery/subscription IDs, category, HTTP status, retryability, and attempt count.

## Scale and operations

`PUSH_FANOUT_CHUNK_SIZE` bounds audience memory and transaction size. Fan-out never materializes the full active audience or enqueues subscriber arrays. Delivery records can grow substantially; retention or aggregation changes are intentionally deferred to the production audit rather than destructively pruning analytics in this phase. Horizon is optional and was not installed.

Before production enablement, keep outbound sending disabled, validate queue/cache connectivity, run dry-run recovery and pruning, start a supervised push worker, send one explicitly targeted test subscription, and verify FCM Accepted/click analytics. Final live credential setup, browser/device certification, workload tuning, and deployment validation belong to Phase 2.3I.