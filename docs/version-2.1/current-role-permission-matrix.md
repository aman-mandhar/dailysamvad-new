# Current Role and Permission Matrix

Audit date: 2026-07-24. Sources: `database/seeders/RolesAndPermissionsSeeder.php`, policies, `ContentAccess`, `UserAdministration`, Filament resources/widgets, and read-only database counts.

The live database contains 9 roles and 32 permissions. Role names are: `super-admin`, `admin`, `editor`, `reporter`, `author`, `reviewer`, `seo-manager`, `media-manager`, and `subscriber`. `analytics-manager` and `contributor` do not exist. Super-admin receives every permission and also has a hardcoded `Gate::before` override when active.

| Role | Current permissions (summary) | Dashboard access | Post scope | Publish | User management | SEO | Analytics |
|---|---|---|---|---|---|---|---|
| super-admin | All 32; permission-driven plus hardcoded gate override | Full administrative widget; welcome; account | All | Yes | Full, including roles/permissions | `manage seo` | View/manage |
| admin | Broad editorial/admin set; lacks `manage roles`, `manage permissions`, `manage settings`, `manage analytics` | Administrative widget; welcome; account | All | Yes | `manage users`, but role assignment constrained | `manage seo` | View only |
| editor | Editorial, taxonomy, media, pages, view analytics | Editorial widget; welcome; account | All | Yes | No | No `manage seo` permission | View only |
| reporter | Own posts, create/edit/submit own, own media | Own-post widget; welcome; account | Own | No | No | No | No |
| author | Own posts, create/edit/submit own; no media | Own-post widget; welcome; account | Own | No | No | No | No |
| reviewer | `view posts`, `view all posts`, `review posts` only | No panel access (`access admin panel` and `view admin dashboard` absent) | All in policy, but panel unavailable | No | No | No | No |
| seo-manager | View/edit all posts, categories/tags/pages | No panel access | All in policy, but panel unavailable | No | No | Partial/historical: lacks `manage seo` | No |
| media-manager | View all posts and manage media | No panel access | All in policy, but panel unavailable | No | No | No | No |
| subscriber | `manage own profile` | Public subscriber dashboard only; Filament denied | None | No | No | No | No |
| analytics-manager | Missing | None | None | No | No | No | No |
| contributor | Missing | None | None | No | No | No | No |

## Authorization characteristics

- Filament access requires an active user and `access admin panel` (`User::canAccessPanel`).
- Resource access is policy/permission-driven. Post and media queries are additionally scoped through `ContentAccess`.
- Reporter/author post updates are limited to their own draft or rejected posts.
- Only super-admin may force-delete posts. User administration contains explicit authority-escalation and last-super-admin protections.
- Navigation groups and badges are permission-driven. Import Dashboard requires `manage settings`; no seeded non-super-admin role has it.
- Historical reviewer/SEO/media roles are partial: their permissions do not make the Filament panel reachable. This is a confirmed configuration gap, not a recommendation to grant access during this audit.

## Classification

- Existing: all nine database roles above.
- Missing: `analytics-manager`, `contributor`.
- Partially implemented: reviewer, SEO manager, media manager.
- Hardcoded: active super-admin gate override and super-admin-only force deletion.
- Permission-driven: routine panel, resource, navigation, dashboard, workflow, media, taxonomy, and user operations.
