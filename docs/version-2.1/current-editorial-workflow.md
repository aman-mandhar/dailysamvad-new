# Current Editorial Workflow

Audit date: 2026-07-24. Sources: `PostStatus`, `PostWorkflow`, `PostPolicy`, post Filament resource/pages, post model/migrations, tests, and console scheduler routes.

## Data model

Actual statuses: `draft`, `pending_review`, `scheduled`, `published`, `rejected`, `archived`.

Relevant post fields include `status`, `author_id`, `reviewed_by`, `scheduled_at`, `submitted_at`, `reviewed_at`, `review_notes`, and `published_at`. The review metadata columns exist, but no workflow-history table/model was found.

## Validated transitions

```text
draft --(submit own posts)--> pending_review
pending_review --(publish posts)--> published
pending_review --(publish posts + future time)--> scheduled
scheduled --(publish posts)--> published
published --(publish posts)--> archived

same status --(edit own/all/update posts)--> same status
super-admin/manage roles --(override)--> any enum status
```

`PostWorkflow::validate` rejects invalid transitions and past/missing scheduled times. Create/edit pages call validation; bulk publish/archive uses a transaction, row lock, policy authorization, and `PostWorkflow::transition`. Published timestamps are set on first publication and then preserved.

## Confirmed gaps

- `rejected` is present and displayed as “Corrections required,” but no standard transition into or out of rejected exists for editor/reviewer permissions; only the `manage roles` override can traverse it through `PostWorkflow`.
- Permissions for approve/reject/request corrections exist, but no matching Filament actions or transition branches were found.
- `reviewed_by`, `submitted_at`, `reviewed_at`, and `review_notes` are not populated by `PostWorkflow`.
- No immutable transition log/history exists.
- No reviewer-assignment workflow was found beyond the `reviewed_by` relationship/field.
- Password reset and subscriber notification infrastructure exist, but no editorial transition notifications were found.
- No scheduled task publishes due scheduled posts. The only console schedule definition is the default `inspire` command; scheduled publication is not queue- or scheduler-driven.

## Authorization assessment

Form and bulk transitions are validated. Policies scope view/update/delete access. Direct model updates outside the Filament workflow could bypass transition validation because it is a support service, not a model-level invariant or database constraint. This is a medium implementation risk; no bypass was executed during the audit.
