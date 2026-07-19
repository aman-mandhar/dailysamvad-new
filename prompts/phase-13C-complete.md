# Daily Samvad Laravel Migration

## Phase 13C — Responsive Image Delivery, Layout Stability, Accessibility, and Performance

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

# 1. Dependency

Read Phase 13A and 13B implementation and reports. Preserve their model, storage, URL, metadata, and admin contracts.
# 2. Objective

- Audit all public image rendering locations.
- Add reliable intrinsic width/height where known.
- Implement responsive srcset and sizes only from verified existing derivatives.
- Define native lazy/eager loading and fetch-priority rules.
- Prevent layout shift and oversized downloads.
- Preserve accessibility, structured data, Open Graph image identity, and existing URLs.
- Prepare WebP/AVIF and CDN compatibility without unverified claims.
# 3. Public Image Audit

- Homepage hero and category cards.
- Breaking-news and latest-news sections.
- Article featured image and inline content.
- Related posts.
- Category, tag, author, search, and date archives.
- Sidebar cards and local advertisement images.
- Logo, error pages, feeds, sitemap image references, Open Graph, and JSON-LD.
- Document duplicate markup, missing dimensions, lazy-loading behavior, CSS aspect ratios, fallbacks, derivative files, and query loading.
# 4. Protected Boundaries

- Do not alter Phase 13A/B contracts.
- Do not rewrite imported post HTML in bulk.
- Do not change routes, slugs, canonical URLs, SEO architecture, or editorial workflow.
- Do not process images during public requests or in Blade.
- Do not add a JS lazy-loading library.
- Do not install an image package unless already present requirements make it unavoidable and approved by evidence.
- Do not delete originals or mass-convert in migrations.
# 5. Shared Rendering Contract

- Use existing Blade/component architecture.
- Accept source, alt, width, height, srcset, sizes, loading, decoding, fetch priority, classes, and fallback.
- Perform no queries and no filesystem scan.
- Escape all attributes.
- Handle null and remote URL states safely.
- Keep link semantics at the caller.
# 6. Intrinsic Dimensions and CLS

- Use stored dimensions when known.
- Do not probe files on every request.
- Do not invent dimensions.
- Use aspect-ratio boxes for design-specific card crops.
- Do not crop main article images like cards.
- Handle portrait, landscape, ultrawide, and missing images.
- Any metadata backfill must be explicit, chunked, resumable, and dry-run capable.
# 7. Derivative Strategy

- Audit reusable WordPress-generated sizes first.
- Create only layout-driven sizes if needed.
- Preserve originals.
- Do not upscale by default.
- Generate outside public requests via command/job.
- Make generation idempotent and chunked.
- Use deterministic paths and atomic writes.
- Record corrupt/unsupported failures without aborting entire batch.
- If processing support is insufficient, implement readiness only and report it.
# 8. srcset and sizes

- Include only existing verified candidates.
- Use correct width descriptors.
- Do not duplicate widths or label full-size files incorrectly.
- Use layout-specific sizes.
- Remote images without derivatives use plain src.
- Missing candidates are omitted safely.
- Parse and test resulting markup.
# 9. Loading Policy

- Primary above-the-fold/LCP image must not be lazy.
- Below-fold cards and thumbnails use native loading=lazy.
- Use fetchpriority=high only for one verified critical image.
- Use decoding=async where appropriate.
- Do not mark several images high priority.
- Document page-specific decisions.
# 10. Article and Inline Images

- Featured image uses meaningful alt, dimensions, stable container, caption/credit semantics, and responsive source.
- Preserve structured-data image references.
- Do not duplicate the featured image.
- Do not use regex-only rewriting for complex post HTML.
- Any render-time HTML enhancement must be safe, parser-based, tested, and non-destructive.
- Otherwise document inline-image limitations for a future migration task.
# 11. Cards, Archives, and Sidebar

- Use a consistent component-specific aspect ratio.
- Use object-fit only for intended crops.
- Load appropriately sized candidates.
- Use meaningful alt fallback based on documented metadata/article context.
- No filename alt.
- No N+1 and no per-card Storage::exists.
# 12. Accessibility

- Meaningful alt for informative images.
- Empty alt for decorative images.
- Do not use credit or title attribute as alt substitute.
- Captions and credits must wrap and remain readable.
- Images inside links need accessible link purpose.
- No duplicate IDs or nested anchors.
- Maintain focus, contrast, zoom, and reflow.
# 13. WebP, AVIF, and CDN Readiness

- Keep JPEG/PNG fallback.
- Use picture only when real alternate files exist.
- Do not claim AVIF/WebP support without runtime and generated-file evidence.
- Do not add a CDN.
- Centralize host generation and avoid hardcoded hosts.
- Keep deterministic cacheable paths.
- Document future CDN integration points.
# 14. Performance and Caching

- No query per image.
- No filesystem existence loop.
- No image probing in Blade.
- No derivative generation during rendering.
- Preserve query-count tests.
- Do not add Redis dependency.
- Do not cache missing-file state indefinitely.
- Report code-level verification separately from measured production performance.
# 15. Responsive Browser Verification

- Verify 1440, 1280, 1024, 768, 430, 390, and 375 px.
- Check overflow, distortion, reserved height, crop, portrait behavior, captions, credits, missing-image fallback, and selected network candidate.
- If browser tooling is unavailable, do not claim visual or network verification.
# 16. SEO Compatibility Boundary

- Do not implement Phase 14.
- Preserve canonical, Open Graph, Twitter, JSON-LD, feed, and sitemap behavior.
- Do not silently change the primary SEO image identity.
- Report every SEO-visible markup change.
# 17. Required Tests

- Valid src and srcset.
- Only existing candidates.
- sizes accompanies srcset.
- Remote image degrades to src.
- Known dimensions render.
- Missing dimensions remain safe.
- Hero is not lazy.
- Below-fold card is lazy.
- Only intended image is high priority.
- Alt and metadata are escaped.
- Decorative alt is empty.
- No component query.
- No N+1 on homepage/archive/article.
- Structured data and Open Graph remain valid.
- Fallback works.
- Unicode filenames work.
- Derivative command idempotent/dry-run if implemented.
# 18. Validation Commands

```powershell
git status --short
php artisan optimize:clear
php artisan view:cache
php artisan test --filter=ResponsiveImage
php artisan test --filter=Media
php artisan test --filter=Homepage
php artisan test --filter=Archive
php artisan test --filter=Article
php artisan test --filter=StructuredData
php artisan test
vendor\bin\pint --test
npm.cmd run build
git status --short
git diff --stat
git diff --check
```
# 19. Completion Criteria

- Every public image location audited.
- Dimensions and layout stability improved safely.
- Loading priority is correct.
- srcset uses real candidates only.
- Originals preserved.
- No request-time processing.
- No query or filesystem regression.
- Accessibility and structured data preserved.
- Focused/full tests, Pint, and build pass.
- Browser limitations reported.
- No Phase 14 work started.
# 20. Required Completion Report

- 1. Phase 13C Summary
- 2. Initial Public Image Audit
- 3. Shared Image Rendering Architecture
- 4. Width and Height Handling
- 5. Derivative Strategy
- 6. WordPress Generated-Size Reuse
- 7. srcset and sizes
- 8. Lazy Loading and Fetch Priority
- 9. Homepage Images
- 10. Archive and Sidebar Images
- 11. Article Images
- 12. Inline Content Findings
- 13. Accessibility
- 14. WebP and AVIF Readiness
- 15. CDN Compatibility
- 16. Cache Boundaries
- 17. Query and Performance Impact
- 18. CSS and Responsive Verification
- 19. SEO and Structured Data Impact
- 20. Database Migrations
- 21. Files Created
- 22. Files Modified
- 23. Validation Results
- 24. Test Results
- 25. Build Results
- 26. Browser and Network Verification
- 27. Git Status
- 28. Remaining Risks
- 29. Deferred Work
- 30. Ready for Phase 13D
# 21. Final Requirement

Improve delivery using verified architecture, return the exact report, and stop before Phase 13D.
