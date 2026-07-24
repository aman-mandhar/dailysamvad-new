# Reviewer assignment rules

An active user with `assign reviewers` and all-post visibility may assign an active user holding `review posts` to a pending-review post. `posts.reviewed_by` is retained as the existing reviewer foreign key; `review_assigned_at` records the time. Same-reviewer assignment is idempotent. Reassignment records previous and new IDs in event metadata. Assignment does not confer unrelated access. Reviewers see only posts assigned to them unless another permission expands scope.

Reviewers approve directly in this business model, but approval never publishes and reviewers have no publishing permission.
