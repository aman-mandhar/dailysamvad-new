# Phase 2.1-I — Image optimization

Additive, opt-in GD pipeline. Originals and public paths remain the source of truth; derivatives live under versioned `media/derivatives/v1`. Processing is bounded and routed to the `media` queue.

`php artisan images:audit --json` verifies current capabilities. `images:process --limit=10` is the controlled rollout; `images:cleanup` is dry-run by default.
