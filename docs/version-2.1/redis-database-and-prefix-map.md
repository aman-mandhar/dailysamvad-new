# Redis database and prefix map

| Logical DB | Allocation | Status |
|---:|---|---|
| 0 | default operations and locks | reserved |
| 1 | application cache | explicitly configured, dormant |
| 2 | sessions | reserved for a future approved migration |
| 3 | queues | reserved for a later phase; inactive |
| 4 | specialized/rate-limit workloads | reserved, not required currently |

Set `REDIS_PREFIX` uniquely per application and environment, for example `dailysamvad:local:` or `dailysamvad:production:`. Never share test and production prefixes. Laravel’s cache store uses the `cache` connection and default lock connection.
