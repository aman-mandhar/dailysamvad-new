# Redis local development

The current Windows environment includes Redis 5.0.14.1 binaries but no running service and no CLI listener. Start a local, non-public instance only through the developer’s approved Laragon/service workflow, then install a compatible client and set a unique local prefix. Run `redis:health` and integration tests explicitly. Tests must never target production Redis.
