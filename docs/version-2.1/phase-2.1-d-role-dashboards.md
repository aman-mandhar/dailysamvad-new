# Phase 2.1-D role dashboards

Daily Samvad now exposes separate permission-gated Filament workspaces for Super Admin, Admin, Editor, Reviewer, Reporter, SEO Manager, Media Manager, Analytics Manager, and Contributor. The public subscriber dashboard remains the `/dashboard` frontend route; subscribers have no Filament panel permission.

Each workspace is a lightweight Page backed by `DashboardMetrics`. Pages use policies and permissions for access and repeat the authorization check at mount time to protect direct URLs. Metrics and workflow activity are scoped through `ContentAccess` and post policy-compatible query objects. No role-specific business logic or workflow transitions are duplicated in dashboard pages.

The shared Filament dashboard remains backward-compatible, but its existing widgets now use the same scoped metric service. This keeps legacy links functional while the Workspaces navigation exposes the role-specific entry points. Empty activity and metric states are rendered explicitly. No new analytics events are fabricated: analytics uses existing publication views and `PostVisit` records only.

The architecture is intentionally presentation-light for Phase 2.1-E: pages supply a stable data contract (`heading`, `description`, `metrics`, `activity`, `actions`) and the view can be replaced without changing query or authorization code.
