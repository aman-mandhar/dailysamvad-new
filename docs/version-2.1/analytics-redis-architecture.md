# Redis architecture

Deduplication uses bounded namespaced keys with TTL through the configured cache store. Redis outage falls back to event idempotency.
