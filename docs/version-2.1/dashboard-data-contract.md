# Dashboard data contract and query rules

`App\Services\DashboardMetrics` is the reusable query boundary. It exposes post counts, own-post summaries, editorial summaries, media summaries, SEO completeness summaries, verified analytics summaries, administrative summaries, and authorized recent workflow activity.

Post data uses `ContentAccess::scopePosts()`, media uses `ContentAccess::scopeMedia()`, and history is filtered through the authorized post subquery. Counts are grouped in SQL where practical and recent activity is eager-free and bounded. Analytics uses only `posts.views_count` and existing `PostVisit` rows; no tracking or event collection was added.

Dashboard pages render a stable data contract and show explicit empty states. Direct page requests repeat `canAccess()` authorization at mount time. Existing resources, workflow services, public subscriber dashboard, routes, imported data, and production configuration are unchanged.
