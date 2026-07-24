# Workflow history architecture

`post_workflow_events` is the append-only audit stream: post, nullable actor (for system events), event, from/to status, notes, JSON metadata, and timestamps. Events are ordered by timestamp and ID and indexed by post/time and event/time. Corrections/rejections retain historical notes even when the latest convenience field changes. Assignment changes retain both reviewer IDs. No fictional events are backfilled for imported or existing posts.

Ordinary application code exposes no update/delete path for events. Internal notes are only available through post authorization plus `view workflow history`; they are never exposed by public routes or notifications.
