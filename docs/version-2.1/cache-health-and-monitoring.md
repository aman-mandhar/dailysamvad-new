# Cache health and monitoring

Use `php artisan redis:health` for Redis connectivity and `php artisan cache:architecture inspect <namespaced-key>` for a single known key. Monitor hit/miss headers, Redis latency, memory, evictions, errors, lock contention, invalidation frequency, and uncached fallback rates. Never log values, credentials, sessions, or arbitrary key scans.
