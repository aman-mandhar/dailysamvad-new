# Phase 12 — Tags System

## Project

Daily Samvad WordPress-to-Laravel migration.

Local Laravel project:

`F:\MYWEB\laragon\www\dailysamvad-new`

Current WordPress reference:

https://dailysamvad.com

Laravel target:

https://dailysamvad.in

---

# Confirmed Roadmap Position

This is:

**Phase 12 — Tags System**

It is **NOT**:

- Media Library
- SEO
- Google News
- Discover
- Performance
- Security
- Deployment

Those belong to later phases.

---

# Current Project Status

Phases completed:

- Phase 1
- Phase 2
- Phase 3
- Phase 4
- Phase 5
- Phase 6
- Phase 7
- Phase 8
- Phase 9A
- Phase 9B
- Phase 9C
- Phase 9D
- Phase 10
- Phase 11

Existing application already includes:

- Homepage
- Article pages
- Category archives
- Tag archives
- Author archives
- Search
- Date archives
- Shared archive architecture
- Shared archive DTO
- Shared archive query
- Responsive archive UI
- Existing tag relationships
- Existing WordPress imported tags
- Existing Filament administration
- Existing tests
- Existing publication scopes

Do **NOT** rebuild the tag archive.

---

# Phase Objective

Complete a production-quality Tags System that supports:

- WordPress compatibility
- tag management
- public archives
- post assignment
- slug normalization
- duplicate prevention
- efficient querying
- import compatibility
- validation
- authorization
- SEO readiness
- testing

The existing architecture must be reused wherever possible.

---

# Core Principle

Audit first.

Never assume a missing Tags System.

Inspect the repository first.

Only implement what is genuinely missing.

---

# Mandatory Initial Audit

Inspect and document:

- Tag model
- Post model
- many-to-many relationship
- pivot table
- migrations
- factories
- seeders
- importer
- archive route
- archive controller
- archive DTO
- archive query
- shared components
- Filament resources
- validation
- tests
- policies
- eager loading
- existing slug generation
- existing tag ordering
- existing tag URLs
- existing canonical behaviour

Search repository for:

```
tag
tags
post_tag
tag_id
slug
taxonomy
```

Do not modify code before completing the audit.

---

# Protected Boundaries

Do not change:

- article URLs
- category URLs
- author URLs
- existing tag URLs
- archive architecture
- shared DTOs
- shared archive queries
- publication scopes
- responsive design
- SEO architecture
- structured-data architecture
- advertisement system
- authentication
- Filament architecture
- migrated posts
- migrated WordPress IDs

Do not introduce:

- duplicate Tag model
- duplicate archive
- React
- Vue
- new CSS framework
- unnecessary packages

---

# Section 1 — Tag Entity

Determine whether the existing Tag model is sufficient.

Do not create another tag table.

Reuse the current architecture.

---

# Section 2 — Database Review

Audit:

- tags table
- pivot table
- indexes
- foreign keys
- unique constraints
- slug uniqueness

Add migrations only when absolutely necessary.

Existing data must remain valid.

---

# Section 3 — WordPress Compatibility

Verify:

- imported tags
- imported slugs
- imported IDs
- imported relationships

Re-running imports must not duplicate tags.

Posts must retain tag assignments.

---

# Section 4 — Tag Slug Strategy

Audit current slug generation.

Requirements:

- deterministic
- unique
- Unicode safe
- collision handling
- preserve existing imported slugs

Do not regenerate existing slugs unnecessarily.

---

# Section 5 — Tag Assignment

Verify:

- create
- edit
- remove
- synchronize

Requirements:

- invalid IDs rejected
- duplicates prevented
- authorization enforced
- eager loading retained

---

# Section 6 — Duplicate Prevention

Prevent:

- duplicate names
- duplicate slugs
- duplicate pivot entries

Provide deterministic behaviour.

---

# Section 7 — Public Tag Archive

Reuse existing archive implementation.

Do not create:

- new controller
- new route
- duplicate Blade views

Verify:

- pagination
- ordering
- sidebar
- advertisements
- canonical
- responsive behaviour

---

# Section 8 — Empty States

Handle:

- no posts
- deleted tags
- hidden tags
- invalid slug
- Unicode names
- long names

No 500 errors.

---

# Section 9 — Filament Tag Management

Audit existing Tag Resource.

Enhance only where needed.

Support:

- create
- edit
- delete
- validation
- slug preview
- search
- filters

Do not redesign Filament.

---

# Section 10 — Import Compatibility

Audit importer.

Verify:

- idempotency
- mapping
- slug reuse
- duplicate handling

No production imports.

---

# Section 11 — Query Performance

Avoid:

- N+1
- duplicate joins
- loading all tags
- unnecessary eager loading

Reuse existing query architecture.

---

# Section 12 — Accessibility

Verify:

- semantic headings
- keyboard navigation
- focus
- wrapping
- readable tags

---

# Section 13 — Responsive Behaviour

Verify:

- desktop
- tablet
- mobile

No overflow.

No layout shifts.

---

# Section 14 — SEO Readiness

Phase 14 owns SEO.

Only ensure:

- existing metadata receives correct tag data
- canonical compatibility remains
- no regressions

Do not rewrite SEO.

---

# Section 15 — Structured Data Compatibility

Verify tag information integrates with existing structured data.

Do not redesign schema.

---

# Section 16 — Security

Prevent:

- XSS
- invalid slugs
- invalid IDs
- mass assignment
- unauthorized editing

Escape output.

Validate input.

---

# Section 17 — Factories & Seeders

Update only if required.

Maintain deterministic tests.

---

# Section 18 — Testing

Add focused tests.

Cover:

## Model

- relationship
- pivot
- uniqueness

## Archive

- HTTP 200
- pagination
- ordering
- empty state

## Assignment

- create
- update
- remove

## Validation

- duplicate slug
- duplicate name
- invalid IDs

## Import

- idempotency
- duplicate prevention

## Security

- authorization
- escaping

## Performance

- N+1
- query count

Do not weaken existing tests.

---

# Validation Commands

Run:

```bash
php artisan optimize:clear
```

```bash
php artisan migrate:status
```

```bash
php artisan test --filter=Tag
```

```bash
php artisan test
```

```bash
vendor/bin/pint --test
```

```bash
npm run build
```

```bash
php artisan route:cache
```

```bash
php artisan view:cache
```

```bash
php -l
```

Attempt:

```bash
git status --short
git diff --stat
git diff --check
```

If Git ownership blocks execution:

- report exact error
- do not modify global Git configuration

---

# Browser Verification

Where available verify:

- archive
- pagination
- long names
- empty state
- responsive layout

Do not claim verification without browser testing.

---

# Files That May Be Modified

Only modify files justified by the audit.

Possible:

- Tag model
- Post model
- pivot migration
- Filament Tag Resource
- archive query
- importer
- factories
- tests

Do not create duplicate implementations.

---

# Completion Report

Return:

1. Phase 12 Summary
2. Initial Audit
3. Existing Architecture Reused
4. Database Review
5. WordPress Compatibility
6. Slug Strategy
7. Assignment System
8. Public Archive
9. Duplicate Prevention
10. Filament Management
11. Performance
12. Security
13. Accessibility
14. Responsive Verification
15. Files Created
16. Files Modified
17. Database Migrations
18. Validation Commands
19. Test Results
20. Build Results
21. Browser Verification
22. Git Status
23. Deferred Work
24. Remaining Risks
25. Ready for Phase 13

---

# Completion Criteria

Phase 12 is complete only when:

- existing architecture reused
- no duplicate tag system
- WordPress compatibility preserved
- duplicate tags prevented
- assignment validated
- public archives preserved
- eager loading retained
- no N+1 regressions
- tests pass
- Pint passes
- build passes
- caches pass
- no deployment work performed

---

# Deferred Work

Do NOT implement:

## Phase 13

Media Library Improvements

## Phase 14

SEO

## Phase 15

Google News

## Phase 16

Discover

## Phase 17

Performance

## Phase 18

Security Hardening

## Phase 19

Admin Dashboard

## Phase 20

Reporter Workflow

## Phase 21+

AI Features

## Phase 29

Production Deployment

---

# Restrictions

Do not:

- deploy
- change production
- modify WordPress
- regenerate imported slugs
- redesign archive system
- replace relationships
- install unnecessary packages
- weaken tests
- expose private data
- modify global Git configuration
- commit
- push
- merge
- rebase
- checkout
- reset

Begin with the repository audit.

Reuse the existing Tags System wherever possible and implement only the missing functionality supported by evidence.