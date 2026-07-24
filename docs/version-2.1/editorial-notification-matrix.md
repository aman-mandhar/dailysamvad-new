# Editorial notification matrix

| Event | Recipient |
|---|---|
| submitted / resubmitted | active users with review permission |
| reviewer assigned/reassigned | selected active reviewer |
| corrections requested, approved, rejected, scheduled, published | active post author |

Notifications use Laravel database notifications and contain only event, post title/ID, a safe summary, and an authorized Filament edit link. Transition idempotency prevents duplicates. Delivery runs after commit; failures are logged and do not roll back editorial state. Email is intentionally not required.
