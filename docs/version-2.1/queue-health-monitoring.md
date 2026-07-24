# Queue health and monitoring

Use `php artisan queue:health` for driver, failed-job count, and status. Add `--probe` to dispatch a short-lived `QueueProbe` to the maintenance queue. Monitor queue depth by named queue, oldest job age, reserved jobs, failed jobs, worker memory, restarts, timeout rate, retry rate, and Redis availability. Do not expose monitoring publicly or log payloads/secrets.
