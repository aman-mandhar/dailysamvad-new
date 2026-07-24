# Daily Samvad — Version 2.1

## Phase 2.1-I: Image Optimization, Responsive Media and Lazy Loading

You are working on the existing Daily Samvad Laravel application.

The application currently uses:

* Laravel 13
* Filament 5
* Livewire 4
* MySQL
* Spatie Laravel Permission
* Redis foundation from Phase 2.1-F
* Cache architecture from Phase 2.1-G
* Queue architecture from Phase 2.1-H
* Imported WordPress posts and media
* Existing featured-media mappings
* Existing public article, archive and homepage templates
* Existing Media model and Filament media management

Phase 2.1-I must implement a safe, backward-compatible and production-ready image optimization pipeline.

The phase must improve image delivery without breaking:

* Imported WordPress media
* Existing featured images
* Article content images
* Media URLs
* SEO metadata
* Structured data
* OpenGraph images
* Google News images
* Legacy content
* Public layouts
* Editorial workflow

---

# 1. Primary Objective

Implement a comprehensive image optimization architecture covering:

* Existing media audit
* Image metadata
* Responsive image variants
* WebP support
* AVIF support where safely available
* Thumbnail generation
* Queue-based image processing
* Lazy loading
* Priority loading
* Image dimensions
* Aspect-ratio preservation
* Placeholder strategy
* Image URL resolution
* Missing-image fallback
* Duplicate processing prevention
* Reprocessing
* Storage organization
* Cleanup safety
* SEO compatibility
* Performance measurement
* Automated tests
* Production deployment documentation

The implementation must preserve the original uploaded image as the permanent source asset unless explicitly documented otherwise.

---

# 2. Core Principles

Every image transformation must have:

* A source image
* A deterministic destination path
* A defined width and height
* A defined format
* A quality setting
* An idempotent generation process
* Failure handling
* Queue behavior
* Cache behavior
* Cleanup rules
* Test coverage

Do not permanently replace or overwrite original images.

Do not break existing media URLs to achieve optimization.

---

# 3. Existing-State Audit

Before modifying anything, audit:

* Media database table
* Media model
* Post featured-image fields
* Featured-media relationships
* Imported WordPress attachment IDs
* Existing file-path normalization
* Existing media storage disk
* Existing storage paths
* Public storage symlink
* Existing image URLs
* Existing thumbnail files
* Existing WordPress generated sizes
* Existing image dimensions
* Existing MIME types
* Existing file extensions
* Existing image-processing libraries
* PHP GD availability
* PHP Imagick availability
* WebP encoder availability
* AVIF encoder availability
* Current queue worker readiness
* Existing media jobs
* Existing media observers
* Existing media upload flows
* Existing Filament media forms
* Existing frontend image components
* Homepage images
* Article featured images
* Archive-card images
* Sidebar images
* Related-post images
* OpenGraph images
* Schema image values
* Sitemap image entries
* Google News image behavior
* Image lazy-loading behavior
* Existing CSS aspect-ratio rules
* Layout shifts caused by images
* Broken-image behavior
* Duplicate image records
* Missing source files
* Unreadable source files
* Unsupported files
* Current page-weight baseline
* Current Lighthouse image warnings
* Existing image-related tests

Document the current state before implementation.

---

# 4. Protected Boundaries

Do not disturb:

* Existing users
* Existing roles
* Existing permissions
* Editorial workflow
* Workflow-history records
* Scheduled publishing
* Imported posts
* Imported media records
* WordPress attachment IDs
* Featured-media mappings
* Existing public URLs
* Existing media URLs
* Post slugs
* Legacy redirects
* SEO titles
* SEO descriptions
* Canonical URLs
* OpenGraph metadata
* Structured data
* Sitemap behavior
* Google News behavior
* Existing cache architecture
* Existing queue architecture
* Existing Redis namespaces
* Existing storage originals
* Existing database records
* Existing production secrets

Do not run destructive commands.

Prohibited commands include:

```bash
php artisan migrate:fresh
php artisan migrate:refresh
php artisan migrate:reset
php artisan db:wipe
git reset --hard
git clean -fd
composer update
npm update
redis-cli FLUSHALL
redis-cli FLUSHDB
rm -rf storage/app/public
```

Do not mass-delete original images.

Do not remove existing WordPress-generated media sizes until a verified migration and rollback plan exists.

---

# 5. Scope of This Phase

This phase includes:

* Image-processing architecture
* Image derivative metadata
* Responsive sizes
* WebP conversion
* Optional AVIF conversion
* Queue-based processing
* Lazy loading
* Priority loading
* Width and height attributes
* Aspect-ratio handling
* Frontend responsive markup
* Picture/source elements
* Fallback images
* Image health diagnostics
* Reprocessing commands
* Image cleanup planning
* Performance testing
* Automated tests
* Documentation

This phase does not include:

* AI image generation
* Video transcoding
* Audio processing
* CDN migration
* Object-storage migration
* Public frontend redesign
* Search replacement
* Analytics collection
* News-Man integration
* Destructive replacement of original media
* Large-scale media deletion

---

# 6. Source Image Policy

The original image must remain the source of truth.

Original media must:

* Remain unchanged
* Retain original path
* Retain original filename where already used
* Remain available for regeneration
* Remain recoverable
* Not be recompressed destructively
* Not be deleted automatically

Generated variants must be treated as rebuildable derivatives.

---

# 7. Image Processing Library Decision

Audit available processing options.

Possible approaches:

```text
Intervention Image
Spatie Laravel Media Library
Native GD
Imagick
Custom image service
```

Do not introduce a large package without justification.

If adding a package:

* Verify Laravel 13 compatibility
* Verify PHP compatibility
* Pin an appropriate version
* Use targeted Composer installation
* Do not run `composer update`
* Document why the existing stack was insufficient
* Add compatibility tests

Prefer the smallest maintainable solution that meets requirements.

---

# 8. GD and Imagick Assessment

Verify:

```bash
php -m
php --ri gd
php --ri imagick
```

Document:

* CLI PHP support
* PHP-FPM support
* JPEG support
* PNG support
* GIF support
* WebP support
* AVIF support
* Memory implications
* EXIF handling
* Orientation support

Do not claim AVIF support unless the actual server encoder successfully generates and reads AVIF files.

---

# 9. Supported Source Formats

Audit and safely support:

```text
JPEG
JPG
PNG
WEBP
GIF
```

Optional:

```text
AVIF
```

Handle cautiously:

```text
SVG
BMP
TIFF
ICO
```

Do not rasterize SVG automatically unless explicitly approved.

Do not process executable or untrusted files as images merely based on extension.

Verify MIME type using trusted file inspection.

---

# 10. Image Metadata Architecture

Store or derive useful metadata such as:

```text
Original width
Original height
Aspect ratio
MIME type
File size
Checksum
Orientation
Processing status
Processing error
Processed timestamp
Available variants
Dominant color where justified
Placeholder metadata where justified
```

Adapt the schema to the current Media model.

Use additive migrations only.

Do not break existing media records.

---

# 11. Variant Metadata

Each generated variant should be identifiable by:

* Media ID
* Variant name
* Width
* Height
* Format
* Quality
* File path
* Public URL
* File size
* Checksum
* Generation timestamp
* Status

Possible storage approaches:

```text
JSON metadata on media table
Dedicated media_variants table
Structured filesystem convention
Hybrid metadata and filesystem
```

Choose the least disruptive maintainable design.

---

# 12. Responsive Width Standard

Define a bounded responsive-width set.

Suggested baseline:

```text
320
480
640
768
960
1200
1600
```

Do not generate a size larger than the original image.

Do not generate every width for every image if the actual usage does not require it.

Document which variants apply to:

* Article featured images
* Homepage cards
* Archive cards
* Sidebar thumbnails
* Related posts
* OpenGraph
* Google News
* Admin previews

---

# 13. Named Image Presets

Define named presets.

Example:

```text
thumbnail:
320 × 180

card:
480 × 270

archive:
640 × 360

article:
1200 × auto

hero:
1600 × auto

square:
600 × 600

og:
1200 × 630
```

Adapt to actual frontend aspect ratios.

Do not crop all images blindly.

Use crop, contain or resize behavior intentionally per preset.

---

# 14. Aspect Ratio Strategy

Define aspect ratios for each context.

Possible ratios:

```text
16:9
4:3
3:2
1:1
Original ratio
```

Rules:

* Preserve original ratio for article content images.
* Use controlled crops for card layouts only where design requires.
* Avoid stretching.
* Use CSS `aspect-ratio` for layout stability.
* Document focal-point limitations.

Do not crop critical image content without an editorially acceptable strategy.

---

# 15. EXIF Orientation

Normalize orientation during processing.

Verify portrait images from phones render correctly.

Do not mutate original file merely to normalize derivatives.

Add tests or fixtures for rotated JPEG metadata where practical.

---

# 16. WebP Generation

Generate WebP derivatives where supported.

Requirements:

* Preserve original
* Use configurable quality
* Skip unsupported sources safely
* Avoid upscaling
* Generate deterministic paths
* Detect existing valid file
* Rebuild corrupted file
* Record failure
* Queue processing
* Test browser fallback markup

Suggested quality range:

```text
75–85
```

Choose based on measured results.

---

# 17. AVIF Generation

AVIF is optional.

Enable only when:

* Server encoder support is verified
* Generation time is acceptable
* File savings are meaningful
* Queue timeouts are configured
* Browser fallback exists
* Operational support is documented

Use a feature flag such as:

```text
IMAGE_AVIF_ENABLED=false
```

Do not make AVIF mandatory for phase completion.

---

# 18. PNG Handling

Preserve PNG when transparency is required.

Do not convert transparent PNG to JPEG.

WebP or AVIF derivatives may be generated if transparency is preserved.

Avoid excessive lossy compression for logos, screenshots and text-heavy graphics.

---

# 19. GIF Handling

Do not destroy animation.

For animated GIF:

* Preserve original GIF
* Avoid generating a static replacement as the only public source
* Detect animation where practical
* Generate poster thumbnail only if explicitly useful
* Document limitations

Do not process large animated GIFs synchronously.

---

# 20. SVG Handling

SVG files must not be passed through raster pipelines automatically.

Verify:

* Sanitization requirements
* Existing upload policy
* Frontend rendering
* Security risk

Do not allow untrusted SVG upload without sanitization.

---

# 21. Deterministic Storage Paths

Use deterministic derivative paths.

Suggested pattern:

```text
storage/app/public/media/variants/{media_id}/{preset}/{filename}.{format}
```

or:

```text
storage/app/public/wordpress/optimized/{year}/{month}/{filename}-{width}.{format}
```

Adapt to current media architecture.

Requirements:

* Environment-safe
* Collision-resistant
* Rebuildable
* Human-debuggable
* Compatible with storage disk
* No path traversal
* No dependency on unsafe user input

---

# 22. Filename Normalization

Normalize generated filenames safely.

Do not rename originals.

Generated filenames may include:

```text
Original basename
Width
Height
Preset
Format
Version
```

Avoid:

* User-supplied path separators
* Extremely long filenames
* Unsafe Unicode path handling
* Query strings
* Secrets

---

# 23. Image Processing Service

Create a central service.

Possible location:

```text
app/Services/Media/ImageOptimizationService.php
```

Possible responsibilities:

```php
inspect(Media $media): ImageInspectionResult
generateVariants(Media $media): ImageProcessingResult
generateVariant(Media $media, ImagePreset $preset, ImageFormat $format): MediaVariant
deleteGeneratedVariants(Media $media): void
regenerate(Media $media): ImageProcessingResult
```

Do not place processing logic directly in controllers, Blade templates or Filament actions.

---

# 24. Image Preset Configuration

Create centralized configuration.

Possible file:

```text
config/images.php
```

It may define:

* Enabled formats
* WebP quality
* AVIF quality
* Presets
* Widths
* Maximum dimensions
* Maximum source size
* Queue name
* Processing timeout
* Placeholder strategy
* Lazy-loading defaults
* Feature flags

Do not hardcode image dimensions throughout templates.

---

# 25. Queue-Based Image Processing

Use Phase 2.1-H queue architecture.

Recommended queue:

```text
media
```

Image jobs must:

* Be idempotent
* Use scalar media ID
* Re-query Media safely
* Avoid duplicate processing
* Use overlap protection
* Define timeout
* Define retry attempts
* Define backoff
* Handle missing source
* Record failure
* Run after database commit where needed

Do not process expensive transformations inside the upload HTTP request unless a small synchronous preview is explicitly required.

---

# 26. Image Processing Job

Possible job:

```text
GenerateImageVariants
```

Recommended behavior:

* Implements `ShouldQueue`
* Uses media queue
* Uses `ShouldBeUnique` or overlap middleware
* Unique by media ID plus processing version
* Loads source safely
* Inspects MIME and dimensions
* Generates required presets
* Records status
* Invalidates relevant caches
* Logs safe context
* Fails cleanly

Do not store raw binary payloads inside the queued job.

---

# 27. Processing Status

Define statuses such as:

```text
pending
processing
completed
partial
failed
unsupported
missing_source
```

Do not mark processing complete when required variants failed.

A partial status may be appropriate when WebP succeeds but AVIF fails.

---

# 28. Retry and Timeout Strategy

Set media-specific values based on measurement.

Example:

```text
tries: 3
backoff: 30, 120, 300
timeout: 300 seconds
```

Adapt to image sizes and server resources.

Ensure queue `retry_after` exceeds job timeout safely.

Do not allow retry storms on permanently corrupt files.

---

# 29. Memory Protection

Image processing can consume large memory.

Implement safeguards:

* Maximum source dimensions
* Maximum file size
* Pixel-count limits
* Queue isolation
* Worker memory limit
* One or few media workers initially
* Controlled processing concurrency
* Cleanup of temporary resources
* Exception handling for memory failures

Do not load unbounded images blindly.

---

# 30. Decompression-Bomb Protection

Protect against malicious or malformed images.

Check:

```text
File size
Width
Height
Total pixel count
MIME type
Decoder failure
```

Reject or mark unsupported when thresholds are exceeded.

Do not rely solely on file extension.

---

# 31. Duplicate Processing Prevention

Prevent multiple jobs processing the same media/version simultaneously.

Use:

* Unique jobs
* Redis locks
* WithoutOverlapping
* Database status checks
* Checksums

A retry must not create duplicate derivative records.

---

# 32. Processing Version

Define an image-pipeline version.

Example:

```text
IMAGE_PROCESSING_VERSION=v1
```

Include it in:

* Variant metadata
* Cache keys
* Job uniqueness
* Regeneration decisions

Changing quality or dimensions should allow controlled reprocessing.

---

# 33. Existing Variant Detection

Before generation:

* Verify metadata
* Verify file exists
* Verify file is readable
* Verify dimensions
* Verify expected format
* Verify non-zero size

Do not skip processing merely because a database record exists.

---

# 34. Reprocessing Command

Create a safe Artisan command.

Suggested command:

```bash
php artisan images:process
```

Possible options:

```text
--media=
--missing
--failed
--unprocessed
--all
--limit=
--chunk=
--sync
--queue
--force
--format=
--preset=
```

Requirements:

* Safe defaults
* Bounded processing
* Dry-run support
* Progress reporting
* Summary
* No secret output
* Queue support
* Non-zero exit for critical failure
* No mass reprocessing without explicit option

---

# 35. Image Audit Command

Consider:

```bash
php artisan images:audit
```

Checks may include:

```text
Missing originals
Unreadable originals
Unsupported MIME types
Missing dimensions
Missing variants
Corrupt variants
Oversized images
Duplicate checksums
Broken public URLs
Processing failures
```

Output must distinguish:

* Healthy
* Missing
* Unreadable
* Unsupported
* Partial
* Failed

---

# 36. Cleanup Command

If implemented, cleanup must be conservative.

Possible command:

```bash
php artisan images:cleanup
```

Default behavior must be dry-run.

It may identify:

* Orphaned generated variants
* Obsolete processing-version variants
* Temporary files
* Metadata records with missing generated files

Never delete:

* Original images
* Unknown files
* WordPress uploads
* Files outside owned derivative directory
* Current-version valid variants

Require explicit confirmation or `--force` for deletion.

---

# 37. Media Upload Integration

For newly uploaded images:

```text
1. Validate upload
2. Save original
3. Create media record
4. Commit transaction
5. Dispatch image-processing job
6. Show pending status
7. Process variants
8. Update status
9. Invalidate relevant cache if media is already public
```

Do not block the upload form for all variant generation.

---

# 38. Imported Media Integration

Imported WordPress media may lack:

* Dimensions
* Metadata
* Valid MIME
* Existing original
* Consistent extension
* Generated variants

The pipeline must support incremental processing of imported media.

Do not require all 5,000+ media records to process in one command.

Use bounded batches and resumable execution.

---

# 39. Featured Image Integration

When a post’s featured media changes:

* Preserve relationship integrity
* Invalidate article cache
* Invalidate homepage and archive fragments
* Update OpenGraph output
* Update structured data output
* Update sitemap image references if applicable
* Dispatch variant generation if missing

Do not change `featured_media_id` automatically during optimization.

---

# 40. Article Content Images

Audit images embedded inside article HTML.

Possible issues:

* Absolute WordPress URLs
* Relative URLs
* `srcset` from WordPress
* Missing local files
* Lazy-loading attributes
* Width and height attributes
* External images
* Broken images
* Inline styles

Do not rewrite all stored article HTML destructively without a separate migration plan.

Prefer safe rendering-time enhancement or controlled content migration.

---

# 41. Responsive Image Component

Create a reusable Blade component.

Possible usage:

```blade
<x-responsive-image
    :media="$post->featuredMedia"
    preset="article"
    :alt="$post->title"
    loading="eager"
    fetchpriority="high"
/>
```

The component should support:

* Original fallback
* WebP sources
* AVIF sources when enabled
* `srcset`
* `sizes`
* Width
* Height
* Alt text
* Loading
* Decoding
* Fetch priority
* CSS class
* Fallback image
* Safe URL generation

Do not duplicate `<picture>` logic throughout templates.

---

# 42. Picture Element Strategy

Use:

```html
<picture>
    <source type="image/avif">
    <source type="image/webp">
    <img>
</picture>
```

Only include sources that actually exist.

The `<img>` fallback should remain a broadly compatible original or optimized compatible format.

Do not reference missing derivative URLs.

---

# 43. Srcset Strategy

Generate valid `srcset`.

Example:

```html
srcset="
image-320.webp 320w,
image-640.webp 640w,
image-960.webp 960w,
image-1200.webp 1200w
"
```

Do not include widths larger than the source.

Do not use duplicate-width entries.

---

# 44. Sizes Attribute

Define context-specific `sizes`.

Examples:

Homepage card:

```text
(max-width: 640px) 100vw,
(max-width: 1024px) 50vw,
33vw
```

Article image:

```text
(max-width: 768px) 100vw,
768px
```

Adapt to actual layout.

Do not use a generic inaccurate `sizes="100vw"` everywhere.

---

# 45. Width and Height Attributes

Every rendered content image should include correct intrinsic dimensions where possible.

Benefits:

* Reduced layout shift
* Better browser selection
* Stable cards
* Improved Core Web Vitals

Do not provide incorrect dimensions.

When exact derivative dimensions differ because of crop, use the derivative dimensions.

---

# 46. Lazy Loading

Use:

```html
loading="lazy"
```

for offscreen images.

Do not lazy-load:

* Primary article hero image
* Main homepage hero image
* Likely Largest Contentful Paint image
* Critical logo where delayed loading causes visible issues

Audit placement before assigning lazy loading.

---

# 47. Priority Loading

For the likely LCP image, use where appropriate:

```html
loading="eager"
fetchpriority="high"
decoding="async"
```

Do not assign high priority to many images.

Generally, only one or a very small number of critical images should receive high priority.

---

# 48. Decoding Strategy

Use:

```html
decoding="async"
```

where appropriate.

Verify it does not conflict with critical rendering behavior.

Do not add unsupported custom attributes.

---

# 49. Native Lazy Loading and JavaScript

Prefer browser-native lazy loading.

Do not introduce a heavy JavaScript lazy-loading library unless required for:

* Advanced placeholders
* Background images
* Compatibility constraints

If JavaScript is introduced:

* Keep it small
* Avoid layout shifts
* Work without JavaScript
* Avoid duplicate loading
* Test Livewire navigation behavior

---

# 50. Placeholder Strategy

Possible placeholders:

```text
Solid background
Dominant color
Low-quality image placeholder
BlurHash
SVG placeholder
```

Start conservatively.

Do not introduce expensive placeholder generation unless performance benefit is measured.

A fixed aspect-ratio container may be sufficient.

---

# 51. Missing Image Fallback

Create context-appropriate fallback behavior.

Possible fallbacks:

```text
Default news image
Category fallback
Neutral placeholder
No-image card state
```

Requirements:

* Correct dimensions
* Accessible alt behavior
* No broken icon
* No infinite fallback loop
* No false claim that fallback is the article image
* Compatible with SEO rules

Do not use one tiny low-resolution image for large hero contexts.

---

# 52. Alt Text

Use meaningful alt text.

Priority:

```text
Media alt text
Media title/caption where appropriate
Post title as fallback
Empty alt for decorative images
```

Do not use file names as alt text by default.

Do not keyword-stuff alt attributes.

---

# 53. SEO Compatibility

Verify image optimization preserves:

* OpenGraph image
* Twitter image
* Schema `image`
* Article structured data
* Image sitemap data
* Google News image eligibility
* Canonical page behavior
* Correct absolute URLs
* Correct image dimensions
* Crawl accessibility

Do not point SEO metadata to temporary or private derivative URLs.

---

# 54. OpenGraph Image Rules

OpenGraph image should:

* Be publicly accessible
* Use HTTPS in production
* Have valid dimensions
* Prefer approximately 1200 × 630 where available
* Fall back safely
* Not require authentication
* Not use lazy-loading semantics
* Not point to missing variants

Preserve current behavior unless improvement is verified.

---

# 55. Google News Image Considerations

Verify:

* Main article image is crawlable
* Image is sufficiently large
* Image is relevant
* Image URL is stable
* Robots rules allow access
* Structured data references a valid URL
* Lazy loading does not hide image from rendered HTML
* `<img src>` fallback exists

Do not provide only JavaScript-generated image URLs.

---

# 56. Sitemap Image Support

If image sitemap entries exist:

* Preserve original or stable derivative URL
* Ensure URL is public
* Invalidate sitemap cache when featured media changes
* Avoid duplicate entries
* Avoid broken URLs
* Preserve XML validity

---

# 57. Browser Cache Headers

Audit image response headers.

Possible headers:

```text
Cache-Control: public, max-age=31536000, immutable
ETag
Last-Modified
Content-Type
```

Long-lived immutable caching is appropriate only for versioned derivative filenames.

Do not assign immutable caching to paths that are overwritten in place.

---

# 58. Derivative URL Versioning

Use filename or path versioning.

Examples:

```text
image-640-v1.webp
v1/image-640.webp
```

This allows long browser caching.

Do not rely solely on query strings if server or CDN behavior is uncertain.

---

# 59. Cache Integration

When a new derivative becomes available:

* Invalidate relevant fragment cache if markup changes
* Invalidate article response cache if source sets change
* Invalidate homepage/archive caches where affected
* Avoid broad `Cache::flush()`
* Use Phase 2.1-G invalidation service

Do not create circular job dispatch between cache warming and image generation.

---

# 60. Queue Integration

Media-processing jobs must use Phase 2.1-H standards.

Verify:

* Correct queue name
* Correct worker group
* Safe timeout
* Safe retry_after
* Overlap prevention
* Health monitoring
* Failed-job recovery
* Deployment restart behavior

Do not claim processing works based only on `Queue::fake()`.

---

# 61. Filament Media UI

Improve media administration where safe.

Possible fields:

```text
Original dimensions
File size
MIME type
Optimization status
Variant count
Last processed
Error summary
```

Possible actions:

```text
Process
Reprocess
Audit
View original
View variants
Retry failed
```

Actions must be permission-protected.

Do not expose private server paths or stack traces.

---

# 62. Role and Permission Boundaries

Use existing RBAC.

Possible permissions:

```text
view media
manage media
optimize media
reprocess media
view media diagnostics
```

Do not create new permissions unless required and consistent with Phase 2.1-B.

Report any new permission explicitly.

Do not rely on hardcoded role names where policy or permission checks are available.

---

# 63. Image Health Dashboard

Add a lightweight diagnostic widget or page only if consistent with existing dashboard architecture.

Possible metrics:

```text
Total media
Image media
Processed
Pending
Failed
Missing source
Unsupported
Oversized
Missing variants
```

Scope visibility to authorized staff.

Do not build analytics unrelated to image health.

---

# 64. External Images

Audit remote images embedded in articles.

Do not automatically download and republish external images unless:

* Licensing is acceptable
* Source handling is approved
* Security checks exist
* Failure handling exists

For this phase, document and safely render external images.

Do not send external URLs through the local optimizer blindly.

---

# 65. Hotlink Protection

Do not implement hotlink protection if it may block:

* Google News
* Search crawlers
* Social previews
* CDN
* RSS readers
* Legitimate embeds

Any server-level change must be separately audited.

---

# 66. Storage Permissions

Verify application and worker access to:

* Original media
* Derivative directory
* Temporary directory
* Storage symlink
* Logs

Do not use global `777`.

Use correct ownership and least-required permissions.

---

# 67. Temporary Files

Use a controlled temporary directory.

Requirements:

* Application-owned path
* Cleanup after success
* Cleanup after failure
* Unique temporary names
* No user-controlled executable paths
* No storage outside approved directories

Do not leave large temporary files indefinitely.

---

# 68. Atomic File Writes

Generate to a temporary file, validate it and then move atomically where possible.

Do not expose partially written images publicly.

If generation fails, preserve the previous valid derivative.

---

# 69. Concurrency Safety

Test simultaneous processing attempts.

Verify:

* One job processes a given media/version
* No corrupt partial outputs
* Metadata remains consistent
* Locks expire safely
* Retry can recover after crash

---

# 70. Error Handling

Handle:

```text
Missing source
Unreadable source
Unsupported MIME
Corrupt file
Decoder error
Encoder error
Write failure
Permission failure
Out-of-memory risk
Timeout
Redis failure
Database failure
Duplicate job
```

Record concise, safe errors.

Do not expose full paths or credentials in UI.

---

# 71. Logging

Log useful context:

```text
Media ID
Processing version
Preset
Format
Source dimensions
Output dimensions
Duration
Output size
Status
Exception class
```

Do not log binary content.

Do not log sensitive filesystem details unnecessarily.

---

# 72. Metrics

Track lightweight metrics:

```text
Processed count
Success count
Failure count
Average duration
Bytes saved
Original bytes
Derivative bytes
Queue wait time
Retry count
Missing-source count
Unsupported count
```

Do not implement a full analytics platform.

---

# 73. Performance Baseline

Measure representative pages before implementation:

```text
Homepage
Article page
Category archive
Tag archive
Mobile viewport
Desktop viewport
```

Record:

* Total page weight
* Image transfer size
* Largest image size
* Number of image requests
* LCP
* CLS
* Image format served
* Unused image bytes
* Oversized-image warnings
* Response time
* Cache behavior

Use repeatable conditions.

---

# 74. Performance Comparison

After implementation, compare:

```text
Original image weight
Optimized image weight
Transfer reduction
LCP change
CLS change
Page-weight change
Variant-generation time
Queue throughput
Redis/cache impact
```

Do not claim improvement without evidence.

---

# 75. Core Web Vitals

Focus on:

```text
LCP
CLS
INP indirectly
```

Image improvements should mainly target:

* Hero-image delivery
* Correct intrinsic dimensions
* Responsive source selection
* Lazy loading below the fold
* Reduced transfer size

Do not sacrifice image quality excessively for synthetic scores.

---

# 76. Mobile Optimization

Verify on mobile:

* Correct image width selected
* No oversized desktop image downloaded unnecessarily
* No horizontal overflow
* Cards retain aspect ratio
* Hero image remains sharp
* Lazy loading works
* Fallback works
* Punjabi and Hindi layouts remain stable

---

# 77. Accessibility

Verify:

* Meaningful alt text
* Decorative images use empty alt
* No critical text embedded only inside image
* Fallback image does not mislead
* Loading states do not trap focus
* Admin actions have labels
* Error status is understandable

---

# 78. Automated Tests

Create focused automated tests.

## 78.1 Configuration Tests

Verify:

* Presets exist
* Widths are bounded
* Quality values are valid
* Feature flags have safe defaults
* Queue name exists
* Processing version exists
* Test storage is isolated

## 78.2 Metadata Tests

Verify:

* Dimensions are stored correctly
* MIME is detected
* Aspect ratio is calculated
* Processing status transitions correctly
* Errors are recorded safely

## 78.3 Variant Generation Tests

Verify:

* JPEG generates expected derivative
* PNG transparency is preserved where applicable
* WebP generates when supported
* AVIF tests skip honestly when unsupported
* No upscaling
* Correct dimensions
* Correct path
* Original remains unchanged
* Existing valid variant is reused
* Corrupt variant is rebuilt

## 78.4 Job Tests

Verify:

* Job dispatches to media queue
* Job dispatches after commit
* Unique scope is correct
* Overlap is prevented
* Retry values are correct
* Timeout is configured
* Missing source fails safely
* Duplicate execution remains idempotent

## 78.5 Responsive Component Tests

Verify:

* `<picture>` output
* AVIF source only when available
* WebP source only when available
* Original fallback exists
* `srcset` is valid
* `sizes` is present
* Width and height are correct
* Alt text is correct
* Lazy loading is correct
* Hero image is eager/high-priority where intended

## 78.6 Cache Invalidation Tests

Verify:

* Variant completion invalidates relevant article cache
* Featured-image update invalidates public pages
* No broad cache flush occurs
* Existing invalidation service is used

## 78.7 SEO Tests

Verify:

* OpenGraph image remains valid
* Schema image remains valid
* Google News image remains accessible
* Sitemap image URLs remain valid
* Absolute production URL generation remains correct

## 78.8 Security Tests

Verify:

* MIME spoofing is rejected
* Path traversal is rejected
* Oversized pixel dimensions are rejected
* Unauthorized reprocessing is denied
* SVG handling is safe
* Private filesystem path is not exposed
* External URL is not processed as local file

## 78.9 Command Tests

Verify:

* Audit command reports categories correctly
* Process command uses bounded defaults
* Dry-run performs no writes
* `--limit` works
* `--media` targets one record
* `--failed` targets failed media
* Cleanup defaults to dry-run
* Unknown files are not deleted

## 78.10 Regression Tests

Verify:

* Existing media URLs still work
* Existing featured images still render
* Article pages remain correct
* Homepage remains correct
* Archive pages remain correct
* Login remains correct
* Filament remains correct
* Editorial workflow remains correct
* Scheduled publishing remains correct
* Cache remains correct
* Queue remains correct
* SEO remains correct
* Legacy redirects remain correct
* WordPress importer remains compatible

---

# 79. Real Processing Verification

After implementation, verify with representative real images:

```text
Small JPEG
Large JPEG
PNG with transparency
Portrait phone image
Existing WebP
Animated GIF
Missing file
Corrupt file
Imported WordPress image
```

Do not run uncontrolled processing across all production media.

Use a safe limited sample first.

---

# 80. Browser Verification

Verify rendered markup and behavior in at least:

```text
Chrome
Mobile Chrome viewport
Firefox where available
Safari compatibility through markup review
```

Verify:

* Correct format selection
* Fallback behavior
* Lazy loading
* No layout shift
* No broken image
* Correct hero priority

---

# 81. Operational Commands

Possible commands:

```bash
php artisan images:audit
php artisan images:process --missing --limit=25
php artisan images:process --failed --limit=25
php artisan images:process --media=123
php artisan images:process --all --dry-run
php artisan images:cleanup --dry-run
php artisan queue:health
php artisan queue:failed
```

Adapt to actual implementation.

Do not run full production reprocessing without explicit operational approval.

---

# 82. Production Rollout Strategy

Use staged rollout.

Recommended stages:

```text
Stage 1:
Audit only

Stage 2:
Metadata collection

Stage 3:
Generate WebP for a small sample

Stage 4:
Enable responsive component on one low-risk card

Stage 5:
Enable archive cards

Stage 6:
Enable article featured images

Stage 7:
Enable homepage images

Stage 8:
Process recent published media

Stage 9:
Process older media in bounded queue batches

Stage 10:
Evaluate AVIF separately
```

Do not convert all images at once.

---

# 83. Deployment Procedure

Recommended sequence:

```text
1. Verify Redis and queue health
2. Verify media worker
3. Deploy code
4. Run safe additive migrations
5. Build configuration cache
6. Verify storage permissions
7. Verify storage symlink
8. Run image audit
9. Process a controlled sample
10. Verify frontend markup
11. Verify SEO image URLs
12. Monitor worker memory and failures
13. Expand rollout gradually
```

---

# 84. Rollback Plan

Document rollback for:

* Broken image markup
* Missing derivatives
* Excessive CPU usage
* Excessive memory usage
* Queue backlog
* Incorrect crops
* SEO image regression
* Storage growth
* Permission failure
* Corrupt derivative generation

Possible rollback:

```text
Disable responsive-image feature flag
Render original image only
Stop media worker
Disable AVIF
Disable background processing
Revert Blade component usage
Invalidate affected application cache
Revert application commit
Keep original media intact
```

Do not delete originals during rollback.

---

# 85. Storage Growth Monitoring

Estimate and monitor:

* Original storage size
* Generated WebP size
* Generated AVIF size
* Variant count
* Daily storage growth
* Temporary storage use
* Orphaned derivative count

Do not create every preset for every media file without usage justification.

---

# 86. Documentation Deliverables

Create or update:

```text
docs/version-2.1/phase-2.1-i-image-optimization.md
docs/version-2.1/image-existing-state-audit.md
docs/version-2.1/image-format-support.md
docs/version-2.1/image-preset-standard.md
docs/version-2.1/image-storage-architecture.md
docs/version-2.1/image-processing-pipeline.md
docs/version-2.1/image-queue-runbook.md
docs/version-2.1/responsive-image-component.md
docs/version-2.1/image-lazy-loading-standard.md
docs/version-2.1/image-seo-compatibility.md
docs/version-2.1/image-health-monitoring.md
docs/version-2.1/image-production-rollout.md
docs/version-2.1/image-rollback-plan.md
docs/version-2.1/image-performance-baseline.md
docs/version-2.1/image-performance-comparison.md
```

Documentation must include:

* Existing media audit
* Library decision
* Format support
* Presets
* Quality settings
* Storage paths
* Metadata design
* Queue behavior
* Retry and timeout
* Responsive markup
* Lazy-loading rules
* Priority-loading rules
* Missing-image fallback
* SEO compatibility
* Cache integration
* Security safeguards
* Operational commands
* Production rollout
* Rollback
* Performance results
* Test results
* Known limitations
* Deferred items

Do not include secrets.

---

# 87. Completion Criteria

Phase 2.1-I is complete only when:

* Existing media architecture is audited.
* Original images remain preserved.
* Image-processing library decision is documented.
* GD or Imagick capabilities are verified.
* WebP generation is implemented and tested where supported.
* AVIF is enabled only when actually supported.
* Responsive presets are centralized.
* Derivative paths are deterministic.
* Processing is idempotent.
* Duplicate processing is prevented.
* Image-processing jobs use the media queue safely.
* Retry and timeout settings are defined.
* Memory and pixel limits exist.
* Responsive image component exists.
* Srcset and sizes are correct.
* Width and height attributes are rendered.
* Offscreen images use lazy loading.
* LCP images avoid lazy loading and use appropriate priority.
* Missing images have safe fallback behavior.
* Existing media URLs remain compatible.
* Featured media mappings remain unchanged.
* SEO image metadata remains correct.
* Google News image behavior remains correct.
* Cache invalidation is integrated.
* Image audit and processing commands exist.
* Cleanup defaults to dry-run.
* A controlled real-image sample is verified.
* Performance is measured before and after.
* Focused tests are executed.
* Full regression result is reported honestly.
* Required documentation is complete.

---

# 88. Deferred Items

Do not implement in this phase:

* AI image generation
* Automatic image-caption generation
* Automatic image copyright verification
* Video transcoding
* Audio processing
* CDN migration
* S3 migration
* Cloudflare Images
* Imgix
* Dedicated image proxy
* Public frontend redesign
* Search replacement
* Analytics collection
* News-Man integration
* Destructive replacement of original media
* Full production media reprocessing without approval

---

# 89. Required Completion Report Format

Return the completion report using this exact structure:

## 1. Executive Summary

## 2. Existing Media Audit

## 3. Existing Storage and URL Architecture

## 4. Image Processing Library Decision

## 5. GD and Imagick Capability Verification

## 6. Supported Image Formats

## 7. Source Image Preservation

## 8. Image Metadata Architecture

## 9. Variant Metadata Architecture

## 10. Image Preset Standard

## 11. Responsive Width Standard

## 12. Aspect Ratio and Crop Strategy

## 13. WebP Implementation

## 14. AVIF Decision and Implementation

## 15. PNG, GIF and SVG Handling

## 16. Derivative Storage Architecture

## 17. Filename and Versioning Strategy

## 18. Image Optimization Service

## 19. Image Queue Architecture

## 20. Processing Job Implementation

## 21. Idempotency and Duplicate Prevention

## 22. Retry, Backoff and Timeout

## 23. Memory and Pixel Safety

## 24. Media Upload Integration

## 25. Imported WordPress Media Integration

## 26. Featured Media Integration

## 27. Article Content Image Assessment

## 28. Responsive Image Component

## 29. Picture, Srcset and Sizes Output

## 30. Width and Height Attributes

## 31. Lazy-Loading Implementation

## 32. LCP and Priority-Loading Strategy

## 33. Placeholder and Missing-Image Strategy

## 34. Alt-Text Strategy

## 35. SEO and OpenGraph Compatibility

## 36. Google News Image Compatibility

## 37. Sitemap Image Compatibility

## 38. Browser Cache Headers

## 39. Cache Invalidation Integration

## 40. Filament Media UI Improvements

## 41. Image Health Diagnostics

## 42. Image Audit Command

## 43. Image Processing Command

## 44. Cleanup Command and Safety

## 45. Security Verification

## 46. Real Image Processing Verification

## 47. Browser Verification

## 48. Performance Baseline

## 49. Performance Comparison

## 50. Storage Impact

## 51. Automated Tests Added or Updated

## 52. Focused Image Test Results

## 53. Queue Integration Test Results

## 54. SEO Test Results

## 55. Security Test Results

## 56. Regression Test Results

## 57. Full Test-Suite Result

## 58. Production Rollout Procedure

## 59. Rollback Procedure

## 60. Backward-Compatibility Verification

## 61. Documentation Created

## 62. Files Created or Modified

## 63. Commands Executed

## 64. Risks and Open Questions

## 65. Deferred Items

## 66. Final Phase Decision

The final phase decision must be one of:

```text
COMPLETE
COMPLETE WITH CONDITIONS
INCOMPLETE
```

Explain the decision using verified evidence.

---

# 90. Strict Rules

* Audit before processing images.
* Preserve every original image.
* Do not rename or overwrite original files.
* Do not alter featured-media mappings.
* Do not break existing media URLs.
* Do not process unsupported files blindly.
* Verify MIME type, not only extension.
* Do not upscale images.
* Prevent decompression bombs.
* Use bounded dimensions and pixel counts.
* Use queue-based processing for expensive work.
* Make processing jobs idempotent.
* Prevent duplicate processing.
* Use deterministic derivative paths.
* Do not reference derivative files that do not exist.
* Provide original-format fallback.
* Do not lazy-load the main LCP image.
* Do not assign high fetch priority to many images.
* Render correct width and height.
* Preserve PNG transparency.
* Preserve animated GIF originals.
* Do not process SVG unsafely.
* Do not rewrite stored article HTML destructively.
* Do not delete WordPress uploads.
* Cleanup must default to dry-run.
* Do not use `Cache::flush()`.
* Do not use `FLUSHALL`.
* Do not use `FLUSHDB`.
* Do not overload the VPS with excessive workers.
* Do not modify `.env` without deployment authority.
* Do not change editorial workflow.
* Do not change roles or permissions.
* Do not change post slugs or public URLs.
* Do not break SEO, OpenGraph, Schema or Google News.
* Do not run destructive database commands.
* Do not upgrade unrelated dependencies.
* Do not claim AVIF support without real verification.
* Do not claim performance improvement without measurements.
* Do not claim queue processing works using only fakes.
* Clearly report skipped, environmental and pre-existing failures.
* Preserve backward compatibility.
