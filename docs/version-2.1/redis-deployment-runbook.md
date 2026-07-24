# Redis deployment runbook

1. Provision Redis using the operating-system package/service convention.
2. Bind it to local/private interfaces with protected mode and firewall restrictions.
3. Configure authentication through deployment secrets only.
4. Install PhpRedis for the exact PHP CLI/FPM version; use Predis only if native installation is unsafe.
5. Set `REDIS_CLIENT`, host, port, DB 0/1, and unique environment prefix.
6. Verify `redis-cli ping` and `php artisan redis:health` from the application runtime.
7. Run focused Redis tests, then application regressions.

Do not change `CACHE_STORE`, `SESSION_DRIVER`, or `QUEUE_CONNECTION` as part of this foundation phase. Redis workers/Horizon are deferred.
