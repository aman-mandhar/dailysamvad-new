# Scheduled publishing runbook

Every minute Laravel selects `scheduled` posts with `scheduled_at <= now()` and dispatches one unique `PublishScheduledPost` per ID. The job rechecks status/time and the service locks the row, publishes once, records a system event, and notifies the author. Future, cancelled, missing, or already-published records are no-ops. Jobs use the configured database queue, three attempts, and a 30-second timeout; Redis is not required.

Production cron (replace the path):

```cron
* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
```

Run a database worker using the deployment's process manager, for example `php artisan queue:work --tries=3 --timeout=30`. Inspect with `php artisan schedule:list`, failed-job tooling, application logs, and queries for overdue scheduled posts. This phase does not install or start production workers.
