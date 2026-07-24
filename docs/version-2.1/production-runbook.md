# Production runbook

Deploy additively, back up first, migrate, cache configuration, verify routes, run `queue:health`, `search:health`, `analytics:health` and `images:audit`, restart workers with `queue:restart`, verify scheduler and smoke-test public and staff boundaries. Never flush Redis or delete unknown data.
