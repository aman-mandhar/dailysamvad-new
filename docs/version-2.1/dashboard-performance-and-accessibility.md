# Dashboard performance and accessibility

Dashboard pages use `DashboardMetrics` and `ContentAccess` rather than inline queries. Recent activity is bounded and filtered through an authorized post subquery. Analytics visits use a database count over the authorized post subquery. `RoleDashboard` memoizes its data for the current Livewire request to avoid repeated aggregate work.

The UI uses semantic landmarks, labelled sections, ordered activity content, `time` elements, screen-reader-only headings, visible focus-compatible Filament buttons, responsive wrapping, dark-mode contrast, and loading/empty states. Direct page mounts repeat permission checks, preventing unauthorized component access even when a navigation link is guessed.
