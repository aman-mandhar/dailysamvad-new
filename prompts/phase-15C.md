# Phase 15C — Role-Based Filament Dashboard and Resource Access

## Project Context

This is an existing Laravel news portal migrated from WordPress.

The project contains production-like data, including approximately 13,628 imported posts.

The following foundations should already exist from earlier phases:

### Phase 15A

* User referral codes
* Referrer relationships
* Post author relationships
* Post reviewer relationships
* Review metadata
* Existing post view-counter preservation
* `post_visits` analytics foundation

### Phase 15B

* Login
* Public registration
* Logout
* Forgot-password and password-reset flow
* Email-verification decision
* Roles and permissions
* Subscriber default role
* Role-based post-login redirects
* Existing Filament panel access control
* Frontend subscriber dashboard route
* Super Admin protection foundation

Required staff roles:

```text
super-admin
admin
editor
reporter
author
```

Public role:

```text
subscriber
```

This phase must implement role-aware Filament dashboards, navigation and resource authorization without disturbing existing users, posts, media, categories, tags or public frontend functionality.

---

# Primary Objective

Implement secure and useful role-based Filament access so that each staff role sees only the dashboard widgets, navigation groups, resources, records, pages and actions relevant to that role.

The implementation must provide:

1. Role-aware Filament dashboard widgets
2. Role-aware navigation visibility
3. Permission-based resource access
4. Record-level post ownership restrictions
5. Role-aware post list queries
6. Role-aware table actions
7. Role-aware form fields
8. Secure direct-URL authorization
9. Safe user-management restrictions
10. Super Admin protections
11. Empty and unauthorized states
12. Focused authorization tests

This phase must not yet implement the complete editorial submission and review workflow. That will be handled in Phase 15D.

---

# Expected Role Experience

## Super Admin

Super Admin should have access to all appropriate Filament functionality, including:

* Complete dashboard
* Posts
* Users
* Roles and permissions
* Categories
* Tags
* Media
* Advertisements
* SEO
* Analytics
* Settings
* Existing system resources
* All permitted dashboard widgets

Super Admin must remain protected from accidental deletion, demotion or lockout.

## Admin

Admin should have access to:

* Administrative dashboard
* Posts
* Users, subject to Super Admin protections
* Categories
* Tags
* Media
* Advertisements
* SEO
* Analytics
* Other explicitly permitted resources

Admin should not automatically receive unrestricted role, permission or critical system-settings access unless Phase 15B explicitly granted those permissions.

## Editor

Editor should have access to:

* Editorial dashboard
* All editorial posts
* Pending-review indicators where existing statuses permit
* Categories
* Tags
* Media
* Editorial analytics
* Post review-related actions permitted by existing permissions

Editor must not access:

* Role management
* Permission management
* Critical settings
* Unauthorized user administration
* Advertisement administration unless explicitly permitted

## Reporter

Reporter should have access to:

* Reporter dashboard
* Create Post
* Own posts
* Own drafts
* Own submitted or returned posts where existing statuses support them
* Own media or permitted shared media
* Own profile

Reporter must not:

* View other reporters’ private drafts
* Edit other users’ posts
* Publish posts without permission
* Access user management
* Access roles or permissions
* Access system settings
* Access unrestricted analytics

## Author

Author should have access to:

* Author dashboard
* Create Post
* Own posts
* Own drafts
* Own profile

Author must remain more limited than Reporter through permissions, not through scattered role-name checks.

## Subscriber

Subscriber must not access the Filament panel.

Subscriber continues using the frontend `/dashboard`.

---

# Protected Boundaries

Do not:

* Create a second Filament staff panel unless the existing architecture clearly requires it
* Replace the current Filament panel
* Change the existing panel path unnecessarily
* Break existing Filament login
* Delete or recreate Filament resources
* Remove existing functionality without authorization evidence
* Rely only on hidden navigation for security
* Rely only on `canAccessPanel()` for resource security
* Authorize records only through table query filtering
* Add role checks directly throughout Blade or Filament views when permissions or policies are suitable
* Grant reporters or authors access to other users’ records
* Grant subscribers Filament access
* Give admins unrestricted control of Super Admin accounts
* Expose role and permission forms to unauthorized users
* Change public frontend routes
* Change article slugs
* Change post content
* Change imported post ownership unexpectedly
* Modify analytics schema unnecessarily
* Run destructive migrations or seeders
* Run `migrate:fresh`
* Run `db:wipe`

---

# Step 1 — Audit Phase 15B Completion

Before changing code, inspect the Phase 15B implementation and completion report where available.

Verify:

* Installed Filament version
* Existing panel ID
* Existing panel path
* Existing panel provider
* Existing dashboard class
* Existing dashboard widgets
* Existing resources
* Existing policies
* Existing role package
* Existing role names
* Existing permission names
* Existing `canAccessPanel()` implementation
* Existing Super Admin handling
* Existing post-login redirects
* Existing User Resource
* Existing Post Resource
* Existing Category Resource
* Existing Tag Resource
* Existing Media Resource
* Existing Advertisement Resource
* Existing SEO or Settings resources

Run:

```bash
php artisan route:list
php artisan about
```

If available:

```bash
composer show filament/filament
composer show spatie/laravel-permission
```

Do not assume Filament v3, v4 or v5 APIs. Use the installed version’s APIs.

---

# Step 2 — Create an Authorization Matrix

Before implementation, create a clear internal matrix based on actual seeded permissions.

At minimum, map:

| Resource or Area  | Super Admin |            Admin |         Editor |                Reporter |   Author | Subscriber |
| ----------------- | ----------: | ---------------: | -------------: | ----------------------: | -------: | ---------: |
| Filament panel    |         Yes |              Yes |            Yes |                     Yes |      Yes |         No |
| Dashboard         |        Full |            Admin |      Editorial |                Own work | Own work |         No |
| View all posts    |         Yes |              Yes |            Yes |                      No |       No |         No |
| View own posts    |         Yes |              Yes |            Yes |                     Yes |      Yes |         No |
| Create posts      |         Yes |              Yes |            Yes |                     Yes |      Yes |         No |
| Edit all posts    |         Yes |              Yes |            Yes |                      No |       No |         No |
| Edit own posts    |         Yes |              Yes |            Yes |                     Yes |      Yes |         No |
| Publish posts     |         Yes |              Yes |            Yes |                      No |       No |         No |
| Manage users      |         Yes |      Conditional |             No |                      No |       No |         No |
| Manage roles      |         Yes | Permission-based |             No |                      No |       No |         No |
| Manage categories |         Yes |              Yes |            Yes |  No or permission-based |       No |         No |
| Manage tags       |         Yes |              Yes |            Yes |  No or permission-based |       No |         No |
| Manage media      |         Yes |              Yes |            Yes |        Permission-based |  Limited |         No |
| View analytics    |         Yes |              Yes | Editorial only | Own only if implemented |       No |         No |

Do not hardcode this table independently from the actual permissions. Align it with the Phase 15B seeder.

If seeded permissions differ from the expected list, preserve the implemented names and document the mapping.

---

# Step 3 — Centralize Filament Access Decisions

Avoid duplicating authorization logic across every resource.

Create focused authorization helpers only where they improve clarity.

Possible locations:

```text
App\Support\Authorization
App\Services\Authorization
App\Filament\Support
```

Examples:

```text
FilamentAccess
PostAccessScope
DashboardVisibility
```

Do not create an unnecessary complex abstraction layer.

At minimum, resource access should use:

* Laravel policies
* User permissions
* Filament resource authorization methods
* Scoped Eloquent queries for record visibility

Use role-name checks only for exceptional protected behavior such as final Super Admin protection.

---

# Step 4 — Filament Panel Access

Audit and finalize `User::canAccessPanel()`.

Recommended principle:

```php
public function canAccessPanel(Panel $panel): bool
{
    if ($this->status !== 'active') {
        return false;
    }

    return $this->can('access admin panel');
}
```

Use the `status` condition only if the column exists.

Requirements:

* Subscriber denied
* User without `access admin panel` denied
* Inactive user denied where status is implemented
* Staff users with permission allowed
* Existing panel ID respected
* Permission cache behavior tested

Do not authorize panel access based solely on email domain unless already required by the project.

---

# Step 5 — Dashboard Strategy

Prefer one existing Filament panel with role-aware widgets rather than multiple panels.

Use:

* Widget `canView()`
* Permission checks
* Role-aware dashboard data
* Dashboard page authorization where supported
* Query-level ownership filtering

Do not create a separate dashboard URL for every role unless Filament architecture already uses separate pages.

The default dashboard may display different widgets according to the authenticated user’s permissions.

---

# Step 6 — Required Dashboard Widgets

Audit existing widgets first. Reuse and adapt them where possible.

Do not duplicate equivalent widgets.

## Global Staff Welcome Widget

Create or adapt a lightweight welcome/profile widget.

Show:

* User name
* Role
* Relevant dashboard description
* Quick action appropriate to role

Examples:

```text
Super Admin: System overview
Admin: Administration and publishing overview
Editor: Editorial review overview
Reporter: Your reporting workspace
Author: Your article workspace
```

Do not expose sensitive permission details unnecessarily.

## Super Admin and Admin Widgets

Where data already exists and queries are efficient, show:

* Total published posts
* Draft posts
* Pending or submitted posts
* Total users
* Total reporters/authors
* Total categories
* Total tags
* Total article views
* Recently created users
* Recently published posts

Widgets must check permissions individually.

Admin should not see widgets for areas it cannot access.

## Editor Widgets

Show only editorially relevant information:

* Posts pending review
* Posts requiring correction
* Scheduled posts
* Published today
* Recent reporter submissions
* Recently reviewed posts

Use actual existing status values.

Do not invent statuses if the current `PostStatus` enum differs.

## Reporter Widgets

Show only the authenticated user’s records:

* My drafts
* My submitted posts
* My posts needing correction
* My published posts
* My total views
* Recent review notes
* Create Post quick action

Every query must scope by:

```php
author_id = auth()->id()
```

unless verified legacy ownership requires another existing field.

## Author Widgets

Show only:

* My drafts
* My submitted posts
* My published posts
* Create Post action
* Own profile summary

## Widget Performance

Requirements:

* Avoid N+1 queries
* Use aggregate queries
* Do not load thousands of records into memory
* Use database counts
* Use indexed fields where available
* Avoid expensive analytics calculations on every dashboard request
* Use existing cached counters when appropriate

Do not add caching unless needed and clearly safe.

---

# Step 7 — Role-Aware Navigation

Each Filament resource and page must appear only when the user has appropriate permissions.

Use the installed Filament version’s supported methods, such as:

```php
canAccess()
canViewAny()
shouldRegisterNavigation()
getNavigationGroup()
getNavigationBadge()
```

Use correct APIs for the installed version.

Navigation groups may include:

```text
Editorial
Content
Users
Media
Monetization
SEO
Analytics
System
```

Recommended visibility:

## Super Admin

All permitted groups.

## Admin

All groups allowed by permissions, excluding protected permission-management or system areas when not granted.

## Editor

```text
Dashboard
Editorial
Posts
Categories
Tags
Media
Analytics
Profile
```

## Reporter

```text
Dashboard
My Posts
Create Post
Media
Profile
```

## Author

```text
Dashboard
My Posts
Create Post
Profile
```

Navigation visibility is only a convenience layer. Every resource must remain protected by policies and resource authorization.

---

# Step 8 — Post Resource Query Scoping

The Post Resource is the most important part of this phase.

Audit the existing `PostResource` and its pages before changing it.

Use a role-aware base query.

Recommended logic:

```php
public static function getEloquentQuery(): Builder
{
    $query = parent::getEloquentQuery();

    $user = auth()->user();

    if (! $user) {
        return $query->whereRaw('1 = 0');
    }

    if ($user->can('view all posts')) {
        return $query;
    }

    if ($user->can('view own posts')) {
        return $query->where('author_id', $user->id);
    }

    return $query->whereRaw('1 = 0');
}
```

Adapt to the installed Filament version.

Requirements:

* Super Admin sees all posts
* Admin sees all posts when permitted
* Editor sees all posts when permitted
* Reporter sees only own posts
* Author sees only own posts
* Unauthorized users see no posts
* Direct record URLs remain policy-protected
* Global scopes already used by the project are preserved
* Existing eager loading is retained
* Existing sorting is retained where safe
* Existing filters continue to work

Do not use only query scoping as authorization.

---

# Step 9 — Post Policy

Audit and strengthen the existing `PostPolicy`.

Required abilities should align with the project and installed Filament version:

```text
viewAny
view
create
update
delete
deleteAny
restore
restoreAny
forceDelete
forceDeleteAny
replicate
reorder
publish
review
```

Not every ability must exist if the resource does not support it.

## View logic

A user may view a post when:

* They can `view all posts`, or
* They can `view own posts` and `post.author_id === user.id`

## Create logic

Require:

```text
create posts
```

## Update logic

Allow when:

* User can `edit all posts`, or
* User can `edit own posts`
* The post belongs to the user
* The existing post status allows author editing

Do not invent status behavior that belongs to Phase 15D.

For now, preserve current editable-state logic and report gaps.

## Delete logic

Allow according to:

* `delete all posts`, or
* `delete own drafts`
* Ownership
* Draft-like status

Do not permit reporters or authors to delete published posts unless explicitly granted.

## Publish logic

Require:

```text
publish posts
```

Reporter and Author must not publish unless explicitly assigned that permission.

---

# Step 10 — Post Resource Actions

Audit all existing table, bulk and header actions.

Each action must have authorization visibility and server-side protection.

## Create Action

Visible only when:

```text
create posts
```

For Reporter and Author, automatically assign:

```php
author_id = authenticated user ID
```

Do not allow Reporter or Author to choose another author.

Admin, Editor or Super Admin may choose an author only if explicitly permitted by current architecture.

## Edit Action

Visible only when the Post Policy permits update.

## Delete Action

Visible only when permitted.

Bulk delete must not allow a Reporter or Author to delete unauthorized records.

## Publish Action

Visible only when:

```text
publish posts
```

Do not implement a new editorial publish workflow in this phase. Secure existing publish behavior only.

## Replicate Action

Disable unless:

* It already exists
* It is needed
* It has explicit permission and ownership handling

## Bulk Actions

All bulk actions must authorize every selected record.

Do not assume query scoping alone is sufficient.

---

# Step 11 — Post Form Field Restrictions

Audit the existing Post Resource form.

Role-aware field behavior must include:

## Author Field

For Reporter and Author:

* Hidden or read-only
* Automatically set to authenticated user
* Cannot be tampered with through request payloads

For Editor:

* Read-only unless permission explicitly allows reassignment

For Admin and Super Admin:

* Editable only when appropriate permission exists

Do not expose all users in the author selector unnecessarily.

Filter eligible authors to staff content roles where possible:

```text
super-admin
admin
editor
reporter
author
```

## Reviewer Fields

Fields such as:

```text
reviewed_by
reviewed_at
review_notes
```

must not be editable by Reporter or Author unless review notes are displayed read-only.

Full review actions belong to Phase 15D.

## Status Field

Reporter and Author must not freely select:

```text
published
scheduled
approved
rejected
```

unless existing permissions explicitly allow it.

In this phase:

* Secure the field
* Preserve existing workflow
* Do not redesign the full status lifecycle

## View Counter and Analytics Fields

Fields such as:

```text
views_count
visitor_id
post visit metrics
```

must not be editable in normal post forms.

## Ownership Enforcement

Set ownership server-side during creation.

Do not trust hidden form values.

---

# Step 12 — User Resource Access

Audit the existing Filament User Resource.

## Super Admin

May access all users, subject to final Super Admin protection.

## Admin

May access users only if granted:

```text
manage users
```

Admin must not:

* Delete final Super Admin
* Demote final Super Admin
* Assign `super-admin` unless explicitly permitted
* Grant permissions beyond its authority
* Modify protected system account fields
* Change referral identifiers without explicit business permission

## Editor, Reporter and Author

Must not access User Resource unless a separate profile page is intentionally provided.

## Subscriber

No Filament access.

## User Form Restrictions

Protect:

```text
refcode
ref_id
roles
permissions
status
email_verified_at
password
```

Rules:

* `refcode` should usually be read-only
* `ref_id` should not be casually changed
* Role assignment requires permission
* Permission assignment requires permission
* Password field should remain blank unless intentionally changed
* Existing password must not be overwritten with null or empty value
* Protected Super Admin handling must be enforced server-side

---

# Step 13 — Role and Permission Resource Access

If Role and Permission Filament Resources exist:

* Only users with `manage roles` may access Role Resource
* Only users with `manage permissions` may access Permission Resource
* Super Admin role must not be deleted
* Final Super Admin assignment must not be removed
* Sensitive permissions must not be granted by unauthorized users
* Public or staff profile forms must not expose permission management

If these resources do not exist, do not build a full permission-management UI unless already planned.

Report their absence for a later administration phase.

---

# Step 14 — Category and Tag Resources

Use existing permissions:

```text
manage categories
manage tags
```

Expected access:

* Super Admin: permitted
* Admin: permitted
* Editor: permitted where seeded
* Reporter: denied unless explicitly permitted
* Author: denied
* Subscriber: denied

Direct URLs must be denied when unauthorized.

Existing relationships and term data must remain unchanged.

---

# Step 15 — Media Resource Access

Audit the current media architecture.

Determine whether media uses:

* Custom media table
* Spatie Media Library
* Filament file uploads
* WordPress-imported attachments
* Custom Media Resource

Expected behavior:

## Super Admin/Admin/Editor

Access according to `manage media`.

## Reporter

May access media only if permitted.

Where ownership exists:

* Reporter should see own uploaded media
* Reporter should not delete media used by another user’s published post
* Reporter should not delete shared system assets
* Reporter should not alter imported media ownership without authorization

## Author

Use limited media access only if currently required.

Do not redesign the complete media library in this phase.

Preserve Phase 13 media-library behavior.

---

# Step 16 — Advertisement, SEO, Analytics and Settings Resources

Apply explicit permissions.

## Advertisements

Require:

```text
manage advertisements
```

## SEO

Require:

```text
manage seo
```

## Analytics

Require:

```text
view analytics
```

For Reporter dashboards, own-post aggregate metrics may be shown without granting unrestricted analytics-resource access.

## Settings

Require:

```text
manage settings
```

Settings access should normally remain limited to Super Admin and explicitly authorized Admin users.

Do not expose environment variables, secrets or credentials in Filament forms.

---

# Step 17 — Resource Badges

Where useful and efficient, add navigation badges.

Examples:

```text
Pending posts
My drafts
Submitted posts
Users
```

Requirements:

* Badge query must follow the same authorization scope
* Reporter badge counts only own posts
* Editor badge may count pending-review posts
* Admin badge may count all permitted posts
* Avoid expensive badge queries
* Do not show misleading counts for unauthorized records

Use actual existing post statuses.

---

# Step 18 — Profile Access

Staff users should have a safe route or Filament profile page for:

```text
name
email
password change
profile photo if already supported
referral code as read-only
```

Users must not edit:

```text
roles
permissions
status
ref_id
email_verified_at
```

through their own profile.

Do not build a second conflicting profile system if one already exists.

---

# Step 19 — Unauthorized and Empty States

Ensure good behavior when:

* User lacks permission
* User opens a direct unauthorized URL
* Reporter has no posts
* Author has no posts
* Editor has no pending submissions
* Widget data is empty
* User has a staff role but missing permissions
* Permission cache is stale

Expected responses:

* Correct 403 response for unauthorized direct access
* No data leakage
* Helpful empty state for legitimate empty collections
* No stack traces in production
* No silent access escalation

---

# Step 20 — Super Admin Protection

Preserve and strengthen Phase 15B protections.

Required safeguards:

* Super Admin role cannot be accidentally deleted
* Final Super Admin assignment cannot be removed
* Final Super Admin user cannot be deleted
* Admin cannot promote themselves to Super Admin unless explicitly authorized
* Admin cannot demote the protected Super Admin
* Reporter, Author and Editor cannot access role controls
* Subscriber cannot access Filament
* Super Admin protection must work through direct requests, not only hidden buttons

Use a policy, service or validation rule rather than only UI visibility.

---

# Step 21 — Query Security

Audit all custom Filament queries.

Requirements:

* Ownership scope is applied before pagination
* Search cannot reveal unauthorized records
* Filters cannot escape the ownership scope
* Relationship selectors do not expose protected users unnecessarily
* Global search respects authorization
* Dashboard widgets scope results correctly
* Export actions respect policies and record scopes
* Bulk actions respect policies
* Soft-deleted records remain protected

Disable global search for resources where authorization cannot be guaranteed.

---

# Step 22 — Filament Global Search

Audit existing global search.

Expected behavior:

## Super Admin/Admin/Editor

Search only resources and records they are permitted to view.

## Reporter/Author

Post search must return only their own records.

## Subscriber

No Filament global search.

If the installed Filament version does not reliably apply the existing scoped resource query to global search, explicitly customize or disable global search for sensitive resources.

---

# Step 23 — Notifications

Use Filament notifications for successful or denied state-changing actions where appropriate.

Examples:

```text
Post created
Post updated
Unauthorized action denied
Profile updated
```

Do not disclose sensitive authorization internals.

Do not introduce full editorial notifications yet. Those belong to Phase 15D.

---

# Step 24 — Tests

Add focused tests while preserving all existing tests.

## Panel Access Tests

* Super Admin can access Filament
* Admin with permission can access Filament
* Editor with permission can access Filament
* Reporter with permission can access Filament
* Author with permission can access Filament
* Subscriber cannot access Filament
* User without role cannot access Filament
* Inactive staff user cannot access when status exists
* Removing `access admin panel` blocks access

## Dashboard Widget Tests

* Super Admin sees full authorized widgets
* Admin sees only permitted widgets
* Editor sees editorial widgets
* Reporter sees own-post widgets
* Author sees own-post widgets
* Subscriber cannot load dashboard
* Reporter counts exclude other users’ posts
* Author counts exclude other users’ posts
* Widget queries do not expose unauthorized records

## Navigation Tests

* Each role sees only allowed navigation items
* Hidden navigation resources remain inaccessible by direct URL
* Subscriber sees no Filament navigation
* Role and Permission Resources are hidden and denied for unauthorized users

## Post Resource Tests

* Super Admin can view all posts
* Admin can view all posts when permitted
* Editor can view all posts when permitted
* Reporter sees only own posts
* Author sees only own posts
* Reporter cannot view another user’s post URL
* Author cannot edit another user’s post
* Reporter can create a post
* Created Reporter post automatically receives Reporter as author
* Author field cannot be tampered with
* Reporter cannot publish without permission
* Reporter cannot edit analytics counters
* Reporter cannot edit reviewer fields
* Unauthorized bulk actions fail
* Filters do not escape ownership scope
* Global search does not expose other users’ posts

## User Resource Tests

* Super Admin can access User Resource
* Authorized Admin can access User Resource
* Editor cannot access User Resource
* Reporter cannot access User Resource
* Author cannot access User Resource
* Admin cannot delete final Super Admin
* Admin cannot remove final Super Admin role
* Unauthorized user cannot assign roles
* Unauthorized user cannot assign permissions
* Empty password does not overwrite existing password
* Refcode is protected from normal editing

## Category and Tag Tests

* Authorized roles can access
* Unauthorized roles receive 403
* Navigation visibility matches permission
* Existing category and tag records remain unchanged

## Media Tests

* Authorized roles can access
* Reporter access follows current ownership rules
* Unauthorized deletion is blocked
* Imported media remains intact

## Other Resource Tests

Test Advertisement, SEO, Analytics and Settings Resources according to existing resources and seeded permissions.

## Existing Data Safety Tests

* User count unchanged
* Post count unchanged
* Existing post ownership unchanged except deliberate test records
* Existing view counts unchanged
* Existing referral data unchanged
* Existing categories unchanged
* Existing tags unchanged
* Existing media unchanged

---

# Step 25 — Validation Commands

Run:

```bash
php artisan optimize:clear
php artisan route:list
php artisan permission:cache-reset
php artisan test
```

Run focused tests where practical:

```bash
php artisan test --filter=Filament
php artisan test --filter=Dashboard
php artisan test --filter=PostResource
php artisan test --filter=PostPolicy
php artisan test --filter=UserResource
php artisan test --filter=Authorization
```

Run formatting only for modified files:

```bash
vendor/bin/pint --test
vendor/bin/pint
```

If PHPStan or Larastan exists:

```bash
vendor/bin/phpstan analyse
```

Do not fix unrelated pre-existing failures unless required for this phase.

---

# Completion Criteria

Phase 15C is complete only when:

* Existing Filament panel is retained
* Subscriber is denied Filament access
* Authorized staff roles can access Filament
* Dashboard widgets change appropriately by role and permission
* Reporter dashboard data is scoped to the Reporter
* Author dashboard data is scoped to the Author
* Editor dashboard shows editorial information only
* Admin dashboard shows only authorized administrative information
* Super Admin retains complete appropriate access
* Navigation is permission-aware
* Direct URLs are policy-protected
* Post Resource is ownership-scoped
* Post Policy enforces view, update and delete permissions
* Reporter and Author cannot access other users’ private posts
* Reporter and Author cannot manipulate author ownership
* Reporter and Author cannot publish without permission
* Analytics and review fields are protected
* User Resource is restricted
* Super Admin protections work
* Category, Tag, Media and other resources use permissions
* Global search does not leak unauthorized records
* Bulk actions cannot bypass authorization
* Existing users remain intact
* Existing posts remain intact
* Existing referral data remains intact
* Existing view counters remain intact
* Existing categories, tags and media remain intact
* Relevant tests pass
* No destructive database command was used

---

# Required Completion Report

Return a structured report with the following sections.

## 1. Filament Audit

Report:

* Filament version
* Existing panel ID
* Existing panel path
* Existing dashboard implementation
* Existing widgets
* Existing resources
* Existing policies
* Existing global search behavior

## 2. Authorization Matrix

Provide the final role-to-resource and role-to-dashboard matrix actually implemented.

## 3. Dashboard Implementation

For each role, report:

* Widgets visible
* Metrics shown
* Query scopes
* Quick actions
* Empty states

## 4. Navigation Implementation

List:

* Navigation groups
* Resource visibility rules
* Page visibility rules
* Permission checks used

## 5. Post Resource Security

Explain:

* Base query scoping
* Policy implementation
* Ownership enforcement
* Author-field behavior
* Status-field behavior
* Reviewer-field restrictions
* View-counter protection
* Table actions
* Bulk actions
* Global search behavior

## 6. User Resource Security

Explain:

* Who can access users
* Role assignment controls
* Permission assignment controls
* Password handling
* Referral-field protection
* Super Admin protections

## 7. Other Resource Access

Report permissions and behavior for:

* Categories
* Tags
* Media
* Advertisements
* SEO
* Analytics
* Settings
* Any other existing Filament resources

## 8. Super Admin Protection

Explain:

* Final Super Admin protection
* Role-removal protection
* User-deletion protection
* Promotion restrictions
* Direct-request protections

## 9. Files Changed

List every created and modified file.

## 10. Database Changes

List any migration or data changes.

This phase should normally require no destructive schema changes.

## 11. Tests and Validation

Report:

* Commands executed
* Total tests
* Total assertions
* Failed tests
* Pre-existing failures
* New failures
* Formatting result
* Static-analysis result

## 12. Existing Data Verification

Report before and after counts for:

```text
users
posts
categories
tags
media
```

Also confirm:

* Existing post ownership preserved
* Existing view counters preserved
* Existing referral data preserved

## 13. Remaining Work

Clearly reserve the following for later phases:

```text
Phase 15D — Reporter and Editor Editorial Workflow
Phase 15E — Subscriber Frontend Dashboard
Phase 15F — Security Hardening and Full Authorization Audit
```

Do not claim completion if:

* Subscriber can access Filament
* Reporter can view another reporter’s private post
* Author ownership can be tampered with
* Navigation is hidden but direct URLs remain accessible
* Bulk actions bypass policies
* Global search leaks records
* Admin can remove the final Super Admin
* Existing record counts change unexpectedly
* Existing imported data is modified without authorization
* Relevant tests fail
