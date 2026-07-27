Fix Newly Created Posts Not Appearing in Filament Posts List

Problem

New posts are being created successfully and are visible in the posts database table, but they are not appearing on the Filament Posts listing page:

/admin/posts

The relevant resource paths are:

app/Filament/Resources/Posts/PostResource.php
app/Filament/Resources/Posts/Pages/ListPosts.php
app/Filament/Resources/Posts/Pages/CreatePost.php
app/Models/Post.php
app/Support/Authorization/ContentAccess.php
app/Policies/PostPolicy.php
app/Enums/PostStatus.php

The current resource query in:

app/Filament/Resources/Posts/PostResource.php

uses:

public static function getEloquentQuery(): Builder
{
    return ContentAccess::scopePosts(
        parent::getEloquentQuery(),
        auth()->user()
    )->with([
        'author',
        'reviewer',
        'primaryCategory',
        'categories',
        'tags',
    ]);
}

ListPosts.php currently contains only the standard Filament list page and Create action, so the likely cause is the query restriction inside:

app/Support/Authorization/ContentAccess.php

especially:

ContentAccess::scopePosts()

Objective

Find and fix the exact reason why newly created posts exist in the database but do not appear in the authorized user's Filament Posts list.

The fix must preserve the intended role-based access rules.

Required Investigation

Before modifying code, inspect:

app/Support/Authorization/ContentAccess.php
app/Filament/Resources/Posts/PostResource.php
app/Filament/Resources/Posts/Pages/ListPosts.php
app/Filament/Resources/Posts/Pages/CreatePost.php
app/Models/Post.php
app/Policies/PostPolicy.php
app/Enums/PostStatus.php

Also inspect any tests related to:

ContentAccess
PostResource
ListPosts
CreatePost
post permissions
role-based visibility
editorial workflow

Determine whether scopePosts() is filtering by any of the following:

author_id
status
published_at
reviewed_by
review assignment
ownership
role
permission
soft deletes

Do not assume the problem. Confirm it through the actual query and tests.

Expected Access Behaviour

Implement or preserve the following logical behaviour according to the existing permissions architecture.

Users with view all posts

A user with permission:

view all posts

must be able to see all non-deleted posts, including:

Draft

Pending review

In review

Approved

Rejected

Scheduled

Published

Archived, only if existing project rules allow archived posts in the normal list

They must not be restricted by:

author_id
published_at
reviewed_by

unless the existing authorization requirements explicitly require it.

Users without view all posts

Users without view all posts should only see records allowed by the existing role workflow, such as:

Their own authored posts

Posts assigned to them for review

Other records explicitly permitted by the existing authorization design

Do not weaken these restrictions globally.

Important Checks

Check whether newly created posts have values such as:

status = draft
published_at = null
author_id = current user
deleted_at = null

A draft post with published_at = null must still appear in the Filament admin list for an authorized user.

The Filament admin listing must not accidentally use the frontend model scope:

published()

because that scope intentionally requires:

status = published
published_at IS NOT NULL
published_at <= now()

The frontend published scope in app/Models/Post.php must remain unchanged.

Sorting Requirement

Ensure the Posts table defaults to showing the newest created records first.

In:

app/Filament/Resources/Posts/PostResource.php

add an appropriate default sort if one is not already present:

->defaultSort('created_at', 'desc')

Prefer created_at DESC.

Do not sort primarily by published_at, because draft posts may have a null published_at.

Likely Fix Area

The primary expected fix location is:

app/Support/Authorization/ContentAccess.php

inside:

public static function scopePosts(Builder $query, ?User $user): Builder

A valid shape may resemble:

public static function scopePosts(Builder $query, ?User $user): Builder
{
    if (! $user) {
        return $query->whereRaw('1 = 0');
    }

    if ($user->can('view all posts')) {
        return $query;
    }

    return $query->where(function (Builder $query) use ($user): void {
        $query
            ->where('author_id', $user->getKey())
            ->orWhere('reviewed_by', $user->getKey());
    });
}

This is only an example.

Do not paste it blindly. Adapt the final implementation to the existing reporter, reviewer, editor and workflow rules already present in the project.

Do Not Change

Do not modify the meaning of:

Post::scopePublished()

Do not remove:

Role-based access

Ownership restrictions

Reviewer assignment restrictions

Post policies

Workflow permissions

Soft-delete handling

Editorial workflow logic

Do not expose every post to reporters, contributors or subscribers.

Do not hardcode user IDs or role IDs.

Do not solve this by removing:

ContentAccess::scopePosts()

from PostResource.

The correct fix must remain centralized in the authorization layer.

Query Verification

Use actual application queries to compare unrestricted and scoped results.

Use tests or temporary Tinker-equivalent logic such as:

$latest = Post::query()
    ->latest('id')
    ->first();

$scopedLatest = ContentAccess::scopePosts(
    Post::query(),
    $user
)->latest('id')->first();

Confirm:

The newest database record exists.

The record is not soft deleted.

The authenticated user's permissions are known.

The scoped query includes or excludes the record for a clear authorization reason.

After the fix, the appropriate user can see it.

Do not leave temporary debug output in production code.

Tests Required

Add or update automated tests for these scenarios.

Admin or Super Admin

A user with:

view all posts

can see:

Imported published posts

Newly created draft posts

Newly created published posts

Posts authored by other users

Posts with published_at = null

Reporter

A reporter can see:

Their own newly created draft

Their own submitted post

A reporter cannot see:

Another reporter’s private draft

Unassigned posts outside their permission scope

Reviewer or Editor

A reviewer or editor can see:

Posts assigned to them

Posts allowed by their existing workflow permissions

List Ordering

The newest post should appear before older posts by default.

Soft Deletes

Soft-deleted posts should remain excluded from the normal listing unless the existing resource explicitly supports trashed records.

Validation Commands

Run targeted tests first:

php artisan test --filter=ContentAccess
php artisan test --filter=PostResource
php artisan test --filter=ListPosts
php artisan test --filter=Post

Then run:

php artisan test
php artisan optimize:clear

Also run the project's formatter, if configured:

vendor/bin/pint --dirty

Do not install any new dependency.

Completion Report

After implementation, report:

Exact root cause.

The condition inside ContentAccess::scopePosts() that excluded the records.

Permissions and role involved.

Files modified.

Final query behaviour for:

Super admin or admin

Editor or reviewer

Reporter or contributor

Default sorting applied.

Tests added or updated.

Commands executed.

Test results.

Confirmation that frontend published() behaviour remains unchanged.

Do not only suggest code.

Inspect the actual project, implement the fix, test it, and provide the final completion report.

Execute Command for Codex

Read and execute this complete specification:

prompts/fix-filament-new-posts-list.md

Inspect the real project before changing code. Focus first on:

app/Support/Authorization/ContentAccess.php

and its:

scopePosts()

method.

Preserve all role-based restrictions, ensure users with view all posts can see every non-deleted post, ensure reporters and reviewers only see authorized records, add newest-first sorting to the Filament Posts table, run targeted tests, run the full test suite, run the formatter if configured, clear Laravel caches, and return the required completion report.

Do not stop after analysis. Implement and validate the fix.