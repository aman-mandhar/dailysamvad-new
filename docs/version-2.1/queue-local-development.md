# Queue local development

The current Windows environment has no Redis listener, no PhpRedis/Predis client, no worker, and no Supervisor/systemd service. Use the existing database queue for local tests. Run `php artisan queue:health`; use `Queue::fake()` for deterministic tests. Redis probe/integration tests must be explicitly run only after a private local Redis server and matching PHP client are provisioned.
