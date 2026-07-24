# Dashboard performance contract

`DashboardMetrics` is the only dashboard aggregate boundary. Post and media queries reuse `ContentAccess`; activity is a bounded single query filtered by an authorized post subquery. Analytics counts visits directly from `post_visits` instead of loading every published post with per-row counts. `RoleDashboard` memoizes its data for the Livewire request to avoid duplicate aggregate work during rendering.

Metrics are aggregate SQL queries and do not introduce N+1 relationships. No caching layer, Redis, analytics collection, or schema change was introduced. Future UI work should preserve the service contract and query scopes.
