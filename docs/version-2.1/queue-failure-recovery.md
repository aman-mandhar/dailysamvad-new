# Queue failure recovery

Inspect first with `php artisan queue:failed` and application logs. Review the exception and payload class before retrying a specific UUID using Laravel’s failed-job tooling. Do not flush failed jobs or delete pending jobs without review. Correct the cause, deploy, run `queue:restart`, then retry bounded batches and verify idempotency.
