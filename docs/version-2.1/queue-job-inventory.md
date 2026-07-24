# Queue job inventory

| Job | Queue | Payload | Retry/timeout | Classification |
|---|---|---|---|---|
| `PublishScheduledPost` | publishing | post ID | 3 / 30s / 30,120s | critical scheduled secondary execution; service revalidates and locks |
| `SubmitIndexNowUrls` | external | bounded URL array | 3 / 15s / 60,300s | external HTTP after commit |
| `QueueProbe` | maintenance | probe ID | 1 / 15s | diagnostic only |

No import, media, analytics, mail, or queued listener jobs currently exist.
