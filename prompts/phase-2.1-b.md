# Daily Samvad — Version 2.1

## Phase 2.1-B: Role, Permission and Access-Control Architecture

You are working on the existing Daily Samvad Laravel application.

The application currently uses:

* Laravel 13
* Filament 5
* Livewire 4
* Spatie Laravel Permission
* A shared user model for public authentication and Filament authentication

Phase 2.1-A baseline audit has already been completed.

This phase must establish a clear, secure and extensible role-based access-control architecture before separate role dashboards are implemented.

---

# 1. Primary Objective

Audit, normalize and complete the application's role and permission architecture so that all current and future dashboard, resource, workflow, SEO, media and analytics access can be controlled through permissions.

The final architecture must be:

* Permission-driven
* Policy-backed
* Compatible with Laravel 13
* Compatible with Filament 5
* Compatible with Livewire 4
* Compatible with Spatie Laravel Permission
* Safe for existing users and imported data
* Extensible for future custom roles
* Testable
* Free from unnecessary role-name checks

Do not create the role-specific dashboards in this phase.

---

# 2. Current Audit Findings

The Version 2.1 baseline audit confirmed the following roles currently exist:

```text
super-admin
admin
editor
reporter
author
reviewer
seo-manager
media-manager
subscriber
```

Expected but currently missing roles:

```text
analytics-manager
contributor
```

Known access-control gaps:

* Reviewer lacks proper Filament panel access.
* SEO Manager lacks proper Filament panel access.
* Media Manager lacks proper Filament panel access.
* SEO Manager lacks the required `manage seo` permission.
* Some access may depend on role names instead of permissions.
* Separate role dashboards have not yet been implemented.
* Analytics permissions and access rules are incomplete.
* Reviewer assignment and workflow actions remain incomplete and will be handled in a later workflow phase.
* Existing users and role assignments must not be disturbed.

Use the audit documents under:

```text
docs/version-2.1/
```

as the baseline source of truth.

---

# 3. Protected Boundaries

Do not modify or disturb:

* Existing users
* Existing passwords
* Existing user IDs
* Existing role assignments unless explicitly required to repair invalid assignments
* Imported WordPress users
* Imported posts
* Imported media
* SEO metadata
* Public article routes
* Legacy redirects
* Existing post statuses
* WordPress importer architecture
* Featured-image mappings
* Existing production environment configuration
* `.env`
* Storage paths
* Deployment configuration
* Existing database records unrelated to RBAC

Do not run destructive commands.

Prohibited commands include:

```bash
php artisan migrate:fresh
php artisan migrate:refresh
php artisan migrate:reset
php artisan db:wipe
php artisan permission:cache-reset --force
composer update
npm update
git reset --hard
git clean -fd
```

Do not manually delete role or permission records from the database.

---

# 4. Architecture Principles

Implement the following principles.

## 4.1 Permission-Driven Access

Authorization must primarily use permissions, policies and gates.

Avoid authorization such as:

```php
$user->hasRole('editor')
$user->role === 'admin'
if ($role === 'reporter')
```

when the real intent can be expressed through a permission.

Preferred examples:

```php
$user->can('view all posts')
$user->can('review posts')
$user->can('publish posts')
$user->can('manage seo')
```

Role checks may remain only where a role itself has a genuine business meaning that cannot be represented safely by a permission.

Document every unavoidable role-name check.

---

## 4.2 Least Privilege

Each role must receive only the permissions required for its intended responsibilities.

Do not grant broad administrative permissions simply to make Filament navigation appear.

---

## 4.3 Super Admin Override

The `super-admin` role must have a centralized authorization override using Laravel's Gate architecture.

Preferred behavior:

```php
Gate::before(function (User $user, string $ability) {
    return $user->hasRole('super-admin') ? true : null;
});
```

Do not duplicate every permission manually for super-admin if an existing reliable override already exists.

Ensure the override:

* Does not affect inactive users
* Does not bypass account suspension rules
* Does not grant access before authentication
* Is covered by automated tests

---

## 4.4 Active Account Enforcement

Inactive or disabled users must not access:

* Filament
* Protected public account routes
* Staff resources
* Dashboard widgets
* Editorial actions
* SEO tools
* Media-management tools
* Analytics tools

Preserve existing active-account behavior.

---

## 4.5 Data Scope

Permissions must distinguish between:

```text
own records
assigned records
team records
all records
```

At minimum, post access must support:

```text
view own posts
view assigned posts
view all posts
create posts
update own posts
update assigned posts
update all posts
delete own posts
delete all posts
```

Do not implement fake permissions that are never enforced.

Every scoped permission must be reflected in:

* Policies
* Queries
* Filament resources
* Table queries
* Record actions
* Bulk actions
* Global search
* Relation managers where applicable

---

# 5. Canonical Roles

Normalize the role architecture around these canonical roles:

```text
super-admin
admin
editor
reviewer
reporter
seo-manager
media-manager
analytics-manager
contributor
subscriber
```

The existing `author` role must be audited.

Determine whether `author` is:

* Actively used
* A historical alias
* Functionally identical to reporter
* Functionally identical to contributor
* Required for backward compatibility

Do not delete or rename `author` without verified evidence and a safe migration strategy.

If `author` must remain, document whether it is:

```text
active canonical role
legacy compatibility role
deprecated role
alias-style role
```

---

# 6. Role Intent Definitions

Implement and document the intended responsibility of every role.

## 6.1 Super Admin

Intended responsibilities:

* Complete application access
* Manage administrators
* Manage roles and permissions
* Manage system settings
* Manage editorial operations
* Manage SEO
* Manage media
* View analytics
* Access technical/system dashboards
* View queue, cache and health information where implemented

Super Admin access must use the centralized override.

---

## 6.2 Admin

Intended responsibilities:

* Access Filament
* Manage users, excluding protected super-admin operations
* Manage posts
* Manage categories and tags
* Manage media
* Review and publish content
* Manage general editorial configuration
* View operational analytics
* View administrative dashboard data

Admin must not automatically receive unrestricted role-management authority unless explicitly justified.

---

## 6.3 Editor

Intended responsibilities:

* Access Filament
* View all editorial posts
* Edit all editorial posts
* Review submissions
* Request corrections
* Approve posts
* Schedule posts
* Publish posts
* Manage categories and tags where required
* View editorial analytics
* View reporter performance
* Assign reviewers or editors where later workflow architecture supports it

Editor must not manage users, roles, system settings or infrastructure.

---

## 6.4 Reviewer

Intended responsibilities:

* Access Filament
* View assigned review items
* View the review queue where authorized
* Review submitted posts
* Add review notes
* Request corrections
* Recommend approval or rejection
* View relevant editorial history

Reviewer must not:

* Publish posts unless separately granted
* Manage users
* Manage roles
* Manage SEO configuration
* Manage system settings
* View unrelated private drafts by default

Actual review transitions will be completed in the workflow phase.

---

## 6.5 Reporter

Intended responsibilities:

* Access Filament
* Create posts
* View own posts
* Edit own eligible posts
* Submit own posts for review
* View editorial feedback on own posts
* View own publishing status
* View own article performance
* Upload media where permitted

Reporter must not:

* Review other reporters' posts
* Publish posts
* Manage users
* Manage roles
* Manage SEO settings
* View administrative analytics

---

## 6.6 SEO Manager

Intended responsibilities:

* Access Filament
* View published and editorial posts as required
* Update SEO metadata
* Manage canonical metadata
* Review missing SEO fields
* Review schema readiness
* Review sitemap and robots status
* Review Google News readiness
* View SEO-related analytics

SEO Manager must not:

* Publish editorial content unless separately granted
* Change article body content except where explicitly authorized
* Manage users
* Manage roles
* Manage infrastructure

Ensure `manage seo` exists and is assigned correctly.

---

## 6.7 Media Manager

Intended responsibilities:

* Access Filament
* View media
* Upload media
* Update media metadata
* Edit alt text and captions
* Identify unused media
* Review missing or broken media
* Manage future optimization queues
* Attach media to eligible content where permitted

Media Manager must not:

* Publish posts
* Manage users
* Manage roles
* Change editorial content unrelated to media
* Manage SEO settings

---

## 6.8 Analytics Manager

Intended responsibilities:

* Access Filament
* View analytics dashboards
* View post, author, category and search analytics
* Export analytics where later supported
* View aggregated metrics
* View reporting trends

Analytics Manager must not:

* Edit posts
* Publish posts
* Manage users
* Manage roles
* Manage SEO
* Manage media
* View unnecessary personally identifiable visitor data

Create this role if it does not exist.

---

## 6.9 Contributor

Intended responsibilities:

* Access only the content-creation surfaces explicitly required
* Create draft posts
* View own drafts
* Edit own drafts
* Submit content to the editorial process if allowed

Contributor must not:

* Publish
* Review
* Approve
* Access analytics
* Manage media globally
* Manage taxonomies
* Manage users

Create this role if it does not exist.

Clearly distinguish contributor from reporter.

Recommended distinction:

```text
Contributor:
External or limited content creator with draft-only access.

Reporter:
Internal newsroom user with full own-post workflow access and personal performance metrics.
```

---

## 6.10 Subscriber

Intended responsibilities:

* Use the public subscriber dashboard
* Manage their own profile
* Manage bookmarks or saved content where implemented
* Access public account features

Subscriber must not access Filament.

Preserve existing public dashboard behavior.

---

# 7. Canonical Permission Catalogue

Audit existing permissions first.

Reuse correctly named existing permissions where possible.

Do not create duplicate permissions with slightly different names.

Normalize the permission catalogue into logical groups.

## 7.1 Panel Access

```text
access admin panel
```

---

## 7.2 Post Permissions

```text
view own posts
view assigned posts
view all posts
create posts
update own posts
update assigned posts
update all posts
delete own posts
delete all posts
submit posts for review
review posts
request post corrections
approve posts
reject posts
schedule posts
publish posts
archive posts
restore posts
```

Only create permissions supported by real application behavior or the immediately planned workflow architecture.

Do not add decorative unused permissions.

---

## 7.3 Taxonomy Permissions

```text
view categories
manage categories
view tags
manage tags
```

---

## 7.4 Media Permissions

```text
view media
upload media
update own media
update all media
delete own media
delete all media
manage media
```

Avoid assigning both granular permissions and `manage media` unless the policy strategy clearly defines their relationship.

---

## 7.5 SEO Permissions

```text
view seo
manage seo
```

Where useful, add more granular permissions only when corresponding features exist:

```text
edit post seo
manage sitemap
manage robots
manage schema
```

Do not create permissions for unimplemented screens.

---

## 7.6 Analytics Permissions

```text
view own analytics
view editorial analytics
view all analytics
export analytics
```

Do not expose sensitive visitor-level information merely because a role has analytics access.

---

## 7.7 User and Role Permissions

```text
view users
create users
update users
disable users
delete users
manage users
view roles
manage roles and permissions
```

Protect super-admin users from unauthorized modification.

---

## 7.8 Settings and System Permissions

Only preserve or add permissions that match existing or immediately planned features:

```text
manage settings
view system health
view queue health
view cache health
```

Do not implement system-health interfaces in this phase.

---

# 8. Permission Naming Rules

Permission names must:

* Use lowercase
* Use spaces consistently
* Use clear verbs
* Avoid duplicate synonyms
* Avoid mixing singular and plural inconsistently
* Match current application conventions unless migration is justified

Examples of duplication to avoid:

```text
edit post
edit posts
update post
update posts
manage posts
```

Choose one canonical meaning for each action.

Create a mapping document for:

```text
existing permission
canonical permission
action taken
compatibility notes
```

Do not silently remove or rename existing permissions that may be assigned to users or roles.

---

# 9. Seeder and Synchronization Requirements

Create or update an idempotent RBAC seeder or synchronization service.

It must:

* Safely create missing roles
* Safely create missing permissions
* Assign canonical permissions to roles
* Preserve existing users
* Preserve existing role assignments
* Avoid duplicate roles
* Avoid duplicate permissions
* Be safe to run more than once
* Avoid deleting unknown custom roles
* Avoid deleting unknown custom permissions
* Avoid changing user assignments automatically
* Use the correct guard
* Clear permission cache safely through the package-supported method where necessary

Do not use destructive `sync` operations unless the exact intended permission set is fully controlled and tested.

If `syncPermissions()` is used, ensure it does not unintentionally remove custom or legacy permissions required by the application.

Prefer an explicit, documented strategy.

---

# 10. Database Migration Requirements

Only create a migration if schema changes are genuinely required.

Possible valid reasons:

* Adding a user active-status field if missing
* Adding role-related indexes if clearly missing
* Adding a safe compatibility field required by authorization

Do not create a migration merely to insert roles or permissions when a seeder or synchronization service is more appropriate.

Any migration must be:

* Backward-compatible
* Non-destructive
* Safe for production records
* Reversible where practical
* Covered by tests

---

# 11. Policy Architecture

Audit and complete Laravel policies for all relevant models.

At minimum inspect:

```text
PostPolicy
MediaPolicy
UserPolicy
CategoryPolicy
TagPolicy
```

Create missing policies only where actual protected resources exist.

Policies must enforce:

* Own-record access
* Assigned-record access
* All-record access
* Workflow action access
* Publishing access
* SEO access
* Media access
* User-management protection

Avoid authorization logic duplicated across:

* Controllers
* Filament resources
* Livewire components
* Blade templates
* Services

Centralize business authorization in policies and gates.

---

# 12. Post Policy Requirements

The post policy must clearly cover relevant abilities such as:

```text
viewAny
view
create
update
delete
restore
forceDelete
submitForReview
review
requestCorrections
approve
reject
schedule
publish
archive
manageSeo
viewAnalytics
```

Implement only abilities supported by current behavior or the documented Version 2.1 architecture.

Rules must consider:

* User active status
* Ownership
* Assignment
* Current post status
* Permission
* Protected published content
* Reviewer/editor scope

Do not allow a user to bypass workflow rules simply because they can edit a record.

---

# 13. Filament Panel Access

Update panel access so intended staff roles can enter Filament through permissions.

Panel access must depend on:

```text
active user
AND
access admin panel permission
```

Expected Filament-access roles after successful implementation:

```text
super-admin
admin
editor
reviewer
reporter
seo-manager
media-manager
analytics-manager
contributor
```

Expected non-Filament role:

```text
subscriber
```

Do not hardcode a role list inside `canAccessPanel()` unless no permission-based alternative is possible.

Preferred behavior:

```php
return $this->is_active
    && $this->can('access admin panel');
```

Adapt this to the actual user model and existing code conventions.

---

# 14. Filament Resource Authorization

Audit every Filament resource.

For each resource verify:

* Navigation visibility
* Resource access
* List query scope
* Record view access
* Create access
* Edit access
* Delete access
* Bulk-action access
* Global-search access
* Relation-manager access
* Header actions
* Table actions

A hidden navigation item is not sufficient authorization.

The backend query and policy must still protect the resource.

---

# 15. Navigation Visibility

Filament navigation must be permission-driven.

Examples:

```text
Posts:
Visible to users with relevant post permissions.

Categories:
Visible to users with category access.

Tags:
Visible to users with tag access.

Media:
Visible to users with media access.

Users:
Visible to users with user-management access.

SEO:
Visible to users with SEO access.

Analytics:
Visible to users with analytics access when the feature exists.

Roles:
Visible only to users allowed to manage roles and permissions.
```

Do not create placeholder navigation items for unimplemented features.

---

# 16. Dashboard Preparation

Do not create dashboards or widgets in this phase.

However, establish reusable access methods needed by the next dashboard phase.

Examples:

```php
$user->canViewAdministrativeDashboard()
$user->canViewEditorialDashboard()
$user->canViewReviewerDashboard()
$user->canViewReporterDashboard()
$user->canViewSeoDashboard()
$user->canViewMediaDashboard()
$user->canViewAnalyticsDashboard()
```

These methods must be permission-driven.

Avoid returning true solely from role names.

If dedicated methods are unnecessary because policies or permission checks already provide a clean solution, document the preferred pattern instead of creating redundant methods.

---

# 17. User Management Protection

Protect high-privilege users.

Rules must prevent unauthorized users from:

* Editing a super-admin
* Disabling a super-admin
* Deleting a super-admin
* Removing the last active super-admin
* Assigning the super-admin role
* Granting `manage roles and permissions`
* Escalating their own role
* Assigning permissions they do not control

Super Admin self-protection rules must be reasonable and documented.

Do not lock the application permanently by preventing all legitimate super-admin maintenance.

---

# 18. Role Assignment UI

Audit the existing Filament user-management form.

If role assignment already exists, secure it.

Requirements:

* Display only assignable roles
* Prevent unauthorized privilege escalation
* Preserve existing roles when the acting user lacks assignment authority
* Validate role guard
* Prevent subscriber-only managers from granting staff access
* Prevent admins from assigning super-admin unless explicitly authorized
* Show clear validation errors
* Avoid mass-assignment vulnerabilities

Do not create a full custom role-builder UI in this phase unless one already exists and only requires correction.

---

# 19. Existing User Compatibility

After implementation verify:

* Existing super-admin can still log in
* Existing admin can still log in
* Existing editor can still log in
* Existing reporter can still log in
* Existing subscriber still uses public dashboard
* Existing role assignments remain intact
* Existing direct permissions remain intact
* Existing inactive-user restrictions remain intact

Do not expose real passwords or sensitive user information in test output or reports.

---

# 20. Required Tests

Create or update focused automated tests.

## 20.1 Role and Permission Seeder Tests

Verify:

* Canonical roles exist
* Canonical permissions exist
* Seeder is idempotent
* Duplicate roles are not created
* Duplicate permissions are not created
* Existing custom roles are preserved
* Existing user role assignments are preserved
* Correct guard is used

---

## 20.2 Filament Access Tests

Verify:

* Active super-admin can access Filament
* Active admin can access Filament
* Active editor can access Filament
* Active reviewer can access Filament
* Active reporter can access Filament
* Active SEO manager can access Filament
* Active media manager can access Filament
* Active analytics manager can access Filament
* Active contributor can access Filament where intended
* Subscriber cannot access Filament
* Inactive staff user cannot access Filament
* Unauthenticated user is redirected to login

---

## 20.3 Resource Access Tests

Verify representative access for:

* Posts
* Categories
* Tags
* Media
* Users
* Roles and permissions
* SEO-related resources if they exist

Test both:

* Navigation/resource visibility
* Direct URL/backend authorization

---

## 20.4 Post Scope Tests

Verify:

* Reporter sees own eligible posts
* Reporter cannot see unrelated private drafts
* Reviewer sees only assigned or authorized review records
* Editor sees editorial records
* SEO manager sees only records required for SEO work
* Contributor sees own drafts
* Admin sees authorized global scope
* Subscriber cannot access staff posts resource

---

## 20.5 Privilege Escalation Tests

Verify:

* Admin cannot grant super-admin without authority
* Editor cannot assign roles
* Reporter cannot assign roles
* Reviewer cannot publish without permission
* SEO manager cannot publish without permission
* Media manager cannot edit unrelated post content
* Subscriber cannot access Filament
* Inactive users cannot use protected actions

---

## 20.6 Super Admin Tests

Verify:

* Super-admin override works
* Override does not apply to unauthenticated requests
* Override does not bypass active-account restrictions
* Last active super-admin cannot be removed unintentionally

---

# 21. Test Execution

Run focused tests first.

Suggested commands:

```bash
php artisan test tests/Feature/Auth
php artisan test tests/Feature/Authorization
php artisan test tests/Feature/Filament
php artisan test tests/Feature/Roles
php artisan test tests/Feature/Permissions
```

Adapt paths to the actual test structure.

Then run the complete suite:

```bash
php artisan test
```

If the existing unrelated HTTP 419 baseline failure remains, clearly distinguish:

```text
pre-existing failure
new regression
fixed failure
```

Do not hide or skip failures merely to produce a green result.

---

# 22. Documentation Deliverables

Create or update:

```text
docs/version-2.1/phase-2.1-b-rbac-implementation.md
docs/version-2.1/canonical-role-catalogue.md
docs/version-2.1/canonical-permission-catalogue.md
docs/version-2.1/role-permission-matrix.md
docs/version-2.1/legacy-role-compatibility.md
docs/version-2.1/filament-access-matrix.md
docs/version-2.1/rbac-security-rules.md
```

Documentation must include:

* Role intent
* Permission intent
* Final role-permission matrix
* Legacy `author` handling
* Panel-access rules
* Resource-access rules
* Data-scope rules
* Super-admin protection
* Known deferred workflow items
* Tests performed
* Remaining risks

Do not include secrets or personal user data.

---

# 23. Completion Criteria

Phase 2.1-B is complete only when:

* Canonical roles are documented.
* Missing approved roles are safely created.
* Canonical permissions are documented.
* Missing required permissions are safely created.
* Reviewer has intended Filament access.
* SEO Manager has intended Filament access.
* Media Manager has intended Filament access.
* Analytics Manager architecture exists.
* Contributor architecture exists.
* Subscriber remains excluded from Filament.
* `manage seo` is correctly implemented and assigned.
* Existing user assignments are preserved.
* Existing direct permissions are preserved.
* Active-user restrictions remain enforced.
* Panel access is permission-driven.
* Filament resources enforce backend authorization.
* Role assignment cannot cause unauthorized privilege escalation.
* Policies and gates cover the relevant resources.
* Seeder or synchronization logic is idempotent.
* Focused tests pass.
* Full-suite result is reported honestly.
* Documentation is complete.
* No dashboard feature is implemented.

---

# 24. Deferred Items

Do not implement the following in this phase:

* Separate role dashboards
* Dashboard UI redesign
* Reviewer assignment engine
* Full editorial workflow history
* Rejection/correction workflow completion
* Redis activation
* Queue optimization
* Scheduled publishing
* Full-page caching
* Search engine integration
* Analytics collection
* Image optimization
* Lazy-loading redesign
* News-Man features

These belong to later Version 2.1 phases.

---

# 25. Required Completion Report Format

Return the completion report using this exact structure:

## 1. Executive Summary

## 2. Audit Findings

## 3. Roles Created or Updated

## 4. Permissions Created or Updated

## 5. Final Role-Permission Matrix

## 6. Legacy Author Role Decision

## 7. Super Admin Architecture

## 8. Active Account Enforcement

## 9. Filament Panel Access

## 10. Filament Resource Authorization

## 11. Post Data-Scoping Rules

## 12. User and Role Assignment Security

## 13. Policies and Gates

## 14. Seeder or Synchronization Architecture

## 15. Database Changes

## 16. Automated Tests Added or Updated

## 17. Focused Test Results

## 18. Full Test-Suite Result

## 19. Backward-Compatibility Verification

## 20. Deferred Workflow Items

## 21. Documentation Created

## 22. Files Created or Modified

## 23. Commands Executed

## 24. Risks and Open Questions

## 25. Final Phase Decision

The final phase decision must be one of:

```text
COMPLETE
COMPLETE WITH CONDITIONS
INCOMPLETE
```

Explain the decision using verified evidence.

---

# 26. Strict Rules

* Inspect the current implementation before changing it.
* Do not assume file names or architecture.
* Follow existing project conventions.
* Do not duplicate existing permissions.
* Do not delete custom roles.
* Do not delete custom permissions.
* Do not change existing user assignments unnecessarily.
* Do not expose user passwords, password hashes or secrets.
* Do not alter `.env`.
* Do not activate Redis.
* Do not implement dashboards.
* Do not implement workflow transitions.
* Do not modify imported content.
* Do not run destructive database commands.
* Do not claim tests passed unless they were executed successfully.
* Clearly distinguish pre-existing failures from new regressions.
* Preserve backward compatibility wherever safely possible.
