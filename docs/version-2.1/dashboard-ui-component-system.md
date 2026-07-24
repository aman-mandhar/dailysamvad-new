# Dashboard UI component system

Dashboard pages reuse Filament sections, buttons, loading indicators, responsive utility classes, and the shared `role-dashboard` view. Metric cards use consistent uppercase labels, tabular numeric values, dark-mode contrast, and hover affordance. Activity uses an ordered list with responsive row layout and `<time datetime>` values. Empty states are explicit and scoped to the current account.

No dashboard-specific business rules are embedded in the view. Quick actions are generated from existing permissions and resource URLs. Subscribers continue using the separate public account dashboard.
