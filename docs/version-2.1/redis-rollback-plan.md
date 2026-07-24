# Redis rollback plan

Redis activation is staged and dormant by default. If health checks fail, leave `CACHE_STORE=database`, `SESSION_DRIVER=database`, and `QUEUE_CONNECTION=database`. Remove only the deployment-level Redis client/config values during a controlled release rollback; do not delete Redis keys or run `FLUSHDB`/`FLUSHALL`. Existing database cache, sessions, queues, users, workflow data, posts, and media remain authoritative.
