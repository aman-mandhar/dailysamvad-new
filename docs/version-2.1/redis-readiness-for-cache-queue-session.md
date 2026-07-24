# Redis readiness decisions

| Subsystem | Current driver | Phase 2.1-F decision |
|---|---|---|
| Cache | database | retain; Redis store is explicit and dormant |
| Sessions | database | retain; migration requires separate approval and rollout |
| Queue | database | retain; Redis queue DB 3 is reserved for a later phase |
| Rate limiter | default cache/database | compatible with Redis cache, unchanged |
| Scheduler locks | default cache/database | compatible with Redis locks, unchanged |

No broad cache, full-page cache, queue activation, session migration, Horizon, or cache warming is implemented.
