# Push topics and device preferences

Phase 2.3F keeps Laravel as the audience source of truth:

`PushSubscription → relational topic preferences → PushAudienceResolver → unique active subscriptions → existing push queue`

## Topic model and synchronization

`push_topics` contains category-backed topics and the `breaking-news` system topic. Category topics reference the existing `categories` row; their stable slug uses the category ID so renaming a Category updates the label without breaking preferences. Inactive or removed Categories deactivate their topic rather than deleting preference history.

Run after deploying migrations and whenever taxonomy changes need reconciliation:

```bash
php artisan push:sync-topics
```

The command is idempotent, creates missing topics, updates labels/order/activity, preserves pivots, and ensures Breaking News exists.

## Preference semantics

- `preferences_configured_at = null`: legacy device. Automatic Post notifications continue to include it.
- Configured with selected topics: receives automatic Posts matching any active selected category/system topic.
- Configured with zero topics: receives no topic-targeted Post notifications. This never silently falls back to legacy behavior.
- Manual **All Active Subscribers** ignores preferences.
- Manual **Selected Topics** targets matching explicitly configured devices only; legacy devices are intentionally excluded.

Preferences belong to each `PushSubscription`, not the account. Guest and authenticated browsers use the same device-specific endpoint. Reactivation of the same subscription retains pivots. When FCM rotates a token for the same browser UUID, the registration service transfers configuration state and topic pivots to the new subscription record.

## Post targeting

Post Categories resolve through their active category topics. `is_breaking` also adds the active Breaking News system topic. A single `EXISTS`-based subscription query applies OR matching and therefore emits one row/job even when several topics match.

Automatic Post audiences include legacy devices for backward compatibility. A Post with no mapped active topics falls back to legacy devices only. Publication and push failure isolation from Phase 2.3D remains unchanged.

## Browser and privacy

The footer loads topics and selected IDs only after the current browser has an active FCM subscription. State endpoints use POST/PUT web routes, same-origin credentials, CSRF headers, and the token plus browser UUID in the request body. Tokens never appear in URLs or responses. Responses are `private, no-store`; selected state is never rendered into globally cached HTML.

The response exposes only topic IDs, labels, types, configured state, and selected IDs. It excludes FCM tokens, hashes, users, IP addresses, user agents, and subscription metadata.

## Manual campaigns

Filament drafts persist `target_type` (`all` or `topics`) and a relational topic selection. The preview shows an informational unique count, while `ManualPushNotificationService` recomputes the audience immediately before its existing atomic send claim. A zero-recipient target is not sent.

## Native FCM topics

Native FCM topic subscription is deliberately deferred. Laravel DB preferences plus individual-token jobs preserve the established retry, invalid-token, queue, and OAuth behavior without introducing a second synchronization authority. This does not block topic targeting.

No recipient history or delivery/click analytics are stored in this phase.
