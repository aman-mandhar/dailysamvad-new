# Daily Samvad Laravel Migration

## Phase 13D — Media Integrity Audit, Regression Testing, Documentation, and Final Validation

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

- Validate all Phase 13 architecture and implementation.
- Run integrity, missing-file, duplicate-candidate, orphan-candidate, authorization, migration, performance, browser, and regression checks.
- Fix only verified Phase 13 defects.
- Complete documentation and return the final Phase 13 report.
- Do not implement Phase 14.
# 2. Dependencies

- Read Phase 13A, 13B, and 13C specifications and completion reports.
- Inspect docs/MEDIA-ARCHITECTURE.md and any media operations docs.
- Inspect all Phase 13 migrations, tests, services, policies, resources, components, commands, and Git diff.
- Confirm implementation matches approved contracts.
# 3. Protected Boundaries

- No architecture redesign.
- No URL rewrite.
- No imported HTML rewrite.
- No automatic duplicate merge or orphan deletion.
- No mass conversion.
- No SEO redesign.
- No package installation.
- No production deployment or destructive sync.
- No commit/push/reset/clean/stash/merge/rebase/global Git config.
# 4. Media Integrity Audit

- Provide or verify a read-only/dry-run chunked audit command.
- Audit DB records, referenced files, missing originals, missing derivatives, files without records, invalid paths, traversal-like paths, duplicate WordPress IDs, zero-byte files, unsupported MIME metadata, missing dimensions, soft-deleted references, gallery references, featured references, and inline-HTML uncertainty.
- Use bounded memory and progress output.
- Produce summary counts and defined exit behavior.
- Write reports outside public web root.
- Do not expose secrets or full server paths.
# 5. Reconciliation and WordPress Sample

- Verify representative imported and editor-uploaded media.
- Include multiple years/categories where source data is available.
- Compare attachment ID, featured association, path, URL, alt, caption, MIME, dimensions, and filename.
- State sample size.
- Do not claim full parity from a sample.
- Do not mutate real editorial records for testing.
# 6. Broken Reference Verification

- DB record with missing file.
- Derivative missing.
- Original missing but derivative exists.
- Malformed remote URL.
- Deleted media still assigned.
- Missing gallery item.
- Missing fallback.
- Public page must not throw or expose paths.
- Admin must surface the issue.
- Logging must not storm.
# 7. Duplicate and Orphan Reporting

- Distinguish exact binary duplicate, WordPress identity conflict, filename-only match, dimensions/size match, and derivative relationship.
- Distinguish zero-known-reference record, storage file without record, possible inline-HTML use, recent temporary upload, soft-deleted-only reference, and derivative without original.
- Do not automatically delete or merge.
# 8. Authorization Regression

- Verify list, view, upload, edit metadata, assign, attach, detach, replace, delete, restore, force-delete, and audit access as supported.
- Verify UI action visibility and server policy agree.
- Test unauthorized direct requests/actions.
# 9. Storage Failure Tests

- Upload write failure.
- DB failure after storage write.
- Replacement write failure.
- Deletion failure.
- Missing derivative source.
- Corrupt image.
- Permission-like storage exception.
- Partial batch failure.
- Verify consistent DB and storage outcomes.
# 10. Migration Verification

- Fresh test migrations pass.
- Rollback test safely where supported.
- No filesystem operation in migrations.
- No destructive rewrite.
- Indexes and constraints verified.
- Existing-row compatibility verified.
- Do not rollback the user's real database.
# 11. Query and Performance Verification

- Media library query count.
- Selector pagination and search.
- Homepage, archive, and article query counts.
- No per-image query.
- No per-card filesystem probe.
- No full media preload.
- Audit command uses chunks and bounded memory.
- Do not fabricate production timings.
# 12. Test Coverage

- Unit path/URL tests.
- Public rendering feature tests.
- Filament/admin tests.
- Policy tests.
- Upload validation and security tests.
- Import idempotency tests.
- Deletion/reference tests.
- Responsive markup tests.
- Derivative tests if implemented.
- Audit command tests.
- Migration tests.
- N+1/query-count tests.
- Missing-file and Unicode tests.
# 13. Browser Verification

- Admin: list, search, filters, upload, errors, edit, assign, replace, delete denial, responsive layout.
- Public: homepage, article, category, tag, author, search, missing image, portrait image, caption/credit, responsive candidate, console, 404, mixed content.
- Widths: 1440, 1280, 1024, 768, 430, 390, 375.
- If unavailable, list exact manual checks and do not claim completion.
# 14. Documentation

- Finalize architecture, upload rules, permissions, formats, limits, featured images, galleries, metadata, replacement, deletion, duplicate/orphan audit, responsive images, derivatives, WebP/AVIF status, CDN readiness, backups, storage link, execution protection, troubleshooting, audit command, and known limitations.
- Update docs/MEDIA-ARCHITECTURE.md.
- Add docs/MEDIA-OPERATIONS.md only when it reduces complexity rather than duplicating content.
# 15. Validation Commands

```powershell
git status --short
php artisan about
php artisan migrate:status
php artisan optimize:clear
php artisan test --filter=Media
php artisan test --filter=Image
php artisan test --filter=Upload
php artisan test --filter=Featured
php artisan test --filter=Gallery
php artisan test --filter=Import
php artisan test --filter=Authorization
php artisan test --filter=ResponsiveImage
php artisan test --filter=Audit
php artisan test --filter=Migration
php artisan test
vendor\bin\pint --test
npm.cmd run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
git status --short
git diff --stat
git diff --check
```
# 16. Phase 13 Completion Criteria

- 13A architecture verified.
- 13B admin and upload workflow verified.
- 13C delivery and layout stability verified.
- WordPress compatibility preserved.
- Existing featured images remain valid.
- Uploads reject executable/traversal/invalid files.
- Assignments and destructive actions are authorized.
- Referenced media protected.
- Duplicate/orphan handling non-destructive.
- Responsive markup valid.
- Hero and lazy-loading policies correct.
- No N+1 or render-time filesystem scan.
- Audit tool safe and chunked.
- Documentation complete.
- Focused/full tests, Pint, Vite build, and Laravel cache commands pass.
- Git diff check passes or exact blocker reported.
- Browser verification completed or limitations explicit.
- No Phase 14 implementation.
# 17. Required Completion Report

- 1. Phase 13D Summary
- 2. Phase 13 Architecture Verification
- 3. Phase 13A Verification
- 4. Phase 13B Verification
- 5. Phase 13C Verification
- 6. Media Integrity Audit
- 7. WordPress Parity Sample
- 8. Missing File Findings
- 9. Broken Reference Findings
- 10. Duplicate Candidate Findings
- 11. Orphan Candidate Findings
- 12. Authorization Verification
- 13. Upload and Storage Failure Verification
- 14. Migration Verification
- 15. Query and Performance Verification
- 16. Accessibility Verification
- 17. Responsive Verification
- 18. Browser Verification
- 19. Documentation
- 20. Database Migrations
- 21. Files Created
- 22. Files Modified
- 23. Validation Commands and Results
- 24. Focused Test Results
- 25. Full Test Results
- 26. Pint Results
- 27. Build Results
- 28. Cache Results
- 29. Git Status
- 30. Remaining Risks
- 31. Deferred Work
- 32. Phase 13 Completion Checklist
- 33. Ready for Phase 14
# 18. Final Requirement

Validate and finish Phase 13, perform no destructive cleanup, return the exact report, and stop before Phase 14.
