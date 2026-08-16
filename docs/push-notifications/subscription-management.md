# Push subscription management

Phase 2.3B persists browser FCM registrations. It does not send notifications.

## Schema and lifecycle

`push_subscriptions` stores the raw token for future delivery and a unique server-generated SHA-256 `token_hash` for lookup and deduplication. A subscription may have a nullable `user_id`; deleting a user sets that association to null. Lightweight compatibility metadata, lifecycle timestamps, permission state, and active state are retained. IP addresses are intentionally not collected. User agents come from the server request rather than client metadata.

Registration uses `POST /push/subscriptions` and unsubscribe uses `DELETE /push/subscriptions`. Both are web routes protected by Laravel CSRF middleware, accept tokens only in JSON request bodies, and never return the token. There is no public listing endpoint.

Repeated registration updates the row identified by `token_hash`, refreshes `last_seen_at` and `last_registered_at`, and reactivates an inactive row. A changed token for the same application-generated device UUID creates the new row and deactivates the previous token. Different device UUIDs remain active, allowing multiple devices and browser profiles.

Guests create rows with `user_id = null`. On authenticated synchronization, Laravel associates the token with `$request->user()`. The browser cannot submit an authoritative user ID. If accounts switch in the same browser, the next synchronization assigns the unique token to the currently authenticated account. A later guest sync does not remove an existing account association.

Unsubscribe marks the row inactive and sets `unsubscribed_at`; it does not delete history or reset browser permission. The browser then asks Firebase to delete its local registration token. The local disabled flag prevents automatic reactivation until the visitor explicitly enables notifications again.

## Browser synchronization

The Phase 2.3A token result flows into `resources/js/push/subscriptions.js`. A privacy-friendly random UUID is saved as `newsman_device_uuid`; no fingerprinting is used. Registration synchronization is limited to once per 15 minutes unless authentication state changes or the visitor explicitly enables notifications.

Network, CSRF, validation, and server failures produce a retry state without affecting the rest of the website. POST and DELETE requests naturally bypass the public GET response cache. CDN and reverse-proxy configurations must not cache these methods.

## Development inspection

After migrating, inspect aggregate state without printing raw tokens:

```bash
php artisan tinker
```

```php
App\Models\PushSubscription::count();
App\Models\PushSubscription::active()->count();
App\Models\PushSubscription::query()->select('id', 'token_hash', 'user_id', 'is_active')->get();
```

Run the focused tests with:

```bash
php artisan test tests/Feature/Push
npm run test:js
```

Phase 2.3C may consume `PushSubscription::active()`. It must treat inactive tokens as undeliverable and must not log raw tokens. Advanced rate limiting and stale-token cleanup policy remain later-phase work.
