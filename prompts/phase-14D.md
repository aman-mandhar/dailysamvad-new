# Phase 14D — XML Sitemaps, News Sitemap, Image Sitemap, Robots.txt and Indexing Notifications

## Objective

Implement a complete Laravel-native sitemap and crawler-control infrastructure for the public news portal.

Phase 14A established the native SEO foundation.

Phase 14B implemented OpenGraph and Twitter/X metadata.

Phase 14C implemented Schema.org structured data and JSON-LD.

Phase 14D must extend the same SEO architecture and provide:

* Sitemap index
* Article/post XML sitemaps
* Category sitemaps
* Tag sitemaps
* Author sitemaps
* Static-page sitemaps when applicable
* Google News sitemap
* Image sitemap support
* Dynamic robots.txt
* Sitemap caching
* Cache invalidation
* Optional IndexNow integration for supported search engines
* Commands and tests for sitemap generation and validation

Implement only Phase 14D.

Do not begin:

* Phase 15 — Google News Optimization
* Google Publisher Center integration
* Google Discover optimization
* Editorial workflow changes
* Admin SEO editors
* Analytics integration
* Search Console API integration unless already configured and explicitly supported

---

## 1. Initial Architecture Audit

Before modifying code, inspect the existing application.

Audit:

* Phase 14A SEO manager and canonical resolver
* Phase 14B social-image resolver
* Phase 14C schema implementation
* Existing public routes
* Article/post routes
* Category routes
* Tag routes
* Author routes
* Date archive routes
* Static-page routes
* Search routes
* Published-post scopes
* Publication-status enums
* Scheduled-post behavior
* Soft deletes
* Canonical URL generation
* Legacy redirect routes
* Imported WordPress URLs
* Existing sitemap routes
* Existing XML sitemap files
* Existing robots.txt file
* Existing robots route
* Existing sitemap packages or services
* Existing scheduled commands
* Existing cache infrastructure
* Existing model observers and events
* Existing queue infrastructure
* Existing tests
* Existing environment and SEO configuration

Search the project for:

```text
sitemap
sitemap.xml
news-sitemap
image-sitemap
robots.txt
IndexNow
ping
google.com/ping
application/xml
text/xml
XMLWriter
SimpleXMLElement
```

Reuse and extend the current native SEO architecture.

Do not create a competing SEO subsystem.

---

## 2. No Third-Party SEO Package by Default

Implement the sitemap infrastructure natively in Laravel.

Do not install a third-party SEO or sitemap package unless:

1. The project already depends on it
2. Removing it would cause a regression
3. It is demonstrably safer to extend than replace
4. The decision is documented in the completion report

Do not introduce packages such as a full SEO plugin merely to generate XML.

Prefer:

* Dedicated services
* DTOs or value objects
* Laravel routes
* Streaming responses
* XMLWriter or another safe native XML-generation approach
* Focused repositories or query objects
* Laravel cache
* Model events or observers where appropriate

---

## 3. Architecture Requirements

Create or extend a dedicated sitemap layer.

Example architecture:

```text
app/
    SEO/
        Sitemap/
            SitemapManager.php
            SitemapIndexBuilder.php
            UrlSetBuilder.php
            SitemapEntry.php
            NewsSitemapBuilder.php
            ImageSitemapBuilder.php
            RobotsTxtBuilder.php
            IndexNowService.php
```

These names are examples only.

Use project conventions and existing Phase 14 structure.

Responsibilities must remain separated.

Suggested responsibilities:

### SitemapManager

Coordinates available sitemap types, pagination, caching and responses.

### SitemapIndexBuilder

Creates the root sitemap index.

### UrlSetBuilder

Builds standard XML sitemap URL sets.

### NewsSitemapBuilder

Builds Google News-specific XML.

### ImageSitemapBuilder

Adds image metadata or creates a dedicated image sitemap.

### RobotsTxtBuilder

Produces environment-aware robots.txt content.

### IndexNowService

Optionally submits changed public URLs to participating search engines.

Do not place XML generation directly in controllers or routes.

Controllers, when needed, should only delegate to services.

---

## 4. Public Endpoints

Provide these public endpoints where applicable:

```text
/sitemap.xml
/sitemaps/posts-1.xml
/sitemaps/categories.xml
/sitemaps/tags.xml
/sitemaps/authors.xml
/sitemaps/pages.xml
/news-sitemap.xml
/image-sitemap.xml
/robots.txt
```

The exact child sitemap naming may differ, but it must be:

* Stable
* Predictable
* Public
* Absolute when referenced
* Backward-compatible
* Suitable for Google Search Console and Bing Webmaster Tools

The root endpoint:

```text
/sitemap.xml
```

should preferably be a sitemap index when multiple child sitemaps exist.

Do not expose internal IDs unnecessarily in public sitemap filenames.

---

## 5. Response Requirements

Sitemap responses must use an appropriate XML content type.

Preferred:

```text
application/xml; charset=UTF-8
```

Robots response should use:

```text
text/plain; charset=UTF-8
```

Requirements:

* Return HTTP 200 for valid sitemap endpoints
* Return valid UTF-8
* Do not include Blade layout HTML
* Do not include debug output
* Do not include BOM characters
* Do not include PHP warnings
* Do not include stack traces
* Do not include authentication redirects
* Do not require cookies
* Do not require JavaScript
* Do not include compressed binary content unless compression is intentionally configured at the web-server layer

---

## 6. XML Safety

Generate XML using a safe serializer such as XMLWriter or an equivalent tested approach.

Do not manually concatenate unescaped XML strings.

Escape correctly:

* Ampersands
* Less-than signs
* Greater-than signs
* Quotes
* Apostrophes
* Unicode titles
* Unicode image captions
* Query parameters in URLs

Requirements:

* Valid XML declaration
* Correct namespace declarations
* No invalid control characters
* No malformed URLs
* No duplicate XML declaration
* No trailing output
* No invalid entity references
* Valid UTF-8

Example declaration:

```xml
<?xml version="1.0" encoding="UTF-8"?>
```

---

## 7. Root Sitemap Index

Generate a sitemap index at:

```text
/sitemap.xml
```

Example:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <sitemap>
        <loc>https://example.com/sitemaps/posts-1.xml</loc>
        <lastmod>2026-07-19T20:00:00+05:30</lastmod>
    </sitemap>
</sitemapindex>
```

The sitemap index should reference only existing, valid and enabled child sitemaps.

Potential children:

* Post/article sitemap chunks
* Category sitemap
* Tag sitemap
* Author sitemap
* Static page sitemap
* Image sitemap
* News sitemap, only if intentionally included in the index

Requirements:

* Absolute URLs
* Production HTTPS
* Canonical application host
* No duplicate sitemap locations
* No nonexistent sitemap URLs
* Valid ISO 8601 `lastmod`
* Stable ordering
* No empty child sitemap references unless intentionally supported
* No localhost URLs in production

---

## 8. Sitemap Size Limits

Respect the sitemap protocol limits.

Each sitemap must contain no more than:

```text
50,000 URLs
```

and must not exceed:

```text
50 MB uncompressed
```

Use a configurable lower application chunk size to protect memory and response performance.

Recommended default:

```php
'urls_per_sitemap' => 10000,
```

or another justified safe value.

Do not load all 13,000+ articles into memory at once.

Use:

* Cursor pagination
* Lazy collections
* Chunking
* ID-based pagination
* Streamed responses

Choose an approach compatible with the existing query architecture.

Document the decision.

---

## 9. Standard Post/Article Sitemaps

Create chunked article sitemaps.

Example endpoints:

```text
/sitemaps/posts-1.xml
/sitemaps/posts-2.xml
```

Include only publicly indexable articles.

A post is eligible only when it is:

* Published
* Publicly accessible
* Canonical
* Not soft-deleted
* Not blocked by application-level SEO policy
* Not a preview
* Not an admin route
* Not a legacy redirect URL
* Not a duplicate URL
* Not `noindex`

Exclude:

* Drafts
* Private posts
* Scheduled unpublished posts
* Trashed posts
* Deleted posts
* Preview URLs
* Duplicate legacy URLs
* Redirect-only URLs
* Search URLs
* Filter URLs
* Tracking URLs

Use the existing published scope and Phase 14A robots/indexability rules.

---

## 10. Standard Sitemap Entry

Each standard URL entry may contain:

```xml
<url>
    <loc>https://example.com/article-slug</loc>
    <lastmod>2026-07-19T18:30:00+05:30</lastmod>
</url>
```

Use only supported meaningful properties.

Do not add misleading:

```xml
<changefreq>
<priority>
```

unless the application has a reliable policy for them.

Search engines may ignore these values, so omit them by default.

`loc` requirements:

* Absolute canonical URL
* Reuse Phase 14A canonical strategy
* Correct domain
* Correct scheme
* Proper XML escaping
* No fragment
* No tracking query parameters
* No duplicate trailing slash variants
* No legacy redirect path

`lastmod` requirements:

* Use meaningful content modification date
* Fall back to publication date where appropriate
* Use ISO 8601
* Include timezone
* Do not use current request time
* Do not fabricate recent dates
* Do not update merely because the sitemap was regenerated

---

## 11. Post Sitemap Ordering

Use deterministic ordering.

Recommended:

```text
Newest published article first
```

with a stable secondary key such as ID.

Example concept:

```text
ORDER BY published_at DESC, id DESC
```

Do not rely on nondeterministic database ordering.

Sitemap chunk boundaries must remain reasonably stable.

Avoid offset-based pagination if it creates major inconsistency or performance problems on large datasets.

Prefer keyset or ID-based pagination where practical.

---

## 12. Homepage Inclusion

Include the canonical homepage URL in an appropriate sitemap.

This may be:

* A dedicated pages sitemap
* A general sitemap
* Another clearly documented location

Do not duplicate the homepage across several child sitemaps.

Use a meaningful `lastmod` only when it can be derived reliably.

Omit `lastmod` when no reliable value exists.

---

## 13. Category Sitemap

Create:

```text
/sitemaps/categories.xml
```

when public category archives exist.

Include only categories that are:

* Public
* Canonical
* Indexable
* Reachable
* Not deleted
* Not hidden
* Not empty, when project policy excludes empty archives

Use existing category routes.

Do not invent category URLs.

For `lastmod`, use the most recent meaningful article update in that category only if it can be obtained efficiently.

Do not run one query per category.

Acceptable strategies include:

* Aggregated query
* Precomputed value
* Omit `lastmod`
* Existing category timestamp when meaningful

Avoid N+1 queries.

---

## 14. Tag Sitemap

Create:

```text
/sitemaps/tags.xml
```

when public tag archives exist.

Include only tags that are:

* Public
* Canonical
* Indexable
* Used by eligible published articles
* Not deleted
* Not hidden

Do not include thousands of empty or orphan tags.

Respect Phase 14A index/noindex policy.

If tag archives are intentionally `noindex`, do not include them in the sitemap.

Do not change the existing tag indexing policy merely to populate a sitemap.

---

## 15. Author Sitemap

Create:

```text
/sitemaps/authors.xml
```

when public author archives exist and are indexable.

Include only authors who:

* Have a public author route
* Have at least one eligible published article, when project policy requires it
* Are not disabled
* Are not private
* Are not system-only accounts
* Are indexable

Do not expose:

* Email addresses
* Internal user IDs
* Admin usernames
* Admin routes
* Private profile URLs

Use the canonical public author URL.

If author archives are noindex, exclude them.

---

## 16. Static Page Sitemap

Create:

```text
/sitemaps/pages.xml
```

only when a real public static-page system exists.

Include:

* Homepage
* About page
* Contact page
* Other public canonical pages

Exclude:

* Login
* Registration
* Password reset
* Account pages
* Admin pages
* Preview pages
* Search results
* Internal APIs
* Webhook endpoints
* Error pages
* Terms pages marked noindex
* Duplicate routes

Do not create a fake static-page system.

If no static-page model exists, include only known route-based pages through a carefully maintained configuration list, or omit this child sitemap.

Document the decision.

---

## 17. Date Archive Sitemap

Do not create a date archive sitemap by default.

Date archives can create large numbers of low-value URLs.

Include date archives only when:

* Public date archive routes exist
* Phase 14A marks them indexable
* They provide meaningful unique archive pages
* The decision is consistent with existing SEO policy

Otherwise exclude them and document why.

Do not alter Phase 14A policy in Phase 14D.

---

## 18. Pagination and Archive URLs

Do not include every paginated archive page by default.

Include archive root URLs unless there is a justified indexing strategy for paginated pages.

Do not include:

```text
/category/punjab?page=2
/tag/news?page=3
/search?q=...
```

unless Phase 14A explicitly defines such pages as canonical and indexable.

Search result pages must not be placed in sitemaps.

---

## 19. Google News Sitemap

Create a dedicated endpoint:

```text
/news-sitemap.xml
```

This sitemap must follow Google News sitemap requirements.

Use namespaces:

```xml
<urlset
    xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
    xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">
```

Example entry:

```xml
<url>
    <loc>https://example.com/article-slug</loc>
    <news:news>
        <news:publication>
            <news:name>Publication Name</news:name>
            <news:language>pa</news:language>
        </news:publication>
        <news:publication_date>2026-07-19T18:30:00+05:30</news:publication_date>
        <news:title>Article title</news:title>
    </news:news>
</url>
```

---

## 20. News Sitemap Eligibility Window

Include only eligible articles published within the last:

```text
2 days
```

Use a precise rolling 48-hour window based on the application timezone and publication timestamp.

Do not include older articles with news metadata.

Older articles must remain in the standard article sitemap.

Requirements:

* Use actual `published_at`
* Do not use `created_at` unless that is the authoritative publication field
* Do not use `updated_at` to make old articles appear recent
* Do not include scheduled future content
* Do not include drafts
* Do not include private posts
* Do not include deleted posts
* Do not include redirected legacy URLs

An empty news sitemap is acceptable when no article was published during the eligible period.

Return valid XML even when empty.

---

## 21. News Sitemap Entry Limit

A Google News sitemap must contain no more than:

```text
1,000 news entries
```

If more than 1,000 eligible articles are published within the rolling two-day window:

* Split them into multiple news sitemap files
* Reference those files from an appropriate index
* Keep `/news-sitemap.xml` backward-compatible

Possible design:

```text
/news-sitemap.xml
/news-sitemaps/news-1.xml
/news-sitemaps/news-2.xml
```

Use the simplest valid architecture for the actual publication volume.

Do not over-engineer multiple files if the project cannot realistically exceed 1,000 articles in 48 hours, but enforce the limit in code and tests.

---

## 22. News Publication Name

Configure the news publication name.

Example:

```php
'news_publication_name' => env(
    'SEO_NEWS_PUBLICATION_NAME',
    env('APP_NAME')
),
```

Requirements:

* Reuse the visible publication/site name where appropriate
* Do not hardcode the name in several files
* Do not invent an alternate name
* Keep it consistent with publisher identity
* Document the environment variable in `.env.example`
* Do not modify the real `.env`

---

## 23. News Language

The `<news:language>` value should use an appropriate short language code.

Examples:

```text
en
hi
pa
```

Use the article’s actual language when reliably stored.

Fallback to the configured application/news language.

Do not put locale strings such as:

```text
pa_IN
pa-IN
```

inside `<news:language>` when the required value is the language code.

Do not build a new multilingual content system.

Document the fallback behavior.

---

## 24. News Publication Date

Use:

```xml
<news:publication_date>
```

Requirements:

* ISO 8601 format
* Actual publication timestamp
* Application timezone
* No future unpublished date
* No request-time value
* No modified date substitution
* No fabricated value

---

## 25. News Title

Use the real article headline.

Requirements:

* Plain text
* Strip HTML
* Decode entities
* Preserve Punjabi, Hindi and Unicode
* Normalize whitespace
* Escape XML
* Do not append unnecessary branding
* Do not use meta keywords
* Do not use raw HTML

Reuse the article headline resolution from Phase 14C where appropriate.

---

## 26. Image Sitemap

Implement image sitemap support.

Preferred endpoint:

```text
/image-sitemap.xml
```

Use namespaces:

```xml
<urlset
    xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
    xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
```

Example:

```xml
<url>
    <loc>https://example.com/article-slug</loc>
    <image:image>
        <image:loc>https://example.com/storage/article.jpg</image:loc>
        <image:caption>Article image caption</image:caption>
        <image:title>Article title</image:title>
    </image:image>
</url>
```

Reuse Phase 14B social and image URL resolution.

Do not create another conflicting image resolver.

---

## 27. Image Sitemap Eligibility

Include images associated with eligible public pages.

Priority:

1. Article featured image
2. Primary article media image
3. Additional article gallery images when reliably associated
4. Static page primary image when supported

Exclude:

* Logos as article images
* Icons
* Avatars unless they are the meaningful page image
* Tracking pixels
* Advertisements
* Placeholder images
* Broken images
* Private images
* Filesystem paths
* Data URLs
* Blob URLs
* Admin images
* Duplicate image URLs
* Tiny UI thumbnails
* Unrelated sidebar images

Do not crawl rendered HTML to discover images.

Use model fields and existing media relationships.

---

## 28. Image Sitemap URL Requirements

Each `<image:loc>` must be:

* Absolute
* Public
* HTTP or HTTPS
* Properly encoded
* XML escaped
* Accessible without authentication
* Free from local filesystem paths
* Free from duplicated host prefixes
* Free from unsafe schemes

Correctly handle:

* Imported WordPress URLs
* Existing absolute URLs
* Relative public paths
* Storage paths
* External CDN URLs
* Unicode filenames
* Spaces in filenames
* Query strings

Do not prepend the application host to already absolute URLs.

---

## 29. Image Metadata

Optional supported properties may include:

```xml
<image:caption>
<image:title>
```

Include only meaningful values.

Use fallback priority:

### Caption

1. Media caption
2. Media alt text
3. Article title

### Title

1. Media title
2. Article title

Requirements:

* Plain text
* No HTML
* No script
* No filenames as preferred title
* Preserve Unicode
* Remove empty values
* Escape XML

Do not fabricate:

```text
image:license
image:geo_location
```

unless reliable data exists and the property remains supported by the chosen implementation.

---

## 30. Dedicated vs Combined Image Sitemap

A dedicated image sitemap is preferred for this phase because it provides clearer testing and Search Console visibility.

However, combining image extensions with post sitemaps is acceptable if:

* The implementation remains valid
* It avoids duplicate data
* It performs better
* It matches the current architecture
* The choice is documented

Do not implement both combined and dedicated image entries if it would create needless duplication.

---

## 31. Robots.txt Endpoint

Provide a root endpoint:

```text
/robots.txt
```

The application may serve it dynamically or through a generated public file.

Choose one source of truth.

Do not allow both a static public file and a conflicting dynamic route.

Preferred dynamic response:

```text
User-agent: *
Allow: /

Disallow: /admin
Disallow: /login
Disallow: /register
Disallow: /password/
Disallow: /api/

Sitemap: https://example.com/sitemap.xml
Sitemap: https://example.com/news-sitemap.xml
```

Rules must reflect actual routes.

Do not copy placeholder disallow rules blindly.

---

## 32. Robots.txt Purpose

Robots.txt controls crawler access.

It is not a reliable mechanism for removing pages from search indexes.

Do not add:

```text
Noindex:
```

to robots.txt.

`noindex` must remain handled through Phase 14A metadata or HTTP headers.

Do not disallow pages that crawlers must access to observe a `noindex` directive unless there is a deliberate security reason.

Authentication and authorization must protect private content.

Robots.txt is not a security boundary.

---

## 33. Production Robots Policy

In production:

```text
User-agent: *
Allow: /
```

Then disallow only genuinely non-public or crawler-waste routes.

Potential disallow paths:

```text
/admin
/login
/register
/password
/api
/livewire
```

Only include routes that exist and are safe to disallow.

Be careful with:

```text
/livewire
```

Do not block assets or endpoints required for public rendering if crawlers need them.

Do not block:

* CSS
* JavaScript
* Public images
* Featured images
* Storage assets
* Canonical public pages
* Article pages
* Category pages
* Tag pages when indexable
* Author pages when indexable

---

## 34. Non-Production Robots Policy

For local, testing, staging and preview environments, support a restrictive policy.

Example:

```text
User-agent: *
Disallow: /
```

Determine environment using configuration, not hostname string hacks.

Possible configuration:

```php
'robots' => [
    'production_indexing' => env(
        'SEO_ALLOW_INDEXING',
        app()->environment('production')
    ),
],
```

Do not call `env()` outside configuration files.

Document:

```text
SEO_ALLOW_INDEXING
```

in `.env.example`.

Do not modify the real `.env`.

Production should not accidentally remain blocked because of an unsafe default.

Design the fallback carefully and document it.

---

## 35. Robots Sitemap Directives

Robots.txt should reference:

```text
Sitemap: https://example.com/sitemap.xml
```

Optionally also reference:

```text
Sitemap: https://example.com/news-sitemap.xml
```

Do not list every child sitemap if the root sitemap index already contains them.

Use absolute URLs.

Do not output localhost URLs in production.

Do not output duplicated sitemap directives.

---

## 36. Sensitive Routes

Audit actual routes and protect sensitive content through application security.

Possible crawler exclusions may include:

* Admin
* Login
* Registration
* Password reset
* Internal APIs
* Webhooks
* Preview routes
* Temporary signed URLs
* Debug routes
* Development tools

Do not expose sensitive routes merely because they are absent from robots.txt.

Do not add secret URLs to robots.txt unnecessarily, because robots.txt is public.

Only list predictable public route prefixes where appropriate.

---

## 37. Legacy URLs

Do not include legacy WordPress-style URLs in sitemaps when they redirect to canonical Laravel article URLs.

Example legacy URL:

```text
/2026/07/article-slug/
```

Canonical URL:

```text
/article-slug/
```

Only include the canonical URL.

Do not include both.

Ensure Phase 14A canonical handling and legacy redirect behavior remain intact.

---

## 38. Sitemap Canonical Consistency

Every URL in a sitemap must match:

* Its canonical `<link>`
* Phase 14A canonical resolver
* Phase 14B `og:url`
* Phase 14C page/entity URL

Do not create a second route-building strategy.

Add tests that compare rendered page canonical URLs with sitemap URLs for representative pages.

---

## 39. Sitemap Cache

Cache sitemap output or sitemap datasets where appropriate.

Possible cache keys:

```text
seo:sitemap:index
seo:sitemap:posts:{page}
seo:sitemap:categories
seo:sitemap:tags
seo:sitemap:authors
seo:sitemap:pages
seo:sitemap:news
seo:sitemap:images:{page}
seo:robots
```

Use a configurable TTL.

Example:

```php
'sitemap_cache_ttl' => env(
    'SEO_SITEMAP_CACHE_TTL',
    3600
),
```

Requirements:

* Do not cache forever without invalidation
* Do not cache environment-specific hosts incorrectly
* Include relevant host or environment context in keys when necessary
* Avoid cache stampedes where practical
* Preserve compatibility with file, database and Redis cache drivers
* Do not require Redis solely for Phase 14D

---

## 40. Cache Invalidation

Invalidate relevant sitemap caches when:

* A post is published
* A published post is updated
* A post is unpublished
* A post is deleted
* A post is restored
* A featured image changes
* A category assignment changes
* A tag assignment changes
* An author changes
* A category is created, updated or deleted
* A tag is created, updated or deleted
* An author becomes public or private
* A static page changes
* Relevant SEO indexability changes

Do not clear the entire application cache.

Invalidate only sitemap-related keys or versions.

A cache-version strategy is acceptable.

Example concept:

```text
seo:sitemap:version
```

Bump the version when relevant content changes.

Avoid observer recursion.

Avoid dispatching notifications during data imports unless intentionally controlled.

---

## 41. WordPress Importer Protection

Do not regress the existing WordPress importer.

Large imports may create thousands of model events.

Ensure sitemap invalidation or IndexNow submission does not:

* Fire one remote request per imported post
* Clear cache thousands of times unnecessarily
* Make imports significantly slower
* Exhaust queue capacity
* Fail the import

Possible safe approaches:

* Suppress indexing notifications during imports
* Batch invalidation
* Debounce events
* Dispatch one refresh after import completion
* Respect existing `withoutEvents` behavior
* Add explicit importer integration only if necessary

Document the chosen protection.

---

## 42. Scheduled Sitemap Refresh

Do not require cron-based physical sitemap generation unless it is the best fit for the application.

Dynamic cached endpoints are preferred.

Optionally provide an Artisan command:

```bash
php artisan seo:sitemaps:warm
```

Purpose:

* Warm sitemap cache
* Validate configured endpoints
* Prepare production cache after deployment

Optional companion command:

```bash
php artisan seo:sitemaps:clear
```

or:

```bash
php artisan seo:sitemaps:refresh
```

Use clear command naming.

Do not add commands without tests.

Do not schedule unnecessary hourly full sitemap rebuilds.

---

## 43. Streamed Generation

For large post or image sitemaps, prefer memory-efficient generation.

Acceptable methods:

* `StreamedResponse`
* XMLWriter writing to output
* Chunked temporary stream
* Cursor-based iteration

Do not:

* Build a massive XML string in memory
* Call `Post::all()`
* Load complete content columns unnecessarily
* Load full relationships for every sitemap entry
* Use collection transformations over the entire database

Select only required columns.

Example article columns may include:

```text
id
slug
published_at
updated_at
featured_image
status
```

Use actual project fields.

---

## 44. Query Performance

Sitemap queries must:

* Use published scopes
* Select only needed columns
* Use indexed fields where available
* Avoid N+1 queries
* Avoid per-category aggregate queries
* Avoid per-author count queries
* Avoid loading article bodies
* Avoid loading unrelated media
* Avoid remote requests
* Avoid sorting unindexed calculated values where possible

Do not add database indexes automatically unless profiling proves they are needed.

Any new index migration must be minimal, justified and tested.

Document query strategy.

---

## 45. Database Restrictions

Do not add database columns solely for sitemap generation.

Reuse:

* Slug
* Status
* Publication date
* Updated date
* Featured image
* Relationships
* SEO indexability fields
* Existing soft-delete fields

A database index migration may be considered only when:

* A real query bottleneck is identified
* The index matches an actual frequent sitemap query
* The migration is safe
* The impact is documented

Do not alter imported data.

---

## 46. Indexing Notifications

Do not use deprecated or unsupported generic Google sitemap ping endpoints.

Do not send automatic requests to legacy endpoints such as:

```text
https://www.google.com/ping?sitemap=...
```

Google sitemap discovery should rely on:

* Google Search Console sitemap submission
* Sitemap directive in robots.txt
* Search Console API only when separately authenticated and explicitly implemented
* Normal crawler discovery

Phase 14D must not fake Google submission success.

---

## 47. IndexNow Integration

Optional IndexNow support may be implemented for Bing and other participating search engines.

Use IndexNow only when configured.

Example configuration:

```php
'indexnow' => [
    'enabled' => env('INDEXNOW_ENABLED', false),
    'key' => env('INDEXNOW_KEY'),
    'endpoint' => env(
        'INDEXNOW_ENDPOINT',
        'https://api.indexnow.org/indexnow'
    ),
],
```

Requirements:

* Disabled by default
* No hardcoded production key
* Key stored in environment
* Key file available at the required public URL when enabled
* Submitted URLs belong to the configured host
* Batch submissions where appropriate
* No submission for drafts or private URLs
* Submit on publish, meaningful public update, unpublish or deletion
* Do not block web requests waiting for IndexNow
* Use queued jobs when queue infrastructure exists
* Fail safely
* Log failures without breaking publication
* Apply timeout and retry limits
* Do not expose the key in logs

Document variables in `.env.example`.

Do not modify the real `.env`.

---

## 48. IndexNow Key File

When IndexNow is enabled, make the key verification file publicly available.

Typical path:

```text
/{key}.txt
```

The content should be the key itself.

Requirements:

* Exact key
* Plain-text response
* No layout HTML
* No public response when integration is disabled
* Validate key format
* Prevent arbitrary filename injection
* Do not expose unrelated environment variables
* Do not allow user-supplied key paths

A static public key file or tightly controlled route may be used.

Document the choice.

---

## 49. IndexNow URL Events

Submit eligible public canonical URLs when:

* A new article is published
* A published article receives a meaningful content update
* A public article is deleted or unpublished
* A canonical URL changes
* A public category/page changes when justified

Do not submit:

* Draft saves
* Autosaves
* View count updates
* Like count updates
* Analytics changes
* Cache updates
* Admin URLs
* Preview URLs
* Search URLs
* Legacy redirect URLs

Prevent repeated submissions caused by insignificant model updates.

Document how meaningful changes are detected.

---

## 50. Queue Safety

When IndexNow is enabled:

* Prefer asynchronous queued submission
* Use a dedicated job
* Set sensible timeout
* Set limited retries
* Use backoff
* Avoid duplicate bursts
* Batch URLs where supported
* Log failures safely
* Do not fail post publication when the remote service fails

When queues are unavailable, either:

* Skip automatic notification safely
* Use a controlled after-response mechanism
* Provide a manual command

Do not make article publication dependent on a remote search engine.

---

## 51. Manual IndexNow Command

Optionally provide:

```bash
php artisan seo:indexnow:submit {url?}
```

Supported behavior may include:

* Submit one canonical URL
* Submit recently changed URLs
* Validate configuration
* Dry-run mode

Example:

```bash
php artisan seo:indexnow:submit \
    --recent=24 \
    --dry-run
```

Do not allow arbitrary external domains.

Validate that submitted URLs belong to the configured host.

Add tests if the command is introduced.

---

## 52. HTTP Client Safety

IndexNow integration must use Laravel’s HTTP client with:

* Timeout
* Retry limits
* HTTPS validation
* JSON request body
* Safe logging
* Error handling
* Fakeable tests

Do not disable TLS verification.

Do not log:

* Full secret key
* Authorization secrets
* Environment values

Use `Http::fake()` in tests.

Do not make real search-engine requests during automated tests.

---

## 53. Route Naming

Name sitemap and robots routes clearly.

Example:

```text
seo.sitemap.index
seo.sitemap.posts
seo.sitemap.categories
seo.sitemap.tags
seo.sitemap.authors
seo.sitemap.pages
seo.sitemap.news
seo.sitemap.images
seo.robots
seo.indexnow.key
```

Use existing naming conventions.

Avoid route conflicts with article slugs.

Register explicit sitemap routes before wildcard article routes when required.

Add tests confirming that:

```text
/sitemap.xml
/news-sitemap.xml
/image-sitemap.xml
/robots.txt
```

are not captured by slug routes.

---

## 54. Route Model and Wildcard Protection

The project may have broad routes such as:

```php
Route::get('/{slug}', ...);
```

Ensure sitemap and robots endpoints resolve correctly.

Requirements:

* Explicit routes before wildcard routes
* Route constraints where appropriate
* No article lookup for `sitemap.xml`
* No article lookup for `robots.txt`
* No redirect loops
* No duplicate route names
* No conflict with legacy year/month routes

Do not break existing canonical article routes.

---

## 55. HTTP Caching Headers

Sitemap and robots responses may include sensible cache headers.

Example:

```text
Cache-Control: public, max-age=...
ETag
Last-Modified
```

Only implement ETag or conditional requests when reliable and tested.

Do not set excessively long browser or CDN caching without invalidation.

Ensure:

* Fresh news sitemap updates are visible promptly
* Article publication invalidates relevant cached responses
* Robots changes are not stuck indefinitely
* CDN behavior is considered

Document response caching.

---

## 56. News Sitemap Cache TTL

The news sitemap changes frequently.

Use a shorter cache TTL than standard sitemaps.

Example:

```php
'news_sitemap_cache_ttl' => env(
    'SEO_NEWS_SITEMAP_CACHE_TTL',
    300
),
```

A default of five minutes is reasonable, but choose based on the existing publishing workflow.

Immediate invalidation on article publication is required.

Do not allow a one-hour cache to hide breaking news when invalidation fails silently.

---

## 57. Standard Sitemap Cache TTL

Standard sitemap files may use a longer TTL.

Example:

```php
'sitemap_cache_ttl' => env(
    'SEO_SITEMAP_CACHE_TTL',
    3600
),
```

Cache invalidation must still occur on meaningful content changes.

Document defaults in `.env.example`.

---

## 58. Robots Cache TTL

Robots.txt may use a short or moderate cache TTL.

Do not cache a staging `Disallow: /` response and accidentally reuse it in production.

Ensure environment-aware cache keys.

---

## 59. Configuration

Extend the existing SEO configuration.

Possible structure:

```php
'sitemaps' => [
    'enabled' => env('SEO_SITEMAPS_ENABLED', true),
    'urls_per_sitemap' => env('SEO_SITEMAP_URL_LIMIT', 10000),
    'cache_ttl' => env('SEO_SITEMAP_CACHE_TTL', 3600),
    'news_cache_ttl' => env('SEO_NEWS_SITEMAP_CACHE_TTL', 300),
    'include_categories' => true,
    'include_tags' => true,
    'include_authors' => true,
    'include_images' => true,
],

'news' => [
    'publication_name' => env(
        'SEO_NEWS_PUBLICATION_NAME',
        env('APP_NAME')
    ),
    'language' => env('SEO_NEWS_LANGUAGE'),
],

'robots' => [
    'allow_indexing' => env(
        'SEO_ALLOW_INDEXING',
        false
    ),
],

'indexnow' => [
    'enabled' => env('INDEXNOW_ENABLED', false),
    'key' => env('INDEXNOW_KEY'),
    'endpoint' => env(
        'INDEXNOW_ENDPOINT',
        'https://api.indexnow.org/indexnow'
    ),
],
```

This structure is an example only.

Reuse existing keys.

Do not duplicate Phase 14A–14C configuration.

Do not call `env()` outside config files.

---

## 60. Environment Documentation

Add applicable variables to `.env.example`.

Possible variables:

```dotenv
SEO_SITEMAPS_ENABLED=true
SEO_SITEMAP_URL_LIMIT=10000
SEO_SITEMAP_CACHE_TTL=3600
SEO_NEWS_SITEMAP_CACHE_TTL=300
SEO_NEWS_PUBLICATION_NAME=
SEO_NEWS_LANGUAGE=pa
SEO_ALLOW_INDEXING=false

INDEXNOW_ENABLED=false
INDEXNOW_KEY=
INDEXNOW_ENDPOINT=https://api.indexnow.org/indexnow
```

Do not overwrite the real `.env`.

Do not put a real IndexNow key into `.env.example`.

Explain that production must deliberately set:

```text
SEO_ALLOW_INDEXING=true
```

when that is the chosen safe-default design.

Avoid a deployment design that silently blocks production indexing.

---

## 61. Artisan Validation Command

Consider adding:

```bash
php artisan seo:sitemaps:validate
```

The command should validate local generated content without making remote requests.

Possible checks:

* XML parses correctly
* Sitemap index child URLs are valid
* No duplicate URLs
* No unsafe schemes
* No filesystem paths
* No empty locations
* Chunk limits respected
* News entries are within 48 hours
* News sitemap does not exceed 1,000 entries
* Robots includes sitemap directive
* Non-production robots is restrictive
* Production configuration is not accidentally blocked

Do not require this command if tests already provide equivalent coverage, but a validation command is recommended.

---

## 62. Automated Test Requirements

Add focused unit, feature and command tests.

At minimum test:

### Sitemap Index

1. `/sitemap.xml` returns HTTP 200
2. Response uses XML content type
3. XML parses successfully
4. Root element is `sitemapindex`
5. Child sitemap URLs are absolute
6. Child sitemap URLs are unique
7. Child sitemap URLs use configured host
8. Child sitemap `lastmod` values are valid when present
9. Disabled sitemap types are omitted
10. Nonexistent child sitemaps are not referenced

### Post Sitemaps

11. Published article appears
12. Draft article does not appear
13. Private article does not appear
14. Scheduled unpublished article does not appear
15. Soft-deleted article does not appear
16. Redirect-only legacy URL does not appear
17. Canonical article URL appears
18. URL matches Phase 14A canonical
19. `lastmod` uses meaningful timestamp
20. `lastmod` is not current request time
21. Duplicate URLs are removed
22. XML special characters are escaped
23. Punjabi slugs and URLs remain valid
24. Article content is not loaded unnecessarily where testable
25. Sitemap chunk limit is enforced
26. Multiple chunk endpoints work
27. Ordering is deterministic

### Categories

28. Indexable category appears
29. Empty or noindex category follows existing policy
30. Category URL is canonical
31. Category generation avoids N+1 behavior where testable

### Tags

32. Indexable used tag appears
33. Orphan tag is omitted when policy requires
34. Noindex tag is omitted
35. Tag URL is canonical

### Authors

36. Public author with published content appears
37. Private/system author does not appear
38. Author email is never exposed
39. Author URL is canonical

### Static Pages

40. Homepage appears once
41. Login page is excluded
42. Admin page is excluded
43. Search results are excluded
44. Static page appears when supported

### News Sitemap

45. `/news-sitemap.xml` returns HTTP 200
46. Response XML parses
47. Required news namespace exists
48. Article published within 48 hours appears
49. Article older than 48 hours does not appear
50. Old article remains in standard sitemap
51. Draft recent article does not appear
52. Future scheduled article does not appear
53. Publication name is correct
54. Language code is correct
55. Publication date is ISO 8601
56. News title is cleaned
57. Unicode title remains valid
58. Maximum 1,000 entries is enforced
59. Empty news sitemap remains valid
60. Updated old article does not become recent news
61. News URL is canonical

### Image Sitemap

62. `/image-sitemap.xml` returns HTTP 200
63. Image XML parses
64. Required image namespace exists
65. Featured image appears
66. Absolute image is not prefixed twice
67. Relative image becomes absolute
68. Storage image becomes public URL
69. Filesystem path is rejected
70. Unsafe image scheme is rejected
71. Duplicate images are removed
72. Placeholder image is omitted where appropriate
73. Image caption is cleaned
74. Unicode caption remains valid
75. Private article image does not appear
76. No per-image remote request occurs

### Robots

77. `/robots.txt` returns HTTP 200
78. Response uses text/plain
79. Production allows public crawling
80. Non-production blocks crawling
81. Sitemap directive is absolute
82. Sitemap directive is not duplicated
83. Robots does not use unsupported `Noindex`
84. Public CSS and images are not blocked
85. Actual sensitive route prefixes are handled
86. Robots response does not expose secrets
87. Environment-specific cache does not leak policy

### Routes

88. Sitemap routes are not captured by wildcard slug route
89. Robots route is not captured by wildcard slug route
90. Legacy route behavior remains working
91. Route names are unique

### Cache

92. Sitemap output is cached when enabled
93. Publishing article invalidates post sitemap
94. Publishing article invalidates news sitemap
95. Featured-image update invalidates image sitemap
96. Category update invalidates category sitemap
97. Cache invalidation does not flush unrelated application cache
98. Import batching avoids excessive invalidations where implemented

### IndexNow

99. IndexNow is disabled by default
100. No HTTP request occurs while disabled
101. Valid public URL can be submitted when enabled
102. External-domain URL is rejected
103. Draft URL is not submitted
104. View-count update does not trigger submission
105. HTTP request uses configured timeout
106. HTTP failure does not break publication
107. Secret key is not logged
108. IndexNow key response is available only when enabled
109. IndexNow HTTP tests use fakes
110. No real remote request occurs during tests

### Regression

111. Phase 14A tests continue passing
112. Phase 14B tests continue passing
113. Phase 14C tests continue passing
114. Existing archive tests continue passing
115. Existing article routes continue passing

Use actual project functionality.

Skip or adapt tests for page types that do not exist.

Document unsupported page types rather than building fake functionality.

---

## 63. XML Parsing Tests

Tests must parse generated XML instead of relying only on raw string assertions.

Use a safe PHP XML parser available in the project.

Tests should fail for:

* Malformed XML
* Invalid namespace structure
* Missing required elements
* Duplicate canonical locations
* Empty `<loc>`
* Unsafe schemes
* Invalid publication dates
* More than 1,000 news entries
* More than configured URL limit

Do not disable XML errors silently without asserting them.

---

## 64. Time Boundary Tests

Test the exact news sitemap eligibility boundary.

Cases:

* Published 47 hours and 59 minutes ago: included
* Published exactly 48 hours ago: behavior defined and tested
* Published 48 hours and 1 minute ago: excluded
* Recently updated but published three days ago: excluded
* Scheduled for one minute in the future: excluded

Freeze time in tests.

Use the application timezone.

Document whether the 48-hour lower boundary is inclusive or exclusive.

---

## 65. Manual Validation

Inspect these endpoints manually:

```text
/sitemap.xml
/sitemaps/posts-1.xml
/sitemaps/categories.xml
/sitemaps/tags.xml
/sitemaps/authors.xml
/news-sitemap.xml
/image-sitemap.xml
/robots.txt
```

Inspect applicable endpoints only.

Confirm:

* HTTP 200
* Correct content type
* Valid XML
* Correct namespaces
* Absolute canonical URLs
* No localhost URLs in production configuration
* No duplicate URLs
* No drafts
* No private content
* No legacy redirect URLs
* No filesystem paths
* Correct publication dates
* News entries within 48 hours
* Images publicly resolvable
* Robots references sitemap index
* Robots production/staging policy works
* Wildcard routes do not capture sitemap files
* Unicode content remains valid

---

## 66. External Validation Guidance

Prepare generated sitemaps for validation with:

* Google Search Console Sitemaps report
* Google News sitemap processing
* Bing Webmaster Tools
* XML sitemap validators
* IndexNow monitoring in Bing Webmaster Tools

Do not claim successful external submission unless it was actually completed.

Localhost sitemaps cannot be fetched by Google or Bing.

When only local testing is possible, state that clearly.

Do not claim Google indexing or ranking.

Sitemap submission is a discovery signal, not an indexing guarantee.

---

## 67. Google Submission Guidance

The completion report should provide deployment guidance, not automatic unsupported submission.

After deployment, recommend:

1. Open Google Search Console
2. Select the verified domain property
3. Submit:

```text
sitemap.xml
```

4. Monitor processing errors
5. Inspect representative article URLs

Do not implement browser automation for Search Console.

Do not require Google account credentials.

Do not store Search Console secrets.

---

## 68. Bing Submission Guidance

After deployment, recommend:

1. Verify the site in Bing Webmaster Tools
2. Submit the root sitemap
3. Enable IndexNow only after configuration
4. Monitor IndexNow activity
5. Inspect representative URLs

Do not claim Bing accepted URLs unless verified.

---

## 69. No Search Engine Guarantee

Do not claim that implementing sitemaps guarantees:

* Crawling
* Indexing
* Ranking
* Google News inclusion
* Discover visibility
* Immediate removal
* Immediate recrawl

The completion report must distinguish:

* Sitemap generation
* Sitemap discovery
* Search-engine processing
* Indexing

---

## 70. Security Review

Confirm that sitemap and robots infrastructure does not expose:

* Draft content
* Private posts
* Preview tokens
* Signed URLs
* Admin routes
* Emails
* Internal IDs
* Filesystem paths
* Storage credentials
* IndexNow secrets
* Environment values
* Debug output
* Stack traces

Ensure errors are handled through normal production error responses.

Do not include exception messages inside XML.

---

## 71. Error Handling

For invalid child sitemap pages or disabled types:

* Prefer HTTP 404 where appropriate
* Do not return an HTML 500 page with XML content type
* Do not expose exceptions
* Log internal errors safely
* Keep the root index from referencing disabled or invalid children

For temporary internal failure:

* Return correct failure status
* Do not cache corrupted XML
* Do not return partial XML as successful output

---

## 72. Protected Boundaries

Do not modify or regress:

* Existing public frontend design
* Public body HTML
* CSS
* JavaScript
* Header
* Navigation
* Breaking-news ticker
* Hero section
* Homepage sections
* Article layout
* Sidebar
* Advertisements
* Livewire behavior
* Existing page-query architecture except minimal sitemap queries
* WordPress importer
* Authentication
* Authorization
* Filament resources
* Media-library functionality
* Author-system functionality
* Phase 14A metadata
* Phase 14A canonical behavior
* Phase 14A robots meta behavior
* Phase 14B OpenGraph metadata
* Phase 14B Twitter/X metadata
* Phase 14B image resolution
* Phase 14C structured data
* Existing redirects
* Previous test coverage

Do not begin Phase 15.

---

## 73. Required Commands

Run the commands appropriate to the project.

At minimum:

```bash
php artisan route:list
php artisan test
php artisan config:clear
php artisan cache:clear
php artisan optimize
```

Run targeted tests where applicable:

```bash
php artisan test --filter=Sitemap
```

```bash
php artisan test --filter=NewsSitemap
```

```bash
php artisan test --filter=ImageSitemap
```

```bash
php artisan test --filter=Robots
```

```bash
php artisan test --filter=IndexNow
```

Use actual test names.

When commands are added, run them:

```bash
php artisan seo:sitemaps:validate
```

```bash
php artisan seo:sitemaps:warm
```

Do not invent results.

If `php artisan optimize` creates an issue:

1. Record the exact error
2. Restore the application to a working state
3. Report it honestly

---

## 74. Completion Criteria

Phase 14D is complete only when:

* `/sitemap.xml` is implemented
* Root sitemap is a valid sitemap index when child sitemaps exist
* Published articles are represented in chunked sitemaps
* Drafts and private content are excluded
* Canonical URLs from Phase 14A are reused
* Category sitemap follows existing indexability policy
* Tag sitemap follows existing indexability policy
* Author sitemap protects private data
* Static pages are supported where they exist
* Search URLs are excluded
* Legacy redirect URLs are excluded
* News sitemap contains only eligible articles from the last 48 hours
* News sitemap enforces the 1,000-entry limit
* Image sitemap uses valid public image URLs
* Robots.txt is environment aware
* Robots.txt references the sitemap
* Robots.txt does not attempt unsupported `noindex`
* Sitemap generation is memory efficient
* Sitemap cache and targeted invalidation work
* Importer performance is protected
* Deprecated Google ping behavior is not implemented
* Optional IndexNow is disabled by default and safe when enabled
* No real remote requests occur during tests
* XML parses successfully
* Automated tests pass
* Phase 14A, 14B and 14C remain intact
* Protected boundaries remain intact
* Phase 15 has not started

---

## 75. Required Completion Report

Return a structured report with these sections.

### 1. Phase 14D Summary

Summarize what was implemented.

### 2. Existing Architecture Audit

Describe:

* Existing SEO architecture
* Existing routes
* Existing sitemap or robots behavior
* Existing caching
* Existing observers/events
* Existing importer behavior
* Existing queue infrastructure

### 3. Files Created

List every file and its purpose.

### 4. Files Modified

List every modified file and summarize the change.

### 5. Sitemap Architecture

Document:

* Sitemap manager
* Builders
* Controllers or routes
* Streaming strategy
* Chunk strategy
* Cache strategy
* XML serialization approach

### 6. Public Endpoints

List all implemented endpoints and their route names.

### 7. Sitemap Index

Document:

* Referenced child sitemaps
* Ordering
* Last-modified behavior
* Disabled sitemap behavior

### 8. Article Sitemap

Document:

* Eligibility query
* Publication filtering
* Canonical URL handling
* Chunking
* Ordering
* `lastmod` mapping
* Memory behavior

### 9. Archive Sitemaps

Report behavior for:

* Categories
* Tags
* Authors
* Date archives
* Static pages
* Pagination
* Search pages

State when a sitemap type was omitted and why.

### 10. News Sitemap

Document:

* 48-hour eligibility
* Boundary behavior
* 1,000-entry enforcement
* Publication name
* Language
* Publication date
* Title mapping
* Empty sitemap behavior

### 11. Image Sitemap

Document:

* Image source priority
* URL resolution
* Caption mapping
* Title mapping
* Duplicate removal
* Excluded image types
* Query impact

### 12. Robots.txt

Document:

* Production policy
* Non-production policy
* Disallow rules
* Sitemap directives
* Environment configuration
* Confirmation that robots.txt is not used for `noindex`

### 13. IndexNow

Report:

* Whether implemented
* Default enabled/disabled state
* Key handling
* Key-file handling
* Event triggers
* Queue behavior
* Failure handling
* Batch behavior
* URL-host validation

If not implemented, explain why and provide a recommendation without claiming it exists.

### 14. Configuration Changes

List:

* New configuration keys
* `.env.example` variables
* Default values
* Safe fallback behavior

Confirm that `.env` was not overwritten.

### 15. Database Impact

State whether:

* Migrations were added
* Columns were added
* Indexes were added
* Existing data was reused

If there were no database changes, say so explicitly.

### 16. Query and Performance Impact

Report:

* Queries used
* Columns selected
* Chunking method
* Memory behavior
* N+1 prevention
* Cache TTLs
* Cache invalidation
* Importer protection
* Remote-request behavior

### 17. Security Review

Confirm handling of:

* Drafts
* Private posts
* Preview URLs
* Author privacy
* Filesystem paths
* Unsafe schemes
* Environment secrets
* IndexNow key
* XML injection
* Debug output

### 18. Automated Test Results

Provide:

* Commands executed
* Targeted test results
* Full suite results
* Test count
* Assertion count
* Pass/failure status
* Existing unrelated failures

Do not hide failures.

### 19. Manual Validation Results

List inspected endpoints and findings.

### 20. External Validation Status

State whether:

* Google Search Console submission occurred
* Bing Webmaster Tools submission occurred
* IndexNow was tested live
* Public URL validation was possible
* Only local validation was completed

Do not claim validation that was not performed.

### 21. Protected Boundaries Confirmation

Confirm protected systems were not modified or regressed.

### 22. Deployment Checklist

Provide concise deployment steps, including:

* Production application URL
* Production indexing configuration
* Config cache refresh
* Sitemap endpoint verification
* Robots verification
* Search Console submission
* Bing submission
* IndexNow activation when desired
* Queue worker requirement when applicable

### 23. Phase 15 Recommendations

Provide only recommendations relevant to:

```text
Phase 15 — Google News Optimization
```

Do not implement Phase 15.

---

## Final Instruction

Implement only:

```text
Phase 14D — XML Sitemaps, News Sitemap, Image Sitemap, Robots.txt and Indexing Notifications
```

Do not begin or implement:

* Phase 15
* Google Publisher Center integration
* Google News editorial optimization
* Google Discover optimization
* RSS/feed redesign
* Article editorial changes
* Headline rewriting
* Author admin changes
* Search Console account integration
* Search Console authentication
* Search Console API submission
* Analytics integration
* SEO dashboard
* Admin sitemap editors
