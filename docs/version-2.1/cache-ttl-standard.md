# Cache TTL standard

| Class | TTL | Use |
|---|---:|---|
| Very short | 60s | dashboard metrics, rapidly changing summaries |
| Short | 300s | homepage/query results |
| Medium | 1800s | article/archive/query results |
| Long | 21600s | stable public fragments |
| Very long | 43200s | low-change public metadata |

TTL is a safety net; event-driven invalidation handles correctness-critical changes.
