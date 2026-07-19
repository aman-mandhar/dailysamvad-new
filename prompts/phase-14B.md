# Phase 14B — OpenGraph and Twitter/X Cards

## Objective

Implement complete social-sharing metadata for all public pages of the Laravel news portal.

Phase 14A already established the native SEO foundation. Phase 14B must extend the existing SEO architecture instead of creating a second or competing metadata system.

The implementation must support correct social previews on:

* Facebook
* WhatsApp
* LinkedIn
* X
* Telegram
* Other platforms that consume OpenGraph metadata

Implement only Phase 14B.

Do not implement Schema.org, sitemaps, robots.txt changes, Google News optimization, or any Phase 14C functionality.

---

## 1. Initial Architecture Audit

Before modifying files, inspect the existing implementation created during Phase 14A.

Audit:

* Existing SEO classes and DTOs
* SEO manager or resolver
* Reusable SEO Blade component or partial
* Main public layout
* Existing metadata configuration
* Post and article models
* Featured-image fields
* Media relationships
* Category relationships
* Tag relationships
* Author relationships
* Homepage route and query
* Article route and query
* Category archive
* Tag archive
* Author archive
* Date archive
* Search pages
* Static pages
* Existing OpenGraph tags
* Existing Twitter tags
* Existing SEO tests

Reuse and extend the existing Phase 14A architecture.

Do not create:

* A second SEO manager
* A parallel metadata service
* Duplicate Blade metadata components
* Per-page OpenGraph markup
* Per-page Twitter markup
* Duplicate configuration systems

If OpenGraph or Twitter metadata already exists, audit it and improve it instead of blindly duplicating it.

---

## 2. Architecture Requirements

Social metadata must be resolved through the native SEO layer.

A suitable architecture may include:

```text
app/
    SEO/
        SEOManager.php
        SEOData.php
        OpenGraphData.php
        TwitterCardData.php
        SocialImage.php
```

These names are examples only.

Use the existing Phase 14A class names and structure wherever possible.

The architecture should provide one resolved SEO object containing:

* Standard metadata
* Canonical URL
* OpenGraph metadata
* Twitter/X card metadata
* Social image information
* Article-specific social metadata

SEO logic must not be placed directly inside controllers or Blade templates.

Controllers should continue passing page models or page data to the view.

The SEO layer should resolve metadata from that existing page data.

---

## 3. OpenGraph Metadata

Generate the following tags when valid data is available:

```html
<meta property="og:title" content="">
<meta property="og:description" content="">
<meta property="og:url" content="">
<meta property="og:type" content="">
<meta property="og:site_name" content="">
<meta property="og:locale" content="">
<meta property="og:image" content="">
<meta property="og:image:secure_url" content="">
<meta property="og:image:type" content="">
<meta property="og:image:width" content="">
<meta property="og:image:height" content="">
<meta property="og:image:alt" content="">
```

Do not render tags with empty values.

Do not render duplicate OpenGraph tags.

All metadata values must be escaped safely.

---

## 4. Article-Specific OpenGraph Metadata

Individual published article pages must additionally support:

```html
<meta property="article:published_time" content="">
<meta property="article:modified_time" content="">
<meta property="article:author" content="">
<meta property="article:publisher" content="">
<meta property="article:section" content="">
<meta property="article:tag" content="">
```

Render one `article:tag` tag for each relevant tag.

Requirements:

* Remove duplicate tags
* Strip HTML from values
* Do not render empty values
* Do not expose author email addresses
* Do not expose private author information
* Do not invent author URLs
* Do not invent publisher URLs
* Do not generate article metadata for non-article pages

---

## 5. OpenGraph Type Rules

Use:

```text
article
```

for an individual published news article.

Use:

```text
website
```

for:

* Homepage
* Category archives
* Tag archives
* Author archives
* Date archives
* Search pages
* Static pages
* Other non-article public pages

Do not use `article` for archive pages.

Do not expose draft, scheduled, private, or unpublished posts as public articles.

---

## 6. OpenGraph Title Resolution

Resolve the OpenGraph title using this priority:

1. Existing custom social title, when already supported
2. Custom SEO title from Phase 14A
3. Article or page title
4. Archive title
5. Site name

Requirements:

* Strip HTML
* Decode HTML entities
* Normalize whitespace
* Avoid duplicated site names
* Avoid empty titles
* Preserve Unicode text
* Escape the final value in Blade

Do not add a new database column only for social titles unless absolutely required by the current architecture.

Prefer existing SEO fields and fallback rules.

---

## 7. OpenGraph Description Resolution

Resolve the OpenGraph description using this priority:

1. Existing custom social description, when already supported
2. Custom SEO description
3. Article excerpt
4. Clean summary generated from article content
5. Archive description
6. Site description
7. Safe site-name fallback

Requirements:

* Strip HTML
* Decode HTML entities
* Remove shortcodes when relevant
* Normalize whitespace
* Avoid broken markup
* Avoid JavaScript or CSS content
* Avoid duplicate site names
* Avoid empty descriptions
* Preserve valid Unicode
* Produce a readable social-preview description

Do not render raw article HTML inside a metadata attribute.

Reuse the description-cleaning logic from Phase 14A where possible.

Do not create a second content-summary implementation.

---

## 8. Canonical OpenGraph URL

The `og:url` value must reuse the canonical URL resolved by Phase 14A.

Do not independently generate a conflicting URL.

Requirements:

* URL must be absolute
* URL must use the configured application host
* Production URLs should use HTTPS
* Remove fragments
* Remove invalid tracking parameters
* Avoid duplicate trailing slash variations
* Preserve valid pagination when pagination has its own canonical URL
* Avoid development URLs in production
* Respect the application’s configured canonical host

Do not hardcode the domain name.

---

## 9. Social Image Resolution

Implement a single social-image resolver inside the SEO layer.

Use this fallback order:

1. Article featured image
2. Article primary media image
3. Contextual page image
4. Category image
5. Author image for author pages, when appropriate
6. Configured default social-sharing image
7. Configured site logo as the final fallback

Do not use unrelated article-body images unless the existing architecture already defines one as the primary media image.

Do not randomly select images from media collections.

Do not trigger unnecessary queries to find an image.

Prefer already-loaded fields and relationships.

---

## 10. Social Image URL Handling

All social image URLs must be:

* Absolute
* Publicly accessible
* Correctly encoded
* Compatible with local and production environments
* HTTPS in production
* Free from local filesystem paths
* Free from Windows paths
* Free from private storage paths

Correctly handle:

* Existing absolute HTTP URLs
* Existing absolute HTTPS URLs
* Laravel public-path images
* Laravel storage URLs
* Imported WordPress media paths
* External image URLs
* Relative image paths
* Leading slash paths
* URL-encoded filenames
* Spaces in filenames
* Unicode filenames where supported
* Missing image files
* Empty image fields

Do not prepend the application URL to an image URL that is already absolute.

Examples of invalid output:

```text
C:\laragon\www\project\storage\image.jpg
```

```text
/home/forge/example.com/storage/image.jpg
```

```text
https://example.com/https://old-domain.com/image.jpg
```

The resolver must avoid these cases.

---

## 11. Default Social Image Configuration

Add or extend an SEO configuration value for the default social image.

Example:

```php
'default_social_image' => env(
    'SEO_DEFAULT_SOCIAL_IMAGE',
    '/images/default-social.jpg'
),
```

Use the current project’s existing configuration structure.

Do not create another configuration file if Phase 14A already introduced one.

Requirements:

* Do not hardcode the production domain
* Provide a safe default
* Support an absolute external URL
* Support a relative public-path URL
* Document the environment variable in `.env.example`
* Do not modify the actual `.env`
* The application must continue working when the environment variable is missing

---

## 12. Social Image Dimensions

Preferred OpenGraph image dimensions are:

```text
1200 × 630 pixels
```

Do not falsely output these dimensions for every image.

Only render:

```html
<meta property="og:image:width" content="">
<meta property="og:image:height" content="">
```

when the dimensions are reliably known.

Reliable sources may include:

* Existing media metadata
* Existing image dimension columns
* Known configured default-image dimensions
* Existing media DTO values

Do not inspect image files on every page request.

Do not make remote requests to inspect external images.

Do not download images during page rendering.

Do not introduce expensive runtime image processing.

---

## 13. Social Image MIME Type

Render:

```html
<meta property="og:image:type" content="">
```

only when the MIME type is reliably known.

Supported values may include:

```text
image/jpeg
image/png
image/webp
image/gif
```

A tested extension-based resolver may be used as a fallback.

Handle URLs containing query strings.

Do not return an invalid MIME type.

Do not guess MIME types from unrelated metadata.

---

## 14. Social Image Alt Text

Resolve image alt text using this priority:

1. Existing media alt text
2. Featured-image alt text
3. Article title
4. Page title
5. Archive title
6. Site name

Requirements:

* Strip HTML
* Normalize whitespace
* Do not render an empty alt tag
* Keep it meaningful
* Do not use raw filenames as the preferred alt text
* Escape the final value safely

Render:

```html
<meta property="og:image:alt" content="">
```

and:

```html
<meta name="twitter:image:alt" content="">
```

when valid alt text exists.

---

## 15. OpenGraph Locale

Generate a valid OpenGraph locale based on the configured application or page locale.

Examples:

```text
en_IN
hi_IN
pa_IN
```

Convert Laravel-style locale values when necessary.

Examples:

```text
en-IN → en_IN
pa-IN → pa_IN
```

Requirements:

* Do not blindly hardcode `en_US`
* Use the application’s configured locale
* Support the current page locale when existing multilingual support provides it
* Do not build a new multilingual system
* Do not output malformed locale values
* Do not output an empty `og:locale`

Support alternate locales only if the application already provides multilingual public URLs.

Do not invent alternate-language pages.

---

## 16. Site Name

Use the configured site name for:

```html
<meta property="og:site_name" content="">
```

Do not hardcode the portal name in multiple files.

Reuse the existing Phase 14A configuration.

---

## 17. Publisher Metadata

Support:

```html
<meta property="article:publisher" content="">
```

only when a valid publisher URL is configured.

The value should normally be the site’s official Facebook page URL when available.

Requirements:

* Must be a valid absolute URL
* Do not invent a URL
* Do not output an empty value
* Do not output a personal profile unless it is intentionally configured
* Add configuration only when consistent with the existing SEO config

Document any new environment variable in `.env.example`.

---

## 18. Article Publication Dates

For article pages, resolve:

```html
<meta property="article:published_time" content="">
<meta property="article:modified_time" content="">
```

Use ISO 8601 format.

Example:

```text
2026-07-19T18:30:00+05:30
```

Requirements:

* Use the actual publication timestamp
* Use the meaningful last-updated timestamp
* Respect the application timezone
* Do not use the current request time
* Do not output invalid dates
* Do not output a future publication time for unpublished content
* Omit values when no reliable date is available

---

## 19. Article Author Metadata

For public article pages, render:

```html
<meta property="article:author" content="">
```

only when a valid public author URL exists.

Requirements:

* Use the public author archive URL
* Do not output an email address
* Do not output an admin URL
* Do not expose internal user IDs
* Do not expose private profile data
* Do not invent an author URL
* Omit the tag when no public author route exists

Use already-loaded author data.

Avoid triggering an extra author query.

---

## 20. Article Section

Render:

```html
<meta property="article:section" content="">
```

using the article’s primary category.

If no explicit primary category exists, use the first appropriate category according to the existing article-query ordering.

Requirements:

* Strip HTML
* Remove excess whitespace
* Do not output multiple section tags
* Do not output an empty section
* Do not trigger an additional category query

---

## 21. Article Tags

Render one tag per article tag:

```html
<meta property="article:tag" content="Punjab">
<meta property="article:tag" content="Politics">
```

Requirements:

* Remove duplicates case-insensitively
* Preserve original readable capitalization
* Strip HTML
* Normalize whitespace
* Ignore empty values
* Do not output unrelated keywords
* Avoid N+1 queries
* Use the existing loaded tags relationship

---

## 22. Twitter/X Card Metadata

Generate:

```html
<meta name="twitter:card" content="">
<meta name="twitter:title" content="">
<meta name="twitter:description" content="">
<meta name="twitter:image" content="">
<meta name="twitter:image:alt" content="">
```

When valid configuration exists, also generate:

```html
<meta name="twitter:site" content="">
<meta name="twitter:creator" content="">
```

Do not render empty tags.

Do not render duplicate Twitter tags.

Twitter metadata should reuse the resolved social metadata wherever possible.

Do not create separate title, description, image, and URL fallback logic unless a platform-specific override already exists.

---

## 23. Twitter Card Type

Use:

```text
summary_large_image
```

when a valid large social image exists.

Use:

```text
summary
```

when no suitable large image is available.

Do not output an invalid card type.

Do not always assume that an image is a valid 1200 × 630 image.

The implementation may use `summary_large_image` whenever a proper social image is configured or resolved, provided the image is appropriate for a large preview.

---

## 24. Twitter Title and Description

Twitter title and description should normally reuse:

* OpenGraph title
* OpenGraph description

Do not maintain duplicate fallback chains.

Allow platform-specific values only when the existing architecture already supports them.

Requirements:

* Safe escaping
* No HTML
* No empty values
* No duplicate site name
* Valid Unicode
* Normalized whitespace

---

## 25. Twitter Image

Twitter image should reuse the resolved social image.

Requirements:

* Must be absolute
* Must be publicly accessible
* Must not be a filesystem path
* Must not be prefixed twice
* Must use HTTPS in production
* Must not be empty

Render:

```html
<meta name="twitter:image" content="">
```

only when a valid image exists.

---

## 26. Twitter Handle Configuration

Support:

```html
<meta name="twitter:site" content="@handle">
```

and, when available:

```html
<meta name="twitter:creator" content="@authorhandle">
```

Handle normalization rules:

* Add one leading `@`
* Remove duplicate `@` characters
* Accept a plain username
* Extract a username from a valid X/Twitter profile URL when safe
* Remove whitespace
* Reject clearly invalid values
* Do not invent handles

Examples:

```text
DailySamvad → @DailySamvad
@DailySamvad → @DailySamvad
@@DailySamvad → @DailySamvad
https://x.com/DailySamvad → @DailySamvad
```

Do not add author social fields or migrations unless such fields already exist and are appropriate.

If no author handle exists, omit `twitter:creator`.

Document site-handle configuration in `.env.example` when a new configuration value is added.

---

## 27. Metadata Reuse

Phase 14B must reuse Phase 14A values for:

* SEO title
* SEO description
* Canonical URL
* Site name
* Page type context
* Robots behavior

OpenGraph and Twitter metadata must not conflict with standard metadata.

Examples:

* `og:url` should match the canonical URL
* `twitter:title` should normally match `og:title`
* `twitter:description` should normally match `og:description`
* `twitter:image` should normally match `og:image`

Use a single resolved metadata object or related DTOs.

Avoid recalculating cleaned descriptions multiple times.

---

## 28. Blade Rendering

Extend the reusable SEO Blade component or partial created in Phase 14A.

The main public layout should continue rendering all SEO metadata once.

Do not insert social metadata directly into:

* Homepage Blade files
* Article Blade files
* Category Blade files
* Tag Blade files
* Author Blade files
* Search Blade files
* Archive Blade files
* Static-page Blade files

Blade requirements:

* Escape all content attributes
* Do not use raw unescaped metadata
* Do not output empty tags
* Do not output duplicate tags
* Do not output conflicting values
* Keep metadata inside `<head>`
* Preserve the existing frontend body markup
* Preserve the existing CSS and JavaScript asset order unless technically necessary

---

## 29. Homepage Metadata

The homepage should produce:

```text
og:type = website
```

Expected metadata:

* Site or homepage title
* Site description
* Canonical homepage URL
* Site name
* Valid locale
* Default social image
* Image alt text
* Twitter card
* Twitter title
* Twitter description
* Twitter image when available
* Twitter site handle when configured

Do not use article metadata on the homepage.

---

## 30. Article Page Metadata

Published article pages should produce:

```text
og:type = article
```

Expected metadata:

* Article title
* Article description
* Canonical article URL
* Site name
* Valid locale
* Featured or fallback social image
* Image type when known
* Image dimensions when known
* Image alt text
* Published time
* Modified time
* Public author URL when available
* Publisher URL when configured
* Primary category section
* Repeated article tags
* Twitter/X card metadata

The implementation must not leak draft information.

---

## 31. Category Archive Metadata

Category archive pages should produce:

```text
og:type = website
```

Expected metadata:

* Category archive title
* Category description or safe fallback
* Canonical category URL
* Site name
* Locale
* Category image when available
* Default image fallback
* Twitter/X card metadata

Do not use the first post’s image as the category image unless the existing SEO architecture explicitly defines that behavior.

---

## 32. Tag Archive Metadata

Tag archive pages should produce:

```text
og:type = website
```

Expected metadata:

* Tag archive title
* Tag description or safe fallback
* Canonical tag URL
* Site name
* Locale
* Default or contextually configured image
* Twitter/X card metadata

Do not emit article-specific metadata.

---

## 33. Author Archive Metadata

Author archive pages should produce:

```text
og:type = website
```

Expected metadata:

* Author archive title
* Author biography or safe description fallback
* Canonical author URL
* Site name
* Locale
* Public author image when appropriate
* Default social-image fallback
* Twitter/X card metadata

Do not expose:

* Author email
* Private phone number
* Internal profile fields
* Admin URLs
* User IDs

---

## 34. Date Archive Metadata

Date archive pages should produce:

```text
og:type = website
```

Expected metadata:

* Date-specific archive title
* Safe description
* Canonical date archive URL
* Site name
* Locale
* Default social image
* Twitter/X card metadata

Use the application’s configured date formatting where appropriate.

Do not introduce new date archive routes.

If date archives do not exist, state that clearly in the completion report.

---

## 35. Search Page Metadata

Search pages should produce:

```text
og:type = website
```

Expected metadata:

* Search-specific title
* Search-specific description
* Canonical search URL according to Phase 14A rules
* Site name
* Locale
* Default social image
* Twitter/X card metadata

Security requirements:

* Safely escape the search term
* Strip HTML from the search term
* Prevent script injection
* Handle quotes safely
* Handle Unicode safely
* Do not expose internal request values
* Do not include unrelated query parameters

Preserve Phase 14A robots behavior for search pages.

Do not change index/noindex policy unless Phase 14A is clearly incorrect and a tested fix is necessary.

---

## 36. Static Page Metadata

For existing public static pages, produce:

```text
og:type = website
```

Expected metadata:

* Page title
* Page description
* Canonical page URL
* Site name
* Locale
* Page image when available
* Default image fallback
* Twitter/X card metadata

Do not create a new static-page system.

If static pages do not exist, state that in the completion report.

---

## 37. URL Security and Validation

Metadata URL resolvers must reject or safely handle malformed values.

Protect against:

* `javascript:` URLs
* `data:` URLs when inappropriate
* Empty strings
* Invalid schemes
* Double-prefixed domains
* Filesystem paths
* Backslashes
* Control characters
* HTML fragments
* Broken URL encoding

Allowed image URL schemes should normally be:

```text
http
https
```

Relative paths may be converted to absolute application URLs.

Do not render unsafe URL schemes.

---

## 38. Escaping and Content Security

All values rendered inside metadata attributes must be safely escaped.

Protect against:

* Quotes inside titles
* Quotes inside descriptions
* HTML tags
* Inline JavaScript
* Malicious search queries
* Malformed Unicode
* Invalid control characters
* Excess whitespace
* Encoded script payloads

Do not use `{!! !!}` for metadata attributes.

Use normal escaped Blade output.

---

## 39. Performance Requirements

The implementation must:

* Reuse the model already loaded for the page
* Reuse eager-loaded relationships
* Avoid duplicate database queries
* Avoid N+1 queries
* Avoid remote HTTP calls
* Avoid image downloads
* Avoid per-request filesystem image inspection
* Avoid expensive repeated HTML parsing
* Avoid loading complete media galleries only for SEO
* Avoid loading every tag or category globally
* Reuse Phase 14A title and description resolution
* Reuse Phase 14A canonical resolution

Do not globally eager-load heavy relationships merely for social metadata.

When a relationship is needed, extend the relevant existing page query intentionally and minimally.

Document any query changes in the completion report.

---

## 40. Caching

Do not introduce broad page caching in Phase 14B.

The metadata implementation must remain compatible with existing application caching.

If existing SEO metadata is cached, ensure it invalidates when relevant content changes, including:

* Article title
* SEO title
* SEO description
* Excerpt
* Content
* Featured image
* Category
* Tags
* Author
* Published timestamp
* Updated timestamp
* Publication status

Prefer not to add new metadata caching unless the existing Phase 14A architecture already includes it.

---

## 41. Database Restrictions

Do not add database columns unless absolutely necessary.

First inspect existing fields for:

* SEO title
* Meta description
* Meta keywords
* Excerpt
* Featured image
* Image alt text
* Author
* Categories
* Tags
* Site settings
* Social-profile settings
* Media dimensions
* MIME types

Prefer fallback rules over schema changes.

Any unavoidable migration must be:

* Minimal
* Backward-compatible
* Justified
* Tested
* Included in the completion report

Do not add custom OpenGraph title, description, or image fields unless they are clearly required by the existing architecture.

---

## 42. Admin Restrictions

Do not redesign or extend the Filament admin panel during Phase 14B.

Do not add:

* Social-preview editors
* Facebook-specific fields
* X-specific fields
* New media selectors
* New author-profile fields
* New site-settings screens

Such improvements belong to later admin phases.

Use existing data and configuration.

---

## 43. Automated Testing Requirements

Add focused automated tests for the social metadata implementation.

At minimum test the following:

1. Homepage renders `og:type=website`
2. Homepage renders OpenGraph title
3. Homepage renders OpenGraph description
4. Homepage renders canonical URL as `og:url`
5. Homepage renders default social image
6. Homepage renders Twitter card metadata
7. Article renders `og:type=article`
8. Article renders the resolved article title
9. Article renders the resolved article description
10. Article `og:url` matches the canonical URL
11. Article featured image is rendered as an absolute URL
12. Existing absolute image URL is not prefixed twice
13. Relative public image path becomes an absolute URL
14. Storage image path becomes a public absolute URL
15. Missing featured image uses the configured default image
16. Article renders `article:published_time`
17. Article renders `article:modified_time`
18. Article renders author URL when valid
19. Author email is not rendered
20. Article renders one category as `article:section`
21. Article renders repeated `article:tag` entries
22. Duplicate article tags are removed
23. Empty article tags are ignored
24. Twitter card is `summary_large_image` when a social image exists
25. Twitter card falls back safely when no image exists
26. Twitter title reuses the resolved social title
27. Twitter description reuses the resolved social description
28. Twitter image reuses the resolved social image
29. Twitter site handle is normalized
30. Invalid Twitter handle is omitted
31. Empty metadata tags are not rendered
32. Metadata values are escaped safely
33. Quotes inside a title do not break HTML
34. HTML inside an excerpt is removed
35. Search terms containing HTML are safely escaped
36. Category archive renders correct social metadata
37. Tag archive renders correct social metadata
38. Author archive renders correct social metadata
39. Date archive renders correct metadata when supported
40. Static page renders correct metadata when supported
41. No duplicate `og:title` tags exist
42. No duplicate `og:description` tags exist
43. No duplicate `og:url` tags exist
44. No duplicate `og:image` tags exist
45. No duplicate `twitter:card` tags exist
46. No duplicate `twitter:title` tags exist
47. Draft or unpublished content does not expose public article metadata
48. Invalid social-image schemes are rejected
49. Filesystem paths are not rendered as image URLs
50. Existing Phase 14A metadata tests continue passing

Prefer unit tests for resolvers and feature tests for rendered page metadata.

Do not rely only on fragile full-page HTML snapshots.

Use DOM parsing or targeted assertions where practical.

---

## 44. Query Validation

For relevant page types, inspect database-query behavior.

Confirm that social metadata does not introduce:

* One query per tag
* One query per category
* One query per author
* One query per image
* Repeated article queries
* Repeated settings queries

Where existing tests support query counting, add focused checks.

Do not introduce complicated query-count tests when the project has no stable query-count infrastructure.

At minimum, audit and report query impact honestly.

---

## 45. Manual Validation

Inspect rendered page source for:

* Homepage
* One article with a featured image
* One article without a featured image
* One article containing categories and tags
* Category archive
* Tag archive
* Author archive
* Date archive, when available
* Search page
* Static page, when available

Confirm:

* Metadata is inside `<head>`
* OpenGraph tags appear once
* Twitter tags appear once
* No empty tags appear
* No filesystem paths appear
* No malformed image URLs appear
* All applicable URLs are absolute
* Production configuration does not output localhost
* Titles are readable
* Descriptions are readable
* HTML entities are escaped
* Unicode text renders correctly
* Published dates use ISO 8601
* Article tags are not duplicated
* Non-article pages use `website`
* Article pages use `article`

---

## 46. External Validator Guidance

The implementation should be suitable for validation with:

* Facebook Sharing Debugger
* LinkedIn Post Inspector
* OpenGraph validators
* WhatsApp link-preview testing
* Telegram preview testing
* X card preview tools, where available

Do not claim that an external validator passed unless validation was actually performed against a publicly reachable URL.

External crawlers cannot validate localhost URLs.

If implementation is tested only locally, state this clearly in the completion report.

Do not delay Phase 14B completion solely because a public deployment is unavailable.

---

## 47. Protected Boundaries

Do not modify or regress:

* Existing frontend design
* Public body HTML structure
* CSS
* JavaScript
* Header
* Navigation
* Breaking-news ticker
* Hero section
* Homepage category sections
* Article-body layout
* Sidebar
* Advertisement system
* Livewire architecture
* Existing page-query architecture
* WordPress importer
* Authentication
* Authorization
* Filament resources
* Existing media-library functionality
* Existing author functionality
* Phase 14A title behavior
* Phase 14A description behavior
* Phase 14A canonical behavior
* Phase 14A robots behavior
* Previous phase functionality

Do not begin Phase 14C.

---

## 48. Required Commands

Run the commands appropriate to the project.

At minimum:

```bash
php artisan test
php artisan route:list
php artisan config:clear
php artisan cache:clear
php artisan optimize
```

Run targeted SEO tests separately when possible.

Examples:

```bash
php artisan test --filter=Seo
```

```bash
php artisan test --filter=OpenGraph
```

```bash
php artisan test --filter=Twitter
```

Use the project’s actual test names.

Do not invent passing results.

If `php artisan optimize` creates an issue in the local testing environment:

1. Record the exact error
2. Restore the application to a working state
3. Report the issue honestly

---

## 49. Completion Criteria

Phase 14B is complete only when:

* OpenGraph metadata is implemented through the existing native SEO layer
* Twitter/X card metadata is implemented through the same SEO layer
* Metadata is rendered once through the shared Blade SEO component
* Article pages use `og:type=article`
* Non-article pages use `og:type=website`
* `og:url` reuses the Phase 14A canonical URL
* Social images follow the required fallback order
* Image URLs are absolute and safe
* Default social-image configuration exists
* Article published and modified dates are supported
* Article author metadata is safe
* Article section metadata is supported
* Article tag metadata is supported
* Twitter card type is resolved correctly
* Twitter handles are normalized safely
* No empty metadata tags are rendered
* No duplicate metadata tags are rendered
* Metadata values are safely escaped
* No filesystem paths are exposed
* No unnecessary database queries are introduced
* Automated tests pass
* Phase 14A tests continue passing
* Protected boundaries remain intact
* Phase 14C has not been started

---

## 50. Required Completion Report

Return a structured completion report with the following sections.

### 1. Phase 14B Summary

Summarize what was implemented.

### 2. Existing Architecture Audit

Describe:

* Existing Phase 14A architecture
* Existing SEO manager or DTOs
* Existing Blade metadata renderer
* Existing page-context handling
* How Phase 14B extended the existing implementation

### 3. Files Created

List every created file and explain its purpose.

### 4. Files Modified

List every modified file and summarize the exact change.

### 5. OpenGraph Implementation

Document:

* Supported tags
* Page-type resolution
* Article-specific metadata
* Locale handling
* Site-name handling
* Publisher handling

### 6. Twitter/X Implementation

Document:

* Card-type selection
* Title resolution
* Description resolution
* Image resolution
* Site-handle resolution
* Creator-handle resolution

### 7. Metadata Resolution Rules

Document the final fallback order for:

* Social title
* Social description
* Social image
* Image alt text
* Canonical social URL
* Article author
* Article section
* Article tags

### 8. Image URL Handling

Explain how the implementation handles:

* Absolute URLs
* Relative URLs
* Storage URLs
* Imported WordPress media
* Missing images
* Default images
* Unsafe schemes
* Filesystem paths
* MIME types
* Dimensions

### 9. Page Coverage

Report validation for:

* Homepage
* Article page
* Category archive
* Tag archive
* Author archive
* Date archive
* Search page
* Static pages

State clearly when a page type does not exist.

### 10. Configuration Changes

List:

* Configuration values added
* Environment variables added to `.env.example`
* Default values
* Fallback behavior

Confirm that the actual `.env` was not overwritten.

### 11. Database Impact

State whether:

* Migrations were added
* Columns were added
* Existing fields were reused

If there was no database change, explicitly say so.

### 12. Query and Performance Impact

Report:

* Whether additional queries were introduced
* Relationships used
* Eager-loading changes
* How N+1 queries were prevented
* Whether remote requests or image inspections occur

### 13. Automated Test Results

Provide:

* Commands executed
* Targeted test results
* Full test-suite results
* Number of tests
* Number of assertions
* Pass or failure status
* Existing unrelated failures

Do not hide failures.

### 14. Manual Validation Results

List pages inspected and findings.

### 15. External Validation Status

State whether public validators were used.

Do not claim public validation for localhost-only pages.

### 16. Protected Boundaries Confirmation

Confirm that protected systems were not modified or regressed.

### 17. Follow-Up Recommendations

Provide only recommendations relevant to:

```text
Phase 14C — Schema.org Structured Data
```

Do not implement those recommendations.

---

## Final Instruction

Implement only:

```text
Phase 14B — OpenGraph and Twitter/X Cards
```

Do not begin or implement:

* Phase 14C
* Schema.org
* JSON-LD structured data
* Organization schema
* NewsArticle schema
* Breadcrumb schema
* Website schema
* SearchAction schema
* XML sitemap
* News sitemap
* Image sitemap
* Robots.txt changes
* Search-engine pinging
* Google News optimization
* Google Discover optimization
* Admin-panel social preview editors
