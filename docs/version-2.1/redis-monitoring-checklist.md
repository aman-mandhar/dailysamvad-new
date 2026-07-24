# Redis monitoring checklist

Monitor service availability, latency, memory/peak memory, eviction and expiry counts, connected/blocked clients, cache hit/miss ratio, rejected connections, persistence errors, and slow-log length. Review `SLOWLOG LEN` and a bounded `SLOWLOG GET` only through secured operator access. Never log credentials, URLs, session payloads, or sensitive values.
