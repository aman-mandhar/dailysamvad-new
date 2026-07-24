# Daily Samvad — Version 2.1

## Phase 2.1-C: Editorial Workflow Completion

You are working on the existing Daily Samvad Laravel application.

The application currently uses:

* Laravel 13
* Filament 5
* Livewire 4
* Spatie Laravel Permission
* A permission-driven role and policy architecture established in Phase 2.1-B

Phase 2.1-A completed the baseline audit.

Phase 2.1-B established the role, permission and access-control architecture.

This phase must complete the editorial workflow foundation required by the upcoming role-specific dashboards.

Do not create separate role dashboards in this phase.

---

# 1. Primary Objective

Complete and secure the Reporter → Reviewer → Editor → Schedule/Publish editorial workflow.

The implementation must provide:

* Validated workflow transitions
* Reporter submission
* Reviewer assignment
* Review notes
* Correction requests
* Approval
* Rejection
* Scheduling
* Publishing
* Archiving
* Workflow history
* Role- and permission-based authorization
* Clear Filament actions
* Status visibility
* Notifications
* Scheduled publishing support
* Automated tests
* Backward compatibility

The workflow must be reliable, auditable and suitable for use by future dynamic dashboards.

---

# 2. Existing Audit Findings

The Version 2.1 baseline audit confirmed the following current post statuses:

```text
draft
pending_review
scheduled
published
rejected
archived
```

Confirmed current transitions:

```text
draft → pending_review
pending_review → published
pending_review → scheduled
scheduled → published
published → archived
```

Confirmed gaps:

* No complete transition into and out of `rejected`
* No complete correction-request workflow
* Review metadata fields are not consistently populated
* No complete reviewer-assignment mechanism
* No workflow-history table
* No editorial notifications
* No automatic scheduled publisher
* Permissions exist for some actions but corresponding actions are missing
* Some workflow state changes may be possible through ordinary record editing
* Reporter, Reviewer and Editor dashboards depend on workflow data that does not yet exist

Use the existing project code and the documents under:

```text
docs/version-2.1/
```

as the verified baseline.

---

# 3. Protected Boundaries

Do not disturb:

* Existing users
* Existing passwords
* Existing user IDs
* Existing role assignments
* Existing direct permissions
* Imported WordPress posts
* Imported WordPress media
* Existing published article URLs
* Existing slugs
* Legacy redirects
* SEO metadata
* Featured-media mappings
* Public routes
* Existing media paths
* WordPress importer architecture
* Existing post IDs
* Existing publication dates unless explicitly changed through a workflow action
* Existing author relationships
* Existing production environment configuration
* `.env`
* Storage configuration
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

Do not rewrite historical workflow data without a safe, documented compatibility strategy.

---

# 4. Workflow Design Principles

The workflow must follow these principles.

## 4.1 Explicit Transitions

Status changes must occur through explicit workflow actions or services.

Do not allow staff to bypass workflow rules by directly changing the status field in a generic edit form.

Preferred pattern:

```php
$workflow->submitForReview($post, $actor);
$workflow->assignReviewer($post, $reviewer, $actor);
$workflow->requestCorrections($post, $actor, $notes);
$workflow->approve($post, $actor);
$workflow->reject($post, $actor, $reason);
$workflow->schedule($post, $actor, $publishAt);
$workflow->publish($post, $actor);
$workflow->archive($post, $actor);
```

Adapt method names to existing project conventions.

---

## 4.2 Permission-Driven Authorization

Use policies and permissions.

Do not authorize transitions primarily through hardcoded role names.

Relevant permissions may include:

```text
submit posts for review
view assigned posts
review posts
request post corrections
approve posts
reject posts
schedule posts
publish posts
archive posts
assign reviewers
view workflow history
```

Reuse existing canonical permissions from Phase 2.1-B.

Create missing permissions only when the corresponding functionality is implemented and enforced.

---

## 4.3 Status and Action Separation

A user who can edit post content must not automatically be allowed to change workflow status.

Examples:

* A reporter may edit their own draft.
* A reporter may not directly mark it as published.
* A reviewer may add review feedback.
* A reviewer may not publish unless explicitly authorized.
* An SEO manager may edit SEO metadata.
* An SEO manager may not change editorial workflow status.
* A media manager may update media metadata.
* A media manager may not approve content.

---

## 4.4 Transactional Integrity

Every workflow transition must execute inside a database transaction where multiple fields, history records, assignments or notifications are involved.

If any part fails, the transition must not leave partial state.

---

## 4.5 Idempotency

Repeated requests must not accidentally duplicate:

* History records
* Notifications
* Reviewer assignments
* Scheduled-publish jobs
* Publish operations
* Review events

Publishing an already-published post must not republish or duplicate downstream actions.

---

# 5. Canonical Workflow States

Audit the existing enum, constants, casts and database values before changing anything.

The canonical workflow must support:

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

If `changes_requested` or `approved` do not currently exist, add them safely only if required by the final workflow design.

Do not remove existing statuses.

Do not rename database values without a backward-compatible migration.

Recommended meanings:

## draft

The post is being created or edited by its author or permitted staff.

## pending_review

The post has been formally submitted for editorial review.

## changes_requested

The reviewer or editor has returned the post for correction.

## approved

Editorial review is complete and the post is ready for scheduling or publishing.

## scheduled

The post is approved and scheduled for a future publish time.

## published

The post is publicly available.

## rejected

The submission has been rejected and requires a documented reason.

## archived

The post has been removed from active publication but retained historically.

---

# 6. Canonical Transition Matrix

Implement and document a strict transition matrix.

Recommended transitions:

```text
draft → pending_review
changes_requested → pending_review
pending_review → changes_requested
pending_review → approved
pending_review → rejected
approved → scheduled
approved → published
scheduled → published
scheduled → approved
rejected → draft
published → archived
archived → draft
```

Optional transitions may be supported only if justified by existing business behavior:

```text
pending_review → draft
approved → changes_requested
scheduled → changes_requested
published → draft
archived → published
```

Do not allow every status to transition to every other status.

Each transition must define:

* Required current status
* Target status
* Required permission
* Allowed actor scope
* Required fields
* Fields updated
* History event
* Notification behavior
* Downstream job behavior

Create a documented transition table.

---

# 7. Workflow Service

Audit the existing workflow implementation.

Create or refactor a central workflow service, action classes or state machine.

The architecture must provide one authoritative path for transitions.

Recommended location examples:

```text
app/Services/EditorialWorkflowService.php
```

or:

```text
app/Actions/Posts/SubmitPostForReview.php
app/Actions/Posts/RequestPostCorrections.php
app/Actions/Posts/ApprovePost.php
```

Choose the pattern that best matches the existing project architecture.

Do not duplicate transition logic inside Filament actions, controllers and model observers.

The service must:

* Authorize the action
* Validate the current state
* Validate required inputs
* Execute inside a transaction
* Update workflow metadata
* Record history
* Dispatch notifications
* Dispatch downstream jobs where required
* Return a clear result or throw a domain-specific exception

---

# 8. Workflow Metadata

Audit existing post columns before creating new ones.

Support the following metadata where not already available:

```text
submitted_at
submitted_by
reviewer_id
review_assigned_at
review_started_at
reviewed_at
reviewed_by
approved_at
approved_by
rejected_at
rejected_by
rejection_reason
corrections_requested_at
corrections_requested_by
correction_notes
scheduled_at
published_at
published_by
archived_at
archived_by
```

Do not blindly add all columns if existing equivalent fields already exist.

Prefer clear foreign keys and timestamps where appropriate.

Every new field must be:

* Nullable where required
* Indexed where query usage justifies it
* Backward-compatible
* Properly cast in the model
* Protected from unsafe mass assignment
* Covered by tests

Do not store large workflow history only in the posts table.

---

# 9. Reviewer Assignment

Implement a reliable reviewer-assignment mechanism.

Requirements:

* An authorized editor/admin may assign a reviewer.
* A reviewer may see assigned posts.
* Unauthorized users may not assign reviewers.
* A reporter may not self-assign a reviewer.
* Assignment must be recorded in workflow history.
* Reassignment must be supported.
* Reassignment must record the previous and new reviewer.
* Reviewer must be active.
* Reviewer must have appropriate review access.
* Assignment must not grant unrelated access.
* The same assignment must not be duplicated.

If automatic reviewer assignment is not required, do not invent it.

Manual assignment is acceptable for this phase.

---

# 10. Reporter Workflow

The Reporter experience must support:

```text
Create draft
Edit own eligible draft
Submit for review
View current status
View reviewer/editor feedback
Correct returned post
Resubmit for review
View publication or rejection outcome
```

Reporter restrictions:

* Cannot submit another user's post
* Cannot approve
* Cannot reject
* Cannot publish
* Cannot schedule
* Cannot archive published content
* Cannot edit post body after publication unless separately authorized
* Cannot delete protected published content
* Cannot clear reviewer feedback
* Cannot alter workflow history
* Cannot assign reviewers

When a post is submitted:

* Validate required content
* Set status to `pending_review`
* Record submission timestamp
* Record submitting user
* Create workflow-history event
* Notify appropriate editorial users where configured

---

# 11. Reviewer Workflow

The Reviewer experience must support:

```text
View assigned review queue
Open assigned post
Begin review
Add review notes
Request corrections
Recommend approval
Reject where authorized
View workflow history
```

Reviewer restrictions:

* Cannot review unrelated private posts unless permitted
* Cannot publish unless separately granted
* Cannot change post ownership
* Cannot modify roles
* Cannot assign themselves to arbitrary content
* Cannot erase historical review notes
* Cannot silently change status through ordinary editing

Decide and document whether reviewers:

```text
approve directly
```

or:

```text
recommend approval for editor action
```

Use the existing business model and permission architecture.

If approval is an Editor-only action, implement a reviewer recommendation state or history event without granting publish authority.

---

# 12. Editor Workflow

The Editor experience must support:

```text
View all permitted submissions
Assign reviewers
Review unassigned submissions
Request corrections
Approve
Reject
Schedule
Publish
Archive
Reassign reviews
View workflow history
```

Editor restrictions:

* Must follow workflow transitions
* Must provide reasons for rejection
* Must provide notes for correction requests
* Must not bypass required approval rules
* Must not silently overwrite reporter ownership
* Must not delete workflow history
* Must not publish invalid or incomplete content where validation rules apply

---

# 13. Correction Request Workflow

Implement a complete correction-request flow.

Recommended transition:

```text
pending_review → changes_requested
```

Required inputs:

```text
correction notes
```

Optional inputs:

```text
structured issue checklist
priority
deadline
```

At minimum:

* Notes must be required.
* Notes must be stored safely.
* The actor must be recorded.
* The time must be recorded.
* A history event must be created.
* The reporter must be notified.
* The reporter must be allowed to edit the post.
* The reporter may resubmit after corrections.
* Old notes must remain available in history.

Do not overwrite all previous correction notes with only the latest note.

---

# 14. Approval Workflow

Recommended transition:

```text
pending_review → approved
```

Approval must:

* Require appropriate permission
* Validate current status
* Record approver
* Record approval time
* Add a workflow-history event
* Notify the reporter
* Make the post eligible for scheduling or publishing
* Not automatically publish unless the application explicitly requires it

Do not treat approval and publication as the same action unless existing business rules require it.

---

# 15. Rejection Workflow

Recommended transition:

```text
pending_review → rejected
```

Rejection must:

* Require a reason
* Record rejecting user
* Record rejection time
* Create a history event
* Notify the reporter
* Prevent accidental publication
* Preserve the post and its content

Support a safe recovery path:

```text
rejected → draft
```

Only authorized users should reopen a rejected post.

Do not delete rejected posts.

---

# 16. Scheduling Workflow

Recommended transition:

```text
approved → scheduled
```

Scheduling must:

* Require `schedule posts`
* Require a valid future date and time
* Store the schedule in the application's configured timezone strategy
* Record the scheduling actor
* Create a history event
* Prevent scheduling an unapproved post
* Prevent scheduling in the past unless explicitly supported
* Prevent duplicate scheduled-publish jobs
* Support rescheduling
* Support cancellation back to `approved`

Clarify the use of:

```text
scheduled_at
published_at
```

Do not overload one field ambiguously.

---

# 17. Automatic Scheduled Publishing

Implement automatic publishing of due scheduled posts.

Use Laravel Scheduler and/or queued jobs according to the existing architecture.

Recommended architecture:

```text
Scheduler runs every minute
→ identifies due scheduled posts
→ dispatches idempotent publish jobs
→ job publishes through workflow service
```

Alternative architecture is acceptable if more suitable.

Requirements:

* Use the central workflow service
* Prevent duplicate publication
* Handle multiple due posts safely
* Use locking or unique-job behavior
* Record workflow history
* Record publishing result
* Preserve retry safety
* Handle failed jobs
* Avoid publishing future posts
* Avoid publishing cancelled schedules
* Avoid publishing posts no longer approved/scheduled
* Work with the current database queue
* Do not require Redis in this phase

Create or update the scheduler registration using Laravel 13 conventions.

Do not activate production workers or edit server Supervisor configuration in this phase unless the user explicitly requested deployment work.

Document required production cron and worker commands.

---

# 18. Publishing Workflow

Recommended transitions:

```text
approved → published
scheduled → published
```

Publishing must:

* Require publish permission or trusted scheduled-system context
* Validate status
* Set `published_at`
* Set `published_by`
* Preserve existing slug
* Preserve public route compatibility
* Create history
* Trigger existing SEO/cache/indexing behavior safely
* Avoid duplicate side effects
* Notify relevant users
* Be idempotent

Do not alter already-published imported posts unnecessarily.

Do not regenerate slugs during publication unless currently required.

---

# 19. Archive Workflow

Recommended transition:

```text
published → archived
```

Archiving must:

* Require archive permission
* Record actor and timestamp
* Create history
* Remove post from active public publication according to existing scopes
* Preserve the database record
* Preserve its workflow history
* Preserve media and SEO metadata
* Avoid deleting the post

If restoring is supported:

```text
archived → draft
```

or:

```text
archived → published
```

Choose one clear, documented rule.

---

# 20. Workflow History

Create a dedicated workflow-history model and table if one does not exist.

Suggested table:

```text
post_workflow_events
```

Suggested fields:

```text
id
post_id
actor_id
event
from_status
to_status
notes
metadata
created_at
updated_at
```

Possible events:

```text
created
submitted
reviewer_assigned
reviewer_reassigned
review_started
corrections_requested
resubmitted
approved
rejected
scheduled
rescheduled
schedule_cancelled
published
archived
restored
```

Requirements:

* Append-only under normal application behavior
* Ordered chronologically
* Related to post
* Related to actor where applicable
* Support system-generated events
* Store structured metadata as JSON where useful
* Never store passwords, tokens or sensitive secrets
* Do not allow ordinary users to edit or delete events
* Protect history through policies
* Include useful indexes

Existing posts must remain valid even if they have no historical events.

Do not invent inaccurate historical events for imported posts.

---

# 21. Review Notes

Determine whether review notes belong in:

* Workflow event notes
* A dedicated review-comments table
* Existing comments/notes architecture

For this phase, workflow events may store transition notes if sufficient.

Requirements:

* Correction notes must be preserved historically
* Rejection reasons must be preserved historically
* Notes must record author and timestamp
* Notes must be escaped safely in output
* Unauthorized users must not see private editorial notes
* Reporters may see feedback intended for them
* Subscribers and public visitors must never see internal notes

If public and internal notes need separation, implement a clear visibility field or separate structure.

---

# 22. Notifications

Implement editorial notifications using Laravel notifications.

At minimum support:

```text
Post submitted for review
Reviewer assigned
Corrections requested
Post resubmitted
Post approved
Post rejected
Post scheduled
Post published
```

Use database notifications where already supported.

Email notifications should not be required unless email infrastructure is already reliable and configured.

Requirements:

* Notifications must be queued where appropriate
* Notification recipients must be permission- and relationship-aware
* Prevent duplicate notifications
* Do not notify inactive users
* Do not expose internal editorial details to unauthorized recipients
* Notification links must point to authorized pages
* Failure to send a non-critical notification must not corrupt the workflow transition

If queueing database notifications creates test or infrastructure complexity, use the safest existing convention and document it.

---

# 23. Filament Workflow Actions

Add secure, status-aware Filament actions.

Possible actions:

```text
Submit for Review
Assign Reviewer
Start Review
Request Corrections
Approve
Reject
Schedule
Reschedule
Cancel Schedule
Publish Now
Archive
Restore
View Workflow History
```

Each action must:

* Be visible only when authorized
* Be visible only in valid statuses
* Validate input
* Call the central workflow service
* Show clear success messages
* Show useful validation/domain errors
* Avoid duplicated transition logic
* Be protected at backend level, not only hidden in UI

Use confirmation modals for destructive or high-impact actions.

Require notes or reasons where needed.

Do not expose all actions to all staff.

---

# 24. Filament Forms

The ordinary post edit form must not allow unauthorized direct workflow changes.

Audit the status field.

Preferred options:

* Make status read-only
* Hide status from unauthorized users
* Remove generic status selection
* Restrict selectable statuses to legal transitions
* Use dedicated workflow actions instead

Dedicated workflow actions are preferred.

Ensure users cannot manipulate hidden form payloads to bypass authorization.

---

# 25. Filament Tables

Update post tables to display useful workflow data.

Possible columns:

```text
Status
Author
Assigned Reviewer
Submitted At
Reviewed At
Scheduled At
Published At
Last Workflow Event
```

Use permission-aware visibility.

Avoid adding expensive per-row queries.

Use eager loading or optimized query scopes.

Add filters where useful:

```text
Status
Author
Reviewer
Submission Date
Scheduled Date
Published Date
Unassigned Reviews
My Assigned Reviews
```

Do not perform dashboard redesign in this phase.

---

# 26. Public Visibility

Public post queries must continue to show only content considered published under the existing publication rules.

Verify:

* Drafts are not public
* Pending review posts are not public
* Changes-requested posts are not public
* Approved but unpublished posts are not public
* Scheduled future posts are not public
* Rejected posts are not public
* Archived posts are not public unless explicitly intended
* Published posts remain public

Preserve legacy redirects and public article routes.

---

# 27. Policies

Complete or update policy methods for workflow actions.

Recommended PostPolicy methods:

```text
submitForReview
assignReviewer
startReview
requestCorrections
approve
reject
schedule
publish
archive
restore
viewWorkflowHistory
```

Each method must consider:

* Active account
* Permission
* Ownership
* Assignment
* Post status
* Scope
* Protected published records

Do not authorize solely based on UI visibility.

---

# 28. Domain Exceptions

Use clear domain exceptions or validation exceptions for invalid transitions.

Examples:

```text
InvalidPostTransition
PostAlreadyPublished
ReviewerAssignmentNotAllowed
PostNotReadyForReview
PostNotApprovedForScheduling
ScheduledTimeMustBeFuture
```

Do not leak stack traces or sensitive details to users.

Filament should convert expected domain errors into clear user-facing messages.

---

# 29. Validation Rules

Define minimum validation requirements before submission.

Audit existing content requirements first.

Possible checks:

```text
title is present
slug is present or safely generated
content is present
author exists
category exists where required
featured image exists where required
SEO fields are present only if the existing policy requires them
```

Do not introduce arbitrary editorial requirements not supported by the current project.

Separate:

```text
required to save draft
required to submit for review
required to approve
required to publish
```

Document each validation level.

---

# 30. Database Migrations

Create only safe, additive migrations.

Possible migrations:

```text
Add missing workflow metadata columns to posts
Create post_workflow_events table
Add reviewer assignment foreign key
Add workflow indexes
```

Requirements:

* No destructive column changes
* No data loss
* No renaming without compatibility
* Existing posts remain readable
* Existing published posts remain published
* Foreign keys use safe delete behavior
* Rollback is supported where practical
* Large-table migration risks are documented
* Production migration does not rewrite all posts unnecessarily

Do not backfill fictional history.

---

# 31. Model Updates

Update the Post model carefully.

Requirements may include:

* Status enum cast
* Date casts
* Reviewer relationship
* Submitted-by relationship
* Approved-by relationship
* Published-by relationship
* Workflow-events relationship
* Useful scopes
* Safe fillable/guarded handling

Avoid excessive model event side effects.

Do not automatically transition status from generic model observers.

Workflow transitions must use the workflow service.

---

# 32. Query Scopes

Create or refine efficient scopes where useful:

```text
drafts
pendingReview
changesRequested
approved
scheduled
dueForPublishing
published
archived
assignedTo
ownedBy
reviewableBy
```

Scopes must:

* Select correct statuses
* Respect publication dates
* Avoid unnecessary joins
* Use indexes
* Be testable
* Preserve existing public `published()` behavior where possible

---

# 33. Concurrency and Locking

Protect critical transitions.

Examples:

* Two editors approving simultaneously
* Scheduler and editor publishing simultaneously
* Reassignment during active review
* Rescheduling while publishing job is running

Use database row locks, atomic updates, unique jobs or equivalent safe strategies where justified.

Do not over-engineer low-risk actions.

Publishing and scheduled-publishing require the strongest protection.

---

# 34. Queue Architecture

This phase may create jobs needed by the workflow.

Possible jobs:

```text
PublishScheduledPost
SendEditorialNotification
ProcessPostPublicationSideEffects
```

Use the existing queue connection.

Do not activate Redis.

Jobs must:

* Be idempotent
* Define retries and timeout
* Handle missing/deleted records safely
* Avoid duplicate side effects
* Record or report failures
* Use explicit queue names only if the project is ready for them

Do not introduce a complex queue topology in this phase.

Queue optimization belongs to a later phase.

---

# 35. Scheduler Architecture

Register due-post publishing using Laravel 13 scheduling conventions.

The scheduled task should:

* Run at an appropriate frequency
* Avoid overlapping
* Use one-server protection where supported and appropriate
* Dispatch due posts safely
* Log failures without exposing secrets
* Be testable

Document required production cron:

```bash
* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
```

Do not assume the actual production path.

Document queue-worker requirements separately.

---

# 36. Existing Data Compatibility

Verify existing records in every current status.

Do not assume all historical records have:

* Reviewer
* Submitted timestamp
* Approver
* Workflow history
* Published-by value

The application must tolerate null historical metadata.

Existing published imported posts must remain valid.

Do not force historical posts through the new workflow.

---

# 37. Required Automated Tests

Create focused tests covering the complete workflow.

## 37.1 Submission Tests

Verify:

* Reporter can submit own draft
* Reporter cannot submit another user's draft
* Draft becomes pending review
* Submission metadata is stored
* History event is created
* Appropriate notification is created
* Invalid content cannot be submitted
* Already pending post cannot be submitted again

---

## 37.2 Reviewer Assignment Tests

Verify:

* Authorized editor can assign reviewer
* Reporter cannot assign reviewer
* Reviewer must be active
* Reviewer must be eligible
* Reassignment records history
* Duplicate assignment is avoided
* Reviewer can view assigned post
* Reviewer cannot view unrelated restricted post

---

## 37.3 Correction Tests

Verify:

* Authorized reviewer/editor can request corrections
* Notes are required
* Status becomes changes requested
* Reporter is notified
* History is preserved
* Reporter can edit returned post
* Reporter can resubmit
* Old correction notes remain available
* Unauthorized users cannot request corrections

---

## 37.4 Approval Tests

Verify:

* Authorized actor can approve
* Invalid status cannot be approved
* Approval metadata is recorded
* History is recorded
* Reporter is notified
* Approval does not automatically publish unless explicitly intended
* Unauthorized reviewer cannot approve if approval is Editor-only

---

## 37.5 Rejection Tests

Verify:

* Rejection requires reason
* Rejection stores actor and timestamp
* History is recorded
* Reporter is notified
* Rejected post is not public
* Authorized reopening works
* Unauthorized reopening fails

---

## 37.6 Scheduling Tests

Verify:

* Approved post can be scheduled
* Unapproved post cannot be scheduled
* Past date is rejected
* Scheduling metadata is stored
* Rescheduling works
* Cancellation works
* History events are created
* Future scheduled post is not public

---

## 37.7 Automatic Publishing Tests

Verify:

* Due scheduled post is selected
* Future post is ignored
* Cancelled post is ignored
* Due post publishes exactly once
* Duplicate jobs do not duplicate publication
* Workflow history is created
* Published metadata is recorded
* Public scope includes newly published post
* Failed job can retry safely

Use time travel helpers.

Do not rely on actual waiting.

---

## 37.8 Direct Publishing Tests

Verify:

* Authorized editor can publish approved post
* Reporter cannot publish
* Reviewer cannot publish without permission
* SEO manager cannot publish
* Already-published post is idempotent or rejected safely
* Slug remains unchanged
* Public route remains valid

---

## 37.9 Archive Tests

Verify:

* Authorized user can archive published post
* Archived post is not publicly visible
* History is created
* Content is not deleted
* Unauthorized user cannot archive
* Restore behavior matches documented rule

---

## 37.10 Workflow History Tests

Verify:

* Events are append-only in normal use
* Events record actor
* System events support null/system actor where needed
* Notes and metadata are stored
* Unauthorized users cannot modify history
* Reporter can view relevant feedback
* Subscriber cannot view internal workflow history

---

## 37.11 Policy Tests

Verify every workflow policy method.

Test:

* Permission
* Ownership
* Assignment
* Active status
* Current post status
* Unauthorized direct request

---

## 37.12 Filament Action Tests

Where practical, test Livewire/Filament actions:

* Visibility
* Authorization
* Form validation
* Status changes
* Notifications
* Invalid transition handling

---

## 37.13 Regression Tests

Verify:

* Existing published posts remain visible
* Existing public article routes work
* Existing legacy redirects work
* Existing SEO metadata remains unchanged
* Existing media relationships remain unchanged
* Existing importer tests remain green
* Subscriber cannot access Filament
* Existing role permissions remain intact

---

# 38. Test Execution

Run focused workflow tests first.

Suggested commands:

```bash
php artisan test tests/Feature/EditorialWorkflow
php artisan test tests/Feature/Filament/PostWorkflow
php artisan test tests/Feature/Authorization/PostPolicyTest.php
php artisan test tests/Feature/ScheduledPublishing
```

Adapt paths to actual files.

Then run relevant regression suites:

```bash
php artisan test tests/Feature/Auth
php artisan test tests/Feature/Import
php artisan test tests/Feature/Seo
php artisan test tests/Feature/Media
php artisan test tests/Feature/Public
```

Finally run:

```bash
php artisan test
```

Do not hide failures.

Clearly distinguish:

```text
pre-existing failure
new regression
environment failure
fixed failure
```

---

# 39. Documentation Deliverables

Create or update:

```text
docs/version-2.1/phase-2.1-c-editorial-workflow.md
docs/version-2.1/editorial-status-catalogue.md
docs/version-2.1/editorial-transition-matrix.md
docs/version-2.1/editorial-permission-matrix.md
docs/version-2.1/reviewer-assignment-rules.md
docs/version-2.1/workflow-history-architecture.md
docs/version-2.1/scheduled-publishing-runbook.md
docs/version-2.1/editorial-notification-matrix.md
```

Documentation must include:

* Final statuses
* Transition matrix
* Required permissions
* Actor scope
* Reviewer assignment rules
* Validation rules
* Workflow-history events
* Notification recipients
* Scheduled publishing architecture
* Queue and scheduler requirements
* Production cron requirements
* Backward-compatibility decisions
* Known deferred items
* Test results
* Remaining risks

Do not include secrets or personal user data.

---

# 40. Completion Criteria

Phase 2.1-C is complete only when:

* Workflow statuses are explicitly documented.
* Valid transitions are enforced.
* Invalid transitions are blocked.
* Reporter can submit and resubmit posts.
* Reviewer assignment works securely.
* Correction requests preserve notes and history.
* Approval works securely.
* Rejection works securely.
* Approved posts can be scheduled.
* Scheduled posts publish automatically.
* Manual publishing is permission-protected.
* Publishing is idempotent.
* Archiving preserves content.
* Workflow history is stored.
* Notifications are implemented.
* Filament workflow actions use the central workflow service.
* Direct status manipulation is blocked.
* Public post visibility remains correct.
* Existing imported posts remain compatible.
* Existing public URLs remain unchanged.
* Focused workflow tests pass.
* Relevant regression tests pass.
* Full-suite result is reported honestly.
* Required documentation is complete.
* No role-specific dashboard is implemented.

---

# 41. Deferred Items

Do not implement:

* Separate Admin dashboard
* Separate Editor dashboard
* Separate Reviewer dashboard
* Separate Reporter dashboard
* Separate SEO Manager dashboard
* Separate Media Manager dashboard
* Separate Analytics dashboard
* Dashboard UI redesign
* Redis activation
* Full-page caching
* General queue optimization
* Search engine integration
* Analytics event collection
* Image conversion pipeline
* Full frontend redesign
* News-Man integration

These belong to later Version 2.1 phases.

---

# 42. Required Completion Report Format

Return the completion report using this exact structure:

## 1. Executive Summary

## 2. Existing Workflow Audit

## 3. Final Workflow Statuses

## 4. Final Transition Matrix

## 5. Workflow Service Architecture

## 6. Reporter Workflow

## 7. Reviewer Workflow

## 8. Editor Workflow

## 9. Reviewer Assignment

## 10. Correction Request Workflow

## 11. Approval Workflow

## 12. Rejection Workflow

## 13. Scheduling Workflow

## 14. Automatic Scheduled Publishing

## 15. Publishing Workflow

## 16. Archive and Restore Workflow

## 17. Workflow Metadata

## 18. Workflow History

## 19. Notifications

## 20. Policies and Permissions

## 21. Filament Actions and Forms

## 22. Public Visibility Verification

## 23. Queue and Scheduler Architecture

## 24. Database Migrations

## 25. Backward-Compatibility Verification

## 26. Automated Tests Added or Updated

## 27. Focused Test Results

## 28. Regression Test Results

## 29. Full Test-Suite Result

## 30. Documentation Created

## 31. Files Created or Modified

## 32. Commands Executed

## 33. Risks and Open Questions

## 34. Deferred Items

## 35. Final Phase Decision

The final phase decision must be one of:

```text
COMPLETE
COMPLETE WITH CONDITIONS
INCOMPLETE
```

Explain the decision using verified evidence.

---

# 43. Strict Rules

* Inspect the existing implementation before changing it.
* Follow existing project conventions.
* Use permission-driven authorization.
* Do not rely on role-name checks unless unavoidable.
* Use a central workflow service or equivalent domain layer.
* Do not duplicate transition logic.
* Do not allow generic status editing to bypass workflow rules.
* Do not create fictional historical events.
* Do not alter imported published posts unnecessarily.
* Do not change existing slugs.
* Do not change public article URLs.
* Do not alter SEO metadata unnecessarily.
* Do not alter featured-media mappings.
* Do not alter `.env`.
* Do not activate Redis.
* Do not implement dashboards.
* Do not run destructive database commands.
* Do not expose passwords, hashes, tokens or secrets.
* Do not claim tests passed unless they were executed successfully.
* Clearly report pre-existing failures.
* Preserve backward compatibility.
