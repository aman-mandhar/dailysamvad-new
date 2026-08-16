# Push notification production runbook

## 1. Architecture

Daily Samvad uses this implemented flow:

`Browser permission CTA → Firebase Web Messaging → FCM token → Laravel PushSubscription → topic preferences → PushAudienceResolver → PushNotification → unique PushNotificationDelivery → push queue → Firebase HTTP v1 → FCM Accepted/Failed → browser service worker → opaque tracking URL → click analytics → Filament`

Firebase acceptance is not proof that a browser displayed a notification.

## 2. Environment variables

Browser-safe Firebase values:

```text
FIREBASE_WEB_API_KEY
FIREBASE_WEB_AUTH_DOMAIN
FIREBASE_PROJECT_ID
FIREBASE_STORAGE_BUCKET
FIREBASE_MESSAGING_SENDER_ID
FIREBASE_WEB_APP_ID
FIREBASE_MEASUREMENT_ID
FIREBASE_VAPID_KEY
```

Server messaging and operational values:

```text
FIREBASE_MESSAGING_PROJECT_ID
FIREBASE_SERVICE_ACCOUNT_PATH
FIREBASE_MESSAGING_TIMEOUT
FIREBASE_MESSAGING_CONNECT_TIMEOUT
FIREBASE_OAUTH_EXPIRY_MARGIN
FIREBASE_OAUTH_LOCK_SECONDS
FIREBASE_PUSH_QUEUE
FIREBASE_PUSH_DEFAULT_ICON
PUSH_SENDING_ENABLED
PUSH_AUTO_PUBLISH_ENABLED
PUSH_AUTO_PUBLISH_BODY_LENGTH
PUSH_FANOUT_CHUNK_SIZE
PUSH_JOB_TRIES
PUSH_JOB_TIMEOUT
PUSH_JOB_BACKOFF
PUSH_SUBSCRIPTION_RATE_LIMIT
PUSH_PREFERENCE_READ_RATE_LIMIT
PUSH_PREFERENCE_WRITE_RATE_LIMIT
PUSH_CLICK_RATE_LIMIT
PUSH_MANUAL_SEND_RATE_LIMIT
PUSH_STUCK_AFTER_MINUTES
PUSH_INACTIVE_RETENTION_DAYS
PUSH_MAINTENANCE_BATCH_SIZE
```

Never put server credentials into `VITE_*`, Blade output, browser Firebase JSON, JavaScript, or `public/`.

## 3. Firebase Console setup

1. Select the production Firebase project.
2. Register the production Web App and copy only its browser-safe configuration.
3. Create or verify the Web Push VAPID key.
4. Enable the Firebase Cloud Messaging HTTP v1 API.
5. Create a least-purpose service-account credential used for FCM sending.
6. Store its JSON outside the public web root and outside Git.
7. Restrict it so the Laravel/queue runtime user can read it; avoid world-readable permissions.
8. Set the browser and server environment variables separately.
9. Keep both push switches off during deployment.
10. Rebuild configuration and run `php artisan push:health`.

## 4. Deployment sequence

Run commands as the normal deployment/application user, not root, so `public/build` and runtime files retain correct ownership.

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate:status
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
php artisan queue:restart
php artisan push:health
php artisan schedule:list
```

Review every pending migration before `migrate --force`. Never use `migrate:fresh`. The production scheduler must invoke `php artisan schedule:run` every minute for scheduled Posts.

## 5. Queue worker

Use a supervised isolated push worker. The Laravel connection remains environment-configured.

```bash
php artisan queue:work --queue=push --tries=4 --timeout=30 --max-jobs=250 --max-time=1800 --memory=192
```

Keep the worker timeout greater than the Firebase HTTP timeout. Separate default, push, and media/image workers. Monitor Redis/database queue size during large fan-out.

## 6. Safe production enable sequence

1. Deploy with `PUSH_SENDING_ENABLED=false`.
2. Keep `PUSH_AUTO_PUBLISH_ENABLED=false`.
3. Review and apply migrations.
4. configure browser Firebase values and VAPID.
5. Install the server credential securely.
6. Verify `/firebase-messaging-sw.js` over HTTPS.
7. Subscribe one controlled admin/developer browser.
8. Verify the database subscription count and token hash without printing the token.
9. Run `php artisan push:health`.
10. Verify the push queue worker is consuming the `push` queue.
11. Set `PUSH_SENDING_ENABLED=true`, rebuild config, and restart workers.
12. Send only to the controlled subscription:

```bash
php artisan push:test --subscription=CONTROLLED_DATABASE_ID
```

13. Confirm FCM Accepted, then separately verify foreground/background browser behavior.
14. Click the notification and verify redirect/click analytics.
15. Test a manual Filament draft with a controlled topic audience; never use all subscribers as the first campaign.
16. Verify multi-topic deduplication.
17. Only then set `PUSH_AUTO_PUBLISH_ENABLED=true`, rebuild config, and restart workers.
18. Publish one controlled article and verify one automatic campaign.
19. Edit the published article and verify there is no second automatic push.
20. Monitor logs, failed jobs, queue backlog, analytics, and quota failures.

## 7. Service worker verification

Verify `/firebase-messaging-sw.js` returns HTTP 200, JavaScript MIME type, no redirect, and root scope. It contains one `push` handler and one `notificationclick` handler. The click handler opens only same-origin opaque tracking URLs.

Do not apply immutable or long-lived caching to this file. Configure Nginx/Varnish/Cloudflare for revalidation or a short TTL. Purge only the service-worker/frontend asset paths when deploying a worker change. If troubleshooting a stale worker, use browser DevTools to unregister it, clear that test device's site data, and reload.

Push requires an HTTPS secure origin in production.

## 8. Cache and proxy rules

Bypass full-page/CDN caching for:

```text
/firebase-messaging-sw.js
/push/click/*
/push/subscriptions
/push/preferences
/admin/*
```

Mutation endpoints must always reach Laravel. Every click URL must execute Laravel so the atomic counter updates. Do not globally disable public-page caching. Topic preferences are fetched per device using a CSRF-protected request body and are not embedded into shared cached HTML.

## 9. Validation checklist

Use PASS, FAIL, NOT TESTED, or UNSUPPORTED only.

- Browser subscription
- Guest persistence and preference reload
- Authenticated-device association and preference reload
- Token rotation/reactivation
- Unsupported and denied-permission UX
- Service-worker registration and update
- OAuth authentication
- One-device FCM HTTP v1 test
- Background notification
- Foreground behavior
- Opaque click redirect and repeat click
- FCM Accepted/failure analytics and CTR
- Manual Filament draft/preview/confirmation/queue
- Topic targeting and multi-topic deduplication
- Automatic Post publication and published-edit no-op
- Scheduled Post no-early-send behavior
- Queue retry/backoff and duplicate accepted-job no-op
- Rate limits
- Global and auto-publish switches
- Recovery and cleanup dry-runs
- Health command

Browser/device matrix:

| Browser/device | Permission | Subscription | Background | Foreground | Click |
|---|---|---|---|---|---|
| Chrome desktop | NOT TESTED | NOT TESTED | NOT TESTED | NOT TESTED | NOT TESTED |
| Edge desktop | NOT TESTED | NOT TESTED | NOT TESTED | NOT TESTED | NOT TESTED |
| Chrome Android | NOT TESTED | NOT TESTED | NOT TESTED | NOT TESTED | NOT TESTED |
| Safari/macOS/iOS | NOT TESTED | NOT TESTED | NOT TESTED | NOT TESTED | NOT TESTED |

## 10. Operational commands

These commands do not broadcast:

```bash
php artisan migrate:status
php artisan route:list --path=push
php artisan schedule:list
php artisan push:health
php artisan push:sync-topics
php artisan push:recover-stuck --dry-run --limit=500
php artisan push:prune-subscriptions --dry-run --limit=500
php artisan queue:failed
```

After reviewing dry-run output, bounded mutation commands are:

```bash
php artisan push:recover-stuck --limit=500
php artisan push:prune-subscriptions --limit=500
```

Do not retry or delete failed jobs blindly. Identify configuration, quota, or code causes first.

## 11. Emergency disable

1. Set `PUSH_SENDING_ENABLED=false`.
2. Run `php artisan optimize:clear` followed by the project's normal config optimization command.
3. Run `php artisan queue:restart`.
4. Confirm `php artisan push:health` reports sending disabled.
5. Leave subscriptions, preferences, campaigns, deliveries, and analytics intact.

Disabling `PUSH_AUTO_PUBLISH_ENABLED` alone stops automatic Post campaigns but does not stop authorized manual/test delivery.

## 12. Rollback

Prefer disabling outbound sending first. Roll back the code release using the deployment system only after preserving logs and checking queue payload compatibility. Restart workers after code rollback. Do not casually roll back live push migrations once subscriptions or delivery analytics exist; use a forward database fix where possible. Do not replay ambiguous queued campaigns automatically.

## 13. Monitoring checklist

Monitor:

- Active and inactive subscription counts
- Push queue backlog and worker heartbeat
- Failed jobs and exhausted retries
- Stale `attempting` deliveries
- Retryable network/server/quota failures
- Invalid-token rate
- FCM Accepted ratio (not browser delivery)
- Unique and total clicks and CTR
- Redis/database queue memory and growth
- Delivery-table growth
- Cleanup candidates
- Firebase quotas and authentication errors

Per-recipient delivery storage grows approximately as `recipients × notifications`. Review indexes and storage growth before adopting a retention policy; always preserve required aggregates before pruning analytics.

## 14. Troubleshooting

- Missing browser config: the CTA should fail gracefully without breaking the site.
- Permission denied: the UI displays a blocked state and does not prompt on page load.
- OAuth/config failure: keep global sending off, verify the credential path and permissions, clear config, then rerun health/check-config.
- No background display: inspect worker scope, MIME type, cache headers, browser console, and the single `push` handler.
- No clicks: verify `/push/click/*` bypasses caches and the payload uses the opaque tracking URL.
- Queue backlog: keep Post publishing independent, scale the isolated push worker conservatively, and inspect quota failures.
- Repeated delivery: inspect notification/subscription uniqueness and accepted-job no-op behavior before retrying jobs.