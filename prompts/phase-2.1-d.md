Daily Samvad — Version 2.1

Phase 2.1-C: Editorial Workflow Completion

You are working on the existing Daily Samvad Laravel application.

The application currently uses:

Laravel 13

Filament 5

Livewire 4

Spatie Laravel Permission

A permission-driven role and policy architecture established in Phase 2.1-B

Phase 2.1-A completed the baseline audit.

Phase 2.1-B established the role, permission and access-control architecture.

This phase must complete the editorial workflow foundation required by the upcoming role-specific dashboards.

Do not create separate role dashboards in this phase.

1. Primary Objective

Complete and secure the Reporter → Reviewer → Editor → Schedule/Publish editorial workflow.

The implementation must provide:

Validated workflow transitions

Reporter submission

Reviewer assignment

Review notes

Correction requests

Approval

Rejection

Scheduling

Publishing

Archiving

Workflow history

Role- and permission-based authorization

Clear Filament actions

Status visibility

Notifications

Scheduled publishing support

Automated tests

Backward compatibility

The workflow must be reliable, auditable and suitable for use by future dynamic dashboards.

2. Existing Audit Findings

The Version 2.1 baseline audit confirmed the following current post statuses:

draft
pending_review
scheduled
published
rejected
archived

Confirmed current transitions:

draft → pending_review
pending_review → published
pending_review → scheduled
scheduled → published
published → archived

Confirmed gaps:

No complete transition into and out of rejected

No complete correction-request workflow

Review metadata fields are not consistently populated

No complete reviewer-assignment mechanism

No workflow-history table

No editorial notifications

No automatic scheduled publisher

Permissions exist for some actions but corresponding actions are missing

Some workflow state changes may be possible through ordinary record editing

Reporter, Reviewer and Editor dashboards depend on workflow data that does not yet exist

Use the existing project code and the documents under:

docs/version-2.1/

as the verified baseline.

3. Protected Boundaries

Do not disturb:

Existing users

Existing passwords

Existing user IDs

Existing role assignments

Existing direct permissions

Imported WordPress posts

Imported WordPress media

Existing published article URLs

Existing slugs

Legacy redirects

SEO metadata

Featured-media mappings

Public routes

Existing media paths

WordPress importer architecture

Existing post IDs

Existing publication dates unless explicitly changed through a workflow action

Existing author relationships

Existing production environment configuration

.env

Storage configuration

Deployment configuration

Do not run destructive commands.

Prohibited commands include:

php artisan migrate:fresh
php artisan migrate:refresh
php artisan migrate:reset
php artisan db:wipe
git reset --hard
git clean -fd
composer update
npm update

Do not rewrite historical workflow data without a safe, documented compatibility strategy.

4. Workflow Design Principles

4.1 Explicit Transitions

Status changes must occur through explicit workflow actions or services.

Do not allow staff to bypass workflow rules by directly changing the status field in a generic edit form.

Preferred pattern:

$workflow->submitForReview($post, $actor);
$workflow->assignReviewer($post, $reviewer, $actor);
$workflow->requestCorrections($post, $actor, $notes);
$workflow->approve($post, $actor);
$workflow->reject($post, $actor, $reason);
$workflow->schedule($post, $actor, $publishAt);
$workflow->publish($post, $actor);
$workflow->archive($post, $actor);

Adapt method names to existing project conventions.

4.2 Permission-Driven Authorization

Use policies and permissions.

Do not authorize transitions primarily through hardcoded role names.

Relevant permissions may include:

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

Reuse existing canonical permissions from Phase 2.1-B.

Create missing permissions only when the corresponding functionality is implemented and enforced.

4.3 Status and Action Separation

A user who can edit post content must not automatically be allowed to change workflow status.

4.4 Transactional Integrity

Every workflow transition must execute inside a database transaction where multiple fields, history records, assignments or notifications are involved.

4.5 Idempotency

Repeated requests must not accidentally duplicate:

History records

Notifications

Reviewer assignments

Scheduled-publish jobs

Publish operations

Review events

5. Canonical Workflow States

Audit the existing enum, constants, casts and database values before changing anything.

The canonical workflow must support:

draft
pending_review
changes_requested
approved
scheduled
published
rejected
archived

If changes_requested or approved do not currently exist, add them safely only if required by the final workflow design.

Do not remove existing statuses.

Do not rename database values without a backward-compatible migration.

6. Canonical Transition Matrix

Recommended transitions:

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

Each transition must define:

Required current status

Target status

Required permission

Allowed actor scope

Required fields

Fields updated

History event

Notification behavior

Downstream job behavior

7. Workflow Service

Audit the existing workflow implementation.

Create or refactor a central workflow service, action classes or state machine.

Recommended location:

app/Services/EditorialWorkflowService.php

The service must:

Authorize the action

Validate the current state

Validate required inputs

Execute inside a transaction

Update workflow metadata

Record history

Dispatch notifications

Dispatch downstream jobs where required

Return a clear result or throw a domain-specific exception

8. Workflow Metadata

Audit existing post columns before creating new ones.

Support the following metadata where not already available:

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

Do not blindly add all columns if equivalent fields already exist.

9. Reviewer Assignment

Implement a reliable reviewer-assignment mechanism.

Requirements:

Authorized editor/admin may assign a reviewer.

Reviewer may see assigned posts.

Unauthorized users may not assign reviewers.

Reporter may not self-assign a reviewer.

Assignment must be recorded in workflow history.

Reassignment must be supported and audited.

Reviewer must be active and eligible.

Duplicate assignments must be prevented.

10. Reporter Workflow

Support:

Create draft
Edit own eligible draft
Submit for review
View current status
View reviewer/editor feedback
Correct returned post
Resubmit for review
View publication or rejection outcome

Reporter must not approve, reject, publish, schedule, archive, assign reviewers or alter workflow history.

11. Reviewer Workflow

Support:

View assigned review queue
Open assigned post
Begin review
Add review notes
Request corrections
Recommend approval
Reject where authorized
View workflow history

Document whether reviewers approve directly or recommend approval to editors.

12. Editor Workflow

Support:

View submissions
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

13. Correction Request Workflow

Recommended transition:

pending_review → changes_requested

Requirements:

Notes are mandatory.

Actor and timestamp are recorded.

History event is created.

Reporter is notified.

Reporter can edit and resubmit.

Previous notes remain available.

14. Approval Workflow

Recommended transition:

pending_review → approved

Approval must record actor, timestamp and history, notify the reporter, and make the post eligible for schedule or publish.

15. Rejection Workflow

Recommended transition:

pending_review → rejected

Rejection requires a reason, actor, timestamp, history and reporter notification.

Support safe recovery:

rejected → draft

16. Scheduling Workflow

Recommended transition:

approved → scheduled

Scheduling must:

Require permission

Require future date/time

Record actor and history

Prevent unapproved scheduling

Support rescheduling

Support cancellation to approved

17. Automatic Scheduled Publishing

Implement automatic publishing of due scheduled posts.

Recommended architecture:

Scheduler runs every minute
→ identifies due scheduled posts
→ dispatches idempotent publish jobs
→ job publishes through workflow service

Requirements:

Prevent duplicate publication

Handle multiple due posts safely

Use locking or unique jobs

Preserve retry safety

Avoid publishing future or cancelled posts

Work with current database queue

Do not require Redis

Document production cron and worker requirements.

18. Publishing Workflow

Publishing must:

Require publish permission or trusted system context

Validate status

Set published_at

Set published_by

Preserve slug and public URL

Create history

Trigger existing SEO/cache/indexing behavior safely

Avoid duplicate side effects

Be idempotent

19. Archive Workflow

Recommended transition:

published → archived

Archiving must preserve the post, media, SEO and history while removing it from active public publication.

20. Workflow History

Create a dedicated history table if none exists:

post_workflow_events

Suggested fields:

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

History should be append-only under normal application behavior.

Do not invent historical events for imported posts.

21. Review Notes

Correction notes, rejection reasons and review notes must:

Preserve author and timestamp

Be safely escaped

Remain private to authorized staff/reporters

Never be exposed publicly

Preserve previous notes

22. Notifications

Implement Laravel notifications for:

Post submitted
Reviewer assigned
Corrections requested
Post resubmitted
Post approved
Post rejected
Post scheduled
Post published

Use database notifications where appropriate.

Avoid duplicate notifications and exclude inactive users.

23. Filament Workflow Actions

Add secure, status-aware actions:

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

Each action must call the central workflow service and enforce backend authorization.

24. Filament Forms

Prevent unauthorized direct status changes.

Prefer dedicated workflow actions rather than a generic editable status dropdown.

25. Filament Tables

Display and filter useful workflow data:

Status
Author
Assigned Reviewer
Submitted At
Reviewed At
Scheduled At
Published At
Last Workflow Event

Avoid N+1 queries.

26. Public Visibility

Verify only published content is public.

Draft, pending, changes-requested, approved, scheduled-future, rejected and archived posts must remain private.

Preserve public routes and legacy redirects.

27. Policies

Complete PostPolicy abilities:

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

Consider active status, permission, ownership, assignment and current post status.

28. Domain Exceptions

Use clear domain exceptions for invalid transitions, duplicate publication and reviewer assignment errors.

29. Validation Rules

Separate validation requirements for:

save draft
submit for review
approve
publish

Do not introduce unsupported arbitrary editorial requirements.

30. Database Migrations

Use safe, additive migrations only.

Possible migrations:

Add missing workflow metadata columns
Create post_workflow_events
Add reviewer assignment foreign key
Add workflow indexes

Do not rewrite or delete existing post data.

31. Model Updates

Update Post model with safe casts, relationships and scopes.

Do not automatically transition status from model observers.

32. Query Scopes

Possible scopes:

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

Preserve existing public published() behavior.

33. Concurrency and Locking

Protect critical transitions against simultaneous approval, publishing, reassignment and scheduler conflicts.

34. Queue Architecture

Possible jobs:

PublishScheduledPost
SendEditorialNotification
ProcessPostPublicationSideEffects

Use current queue connection.

Do not activate Redis.

35. Scheduler Architecture

Use Laravel 13 scheduling conventions.

The task should:

Run appropriately

Avoid overlap

Be safe on one server where relevant

Dispatch due posts safely

Be testable

36. Existing Data Compatibility

Existing imported and published posts must remain valid even without historical workflow metadata.

Do not force old posts through the new workflow.

37. Required Automated Tests

Create focused tests covering:

Submission

Reviewer assignment

Corrections

Approval

Rejection

Scheduling

Automatic publishing

Manual publishing

Archiving/restoring

Workflow history

Policy authorization

Filament action visibility and execution

Public visibility

Existing route, SEO, media and importer regressions

Use time-travel helpers for scheduling tests.

38. Test Execution

Run focused workflow tests first, then relevant regressions, then:

php artisan test

Do not hide failures.

Clearly classify pre-existing, new, environmental and fixed failures.

39. Documentation Deliverables

Create or update:

docs/version-2.1/phase-2.1-c-editorial-workflow.md
docs/version-2.1/editorial-status-catalogue.md
docs/version-2.1/editorial-transition-matrix.md
docs/version-2.1/editorial-permission-matrix.md
docs/version-2.1/reviewer-assignment-rules.md
docs/version-2.1/workflow-history-architecture.md
docs/version-2.1/scheduled-publishing-runbook.md
docs/version-2.1/editorial-notification-matrix.md

40. Completion Criteria

Phase 2.1-C is complete only when:

Valid transitions are enforced.

Invalid transitions are blocked.

Reporter submission/resubmission works.

Reviewer assignment works.

Corrections preserve notes/history.

Approval and rejection work.

Scheduling and automatic publishing work.

Publishing is permission-protected and idempotent.

Archiving preserves data.

Workflow history and notifications exist.

Filament actions use central workflow logic.

Direct status bypass is blocked.

Public visibility remains correct.

Existing imported data and URLs remain compatible.

Tests and documentation are complete.

No role-specific dashboard is implemented.

41. Deferred Items

Do not implement:

Separate role dashboards

Dashboard UI redesign

Redis activation

Full-page caching

General queue optimization

Search engine integration

Analytics collection

Image conversion pipeline

Frontend redesign

News-Man integration

42. Required Completion Report Format

Return the completion report using this exact structure:

1. Executive Summary

2. Existing Workflow Audit

3. Final Workflow Statuses

4. Final Transition Matrix

5. Workflow Service Architecture

6. Reporter Workflow

7. Reviewer Workflow

8. Editor Workflow

9. Reviewer Assignment

10. Correction Request Workflow

11. Approval Workflow

12. Rejection Workflow

13. Scheduling Workflow

14. Automatic Scheduled Publishing

15. Publishing Workflow

16. Archive and Restore Workflow

17. Workflow Metadata

18. Workflow History

19. Notifications

20. Policies and Permissions

21. Filament Actions and Forms

22. Public Visibility Verification

23. Queue and Scheduler Architecture

24. Database Migrations

25. Backward-Compatibility Verification

26. Automated Tests Added or Updated

27. Focused Test Results

28. Regression Test Results

29. Full Test-Suite Result

30. Documentation Created

31. Files Created or Modified

32. Commands Executed

33. Risks and Open Questions

34. Deferred Items

35. Final Phase Decision

The final phase decision must be one of:

COMPLETE
COMPLETE WITH CONDITIONS
INCOMPLETE

Explain the decision using verified evidence.

43. Strict Rules

Inspect existing implementation before changing it.

Follow project conventions.

Use permission-driven authorization.

Use one central workflow domain layer.

Do not duplicate transition logic.

Prevent generic status bypass.

Do not create fictional history.

Do not alter imported published posts unnecessarily.

Do not change slugs or public URLs.

Do not alter SEO metadata or featured-media mappings unnecessarily.

Do not alter .env.

Do not activate Redis.

Do not implement dashboards.

Do not run destructive database commands.

Do not expose passwords, hashes, tokens or secrets.

Do not claim tests passed unless executed successfully.

Clearly report pre-existing failures.

Preserve backward compatibility.