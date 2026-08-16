# Filament push notification panel

Phase 2.3E adds a permission-protected manual composer under **Content → Push Notifications**. It reuses the Phase 2.3C `PushMessage`, `PushNotificationService`, push queue, and active subscription scope. It does not contain Firebase transport code.

## Permissions

- `view push notifications`
- `create push notifications`
- `update push notifications`
- `delete push notifications`
- `send push notifications`

Administrators receive all five permissions. Editors may view, create, update, and delete drafts but cannot send. Reporters and contributors receive none. The existing super-admin Gate bypass remains authoritative. Policies protect direct resource URLs as well as visible actions.

## Operator workflow

1. Open **Push Notifications** and select **Create Draft**.
2. Write a standalone title/body or select a published Post to copy its existing push-message snapshot.
3. Optionally adjust the HTTP/HTTPS image and destination URL.
4. Review the approximate preview, target, and current active subscriber count.
5. Save the draft. Saving and editing never send.
6. Select **Send Notification**, review the confirmation, and explicitly queue it.

Post pre-fill uses `PostPushMessageFactory`, including its plain-text excerpt, canonical URL, and featured-image resolution. The copied values remain editable and do not depend on the Post after saving.

## Lifecycle and safety

- `draft`: editable and unsent.
- `queued`: atomically claimed for fan-out and protected from a second send.
- `sent`: all intended per-subscription jobs were successfully queued. It does not mean browsers received or opened the notification.
- `failed`: campaign-level queue fan-out could not be initiated. The stored failure is a generic operational message; exception details, credentials, and tokens are not persisted.

The backend changes `draft → queued` with a conditional database update. Only one concurrent request can claim the record. Queued, sent, and failed snapshots cannot be edited or sent through the standard action. Creating a new draft is the safe way to intentionally send similar content again.

`recipient_count` is the number of active subscriptions selected during fan-out. It is operational metadata, not delivery analytics. Subscriber IDs and FCM tokens are never stored on the notification record or displayed in Filament.

If there are no active subscribers, the record remains a draft and nothing is queued. If fan-out throws after the atomic claim, the record becomes failed and is not falsely marked sent. Individual device failures continue through the Phase 2.3C retry and invalid-token lifecycle.

Manual sends cannot be recalled after queue fan-out begins. Operators should verify queue workers and the separate server-side Firebase configuration before enabling production use.

Phase 2.3E targets **All Active Subscribers** only. Future topic/category targeting can extend audience resolution before calling the same push service without changing the transport or message contract.
