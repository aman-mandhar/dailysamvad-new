# Queue runbook

`ProcessMediaImage` carries only a media ID, uses queue `media`, three bounded attempts, backoff 30/120 seconds, 120-second timeout and overlap protection. Use `images:process --sync` only for a small verified sample.
