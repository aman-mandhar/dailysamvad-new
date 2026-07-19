# Daily Samvad Laravel Migration

## Phase 13B — Media Library, Upload Workflow, Featured Images, Galleries, and Admin UX

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

# 1. Dependency on Phase 13A

Read the Phase 13A implementation, documentation, tests, and completion report before editing. Use its canonical model, path, disk, URL, WordPress identity, and deletion contracts.
# 2. Objective

- Implement a scalable Filament media-library workflow.
- Implement secure image upload.
- Support featured-image selection, upload, replacement, detachment, and preview.
- Support metadata: alt, caption, credit, copyright/source where justified.
- Support gallery assignment only if architecture and current product requirements justify it.
- Implement authorization, duplicate detection, missing-file visibility, and non-destructive orphan reporting.
- Do not implement responsive derivatives, srcset, WebP/AVIF conversion, CDN, AI media, or Phase 14 SEO.
# 3. Mandatory Audit

- Inspect all Phase 13A files and docs.
- Inspect existing Filament post forms, resources, policies, and image fields.
- Confirm whether media is reusable or post-owned.
- Confirm current permissions and validation.
- Confirm existing delete/observer behavior.
- Confirm whether galleries already exist.
- Confirm whether imported media is editable.
- Confirm admin selector performance with thousands of records.
# 4. Protected Boundaries

- Do not redesign Phase 13A.
- Do not alter public routes, canonical URLs, structured data, editorial workflow, authors, tags, categories, or imported HTML.
- Do not preload the entire media table.
- Do not install a media package.
- Do not fetch arbitrary remote URLs.
- Do not delete referenced files.
- Do not silently rewrite imported records.
- Do not commit or push.
# 5. Media Library Resource

- Create or complete a Filament resource only when Phase 13A architecture supports a media entity.
- Show thumbnail, filename/title, alt, MIME, dimensions, size, source, WordPress ID, uploader, created date, reference count, and missing state where fields exist.
- Use server-side pagination.
- Use searchable and sortable safe columns.
- Use filters for type, date, origin, missing status, reference status, and soft-deleted status where supported.
- Eager-load counts and avoid per-row filesystem scans.
- Keep admin layout accessible and responsive.
# 6. Upload Workflow

- Authorized users only.
- Use configured Laravel disk.
- Generate collision-resistant safe stored filenames.
- Preserve original filename as metadata when supported.
- Detect real MIME; do not trust extension.
- Reject executable, invalid, empty, corrupt, oversized, or disallowed files.
- Prevent traversal and overwrite.
- Clean newly stored file if DB creation fails.
- Create no DB record if storage write fails.
- Do not store absolute server paths.
# 7. Formats and Limits

- Audit actual PHP/image support.
- At minimum consider JPEG, PNG, GIF, and WebP.
- Reject SVG by default unless safe sanitization already exists and is tested.
- Accept AVIF only if runtime support is proven.
- Use configuration-backed file-size and dimension limits where useful.
- Do not reject valid historical images with arbitrary dimensions.
- Provide clear validation messages.
# 8. Filename and Directory Strategy

- Follow Phase 13A.
- Do not use raw user filename as storage path.
- Do not make path depend on post slug or metadata.
- Metadata edits must not rename binaries.
- Do not bulk rename WordPress files.
- Paths must remain stable across deployments and backups.
# 9. Duplicate Detection

- Use checksum plus supporting metadata when justified.
- Do not rely on filename alone.
- Calculate checksum only on upload/import/audit, never normal render.
- Warn or offer reuse; do not automatically merge or delete.
- Keep repeated WordPress imports idempotent.
- Chunk any checksum backfill and provide dry-run.
# 10. Featured Image Workflow

- Select existing media without preloading all rows.
- Allow safe upload from the post workflow where appropriate.
- Preview current image.
- Allow detach separately from delete.
- Allow replacement only with explicit confirmation and policy checks.
- Preserve existing path-based posts through backward compatibility.
- Prevent selection of invalid media types.
- Avoid stale hidden IDs and N+1 in post tables.
# 11. Gallery Workflow

- Implement only if an existing gallery relationship or verified requirement exists.
- Support ordered attachments, reuse, upload, reorder, and detach.
- Detaching must not delete shared binaries.
- Prevent duplicate attachment where not intended.
- Define whether metadata is inherited or overridden.
- Do not redesign article content rendering.
# 12. Metadata

- Support Unicode Hindi, Punjabi, and English.
- Do not copy filename into alt text.
- Do not blindly copy title into alt text.
- Allow empty alt only for intentionally decorative usage.
- Escape metadata in public/admin HTML.
- Define whether alt/caption/credit are global or per usage.
- Preserve imported values.
- Enforce reasonable lengths.
# 13. Replace File

- Explicit authorized action and confirmation.
- Validate new file fully.
- Preserve media identity and references.
- Write new file safely, switch reference atomically where practical, then remove old binary only when safe.
- Preserve metadata unless edited.
- Test storage failure and database failure.
- Never silently replace an imported original.
# 14. Delete, Restore, and Orphans

- Deny hard delete while referenced.
- Show reference count.
- Distinguish detach from delete.
- Support soft delete/restore only if architecture supports it.
- Do not claim an inline-HTML-referenced file is certainly orphaned.
- Provide dry-run, chunked orphan candidate reporting.
- Do not auto-delete or bulk-delete uncertain files.
# 15. Authorization

- Use existing roles and permissions.
- Enforce view, upload, edit metadata, assign, replace, delete, restore, and force-delete capabilities as supported.
- Apply policies server-side and align UI visibility.
- Do not invent role names without evidence.
# 16. WordPress Import Compatibility

- Reuse existing old_wp_id mapping.
- Preserve original path, alt, caption, credit, MIME, and dimensions where available.
- Do not copy the same binary repeatedly.
- Report missing source files.
- Keep import repeatable.
- Use fixtures or isolated data; do not rerun destructive full imports.
# 17. Factories and Tests

- Use Storage::fake or equivalent.
- Do not write large real files.
- Test valid uploads and invalid MIME.
- Test executable disguised as image.
- Test size limits, filename safety, Unicode metadata, DB/storage failure cleanup, duplicate detection, featured selection/replacement/detach, reference-safe deletion, permissions, missing-file admin state, selector pagination, no N+1, import idempotency, gallery ordering if implemented, and orphan dry-run.
# 18. Validation Commands

```powershell
git status --short
php artisan migrate:status
php artisan optimize:clear
php artisan test --filter=Media
php artisan test --filter=Upload
php artisan test --filter=Featured
php artisan test --filter=Gallery
php artisan test --filter=Authorization
php artisan test --filter=Import
php artisan test
vendor\bin\pint --test
npm.cmd run build
git status --short
git diff --stat
git diff --check
```
# 19. Completion Criteria

- Phase 13A architecture preserved.
- Media administration scales.
- Uploads are secure and transactional.
- Featured-image workflow is safe.
- Metadata is editable and escaped.
- Delete/detach/replace semantics are clear.
- Referenced media is protected.
- Duplicate and orphan behavior is non-destructive.
- Authorization is enforced.
- WordPress imports remain idempotent.
- Focused/full tests, Pint, and build pass.
- No Phase 13C work started.
# 20. Required Completion Report

- 1. Phase 13B Summary
- 2. Phase 13A Architecture Used
- 3. Initial Admin and Upload Audit
- 4. Media Library Resource
- 5. Upload Workflow
- 6. Supported Formats and Limits
- 7. Filename and Directory Strategy
- 8. Duplicate Detection
- 9. Featured Image Workflow
- 10. Gallery Workflow
- 11. Metadata Support
- 12. File Replacement
- 13. Delete, Restore, and Detach Rules
- 14. Orphan Detection
- 15. Authorization
- 16. WordPress Import Compatibility
- 17. Query and Performance Impact
- 18. Database Migrations
- 19. Files Created
- 20. Files Modified
- 21. Validation Results
- 22. Test Results
- 23. Build Results
- 24. Browser/Admin Verification
- 25. Git Status
- 26. Remaining Risks
- 27. Deferred Work
- 28. Ready for Phase 13C
# 21. Final Requirement

Implement the editorial workflow using Phase 13A, return the exact report, and stop before Phase 13C.
