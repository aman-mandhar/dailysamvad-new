# Daily Samvad — Version 2.1

## Phase 2.1-L: Complete Testing, Stabilization, Production Readiness and Final Sign-Off

You are working on the existing Daily Samvad Laravel application.

This is the final implementation and verification phase of Daily Samvad Version 2.1.

Previous Version 2.1 phases include:

```text
2.1-A — Baseline Audit
2.1-B — RBAC and Role Intent Architecture
2.1-C — Editorial Workflow Completion
2.1-D — Separate Dynamic Role Dashboards
2.1-E — Dashboard UI/UX Redesign
2.1-F — Redis Foundation
2.1-G — Cache Architecture
2.1-H — Queue Architecture
2.1-I — Image Optimization
2.1-J — Search Improvements
2.1-K — Analytics Architecture
2.1-L — Testing, Stabilization and Final Sign-Off
```

The application currently includes or is expected to include:

* Laravel application architecture
* Filament administration panel
* Livewire components
* MySQL database
* Spatie Laravel Permission
* Imported WordPress users
* Imported WordPress posts
* Imported categories
* Imported tags
* Imported media
* Imported SEO metadata
* Public news frontend
* Role-based authentication
* Role-based dashboards
* Reporter workflow
* Reviewer workflow
* Editor workflow
* Scheduled publishing
* Redis
* Application caching
* Full-page or response caching where implemented
* Queue workers
* Image optimization
* Responsive images
* Search architecture
* Analytics architecture
* Google News optimization
* Structured data
* OpenGraph
* Sitemaps
* Feeds
* Legacy WordPress redirects

Phase 2.1-L must not become another uncontrolled feature-development phase.

Its primary purpose is to:

* Audit all Version 2.1 work
* Verify cross-phase integration
* Find defects
* Fix verified defects
* Remove regressions
* Improve test coverage
* Validate production infrastructure
* Confirm backward compatibility
* Verify security
* Verify performance
* Verify data integrity
* Verify deployment readiness
* Produce final Version 2.1 documentation
* Deliver an evidence-based final sign-off

Do not declare Version 2.1 complete merely because individual phases were reported as complete.

The final decision must be based on real application evidence.

---

# 1. Primary Objective

Perform a complete technical, functional, security, performance and operational verification of Daily Samvad Version 2.1.

The phase must cover:

* Repository state
* Dependency state
* Configuration
* Environment readiness
* Database integrity
* Imported-data integrity
* Authentication
* Authorization
* Role behavior
* Editorial workflow
* Dashboards
* Public frontend
* Redis
* Cache
* Queue
* Images
* Search
* Analytics
* SEO
* Google News
* Scheduled tasks
* Deployment
* Monitoring
* Backup
* Recovery
* Security
* Accessibility
* Responsive behavior
* Browser compatibility
* Performance
* Automated tests
* Manual verification
* Production smoke testing
* Rollback readiness
* Documentation
* Final sign-off

---

# 2. Core Principles

The final phase must be:

* Evidence-based
* Regression-focused
* Production-oriented
* Non-destructive
* Repeatable
* Documented
* Honest
* Role-aware
* Security-conscious
* Data-safe
* Performance-conscious
* Backward-compatible

Do not hide:

* Failed tests
* Skipped tests
* Environmental failures
* Pre-existing defects
* Newly introduced regressions
* Missing infrastructure
* Incomplete worker verification
* Missing production evidence
* Unsupported formats
* Unverified assumptions
* Deferred items

---

# 3. Protected Boundaries

Do not disturb:

* Existing production users
* Existing passwords
* Existing roles
* Existing permissions
* Existing role assignments
* Existing workflow history
* Existing reporter assignments
* Existing reviewer assignments
* Existing editor actions
* Existing posts
* Existing media
* Existing categories
* Existing tags
* Existing SEO metadata
* Existing analytics counters
* Existing search records
* Existing Redis prefixes
* Existing cache prefixes
* Existing queue configuration
* Existing public URLs
* Existing slugs
* Existing canonical URLs
* Existing legacy redirects
* Existing WordPress mappings
* Existing Google News behavior
* Existing sitemaps
* Existing feeds
* Existing storage files
* Existing production secrets
* Existing backups

Do not run destructive commands.

Prohibited commands include:

```bash
php artisan migrate:fresh
php artisan migrate:refresh
php artisan migrate:reset
php artisan db:wipe
php artisan cache:clear
git reset --hard
git clean -fd
composer update
npm update
redis-cli FLUSHALL
redis-cli FLUSHDB
rm -rf
```

`php artisan cache:clear` must not be used casually in production because it may broadly remove application cache.

Use targeted invalidation and normal deployment cache commands only where justified.

---

# 4. Scope Control

This phase may:

* Fix verified defects
* Add missing tests
* Add missing indexes supported by evidence
* Correct authorization leaks
* Correct workflow defects
* Correct cache-key collisions
* Correct queue configuration
* Correct image rendering defects
* Correct search regressions
* Correct analytics duplication
* Correct accessibility issues
* Correct responsive layout defects
* Correct SEO regressions
* Correct documentation
* Add safe diagnostics
* Add safe health checks

This phase must not:

* Add unrelated business features
* Redesign the entire frontend
* Replace core architecture without evidence
* Introduce a new search engine automatically
* Introduce a new queue dashboard automatically
* Introduce a new analytics provider automatically
* Rewrite imported content
* Re-run destructive imports
* Upgrade Laravel or unrelated dependencies
* Change production hosting architecture unnecessarily
* Introduce AI features
* Start Version 2.2 work

Any newly discovered large feature requirement must be documented as deferred work.

---

# 5. Initial Repository Audit

Before changing code, inspect:

```bash
git status
git branch --show-current
git log -10 --oneline
git remote -v
git diff
git diff --staged
```

Verify:

* Correct repository
* Correct branch
* No unexpected uncommitted changes
* No generated secrets
* No accidental debug files
* No local-only paths
* No unresolved merge conflicts
* No large accidental binaries
* No committed `.env`
* No committed credentials
* No production database dumps
* No private SSH keys

Document the starting commit.

Do not overwrite unrelated uncommitted work.

---

# 6. Dependency Audit

Audit:

* PHP version
* Composer version
* Laravel version
* Filament version
* Livewire version
* Node.js version
* npm version
* Vite version
* Redis client
* Image libraries
* Testing libraries
* Queue dependencies
* Search dependencies
* Analytics dependencies

Run safe checks such as:

```bash
php -v
composer --version
php artisan about
composer validate
composer audit
npm --version
node --version
npm audit
```

Do not automatically run dependency upgrades.

Classify vulnerabilities as:

```text
Critical
High
Moderate
Low
Development-only
False positive or non-applicable
```

---

# 7. Environment Audit

Audit production and local configuration expectations for:

* `APP_ENV`
* `APP_DEBUG`
* `APP_URL`
* `APP_KEY`
* Database
* Redis
* Cache
* Session
* Queue
* Filesystem
* Mail
* Trusted proxies
* Logging
* Analytics
* Search
* Images
* Scheduler
* Filament
* Google News
* Sitemap
* Cookie security

Do not print secret values.

Report only:

* Present
* Missing
* Invalid
* Unsafe
* Not applicable

Production requirements include:

```text
APP_ENV=production
APP_DEBUG=false
HTTPS public URL
Secure cookies
Correct trusted proxy configuration
Correct Redis prefix
Correct cache prefix
Correct queue connection
Correct public storage link
```

Do not modify `.env` without deployment authority.

---

# 8. Configuration Validation

Verify:

```bash
php artisan config:show app
php artisan config:show database
php artisan config:show cache
php artisan config:show queue
php artisan config:show filesystems
```

Avoid exposing secrets in reports.

Verify configuration cache behavior:

```bash
php artisan config:cache
```

Confirm the application works with configuration cached.

Do not rely on direct `env()` calls outside configuration files.

Search for inappropriate `env()` usage.

---

# 9. Route Audit

Run:

```bash
php artisan route:list
```

Audit:

* Duplicate route names
* Duplicate route paths
* Incorrect middleware
* Public/private route exposure
* Filament routes
* Authentication routes
* Registration routes
* Search routes
* Analytics routes
* Sitemap routes
* Feed routes
* Legacy routes
* Catch-all route order
* Unsafe debug routes
* Test-only routes
* Unprotected administrative routes

Verify legacy routes do not swallow current routes.

---

# 10. Database Migration Audit

Run:

```bash
php artisan migrate:status
```

Verify:

* All required migrations are applied
* No destructive pending migration exists
* No migration unexpectedly modifies imported content
* Additive migrations are reversible where practical
* Foreign keys are valid
* Indexes support actual query patterns
* Unique constraints match application rules
* Nullable fields match workflow requirements
* Default values are intentional

Do not rerun historical migrations destructively.

---

# 11. Database Integrity Audit

Verify:

* Users
* Roles
* Permissions
* Role assignments
* Posts
* Categories
* Tags
* Media
* SEO metadata
* Workflow records
* Analytics records
* Search-index records where applicable
* Pivot tables
* Foreign keys
* Orphan records
* Duplicate slugs
* Duplicate WordPress IDs
* Missing authors
* Missing featured-media references
* Invalid status values
* Future publication dates
* Corrupt timestamps
* Negative counters
* Invalid analytics totals
* Duplicate queue-generated records

Use read-only queries first.

Create repair commands only where justified.

Do not delete orphan records automatically.

---

# 12. Imported WordPress Data Verification

Verify the imported dataset without modifying it.

Check:

* Total imported users
* Total imported posts
* Published posts
* Draft posts
* Pending posts
* Scheduled posts
* Categories
* Tags
* Media records
* Media files
* SEO records
* WordPress IDs
* Featured-image mappings
* Author mappings
* Category mappings
* Tag mappings
* Legacy URL mappings

Test representative imported posts from multiple years.

Verify:

* Article title
* Slug
* Content
* Author
* Category
* Tags
* Featured image
* Publication date
* SEO title
* SEO description
* Canonical URL
* Legacy redirect

Do not invent missing historical data.

---

# 13. Authentication Verification

Verify:

* Login
* Logout
* Registration where enabled
* Password hashing
* Password reset
* Remember-me behavior
* Session regeneration
* Session invalidation
* Login throttling
* Disabled user handling
* Unverified-email behavior according to project decision
* Filament login
* Public login
* CSRF protection
* Secure cookies
* SameSite behavior
* HTTPS behavior

Verify for each relevant user type.

---

# 14. Role and Permission Matrix

Verify canonical roles:

```text
super-admin
admin
editor
reviewer
reporter
seo-manager
media-manager
analytics-manager
contributor
subscriber
```

Use actual project roles as the source of truth.

Audit:

* Role records
* Permission records
* Role-permission assignments
* Direct user permissions
* Guard names
* Duplicate roles
* Duplicate permissions
* Permission cache
* Super Admin override behavior
* Policy integration
* Filament resource access
* Dashboard access
* Route middleware

Produce a final permission matrix.

Do not grant broader permissions merely to make tests pass.

---

# 15. Authorization Isolation Tests

Verify:

## Subscriber

Cannot access:

* Filament staff dashboard
* Draft posts
* Editorial search
* Workflow actions
* Analytics dashboards
* User management
* Role management

## Reporter

Can access only authorized reporter functions.

Must not:

* Edit another reporter’s private draft
* Publish directly without permission
* Manage users
* Manage roles
* View unrestricted analytics

## Reviewer

Can access assigned or policy-authorized review content.

Must not:

* Publish unless explicitly permitted
* Edit unrestricted reporter drafts
* Manage roles
* View unauthorized private analytics

## Editor

Can perform approved editorial actions within policy.

## SEO Manager

Can manage SEO-authorized content without gaining unrelated editorial authority.

## Media Manager

Can manage authorized media without gaining publication authority.

## Analytics Manager

Can access analytics without gaining user-management or editorial publication rights.

## Admin and Super Admin

Must follow existing project rules.

---

# 16. Editorial Workflow Verification

Verify the complete workflow:

```text
Reporter creates draft
Reporter edits own draft
Reporter submits for review
Reviewer receives assignment
Reviewer opens assigned post
Reviewer approves
Reviewer returns for correction
Reviewer rejects where supported
Reporter corrects returned post
Reporter resubmits
Editor reviews approved post
Editor schedules or publishes
Scheduled post publishes once
Published post becomes public
Workflow history remains complete
```

Verify every transition:

* Requires correct permission
* Starts from allowed state
* Ends in correct state
* Records actor
* Records timestamp
* Records reason where required
* Avoids duplicate history
* Invalidates correct cache
* Dispatches correct jobs after commit
* Does not expose private content

---

# 17. Workflow Concurrency Tests

Test:

* Two reviewers acting on the same post
* Reporter editing while reviewer acts
* Editor publishing while another action is in progress
* Duplicate form submission
* Browser refresh after workflow action
* Queue retry after publication
* Scheduler duplicate execution
* Database transaction rollback
* Stale UI action

Use locking or state checks where justified.

Do not silently overwrite a newer workflow decision.

---

# 18. Scheduled Publishing Verification

Verify:

* Future post remains private
* Scheduler selects due posts
* Publication occurs once
* Duplicate scheduler runs do not duplicate publication
* Workflow history records publication
* Search index updates
* Cache invalidates
* Sitemap updates or refreshes
* Analytics eligibility begins only after publication
* Featured image remains available
* Public URL works
* Google News requirements remain valid

Verify application timezone, especially:

```text
Asia/Kolkata
```

unless the project configuration specifies otherwise.

---

# 19. Role Dashboard Verification

Verify each dashboard separately:

* Super Admin
* Admin
* Editor
* Reviewer
* Reporter
* SEO Manager
* Media Manager
* Analytics Manager
* Contributor
* Subscriber where applicable

Verify:

* Correct widgets
* Correct navigation
* Correct record counts
* Correct role scope
* Correct empty states
* Correct links
* Correct permissions
* No data leakage
* No cross-user cache leakage
* Mobile responsiveness
* Loading performance
* Failure handling

---

# 20. Dashboard UI/UX Verification

Check:

* Visual hierarchy
* Consistent spacing
* Heading hierarchy
* Card alignment
* Table readability
* Dark/light behavior if supported
* Mobile layout
* Tablet layout
* Desktop layout
* Navigation overflow
* Long Punjabi titles
* Long Hindi titles
* Empty states
* Loading states
* Error states
* Confirmation dialogs
* Destructive-action warnings
* Keyboard navigation
* Focus visibility
* Color contrast

Do not initiate a broad redesign.

---

# 21. Public Frontend Functional Testing

Verify:

* Homepage
* Breaking-news ticker
* Primary navigation
* Secondary navigation
* Article pages
* Category archives
* Tag archives
* Author archives
* Date archives
* Search results
* Pagination
* Sidebar
* Related posts
* Featured images
* Advertisements where applicable
* Breadcrumbs
* Footer
* Mobile navigation
* Error pages
* Empty states

Test real imported content.

---

# 22. Public URL Verification

Verify:

* Current Laravel article URL
* WordPress legacy URL
* Category URL
* Tag URL
* Author URL
* Date archive URL
* Search URL
* Sitemap URL
* News sitemap URL
* Feed URL
* Robots URL
* Login URL
* Filament URL

Check response status:

```text
200
301
302
403
404
422
429
500
```

Ensure redirects do not loop.

---

# 23. Legacy Redirect Verification

Test representative WordPress URLs from multiple years.

Verify:

* Correct 301 status
* Correct destination
* Query-string handling
* No redirect loops
* No chain of unnecessary redirects
* Missing posts return appropriate response
* Current Laravel routes are not intercepted
* Canonical URL matches final destination

Do not weaken route validation merely to pass malformed URLs.

---

# 24. Redis Verification

Verify:

* Redis connection
* Application prefix
* Cache prefix
* Queue database
* Session database if Redis sessions are used
* Analytics namespace
* Search namespace
* Image-processing locks
* Dashboard cache keys
* Environment isolation
* TTL behavior
* Memory usage
* Eviction policy
* Persistence policy where applicable

Use safe commands such as:

```bash
php artisan redis:health
redis-cli PING
redis-cli INFO memory
redis-cli INFO persistence
```

Do not use destructive Redis commands.

---

# 25. Cache Verification

Verify Phase 2.1-G behavior:

* Cache abstraction
* Cache keys
* Cache versions
* Cache tags where supported
* TTLs
* Article cache
* Archive cache
* Dashboard cache
* Search cache
* Analytics cache
* Image metadata cache
* User-scope isolation
* Role-scope isolation
* Invalidations
* Publication behavior
* Unpublication behavior
* Correction behavior
* Scheduled publication behavior

Test for stale data and cross-user leakage.

Do not use broad flushes as a normal invalidation strategy.

---

# 26. Full-Page Cache Verification

Where implemented, verify:

* Anonymous public pages cache correctly
* Authenticated pages do not leak
* Cookies do not fragment cache unnecessarily
* CSRF data is not cached publicly
* Draft previews are not cached publicly
* Search pages follow intended cache policy
* Analytics still records cached article views
* Scheduled publication invalidates stale pages
* SEO metadata remains correct
* Mobile/desktop rendering remains valid
* Error responses are not cached improperly

---

# 27. Queue Verification

Verify:

* Queue connection
* Queue names
* Worker topology
* Supervisor or systemd configuration
* Worker user
* Worker restart deployment procedure
* Retry-after
* Job timeout
* Backoff
* Failed-job storage
* Failed-job retry
* Job uniqueness
* Overlap protection
* After-commit dispatch
* Queue priority
* Queue backlog
* Stuck jobs
* Long-running jobs
* Memory limits

Verify real workers, not only queue fakes.

---

# 28. Queue Job Inventory

Produce a complete job inventory.

Possible job categories:

```text
default
high
editorial
media
search
analytics
low
```

For every job document:

* Job class
* Trigger
* Queue name
* Timeout
* Tries
* Backoff
* Unique behavior
* Overlap behavior
* Failure effect
* Recovery procedure
* Idempotency status

Do not leave jobs on queues without workers.

---

# 29. Failed Job Recovery

Verify:

```bash
php artisan queue:failed
```

Test controlled failure handling.

Verify:

* Failure is recorded
* Failure logs are useful
* Sensitive data is not logged
* Retry does not duplicate side effects
* Retry succeeds after root cause is corrected
* Permanent failure is visible
* Article delivery and publication remain safe

Do not delete all failed jobs without review.

---

# 30. Image Pipeline Verification

Verify Phase 2.1-I:

* Original preservation
* Featured-image compatibility
* WordPress imports
* JPEG
* PNG
* WebP
* AVIF where supported
* GIF
* Animated GIF
* SVG
* EXIF rotation
* Transparency
* No upscaling
* Responsive variants
* Deterministic paths
* Processing version
* Missing-source handling
* Corrupt-source handling
* Queue processing
* Retry behavior
* Cleanup safety
* Storage growth

Verify actual generated derivative files.

---

# 31. Responsive Image Markup

Verify:

* `<picture>`
* `srcset`
* `sizes`
* Width
* Height
* Alt text
* Lazy loading
* Eager loading
* Fetch priority
* Fallback
* Missing image
* LCP image treatment
* Card image treatment
* Mobile image choice
* Browser compatibility

Do not lazy-load the main LCP image.

Do not reference non-existent variants.

---

# 32. Search Verification

Verify Phase 2.1-J:

* Public search
* Editorial search
* Exact title
* Phrase
* Prefix
* Partial match
* Punjabi
* Hindi
* English
* Mixed-language
* Category filter
* Tag filter
* Author filter
* Date filter
* Status filter
* Sorting
* Pagination
* Deep pagination
* Highlighting
* Snippets
* Suggestions
* Zero results
* Search cache
* Index updates
* Index removal
* Fallback behavior
* Search health
* Reindex command

Public search must not expose private content.

---

# 33. Search Relevance Review

Review at least:

```text
20 representative real queries
```

Include:

* Punjab
* Jalandhar
* Punjabi person name
* Hindi person name
* English person name
* Politics
* Crime
* Sports
* National news
* Exact headline
* Partial headline
* Misspelled term where supported
* Category term
* Tag term
* Zero-result query

Record expected and actual top results.

Do not claim relevance success based solely on response time.

---

# 34. Analytics Verification

Verify Phase 2.1-K:

* Beacon
* Event ID
* Published post validation
* Draft protection
* Future-post protection
* Visitor key
* Cookie behavior
* Deduplication
* Raw views
* Deduplicated views
* Estimated unique visitors
* Bot classification
* Internal traffic
* Redis counters
* Queue ingestion
* Daily aggregation
* Post lifetime count
* Reconciliation
* Referrer classification
* Device classification
* Workflow metrics
* Role-scoped dashboards
* Data freshness
* Retention
* Pruning
* Audit and health commands

Article delivery must remain functional if analytics fails.

---

# 35. Analytics Duplicate and Replay Tests

Verify:

* Same event ID submitted twice
* Same visitor views same post twice within dedupe window
* Same visitor views different posts
* Different visitors view same post
* View after dedupe TTL
* Queue retry
* Worker restart
* Redis outage
* Database transient error
* Beacon refresh
* Livewire navigation
* Cached article response

No event should inflate durable totals through retry or replay.

---

# 36. SEO Verification

Verify:

* Title
* Meta description
* Canonical
* OpenGraph
* Twitter cards
* Article schema
* Breadcrumb schema
* Organization schema
* Author schema
* Image metadata
* Published date
* Modified date
* Category archive metadata
* Tag archive policy
* Author archive policy
* Search page robots policy
* Pagination canonical behavior
* Sitemap
* News sitemap
* Robots
* Feeds
* Legacy redirects

Use representative pages.

---

# 37. Google News Verification

Verify:

* Published articles are eligible according to current implementation
* Publication date is correct
* Modified date is correct
* Headline is present
* Article image is accessible
* Image dimensions are appropriate
* Author information is available
* Publisher information is valid
* News sitemap contains eligible recent posts
* Future posts are excluded
* Drafts are excluded
* Duplicate URLs are avoided
* Canonical URLs are stable
* Structured data is valid

Do not claim Google News approval or indexing without external evidence.

---

# 38. Sitemap Verification

Verify:

* Sitemap index
* Post sitemap
* Category sitemap where used
* Tag sitemap where used
* Author sitemap where used
* News sitemap
* Image sitemap where used
* Correct URL count
* Correct status
* Correct XML
* Correct content type
* No drafts
* No future posts
* No private content
* No duplicate URLs
* Stable timestamps
* Large-dataset pagination

---

# 39. Feed Verification

Verify:

* Main feed
* Category feed where supported
* Valid XML
* Correct content type
* Published posts only
* Correct dates
* Correct URLs
* Correct excerpts/content
* Valid images where included
* No private data
* No malformed HTML

---

# 40. Security Audit

Audit:

* Authentication
* Authorization
* CSRF
* XSS
* SQL injection
* File upload
* MIME validation
* SVG handling
* Path traversal
* Mass assignment
* Open redirects
* SSRF
* Rate limiting
* Brute-force protection
* Session security
* Cookie security
* Proxy trust
* Log redaction
* Debug exposure
* Error pages
* Admin routes
* Analytics endpoint
* Search endpoint
* Import commands
* Artisan command permissions
* Storage exposure
* Backup exposure
* `.git` exposure
* Environment-file exposure

Fix verified vulnerabilities within scope.

---

# 41. File Upload Security

Verify media uploads for:

* Allowed MIME types
* File extension mismatch
* Executable files
* PHP payload
* SVG scripts
* Oversized files
* Image bombs
* Invalid image dimensions
* Double extensions
* Null-byte attempts
* Path traversal
* Filename normalization
* Storage location
* Public access
* Authorization

Do not rely on extension alone.

---

# 42. Rate-Limiting Verification

Verify rate limits for:

* Login
* Registration
* Password reset
* Public search
* Search autocomplete
* Analytics beacon
* Sensitive workflow actions
* Media upload
* API routes where applicable

Ensure reverse-proxy configuration does not collapse all traffic into one IP.

---

# 43. Logging Verification

Verify:

* Log channel
* Log rotation
* Log level
* Production debug behavior
* Queue failures
* Redis failures
* Cache failures
* Search failures
* Image failures
* Analytics failures
* Workflow failures
* Scheduled-task failures

Logs must not contain:

* Passwords
* Tokens
* Authorization headers
* Full cookies
* Raw private keys
* Raw session IDs
* Raw IP addresses unnecessarily
* Database credentials
* Mail credentials

---

# 44. Backup Verification

Audit:

* Database backup method
* Media backup method
* Environment backup policy
* Redis persistence expectations
* Backup destination
* Retention
* Encryption
* Access permissions
* Restore documentation
* Backup monitoring

Do not assume a backup is valid merely because a file exists.

Verify a controlled restore procedure in a safe environment where possible.

---

# 45. Disaster Recovery Readiness

Document recovery for:

* Database corruption
* Media loss
* Redis loss
* Queue loss
* Failed deployment
* Broken migration
* Search-index corruption
* Analytics corruption
* Server replacement
* Domain/DNS incident
* SSL failure
* Compromised credentials

Define:

```text
Recovery Point Objective
Recovery Time Objective
```

Use realistic values.

---

# 46. Scheduler Verification

Verify production cron:

```bash
* * * * * php artisan schedule:run
```

using the correct application path and system user.

Run:

```bash
php artisan schedule:list
```

Audit:

* Scheduled publishing
* Queue cleanup
* Analytics aggregation
* Analytics pruning
* Cache maintenance
* Search maintenance
* Image cleanup
* Health checks
* Sitemap generation where scheduled
* Backup tasks where application-managed

Use:

* `withoutOverlapping`
* `onOneServer` where appropriate
* Safe lock expiry
* Idempotency

---

# 47. Storage Verification

Verify:

* Storage disks
* Public disk
* WordPress media path
* Image derivative path
* Analytics export path
* Search temporary path where applicable
* Storage symlink
* Ownership
* Permissions
* Available disk space
* Inode usage
* Backup exclusion/inclusion
* Temporary file cleanup
* Log growth

Run safe checks such as:

```bash
php artisan storage:link
df -h
df -i
```

Do not recreate or overwrite links blindly.

---

# 48. Web Server Verification

Audit Nginx or the active web server for:

* Document root
* `public` directory
* PHP-FPM socket
* HTTPS
* HTTP-to-HTTPS redirect
* Canonical domain
* Compression
* Static asset caching
* Security headers
* Upload limits
* Request timeout
* FastCGI timeout
* Hidden-file protection
* PHP execution restrictions
* Storage access
* Sitemap handling
* Legacy redirects
* Full-page cache behavior where used

Run:

```bash
nginx -t
```

where authorized.

Do not restart services unnecessarily.

---

# 49. PHP-FPM Verification

Audit:

* PHP version
* FPM pool user
* Memory limit
* Upload limit
* Post size
* Execution time
* OPcache
* Process manager
* Worker capacity
* Slow log
* Error log
* Redis extension
* GD
* Imagick
* WebP
* AVIF
* Intl
* Mbstring

Do not change server values without documenting impact.

---

# 50. Performance Baseline

Measure before final fixes:

* Homepage response
* Article response
* Cached article response
* Category archive
* Tag archive
* Search page
* Login page
* Filament dashboard
* Post listing
* Post editing
* Analytics dashboard
* Image processing
* Search indexing
* Queue throughput

Record:

* Server response time
* Total response time
* SQL query count
* Slowest queries
* Redis operations
* Memory usage
* Page weight
* JavaScript size
* CSS size
* Image bytes
* LCP
* CLS
* INP where measurable
* Cache status

---

# 51. Performance Stabilization

Fix verified performance defects such as:

* N+1 queries
* Missing indexes
* Unbounded queries
* Oversized eager loading
* Large dashboard payloads
* Repeated configuration reads
* Duplicate Redis calls
* Cache-key collisions
* Missing cache invalidation
* Synchronous queue-worthy work
* Oversized images
* Incorrect lazy loading
* Expensive search fallback
* Raw analytics scans
* Inefficient pagination

Do not introduce complex optimization without evidence.

---

# 52. Performance Targets

Set practical production targets based on actual VPS capacity.

Suggested reference targets:

```text
Homepage server response: under 500 ms warm
Article server response: under 400 ms warm
Cached article response: under 150 ms where full-page cache applies
Search execution: under 200 ms for common warm queries
Filament dashboard: under 1 second warm
Analytics beacon: under 150 ms normal conditions
No common page N+1
No unbounded dashboard query
No uncontrolled queue backlog
Stable memory under repeated requests
```

Adjust targets using measured evidence.

---

# 53. Load and Concurrency Testing

Perform controlled tests only.

Test:

* Concurrent article reads
* Cached article reads
* Search requests
* Analytics beacon requests
* Dashboard requests
* Publication job
* Image processing job
* Search indexing job
* Analytics job

Do not run uncontrolled load tests against production.

Use a local, staging or bounded production-safe sample.

Record:

* Error rate
* Latency
* Throughput
* CPU
* Memory
* Redis memory
* Database connections
* Queue backlog

---

# 54. Memory-Leak and Long-Running Worker Tests

Verify workers under repeated jobs:

* Memory growth
* Model retention
* Temporary file cleanup
* Image-resource cleanup
* Database connection health
* Redis connection health
* Worker restart thresholds
* Job timeout handling

Use worker lifecycle settings such as:

```text
--max-jobs
--max-time
--memory
```

where appropriate.

---

# 55. Browser Compatibility

Test current supported browsers:

* Chrome
* Edge
* Firefox
* Safari where available
* Android Chrome
* iOS Safari where available

Verify:

* Navigation
* Responsive images
* Lazy loading
* JavaScript
* Forms
* Filament
* Livewire
* Search suggestions
* Analytics beacon
* File uploads
* Date inputs
* Punjabi and Hindi rendering

Document browsers not tested.

---

# 56. Responsive Testing

Test widths representative of:

```text
320px
375px
390px
768px
1024px
1280px
1440px
```

Verify:

* Header
* Navigation
* Ticker
* Article
* Archives
* Search
* Dashboard
* Tables
* Forms
* Modals
* Media library
* Analytics
* Images
* Pagination

No horizontal overflow should remain on standard pages.

---

# 57. Accessibility Verification

Audit:

* Semantic headings
* Form labels
* Button names
* Link names
* Keyboard navigation
* Focus states
* Color contrast
* Alt text
* Error messages
* Table headings
* Modal focus
* Live-region usage
* Search result count
* Dashboard charts
* Icon-only buttons
* Skip link
* Language attributes

Fix clear failures within scope.

Do not claim full WCAG conformance without a formal audit.

---

# 58. Automated Test Strategy

Classify tests:

```text
Unit
Feature
Integration
Browser
Architecture
Security
Performance
Smoke
Regression
```

Run focused suites before the complete suite.

Tests must not depend on production secrets or live external services.

Use fakes where appropriate, but separately verify real infrastructure.

---

# 59. Core Model Tests

Verify:

* User model
* Post model
* Media model
* Category model
* Tag model
* Workflow models
* Analytics models
* Search DTOs
* Relationships
* Casts
* Scopes
* Status transitions
* Slug behavior
* Published scope
* Scheduled scope
* Soft deletes where used

---

# 60. Authentication and Authorization Tests

Verify:

* Login
* Logout
* Registration
* Password reset
* Session regeneration
* Login throttling
* Policy checks
* Role checks
* Permission checks
* Filament access
* Resource access
* Dashboard access
* Subscriber restrictions
* Reporter isolation
* Reviewer isolation
* Editor authority
* Admin authority
* Super Admin behavior

---

# 61. Workflow Tests

Verify all valid transitions and reject all invalid transitions.

Include:

* Duplicate actions
* Unauthorized actions
* Transaction rollback
* Concurrent action
* Scheduled publishing
* Cache invalidation
* Search update
* Analytics eligibility
* Queue dispatch
* Workflow history

---

# 62. Cache and Redis Tests

Verify:

* Key format
* Prefix
* TTL
* Version
* Invalidation
* User isolation
* Role isolation
* Environment isolation
* Redis outage
* Cache-store fallback
* Lock behavior
* No broad flush

---

# 63. Queue Tests

Verify:

* Real dispatch
* Fake dispatch
* After-commit
* Queue name
* Timeout
* Retry
* Backoff
* Idempotency
* Overlap
* Failure
* Recovery
* Worker consumption
* Supervisor restart

Clearly distinguish queue fake tests from real worker evidence.

---

# 64. Image Tests

Verify:

* Original preservation
* Variant generation
* WebP
* AVIF skip or success
* PNG transparency
* GIF handling
* EXIF orientation
* Corrupt source
* Missing source
* No upscaling
* Responsive markup
* LCP behavior
* Cleanup dry-run

---

# 65. Search Tests

Verify:

* Multilingual normalization
* Public scope
* Editorial scope
* Ranking
* Filters
* Sorting
* Pagination
* Highlighting
* Cache
* Indexing
* Removal
* Fallback
* Security
* Commands

---

# 66. Analytics Tests

Verify:

* Beacon
* Visitor identity
* Deduplication
* Replay protection
* Bot filtering
* Internal traffic
* Queue
* Aggregation
* Reconciliation
* Retention
* Authorization
* Dashboard cache
* Cache compatibility
* Failure degradation

---

# 67. SEO Tests

Verify:

* Metadata
* Canonical
* OpenGraph
* Schema
* Sitemap
* News sitemap
* Robots
* Feeds
* Redirects
* Published/future/private exclusions

---

# 68. Importer Regression Tests

Verify the WordPress importer remains functional.

Test safe dry-run or bounded fixtures for:

* Users
* Categories
* Tags
* Posts
* Media
* SEO
* Duplicate handling
* Resume
* Checkpoints
* Featured-image mapping
* Slugs
* Author mapping
* Legacy IDs
* Existing-record preservation

Do not run a destructive full import against the verified production database.

---

# 69. Command Audit

Inventory all custom Artisan commands.

For each command verify:

* Signature
* Description
* Help output
* Safe defaults
* Dry-run support where destructive
* Bounded chunking
* Progress
* Exit codes
* Logging
* Authorization or operational restrictions
* Idempotency
* Production safety

Commands that delete or repair data must not default to destructive behavior.

---

# 70. Complete Test Suite

Run the complete test suite using the project-standard command.

Possible commands:

```bash
php artisan test
```

or:

```bash
vendor/bin/phpunit
```

Also run relevant static analysis or formatting tools already configured in the project.

Possible commands:

```bash
vendor/bin/pint --test
vendor/bin/phpstan analyse
npm run build
npm run lint
```

Do not introduce new tooling solely to inflate the completion report.

---

# 71. Test Result Classification

Report results as:

```text
Passed
Failed
Skipped
Incomplete
Environment-blocked
Pre-existing failure
New regression
Flaky
Not applicable
```

Do not report skipped tests as passed.

Do not hide failures by narrowing the test filter.

---

# 72. Flaky Test Handling

For flaky tests:

* Reproduce
* Identify timing or shared-state cause
* Remove dependency on test order
* Remove external-service dependency
* Improve database isolation
* Improve queue isolation
* Improve cache isolation
* Improve timezone control

Do not merely increase sleeps and declare stability.

---

# 73. Manual Role Acceptance Testing

Perform manual acceptance testing with representative accounts:

* Super Admin
* Admin
* Editor
* Reviewer
* Reporter
* SEO Manager
* Media Manager
* Analytics Manager
* Subscriber

Record:

* Login result
* Dashboard result
* Navigation
* Allowed actions
* Denied actions
* Workflow
* Search
* Analytics
* Logout

Use test accounts or safely controlled existing accounts.

Do not expose credentials in reports.

---

# 74. Production Smoke Test

After approved deployment, verify:

* Homepage
* One article
* One category
* One tag
* One author
* Search
* Login
* Filament
* Role dashboard
* Draft workflow
* Review workflow
* Publication
* Image rendering
* Search indexing
* Analytics beacon
* Redis
* Cache
* Queue
* Scheduler
* Sitemap
* News sitemap
* Feed
* Legacy redirect
* Error logs

Do not perform destructive actions on production content.

---

# 75. Deployment Readiness

Confirm:

* Correct Git commit
* Clean working tree
* Successful build
* Composer install strategy
* npm build strategy
* Additive migrations
* Configuration cache
* Route cache where compatible
* View cache
* Storage link
* Queue restart
* Scheduler
* File permissions
* Backup
* Rollback commit
* Maintenance-mode strategy
* Post-deployment smoke tests

---

# 76. Deployment Command Sequence

Document the exact approved production deployment process.

Possible sequence:

```bash
git fetch origin
git status
git pull --ff-only origin main
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan storage:link
php artisan queue:restart
php artisan schedule:list
php artisan about
```

Only include commands compatible with the actual project.

If route caching fails because of closure routes, report and fix or omit it honestly.

Do not run commands blindly.

---

# 77. Zero-Downtime Considerations

Evaluate:

* Maintenance mode
* Queue pause
* Migration lock time
* Backward-compatible code and schema
* Asset versioning
* OPcache refresh
* Worker restart
* Cache compatibility
* Old/new code overlap
* Scheduled publication during deployment
* Analytics events during deployment

Use additive migrations before code depends on them where required.

---

# 78. Rollback Verification

Document rollback for:

* Code
* Assets
* Database migrations
* Configuration
* Queue workers
* Redis
* Search index
* Image processing
* Analytics
* Cache
* Nginx
* PHP-FPM

A rollback must not depend on deleting production data.

Identify migrations that are not safely reversible.

---

# 79. Observability and Health Checks

Provide or verify health checks for:

* Application
* Database
* Redis
* Cache
* Queue
* Scheduler
* Storage
* Search
* Analytics
* Images
* Mail where relevant

Health checks must not expose secrets.

Classify status:

```text
healthy
degraded
unhealthy
```

---

# 80. Post-Deployment Monitoring

Define what to monitor during the initial Version 2.1 production period:

* HTTP 5xx
* HTTP 404 spikes
* Login failures
* Queue backlog
* Failed jobs
* Redis memory
* Database CPU
* Database connections
* Slow queries
* PHP-FPM saturation
* Disk usage
* Log growth
* Search latency
* Analytics duplication
* Image failures
* Scheduled-publishing failures
* Cache hit ratio
* Google News sitemap errors

Define alert thresholds where practical.

---

# 81. Version 2.1 Documentation Index

Create:

```text
docs/version-2.1/README.md
```

It must link to:

* Baseline audit
* RBAC
* Workflow
* Dashboards
* UI/UX
* Redis
* Cache
* Queue
* Images
* Search
* Analytics
* Testing
* Deployment
* Rollback
* Known issues
* Deferred items

---

# 82. Architecture Summary

Create:

```text
docs/version-2.1/version-2.1-architecture-summary.md
```

Include:

* Application architecture
* Role architecture
* Workflow architecture
* Cache architecture
* Redis architecture
* Queue architecture
* Image architecture
* Search architecture
* Analytics architecture
* Deployment architecture
* Operational ownership

---

# 83. Final Role Matrix

Create:

```text
docs/version-2.1/final-role-permission-matrix.md
```

Include:

* Roles
* Permissions
* Dashboard access
* Resource access
* Workflow actions
* Search access
* Analytics access
* Media access
* User-management access
* Role-management access

Use verified application behavior.

---

# 84. Final Test Matrix

Create:

```text
docs/version-2.1/final-test-matrix.md
```

For each critical feature list:

* Test type
* Test command
* Test result
* Evidence
* Environment
* Limitations
* Owner or follow-up

---

# 85. Known Issues Register

Create:

```text
docs/version-2.1/known-issues.md
```

Classify:

```text
Critical
High
Medium
Low
Cosmetic
Environmental
Deferred
```

Include:

* Description
* Impact
* Reproduction
* Workaround
* Fix status
* Target phase
* Release blocker status

Do not hide known release blockers.

---

# 86. Deferred Work Register

Create:

```text
docs/version-2.1/deferred-items.md
```

Include work intentionally excluded from Version 2.1.

Possible examples:

* AI semantic search
* Advanced geographic analytics
* Real-time analytics
* Horizon
* External search engine
* AVIF if unsupported
* Full accessibility certification
* Automated browser matrix
* News-Man
* AI image generation
* AI audio
* AI video

Do not silently treat deferred work as complete.

---

# 87. Production Runbook

Create:

```text
docs/version-2.1/production-runbook.md
```

Include:

* Deployment
* Queue restart
* Scheduler
* Cache commands
* Redis health
* Search health
* Analytics health
* Image health
* Failed-job handling
* Backup
* Restore
* Rollback
* Emergency disable flags
* Log locations
* Service names
* Permissions
* Common incidents

Do not include secret values.

---

# 88. Release Checklist

Create:

```text
docs/version-2.1/release-checklist.md
```

Use checkboxes for:

* Backup verified
* Git commit verified
* Tests passed
* Build passed
* Migrations reviewed
* Redis healthy
* Queue healthy
* Scheduler healthy
* Storage healthy
* Images verified
* Search verified
* Analytics verified
* SEO verified
* Google News verified
* Role tests verified
* Workflow verified
* Performance verified
* Security reviewed
* Rollback prepared
* Smoke test passed
* Monitoring enabled

Unchecked critical items must block a `COMPLETE` decision.

---

# 89. Version Number and Release Notes

Audit how the application tracks version information.

Create:

```text
docs/version-2.1/release-notes.md
```

Include:

* Major improvements
* Role dashboards
* Editorial workflow
* Redis
* Cache
* Queue
* Images
* Search
* Analytics
* Performance
* Security
* Compatibility
* Known limitations
* Upgrade notes
* Operational notes

Do not expose sensitive internal details.

---

# 90. Release Blocking Criteria

The following must block `COMPLETE`:

* Public drafts exposed
* Role authorization leak
* Reporter can edit another reporter’s private draft
* Reviewer can access unauthorized private posts
* Subscriber can access staff dashboard
* Broken publication workflow
* Duplicate publication
* Broken article pages
* Broken legacy redirects
* Missing imported content
* Corrupt featured-media mappings
* Redis namespace collision
* Global cache leakage
* Queue jobs not consumed
* Duplicate queue side effects
* Search exposes private content
* Analytics inflates through retry
* Raw IP or private visitor data exposed
* Image originals overwritten
* Broken canonical URLs
* Drafts in sitemap
* Future posts in public search or sitemap
* Critical security vulnerability
* Destructive migration required without safe plan
* Production test suite has unexplained new failures
* No rollback plan
* No verified backup

---

# 91. Completion Criteria

Phase 2.1-L is complete only when:

* Repository state is audited.
* Dependencies are audited.
* Environment requirements are documented.
* Configuration caching is verified.
* Routes are audited.
* Migrations are audited.
* Database integrity is verified.
* Imported data is sampled and verified.
* Authentication is tested.
* Roles and permissions are tested.
* Role isolation is verified.
* Editorial workflow is tested end to end.
* Workflow concurrency is tested.
* Scheduled publishing is tested.
* Every role dashboard is tested.
* Public frontend is tested.
* Legacy redirects are tested.
* Redis is verified.
* Cache behavior is verified.
* Full-page cache is verified where applicable.
* Queue workers are verified with real jobs.
* Failed-job recovery is verified.
* Images are verified.
* Search is verified.
* Analytics is verified.
* SEO is verified.
* Google News implementation is verified.
* Sitemap and feeds are verified.
* Security review is completed.
* Scheduler is verified.
* Storage is verified.
* Web server expectations are documented.
* Performance is measured.
* Critical performance defects are fixed.
* Responsive behavior is tested.
* Accessibility issues are reviewed.
* Focused tests pass or failures are documented.
* Complete test suite is executed.
* Production smoke testing is completed where authorized.
* Deployment procedure is documented.
* Rollback is documented.
* Monitoring is documented.
* Final documentation is complete.
* Release checklist is complete.
* No release-blocking defect remains.

---

# 92. Required Completion Report Format

Return the completion report using this exact structure:

## 1. Executive Summary

## 2. Version 2.1 Phase Coverage

## 3. Starting Repository State

## 4. Git and Branch Verification

## 5. Dependency Audit

## 6. Environment Audit

## 7. Configuration Audit

## 8. Route Audit

## 9. Migration Audit

## 10. Database Integrity Audit

## 11. Imported WordPress Data Verification

## 12. Authentication Verification

## 13. Role and Permission Verification

## 14. Final Role Matrix

## 15. Authorization Isolation Verification

## 16. Reporter Workflow Verification

## 17. Reviewer Workflow Verification

## 18. Editor Workflow Verification

## 19. Workflow Concurrency Verification

## 20. Scheduled Publishing Verification

## 21. Role Dashboard Verification

## 22. Dashboard UI/UX Verification

## 23. Public Frontend Verification

## 24. Responsive Verification

## 25. Browser Compatibility Verification

## 26. Accessibility Review

## 27. Public URL Verification

## 28. Legacy Redirect Verification

## 29. Redis Verification

## 30. Cache Architecture Verification

## 31. Full-Page Cache Verification

## 32. Queue Architecture Verification

## 33. Queue Worker Verification

## 34. Queue Job Inventory

## 35. Failed Job and Retry Verification

## 36. Image Pipeline Verification

## 37. Responsive Image Verification

## 38. Search Architecture Verification

## 39. Search Relevance Verification

## 40. Multilingual Search Verification

## 41. Search Security Verification

## 42. Analytics Architecture Verification

## 43. Analytics Deduplication Verification

## 44. Analytics Queue and Aggregation Verification

## 45. Analytics Authorization Verification

## 46. SEO Verification

## 47. Google News Verification

## 48. Sitemap Verification

## 49. Feed Verification

## 50. Security Audit

## 51. Upload Security Verification

## 52. Rate-Limiting Verification

## 53. Logging and Secret-Redaction Verification

## 54. Backup Verification

## 55. Disaster Recovery Readiness

## 56. Scheduler Verification

## 57. Storage Verification

## 58. Web Server Verification

## 59. PHP-FPM and Extension Verification

## 60. Performance Baseline

## 61. Performance Improvements

## 62. Final Performance Results

## 63. Load and Concurrency Results

## 64. Worker Memory and Stability Results

## 65. Automated Tests Added or Updated

## 66. Unit Test Results

## 67. Feature Test Results

## 68. Integration Test Results

## 69. Security Test Results

## 70. Workflow Test Results

## 71. Cache and Redis Test Results

## 72. Queue Test Results

## 73. Image Test Results

## 74. Search Test Results

## 75. Analytics Test Results

## 76. SEO Test Results

## 77. Importer Regression Test Results

## 78. Complete Test-Suite Result

## 79. Skipped and Environment-Blocked Tests

## 80. Pre-Existing Failures

## 81. New Regressions

## 82. Manual Role Acceptance Results

## 83. Production Smoke-Test Results

## 84. Deployment Readiness

## 85. Deployment Command Sequence

## 86. Zero-Downtime Assessment

## 87. Rollback Verification

## 88. Health Checks and Monitoring

## 89. Documentation Created

## 90. Files Created or Modified

## 91. Commands Executed

## 92. Known Issues

## 93. Deferred Items

## 94. Release-Blocking Issues

## 95. Release Checklist Result

## 96. Version 2.1 Final Decision

The final decision must be exactly one of:

```text
COMPLETE
COMPLETE WITH CONDITIONS
INCOMPLETE
```

## 97. Version 2.1 Sign-Off Statement

Provide a clear sign-off statement explaining:

* Whether Version 2.1 is safe to deploy
* Whether it is already verified in production
* Which limitations remain
* Which operational actions are required
* Whether Version 2.1 may be closed
* Whether the project may proceed to the next version

---

# 93. Decision Rules

Use:

```text
COMPLETE
```

only when:

* No critical or high release blocker remains.
* Required tests pass.
* Role isolation is verified.
* Workflow is verified.
* Real Redis, cache and queue evidence exists.
* Public search is safe.
* Analytics duplication is controlled.
* Imported content remains intact.
* Security review has no unresolved critical issue.
* Deployment and rollback are documented.
* Production smoke verification is completed where deployment authority exists.

Use:

```text
COMPLETE WITH CONDITIONS
```

when:

* Application code is complete and safe.
* No critical data-leak or workflow defect remains.
* One or more environmental or operational items remain.
* Examples include unavailable AVIF support, missing production worker access, unverified backup restore, or pending production smoke test.
* Conditions are explicit, bounded and non-destructive.

Use:

```text
INCOMPLETE
```

when:

* A critical or high release blocker remains.
* Authorization leakage exists.
* Workflow is broken.
* Imported data is damaged.
* Queue side effects duplicate.
* Search exposes private posts.
* Analytics is unsafe.
* Security issues remain.
* Production deployment cannot be performed safely.
* Test failures indicate new regressions.

---

# 94. Strict Rules

* This is the final Version 2.1 phase.
* Audit every previous Version 2.1 phase.
* Do not trust previous completion reports without verification.
* Do not add unrelated features.
* Do not begin Version 2.2.
* Preserve all imported content.
* Preserve existing users.
* Preserve historical visit counts.
* Preserve featured-media mappings.
* Preserve public URLs.
* Preserve slugs.
* Preserve legacy redirects.
* Preserve SEO metadata.
* Preserve workflow history.
* Do not weaken authorization to make tests pass.
* Do not grant broad permissions casually.
* Do not expose private content.
* Do not expose drafts publicly.
* Do not expose future posts publicly.
* Do not expose raw visitor data.
* Do not overwrite image originals.
* Do not run uncontrolled full imports.
* Do not run uncontrolled full media processing.
* Do not run uncontrolled load tests on production.
* Do not use `Cache::flush()`.
* Do not use Redis `FLUSHALL`.
* Do not use Redis `FLUSHDB`.
* Do not use destructive database commands.
* Do not use `git reset --hard`.
* Do not use `git clean -fd`.
* Do not delete failed jobs without review.
* Do not delete analytics data by default.
* Do not modify `.env` without authority.
* Do not upgrade unrelated dependencies.
* Do not hide failed tests.
* Do not label skipped tests as passed.
* Do not claim real queue verification using fakes.
* Do not claim performance improvement without measurements.
* Do not claim accessibility compliance without evidence.
* Do not claim Google News acceptance without external evidence.
* Do not declare Version 2.1 complete while a release blocker remains.
* Clearly separate code completion from production verification.
* Produce an honest, evidence-based final decision.
