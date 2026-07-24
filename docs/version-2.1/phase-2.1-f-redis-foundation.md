# Phase 2.1-F Redis foundation

The audit found PHP 8.3.16 CLI on Windows, no PhpRedis extension, no Predis package, Redis Server 5.0.14.1 binaries present but stopped, and no listener on `127.0.0.1:6379`. Laravel remains database-backed for cache, sessions, and queues. This phase establishes dormant, environment-driven Redis readiness without activating production subsystems.

The selected fallback is Predis when it can be installed through the deployment's approved Composer network. It was not installed in this environment because Packagist access was unavailable; no dependency change was retained. PhpRedis remains the preferred production client.

`php artisan redis:health` performs ping, namespaced cache write/read/delete, and atomic lock checks without printing secrets. It exits non-zero when Redis is unavailable. Full activation requires provisioning a local/private Redis endpoint, a supported PHP client in both CLI and FPM, then rerunning the focused integration suite.
