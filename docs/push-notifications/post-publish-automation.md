# Post publish push automation

Phase 2.3D connects the canonical post publication transition to the Phase 2.3C push engine. It does not add campaigns, topics, analytics, or a Filament notification panel.

## Authoritative publication rule

A post is publicly published only when its status is published, published_at is present, and published_at is not in the future. The Post observer compares the previous persisted state with that rule and dispatches PostPublished only for a non-public to public transition. The event implements Laravel after-commit dispatch, so rolled-back publication transactions cannot start push fan-out.

Draft, review, approved, and future scheduled saves do not trigger the event. Editing title, body, media, or SEO on an already public post also does not trigger it. The existing EditorialWorkflowService and PublishScheduledPost job remain the authoritative manual and scheduled publication paths.

## Durable idempotency

The additive push_notified_at column means automatic fan-out has been accepted for the post. PostPublishPushAutomation atomically changes null to a timestamp before queue fan-out; concurrent handlers and later unpublish/republish corrections therefore cannot start another automatic broadcast. If message construction fails, no claim is made. If fan-out initiation throws, the claim is released so a controlled retry remains possible. Individual delivery failures are handled by the existing per-subscription job retries and do not restart the broadcast.

The migration backfills push_notified_at from published_at for every post already published at deployment. This deliberately treats the existing dataset as historical and prevents bulk alerts. Posts with old_wp_id are additionally excluded from automatic events, including later importer updates.

## Message mapping

PostPushMessageFactory maps the post title, a short plain-text excerpt/meta-description/content fallback, canonical publicUrl(), and the existing featured-image URL accessor into PushMessage. Script/style content, HTML, entities, and repeated whitespace are cleaned. Missing images are omitted. Generic data contains type (post or breaking_news) and entity_id; no analytics identifiers are added.

## Configuration

Automatic post push is disabled by default:

```dotenv
PUSH_AUTO_PUBLISH_ENABLED=false
PUSH_AUTO_PUBLISH_BODY_LENGTH=180
```

Subscriptions, the manual push:test command, and the underlying delivery engine continue to work while automation is disabled. Enable only after Firebase credentials, an active push worker, a test subscription, and manual delivery have been verified.

## Runtime flow

```text
real publish transition
  -> PostPublished after commit
  -> SendPostPublishedPush
  -> PostPublishPushAutomation atomic claim
  -> PostPushMessageFactory
  -> PushNotificationService
  -> SendPushNotificationJob per active subscription
```

Failures are logged with post ID, event, stage, and exception class only. Publishing is already committed before the listener runs, and listener failures are swallowed safely so Firebase or queue problems cannot roll back editorial publication.
