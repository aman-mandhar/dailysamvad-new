# Daily Samvad — Version 2.1

## Phase 2.1-E: Dynamic Dashboard UI/UX Redesign

You are working on the existing Daily Samvad Laravel application.

The application currently uses:

* Laravel 13
* Filament 5
* Livewire 4
* Spatie Laravel Permission
* Permission-driven RBAC established in Phase 2.1-B
* Editorial workflow architecture completed in Phase 2.1-C
* Role-specific dynamic dashboards established in Phase 2.1-D

This phase must redesign and standardize the authenticated staff dashboard experience without changing business logic, permissions, workflow behavior or public-site functionality.

---

# 1. Primary Objective

Redesign the Filament staff dashboard and related role workspaces into a modern, responsive, accessible and consistent interface.

The implementation must improve:

* Visual hierarchy
* Navigation clarity
* Dashboard usability
* Mobile responsiveness
* Widget consistency
* Readability
* Empty states
* Loading states
* Accessibility
* Query efficiency
* Role-specific information prioritization
* Reusable design architecture
* Overall staff productivity

This is a UI/UX and dashboard presentation phase.

Do not introduce unrelated business features.

---

# 2. Current Phase Context

Previous Version 2.1 phases established:

```text
Phase 2.1-A
Baseline audit and implementation readiness

Phase 2.1-B
Roles, permissions, policies and panel access

Phase 2.1-C
Editorial workflow, reviewer assignment, scheduling,
workflow history and notifications

Phase 2.1-D
Separate dynamic dashboards and role-aware widgets
```

Phase 2.1-E must build on those foundations.

Do not replace or bypass the architecture implemented in previous phases.

---

# 3. Protected Boundaries

Do not disturb:

* Existing users
* Existing passwords
* Existing user IDs
* Existing role assignments
* Existing permissions
* Existing direct permissions
* Existing policies
* Existing editorial workflow transitions
* Existing workflow-history records
* Existing reviewer assignments
* Existing scheduled publishing behavior
* Imported WordPress posts
* Imported media
* Featured-media mappings
* SEO metadata
* Public routes
* Public URLs
* Slugs
* Legacy redirects
* Existing media paths
* WordPress importer architecture
* Production environment configuration
* `.env`
* Queue configuration
* Cache configuration
* Deployment configuration

Do not run destructive commands.

Prohibited commands include:

```bash
php artisan migrate:fresh
php artisan migrate:refresh
php artisan migrate:reset
php artisan db:wipe
git reset --hard
git clean -fd
composer update
npm update
```

---

# 4. Scope of This Phase

This phase includes:

* Filament dashboard layout redesign
* Dashboard navigation improvements
* Role-aware dashboard visual hierarchy
* Widget design standardization
* Responsive behavior
* Mobile and tablet usability
* Accessibility improvements
* Loading states
* Empty states
* Status indicators
* Query-count optimization for dashboard presentation
* Consistent terminology
* Dashboard-related theme improvements
* Reusable dashboard UI components
* Dashboard-specific tests and documentation

This phase does not include:

* New business workflows
* Redis activation
* Full-page caching
* Search-engine replacement
* Analytics event collection
* New media-processing pipeline
* Public frontend redesign
* News-Man implementation

---

# 5. Design Principles

The redesigned dashboard must follow these principles.

## 5.1 Permission-Driven Experience

All navigation items, widgets, cards, actions and data must remain permission-driven.

Do not expose a widget merely because the user has a particular role name.

Preferred logic:

```php
$user->can('view editorial analytics')
$user->can('view own analytics')
$user->can('manage seo')
$user->can('manage media')
$user->can('view system health')
```

Avoid:

```php
$user->hasRole('editor')
$user->role === 'admin'
```

Role-name checks may remain only where genuinely required and must be documented.

---

## 5.2 Show Relevant Information First

Each role should see the most important information first.

Examples:

```text
Admin:
System overview, pending operations, content health,
user activity and operational warnings.

Editor:
Review queue, unassigned submissions, scheduled posts,
correction requests and publishing deadlines.

Reviewer:
Assigned reviews, overdue reviews, recent feedback and
items waiting for action.

Reporter:
Own drafts, returned posts, submitted posts, published
performance and editorial feedback.

SEO Manager:
Missing metadata, schema issues, sitemap status, canonical
issues and Google News readiness.

Media Manager:
Missing alt text, broken media, unused media, recent uploads
and optimization status.

Analytics Manager:
Traffic summaries, top content, top authors, trends and
reporting alerts.

Contributor:
Own drafts, feedback and submission status.
```

Do not show irrelevant administrative metrics to every role.

---

## 5.3 Clear Visual Hierarchy

The interface should clearly distinguish:

* Page title
* Page description
* Primary metrics
* Urgent items
* Work queues
* Recent activity
* Secondary information
* Actions
* Help or guidance

Avoid dense pages with equal visual weight for every card.

---

## 5.4 Consistency

Use consistent:

* Card design
* Widget headers
* Icon sizing
* Spacing
* Border radius
* Typography
* Metric formats
* Status colors
* Empty-state language
* Date formats
* Button hierarchy
* Action placement
* Table density

Do not create a different visual language for every role.

---

## 5.5 Accessibility

Meet practical accessibility requirements.

At minimum:

* Sufficient text contrast
* Visible keyboard focus
* Logical heading order
* Semantic labels
* Accessible form labels
* Accessible action names
* No color-only status meaning
* Tooltips where icon meaning is not obvious
* Reasonable touch targets
* Screen-reader-friendly metric labels
* Reduced-motion respect where possible

---

## 5.6 Responsive Design

Support:

```text
Desktop
Laptop
Tablet
Mobile
```

Dashboard content must remain usable without horizontal overflow.

Cards, tables, charts and action groups must adapt intelligently.

Do not simply shrink desktop layouts.

---

# 6. Initial UI/UX Audit

Before implementation, audit:

* Current Filament panel provider
* Current theme configuration
* Dashboard page classes
* Registered widgets
* Widget sort order
* Widget column spans
* Widget visibility rules
* Dashboard queries
* Navigation groups
* Navigation labels
* Navigation icons
* Resource labels
* Table layouts
* Form layouts
* Mobile behavior
* Loading behavior
* Empty states
* Accessibility issues
* Color usage
* Typography
* Custom CSS and JavaScript
* Existing design tokens
* Dark mode behavior
* Build pipeline
* Filament 5 theme conventions

Document current problems before changing them.

---

# 7. Dashboard Shell

Redesign the dashboard shell while respecting Filament 5 conventions.

Audit and improve:

* Sidebar width
* Sidebar grouping
* Top navigation
* User menu
* Page header
* Breadcrumbs
* Content width
* Dashboard padding
* Mobile menu
* Notification access
* Search access
* Profile access
* Logout access
* Brand/logo display

Do not replace Filament with a completely custom admin framework.

Prefer supported Filament extension points.

---

# 8. Branding

Apply a consistent Daily Samvad staff identity.

Use existing project branding where available.

Audit:

* Logo
* Brand name
* Primary color
* Neutral colors
* Typography
* Favicon
* Login page branding
* Panel title
* Navigation branding

Do not invent a completely unrelated brand palette.

Do not use decorative colors that reduce readability.

---

# 9. Color System

Create or document a consistent semantic color system.

Suggested meanings:

```text
Success:
Published, completed, healthy

Warning:
Scheduled, pending, needs attention

Danger:
Failed, rejected, overdue, critical

Info:
Draft, assigned, informational

Neutral:
Archived, inactive, secondary
```

Do not use color as the only status indicator.

Use labels, icons or text together with color.

---

# 10. Typography

Improve typography using available project fonts and Filament conventions.

Requirements:

* Clear heading levels
* Readable body text
* Consistent metric sizing
* Legible table text
* Avoid excessively small text
* Avoid excessive uppercase
* Consistent line height
* Appropriate font weight
* Local fonts preferred where already available

Do not add unnecessary remote font dependencies.

---

# 11. Spacing and Layout

Create consistent spacing rules.

Audit and normalize:

* Widget padding
* Grid gaps
* Section margins
* Table spacing
* Form spacing
* Header spacing
* Mobile spacing
* Empty-state spacing
* Action-group spacing

Avoid cramped layouts and excessive white space.

---

# 12. Dashboard Grid System

Use a responsive grid.

Recommended behavior:

```text
Desktop:
3–4 metric cards per row where appropriate

Tablet:
2 cards per row

Mobile:
1 card per row
```

Larger operational widgets may span multiple columns.

Each widget must define sensible responsive column spans.

Do not force every widget into equal dimensions.

---

# 13. Dashboard Header

Create a clear role-aware dashboard header.

Possible content:

* User greeting
* Role/workspace label
* Current date
* Short contextual description
* Primary action
* Important alert count

Examples:

```text
Editorial Workspace
Review pending stories and manage publication flow.

Reporter Workspace
Continue drafts, respond to feedback and track published work.
```

Do not expose sensitive role or permission debugging information.

---

# 14. Quick Actions

Provide permission-aware quick actions where useful.

Examples:

```text
Create Post
Review Next Post
Assign Reviewer
Schedule Post
Upload Media
Check Missing SEO
View Reports
Manage Users
```

Requirements:

* Visible only when authorized
* Use correct destination
* Work on mobile
* Avoid duplicating full navigation
* Prioritize the most common actions
* Limit visual clutter

---

# 15. KPI and Statistic Cards

Standardize statistic cards.

Each card should support:

* Clear label
* Main number
* Optional trend or comparison
* Supporting description
* Relevant icon
* Optional click-through
* Accessible text
* Consistent number formatting
* Loading state
* Empty state

Avoid misleading trend indicators.

Do not show percentage changes unless the underlying comparison is real.

---

# 16. Role-Specific Widget Hierarchy

Preserve the role dashboards from Phase 2.1-D, but improve ordering and presentation.

## 16.1 Super Admin

Recommended hierarchy:

```text
1. Critical system alerts
2. Publishing operations
3. Content overview
4. User and role activity
5. Queue/cache/system-health summaries
6. Recent high-impact actions
```

## 16.2 Admin

Recommended hierarchy:

```text
1. Operational alerts
2. Pending editorial work
3. User and content activity
4. Publishing schedule
5. Media and SEO health
6. Recent actions
```

## 16.3 Editor

Recommended hierarchy:

```text
1. Pending review
2. Unassigned review items
3. Changes requested
4. Approved and ready to publish
5. Scheduled publication
6. Reporter activity
```

## 16.4 Reviewer

Recommended hierarchy:

```text
1. Assigned reviews
2. Overdue reviews
3. New assignments
4. Correction follow-ups
5. Recently completed reviews
```

## 16.5 Reporter

Recommended hierarchy:

```text
1. Returned for correction
2. Drafts
3. Pending review
4. Approved/scheduled
5. Published performance
6. Recent editorial feedback
```

## 16.6 SEO Manager

Recommended hierarchy:

```text
1. Critical SEO issues
2. Missing metadata
3. Google News readiness
4. Sitemap/schema status
5. Recently published content requiring review
6. SEO performance summary where available
```

## 16.7 Media Manager

Recommended hierarchy:

```text
1. Broken or missing media
2. Missing alt text
3. Recent uploads
4. Unused media
5. Storage summary
6. Optimization queue status where available
```

## 16.8 Analytics Manager

Recommended hierarchy:

```text
1. Traffic overview
2. Top content
3. Top authors
4. Category performance
5. Search behavior
6. Reporting alerts
```

Do not fabricate analytics if collection is not implemented.

Use only currently available verified data.

## 16.9 Contributor

Recommended hierarchy:

```text
1. Own drafts
2. Changes requested
3. Pending submissions
4. Recent feedback
```

---

# 17. Widget Standardization

Create reusable widget patterns.

Possible shared abstractions:

```text
MetricCard
StatusSummaryWidget
WorkQueueWidget
RecentActivityWidget
AlertWidget
EmptyStateWidget
QuickActionWidget
TrendWidget
```

Use Filament-native components where possible.

Do not build an unnecessary custom component framework.

---

# 18. Widget Headers

Each widget header should clearly provide:

* Title
* Short description where useful
* Optional filter
* Optional action
* Optional updated time

Avoid long explanatory text inside widgets.

---

# 19. Widget Empty States

Every work-queue or content widget must have a useful empty state.

Examples:

```text
No posts are waiting for review.
No correction requests need your attention.
No scheduled posts are due today.
No SEO issues were found.
No media items are missing alt text.
```

Empty states should:

* Explain what the absence means
* Offer a relevant action where appropriate
* Avoid showing blank white cards
* Avoid technical language

---

# 20. Loading States

Improve Livewire loading behavior.

Requirements:

* Prevent layout shift
* Show lightweight skeletons or spinners
* Disable repeated actions during requests
* Show progress for longer operations
* Avoid full-page flashing
* Preserve user context
* Use `wire:loading` or Filament-supported patterns appropriately

Do not add heavy animation libraries.

---

# 21. Error States

Dashboard widgets must fail gracefully.

Requirements:

* Do not expose stack traces
* Show a clear user-facing message
* Log the technical error
* Avoid breaking the whole dashboard because one widget fails
* Provide retry where practical
* Preserve authorization boundaries

---

# 22. Alerts and Urgency

Create a consistent alert hierarchy.

Suggested levels:

```text
Critical
Action required
Attention
Informational
```

Examples:

* Failed scheduled publication
* Overdue review
* Missing required SEO
* Broken media
* Queue failure
* Unassigned urgent submission

Avoid turning every status into a warning.

---

# 23. Status Badges

Standardize status badges for:

```text
draft
pending_review
changes_requested
approved
scheduled
published
rejected
archived
```

Each badge should have:

* Consistent label
* Semantic icon where useful
* Accessible text
* Stable color meaning
* Consistent use across dashboard, tables and forms

---

# 24. Navigation Architecture

Audit and improve Filament navigation.

Recommended logical groups:

```text
Dashboard
Editorial
Content
Media
SEO
Analytics
Users and Access
System
Account
```

Only show groups containing at least one authorized item.

Do not show empty navigation groups.

---

# 25. Navigation Labels

Use clear, plain labels.

Examples:

```text
Posts
Review Queue
Scheduled Posts
Media Library
SEO Health
Analytics
Users
Roles and Permissions
System Health
```

Avoid internal technical names.

---

# 26. Navigation Order

Place daily work before administrative tools.

Recommended order:

```text
Dashboard
Editorial
Content
Media
SEO
Analytics
Users and Access
System
```

Adjust by role and permission.

---

# 27. Navigation Icons

Use a consistent icon family supported by Filament.

Requirements:

* Icons must match meaning
* Avoid duplicate icons for unrelated sections
* Do not depend on icon alone
* Keep icon size consistent
* Verify icons exist in the installed version

---

# 28. Mobile Navigation

Ensure mobile users can:

* Open and close navigation
* Identify current section
* Reach primary actions
* Access notifications
* Access profile/logout
* Use tables without unusable horizontal scrolling
* Use action menus with touch
* Read metric cards

Test common mobile widths.

---

# 29. Dashboard Tables

Improve dashboard tables.

Requirements:

* Appropriate default columns
* Responsive column hiding
* Clear row actions
* Status badges
* Useful filters
* Search where needed
* Pagination where needed
* Empty states
* No N+1 queries
* Proper column alignment
* Readable dates
* Truncated long text with accessible full value
* Mobile-friendly actions

Do not overload dashboard tables with every database column.

---

# 30. Forms and Actions

Although this is not a form-redesign project, update dashboard-related action forms for consistency.

Requirements:

* Clear labels
* Helpful descriptions
* Logical grouping
* Required-field indicators
* Consistent validation messages
* Safe defaults
* Confirmation for high-impact actions
* Mobile usability
* Accessible focus behavior

Do not change workflow business rules.

---

# 31. Dates and Time

Use consistent date formatting.

Show human-readable dates where useful:

```text
Today, 4:30 PM
Tomorrow, 9:00 AM
2 hours ago
```

Provide exact date/time where ambiguity matters.

Respect the application's timezone strategy.

Do not silently change stored timestamps.

---

# 32. Number Formatting

Standardize:

* Large counts
* Percentages
* File sizes
* Durations
* View counts

Examples:

```text
1,250
12.4K
84%
2.6 MB
3 min
```

Use compact formatting only where it improves scanning.

Allow exact values through tooltips where helpful.

---

# 33. Dashboard Filters

Provide lightweight filters where useful.

Examples:

```text
Today
Last 7 days
Last 30 days
My items
Assigned to me
All authorized items
Status
Category
Author
Reviewer
```

Filters must:

* Respect authorization
* Preserve query efficiency
* Use sensible defaults
* Avoid changing global application state unexpectedly
* Be testable

---

# 34. Personalization

Allow safe presentation-level personalization only where existing architecture supports it.

Possible options:

* Remember selected date range
* Remember compact/comfortable table preference
* Remember collapsed navigation state

Do not add complex preference storage unless justified.

Do not allow users to bypass role or permission rules through personalization.

---

# 35. Dark Mode

Audit Filament dark-mode behavior.

Requirements:

* Maintain readable contrast
* Preserve semantic status distinctions
* Ensure charts and badges remain readable
* Avoid hardcoded light-only colors
* Test custom CSS in both modes

Do not force dark mode if the current project intentionally disables it.

---

# 36. Accessibility Verification

Verify:

* Keyboard navigation
* Focus visibility
* Form labels
* Button names
* Icon tooltips
* Heading order
* Status text
* Contrast
* Error announcements
* Livewire loading announcements where appropriate
* Touch target size

Document known limitations.

---

# 37. Performance Requirements

The UI redesign must not make dashboards slower.

Audit dashboard queries before and after.

Requirements:

* Avoid N+1 queries
* Avoid repeated identical aggregate queries
* Reuse query services where practical
* Select only needed columns
* Eager-load required relations
* Paginate tables
* Avoid loading large datasets into widgets
* Avoid expensive calculations during every render
* Use lazy widget loading where suitable
* Avoid polling unless justified
* Avoid unnecessary Livewire refreshes

Do not activate Redis or full-page caching in this phase.

---

# 38. Query Budget

Define a reasonable query budget for each dashboard.

At minimum document:

* Queries on initial dashboard render
* Queries per major widget
* Duplicate query findings
* Slow query risks
* N+1 verification

Do not claim performance improvements without measurement.

---

# 39. Lazy-Loaded Widgets

Use lazy loading for non-critical widgets where appropriate.

Priority content should render first.

Examples of candidates for delayed loading:

* Historical trends
* Secondary analytics
* Recent activity
* Large data tables
* System-health details

Do not lazy-load urgent workflow items if it harms usability.

---

# 40. Polling

Audit existing polling.

Only use polling where data freshness genuinely matters.

Possible valid cases:

* Queue status
* Scheduled publication countdown
* Operational alerts

Avoid frequent polling for static statistics.

Document intervals and query impact.

---

# 41. Charts

Use charts only when verified data exists and the chart adds value.

Requirements:

* Clear title
* Labeled axes where relevant
* Accessible summary
* Responsive rendering
* Reasonable date range
* Empty state
* No misleading scales
* No invented data

Do not add decorative charts.

---

# 42. Reusable Dashboard Services

Move dashboard data retrieval into reusable query or service classes where needed.

Possible examples:

```text
AdminDashboardMetrics
EditorialDashboardMetrics
ReviewerDashboardMetrics
ReporterDashboardMetrics
SeoDashboardMetrics
MediaDashboardMetrics
AnalyticsDashboardMetrics
```

Do not place complex queries directly inside presentation code if they are reused or difficult to test.

---

# 43. Widget Authorization

Every widget must enforce authorization at more than presentation level where sensitive data is involved.

Requirements:

* Visibility check
* Query scope
* Action authorization
* Direct component access protection
* No sensitive data leakage through counts
* No unauthorized global metrics

A hidden widget alone is not sufficient protection.

---

# 44. Widget Data Scope

Ensure each widget respects:

```text
own records
assigned records
editorial records
all authorized records
```

Examples:

* Reporter metrics must be scoped to their own posts.
* Reviewer metrics must be scoped to assigned or authorized posts.
* Editor metrics may use broader editorial scope.
* SEO Manager metrics must not expose private unrelated data beyond required SEO work.
* Media Manager metrics must use media permissions.
* Analytics Manager metrics must follow analytics permissions.

---

# 45. Notifications UI

Improve notification presentation where database notifications exist.

Requirements:

* Clear unread count
* Useful message
* Relevant timestamp
* Authorized link
* Mark-as-read behavior
* Empty state
* Mobile usability

Do not expose internal links to users lacking access.

---

# 46. Recent Activity

Recent activity widgets must:

* Show only authorized events
* Use understandable language
* Show actor where permitted
* Show time
* Link safely
* Avoid exposing sensitive metadata
* Limit record count
* Avoid expensive queries

---

# 47. Breadcrumbs

Use breadcrumbs consistently where they improve orientation.

Avoid overly long breadcrumb trails.

Do not expose hidden resources through breadcrumb labels or URLs.

---

# 48. Page Titles and Descriptions

Standardize page titles and supporting text.

Examples:

```text
Review Queue
Stories waiting for editorial review.

Scheduled Posts
Approved stories planned for future publication.

SEO Health
Review metadata, schema and Google News readiness.
```

Avoid vague titles such as:

```text
Overview
Data
Management
```

unless context is already clear.

---

# 49. Empty Dashboard State

A new user or role with no records must see a useful dashboard.

Examples:

* Contributor with no drafts
* Reporter with no published posts
* Reviewer with no assignments
* SEO Manager with no current issues
* Media Manager with no missing metadata

Do not show zero-filled cards without explanation when an empty state is more useful.

---

# 50. First-Use Guidance

Provide minimal contextual guidance where useful.

Examples:

```text
Create your first draft.
No reviews are assigned to you.
All current articles have SEO metadata.
```

Do not implement a complex onboarding tour unless already supported.

---

# 51. Custom CSS and Theme Architecture

Use a maintainable Filament theme approach.

Requirements:

* Follow Filament 5 theme conventions
* Keep custom CSS scoped
* Avoid global selectors that break resources
* Avoid `!important` unless justified
* Avoid inline styles
* Use Tailwind utilities where appropriate
* Reuse design tokens
* Test production builds
* Preserve upgrade compatibility

Document all custom theme files.

---

# 52. JavaScript

Avoid custom JavaScript unless necessary.

If used:

* Keep it small
* Use existing Alpine/Livewire conventions
* Avoid duplicate libraries
* Avoid jQuery
* Avoid global side effects
* Preserve CSP compatibility where relevant
* Test after Livewire navigation

---

# 53. Build Pipeline

Verify:

```bash
npm run build
```

Requirements:

* Build succeeds
* No missing theme entrypoints
* No unresolved imports
* No console-breaking errors
* Production assets are generated correctly
* Existing public frontend assets remain intact

Do not upgrade frontend packages in this phase.

---

# 54. Browser Verification

Verify at minimum:

* Desktop Chrome-compatible browser
* Mobile-width responsive layout
* Filament navigation
* Dashboard rendering
* Widget actions
* Tables
* Notifications
* Light mode
* Dark mode where enabled

Document manual verification performed.

---

# 55. Required Automated Tests

Create or update tests for the redesigned dashboard architecture.

## 55.1 Dashboard Access Tests

Verify:

* Each staff role reaches the correct authorized dashboard
* Subscriber cannot access Filament
* Inactive staff cannot access Filament
* Unauthorized dashboard routes are blocked
* Permission changes affect dashboard access correctly

## 55.2 Widget Visibility Tests

Verify representative widgets:

* Admin widgets
* Editor widgets
* Reviewer widgets
* Reporter widgets
* SEO widgets
* Media widgets
* Analytics widgets
* Contributor widgets

Test both visible and hidden cases.

## 55.3 Widget Scope Tests

Verify:

* Reporter sees own data only
* Reviewer sees assigned data only
* Editor sees authorized editorial scope
* Admin sees authorized operational scope
* SEO Manager sees SEO-relevant data
* Media Manager sees permitted media data
* Analytics Manager sees aggregated analytics only

## 55.4 Navigation Tests

Verify:

* Navigation group visibility
* Resource visibility
* No empty groups
* Direct URL access remains protected
* Subscriber receives no staff navigation

## 55.5 Responsive Component Tests

Where practical, test:

* Widget column spans
* Table responsive configuration
* Mobile-safe actions
* Conditional column visibility

Automated tests cannot replace manual responsive verification.

## 55.6 Accessibility Tests

Where practical, verify:

* Labels
* Button names
* Status text
* Accessible action descriptions
* No icon-only critical actions without labels/tooltips

## 55.7 Query Tests

Verify representative dashboards do not introduce:

* N+1 queries
* Duplicate per-row queries
* Unbounded result loading

Document query counts.

## 55.8 Regression Tests

Verify:

* Editorial workflow remains unchanged
* Workflow actions still work
* Public routes still work
* Imported posts remain unchanged
* SEO metadata remains unchanged
* Media mappings remain unchanged
* Role permissions remain unchanged
* Scheduled publishing remains unchanged

---

# 56. Test Execution

Run focused tests first.

Suggested commands:

```bash
php artisan test tests/Feature/Filament/Dashboard
php artisan test tests/Feature/Filament/Widgets
php artisan test tests/Feature/Filament/Navigation
php artisan test tests/Feature/Authorization
```

Adapt paths to the actual test structure.

Run build verification:

```bash
npm run build
```

Then run the full suite:

```bash
php artisan test
```

Clearly distinguish:

```text
pre-existing failure
new regression
environmental failure
fixed failure
```

Do not hide failures.

---

# 57. Documentation Deliverables

Create or update:

```text
docs/version-2.1/phase-2.1-e-dashboard-ui-ux.md
docs/version-2.1/dashboard-design-system.md
docs/version-2.1/dashboard-navigation-map.md
docs/version-2.1/dashboard-widget-style-guide.md
docs/version-2.1/dashboard-responsive-behavior.md
docs/version-2.1/dashboard-accessibility-checklist.md
docs/version-2.1/dashboard-performance-baseline.md
docs/version-2.1/dashboard-browser-verification.md
```

Documentation must include:

* Design goals
* Before/after audit findings
* Layout architecture
* Navigation architecture
* Role-specific widget hierarchy
* Design tokens
* Status colors and labels
* Responsive rules
* Accessibility decisions
* Query-count findings
* Loading and empty-state patterns
* Theme files
* Build results
* Browser verification
* Known limitations
* Deferred items

Do not include secrets or personal user data.

---

# 58. Completion Criteria

Phase 2.1-E is complete only when:

* Filament dashboard layout is modernized.
* Navigation is logically grouped.
* Role workspaces remain permission-driven.
* Widget designs are consistent.
* Important role-specific information appears first.
* Mobile and tablet layouts are usable.
* Empty states are implemented.
* Loading states are implemented.
* Error states fail gracefully.
* Status badges are standardized.
* Typography and spacing are consistent.
* Accessibility improvements are verified.
* Dark mode works where enabled.
* Dashboard query counts are measured.
* N+1 issues are removed.
* No unauthorized data is exposed.
* Existing editorial workflows remain unchanged.
* Existing public routes remain unchanged.
* Production frontend build succeeds.
* Focused tests pass.
* Full-suite result is reported honestly.
* Required documentation is complete.

---

# 59. Deferred Items

Do not implement in this phase:

* Redis activation
* Full-page caching
* Cache warming
* General queue optimization
* Search-engine integration
* Analytics event collection
* New analytics schema
* Image conversion or optimization pipeline
* Public frontend redesign
* News-Man integration
* Server deployment changes
* Supervisor configuration
* `.env` changes

---

# 60. Required Completion Report Format

Return the completion report using this exact structure:

## 1. Executive Summary

## 2. Existing UI/UX Audit

## 3. Dashboard Shell Changes

## 4. Branding and Theme

## 5. Navigation Architecture

## 6. Role-Specific Dashboard Hierarchy

## 7. Widget Design System

## 8. Statistic Cards

## 9. Work Queue Widgets

## 10. Tables and Filters

## 11. Quick Actions

## 12. Status Badges

## 13. Loading States

## 14. Empty States

## 15. Error States

## 16. Responsive Design

## 17. Mobile Navigation

## 18. Accessibility Improvements

## 19. Dark Mode Verification

## 20. Notifications UI

## 21. Recent Activity UI

## 22. Dashboard Query Optimization

## 23. Query Count Comparison

## 24. Reusable Components and Services

## 25. Custom CSS and JavaScript

## 26. Build Verification

## 27. Browser Verification

## 28. Automated Tests Added or Updated

## 29. Focused Test Results

## 30. Full Test-Suite Result

## 31. Backward-Compatibility Verification

## 32. Documentation Created

## 33. Files Created or Modified

## 34. Commands Executed

## 35. Risks and Open Questions

## 36. Deferred Items

## 37. Final Phase Decision

The final phase decision must be one of:

```text
COMPLETE
COMPLETE WITH CONDITIONS
INCOMPLETE
```

Explain the decision using verified evidence.

---

# 61. Strict Rules

* Audit the existing UI before changing it.
* Follow Filament 5 conventions.
* Preserve permission-driven authorization.
* Do not hardcode role checks unnecessarily.
* Do not expose unauthorized data through widgets or counts.
* Do not change editorial workflow rules.
* Do not change existing permissions or role assignments unnecessarily.
* Do not modify imported content.
* Do not change slugs or public URLs.
* Do not alter SEO metadata.
* Do not alter featured-media mappings.
* Do not alter `.env`.
* Do not activate Redis.
* Do not add full-page caching.
* Do not replace search.
* Do not add analytics collection.
* Do not run destructive database commands.
* Do not upgrade dependencies.
* Do not claim performance improvement without measurements.
* Do not claim tests passed unless executed successfully.
* Clearly report pre-existing failures.
* Preserve backward compatibility.
