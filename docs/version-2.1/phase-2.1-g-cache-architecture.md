# Phase 2.1-G cache architecture

The audit found database-backed default cache, existing versioned sitemap caching, and no generic response/query/dashboard cache. Phase 2.1-G adds a centralized key builder, invalidation service, optional query/dashboard caching, safe public response middleware, lock-based stampede protection, bounded warming, diagnostics, and rollout flags.

Redis is optional at runtime. Flags default off, so unavailable Redis falls back to database-backed uncached rendering. The database remains authoritative. No session, queue, workflow, imported-content, URL, SEO, or media behavior was changed.
