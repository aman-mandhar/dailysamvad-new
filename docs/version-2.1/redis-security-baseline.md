# Redis security baseline

The intended endpoint is local/private only: `127.0.0.1` (and optionally `::1`), protected mode enabled, port 6379 not publicly exposed, and authentication supplied only by deployment environment variables when required. No Redis server was running locally, so bind, firewall, ACL, persistence, and memory settings could not be queried. Production operators must verify them before activation; credentials are never logged or documented.
