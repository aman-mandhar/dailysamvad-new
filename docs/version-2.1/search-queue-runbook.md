# Queue runbook

No external indexing worker is activated. Future indexing jobs must carry scalar IDs, dispatch after commit, and use idempotent overlap-safe processing.
