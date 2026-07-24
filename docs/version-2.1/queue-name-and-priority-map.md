# Queue names and priority

Priority order for supervised workers:

```text
publishing,external,maintenance,default
```

Publishing is isolated from external HTTP and maintenance probes. Queue names are explicit on jobs and Redis queue DB 3 is reserved separately from cache DB 1 and default DB 0.
