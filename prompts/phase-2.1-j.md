# Daily Samvad — Version 2.1

## Phase 2.1-J: Search Architecture, Relevance Improvements and Editorial Search

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
* Image optimization from Phase 2.1-I
* Imported WordPress posts, categories, tags, authors and SEO metadata
* Existing public search page
* Existing archive query architecture
* Existing role-based Filament dashboards and resources

Phase 2.1-J must improve public and editorial search without breaking existing URLs, imported content, publication rules, permissions, SEO or cache behavior.

The implementation must prioritize:

* Search relevance
* Search speed
* Punjabi, Hindi and English content
* Exact and partial matches
* Typo tolerance where safely possible
* Secure filtering
* Role-scoped editorial search
* Search analytics readiness
* Crawl-safe public search behavior
* Graceful fallback when optional search infrastructure is unavailable

Do not replace the existing search implementation blindly.

Audit first, benchmark current behavior and introduce improvements incrementally.

---

# 1. Primary Objective

Implement a safe, maintainable and production-ready search architecture for Daily Samvad.

The phase must cover:

* Existing search audit
* Search requirements
* Public search
* Editorial search
* Search indexing
* Search document architecture
* Multilingual normalization
* Exact matching
* Phrase matching
* Prefix matching
* Partial matching
* Typo tolerance
* Relevance ranking
* Recency ranking
* Popularity signals
* Category and tag filters
* Author filters
* Date filters
* Status filters
* Permission-safe result scoping
* Pagination
* Highlighting
* Search suggestions
* Empty states
* Search caching
* Search logging
* Search health diagnostics
* Index rebuild
* Incremental indexing
* Queue integration
* Performance benchmarks
* Automated tests
* Production rollout
* Rollback documentation

The database must remain the source of truth.

---

# 2. Core Principles

Search must be:

* Relevant
* Fast
* Secure
* Permission-aware
* Multilingual
* Resilient
* Observable
* Incrementally maintainable
* Backward-compatible
* Gracefully degradable

Every search result must respect:

* Publication status
* Visibility
* User permissions
* Editorial ownership
* Workflow state
* Scheduled publication time
* Archive state
* Soft-deletion state
* Existing application policies

Public search must never expose:

* Drafts
* Pending-review posts
* Rejected posts
* Scheduled future posts
* Archived private records
* Internal notes
* Reviewer assignments
* Workflow comments
* Hidden users
* Private analytics
* Media filesystem paths

---

# 3. Existing-State Audit

Before modifying anything, audit:

* Current public search route
* Search controller
* Search query class
* Search form
* Search result view
* Search pagination
* Search URL parameters
* Existing SQL queries
* Existing indexes
* Existing full-text indexes
* Existing LIKE-based search
* Existing title search
* Existing body-content search
* Existing excerpt search
* Existing slug search
* Existing category search
* Existing tag search
* Existing author search
* Existing SEO-title search
* Existing SEO-description search
* Existing published scope
* Existing article visibility logic
* Existing archive query architecture
* Existing DTOs
* Existing pagination components
* Existing empty states
* Existing structured data
* Existing cache behavior
* Existing Redis usage
* Existing search logs
* Existing analytics events
* Existing Filament global search
* Existing Filament resource search
* Existing admin filters
* Existing role scoping
* Existing search-related jobs
* Existing scheduler tasks
* Existing tests
* Current query performance
* Current slow-query log evidence
* Current multilingual behavior
* Current Punjabi Unicode behavior
* Current Hindi Unicode behavior
* Current English behavior
* Current punctuation handling
* Current stop-word behavior
* Current zero-result behavior
* Current SQL injection safeguards
* Current rate limiting
* Current bot traffic behavior

Document the existing state before implementation.

---

# 4. Protected Boundaries

Do not disturb:

* Existing users
* Existing roles
* Existing permissions
* Existing policies
* Editorial workflow
* Reviewer assignments
* Workflow history
* Scheduled publishing
* Imported posts
* Imported categories
* Imported tags
* Imported users
* Imported media
* Featured-media mappings
* SEO metadata
* Post slugs
* Public article URLs
* Legacy redirects
* Canonical URLs
* OpenGraph metadata
* Structured data
* Google News behavior
* Sitemaps
* Feeds
* Existing cache architecture
* Existing queue architecture
* Existing Redis prefixes
* Existing analytics data
* Existing production secrets
* Existing database records

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
```

Do not remove existing search functionality until the replacement is verified.

Do not install a new external search engine automatically.

---

# 5. Scope of This Phase

This phase includes:

* Search architecture
* Search query normalization
* Database indexing
* Search index strategy
* Public search relevance
* Editorial search
* Permission-safe filters
* Search suggestions
* Search health checks
* Search indexing commands
* Incremental indexing
* Search-result caching
* Queue integration
* Search performance tests
* Search security tests
* Search documentation

This phase does not include:

* Full analytics implementation
* AI semantic search
* Embeddings
* Vector databases
* News-Man content generation
* AI recommendations
* Personalized news feeds
* Paid external search SaaS without approval
* Public frontend redesign
* Elasticsearch cluster
* OpenSearch cluster
* Typesense cluster
* Meilisearch production activation without approval
* Algolia integration without approval

---

# 6. Search Engine Decision

Audit the current project scale and search requirements before choosing an engine.

Possible implementation paths:

```text
MySQL indexed search
MySQL FULLTEXT search
Laravel Scout with database engine
Laravel Scout with Meilisearch
Laravel Scout with Typesense
Hybrid MySQL and Redis suggestion cache
```

Default preference for this phase:

```text
Use the simplest architecture that meets verified relevance and performance requirements.
```

Do not install an external engine merely because it offers more features.

The decision must consider:

* Current post count
* Expected growth
* VPS memory
* VPS CPU
* Deployment complexity
* Punjabi support
* Hindi support
* Tokenization quality
* Typo tolerance
* Filtering
* Re-indexing time
* Operational maintenance
* Backup
* Failure recovery
* Security
* Cost

If MySQL search is sufficient, improve it first.

If an external engine is clearly required but production approval is unavailable, implement an adapter-ready architecture and report:

```text
COMPLETE WITH CONDITIONS
```

---

# 7. Search Abstraction

Create a central search abstraction.

Possible contract:

```php
interface PostSearchService
{
    public function search(PostSearchCriteria $criteria): LengthAwarePaginator;
}
```

Possible supporting classes:

```text
PostSearchCriteria
PostSearchResult
SearchQueryNormalizer
SearchFilterValidator
SearchResultPresenter
SearchIndexService
```

Do not place all search logic directly inside controllers, Livewire components or Filament resources.

---

# 8. Search Criteria DTO

Create a validated search criteria object.

Possible fields:

```text
query
page
perPage
categoryId
tagId
authorId
dateFrom
dateTo
status
language
sort
scope
userId
```

Requirements:

* Immutable where practical
* Validated
* Bounded
* Permission-aware
* Safe for cache-key generation
* Safe for logging
* No raw SQL fragments
* No arbitrary column names

---

# 9. Public Search Scope

Public search must return only posts that are:

* Published
* Publicly visible
* Not deleted
* Not archived from public view
* Published at or before current time
* Accessible through the canonical public route

Public search must not expose:

* Draft
* Pending review
* Rejected
* Scheduled future
* Private
* Internal editorial records

Use the existing canonical published scope.

Do not duplicate status logic inconsistently.

---

# 10. Editorial Search Scope

Editorial search must respect role and policy boundaries.

Possible behavior:

## Reporter

May search:

* Own drafts
* Own pending-review posts
* Own rejected/correction posts
* Own published posts where permitted

Must not search unrestricted private posts owned by others.

## Reviewer

May search:

* Assigned-review posts
* Reviewable posts according to policy
* Own content
* Published posts

## Editor

May search:

* Posts within editorial permissions
* Pending-review posts
* Approved posts
* Scheduled posts
* Published posts
* Rejected posts where permitted

## SEO Manager

May search:

* Posts available for SEO management
* Published and approved content according to policy

Must not receive broader editorial rights merely through search.

## Admin and Super Admin

May search according to existing permissions and policies.

Do not hardcode access solely by role name where permissions and policies already exist.

---

# 11. Searchable Fields

Audit and define field weights.

Possible fields:

```text
Title
Slug
Excerpt
Article body
SEO title
SEO description
Category name
Tag name
Author display name
Legacy WordPress ID
Reference code
```

Public search should prioritize editorial content fields.

Internal identifiers should be searchable only in authenticated editorial search where justified.

Do not expose internal identifiers in public result output unnecessarily.

---

# 12. Relevance Weighting

Define relevance weights.

Suggested conceptual priority:

```text
Exact title match
Exact normalized title match
Title phrase match
Title prefix match
Title term match
Slug match
SEO title match
Excerpt match
Category/tag match
Body-content match
Author match
```

Do not rank a weak body match above an exact title match.

Document the final weighting.

---

# 13. Exact-Match Behavior

Exact title and exact phrase matches should receive the strongest relevance boost.

Normalize safely before comparison.

Examples:

```text
"Punjab election"
"ਪੰਜਾਬ ਚੋਣ"
"पंजाब चुनाव"
```

Do not require case-sensitive matching for English.

Preserve Unicode correctness.

---

# 14. Prefix and Partial Matching

Support useful partial matching for titles.

Examples:

```text
Amrit
Amritsar
Punjab
Punj
```

Avoid unbounded leading-wildcard queries on large text columns where they cause full table scans.

Prefer indexed prefixes or constrained fallback logic.

---

# 15. Phrase Matching

Support quoted or phrase-like matching where practical.

Example:

```text
"Punjab government"
```

Do not implement an overly complex query language unless justified.

Treat malformed quotes safely.

---

# 16. Typo Tolerance

Typo tolerance is optional for the first safe implementation.

Possible approaches:

* Search suggestions from indexed terms
* Prefix matching
* Levenshtein comparison on bounded candidate sets
* External engine typo tolerance
* Synonym and alias mapping

Do not run expensive edit-distance calculations across all posts per request.

If full typo tolerance requires an external engine, document and defer production activation.

---

# 17. Multilingual Search

Search must support:

* Punjabi Gurmukhi
* Hindi Devanagari
* English
* Mixed-language queries
* Romanized Punjabi or Hindi where practical

Verify:

* UTF-8 storage
* utf8mb4 connection
* Collation behavior
* Unicode normalization
* Whitespace
* Punctuation
* Apostrophes
* Dashes
* Smart quotes
* Numerals
* English case normalization

Do not transliterate automatically unless accuracy is tested.

---

# 18. Unicode Normalization

Create a safe query normalizer.

Possible responsibilities:

* Trim leading and trailing whitespace
* Collapse repeated whitespace
* Normalize Unicode forms where supported
* Normalize smart punctuation
* Remove invisible control characters
* Bound query length
* Preserve meaningful Punjabi and Hindi characters

Do not strip letters from non-Latin scripts.

---

# 19. Search Query Length

Define safe limits.

Example:

```text
Minimum useful length: 2 characters
Maximum accepted length: 200 characters
```

Single-character searches may be rejected or restricted when too expensive.

Do not allow unlimited query length.

Return clear validation feedback.

---

# 20. Stop Words

Audit stop-word behavior.

English stop words may reduce noise, but Punjabi and Hindi stop-word handling must be conservative.

Do not remove words blindly if it harms names, places or headlines.

Document whether stop words are used.

---

# 21. Stemming and Morphology

Do not introduce unsupported stemming for Punjabi or Hindi without evidence.

English stemming may be considered only if the chosen engine supports it reliably.

Prefer predictable exact and term matching over inaccurate linguistic transformations.

---

# 22. Synonyms and Aliases

Prepare a controlled synonym architecture where useful.

Examples:

```text
CM → Chief Minister
PM → Prime Minister
Punjab Govt → Punjab Government
Jalandhar → जालंधर → ਜਲੰਧਰ
```

Do not create uncontrolled automatic cross-language synonyms.

Synonyms must be reviewed and version-controlled or managed through an approved administrative process.

---

# 23. Search Index Document

If using a separate search index, define a stable document.

Possible fields:

```text
id
title
slug
excerpt
body_text
seo_title
seo_description
category_ids
category_names
tag_ids
tag_names
author_id
author_name
status
published_at
updated_at
language
visibility
featured_media_id
popularity_score
```

Do not index:

* Passwords
* Email addresses unnecessarily
* Internal workflow comments
* Private notes
* Reviewer comments
* Session data
* Secrets
* Raw private metadata

---

# 24. HTML-to-Text Normalization

Article body search should use normalized text.

Requirements:

* Strip HTML safely
* Decode entities
* Preserve paragraph separation where useful
* Remove scripts and styles
* Avoid indexing navigation fragments
* Avoid indexing shortcode noise
* Bound indexed size if necessary

Do not mutate stored article HTML.

---

# 25. Database Indexes

Audit existing indexes before adding new ones.

Possible indexes:

```text
posts.status
posts.published_at
posts.author_id
posts.slug
posts.updated_at
posts.created_at
post_category pivot
post_tag pivot
media relationships
```

For compound queries, consider composite indexes based on actual query plans.

Use:

```sql
EXPLAIN
```

or equivalent.

Do not add redundant indexes blindly.

Use additive migrations only.

---

# 26. Full-Text Indexes

If using MySQL FULLTEXT, audit compatibility for:

* Storage engine
* MySQL version
* utf8mb4
* Punjabi
* Hindi
* Minimum token length
* Stop words
* Natural-language mode
* Boolean mode

Test actual Punjabi and Hindi search results.

Do not assume FULLTEXT works well for every language without evidence.

---

# 27. Fallback Search Strategy

Define graceful fallback.

Possible flow:

```text
1. Exact title/slug match
2. Indexed/full-text search
3. Prefix title search
4. Bounded partial title search
5. No-result suggestions
```

If an external search engine is unavailable:

* Public search must remain functional.
* Editorial search must remain permission-safe.
* The system may fall back to MySQL.
* The user must receive a normal search page, not a server error.

---

# 28. Search Sorting

Provide safe sorting options.

Public options may include:

```text
Relevance
Newest
Oldest
Most viewed
```

Editorial options may include:

```text
Updated recently
Created recently
Publication date
Status
Title
Relevance
```

Validate sort values against an allow-list.

Do not accept arbitrary SQL column names from the request.

---

# 29. Recency Ranking

Recent posts may receive a moderate relevance boost.

Do not let recency overpower an exact-title match.

Document the ranking balance.

Scheduled future posts must remain excluded from public search regardless of recency.

---

# 30. Popularity Ranking

Popularity may use verified signals such as:

* Visit count
* Unique view count
* Recent view score

Do not use popularity if the current analytics data is unreliable.

Do not make popularity the default ranking without relevance safeguards.

Phase 2.1-K may improve these signals later.

---

# 31. Category Filter

Public search may support category filtering.

Requirements:

* Valid category ID or slug
* Public category
* Existing relationship
* Efficient query
* Canonical parameter handling
* Correct empty state

Do not expose hidden internal taxonomy values.

---

# 32. Tag Filter

Public search may support tag filtering.

Requirements:

* Valid tag ID or slug
* Efficient relationship query
* Correct pagination
* Safe empty state

Avoid extremely expensive tag combinations without limits.

---

# 33. Author Filter

Public author filtering must use publicly visible author identity.

Editorial author filtering must respect user-management permissions.

Do not expose private email addresses or internal user data.

---

# 34. Date Filters

Support bounded date filters.

Possible parameters:

```text
from
to
year
month
```

Validate date ranges.

Do not allow malformed or excessively broad requests to trigger unbounded expensive queries without controls.

---

# 35. Status Filters

Status filtering is for authorized editorial search only.

Validate against known workflow statuses.

Public search must ignore or reject status parameters that attempt to expose non-public content.

---

# 36. Language Filter

If reliable language metadata exists, support filtering.

If language is not reliably stored, do not infer and persist language automatically without a migration plan.

Document current language detection limitations.

---

# 37. Pagination

Use bounded pagination.

Suggested defaults:

```text
20 results per page
Maximum 50 results per page
```

Preserve search and filter parameters across pages.

Use efficient pagination for the chosen engine.

Do not allow arbitrary very large page sizes.

---

# 38. Deep Pagination

Audit behavior for very high page numbers.

Options:

* Standard pagination for moderate result sets
* Cursor pagination where suitable
* Maximum page guard
* Search-engine result-window limits

Do not allow abusive deep pagination to overload the database.

---

# 39. Search Result Presentation

Public result cards should include:

* Title
* Canonical URL
* Excerpt or highlighted snippet
* Publication date
* Category
* Author where already public
* Featured image through Phase 2.1-I component
* Accessible markup

Do not reveal internal workflow status publicly.

---

# 40. Search Highlighting

Highlight matching terms safely.

Requirements:

* Escape result content first
* Highlight only normalized matched terms
* Avoid injecting raw HTML
* Bound snippet length
* Preserve Unicode
* Avoid broken tags

Do not trust search-engine highlight HTML without sanitization.

---

# 41. Search Snippets

Generate useful snippets from:

```text
Excerpt
Matching body region
SEO description
Fallback summary
```

Prefer a snippet containing the matched term.

Do not return the entire article body.

---

# 42. Search Suggestions

Possible suggestions:

* Recent popular searches
* Matching titles
* Categories
* Tags
* Corrected query
* Related terms

Suggestions must be bounded and cached briefly.

Do not expose private editorial titles in public suggestions.

---

# 43. Autocomplete

Autocomplete is optional.

If implemented:

* Minimum input length
* Debounce
* Maximum result count
* Rate limiting
* Public-only scope
* Safe escaping
* Accessible keyboard navigation
* No private data leakage
* Redis caching where appropriate

Do not execute a heavy body-content query on every keystroke.

---

# 44. Zero-Result State

Provide a useful empty state.

Possible content:

* Corrected spacing
* Suggested categories
* Suggested tags
* Recent articles
* Search tips

Do not show unrelated private or draft content.

Do not automatically redirect zero-result searches.

---

# 45. Search URL Structure

Preserve existing public search URL where possible.

Example:

```text
/search?q=punjab
```

Additional filters may use stable query parameters.

Do not change the current route without redirects and compatibility verification.

---

# 46. Canonical and SEO Behavior

Search-result pages generally should not compete with article pages in search engines.

Audit current behavior.

Possible policy:

```html
<meta name="robots" content="noindex,follow">
```

Use only if consistent with the project’s SEO strategy.

Preserve:

* Canonical handling
* Query parameters
* OpenGraph behavior
* Sitemap exclusion
* Structured data validity

Do not add search pages to the sitemap unless explicitly justified.

---

# 47. Search Caching

Use Phase 2.1-G architecture.

Cache only safe public search results.

Cache key must include:

* Normalized query
* Filters
* Sort
* Page
* Per-page
* Locale/language where relevant
* Search-index version
* Application environment

Do not cache:

* Authenticated editorial results globally
* User-specific result sets without user/permission scope
* Private statuses in shared cache
* CSRF data
* Full request objects

---

# 48. Search Cache TTL

Use short TTLs.

Suggested range:

```text
30 seconds to 5 minutes
```

Popular stable searches may use a slightly longer bounded TTL.

Do not use long immutable caching for search results.

---

# 49. Search Cache Invalidation

Public search caches may use:

* Short TTL
* Search index version
* Bounded tag invalidation
* Publication version bump

Avoid invalidating every possible query individually.

Publishing, unpublishing, archiving or correcting public content must become visible within the documented freshness window.

Do not use `Cache::flush()`.

---

# 50. Search Index Versioning

Define a version:

```text
SEARCH_INDEX_VERSION=v1
```

Use it in:

* Index documents
* Cache keys
* Rebuild decisions
* Deployment documentation

Changing searchable fields or normalization rules should support controlled re-indexing.

---

# 51. Incremental Indexing

Index changes after:

* Post publication
* Published post update
* Title update
* Excerpt update
* Body update
* SEO-title update
* Category update
* Tag update
* Author update
* Unpublish
* Archive
* Restore
* Delete

Use events or observers carefully.

Do not index uncommitted data.

Dispatch queue jobs after commit where applicable.

---

# 52. Search Index Job

Possible job:

```text
UpdatePostSearchIndex
```

Requirements:

* Uses scalar post ID
* Dispatches after commit
* Is idempotent
* Is unique or overlap-protected by post/version
* Uses the existing queue architecture
* Defines timeout
* Defines retries
* Defines backoff
* Handles missing post
* Removes non-searchable content
* Logs safe context

Possible queue:

```text
search
```

If a new queue is added, update Phase 2.1-H documentation and worker topology.

Do not create a new queue without ensuring a worker consumes it.

---

# 53. Index Removal

When content is:

* Unpublished
* Archived
* Deleted
* Made private

remove it from public search promptly.

Editorial search may still use the database under permission-safe scope.

Do not leave stale public documents searchable.

---

# 54. Index Rebuild Command

Create a safe command.

Suggested:

```bash
php artisan search:reindex
```

Possible options:

```text
--post=
--published
--all-authorized
--missing
--stale
--limit=
--chunk=
--queue
--sync
--dry-run
--force
```

Requirements:

* Safe defaults
* Bounded chunks
* Dry-run
* Progress
* Summary
* Resume/checkpoint support where needed
* No automatic full production rebuild
* No secret output

---

# 55. Search Audit Command

Create:

```bash
php artisan search:audit
```

Possible checks:

* Search engine availability
* Index version
* Published post count
* Indexed document count
* Missing documents
* Stale documents
* Non-public documents in public index
* Invalid filters
* Index latency
* Query performance
* Queue backlog
* Failed indexing jobs

Return a non-zero code for critical integrity failures.

---

# 56. Search Health Command

Create:

```bash
php artisan search:health
```

Check:

* Database connectivity
* Search-engine connectivity where applicable
* Index availability
* Index version
* Basic known-query result
* Public-scope integrity
* Query latency
* Queue readiness
* Final health status

Do not expose article content or private data in command output.

---

# 57. Queue Integration

Use Phase 2.1-H standards.

Verify:

* Queue exists
* Worker consumes it
* Retry-after is safe
* Job timeout is bounded
* Failed jobs are recoverable
* Re-indexing is resumable
* Duplicate jobs are prevented
* Publication does not depend on optional index completion

If index update fails:

* Publication must remain committed.
* Public fallback search must remain available where possible.
* Failure must be observable.
* The index must be repairable.

---

# 58. Search and Publication Workflow

Publishing flow:

```text
1. Validate permission
2. Commit publication state
3. Record workflow history
4. Invalidate article and archive cache
5. Dispatch search-index update after commit
6. Dispatch optional cache warming
7. Log failures safely
```

Do not delay publication merely because an optional external search engine is temporarily unavailable.

---

# 59. Search and Scheduled Publishing

When a scheduled post becomes public:

* Add to search index once
* Prevent duplicate indexing
* Invalidate search freshness version where used
* Preserve exact publication time
* Avoid indexing future scheduled content early

Add tests for duplicate scheduler runs.

---

# 60. Filament Global Search

Audit Filament global search behavior.

Requirements:

* Permission-aware resources
* Role-scoped records
* No private data leakage
* Efficient search columns
* Bounded results
* Useful labels
* Correct URLs
* Correct visibility

Do not let Filament global search bypass resource authorization.

---

# 61. Filament Post Search

Improve post resource search safely.

Possible searchable fields:

* Title
* Slug
* Legacy WordPress ID
* Reference code
* Author name
* Category
* Tag

Use appropriate filters for:

* Status
* Author
* Reviewer
* Category
* Tag
* Publication date
* Created date
* Updated date
* SEO completeness

Respect policies and role-specific query scoping.

---

# 62. Reviewer Search

Reviewer search should prioritize:

* Assigned posts
* Pending-review posts they may access
* Correction-returned posts
* Recently reviewed posts

Do not allow reviewers to search all private content unless their permissions explicitly allow it.

---

# 63. Reporter Search

Reporter search should prioritize:

* Own drafts
* Own pending posts
* Own rejected/correction posts
* Own published posts

Do not expose other reporters’ drafts.

---

# 64. SEO Manager Search

SEO search may include filters such as:

* Missing SEO title
* Missing SEO description
* Missing featured image
* Missing canonical
* Missing schema fields
* Low content length
* Published date
* Category
* Author

Do not modify SEO data automatically during search.

---

# 65. Search Permissions

Possible permissions:

```text
search public content
search own posts
search assigned reviews
search editorial content
search all posts
manage search index
view search diagnostics
```

Do not add permissions unless required and aligned with Phase 2.1-B.

Report all additions explicitly.

---

# 66. Search Rate Limiting

Protect public search and autocomplete.

Use separate rate limits for:

* Full search
* Autocomplete
* Health endpoints if any
* Authenticated editorial search where justified

Rate limiting must consider:

* IP
* Authenticated user
* Proxy headers
* Cloudflare or reverse-proxy trust configuration

Do not block legitimate readers excessively.

---

# 67. Search Abuse Protection

Protect against:

* Extremely long queries
* Repeated wildcard abuse
* Deep pagination abuse
* Invalid filter combinations
* SQL injection
* Regex denial of service
* High-frequency autocomplete requests
* Bot scraping
* Search-cache explosion

Use bounded inputs and normalized cache keys.

---

# 68. Cache-Key Explosion Prevention

Do not cache every arbitrary query forever.

Safeguards:

* Query-length limits
* Short TTL
* Maximum filter count
* Stable normalization
* Bounded pagination
* Optional minimum result count before caching
* Cache-key hashing

Do not include raw unbounded query text directly in Redis keys.

---

# 69. Search Logging

Log search operational data safely.

Possible fields:

```text
Normalized query hash
Result count
Duration
Filters
Sort
Public/editorial scope
Cache hit
Search engine
Error class
```

Do not log:

* Private article body
* Passwords
* Tokens
* Raw session data
* Sensitive personal data

Raw public query logging requires a clear retention and privacy decision.

---

# 70. Search Analytics Readiness

Prepare for Phase 2.1-K.

Potential events:

* Search performed
* Search result clicked
* Zero-result search
* Filter applied
* Suggestion selected
* Search duration
* Result rank clicked

Do not implement full analytics if Phase 2.1-K has not started.

Define event contracts without collecting excessive personal data.

---

# 71. Search Result Click Tracking

If implemented later, use first-party, privacy-conscious tracking.

Do not alter result URLs in a way that harms SEO or user trust.

Do not add invasive cross-site tracking.

---

# 72. Search Metrics

Track lightweight operational metrics:

* Query count
* Average duration
* P95 duration
* Cache-hit ratio
* Zero-result rate
* Index size
* Index lag
* Failed indexing jobs
* Top slow queries
* Queue wait time

Do not build the full analytics dashboard in this phase.

---

# 73. Search Performance Baseline

Measure before implementation:

* Exact-title query
* Common keyword
* Punjabi keyword
* Hindi keyword
* English keyword
* Multi-term query
* Category-filtered query
* Tag-filtered query
* Zero-result query
* Deep page query
* Editorial scoped query

Record:

* SQL count
* Query duration
* Total response time
* Memory
* Result count
* Query plan
* Cache status
* Relevance quality

---

# 74. Search Performance Targets

Define practical targets based on the VPS.

Possible initial targets:

```text
Database/search execution under 150 ms for common warm queries
Total server response under 400 ms for standard search pages
Autocomplete under 200 ms where implemented
No unindexed full table scan for common queries
```

Adapt based on actual baseline.

Do not claim success without measurements.

---

# 75. Query-Plan Verification

Use `EXPLAIN` for representative SQL.

Verify:

* Relevant index usage
* Join order
* Rows examined
* Temporary tables
* Filesort
* Full table scans

Not every filesort is automatically incorrect, but expensive plans must be documented.

---

# 76. N+1 Prevention

Search results must eager-load only required relationships.

Possible relationships:

* Featured media
* Primary category
* Author
* Tags only when displayed

Do not load full article bodies or large relationship graphs unnecessarily.

---

# 77. Payload Control

Search result DTOs must contain only fields required for display.

Do not serialize:

* Full model relations
* Internal notes
* Workflow history
* Private metadata
* Large HTML bodies

---

# 78. Mobile Search UX

Verify:

* Search form fits small screens
* Filters remain usable
* Result cards do not overflow
* Punjabi and Hindi text wraps correctly
* Images use responsive components
* Pagination is usable
* Keyboard does not obscure controls
* Autocomplete remains accessible

Do not perform a broad frontend redesign.

---

# 79. Accessibility

Verify:

* Search input has a label
* Search button has an accessible name
* Result count is announced appropriately
* Empty state is understandable
* Filters use labels
* Keyboard navigation works
* Highlight markup remains readable
* Autocomplete uses accessible roles where implemented
* Focus behavior is stable

---

# 80. Error Handling

Handle:

* Search engine unavailable
* Redis unavailable
* Database timeout
* Invalid query
* Invalid filter
* Excessive page number
* Index missing
* Index version mismatch
* Queue failure
* Corrupt search document
* Permission denial

Public users should receive a safe search page, not stack traces.

---

# 81. Search Engine Outage

If an optional external engine is unavailable:

```text
Public search:
Fall back to safe MySQL search

Editorial search:
Fall back to policy-scoped database search

Index updates:
Record failures and retry

Autocomplete:
Disable or return empty suggestions safely
```

Do not silently expose broader result sets during fallback.

---

# 82. Security Tests

Verify:

* SQL injection payloads remain harmless
* Wildcard abuse is bounded
* Invalid sort columns are rejected
* Public status filtering cannot expose drafts
* Editorial results respect policies
* Reporter cannot search others’ drafts
* Reviewer cannot access unassigned private posts
* Subscriber cannot access editorial search
* Autocomplete does not leak private titles
* Highlighting prevents XSS
* Query logs do not contain secrets
* Cache does not leak authenticated results
* Search index contains no protected fields

---

# 83. Automated Tests

Create focused tests.

## 83.1 Normalization Tests

Verify:

* Whitespace collapse
* Unicode preservation
* Punjabi query handling
* Hindi query handling
* English case handling
* Punctuation normalization
* Maximum length enforcement
* Control-character removal
* Empty-query behavior

## 83.2 Public Search Tests

Verify:

* Published post appears
* Draft does not appear
* Pending-review post does not appear
* Rejected post does not appear
* Future scheduled post does not appear
* Archived/private post does not appear
* Exact-title match ranks first
* Phrase match outranks body-only match
* Pagination preserves query
* Filters work
* Zero-result state works

## 83.3 Multilingual Tests

Verify representative:

* Punjabi title query
* Hindi title query
* English title query
* Mixed-language title
* Unicode punctuation
* Numerals
* Partial title match

## 83.4 Ranking Tests

Verify:

* Exact title > title phrase
* Title phrase > title term
* Title term > body-only match
* Exact slug match is strong
* Recency does not overpower exact match
* Popularity does not overpower exact match

## 83.5 Editorial Scope Tests

Verify:

* Reporter sees own draft
* Reporter does not see another reporter’s draft
* Reviewer sees assigned post
* Reviewer does not see inaccessible post
* Editor sees policy-authorized editorial content
* SEO Manager sees only SEO-manageable content
* Subscriber cannot access editorial search
* Admin access follows permissions

## 83.6 Filter Tests

Verify:

* Category
* Tag
* Author
* Date range
* Status
* Sort
* Invalid values
* Combined filters
* Public status override attempt

## 83.7 Indexing Tests

Verify:

* Published post indexes after commit
* Rolled-back publication does not index
* Updated post re-indexes
* Unpublished post is removed
* Archived post is removed
* Future scheduled post is excluded
* Duplicate indexing is idempotent
* Missing post is handled safely
* Index version is recorded

## 83.8 Queue Tests

Verify:

* Job uses correct queue
* Job is unique or overlap-protected
* Retry values are bounded
* Backoff is correct
* Timeout exists
* Failure is recoverable
* Worker outage does not break publication

## 83.9 Cache Tests

Verify:

* Public search result caches safely
* Cache key includes normalized query and filters
* Cache key includes index version
* Different pages do not collide
* Different filters do not collide
* Editorial cache is user/permission scoped or disabled
* No private-result leakage
* No broad cache flush

## 83.10 Highlight Tests

Verify:

* Safe HTML escaping
* Unicode terms highlighted correctly
* Script tags remain escaped
* Snippets are bounded
* Empty query does not inject markup

## 83.11 Command Tests

Verify:

* Audit command
* Health command
* Reindex dry-run
* Reindex limit
* Reindex one post
* Missing-only mode
* Stale-only mode
* Non-zero exit on critical failure
* No full rebuild without explicit option

## 83.12 Regression Tests

Verify:

* Public article pages
* Homepage
* Category archives
* Tag archives
* Author archives
* Date archives
* Login
* Filament
* Role dashboards
* Editorial workflow
* Scheduled publishing
* Redis
* Cache
* Queue
* Images
* SEO
* Sitemaps
* Feeds
* Legacy redirects
* WordPress importer

---

# 84. Search Relevance Fixtures

Create representative test fixtures containing:

* Similar titles
* Exact title
* Phrase title
* Body-only match
* Punjabi title
* Hindi title
* English title
* Duplicate terms
* Old highly relevant post
* Recent weakly relevant post
* Popular weak match
* Scheduled future post
* Draft
* Archived post

Use deterministic assertions.

---

# 85. Real Search Verification

Test against a controlled production-like dataset.

Queries should include real examples from:

* Punjab
* Jalandhar
* National news
* Politics
* Crime
* Sports
* Punjabi headlines
* Hindi headlines
* English terms
* Person names
* Place names

Do not publish private dataset excerpts in documentation.

---

# 86. Relevance Review

Manually review top results for at least:

```text
20 representative queries
```

Score:

* Exactness
* Usefulness
* Freshness
* Language correctness
* Noise
* Missing expected result

Document tuning decisions.

Do not rely only on automated execution-time tests.

---

# 87. Production Rollout Strategy

Recommended stages:

```text
Stage 1:
Audit current search and indexes

Stage 2:
Add query normalization and validation

Stage 3:
Add safe database indexes

Stage 4:
Introduce centralized search service

Stage 5:
Improve exact, phrase and title matching

Stage 6:
Add public filters

Stage 7:
Add role-scoped editorial search

Stage 8:
Add search caching

Stage 9:
Add incremental indexing architecture

Stage 10:
Enable optional suggestions

Stage 11:
Evaluate external search engine separately
```

Do not replace the entire search stack in one deployment.

---

# 88. Deployment Procedure

Recommended sequence:

```text
1. Review current search health
2. Back up database schema
3. Deploy code
4. Run additive index migrations
5. Verify query plans
6. Build configuration cache
7. Verify Redis and queue health
8. Run search audit
9. Run controlled reindex if required
10. Verify public search
11. Verify role-scoped editorial search
12. Verify SEO robots/canonical behavior
13. Monitor slow queries and failures
14. Expand rollout
```

Do not run an uncontrolled full reindex automatically.

---

# 89. Rollback Plan

Document rollback for:

* Poor relevance
* Slow queries
* External engine outage
* Index corruption
* Queue backlog
* Private-data leakage
* Search cache collision
* Invalid multilingual behavior
* Excessive Redis usage
* Deployment failure

Possible rollback:

```text
Disable advanced search feature flag
Return to verified database search
Disable autocomplete
Disable external engine adapter
Stop search worker
Revert search Blade changes
Revert application commit
Remove only additive indexes through reviewed rollback
Preserve search logs and failure evidence
```

Do not delete source content.

---

# 90. Feature Flags

Use configuration-driven activation.

Possible flags:

```text
SEARCH_ADVANCED_ENABLED
SEARCH_CACHE_ENABLED
SEARCH_AUTOCOMPLETE_ENABLED
SEARCH_EXTERNAL_ENGINE_ENABLED
SEARCH_EDITORIAL_ENABLED
SEARCH_TYPO_TOLERANCE_ENABLED
```

Use safe defaults.

Do not commit production secrets.

---

# 91. Documentation Deliverables

Create or update:

```text
docs/version-2.1/phase-2.1-j-search-improvements.md
docs/version-2.1/search-existing-state-audit.md
docs/version-2.1/search-engine-decision.md
docs/version-2.1/search-field-and-weight-map.md
docs/version-2.1/search-query-normalization.md
docs/version-2.1/search-multilingual-support.md
docs/version-2.1/search-public-scope.md
docs/version-2.1/search-editorial-scope.md
docs/version-2.1/search-filter-standard.md
docs/version-2.1/search-index-architecture.md
docs/version-2.1/search-queue-runbook.md
docs/version-2.1/search-cache-strategy.md
docs/version-2.1/search-security.md
docs/version-2.1/search-health-monitoring.md
docs/version-2.1/search-performance-baseline.md
docs/version-2.1/search-performance-comparison.md
docs/version-2.1/search-production-rollout.md
docs/version-2.1/search-rollback-plan.md
```

Documentation must include:

* Existing search audit
* Search-engine decision
* Searchable fields
* Relevance weights
* Query normalization
* Multilingual behavior
* Filters
* Public scope
* Editorial scope
* Permission boundaries
* Index architecture
* Incremental indexing
* Queue behavior
* Cache behavior
* Rate limiting
* Security controls
* Health commands
* Re-index command
* Performance baseline
* Performance comparison
* Rollout
* Rollback
* Test results
* Known limitations
* Deferred items

Do not include secrets or private article content.

---

# 92. Completion Criteria

Phase 2.1-J is complete only when:

* Existing search behavior is audited.
* Search-engine decision is documented.
* Search logic is centralized.
* Query normalization is implemented.
* Punjabi, Hindi and English queries are tested.
* Public search returns only publicly visible content.
* Editorial search respects policies and role scope.
* Exact-title matches rank strongly.
* Phrase and title matches outrank body-only matches.
* Search filters are validated.
* Pagination is bounded.
* Sort options are allow-listed.
* Search result highlighting is safe.
* Search result snippets are bounded.
* Database indexes are based on query evidence.
* Full-text behavior is tested if used.
* Search fallback exists.
* Search cache is safe and versioned.
* Index updates occur after commit.
* Unpublished content is removed from public search.
* Queue integration is idempotent.
* Search audit and health commands exist.
* Reindex command is bounded and supports dry-run.
* Search abuse protections exist.
* Search performance is measured.
* Relevance is manually reviewed.
* Focused tests are run.
* Full regression results are reported honestly.
* Required documentation is complete.

---

# 93. Deferred Items

Do not implement in this phase unless explicitly approved:

* AI semantic search
* Embeddings
* Vector search
* Personalized search
* Personalized recommendations
* Automatic cross-language translation
* Automatic transliteration
* Elasticsearch cluster
* OpenSearch cluster
* Meilisearch production service
* Typesense production service
* Algolia
* Search advertising
* Full search analytics dashboard
* User behavioral profiling
* News-Man integration
* AI-generated suggestions
* Voice search

---

# 94. Required Completion Report Format

Return the completion report using this exact structure:

## 1. Executive Summary

## 2. Existing Search Audit

## 3. Existing Public Search Architecture

## 4. Existing Editorial Search Architecture

## 5. Search Engine Decision

## 6. Search Abstraction

## 7. Search Criteria and Validation

## 8. Searchable Fields

## 9. Relevance Weighting

## 10. Exact-Match Behavior

## 11. Phrase and Prefix Matching

## 12. Partial and Typo-Tolerance Decision

## 13. Punjabi Search Verification

## 14. Hindi Search Verification

## 15. English Search Verification

## 16. Unicode Normalization

## 17. Stop-Word and Stemming Decision

## 18. Synonym and Alias Strategy

## 19. Public Search Scope

## 20. Editorial Search Scope

## 21. Reporter Search Scope

## 22. Reviewer Search Scope

## 23. Editor Search Scope

## 24. SEO Manager Search Scope

## 25. Search Permission Boundaries

## 26. Database Index Changes

## 27. Full-Text Search Assessment

## 28. Search Index Document Architecture

## 29. Incremental Indexing

## 30. Index Removal Behavior

## 31. Search Queue Integration

## 32. Search Job Idempotency

## 33. Search Retry, Backoff and Timeout

## 34. Search Filters

## 35. Search Sorting

## 36. Recency Ranking

## 37. Popularity Ranking Decision

## 38. Pagination and Deep Pagination

## 39. Search Result Presentation

## 40. Highlighting and Snippets

## 41. Suggestions and Autocomplete

## 42. Zero-Result Experience

## 43. Search URL Compatibility

## 44. Search SEO and Robots Behavior

## 45. Search Cache Architecture

## 46. Search Cache Invalidation

## 47. Search Rate Limiting

## 48. Search Abuse Protection

## 49. Search Logging

## 50. Search Analytics Readiness

## 51. Filament Global Search

## 52. Filament Post Search and Filters

## 53. Search Audit Command

## 54. Search Health Command

## 55. Reindex Command

## 56. External Engine Failure Fallback

## 57. Search Security Verification

## 58. Search Performance Baseline

## 59. Search Performance Comparison

## 60. Query-Plan Verification

## 61. Relevance Review

## 62. Automated Tests Added or Updated

## 63. Public Search Test Results

## 64. Multilingual Search Test Results

## 65. Editorial Scope Test Results

## 66. Indexing and Queue Test Results

## 67. Cache Test Results

## 68. Security Test Results

## 69. Regression Test Results

## 70. Full Test-Suite Result

## 71. Production Rollout Procedure

## 72. Rollback Procedure

## 73. Backward-Compatibility Verification

## 74. Documentation Created

## 75. Files Created or Modified

## 76. Commands Executed

## 77. Risks and Open Questions

## 78. Deferred Items

## 79. Final Phase Decision

The final phase decision must be one of:

```text
COMPLETE
COMPLETE WITH CONDITIONS
INCOMPLETE
```

Explain the decision using verified relevance, security, performance, indexing and test evidence.

---

# 95. Strict Rules

* Audit before replacing search logic.
* Preserve existing public search URLs where possible.
* Keep the database as the source of truth.
* Do not expose drafts in public search.
* Do not expose future scheduled posts.
* Do not expose archived or private content.
* Do not let editorial search bypass policies.
* Do not let Reporter search other reporters’ private drafts.
* Do not let Reviewer search unauthorized private posts.
* Do not expose private titles through autocomplete.
* Do not accept arbitrary sort columns.
* Do not accept raw SQL from request parameters.
* Bound query length.
* Bound page size.
* Protect against deep-pagination abuse.
* Protect against wildcard abuse.
* Preserve Punjabi and Hindi Unicode.
* Do not strip non-Latin characters.
* Do not assume MySQL FULLTEXT multilingual quality without testing.
* Do not run edit-distance logic across all posts.
* Do not mutate stored article HTML for indexing.
* Do not index private notes or workflow comments.
* Do not log secrets.
* Do not cache authenticated result sets globally.
* Do not include raw unbounded search text in Redis keys.
* Do not use `Cache::flush()`.
* Do not use `FLUSHALL`.
* Do not use `FLUSHDB`.
* Dispatch index updates after commit.
* Make indexing jobs idempotent.
* Remove unpublished content from public search.
* Keep publication independent from optional external search availability.
* Provide a safe fallback.
* Do not install an external search service automatically.
* Do not modify `.env` without deployment authority.
* Do not change roles or permissions casually.
* Do not change editorial workflow rules.
* Do not modify imported posts.
* Do not change slugs or public URLs.
* Do not break SEO, sitemaps, feeds or Google News.
* Do not run destructive database commands.
* Do not upgrade unrelated dependencies.
* Do not claim relevance improvement without representative review.
* Do not claim performance improvement without measurement.
* Do not report fake queue tests as real worker evidence.
* Clearly report environmental, skipped and pre-existing failures.
* Preserve backward compatibility.
