# Queue worker topology

Recommended supervised groups:

```text
publishing: publishing
external: external
maintenance: maintenance
```

Use bounded worker memory, max jobs, max time, and one application user. Do not activate workers until Redis and deployment supervision are verified. Current local environment has no worker or process manager.
