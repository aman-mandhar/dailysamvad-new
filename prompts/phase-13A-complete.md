# Daily Samvad Laravel Migration

## Phase 13A — Media Architecture Audit, Storage Contract, and WordPress Compatibility

### Project

```text
F:\MYWEB\laragon\www\dailysamvad-new
```

### Reference Website

```text
https://dailysamvad.com
```

### Current Status

- Phase 0–12 complete.
- Public frontend, articles, archives, search, authors, tags, advertisements, structured data, and production-readiness work already exist.
- WordPress content and a large local media set already exist.
- Phase 13 is split into 13A, 13B, 13C, and 13D.
- Audit the repository before changing architecture.
- Do not continue to the next sub-phase automatically.

---

# 1. Objective

Establish the verified media architecture before building a media-library UI or responsive-image pipeline. Reuse existing code wherever possible and make only evidence-based, backward-compatible changes.

- Preserve all existing WordPress and Laravel image URLs.
- Preserve current featured-image behavior.
- Prevent duplicate media architecture.
- Define canonical storage, path, URL, deletion, and import rules.
- Support future Phase 13B, 13C, SEO, Google News, Discover, and AI-media phases.
- Do not build the full admin media library in 13A.
- Do not generate responsive derivatives in 13A.
# 2. Mandatory Repository Audit

Before modifying files, inspect all media-related implementation and report verified findings.

- Inspect app/Models, Observers, Policies, Services, Support, Queries, DTOs, Filament, Commands, Jobs, Controllers, Requests.
- Inspect database/migrations, factories, seeders, tests, docs, composer.json, package.json.
- Inspect config/filesystems.php, .env.example, storage/app/public, public/storage, public and storage .htaccess files.
- Search for image, image_path, featured_image, thumbnail, attachment, media, gallery, wp-content, old_wp_id, old_url, disk, storage, caption, alt, credit, checksum, cleanup.
- Identify every model and field that stores a path, URL, ID, JSON value, or relationship.
- Identify every file-deletion path and every public URL-generation path.
- Identify existing Filament upload fields and media selectors.
- Identify existing import behavior for _thumbnail_id, _wp_attached_file, attachment metadata, and WordPress upload directories.
- Measure media count and total size only when safely available; never guess.
# 3. Required Audit Questions

- What owns the featured image today?
- Is there an Image, Media, Attachment, or polymorphic model already?
- Are values relative paths, absolute paths, full URLs, media IDs, or mixed legacy formats?
- Which disk is used and how is its URL generated?
- Does public/storage exist and point to the expected directory?
- How are missing images handled?
- How are imported WordPress URLs and IDs preserved?
- Are alt, caption, credit, MIME, size, width, and height stored?
- Does any observer delete files?
- Can a file be shared by more than one post?
- Are soft deletes involved?
- Are there N+1 risks?
- Is a third-party media package installed?
- Are uploads protected from executable files and traversal?
# 4. Architecture Decision Rule

Prefer the current architecture. A new Media model is allowed only when the audit proves the current implementation cannot safely satisfy later requirements.

- Do not introduce a new entity merely for stylistic preference.
- Reuse an existing Image/Attachment model when it can be extended safely.
- If a new entity is required, provide a backward-compatible migration path.
- Existing posts must continue rendering during and after rollout.
- Document chosen architecture and rejected alternatives.
# 5. Protected Boundaries

- Do not change publication scopes, editorial workflow, authors, tags, categories, archive routes, article routes, canonical URLs, structured data, advertisements, homepage queries, or authentication.
- Do not modify the WordPress source database or imported post HTML.
- Do not rename, move, re-encode, or delete the existing uploads tree.
- Do not hotlink WordPress media.
- Do not install packages.
- Do not add Livewire to the public frontend.
- Do not add cloud storage or a CDN.
- Do not run destructive cleanup.
- Do not commit, push, reset, clean, stash, merge, rebase, or alter global Git configuration.
# 6. Canonical Media Contract

Define one documented contract for all future media work.

- Canonical database value format.
- Canonical disk name and configuration source.
- Canonical local public URL resolution.
- Rules for valid external URLs.
- Rules for legacy WordPress /wp-content/uploads URLs.
- Rules for missing files and fallbacks.
- Rules for featured images and reusable images.
- Rules for galleries if already present.
- Rules for deletion, detachment, replacement, soft deletion, and restoration.
- Rules for metadata ownership: global media metadata versus per-usage metadata.
- Rules for import identity and idempotency.
# 7. WordPress Compatibility

- Audit attachment post IDs, _thumbnail_id, _wp_attached_file, attachment metadata, generated sizes, captions, alt metadata, MIME, dimensions, uploader, and year/month folders.
- Preserve old_wp_id and original URL evidence when available.
- Repeated imports must not create duplicate records.
- Prefer WordPress attachment ID for stable matching.
- Do not match unrelated files by filename alone.
- Preserve existing directory structure.
- Do not rewrite post HTML in this phase.
- Document unsupported edge cases.
# 8. Filesystem and Path Rules

- Use Laravel Storage APIs for new operations.
- Do not store absolute Windows or Linux server paths.
- Keep disk selection configurable.
- Support Laragon locally and HTTPS VPS deployment.
- Reject path traversal and executable files.
- Avoid duplicate /storage/storage prefixes and duplicate slashes.
- Handle Unicode filenames and URL encoding safely.
- Do not convert valid remote URLs into local paths.
- Do not expose filesystem paths in output or logs.
# 9. Path Normalizer and URL Resolver

Create or consolidate focused reusable components only if equivalent code does not already exist.

- Accept existing legacy forms safely.
- Return a safe public URL or null.
- Perform no database query.
- Avoid per-card filesystem checks.
- Do not throw on malformed legacy values.
- Unit-test local paths, remote URLs, Unicode, Windows separators, query strings, duplicate slashes, and traversal attempts.
# 10. Database Rules

- Inspect schema before creating migrations.
- Add only fields justified by the architecture.
- Any migration must be additive, reversible, and safe for existing rows.
- Do not perform filesystem work or large backfills inside migrations.
- Use explicit chunked Artisan commands for backfills.
- Any repair/backfill command must support dry-run.
- Indexes must be justified by real lookup or uniqueness requirements.
# 11. Reference and Deletion Safety

- Prevent binary deletion while a known reference exists.
- Define behavior for post deletion, soft deletion, restoration, image replacement, and gallery detachment.
- Do not assume an image is orphaned when it may be referenced inside rich HTML.
- Do not cascade-delete a shared file because one post is removed.
- Use eager loading and avoid N+1.
# 12. Missing File Behavior

- Public pages must degrade to a safe fallback or omit the image without broken markup.
- Admin must show missing-file status without silently deleting metadata.
- Import must report missing source files and continue according to existing import policy.
- Do not call Storage::exists for every image on normal list pages.
- Do not create logging storms.
# 13. Security

- No remote URL fetcher in 13A.
- Reject traversal and executable uploads.
- Decide SVG policy explicitly; reject by default unless sanitization already exists.
- Escape user-facing metadata.
- Do not trust extensions alone.
- No public Artisan routes or debug helpers.
- Verify storage execution protection in code/docs; do not claim server enforcement unless tested.
# 14. Performance

- No recursive scan during web requests.
- No media-table preload.
- No image probing during Blade rendering.
- No checksum calculation during rendering.
- Chunk all audits/backfills.
- Keep binary reads out of listing queries.
- Report measured counts only when actually measured.
# 15. Documentation

Create or update docs/MEDIA-ARCHITECTURE.md.

- Document models, fields, disk, paths, URL resolution, WordPress mapping, deletion, replacement, missing-file behavior, security, performance, backup, deployment, and Phase 13B/13C extension points.
# 16. Required Tests

- Existing featured images still render.
- Local path resolves correctly.
- Allowed remote URL remains unchanged.
- Malformed path fails safely.
- Traversal is rejected.
- Unicode filename is safe.
- Duplicate slash normalization is correct.
- Missing media degrades safely.
- WordPress identity mapping is idempotent.
- Repeated mapping creates no duplicate.
- Shared/reference deletion rules are safe.
- Resolver makes no database query.
- Homepage, archive, and article media relationships do not create N+1.
- Existing media tests remain green.
# 17. Validation Commands

```powershell
git status --short
php artisan about
php artisan migrate:status
php artisan optimize:clear
php artisan test --filter=Media
php artisan test --filter=Image
php artisan test --filter=Featured
php artisan test --filter=Import
php artisan test
vendor\bin\pint --test
npm.cmd run build
git status --short
git diff --stat
git diff --check
```
# 18. Validation Honesty

- Run php -l for every modified PHP file.
- Do not claim a command passed unless it passed.
- Report unavailable commands and exact errors.
- If Git reports dubious ownership, do not change global safe.directory; report it.
# 19. Completion Criteria

- Audit completed and documented.
- Existing architecture reused or replacement fully justified.
- Canonical media contract established.
- WordPress compatibility preserved.
- Storage and URL rules centralized or verified.
- Missing-file and deletion behavior defined.
- Security and scale constraints satisfied.
- Safe migrations/backfills tested if created.
- Focused tests, full suite, Pint, and Vite build pass.
- Git diff check passes or exact blocker is reported.
- No Phase 13B or 13C work started.
# 20. Required Completion Report

- 1. Phase 13A Summary
- 2. Initial Repository Audit
- 3. Existing Media Models and Fields
- 4. Existing Featured-Image Architecture
- 5. WordPress Media Compatibility
- 6. Storage and Filesystem Findings
- 7. Media Volume Findings
- 8. Architecture Decision
- 9. Rejected Alternatives
- 10. Canonical Media Contract
- 11. Path Normalization
- 12. Public URL Resolution
- 13. Missing-File Behavior
- 14. Reference and Deletion Rules
- 15. Security Findings
- 16. Performance Findings
- 17. Database Migrations
- 18. Files Created
- 19. Files Modified
- 20. Documentation
- 21. Validation Results
- 22. Test Results
- 23. Build Results
- 24. Git Status
- 25. Remaining Risks
- 26. Deferred Work
- 27. Ready for Phase 13B
# 21. Final Requirement

Audit first, reuse existing architecture, make only minimal evidence-based changes, return the exact report, and stop before Phase 13B.
