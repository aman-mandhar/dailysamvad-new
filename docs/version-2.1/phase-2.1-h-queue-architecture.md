# Phase 2.1-H queue architecture

Audit found two jobs, database queue default, database failed-job storage, scheduled publishing with overlap protection, and no worker/process-manager configuration. Redis queue configuration is now isolated to logical DB 3 and named queues, but activation remains disabled until a supervised worker and Redis readiness are verified.

Correctness-critical policy checks, workflow transitions, state writes, assignments, history, and cache invalidation remain synchronous/transactional. External IndexNow HTTP is queued after commit. Scheduled publication remains unique, scalar-ID, lock-protected, and idempotent.
