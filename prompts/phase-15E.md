# Phase 15E — Subscriber Frontend Dashboard

## Project Context

This is an existing Laravel news portal migrated from WordPress.

The project contains production-like data, including approximately 13,628 imported posts.

The following foundations should already exist:

### Phase 15A

* User referral codes
* Referrer relationships
* Post author and reviewer relationships
* Editorial metadata
* Post view-counter preservation
* `post_visits` analytics foundation

### Phase 15B

* Public login
* Public registration
* Logout
* Password reset
* Email-verification decision
* Roles and permissions
* Public registrations assigned the `subscriber` role
* Staff and subscriber post-login redirects
* Subscriber denied Filament access
* Basic frontend `/dashboard` route

### Phase 15C

* Role-aware Filament dashboards
* Staff navigation and resource authorization
* Subscriber blocked from Filament
* Staff profile and resource protections

### Phase 15D

* Reporter → Editor → Publish workflow
* Editorial statuses
* Review history
* Editorial notifications
* Scheduled publishing
* Workflow authorization

This phase must build a secure, responsive and useful frontend account area for subscribers without exposing staff, editorial or administrative functionality.

---

# Primary Objective

Implement the complete Subscriber Frontend Dashboard with:

1. Subscriber-only frontend account area
2. Dashboard overview
3. Profile management
4. Password change
5. Email-verification status and resend flow
6. Referral code and referral-link display
7. Referral summary
8. Referred-user list
9. Saved or bookmarked articles
10. Reading-history foundation
11. Account notifications
12. Notification preferences
13. Account security and active-session visibility where safely supported
14. Role-aware frontend redirects
15. Responsive and accessible design
16. Secure policies and route protection
17. Focused tests
18. Existing-data protection

Do not expose Filament, editorial resources, internal analytics or private user information.

---

# Expected Subscriber Experience

After login, a Subscriber should land on:

```text
/dashboard
```

The dashboard should provide access to:

```text
Dashboard
Profile
Security
Referrals
Saved Articles
Reading History
Notifications
Preferences
Logout
```

Only implement sections that are supported by the current project architecture.

Where a feature does not yet have a foundation, create a safe minimal implementation or document it as deferred rather than introducing a fragile subsystem.

---

# Protected Boundaries

Do not:

* Give Subscribers access to Filament
* Give Subscribers staff roles or permissions
* Expose Reporter, Editor, Admin or Super Admin dashboards
* Expose unpublished posts
* Expose editorial review notes
* Expose private post analytics
* Expose other users’ email addresses
* Expose other users’ phone numbers
* Expose other users’ referral trees beyond permitted summary information
* Allow arbitrary editing of `refcode`
* Allow arbitrary editing of `ref_id`
* Allow public requests to assign roles
* Allow public requests to assign permissions
* Allow public requests to modify account status
* Allow Subscribers to modify `email_verified_at`
* Allow Subscribers to change their email without a safe verification strategy
* Store plain-text passwords
* Add GET logout routes
* Trust hidden form fields for user identity
* Change existing Filament panel behavior
* Change public article routes
* Change article slugs
* Change imported content
* Change existing editorial data
* Change existing view counters
* Delete analytics records
* Run `migrate:fresh`
* Run `db:wipe`
* Run destructive seeders

---

# Step 1 — Audit Existing Frontend Account Architecture

Before modifying files, inspect:

## Authentication

* Existing `/dashboard`
* Existing profile routes
* Existing password update routes
* Existing email-verification routes
* Existing logout route
* Existing account middleware
* Existing subscriber redirect logic

## Frontend stack

Determine whether the public frontend uses:

* Blade
* Livewire
* Volt
* Alpine.js
* Tailwind CSS
* Bootstrap
* Custom CSS
* Existing design tokens
* Existing reusable layouts and components

Use the existing frontend stack and design system.

Do not install a new frontend framework.

## User model

Inspect:

* `refcode`
* `ref_id`
* `email_verified_at`
* Status field
* Profile fields
* Avatar field
* Notification settings
* Saved-article relationships
* Reading-history relationships
* Existing casts
* Existing accessors

## Existing tables and models

Audit whether the project already has:

```text
bookmarks
saved_posts
favorites
post_bookmarks
reading_history
post_reads
notifications
notification_preferences
user_sessions
```

Reuse existing structures where compatible.

Do not create duplicate tables or models.

---

# Step 2 — Subscriber Route Group

Create or retain a frontend account route group.

Recommended structure:

```php
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', ...)->name('dashboard');

    Route::prefix('account')
        ->name('account.')
        ->group(function () {
            // Profile, security, referrals, saved posts, history, notifications.
        });
});
```

Use `verified` middleware only where email verification has been safely enabled.

Requirements:

* Guests redirected to login
* Subscribers allowed
* Staff users may access the frontend account area only when safe
* Subscriber role required where a route is specifically subscriber-only
* Staff routes must not be exposed through frontend account controllers
* Route names must be stable
* No duplicate route names
* No route conflict with Filament

Prefer named routes.

---

# Step 3 — Account Layout

Create or reuse a frontend account layout.

Possible location:

```text
resources/views/account/layout.blade.php
```

or an existing project-consistent equivalent.

The layout should include:

* Public site header
* Account navigation
* Main content area
* Responsive mobile navigation
* User name
* Role label where appropriate
* Logout action
* Public-site return link

Do not duplicate the entire public layout if an existing shared layout can be extended.

## Recommended account navigation

```text
Dashboard
Profile
Security
Referrals
Saved Articles
Reading History
Notifications
Preferences
```

Navigation items must appear only when the related feature exists.

---

# Step 4 — Dashboard Overview

The Subscriber dashboard should show useful account information without exposing sensitive data.

Recommended cards:

```text
Account status
Email-verification status
Referral code
Total referrals
Saved articles
Unread notifications
Recently read articles
```

Optional quick actions:

```text
Complete Profile
Verify Email
Copy Referral Link
View Saved Articles
Change Password
```

Requirements:

* Use authenticated user data only
* Avoid expensive queries
* Use counts rather than loading large collections
* Do not expose staff permissions
* Do not expose internal IDs where not needed
* Do not show private analytics
* Do not show editorial content

If the user is staff, show the frontend dashboard safely or redirect to the staff dashboard according to the existing redirect strategy.

---

# Step 5 — Profile Management

Create a Subscriber profile page.

Recommended editable fields, only when they already exist or are intentionally added:

```text
name
email
phone
profile photo
city
state
country
preferred language
```

Do not add unnecessary personal-data fields.

## Required profile behavior

* User may update only their own profile
* Validate all fields
* Normalize email where appropriate
* Enforce email uniqueness
* Protect against mass assignment
* Preserve existing password
* Preserve role and permission assignments
* Preserve `refcode`
* Preserve `ref_id`
* Preserve status
* Preserve verification timestamp unless email changes

## Email-change behavior

If the email address changes:

* Require the current password where appropriate
* Reset `email_verified_at` only if verification is enabled
* Send a new verification notification
* Prevent duplicate email addresses
* Do not silently change login identity without validation

If safe email-change verification cannot be implemented, make email read-only and report the limitation.

---

# Step 6 — Password Change

Create a secure password-change page or section.

Required fields:

```text
current_password
password
password_confirmation
```

Requirements:

* Validate current password
* Use Laravel password rules
* Hash the new password
* Reject reuse only if an existing password-history feature supports it
* Regenerate session where appropriate
* Preserve the current login safely
* Invalidate other sessions only if explicitly requested or supported
* Show generic validation errors
* Do not log passwords
* Do not include passwords in notifications

Use the existing framework-native password update behavior when available.

---

# Step 7 — Email Verification

Audit Phase 15B’s verification decision.

If verification is enabled:

Show:

```text
Verified
Not Verified
Resend Verification Email
```

Requirements:

* Resend action rate-limited
* Signed verification route preserved
* Verification status updated safely
* Invalid signatures rejected
* Already verified users handled gracefully
* Do not expose verification links to other users

If verification is not enabled:

* Do not fake a verification system
* Hide unsupported actions
* Report that verification remains deferred

---

# Step 8 — Referral Code and Referral Link

Display the authenticated user’s referral code as read-only.

Example:

```text
DS7K4P9XM2
```

Generate a safe referral URL using a named public-registration route.

Example behavior:

```text
/register?ref=DS7K4P9XM2
```

Requirements:

* Use `refcode`, never raw `ref_id`
* Referral code cannot be edited
* Referral link must be generated server-side
* Use route helpers
* Copy button may use Alpine.js or existing frontend JS
* Share buttons may be included only for safe public URLs
* Do not expose referrer private data

Possible share options:

```text
Copy Link
WhatsApp
Email
```

Do not add third-party scripts merely for sharing.

---

# Step 9 — Referral Summary

Create a referral overview page.

Show safe summary information:

```text
Referral code
Referral link
Total direct referrals
Recent referral count
Account creation date
```

Optional metrics only when supported:

```text
Referrals this month
Verified referrals
Active referrals
```

Do not invent earnings, commissions or rewards unless an actual referral-reward system exists.

Do not show:

* Other users’ passwords
* Private contact details
* Referral users’ internal permissions
* Complete multi-level referral tree unless explicitly required
* Unverified financial claims

---

# Step 10 — Referred Users List

Display direct referrals through:

```php
$user->referrals()
```

Recommended columns:

```text
Name or privacy-safe display name
Joined date
Verification status
Account status where appropriate
```

Privacy requirements:

* Prefer a partially masked name or email where necessary
* Do not display full email addresses unless the business requirement explicitly allows it
* Do not display phone numbers
* Do not display roles or permissions
* Do not display internal user IDs
* Show only direct referrals
* Paginate results
* Prevent access to another user’s referral list

Recommended privacy-safe email display:

```text
a***@example.com
```

Only implement masking if email display is required.

---

# Step 11 — Saved Articles Foundation

Audit whether bookmarks or saved articles already exist.

If no compatible implementation exists, create a focused structure.

Recommended table:

```text
post_bookmarks
```

Suggested migration:

```php
Schema::create('post_bookmarks', function (Blueprint $table) {
    $table->id();

    $table->foreignId('user_id')
        ->constrained()
        ->cascadeOnDelete();

    $table->foreignId('post_id')
        ->constrained()
        ->cascadeOnDelete();

    $table->timestamps();

    $table->unique(['user_id', 'post_id']);
    $table->index(['user_id', 'created_at']);
});
```

Use the project’s preferred table name if one already exists.

Create model only if the project architecture benefits from one:

```text
App\Models\PostBookmark
```

Relationships:

```php
User::bookmarks()
User::savedPosts()
Post::bookmarkedByUsers()
```

Avoid ambiguous duplicate relationships.

---

# Step 12 — Save and Unsave Article Actions

Add a save/bookmark control on public published article pages.

Requirements:

* Only authenticated users may save
* Guest action redirects to login or shows login prompt
* Only published and publicly accessible posts may be saved
* User can save only for themselves
* Duplicate bookmarks prevented
* Unsave supported
* Use CSRF-protected POST/DELETE requests
* No GET state-changing routes
* Button state reflects saved/unsaved status
* Action must not affect view counters
* Action must not expose unpublished posts

Implementation may use:

* Standard controller requests
* Livewire component
* Existing frontend interaction stack

Do not install an unnecessary JavaScript framework.

---

# Step 13 — Saved Articles Page

Create:

```text
/account/saved-articles
```

Recommended features:

* Paginated list
* Article title
* Featured image
* Category
* Published date
* Saved date
* Remove bookmark action
* Link to public article

Requirements:

* Query only authenticated user’s bookmarks
* Show only currently accessible published posts
* Handle deleted or unpublished posts safely
* Avoid N+1 queries
* Reuse existing public article-card components
* Provide helpful empty state

Example empty state:

```text
You have not saved any articles yet.
```

Do not expose another user’s saved list.

---

# Step 14 — Reading History Foundation

Audit whether post visits already provide a reliable logged-in reading history through:

```text
post_visits.visitor_id
```

Prefer reusing `post_visits` rather than creating a duplicate reading-history table.

Recommended reading-history query:

* Authenticated user’s `post_visits`
* Only publicly accessible posts
* Group or deduplicate repeated visits by post
* Order by latest visit
* Paginate

Possible display:

```text
Article
Last read
Category
Published date
```

Requirements:

* Do not expose raw IP addresses
* Do not expose session IDs
* Do not expose user agents
* Do not show internal analytics metadata
* Do not allow users to view another user’s history
* Avoid recording history for bots or background requests where existing tracking handles this

If `post_visits` tracking is not active yet, create the account-page foundation and clearly show that history will populate once visit recording is enabled.

---

# Step 15 — Clear Reading History

Optional action:

```text
Clear Reading History
```

Only implement if privacy requirements support user deletion of their own history.

Requirements:

* Confirmation required
* Delete only rows where `visitor_id` equals authenticated user ID
* Do not delete anonymous analytics unrelated to that user
* Do not delete aggregate post view counts
* Do not affect other users
* Use POST or DELETE
* Record an application log if appropriate

If analytics retention policy requires preserving history, omit this action and document the decision.

---

# Step 16 — Account Notifications

Use Laravel’s existing database notifications if Phase 15D or other phases already introduced them.

Create a frontend notifications page:

```text
/account/notifications
```

Recommended behavior:

* List authenticated user’s notifications
* Read/unread status
* Created time
* Relevant safe action link
* Mark one as read
* Mark all as read
* Pagination
* Empty state

Requirements:

* User sees only own notifications
* Notification payload rendered safely
* Do not trust arbitrary URLs stored in payload
* Use named-route mapping or validated internal URLs
* Do not expose sensitive system details
* No cross-user notification access

Possible notification types:

```text
Email verification
Account update
Post-related subscriber notifications
Future saved-topic alerts
```

Editorial notifications should only be shown to staff who receive them.

---

# Step 17 — Notification Preferences

Create a minimal preferences page if notification preferences are supported.

Possible preferences:

```text
Email notifications
Browser notifications
Breaking-news alerts
Category updates
Saved-topic updates
Marketing messages
```

Do not implement browser push unless infrastructure already exists.

Recommended storage options:

* Existing user settings JSON
* Existing notification-preferences table
* New focused table only if needed

Suggested table if no equivalent exists:

```text
user_notification_preferences
```

Possible fields:

```php
$table->id();
$table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
$table->boolean('email_news')->default(true);
$table->boolean('breaking_news')->default(false);
$table->boolean('category_updates')->default(false);
$table->boolean('marketing')->default(false);
$table->timestamps();
```

Only add preferences that the application can actually honor.

Do not create non-functional toggles.

---

# Step 18 — Account Preferences

Provide safe general preferences where supported:

```text
Preferred language
Theme preference
Content density
Email notification preference
```

Do not override global ChatGPT or browser settings.

Do not store unnecessary tracking preferences.

If the site supports multilingual content, use the existing language enum or configured language list.

---

# Step 19 — Account Status

Display a safe account-status summary.

Possible values:

```text
Active
Pending Verification
Suspended
Blocked
```

Use only existing status fields and enum values.

Requirements:

* User cannot edit status
* Suspended or blocked users handled according to Phase 15B login policy
* Do not display internal moderation notes
* Do not expose security flags

If no status field exists, do not add one solely for display.

---

# Step 20 — Active Sessions and Security

Audit whether the authentication stack supports session management.

If safe and supported, show:

```text
Current device
Recent login
Other active sessions
Log out other sessions
```

Do not build unreliable session tracking from raw Laravel session files without a clear database-session architecture.

If `SESSION_DRIVER=database` and session records can be reliably linked to users:

* Show privacy-safe device information
* Mask IP addresses where appropriate
* Allow user to invalidate other sessions after password confirmation

If not reliably supported:

* Provide password change and current-session logout only
* Report active-session management as deferred

Never expose full session IDs.

---

# Step 21 — Account Deletion

Do not automatically implement account deletion unless explicitly supported by project policy.

If implemented:

* Require password confirmation
* Explain impact
* Protect system accounts
* Prevent staff-account deletion through subscriber UI
* Decide whether to anonymize or delete
* Preserve legally required analytics
* Handle referrals safely
* Handle bookmarks
* Handle notifications
* Handle post authorship for staff accounts

For this phase, account deletion may be deferred and documented.

---

# Step 22 — Role and Permission Safety

Every account controller, component and request must use the authenticated user.

Do not accept arbitrary user IDs from public forms.

Subscriber account forms must not update:

```text
role
roles
permissions
status
refcode
ref_id
email_verified_at
created_at
updated_at
```

except framework-controlled timestamps.

Staff users using the frontend profile must not accidentally lose staff roles or permissions.

---

# Step 23 — Policies

Create or audit policies for:

```text
PostBookmark
Notification
Referral view
Reading history
User profile
```

Not every feature requires a dedicated policy class if ownership checks are cleanly enforced in controllers and queries.

Minimum rules:

* User may view only own dashboard
* User may update only own profile
* User may view only own referrals
* User may view and delete only own bookmarks
* User may view only own notifications
* User may mark only own notifications as read
* User may view only own reading history

Use policies or centralized ownership checks consistently.

---

# Step 24 — Form Requests

Create focused Form Request classes where appropriate.

Possible requests:

```text
UpdateProfileRequest
UpdatePasswordRequest
UpdateNotificationPreferencesRequest
```

Requirements:

* Authorization uses authenticated user
* Validation is centralized
* Sensitive fields excluded
* Email uniqueness ignores current user safely
* Password confirmation validated
* Current password validated
* Language values validated against allowed configuration

Do not accept role or permission fields.

---

# Step 25 — Controllers or Livewire Components

Use the existing frontend architecture.

Possible controllers:

```text
DashboardController
ProfileController
SecurityController
ReferralController
SavedArticleController
ReadingHistoryController
AccountNotificationController
NotificationPreferenceController
```

Do not create unnecessary one-method classes when an existing grouped architecture is preferred.

If using Livewire:

* Validate authorization on every action
* Do not trust public properties for user IDs
* Use authenticated user ID server-side
* Reset sensitive fields after action
* Preserve CSRF and session protections

---

# Step 26 — Frontend Design

The account dashboard must match the existing public news-site design.

Requirements:

* Reuse design tokens
* Reuse public layout
* Responsive desktop/tablet/mobile behavior
* Accessible labels
* Keyboard-accessible navigation
* Visible focus states
* Semantic headings
* Sufficient contrast
* Clear validation messages
* Clear success messages
* No horizontal overflow
* Cards stack appropriately on mobile
* Tables become responsive lists or scroll safely

Do not redesign the entire public frontend.

---

# Step 27 — Accessibility

Minimum accessibility requirements:

* Proper form labels
* Error association
* Button text understandable without icons
* ARIA only where necessary
* Keyboard-accessible modal and dropdown behavior
* Focus returned appropriately after actions
* Status not communicated by color alone
* Copy-link feedback announced where practical
* Empty states understandable

---

# Step 28 — Security Requirements

Audit and enforce:

* CSRF protection
* Authentication middleware
* Email-verification middleware where applicable
* Rate limiting for sensitive actions
* Current-password confirmation
* Session regeneration after password change where appropriate
* XSS-safe output
* Safe internal redirects
* No raw notification URL trust
* No mass assignment
* No insecure direct object references
* No other-user resource access
* No staff access escalation
* No GET state-changing routes

Do not log:

```text
password
password_confirmation
verification tokens
session IDs
reset tokens
```

---

# Step 29 — Referral Privacy

Subscriber may view only direct referrals linked through:

```text
users.ref_id = authenticated user ID
```

Do not expose:

* Referrals of another user
* Full referral hierarchy
* Staff users accidentally linked as referrals unless business rules permit it
* Sensitive account status details
* Financial values not backed by a real rewards system

Referral counts must use the same direct-referral scope as the list.

---

# Step 30 — Bookmarks and Published Visibility

A saved article must be displayable only if the current user can access the post publicly.

Use existing public visibility logic, such as:

```php
Post::query()->published()
```

Requirements:

* Draft saved through a manipulated request must be rejected
* Unpublished article removed from saved-list display
* Deleted post handled gracefully
* Bookmark row may be cleaned automatically or hidden safely
* Saving does not alter publication status
* Saving does not increment view count

---

# Step 31 — Reading-History Privacy

Reading history should be derived only from the authenticated user’s visits.

Do not combine anonymous `visitor_uuid` history with a user account unless a deliberate privacy-safe account-linking strategy exists.

For this phase:

```text
visitor_id = authenticated user ID
```

is the trusted ownership link.

Do not expose anonymous-history matching through cookies.

---

# Step 32 — Notifications Security

When a notification contains an action target:

* Map notification type to a known route
* Validate referenced resource ownership or public visibility
* Avoid rendering raw HTML from notification data
* Escape message content
* Reject external redirect URLs unless explicitly allow-listed
* Ignore stale or deleted resources gracefully

---

# Step 33 — Dashboard Query Performance

Requirements:

* Use counts
* Eager load relationships
* Paginate saved articles, referrals, history and notifications
* Avoid loading complete referral lists for dashboard cards
* Avoid per-row count queries
* Avoid parsing user agents on every request
* Add only necessary indexes
* Reuse Phase 15A indexes where possible

Potential indexes:

```text
users.ref_id
post_bookmarks.user_id + created_at
post_visits.visitor_id + visited_at
notifications.notifiable_type + notifiable_id + read_at
```

Audit before adding duplicates.

---

# Step 34 — Existing Data Handling

Existing users and posts must remain intact.

Requirements:

* Existing users retain passwords
* Existing roles retained
* Existing permissions retained
* Existing referral codes retained
* Existing referral relationships retained
* Existing post counts unchanged
* Existing bookmarks retained if present
* Existing notifications retained
* Existing post visits retained
* Existing view counters retained
* Existing editorial history retained
* Existing staff access retained

Do not backfill fabricated bookmarks, history or notifications.

---

# Step 35 — Tests

Add focused tests while preserving existing tests.

## Dashboard access tests

* Guest redirected to login
* Subscriber can access frontend dashboard
* Subscriber cannot access Filament
* Staff access follows existing redirect policy
* User without role cannot gain staff access
* Suspended user behavior follows existing auth rules

## Profile tests

* User can view own profile
* User can update own name
* User cannot update another user
* User cannot change roles
* User cannot change permissions
* User cannot change status
* User cannot change `refcode`
* User cannot change `ref_id`
* Duplicate email rejected
* Email change resets verification only when required
* Existing password preserved when not changed

## Password tests

* Correct current password required
* Incorrect current password rejected
* Password confirmation required
* New password is hashed
* Old password fails after change
* New password succeeds
* Sensitive values not persisted in session

## Verification tests

Where enabled:

* Verification status displayed
* Resend works
* Resend rate limited
* Already verified user handled
* Invalid signed link rejected

## Referral tests

* Referral code displayed
* Referral link uses code
* Direct referrals counted correctly
* User sees only own referrals
* Another user’s referral page denied
* Referral email or personal data remains protected
* Pagination works
* No fake reward values displayed

## Bookmark tests

* Authenticated user can save published post
* Guest cannot save
* Duplicate bookmark prevented
* User can unsave own bookmark
* User cannot delete another user’s bookmark
* Draft post cannot be saved
* Unpublished post not displayed in saved list
* Saved-list query belongs to authenticated user
* Bookmark action does not change view count

## Reading-history tests

* User sees own visit history
* User cannot see another user’s history
* Anonymous visit is not shown as authenticated history
* Repeated visits deduplicated when intended
* Latest read time displayed
* Raw analytics fields not exposed
* Clear-history action deletes only own visit rows when implemented
* Aggregate post view counter remains unchanged

## Notification tests

* User sees only own notifications
* User can mark own notification as read
* User cannot mark another user’s notification
* Mark-all-read affects only authenticated user
* Unsafe external URL not rendered as trusted action
* Deleted-resource notification handled safely
* Unread count correct

## Preference tests

* User can update supported preferences
* Unsupported fields rejected
* User cannot update another user’s preferences
* Non-functional preference options are not exposed

## Security tests

* CSRF protection applies
* State-changing GET routes absent
* IDOR attempts fail
* Mass-assignment attempts fail
* Subscriber cannot assign staff role
* Subscriber cannot access editorial data
* Subscriber cannot access unpublished posts
* Raw notification HTML is escaped
* Open redirects rejected

## Responsive and view tests

Where practical:

* Dashboard renders
* Empty saved-article state renders
* Empty referral state renders
* Empty notification state renders
* Unverified-email warning renders when applicable

## Existing-data tests

* User count unchanged
* Post count unchanged
* Existing role assignments unchanged
* Existing permissions unchanged
* Existing referral codes unchanged
* Existing view counters unchanged
* Existing post visits preserved
* Existing editorial data preserved

---

# Step 36 — Validation Commands

Run:

```bash
php artisan optimize:clear
php artisan route:list
php artisan migrate --pretend
php artisan migrate
php artisan test
```

Run focused tests:

```bash
php artisan test --filter=SubscriberDashboard
php artisan test --filter=Account
php artisan test --filter=Profile
php artisan test --filter=Password
php artisan test --filter=Referral
php artisan test --filter=Bookmark
php artisan test --filter=ReadingHistory
php artisan test --filter=Notification
```

If permissions are used:

```bash
php artisan permission:cache-reset
```

Run formatting only on modified files:

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

Phase 15E is complete only when:

* Subscriber frontend dashboard exists
* Guest access is blocked
* Subscriber can access frontend account pages
* Subscriber remains blocked from Filament
* Dashboard shows authenticated-user data only
* Profile update works securely
* Password change works securely
* Email-verification status is handled correctly
* Referral code is displayed read-only
* Referral link is generated safely
* Direct-referral summary works
* Referral list is privacy-safe
* Saved articles work or a compatible existing implementation is reused
* Bookmark actions use secure HTTP methods
* Only published posts can be saved
* Saved-list ownership is enforced
* Reading history uses authenticated visit records safely
* User sees only own reading history
* Notifications page shows only own notifications
* Notification read actions are secure
* Supported preferences work
* Unsupported toggles are not exposed
* No staff permissions can be assigned through frontend account forms
* No IDOR vulnerability exists
* Existing users remain intact
* Existing posts remain intact
* Existing roles and permissions remain intact
* Existing referral data remains intact
* Existing view counters remain intact
* Existing analytics remain intact
* Existing editorial data remains intact
* Relevant tests pass
* No destructive database command was used

---

# Required Completion Report

Return a structured completion report with the following sections.

## 1. Frontend Account Audit

Report:

* Existing dashboard route
* Existing account/profile implementation
* Existing frontend stack
* Existing shared layout
* Existing bookmark implementation
* Existing reading-history implementation
* Existing notification implementation
* Existing preference implementation

## 2. Route Structure

List:

* Dashboard route
* Profile routes
* Security routes
* Referral routes
* Saved-article routes
* Reading-history routes
* Notification routes
* Preference routes
* Middleware applied

## 3. Dashboard Implementation

Explain:

* Dashboard cards
* Quick actions
* Query sources
* Role behavior
* Empty states
* Performance decisions

## 4. Profile and Security

Explain:

* Editable fields
* Protected fields
* Email-change behavior
* Password-change behavior
* Session behavior
* Verification behavior

## 5. Referral Dashboard

Explain:

* Referral-code display
* Referral-link format
* Direct-referral count
* Referral-list privacy
* Pagination
* Invalid or missing referrer behavior

## 6. Saved Articles

Explain:

* Existing or new table
* Model and relationships
* Save action
* Unsave action
* Published-post restriction
* Saved-list page
* Empty state

## 7. Reading History

Explain:

* Data source
* `visitor_id` behavior
* Deduplication
* Privacy rules
* Clear-history decision
* Empty state

## 8. Notifications

Explain:

* Notification source
* List behavior
* Read/unread handling
* Safe action links
* Mark-all-read behavior
* Stale-resource handling

## 9. Preferences

List:

* Supported preferences
* Storage method
* Validation
* Deferred preferences
* Non-functional options intentionally omitted

## 10. Authorization and Security

Explain:

* Route protection
* Ownership checks
* Policies
* Form Requests
* Mass-assignment protections
* IDOR protections
* CSRF protections
* Role and permission protections
* Safe redirects

## 11. Frontend and Accessibility

Report:

* Layout used
* Components created
* Mobile behavior
* Accessibility improvements
* Validation and success-message behavior

## 12. Database Changes

List every:

* Migration
* New table
* New column
* New index
* Backfill
* Existing-data change

Confirm that no fabricated bookmarks, notifications or reading history were inserted.

## 13. Files Changed

List every created and modified file.

## 14. Tests and Validation

Report:

* Commands executed
* Focused tests
* Full test suite
* Total tests
* Total assertions
* Failures
* Pre-existing failures
* Formatting result
* Static-analysis result

## 15. Existing Data Verification

Report before and after counts for:

```text
users
posts
roles
permissions
post visits
bookmarks
notifications
```

Also confirm:

* Existing referral codes preserved
* Existing referral relationships preserved
* Existing passwords preserved
* Existing staff access preserved
* Existing view counters preserved
* Existing editorial history preserved

## 16. Deferred Work

Reserve the following for:

```text
Phase 15F — Security Hardening and Full Authorization Audit
```

Optional future subscriber features:

```text
Follow authors
Follow categories
Personalized news feed
Newsletter subscriptions
Browser push notifications
Comment system
Reading-progress sync
Multi-device session manager
Account deletion and data export
Referral rewards
Subscriber premium plans
```

Do not claim completion if:

* Subscriber can access Filament
* Subscriber can assign a staff role
* Subscriber can view another user’s referrals
* Subscriber can view another user’s bookmarks
* Subscriber can view another user’s reading history
* Subscriber can view another user’s notifications
* Draft or unpublished posts can be saved through request tampering
* Password change works without current-password validation
* Email change bypasses uniqueness or verification rules
* Referral codes can be edited publicly
* State changes use GET routes
* Existing users or posts are modified unexpectedly
* Relevant tests fail
