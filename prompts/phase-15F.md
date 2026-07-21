# Phase 15F — Security Hardening and Full Authorization Audit

## Project Context

This is an existing Laravel news portal migrated from WordPress.

The project contains production-like data, including approximately 13,628 imported posts.

The following foundations should already exist:

### Phase 15A

* User referral codes
* Referrer relationships
* Post author and reviewer relationships
* Editorial metadata
* Existing post view-counter preservation
* `post_visits` analytics foundation

### Phase 15B

* Login
* Signup
* Logout
* Password reset
* Email-verification decision
* Roles and permissions
* Subscriber default role
* Staff and subscriber redirects
* Filament access rules
* Super Admin protection foundation

### Phase 15C

* Role-aware Filament dashboard
* Navigation authorization
* Resource authorization
* Record ownership scoping
* Direct-URL protection
* User Resource restrictions
* Super Admin protections

### Phase 15D

* Reporter → Editor → Publish workflow
* Controlled status transitions
* Review queue
* Correction and rejection flows
* Scheduling and publishing
* Editorial audit trail
* Notifications and workflow tests

### Phase 15E

* Subscriber frontend dashboard
* Profile and password management
* Referral dashboard
* Saved articles
* Reading history
* Notifications
* Preferences
* Subscriber ownership and privacy rules

This phase is the final security and authorization phase for the Phase 15 user, staff, editorial and subscriber system.

The goal is not to add major new features. The goal is to audit, attack-test, harden and verify the complete implementation from Phase 15A through Phase 15E.

---

# Primary Objective

Perform a complete security-hardening and authorization audit covering:

1. Authentication
2. Registration
3. Password reset
4. Email verification
5. Session security
6. Login throttling
7. Role and permission enforcement
8. Filament panel access
9. Filament resource access
10. Post ownership
11. Editorial workflow transitions
12. Super Admin protection
13. Subscriber dashboard ownership
14. Referral privacy
15. Saved-article ownership
16. Reading-history privacy
17. Notification ownership
18. Mass-assignment protection
19. Insecure direct-object reference protection
20. CSRF protection
21. Open-redirect protection
22. Cross-site scripting protection
23. SQL-injection resistance
24. File-upload security
25. Rate limiting
26. Logging and sensitive-data protection
27. Security headers
28. Cookie and session configuration
29. Production configuration review
30. Regression and adversarial tests
31. Existing-data verification
32. Final authorization matrix validation

This phase must produce a clear, evidence-based report.

Do not claim the system is secure merely because routes are hidden or tests pass.

---

# Protected Boundaries

Do not:

* Delete users
* Delete posts
* Reassign existing post authors
* Reset existing passwords
* Change existing referral codes
* Change existing referral relationships without evidence
* Change imported post slugs
* Change imported publication dates
* Reset view counters
* Delete analytics records
* Delete editorial history
* Replace the authentication stack
* Replace the authorization package
* Replace the existing Filament panel
* Install a large security package without audit evidence
* Add speculative middleware that breaks existing flows
* Run `migrate:fresh`
* Run `db:wipe`
* Run destructive seeders
* Disable CSRF protection
* Disable authorization checks for tests
* hardcode production secrets
* expose `.env`
* expose stack traces in production
* weaken password rules merely to make tests pass
* use hidden navigation as the only security boundary
* use frontend JavaScript as the only authorization boundary
* trust user-submitted IDs, roles, permissions, statuses or ownership fields
* silently ignore failed security tests

---

# Step 1 — Establish a Security Baseline

Before changing code, record:

```text
User count
Post count
Published-post count
Role count
Permission count
Category count
Tag count
Media count
Post-visit count
Post-status-history count
Bookmark count
Notification count
```

Also record:

* Current Laravel version
* Current PHP version
* Installed Filament version
* Installed authorization package and version
* Current environment
* Session driver
* Cache driver
* Queue driver
* Mail driver
* Hashing driver
* Authentication guards
* Password broker
* Current trusted-proxy configuration
* Current CORS configuration
* Existing security middleware
* Existing rate limiters

Run:

```bash
php artisan about
php artisan route:list
composer show laravel/framework
composer show filament/filament
composer show spatie/laravel-permission
```

Run only commands relevant to installed packages.

---

# Step 2 — Build the Final Authorization Matrix

Audit the actual behavior, not only the seeded role definitions.

Required roles:

```text
super-admin
admin
editor
reporter
author
subscriber
```

Audit the final effective permissions for each role.

Produce a matrix covering:

```text
Public frontend
Subscriber dashboard
Filament panel
Dashboard widgets
Posts
Own posts
All posts
Post creation
Post submission
Review queue
Correction requests
Approval
Scheduling
Publishing
Users
Roles
Permissions
Categories
Tags
Media
Advertisements
SEO
Analytics
Settings
Referrals
Bookmarks
Reading history
Notifications
Profile
Password
```

For every matrix cell, verify:

* UI visibility
* Route access
* Policy authorization
* Record-query scoping
* Direct URL behavior
* Form-submission behavior
* Bulk action behavior
* Global-search behavior

Do not trust only `hasRole()` or navigation visibility.

---

# Step 3 — Authentication Audit

Audit all login entry points.

Potential routes:

```text
/login
/admin/login
/register
/logout
/forgot-password
/reset-password
/email/verify
```

Verify:

* One intentional public login flow
* Filament login remains compatible
* Generic credential errors
* Session regeneration after login
* Safe remember-me behavior
* Rate limiting
* Inactive or blocked-user checks where status exists
* Subscriber post-login redirect
* Staff post-login redirect
* No open redirect through `redirect`, `return`, `next` or similar parameters
* No role escalation through login payloads

Test:

* Valid login
* Invalid password
* Unknown email
* Case handling according to project policy
* Repeated failed attempts
* Login after password reset
* Login after role removal
* Login after panel-access permission removal
* Login after account suspension where supported

---

# Step 4 — Registration Audit

Audit public registration requests and forms.

Confirm that public registration accepts only approved fields.

Allowed examples:

```text
name
email
password
password_confirmation
optional referral code
```

Must reject or ignore safely:

```text
role
roles
permission
permissions
status
ref_id
refcode
email_verified_at
is_admin
admin
user_type
guard_name
```

Verify:

* New user receives only `subscriber`
* Password is hashed
* Referral code generated server-side
* Referrer resolved server-side
* Invalid referral code handled safely
* Duplicate email rejected
* Email verification behavior correct
* Mass assignment blocked
* Registration rate limiting exists where appropriate
* No user enumeration through validation response beyond normal uniqueness validation
* No staff role can be requested

Add adversarial tests submitting every protected field.

---

# Step 5 — Password Reset and Verification Audit

Verify password-reset flow:

* Generic forgot-password response
* Secure token validation
* Token expiry
* Email matching
* Password confirmation
* Password hashing
* Remember-token behavior
* Password-reset event
* Invalid-token failure
* Reused token failure
* No account enumeration
* Rate limiting

Verify email verification:

* Signed URLs
* Expiry
* Correct authenticated-user binding
* Resend throttling
* Already verified behavior
* Invalid signature handling
* User cannot manually set `email_verified_at`
* Changing email resets verification only where designed
* Existing staff are not unexpectedly locked out

---

# Step 6 — Session and Cookie Security

Audit session configuration.

Inspect:

```text
SESSION_DRIVER
SESSION_LIFETIME
SESSION_ENCRYPT
SESSION_SECURE_COOKIE
SESSION_HTTP_ONLY
SESSION_SAME_SITE
SESSION_DOMAIN
APP_URL
APP_ENV
APP_DEBUG
```

Recommended production expectations:

```text
APP_ENV=production
APP_DEBUG=false
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

Use stricter values only where compatible.

Verify:

* Session ID regenerates after login
* Session invalidates after logout
* CSRF token regenerates after logout
* Password change handles session safely
* Session fixation is prevented
* Other-session invalidation works only if implemented
* Session IDs are not exposed
* Cookies are not accessible through JavaScript where avoidable
* HTTPS-only cookies are documented for production

Do not hardcode production values into version-controlled files unless configuration defaults are appropriate.

---

# Step 7 — CSRF and HTTP Method Audit

Audit all state-changing routes.

State-changing actions must not use GET.

Examples:

```text
logout
save article
unsave article
mark notification read
mark all notifications read
clear reading history
submit post
approve post
reject post
publish post
schedule post
role assignment
permission assignment
profile update
password update
```

Verify:

* CSRF middleware active
* Forms contain valid tokens
* Livewire actions retain framework protection
* API routes use appropriate token-based protection if any exist
* No CSRF exemptions were added broadly
* No wildcard exemption covers account or admin actions

---

# Step 8 — Insecure Direct Object Reference Audit

Test direct-object access across:

```text
posts
users
roles
permissions
bookmarks
referrals
reading history
notifications
post-status history
media
categories
tags
advertisements
SEO resources
settings
```

Attempt:

* Incrementing IDs
* Changing UUIDs
* Submitting another user’s resource ID
* Accessing direct Filament record URLs
* Accessing hidden routes
* Calling actions on unauthorized records
* Accessing deleted or unpublished posts
* Accessing another user’s notification
* Deleting another user’s bookmark
* Viewing another user’s referrals
* Viewing another user’s reading history

Expected result:

* 403, 404 or safe denial according to project convention
* No private data leakage
* No unauthorized side effect
* No different error detail that reveals sensitive existence unnecessarily

---

# Step 9 — Mass Assignment Audit

Audit:

```text
User::$fillable
Post::$fillable
PostVisit::$fillable
PostBookmark::$fillable
PostStatusHistory::$fillable
Notification preference models
Form Requests
Filament form mutations
Livewire public properties
Controllers using request()->all()
Model::create($request->all())
Model::update($request->all())
```

Protected fields include:

```text
role
roles
permissions
status
refcode
ref_id
email_verified_at
reviewed_by
reviewed_at
approved_at
rejected_at
published_at
views_count
author_id
visitor_id
user_id
actor_id
```

Some protected fields may be set internally, but never trusted from public input.

Replace unsafe broad assignment with validated data or explicit field mapping.

---

# Step 10 — Role and Permission Audit

Verify:

* Permission guard matches User guard
* Permission cache is handled correctly
* Seeder is idempotent
* Duplicate roles absent
* Duplicate permissions absent
* Subscriber has no staff permission
* Reporter and Author lack publish permission
* Editor has only intended permissions
* Admin cannot silently manage protected permissions unless granted
* Super Admin behavior is deliberate
* `Gate::before()` does not bypass account-status checks
* Role removal takes effect after cache reset
* Direct policy checks reflect permission changes
* Tests do not depend on stale permission cache

Run:

```bash
php artisan permission:cache-reset
```

Use correct command for the installed package.

---

# Step 11 — Super Admin Protection Audit

Verify:

* At least one Super Admin exists
* Final Super Admin cannot be deleted
* Final Super Admin role cannot be removed
* Protected Super Admin cannot be demoted by Admin
* Admin cannot promote self to Super Admin
* Unauthorized users cannot assign `super-admin`
* Seeder does not create unintended Super Admins
* Direct requests are protected
* Bulk actions are protected
* Filament form tampering is protected
* Database constraints and application logic are compatible

Test race-like scenarios where two Super Admin modifications happen close together.

Use a transaction or locking strategy where necessary to prevent removal of the final Super Admin.

---

# Step 12 — Filament Panel Audit

Audit:

* `canAccessPanel()`
* Panel ID and path
* Staff login
* Subscriber denial
* User without staff permission denial
* Inactive-user denial where applicable
* Dashboard widget visibility
* Navigation visibility
* Resource authorization
* Page authorization
* Relation-manager authorization
* Global search
* Table search
* Filters
* Bulk actions
* Exports
* Imports
* Custom pages
* Custom actions
* Widget queries

Ensure hidden navigation resources remain inaccessible by direct URL.

---

# Step 13 — Post Resource and Ownership Audit

Verify:

* Reporter sees only own posts
* Author sees only own posts
* Editor sees intended editorial records
* Admin sees intended records
* Super Admin sees all appropriate records
* Search does not escape ownership scope
* Filters do not escape ownership scope
* Global search does not leak records
* Export does not include unauthorized records
* Bulk actions authorize every record
* Relationship selectors do not expose protected users unnecessarily
* Author field cannot be tampered with
* Reviewer fields cannot be tampered with
* View counters cannot be edited
* Status cannot be changed directly outside workflow
* Reporter cannot edit submitted, approved, scheduled or published posts
* Reporter cannot view another reporter’s private post

---

# Step 14 — Editorial Workflow Audit

Audit every transition:

```text
draft → submitted
needs_correction → submitted
submitted → under_review
submitted/under_review → needs_correction
submitted/under_review → rejected
submitted/under_review → approved
approved → scheduled
approved → published
scheduled → published
rejected → draft
```

Verify:

* Permission required
* Ownership required where applicable
* Current status validated
* Notes required where applicable
* Transaction used
* Latest row locked where practical
* Audit history created exactly once
* Notification/event dispatched after commit
* Failed transition creates no history
* Failed transition sends no notification
* Double-click does not duplicate effects
* Stale form cannot overwrite newer status
* Scheduled command is idempotent
* Reporter cannot publish
* Author cannot approve
* Editor without permission cannot act
* Direct status payload tampering fails

---

# Step 15 — Subscriber Dashboard Audit

Verify:

* Guest redirected to login
* Subscriber can access own dashboard
* Subscriber cannot access Filament
* Subscriber cannot access staff endpoints
* Staff user frontend behavior follows intended policy
* Dashboard data belongs to authenticated user
* Profile updates only own account
* Password change requires current password
* Referral code is read-only
* `ref_id` is protected
* Account status is protected
* Role and permissions are protected
* Email-verification timestamp is protected
* No arbitrary user ID accepted

---

# Step 16 — Referral Privacy Audit

Verify:

* User sees only direct referrals
* Query uses authenticated user ID
* Referral count matches list scope
* No other user’s referral page accessible
* Full emails are masked or omitted according to policy
* Phone numbers not exposed
* Roles and permissions not exposed
* Internal IDs not exposed
* No fabricated earnings shown
* Referral link uses `refcode`
* Raw `ref_id` is never used publicly
* Referral code cannot be changed through public forms

---

# Step 17 — Bookmark Audit

Verify:

* Guest cannot create bookmark without authentication
* Only published public posts can be saved
* Duplicate bookmark prevented
* User can remove own bookmark
* User cannot remove another user’s bookmark
* User sees only own saved list
* Draft or private post cannot be saved through tampering
* Unpublished posts do not leak through saved list
* Save action does not increment views
* State-changing route uses POST/DELETE
* Unique database constraint exists where appropriate

---

# Step 18 — Reading History Audit

Verify:

* Reading history uses authenticated `visitor_id`
* User sees only own history
* Anonymous history is not merged without consent
* Raw IP, session ID and user agent are not exposed
* Clear-history action affects only own visit rows
* Aggregate post views remain unchanged
* Another user’s history cannot be queried through IDs
* Pagination and deduplication are efficient
* Bots do not populate subscriber history where tracking already identifies bots

---

# Step 19 — Notification Audit

Verify:

* User sees only own notifications
* User can mark only own notification as read
* Mark-all-read affects only authenticated user
* Notification payload is escaped
* Raw HTML is not rendered
* External URLs are not blindly trusted
* Internal action links are validated
* Deleted target resources are handled safely
* Staff editorial notifications do not leak to subscribers
* Notification type cannot be used to instantiate arbitrary classes

---

# Step 20 — File Upload Audit

Audit all upload fields:

```text
profile image
featured image
media library
post attachments
advertisement image
SEO image
```

Verify:

* MIME type validated
* Extension validated
* File size limited
* Image dimensions limited where appropriate
* Executable files rejected
* Double extensions rejected
* SVG policy explicit
* File names randomized or safely normalized
* User-controlled path traversal impossible
* Storage disk appropriate
* Public/private visibility correct
* Uploaded PHP or script file cannot execute
* Image metadata handling does not expose sensitive data where relevant
* Reporter cannot overwrite another user’s media
* Deleted media is not shared by another post before deletion

Do not add SVG support unless sanitization exists.

---

# Step 21 — XSS Audit

Audit user-rendered and admin-rendered content:

```text
name
profile fields
review notes
rejection reasons
notification messages
post title
post content
category names
tag names
advertisement copy
SEO fields
referral display
uploaded filenames
```

Verify:

* Blade escaped output uses `{{ }}`
* `{!! !!}` is used only for trusted sanitized HTML
* Post HTML sanitation strategy is understood
* Review notes are escaped
* Notification payloads are escaped
* Filament columns do not render unsafe HTML
* URLs use safe schemes
* JavaScript event attributes cannot be injected
* Rich-text editor content follows existing sanitation policy

Do not strip legitimate imported article HTML blindly. Audit and document trusted-content boundaries.

---

# Step 22 — SQL Injection and Query Audit

Audit:

```text
whereRaw
orderByRaw
selectRaw
havingRaw
DB::raw
dynamic table names
dynamic column names
search filters
sort parameters
reporting queries
analytics queries
```

Verify:

* User input is bound
* Sort fields are allow-listed
* Direction is allow-listed
* Search does not interpolate SQL
* Raw status or role parameters are validated
* Export filters are safe
* Date-range inputs are validated
* No user-controlled table or column identifiers

Replace unsafe interpolation with bound parameters or allow-lists.

---

# Step 23 — Open Redirect and URL Audit

Audit parameters such as:

```text
redirect
return
return_to
next
continue
url
target
```

Verify:

* Post-login redirect uses named internal routes
* Verification redirect safe
* Password-reset redirect safe
* Notification action links safe
* External URLs require allow-list if supported
* `javascript:` and unsafe schemes rejected
* Protocol-relative URLs handled safely
* Host validation exists where needed

---

# Step 24 — Rate Limiting Audit

Audit and implement reasonable limits for:

```text
login
registration
forgot password
verification resend
password change
bookmark toggle
notification actions
profile update
referral lookup
editorial actions
media upload
search
```

Do not over-throttle normal reading behavior.

Use framework-native rate limiters.

Editorial actions may rely on authentication plus action confirmation rather than aggressive limits, but repeated abuse should not be unbounded.

---

# Step 25 — Security Headers

Audit current web-server and application headers.

Recommended headers, subject to compatibility:

```text
X-Content-Type-Options: nosniff
Referrer-Policy: strict-origin-when-cross-origin
X-Frame-Options: SAMEORIGIN
Permissions-Policy
Content-Security-Policy
Strict-Transport-Security
```

Important:

* HSTS only on HTTPS production
* CSP must be audited against existing scripts, ads, analytics, images and inline assets
* Do not deploy a restrictive CSP that breaks the live frontend
* Start with report-only mode if required
* Do not duplicate conflicting Nginx and application headers
* Filament compatibility must be tested

Document which headers belong in Laravel and which belong in Nginx/Hostinger configuration.

---

# Step 26 — CORS and Trusted Proxy Audit

Audit:

* `config/cors.php`
* Trusted proxy middleware
* Production load balancer or Cloudflare behavior
* HTTPS detection
* Secure URL generation
* Host header handling

Verify:

* CORS is not wildcarded with credentials
* Only required origins allowed
* Public web routes do not need permissive CORS
* Trusted proxies are not set to unsafe universal values without infrastructure reason
* HTTPS scheme is recognized behind proxy
* Password reset and verification links use correct HTTPS host
* Host header injection does not affect generated links

---

# Step 27 — Sensitive Data and Logging Audit

Audit logs for:

```text
passwords
password reset tokens
verification tokens
session IDs
cookies
authorization headers
full request payloads
personal emails
phone numbers
IP addresses
review notes
security exceptions
```

Requirements:

* Never log passwords or tokens
* Avoid logging complete authentication payloads
* Mask sensitive fields
* Security denial logs should be useful but not expose secrets
* Production logs should not expose stack traces publicly
* Audit logs and editorial history should remain separate concepts
* Notification failures should not leak credentials

Audit exception rendering for production.

---

# Step 28 — Production Configuration Audit

Review, without exposing secret values:

```text
APP_ENV
APP_DEBUG
APP_URL
LOG_LEVEL
SESSION settings
CACHE_STORE
QUEUE_CONNECTION
MAIL_MAILER
FILESYSTEM_DISK
DB connection
SANCTUM settings if installed
TRUSTED_PROXIES
CORS
```

Verify production recommendations:

* Debug disabled
* Correct canonical URL
* HTTPS
* Secure session cookie
* Appropriate log level
* Queue worker documented
* Scheduler cron documented
* Storage link documented
* Permission cache reset included in deployment
* Config, route and view caching tested

Do not output secret values in the completion report.

---

# Step 29 — Dependency and Framework Audit

Use authoritative tooling available locally.

Run:

```bash
composer audit
composer outdated --direct
```

Do not automatically upgrade major dependencies in this phase.

Report:

* Security advisories
* Direct outdated packages
* Laravel compatibility concerns
* Filament compatibility concerns
* Spatie permission compatibility concerns

Apply only safe, narrowly scoped security updates if they do not introduce architectural risk and are required to remove a known vulnerability.

Document deferred upgrades.

---

# Step 30 — Error Handling Audit

Verify:

* Unauthorized access gives correct 403/404
* Validation gives safe 422 or redirect response
* Authentication failure does not leak account existence
* Workflow domain exceptions render safely
* File-upload errors are user-friendly
* Database exceptions do not expose SQL
* Production exception pages hide traces
* Filament actions display safe notifications
* Scheduled command logs failures and continues where intended
* Mail failure does not roll back a completed workflow transition

---

# Step 31 — Database Integrity Audit

Inspect constraints and indexes related to:

```text
users.refcode
users.ref_id
posts.author_id
posts.reviewed_by
post_visits
post_bookmarks
post_status_histories
roles
permissions
model_has_roles
model_has_permissions
notifications
```

Verify:

* Referral code uniqueness
* Bookmark uniqueness
* Foreign-key behavior appropriate
* Nullable legacy fields supported
* Cascades do not delete important editorial history unexpectedly
* User deletion policy compatible with authored posts
* Final Super Admin protection remains application-enforced
* No duplicate indexes
* Status values compatible with enum
* History rows immutable

Do not add constraints that would break imported legacy data.

---

# Step 32 — Cache and Permission Consistency

Audit:

* Permission cache
* Application cache
* Route cache
* Config cache
* View cache
* Dashboard counts
* Published-post cache
* Sitemap cache
* News sitemap cache

Verify:

* Permission change takes effect predictably
* Role removal does not leave stale panel access
* Publishing clears required caches
* Scheduled publishing updates public visibility
* Security checks do not depend on stale cached user role objects
* Cache keys do not leak private data across users

---

# Step 33 — Adversarial Test Suite

Create targeted tests that act like an attacker.

## Authentication attacks

* Brute-force throttling
* Session fixation
* Open redirect
* Logout through GET
* Password-reset token reuse
* Verification-link misuse

## Privilege escalation

* Register as Admin through payload
* Change own role through profile request
* Assign own permissions
* Set `email_verified_at`
* Set account status
* Set `ref_id`
* Set `author_id`
* Set `reviewed_by`
* Set post status directly
* Set `views_count`

## IDOR

* View another user’s profile
* View another user’s referrals
* Delete another user’s bookmark
* Mark another user’s notification
* View another user’s reading history
* Edit another reporter’s post
* Access hidden Filament record
* Trigger action on unauthorized post

## Workflow abuse

* Reporter publishes
* Reporter approves
* Reporter edits submitted post
* Editor approves without permission
* Invalid status jump
* Duplicate scheduled publish
* Double-submit
* Stale action overwrite
* Empty correction note
* Empty rejection reason

## Injection and XSS

* Script in profile name
* Script in review notes
* Script in notification payload
* Unsafe sort parameter
* SQL fragment in filter
* `javascript:` notification URL

## Upload attacks

* PHP file renamed as image
* Double extension
* Oversized image
* SVG with script where SVG disallowed
* Path traversal filename

Every discovered vulnerability must receive:

* Reproduction test
* Fix
* Regression test

---

# Step 34 — Full Regression Tests

Run focused security tests first.

Suggested commands:

```bash
php artisan test --filter=Security
php artisan test --filter=Authorization
php artisan test --filter=Authentication
php artisan test --filter=Registration
php artisan test --filter=Filament
php artisan test --filter=PostWorkflow
php artisan test --filter=Subscriber
php artisan test --filter=Referral
php artisan test --filter=Bookmark
php artisan test --filter=ReadingHistory
php artisan test --filter=Notification
```

Then run the complete test suite:

```bash
php artisan test
```

Report:

* Total tests
* Total assertions
* Failures
* Skipped tests
* Risky tests where applicable
* Pre-existing failures
* Newly introduced failures

Do not hide failing security tests.

---

# Step 35 — Static Analysis and Formatting

Run only tools already configured.

```bash
vendor/bin/pint --test
```

Then format modified files if needed:

```bash
vendor/bin/pint
```

If Larastan or PHPStan exists:

```bash
vendor/bin/phpstan analyse
```

If Blade formatter or frontend linting exists, run the project’s configured commands.

Do not introduce a new static-analysis platform solely for this phase unless necessary.

---

# Step 36 — Production Cache Validation

After tests pass, validate:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Then run relevant smoke tests or route checks.

If route caching fails because of closure routes:

* Report exact routes
* Fix only when safe
* Do not conceal the failure

Finally clear or retain caches according to the local development environment.

Do not leave production-only cache assumptions undocumented.

---

# Step 37 — Deployment Security Checklist

Produce a deployment checklist covering:

```text
Backup database
Backup uploaded media
Set APP_ENV=production
Set APP_DEBUG=false
Set canonical APP_URL
Enable HTTPS
Configure secure cookies
Configure Nginx security headers
Configure storage link
Run migrations
Run role/permission seeder safely
Reset permission cache
Cache config/routes/views
Start queue worker
Configure scheduler cron
Verify mail
Verify password reset
Verify email verification
Verify Filament login
Verify subscriber login
Verify scheduled publishing
Verify file upload restrictions
Verify log permissions
Verify backup policy
```

Do not run production deployment in this phase unless explicitly requested.

---

# Completion Criteria

Phase 15F is complete only when:

* Full authorization matrix is verified
* Public registration cannot create staff users
* Subscriber cannot access Filament
* Reporter cannot access other reporters’ private posts
* Reporter cannot publish or approve
* Editor actions require correct permissions
* Super Admin cannot be accidentally removed
* Direct URLs are protected
* Bulk actions are protected
* Global search is protected
* Profile forms cannot change roles or protected fields
* Referral privacy is enforced
* Bookmark ownership is enforced
* Reading-history privacy is enforced
* Notification ownership is enforced
* Password reset is safe
* Email verification is safe or explicitly deferred
* Login and sensitive endpoints are rate limited
* Session fixation is prevented
* Logout is secure
* State changes do not use GET
* CSRF protection is active
* Mass assignment is controlled
* Open redirects are blocked
* XSS boundaries are reviewed
* Raw SQL usage is safe
* File uploads are restricted
* Security headers are documented or implemented safely
* Production debug is documented as disabled
* Dependency advisories are reviewed
* All discovered vulnerabilities have regression tests
* Existing users and imported posts remain unchanged
* Existing slugs and publication dates remain unchanged
* Existing view counters and analytics remain unchanged
* Relevant focused tests pass
* Complete test suite passes, or every pre-existing failure is clearly documented
* No destructive database command was used

---

# Required Completion Report

Return a structured report with the following sections.

## 1. Executive Security Summary

Provide:

* Overall status
* Critical findings
* High-risk findings
* Medium-risk findings
* Low-risk findings
* Fixed findings
* Deferred findings
* Deployment blockers

Do not state “fully secure” or “100% secure.”

## 2. Environment and Package Audit

Report:

* Laravel version
* PHP version
* Filament version
* Authorization package
* Authentication stack
* Session driver
* Queue driver
* Cache driver
* Relevant security advisories

Do not expose secrets.

## 3. Final Authorization Matrix

Provide the verified effective-access matrix for all roles.

## 4. Authentication Findings

Report:

* Login
* Registration
* Logout
* Password reset
* Verification
* Redirects
* Rate limiting
* Session regeneration
* Account-status handling

## 5. Filament Findings

Report:

* Panel access
* Navigation
* Resource access
* Direct URLs
* Global search
* Bulk actions
* Exports
* Widgets
* Subscriber denial

## 6. Editorial Workflow Findings

Report:

* Transition protection
* Permission enforcement
* Ownership enforcement
* Concurrency protection
* Audit history
* Notifications
* Scheduled publishing
* Idempotency

## 7. Subscriber Security Findings

Report:

* Profile
* Password
* Referrals
* Bookmarks
* Reading history
* Notifications
* Preferences
* IDOR protection

## 8. Input and Data Security

Report:

* Mass assignment
* Validation
* XSS
* SQL injection
* File uploads
* Safe URLs
* Sensitive logging
* Error handling

## 9. Session, Cookie and Header Findings

Report:

* Cookie settings
* HTTPS behavior
* Trusted proxies
* CORS
* Security headers
* CSP decision
* HSTS decision

## 10. Database Integrity

Report:

* Foreign keys
* Unique constraints
* Indexes
* Legacy-data compatibility
* Final Super Admin integrity
* History immutability

## 11. Vulnerabilities Found and Fixed

For each issue provide:

```text
Severity
Affected area
Reproduction
Root cause
Fix
Regression test
Remaining risk
```

## 12. Deferred Risks

List every unresolved item with:

```text
Risk
Reason deferred
Recommended owner
Recommended phase
Deployment impact
```

## 13. Files Changed

List every created and modified file.

## 14. Database Changes

List every migration, index, constraint or data change.

Confirm no destructive changes were made.

## 15. Tests and Validation

Report:

* Commands executed
* Focused security tests
* Full test suite
* Total tests
* Total assertions
* Failures
* Skips
* Pre-existing failures
* New failures
* Static-analysis result
* Formatting result
* Cache-build result
* Dependency-audit result

## 16. Existing Data Verification

Report before and after counts for:

```text
users
posts
published posts
roles
permissions
categories
tags
media
post visits
post-status histories
bookmarks
notifications
```

Also confirm:

* Existing passwords preserved
* Existing role assignments preserved
* Existing referral codes preserved
* Existing referral relationships preserved
* Existing post ownership preserved
* Existing slugs preserved
* Existing publication dates preserved
* Existing view counters preserved
* Existing analytics preserved
* Existing editorial history preserved

## 17. Production Deployment Checklist

Provide exact project-relevant deployment and post-deployment verification steps.

## 18. Final Decision

Choose one:

```text
PASS — Ready for the next phase
PASS WITH CONDITIONS — Ready after listed fixes
FAIL — Security blockers remain
```

Explain the decision with evidence.

Do not claim completion if:

* Any critical vulnerability remains
* Subscriber can access Filament
* Public registration can assign staff role
* Reporter can publish
* IDOR exposes another user’s data
* Final Super Admin can be removed accidentally
* Direct URLs bypass policies
* Global search leaks records
* Password reset or verification is insecure
* State-changing GET routes remain
* File uploads permit executable content
* Production debug remains enabled in deployment configuration
* Existing imported records change unexpectedly
* Security tests fail without explanation
