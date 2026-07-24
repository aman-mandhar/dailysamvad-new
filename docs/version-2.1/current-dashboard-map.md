# Current Dashboard Map

Audit date: 2026-07-24. Sources: `AdminPanelProvider`, `app/Filament/Widgets`, `ImportDashboard`, dashboard/account controllers, routes, and role seeder.

## Routes and dashboards

| Experience | Route | Access |
|---|---|---|
| Filament dashboard | `/admin` | Active user with `access admin panel` |
| Filament login | `/admin/login` | Filament guest flow |
| Import Dashboard | `/admin/import-dashboard` | `manage settings` (effectively super-admin currently) |
| Public account dashboard | `/dashboard` | Authenticated active user |

## Registered Filament widgets

| Widget | Visibility | Queries/contents | Risk observation |
|---|---|---|---|
| StaffWelcomeWidget | `view admin dashboard` | Role-specific copy; optional create URL | Safe permission check; copy explicitly covers only five staff roles |
| AdministrativeOverviewWidget | `manage users` | 3 post counts, user count, staff role count, category/tag counts, total view sum | 8 aggregate queries per render; uncached |
| EditorialOverviewWidget | `review posts` and not `manage users` | Pending, rejected, scheduled, published-today counts | 4 aggregate queries per render; uncached |
| OwnPostOverviewWidget | `view own posts` and not `view all posts` | Four own-status counts plus view sum | 5 aggregate queries per render; scoped to authenticated author |
| AccountWidget | Authenticated Filament user | Filament account widget | Framework-provided |

Resource list queries eager-load their displayed relationships. Posts load author, reviewer, primary category, categories, and tags; media loads uploader and featured-post counts; taxonomy lists use parent/post counts. No obvious per-row N+1 was found in those query definitions.

## Navigation

- Post navigation is `Editorial` for `view all posts`; otherwise `My Work`.
- Categories, tags, media, users, and Import Dashboard rely on policies or explicit permission checks.
- Post navigation badges execute an additional scoped count query.
- The panel uses discovery for resources/pages/widgets plus explicit widget registration. Duplicate widget registration was not observed in tests or source.

## Current role experience

| Role | Current experience |
|---|---|
| Super Admin | Welcome + administrative metrics, all resources through gate override, Import Dashboard |
| Admin | Welcome + administrative metrics; users/posts/taxonomy/media according to permissions; no Import Dashboard |
| Editor | Welcome + editorial pipeline metrics; all-post editorial resources |
| Reviewer | Cannot enter Filament despite review permissions |
| Reporter | Welcome + own-post metrics; own posts and own uploaded media |
| SEO Manager | Cannot enter Filament; historical permissions are not sufficient for panel access and lack `manage seo` |
| Subscriber | No Filament; public account dashboard and account pages |

There is no dedicated analytics dashboard, search dashboard, media dashboard, reviewer dashboard, or SEO dashboard. Analytics permissions are seeded but no corresponding page/widget was found.
