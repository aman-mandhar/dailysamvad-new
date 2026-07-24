# Cache production rollout

1. Verify Redis client/server and health command.
2. Set unique environment prefix.
3. Enable query/dashboard flags for a small staff cohort.
4. Observe authorization, hit/miss, latency, lock, and error metrics.
5. Enable public full-page caching for explicitly allowlisted routes.
6. Warm only bounded safe targets.
7. Expand gradually after regression and SEO verification.

`CACHE_STORE`, sessions, and queues remain unchanged until separately approved.
