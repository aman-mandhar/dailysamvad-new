# Cache rollback plan

Disable `CACHE_FULL_PAGE_ENABLED`, `CACHE_QUERY_ENABLED`, `CACHE_DASHBOARD_ENABLED`, and `CACHE_ARCHITECTURE_ENABLED` through deployment configuration. Application requests then render from the database. Do not use `Cache::flush()`, `FLUSHDB`, `FLUSHALL`, unknown-key deletion, or destructive database commands. Existing database data remains authoritative.
