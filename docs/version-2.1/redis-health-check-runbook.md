# Redis health-check runbook

Run `php artisan redis:health` or `php artisan redis:health --json`. The command reports client selection, connectivity, ping, cache write/read/delete, lock support, and final status. It uses random environment-namespaced temporary keys and performs best-effort cleanup. It never prints connection URLs, passwords, raw INFO output, payloads, or unknown keys.

Exit code 0 means all checks passed; exit code 1 means unavailable or misconfigured. Run after provisioning the server and PHP client, and from both CLI and the web/FPM runtime.
