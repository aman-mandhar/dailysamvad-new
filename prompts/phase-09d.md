# Daily Samvad Laravel Migration

## Phase 9D — Archive Pages Visual Parity and Responsive Polish

### Project

```text
F:\MYWEB\laragon\www\dailysamvad-new
```

### Reference Website

```text
https://dailysamvad.com
```

### Current Status

- Phase 0–8 complete
- Phase 8 global breaking-news ticker hotfix complete
- Phase 9A static-page visual fixes complete
- Phase 9B homepage visual parity complete
- Phase 9C single-article visual parity complete
- Shared archive architecture already exists
- Existing targeted archive/search validation baseline: 24 tests, 128 assertions

---

# 1. Objective

Perform a complete visual-parity audit and responsive polish of every Laravel archive page against the live Daily Samvad WordPress website.

This phase is strictly for:

- visual comparison
- spacing correction
- typography correction
- archive-header refinement
- card-layout refinement
- image-ratio correction
- breadcrumb alignment
- sidebar alignment
- advertisement spacing
- pagination polish
- empty-state polish
- responsive behavior
- overflow prevention
- accessibility verification
- browser validation

Do not redesign the archive pages.

Do not rebuild existing archive architecture.

Do not introduce new business logic merely to imitate appearance.

Before changing files, inspect the current implementation and report the verified differences between Laravel and WordPress.

---

# 2. Archive Types in Scope

Audit all supported archive pages:

1. Category archive
2. Tag archive
3. Search-results archive
4. Year archive
5. Month archive
6. Day archive
7. Author archive
8. Empty archive states
9. Empty search-results state
10. Paginated archive pages

Use multiple real examples when possible, including:

- a category with many posts
- a category with few posts
- a tag page
- a long search query
- a no-results search
- a date archive
- an author archive
- a later pagination page

Do not judge parity from only one archive URL.

---

# 3. Protected Boundaries

Do not change:

- database schema
- migrations
- imported WordPress data
- WordPress import commands
- models unless a verified display-only bug requires a minimal accessor change
- publication scopes
- archive routes
- route parameter contracts
- archive controller responsibilities
- `ArchivePageQuery` architecture
- `ArchivePageData` DTO contract
- shared archive query services
- structured-data architecture
- SEO architecture
- canonical URL rules
- sidebar query architecture
- advertisement DTOs
- advertisement resolver architecture
- breaking-news query architecture
- homepage architecture
- article architecture
- static-page architecture
- Filament/admin
- authentication
- authorization
- API routes

Do not:

- install packages
- introduce Livewire
- add Blade database queries
- duplicate archive templates
- create separate query logic for each archive type
- create route-specific CSS hacks unless absolutely necessary
- use inline styles
- use broad global selectors that affect unrelated pages
- use `!important` unless unavoidable and documented
- change ordering, pagination, or filtering logic for visual reasons
- commit or push changes

Preserve all Phase 0–9C architecture.

---

# 4. First Step — Existing Implementation Audit

Before editing, inspect:

1. Archive routes
2. Archive controllers
3. `ArchivePageQuery`
4. `ArchivePageData`
5. Search query handling
6. Category query handling
7. Tag query handling
8. Date archive handling
9. Author archive handling
10. Shared archive Blade layout
11. Archive header component
12. Breadcrumb component
13. Post-card component
14. Pagination component
15. Sidebar integration
16. Advertisement integration
17. Empty-state component
18. SEO metadata rendering
19. Structured-data output
20. Archive CSS
21. Card CSS
22. Pagination CSS
23. Sidebar CSS
24. Existing tests
25. Current browser rendering

Then compare against the live WordPress reference.

Report actual differences before applying corrections.

Do not make speculative changes.

---

# 5. Expected Page Composition

Confirm the actual WordPress order before changing markup.

Expected sequence:

```text
Header
Primary Navigation
Secondary Navigation, where configured
Breaking News Ticker
Breadcrumbs
Archive Header
Top Advertisement, where configured
Archive Results
Pagination
Sidebar
Bottom Advertisement, where configured
Footer
```

Requirements:

- ticker appears once
- breadcrumbs appear once
- archive H1 appears once
- sidebar markup appears once
- advertisement slots are not duplicated
- empty states do not render fake result wrappers
- footer follows naturally without fixed-height gaps

---

# 6. Global Archive Layout

At desktop widths, preserve the newspaper-style layout:

```text
Main archive column | Sidebar
```

Audit and correct only verified differences involving:

- outer container width
- left and right gutters
- main-column width
- sidebar width
- inter-column gap
- top alignment
- section alignment
- footer alignment
- `min-width: 0`
- overflow behavior
- breakpoint transitions

Use existing layout tokens and shared container rules.

Do not create competing archive container widths.

---

# 7. Breadcrumbs

Verify:

- distance below ticker or navigation
- distance above archive header
- left alignment with archive content
- font size
- line height
- separator appearance
- current-page contrast
- mobile wrapping
- long-title truncation or wrapping
- accessible breadcrumb label

Reuse the shared breadcrumb component.

Do not create archive-specific breadcrumb markup.

Do not apply separate spacing fixes to category, tag, search, date, and author pages.

---

# 8. Archive Header

Use one shared archive-header presentation for all archive types unless existing architecture already provides an equivalent abstraction.

Audit:

- H1 size
- H1 weight
- H1 line height
- title width
- title wrapping
- archive-type label, if present
- archive description
- result count
- author avatar and bio
- search-query presentation
- spacing above
- spacing below
- divider placement
- alignment with result cards
- mobile behavior

Requirements:

- exactly one H1
- no duplicate title
- no empty description wrapper
- no empty result-count wrapper
- no dangling separators
- long Hindi and English titles wrap safely
- long search queries do not overflow
- search query is escaped
- author information only renders when real data exists

Examples:

```text
पंजाब

34 Articles
```

```text
Search Results for “Punjab”

54 results found
```

Do not hardcode English labels when localized labels already exist.

---

# 9. Category Archives

Audit:

- category name
- category description
- result count
- breadcrumbs
- archive-card spacing
- sidebar alignment
- top advertisement
- pagination
- empty category state
- mobile stacking

Requirements:

- description preserves safe HTML policy
- description does not overflow
- no duplicate category title
- pagination retains category context
- no query changes
- no ordering changes

---

# 10. Tag Archives

Audit:

- tag name
- optional description
- result count
- breadcrumb trail
- archive cards
- pagination
- sidebar
- spacing
- mobile layout

Requirements:

- compact editorial presentation
- no oversized tag pill for page title
- no unnecessary blank space when description is absent
- pagination retains tag context
- no logic changes

---

# 11. Search Results

Audit:

- search heading
- displayed search term
- result count
- search input, if present
- card layout
- no-results state
- pagination
- sidebar
- long-query wrapping
- special-character handling
- mobile behavior

Requirements:

- query is safely escaped
- no raw HTML from query
- long query wraps naturally
- no page-level overflow
- no empty quoted string
- no fake results
- no changed search ranking
- no changed filtering logic
- no query regression

No-results state should clearly communicate:

- no results were found
- the searched phrase
- an optional retry action or search form
- an optional homepage link

Keep the state restrained and editorial, not dashboard-like.

---

# 12. Date Archives

Audit all supported date archive types:

- year
- month
- day

Verify:

- date formatting
- translated month names where applicable
- H1
- breadcrumb trail
- result count
- archive cards
- pagination
- sidebar
- empty state
- canonical behavior remains untouched

Requirements:

- do not alter date query logic
- do not alter timezone handling
- do not alter route bindings
- date heading must wrap safely
- shared archive layout must be reused

---

# 13. Author Archives

If author archives are enabled, audit:

- author name
- avatar
- biography
- post count
- archive cards
- sidebar
- pagination
- mobile stacking

Requirements:

- no email exposure
- no private metadata
- avatar has meaningful alt text
- absent avatar uses existing fallback
- absent biography does not leave blank space
- one H1 only
- no query changes

If author archives are intentionally unsupported, report that and make no unrelated changes.

---

# 14. Archive Result Cards

Compare the current shared archive card with WordPress.

Audit:

- card orientation
- thumbnail width
- thumbnail height
- image aspect ratio
- image crop
- title size
- title weight
- title line height
- title line clamp, if present
- excerpt size
- excerpt line height
- excerpt length presentation
- category label
- date
- author, if shown
- metadata separators
- card padding
- card gap
- card divider
- hover state
- focus state
- image fallback
- mobile layout

Requirements:

- reuse shared card component
- do not duplicate card markup per archive
- no nested anchors
- meaningful image alt text
- no fake excerpt generation in Blade
- no N+1 access
- no distorted thumbnails
- explicit image dimensions or aspect ratio retained
- long titles wrap safely
- metadata does not create dangling separators
- card remains compact and editorial
- avoid SaaS-style shadows and oversized rounded corners

Desktop should preserve the reference card density.

Mobile cards must not let images crush text.

At narrow widths, use the verified WordPress behavior:

- safe horizontal card layout, or
- full-width stacked card layout

Do not invent a different design.

---

# 15. Archive Grid and Result Rhythm

Verify:

- spacing between cards
- separators
- first-card alignment
- last-card spacing
- relation to pagination
- relation to sidebar
- relation to advertisements
- consistent card rhythm across archive types

Avoid:

- excessive vertical whitespace
- inconsistent card padding
- different layouts for equivalent archive pages
- fixed heights that clip titles
- equal-height forcing that causes blank areas

---

# 16. Images

Verify:

- correct image source
- correct size variant
- explicit width and height
- aspect-ratio reservation
- object-fit behavior
- fallback image
- lazy loading below the fold
- no cumulative layout shift
- no distorted portrait images
- no page-level overflow

Do not change image-resolution architecture unless a verified rendering bug requires a minimal correction.

Do not load full-size originals unnecessarily.

---

# 17. Metadata

Audit:

- category
- author
- publication date
- reading time, if shown
- result-specific metadata
- icon alignment
- separators
- wrapping
- mobile order

Requirements:

- semantic `<time>`
- no fake values
- no empty metadata wrapper
- no dangling separators
- no internal email
- no misleading updated date
- consistent card metadata across archive types

---

# 18. Excerpts

Verify:

- font size
- line height
- color
- width
- number of visible lines
- spacing from title
- spacing before metadata
- mobile visibility

Requirements:

- use existing excerpt data
- no expensive runtime generation
- no HTML leakage
- no broken entities
- no overflow
- absent excerpt must not leave blank space

---

# 19. Empty States

Audit empty states for:

- category
- tag
- search
- date
- author

Verify:

- heading
- explanatory message
- spacing
- optional search retry
- optional homepage link
- sidebar behavior
- advertisements
- footer transition
- mobile layout

Requirements:

- no fake cards
- no empty pagination
- no result-count contradiction
- no oversized illustration unless already part of design
- no dashboard-style panel
- one clear message
- accessible action labels
- no horizontal overflow

---

# 20. Pagination

Audit against WordPress:

- position
- alignment
- top margin
- bottom margin
- page-number spacing
- active state
- hover state
- focus state
- previous/next labels
- disabled state
- ellipsis
- touch-target size
- wrapping
- mobile layout

Requirements:

- preserve Laravel pagination logic
- preserve query string on search
- preserve archive context
- use semantic navigation
- include accessible label
- active page is identifiable without color alone
- no overflow on narrow screens
- no tiny touch targets
- no duplicate pagination

Do not replace the paginator implementation merely for styling.

---

# 21. Sidebar

Verify:

- starts at the correct vertical position
- aligns with archive header or first result according to reference
- width
- widget spacing
- title styling
- latest-news density
- popular-news density
- category-list spacing
- social widget
- advertisement slots
- sticky offset
- sticky containment
- footer overlap
- tablet behavior
- mobile stacking

Requirements:

- reuse shared sidebar
- no duplicate sidebar query
- no duplicate widget markup
- sticky behavior only where already intended
- disable sticky behavior below the established breakpoint
- sidebar moves below main content on mobile
- no horizontal overflow
- no fixed minimum height causing blank areas

Do not modify sidebar data architecture.

---

# 22. Advertisements

Audit all archive-related slots already supported by the system, including:

- archive top
- archive inline, if present
- sidebar advertisements
- archive bottom

Verify:

- correct slot placement
- wrapper width
- height reservation
- spacing above and below
- placeholder behavior
- disabled-slot behavior
- responsive containment
- image containment
- script containment
- no duplicate slot
- no CLS
- no blank wrapper when disabled
- no fixed-width mobile overflow

Do not:

- create a new advertisement renderer
- duplicate ad resolution
- insert ads inside card anchors
- alter advertisement DTO contracts
- hardcode campaign data in Blade

---

# 23. Typography

Compare WordPress and Laravel typography for:

- archive H1
- archive description
- result count
- card title
- card excerpt
- metadata
- breadcrumb
- pagination
- sidebar headings
- empty states

Verify:

- Hindi rendering
- English metadata
- font fallback
- line height
- font weight
- contrast
- mobile scaling

Use existing font system and design tokens.

Do not install a new font package.

Keep the design dense, readable, and editorial.

---

# 24. CSS Architecture

Prefer modifying existing shared files such as:

```text
resources/css/frontend/archive.css
resources/css/frontend/cards.css
resources/css/frontend/breadcrumbs.css
resources/css/frontend/pagination.css
resources/css/frontend/sidebar.css
resources/css/frontend/advertisements.css
resources/css/app.css
```

Use the project’s actual existing paths and naming conventions.

Requirements:

- scope frontend rules under `.ds-site`
- use existing custom properties
- consolidate repeated media queries
- avoid route-specific selectors
- avoid inline styles
- avoid broad element selectors
- avoid unnecessary new CSS files
- avoid excessive specificity
- document unavoidable `!important`

Do not change unrelated homepage or article styling unless the corrected rule is genuinely shared and regression-tested.

---

# 25. Responsive Verification

Verify at:

```text
1440px
1280px
1024px
768px
430px
390px
375px
```

## 1440px and 1280px

Verify:

- correct outer container
- correct main/sidebar ratio
- card density
- thumbnail dimensions
- archive header width
- pagination alignment
- sticky sidebar
- advertisements contained
- no excess side whitespace

## 1024px

Verify:

- no squeezed content column
- no navigation overflow
- safe sidebar behavior
- card image does not dominate
- long titles wrap safely
- pagination remains contained

## 768px

Verify:

- stacked layout where required
- sticky sidebar disabled
- cards reflow correctly
- archive header remains readable
- ads fit
- pagination wraps
- no page-level overflow

## 430px, 390px, and 375px

Verify:

- no horizontal overflow
- breadcrumbs fit or wrap
- archive H1 wraps naturally
- result count stays readable
- long search query wraps
- card image and text remain balanced
- excerpts remain readable
- metadata wraps cleanly
- pagination is usable
- ads fit viewport
- sidebar appears below results
- footer remains contained
- touch targets remain accessible

Do not solve layout problems with a blanket page-level `overflow-x: hidden` unless absolutely necessary.

Fix the responsible component.

---

# 26. Overflow Audit

Inspect for overflow caused by:

- long archive titles
- long search queries
- long post titles
- long category names
- image widths
- card flex children
- metadata
- pagination
- advertisements
- sidebar widgets
- breadcrumbs
- author biography
- URLs in excerpts

Use:

- `min-width: 0`
- safe wrapping
- responsive dimensions
- contained overflow where semantically appropriate

Do not globally hide broken layout.

---

# 27. CLS and Rendering Stability

Inspect:

- archive thumbnails
- fallback images
- ads
- sidebar thumbnails
- fonts
- ticker
- archive header
- author avatar

Preserve or add, where appropriate:

- explicit width and height
- `aspect-ratio`
- stable placeholders
- predictable ad wrappers

Do not remove existing dimensions.

Do not introduce animation that shifts content.

---

# 28. Accessibility

Preserve and verify:

- one `<main>`
- one H1
- semantic archive sections
- breadcrumb navigation label
- semantic result articles
- meaningful link text
- meaningful image alt text
- semantic `<time>`
- pagination navigation label
- `aria-current="page"`
- visible keyboard focus
- author image alt text
- accessible empty-state actions
- no duplicate IDs
- no nested anchors
- sufficient contrast
- logical heading hierarchy

Visual changes must not reduce accessibility.

---

# 29. SEO and Structured Data

Do not change SEO architecture.

Verify visually related markup changes do not break:

- title tag
- meta description
- canonical URL
- robots directives
- archive structured data
- breadcrumb structured data
- search-page indexing policy
- pagination canonical behavior
- author metadata
- date archive metadata

Only update tests if a markup correction changes a tested contract.

Do not invent SEO behavior in this phase.

---

# 30. Performance and Query Safety

This phase must not:

- increase archive query count
- add Blade queries
- add N+1 behavior
- duplicate archive queries
- duplicate sidebar queries
- duplicate ticker queries
- duplicate advertisement queries
- load full-size images unnecessarily
- add third-party scripts
- add client-side rendering
- add Livewire

Measure or verify query behavior using the project’s existing test strategy.

Report query impact explicitly.

---

# 31. JavaScript

Do not add JavaScript unless a verified existing interaction bug requires it.

No new:

- sliders
- infinite scroll
- client-side pagination
- social SDKs
- animation libraries
- card scripts

Preserve the current frontend bootstrap and ticker behavior.

---

# 32. Required Tests

Update tests only where markup contracts change.

At minimum verify:

- category archive returns 200
- tag archive returns 200
- search results return 200
- no-results search returns 200
- year archive returns 200
- month archive returns 200
- day archive returns 200
- author archive returns 200, if supported
- one H1 per archive
- breadcrumbs render once
- ticker renders once
- archive header renders
- archive cards render
- empty state renders when appropriate
- sidebar renders once
- pagination renders when required
- search query remains escaped
- search query string is preserved during pagination
- no duplicate IDs
- no nested anchors
- image dimensions remain
- no Blade queries
- no N+1 behavior
- query count does not regress
- advertisement slots do not duplicate
- mobile wrapper classes remain
- structured data remains valid

Do not create brittle tests for exact CSS pixel values.

Preserve the current 24-test / 128-assertion targeted baseline and add only meaningful coverage.

---

# 33. Browser Verification

Use a real browser if available.

Compare Laravel with WordPress at the required widths.

Capture screenshots for:

## Desktop

1. Category archive
2. Tag archive
3. Search-results archive
4. Date archive
5. Author archive, if supported
6. Pagination
7. Empty state
8. Sidebar alignment
9. Advertisement placement

## Mobile

1. Category archive
2. Search-results archive
3. Long search query
4. Empty state
5. Pagination
6. Sidebar below results
7. Advertisement containment

Required widths:

```text
1440px
1280px
1024px
768px
430px
390px
375px
```

If browser tooling is unavailable:

- state this clearly
- do not claim pixel-perfect parity
- list the exact remaining manual checks
- still complete code, test, and build validation

---

# 34. Validation Commands

Before changes:

```powershell
git status --short
```

Clear and rebuild Laravel view/config state:

```powershell
php artisan config:clear
php artisan view:clear
php artisan view:cache
```

Run focused tests:

```powershell
php artisan test --filter=Archive
php artisan test --filter=Category
php artisan test --filter=Tag
php artisan test --filter=Search
php artisan test --filter=Date
php artisan test --filter=Author
php artisan test --filter=Pagination
php artisan test --filter=Sidebar
php artisan test --filter=Breaking
```

Run full test suite:

```powershell
php artisan test
```

Run formatting check:

```powershell
vendor\bin\pint --test
```

Run frontend build:

```powershell
npm.cmd run build
```

Run syntax validation on modified PHP files:

```powershell
php -l path\to\modified-file.php
```

After changes:

```powershell
git status --short
git diff --stat
git diff --check
```

Do not claim a command passed unless it actually passed.

If a command is unavailable or blocked, report the exact reason.

---

# 35. Git Safety

Requirements:

- inspect Git status before editing
- do not overwrite unrelated local changes
- do not modify global Git configuration
- do not create commits
- do not push
- do not reset user work
- do not use destructive checkout commands
- do not clean untracked files
- report dubious-ownership errors
- do not bypass ownership globally
- show final modified-file list
- run `git diff --check`

---

# 36. Completion Criteria

Phase 9D is complete only when:

- all supported archive types were audited
- actual Laravel/WordPress differences were documented
- archive header more closely matches WordPress
- breadcrumb spacing is consistent
- card proportions are corrected
- thumbnail ratios are stable
- title and excerpt typography are corrected
- metadata is compact and consistent
- category archive is polished
- tag archive is polished
- search results are polished
- no-results search is polished
- date archives are polished
- author archive is polished, if supported
- pagination is accessible and responsive
- sidebar alignment is correct
- advertisements remain contained
- mobile overflow is absent
- CLS protections remain
- accessibility remains intact
- SEO behavior remains intact
- structured data remains intact
- no architecture regression occurs
- no query-count regression occurs
- no N+1 behavior is introduced
- focused tests pass
- full test suite passes
- Pint passes
- Vite build passes
- Git diff check passes
- browser limitations are honestly reported

---

# 37. Required Completion Report

Return exactly these sections:

## 1. Phase 9D Summary

## 2. Initial Visual Audit

## 3. Archive Types Audited

## 4. Breadcrumb Corrections

## 5. Archive Header Corrections

## 6. Category Archive Corrections

## 7. Tag Archive Corrections

## 8. Search Results Corrections

## 9. Date Archive Corrections

## 10. Author Archive Corrections

## 11. Archive Card Corrections

## 12. Image and Metadata Corrections

## 13. Empty-State Corrections

## 14. Pagination Corrections

## 15. Sidebar Corrections

## 16. Advertisement Corrections

## 17. Typography Corrections

## 18. Responsive Verification

## 19. Overflow and CLS Verification

## 20. Accessibility

## 21. SEO and Structured Data Impact

## 22. Query and Performance Impact

## 23. Files Created

## 24. Files Modified

## 25. Validation Results

## 26. Test Results

## 27. Browser Verification

## 28. Screenshots

## 29. Git Status

## 30. Remaining Risks

## 31. Ready for Phase 9E

---

# 38. Final Requirement

This is a visual-refinement phase, not a redesign.

Preserve all Phase 0–9C architecture.

Make only verified, reusable, minimal corrections.

Do not modify protected architecture merely to imitate WordPress markup.

Do not report pixel-perfect parity unless a real browser comparison was completed at the required widths.

After implementation, return the exact completion-report structure defined above.