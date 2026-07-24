# Daily Samvad — Version 2.1

## Phase 2.1-H: Queue Architecture, Worker Optimization and Reliable Background Processing

You are working on the existing Daily Samvad Laravel application.

The application currently uses:

* Laravel 13
* Filament 5
* Livewire 4
* MySQL
* Spatie Laravel Permission
* Permission-driven RBAC from Phase 2.1-B
* Editorial workflow from Phase 2.1-C
* Dynamic dashboards from Phase 2.1-D
* Dashboard UI/UX improvements from Phase 2.1-E
* Redis foundation from Phase 2.1-F
* Redis-backed cache architecture from Phase 2.1-G

Phase 2.1-H must establish a safe, observable and production-ready background-processing architecture.

Do not treat queue activation as merely changing:

```text
QUEUE_CONNECTION=redis
```

The phase must audit existing jobs, define queue boundaries, configure Redis queues safely, implement worker supervision, improve retry and failure behavior, protect against duplicate processing, test deployment behavior and document operational recovery.

---

# 1. Primary Objective

Implement and verify a reliable Laravel queue architecture for Daily Samvad.

The implementation must cover:

* Existing queue audit
* Job classification
* Redis queue configuration
* Queue naming
* Queue priorities
* Worker configuration
* Supervisor or systemd management
* Retry policies
* Timeout policies
* Failed-job handling
* Duplicate-job prevention
* Job uniqueness
* Overlap prevention
* Idempotency
* Database transaction safety
* Job batching and chaining where justified
* Queue monitoring
* Queue health checks
* Graceful deployment restarts
* Queue failure recovery
* Automated tests
* Production documentation

The phase must preserve synchronous execution for operations that must complete immediately and safely.

---

# 2. Core Principles

Every queued job must have:

* A clear purpose
* A defined queue
* A retry policy
* A timeout
* Failure behavior
* Idempotent execution where practical
* Authorization-independent serialized data
* Safe database interaction
* Observability
* Test coverage

Do not queue work merely to make HTTP requests appear faster.

Do not move critical operations into the background unless failure and recovery are fully defined.

---

# 3. Existing-State Audit

Before modifying anything, audit:

* Current `QUEUE_CONNECTION`
* Existing queue configuration
* Existing Redis queue configuration
* Existing jobs under `app/Jobs`
* Existing queued listeners
* Existing queued notifications
* Existing mail jobs
* Existing import jobs
* Existing media-processing jobs
* Existing scheduled jobs
* Existing workflow-related jobs
* Existing failed-jobs table
* Existing job-batches table
* Current queue workers
* Current Supervisor configuration
* Current systemd units
* Current CloudPanel process configuration
* Current cron and scheduler configuration
* Existing queue-related environment variables
* Existing worker restart procedure
* Existing retry settings
* Existing timeout settings
* Existing queue priorities
* Existing queue monitoring
* Existing tests
* Existing production failures
* Existing long-running synchronous operations
* Existing jobs that may serialize full Eloquent models
* Existing jobs that may create duplicates
* Existing jobs dispatched inside transactions

Document all findings before implementing changes.

---

# 4. Protected Boundaries

Do not disturb:

* Existing users
* Existing passwords
* Existing roles
* Existing permissions
* Existing policies
* Existing editorial workflow
* Existing workflow history
* Existing reviewer assignments
* Existing scheduled publishing
* Imported WordPress posts
* Imported media
* Featured-media mappings
* SEO metadata
* Public URLs
* Slugs
* Legacy redirects
* Publication dates
* Existing cache architecture
* Existing Redis key prefixes
* Existing session behavior
* Existing production secrets
* Existing database records
* Existing synchronous operations unless safely migrated
* Existing notifications unless explicitly audited
* Existing deployment configuration outside this phase

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

Do not delete pending jobs merely to simplify implementation.

Do not clear failed jobs without reviewing and documenting them.

---

# 5. Scope of This Phase

This phase includes:

* Redis queue activation where readiness is verified
* Queue connection architecture
* Named queues
* Priority strategy
* Worker process configuration
* Supervisor or systemd configuration
* Graceful worker restart
* Retry and timeout standards
* Backoff strategies
* Failed-job management
* Job uniqueness
* Job overlap prevention
* Job idempotency
* Transaction-safe dispatching
* Queue health diagnostics
* Queue monitoring
* Queue deployment runbook
* Queue rollback runbook
* Focused automated tests

This phase does not include:

* Broad News-Man implementation
* AI content generation
* Search-engine replacement
* Analytics collection
* Image pipeline redesign
* Video processing infrastructure
* Multi-server queue cluster
* Kubernetes
* Redis Cluster
* Redis Sentinel
* Kafka
* RabbitMQ
* CDN changes
* Public frontend redesign

Laravel Horizon may be introduced only if it is clearly compatible, justified and approved by the existing phase requirements.

Do not install Horizon automatically.

---

# 6. Queue Driver Decision

Preferred production queue driver:

```text
Redis
```

Use Redis only when Phase 2.1-F connectivity, isolation, security and locking checks are verified.

Do not activate Redis queues if:

* Redis is unavailable
* Redis authentication is failing
* Prefix isolation is incorrect
* Queue Redis database is shared unsafely
* Worker supervision is not ready
* Failure recovery is undefined

If Redis cannot be safely activated, complete the architecture and report:

```text
COMPLETE WITH CONDITIONS
```

rather than forcing activation.

---

# 7. Queue Connection Configuration

Audit and configure `config/queue.php`.

Use environment-driven configuration.

Possible variables:

```text
QUEUE_CONNECTION=redis
REDIS_QUEUE_CONNECTION=default
REDIS_QUEUE=default
REDIS_QUEUE_RETRY_AFTER=120
QUEUE_BLOCK_FOR=5
```

Adapt names to the existing project.

Do not hardcode production credentials or host details.

Verify queue prefixes do not collide with:

* Cache keys
* Sessions
* Rate limits
* Other hosted Laravel applications
* Staging
* Testing
* Local development

---

# 8. Redis Queue Database Allocation

Use the Redis logical-database plan established in Phase 2.1-F.

Recommended reservation:

```text
Redis DB 3 = queues
```

Adapt to the actual documented map.

Queue keys must have a clear environment-specific prefix.

Example:

```text
dailysamvad:production:queue:
```

Do not use the cache Redis database accidentally.

---

# 9. Queue Naming Strategy

Define explicit named queues.

Recommended baseline:

```text
critical
editorial
notifications
media
imports
analytics
default
low
```

Only create queues required by current application behavior.

Possible responsibilities:

```text
critical:
Correctness-sensitive operational jobs

editorial:
Scheduled publishing, workflow notifications and editorial processing

notifications:
Email and application notifications

media:
Image metadata, thumbnails or later optimization work

imports:
WordPress importer chunks and long migration tasks

analytics:
Aggregation or reporting jobs where implemented

default:
General background tasks

low:
Non-urgent cleanup and maintenance
```

Do not put every job on `default`.

---

# 10. Queue Priority

Define worker queue order.

Example:

```text
critical,editorial,notifications,default,media,imports,analytics,low
```

Ensure long-running imports cannot block:

* Scheduled publishing
* Editorial notifications
* Time-sensitive operational jobs

Do not run all queues through one single worker if workload characteristics differ significantly.

---

# 11. Job Inventory and Classification

Create a job inventory containing:

* Job class
* Purpose
* Current execution mode
* Target queue
* Expected duration
* Timeout
* Retry count
* Backoff
* Uniqueness requirements
* Idempotency status
* Database transaction requirements
* Failure impact
* Monitoring priority

Document synchronous operations that should remain synchronous.

---

# 12. Suitable Queue Candidates

Audit possible candidates such as:

* Editorial notifications
* Scheduled-publication follow-up
* SEO recalculation
* Sitemap warming
* Cache warming
* Media metadata extraction
* Image optimization in later phases
* Import chunks
* Analytics aggregation
* Email delivery
* Non-critical audit summaries
* External API calls
* News-Man processing in later versions

Do not queue the core database transaction that changes a post from approved to published unless publication correctness is fully protected.

---

# 13. Operations That Should Remain Synchronous

Generally preserve synchronous execution for:

* Permission checks
* Policy checks
* Core workflow transition validation
* Saving required post state
* Assigning reviewer
* Recording workflow history
* Immediate authorization-sensitive changes
* User-visible validation
* Critical database consistency operations

Secondary effects may be queued after the primary transaction commits.

---

# 14. Dispatch After Commit

Jobs dependent on committed database data must use transaction-safe dispatching.

Use:

```php
->afterCommit()
```

or equivalent configuration where appropriate.

Prevent jobs from reading:

* Uncommitted posts
* Missing workflow records
* Incomplete taxonomy relationships
* Uncommitted user changes

Add tests for rollback behavior.

---

# 15. Job Payload Standards

Prefer scalar identifiers and compact immutable data.

Example:

```php
public function __construct(
    public readonly int $postId,
) {}
```

Avoid serializing:

* Large Eloquent relationship graphs
* Authenticated user sessions
* Request objects
* Uploaded file streams
* Service instances
* Closures
* Full article HTML unnecessarily
* Credentials
* Tokens

Re-query required records safely inside `handle()`.

---

# 16. Model Serialization Risks

Audit jobs using Laravel model serialization.

Verify behavior when a model is:

* Deleted
* Archived
* Soft-deleted
* Reassigned
* Updated before execution
* Missing during retry

Use `deleteWhenMissingModels` only when data loss is acceptable and documented.

Do not silently discard correctness-critical jobs.

---

# 17. Idempotency

Every retryable job must be safe to run more than once.

Examples:

* Notification job must not send duplicate email unintentionally.
* Scheduled publish job must not publish the same post twice.
* Cache warm job may safely overwrite an existing cache entry.
* Import job must not create duplicate records.
* Sitemap refresh must produce deterministic output.
* Analytics aggregation must avoid double counting.

Use database constraints, unique job IDs, locks or state checks where appropriate.

---

# 18. Unique Jobs

Use Laravel unique-job contracts where appropriate:

```php
ShouldBeUnique
ShouldBeUniqueUntilProcessing
```

Define:

* Unique ID
* Unique duration
* Lock store
* Behavior after failure
* Behavior during deployment

Do not use global uniqueness where user or post scope is required.

---

# 19. Overlap Prevention

Use middleware such as:

```php
WithoutOverlapping
```

for jobs that must not process the same resource concurrently.

Possible examples:

* Publishing the same post
* Rebuilding the same sitemap
* Warming the same route
* Processing the same media record
* Importing the same WordPress record
* Recalculating the same SEO score

Use explicit lock expiration to avoid permanent deadlocks.

---

# 20. Exception Throttling

For unstable external dependencies, consider:

```php
ThrottlesExceptions
```

Define controlled behavior for:

* Email provider
* External APIs
* News sources
* AI providers in later phases
* Remote media downloads

Do not hide repeated failures indefinitely.

---

# 21. Retry Standards

Define retry attempts according to job type.

Example baseline:

```text
Critical/editorial:
3–5 attempts

Notifications:
3 attempts

External API:
3–5 attempts with backoff

Imports:
3 attempts per chunk

Maintenance:
1–3 attempts
```

Adapt based on actual risk.

Do not use unlimited retries.

---

# 22. Backoff Standards

Use increasing backoff where appropriate.

Example:

```php
public function backoff(): array
{
    return [10, 30, 120];
}
```

Avoid immediate retry storms.

For rate-limited services, align backoff with provider behavior.

---

# 23. Timeout Standards

Every potentially long-running job must define a timeout.

Example categories:

```text
Short:
30 seconds

Medium:
60–120 seconds

Long:
300–900 seconds

Import chunk:
Measured and bounded
```

Worker timeout must remain lower than or compatible with `retry_after` to avoid duplicate execution.

Document:

```text
job timeout < worker timeout < retry_after
```

or the exact safe relationship used.

---

# 24. Retry-After Safety

Verify Redis queue `retry_after`.

The value must exceed the longest normal job execution time plus a safety margin.

Incorrect configuration can cause the same job to execute simultaneously.

Test and document the final values.

---

# 25. Worker Sleep and Blocking

Audit:

* `--sleep`
* `--rest`
* `block_for`
* Worker CPU usage
* Queue latency

For Redis, use blocking behavior where compatible to reduce polling overhead.

Do not set indefinite blocking that prevents graceful worker shutdown.

---

# 26. Worker Memory Management

Configure bounded worker lifetime.

Possible options:

```text
--max-jobs
--max-time
--memory
```

This helps control memory leaks in long-running PHP processes.

Example:

```bash
php artisan queue:work redis \
  --queue=critical,editorial,notifications,default \
  --sleep=1 \
  --tries=3 \
  --timeout=120 \
  --max-time=3600 \
  --memory=256
```

Adapt based on verified workloads.

Do not blindly copy example values.

---

# 27. Worker Groups

Create separate worker groups where needed.

Possible groups:

## Fast Worker

Queues:

```text
critical,editorial,notifications,default
```

Characteristics:

* Low latency
* Short jobs
* Higher priority

## Media Worker

Queue:

```text
media
```

Characteristics:

* Higher memory allowance
* Longer timeout
* Lower process count initially

## Import Worker

Queue:

```text
imports
```

Characteristics:

* One controlled process
* Long timeout
* Low priority
* Strict memory controls

## Low-Priority Worker

Queues:

```text
analytics,low
```

Create only the groups justified by current jobs.

---

# 28. Process Count

Start conservatively.

Determine worker count using:

* Available CPU cores
* Available RAM
* PHP memory usage
* Redis latency
* Database capacity
* Job duration
* Expected queue volume

Do not create excessive workers that overload MySQL or the VPS.

Document the calculation and chosen initial values.

---

# 29. Supervisor or systemd

Use the server’s established process-management approach.

Possible options:

```text
Supervisor
systemd
CloudPanel-supported process manager
```

Do not manage the same worker through multiple supervisors.

Audit existing process managers first.

---

# 30. Supervisor Configuration

If Supervisor is used, create production-safe configuration.

Example structure:

```ini
[program:dailysamvad-worker-fast]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work redis --queue=critical,editorial,notifications,default --sleep=1 --tries=3 --timeout=120 --max-time=3600 --memory=256
directory=/path/to/application
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=news-man
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/storage/logs/worker-fast.log
stopwaitsecs=150
```

Adapt paths, user, process count, timeout and memory.

Do not run application workers as root.

---

# 31. Worker User and Permissions

Workers must run as the correct application user.

Verify access to:

* Application files
* `storage`
* `bootstrap/cache`
* Logs
* Redis
* MySQL
* Required media paths

Do not solve permission issues with unsafe global `777` permissions.

---

# 32. Worker Logging

Create clear worker log separation where practical.

Possible logs:

```text
worker-fast.log
worker-media.log
worker-import.log
worker-low.log
```

Use log rotation.

Do not allow worker logs to grow indefinitely.

Do not log secrets or full private payloads.

---

# 33. Graceful Deployment Restart

Use:

```bash
php artisan queue:restart
```

during deployment after new code becomes active.

Verify workers:

* Finish current job
* Exit gracefully
* Restart under process manager
* Load new code

Do not kill active workers abruptly unless recovery requires it.

---

# 34. Deployment Ordering

Recommended queue deployment sequence:

```text
1. Verify Redis health
2. Review pending and failed jobs
3. Deploy application code
4. Run safe additive migrations
5. Rebuild controlled configuration cache
6. Restart workers gracefully
7. Verify process-manager status
8. Dispatch a health-check job
9. Confirm job completion
10. Monitor failures and queue latency
```

Document rollback.

---

# 35. Failed Jobs

Verify failed-job storage exists.

Audit:

```bash
php artisan queue:failed
```

Confirm failed jobs record:

* UUID
* Connection
* Queue
* Payload
* Exception
* Failure time

Do not expose sensitive payload data in public dashboards.

---

# 36. Failed-Job Operations

Document safe use of:

```bash
php artisan queue:failed
php artisan queue:retry <id>
php artisan queue:retry all
php artisan queue:forget <id>
php artisan queue:flush
```

Do not run `queue:flush` automatically.

Review jobs before retrying.

Jobs must be idempotent before using `queue:retry all`.

---

# 37. Job Failure Hooks

Use `failed()` where useful.

Failure handling may:

* Log context
* Notify authorized administrators
* Mark operational status
* Release temporary resources
* Record diagnostic state

Do not change core editorial state automatically on a secondary job failure unless business rules require it.

---

# 38. Queue Health Check

Create a safe queue health-check mechanism.

Suggested command:

```bash
php artisan queue:health
```

Recommended checks:

* Queue connection
* Redis connectivity
* Queue prefix
* Worker status where detectable
* Pending job counts
* Delayed jobs
* Reserved jobs
* Failed jobs
* Oldest job age
* Test-job dispatch and completion
* Final health status

Do not expose job payloads or secrets.

---

# 39. Queue Probe Job

Consider a lightweight job:

```text
QueueHealthProbe
```

It should:

* Use a dedicated health queue or safe default queue
* Write a temporary status record or cache key
* Execute quickly
* Be idempotent
* Contain no private data
* Expire its result
* Support health-command verification

Do not leave permanent probe data.

---

# 40. Queue Monitoring

Monitor:

* Pending jobs
* Reserved jobs
* Delayed jobs
* Failed jobs
* Oldest pending job age
* Throughput
* Processing duration
* Retry count
* Worker count
* Worker uptime
* Redis memory
* Database load
* Job timeouts
* Queue latency

Do not build a heavy analytics platform in this phase.

---

# 41. Horizon Decision

Audit whether Laravel Horizon supports the installed Laravel and Redis versions.

Install Horizon only when:

* Compatibility is verified
* Redis queues are active
* Operational benefit is clear
* Existing process-management strategy supports it
* Dashboard access can be permission-protected
* Configuration and deployment are documented

Do not install Horizon merely because Redis is available.

If deferred, document the reason.

---

# 42. Horizon Security

If Horizon is installed:

* Restrict access through authorization
* Allow only approved roles or permissions
* Do not expose it publicly
* Configure production environments explicitly
* Protect metrics and job details
* Verify job payload privacy

Prefer a permission such as:

```text
view queue monitoring
```

Do not use a hardcoded email allow-list as the primary authorization model.

---

# 43. Scheduled Publishing

Audit scheduled publishing architecture from Phase 2.1-C.

Ensure:

* Scheduler identifies due posts
* Duplicate publishing is prevented
* Publication remains atomic
* Queue delay does not cause duplicate status transitions
* Cache invalidation occurs
* Workflow history is recorded once
* Notifications may be queued after commit
* Failures are recoverable

Do not make publication dependent on an unreliable secondary notification job.

---

# 44. Delayed Jobs

Use delayed jobs where appropriate.

Possible cases:

* Follow-up notification
* Cache warm after publishing
* Retry external service later
* Deferred analytics aggregation

Do not use delayed jobs as a replacement for Laravel scheduler when scheduler semantics are clearer.

---

# 45. Notifications

Audit queued notifications.

Verify:

* Correct queue name
* Retry policy
* Backoff
* Email-provider failures
* Duplicate prevention
* Locale
* Recipient validity
* No serialization of sensitive session state

Notification failure must not roll back already committed editorial work.

---

# 46. Email Queue

If email is queued:

* Use dedicated notification queue where practical
* Verify mail configuration separately
* Apply rate limiting when provider requires it
* Prevent duplicate sends
* Log message type and recipient identifier safely
* Do not log email body or sensitive content unnecessarily

---

# 47. Import Queue Architecture

Audit WordPress importer suitability for queues.

Potential design:

* One coordinator
* Bounded chunks
* Per-record idempotency
* Resume support
* Progress checkpoints
* Duplicate protection
* Failure isolation
* Low-priority queue
* Controlled concurrency

Do not redesign the working importer unless explicitly necessary.

Do not trigger a full production import during this phase.

---

# 48. Media Queue Architecture

Prepare media processing for later image optimization.

Possible future jobs:

* Metadata extraction
* Thumbnail generation
* WebP/AVIF conversion
* Dimension calculation
* Broken-media verification

In this phase, only queue existing justified media work.

Do not implement Phase 2.1-I image optimization early.

---

# 49. Cache and Queue Interaction

Queue jobs may warm or invalidate cache.

Requirements:

* Idempotent cache operations
* Correct cache prefixes
* No `Cache::flush()`
* Locks where rebuilds may overlap
* Safe behavior if Redis cache is unavailable
* No circular dispatch loop

---

# 50. Queue Rate Limiting

Use job middleware for rate-limited resources.

Possible examples:

* Email provider
* External API
* AI provider
* Remote media host

Define:

* Rate key
* Allowed rate
* Release delay
* Retry limit
* Failure behavior

Do not create one global limiter that blocks unrelated jobs.

---

# 51. Database Load Protection

Background workers can overload MySQL.

Protect the database using:

* Controlled worker count
* Bounded chunk sizes
* Indexed queries
* Avoiding N+1 queries
* Transaction limits
* Job pacing
* Queue separation
* Query monitoring

Do not increase worker count before measuring database impact.

---

# 52. Long-Running Jobs

Split excessively long jobs into bounded chunks where safe.

Each chunk must:

* Be independently retryable
* Be idempotent
* Record progress
* Avoid loading unbounded data
* Respect memory limits
* Preserve ordering where needed

Do not create millions of tiny jobs unnecessarily.

---

# 53. Job Batching

Use Laravel batches only when useful.

Possible cases:

* Controlled import chunks
* Media verification
* Bulk cache warming
* SEO recalculation

Define:

* Batch name
* Progress behavior
* Cancellation behavior
* Failure tolerance
* Completion callback
* Partial failure handling

Do not introduce batching for simple single jobs.

---

# 54. Job Chaining

Use chains for strict sequential dependencies.

Example:

```text
Recalculate SEO
→ invalidate article cache
→ warm article cache
```

Use only where failure semantics are clear.

Do not create fragile long chains.

---

# 55. Job Encryption

Consider encrypted jobs only for payloads containing information that requires protection.

Prefer avoiding sensitive payloads entirely.

Do not assume Redis authentication alone makes all job payloads safe.

---

# 56. Queue Data Retention

Document retention for:

* Failed jobs
* Job batches
* Horizon metrics if used
* Worker logs
* Operational status records

Create safe pruning schedules where supported.

Do not delete forensic data too aggressively.

---

# 57. Queue Pruning

Audit commands such as:

```bash
php artisan queue:prune-failed
php artisan queue:prune-batches
php artisan queue:prune-batches --unfinished=
php artisan queue:prune-batches --cancelled=
```

Use conservative retention.

Document scheduled pruning.

---

# 58. Scheduler Integration

Verify Laravel scheduler runs every minute through cron.

Typical entry:

```cron
* * * * * cd /path/to/application && php artisan schedule:run >> /dev/null 2>&1
```

Audit the existing path and user.

Do not add duplicate scheduler cron entries.

---

# 59. Scheduler and Queue Separation

Scheduler should dispatch background work where appropriate but should not wait on long operations.

Verify scheduled commands:

* Use locks
* Avoid overlap
* Dispatch only bounded jobs
* Report failures
* Do not create duplicate queue storms

---

# 60. Queue Feature Flag

Use a configuration-driven staged activation.

Possible flags:

```text
QUEUE_BACKGROUND_NOTIFICATIONS
QUEUE_CACHE_WARMING
QUEUE_MEDIA_PROCESSING
QUEUE_IMPORT_CHUNKS
```

Do not add real production values to source control.

Default flags must preserve safe behavior.

---

# 61. Synchronous Fallback

For optional background side effects, define a controlled fallback.

Examples:

```text
Cache warm failure:
Skip warm; next request rebuilds

Optional notification queue unavailable:
Record failure and allow editorial transaction to remain committed
```

Do not synchronously execute heavy work unexpectedly during an outage unless explicitly designed.

---

# 62. Queue Failure Behavior

Define behavior for:

* Redis unavailable
* Worker stopped
* Worker crash
* Job timeout
* Job exception
* Duplicate execution
* Failed notification
* Failed cache warm
* Failed import chunk
* Missing model
* Database deadlock
* Deployment during processing

Document operational response.

---

# 63. Queue Security

Protect against:

* Arbitrary serialized objects
* Sensitive payload exposure
* Unauthorized Horizon access
* User-controlled class dispatch
* Unsafe command arguments
* Queue poisoning
* Cross-environment key collision
* Public health endpoints
* Log leakage

Never dispatch a class name based directly on untrusted user input.

---

# 64. Local Development

Provide a supported local queue workflow.

Possible commands:

```bash
php artisan queue:work
php artisan queue:listen
php artisan queue:work --once
```

Document:

* Local queue connection
* Redis availability
* Sync fallback for development
* Testing approach
* Windows/Laragon considerations
* Worker restart after code changes

Do not force production Redis credentials locally.

---

# 65. Windows and Laragon

The project is developed locally on Windows with Laragon.

Document:

* Running Redis through WSL, Docker or a supported Windows-compatible service
* PhpRedis availability
* Predis fallback if applicable
* Running workers in PowerShell
* Keeping worker terminal open
* Restarting workers after code changes
* Test environment isolation

---

# 66. Testing Environment

Tests must not use production queue keys.

Use:

* `Queue::fake()` for dispatch behavior
* `Bus::fake()` for chains and batches
* Dedicated Redis test prefix for integration tests
* Dedicated test Redis database where available
* Synchronous execution only when testing job logic intentionally

Do not report fake-only tests as proof that Redis workers function in production.

---

# 67. Required Automated Tests

## 67.1 Configuration Tests

Verify:

* Redis queue connection exists
* Correct Redis database is used
* Queue prefix is environment-specific
* Queue names are configured
* Test environment is isolated
* Cache and queue Redis namespaces do not collide

## 67.2 Dispatch Tests

Verify:

* Job dispatches on correct queue
* Job dispatches after commit where required
* Rolled-back transaction does not dispatch
* Optional jobs obey feature flags

## 67.3 Idempotency Tests

Verify:

* Duplicate execution does not duplicate effects
* Retried notification does not duplicate delivery where protected
* Scheduled publish executes once
* Cache warm can repeat safely
* Import chunk remains duplicate-safe

## 67.4 Unique and Overlap Tests

Verify:

* Unique job key is correctly scoped
* Duplicate unique job is not dispatched
* Overlapping job is delayed or rejected safely
* Lock expires and recovers

## 67.5 Retry Tests

Verify:

* Retry attempts are bounded
* Backoff values are correct
* Permanent failure reaches failed-jobs storage
* Temporary failure succeeds on retry where simulated

## 67.6 Timeout Tests

Verify configuration relationships between:

* Job timeout
* Worker timeout
* Queue retry-after

## 67.7 Failure Tests

Verify:

* `failed()` behavior
* Failure logging
* Missing model behavior
* Redis unavailability behavior
* Worker outage health status
* No silent loss of critical side effects

## 67.8 Scheduled Publishing Tests

Verify:

* Due approved post publishes once
* Future post remains scheduled
* Archived post is not published
* Duplicate scheduler run does not duplicate publication
* Workflow history is recorded once
* Cache invalidation occurs
* Notification dispatch occurs after commit where applicable

## 67.9 Queue Health Tests

Verify:

* Health command detects available connection
* Probe job completes
* Missing worker is detected where supported
* Failed-job count is reported
* No secrets are displayed
* Non-zero exit code occurs on critical failure

## 67.10 Authorization Tests

If queue monitoring UI exists:

* Unauthorized users cannot access
* Subscribers cannot access
* Reporter cannot access unless explicitly permitted
* Authorized Admin or Super Admin can access
* Direct URL access is protected

## 67.11 Regression Tests

Verify:

* Login remains functional
* Filament access remains functional
* Role dashboards remain correct
* Editorial transitions remain correct
* Scheduled publishing remains correct
* Public routes remain correct
* Cache invalidation remains correct
* SEO remains correct
* Media mappings remain correct
* Legacy redirects remain correct
* Existing importer remains compatible

---

# 68. Integration Verification

After configuration, verify a real queued probe.

Suggested process:

```text
1. Confirm Redis health
2. Confirm worker is running
3. Dispatch QueueHealthProbe
4. Verify job appears
5. Verify worker reserves it
6. Verify job completes
7. Verify temporary result
8. Remove temporary result
9. Confirm no failed job was created
```

Do not claim production queue activation based only on `Queue::fake()`.

---

# 69. Performance Verification

Measure:

* Dispatch latency
* Queue waiting time
* Job processing time
* Throughput
* Worker memory
* Worker CPU
* Redis operations
* MySQL load
* Failed-job rate

Compare synchronous versus queued behavior only where a migration occurred.

Do not claim performance improvement without measurement.

---

# 70. Operational Commands

Possible commands:

```bash
php artisan queue:health
php artisan queue:work --once
php artisan queue:failed
php artisan queue:retry <id>
php artisan queue:restart
php artisan schedule:list
php artisan schedule:run
supervisorctl status
systemctl status supervisor
redis-cli ping
```

Use commands appropriate to the verified server.

Do not expose secrets in output.

---

# 71. Production Rollout Strategy

Recommended stages:

```text
Stage 1:
Queue configuration and tests

Stage 2:
Queue health probe

Stage 3:
One worker for default/notifications

Stage 4:
Move non-critical notifications

Stage 5:
Move cache warming

Stage 6:
Add editorial secondary jobs

Stage 7:
Add media worker when justified

Stage 8:
Add import worker when needed

Stage 9:
Tune process counts using measurements
```

Do not move every job at once.

---

# 72. Rollback Plan

Document rollback for:

* Redis queue failure
* Worker crash loop
* Excessive retries
* Duplicate jobs
* Database overload
* Deployment failure
* Supervisor failure
* Incorrect queue routing

Possible rollback actions:

```text
Disable queue feature flag
Restore previous queue connection
Stop affected worker group
Return optional operation to synchronous mode
Gracefully restart workers
Revert application commit
Retry reviewed failed jobs
```

Do not delete queued jobs as the default rollback.

---

# 73. Documentation Deliverables

Create or update:

```text
docs/version-2.1/phase-2.1-h-queue-architecture.md
docs/version-2.1/queue-job-inventory.md
docs/version-2.1/queue-name-and-priority-map.md
docs/version-2.1/queue-retry-timeout-standard.md
docs/version-2.1/queue-idempotency-map.md
docs/version-2.1/queue-worker-topology.md
docs/version-2.1/queue-supervisor-runbook.md
docs/version-2.1/queue-health-monitoring.md
docs/version-2.1/queue-failure-recovery.md
docs/version-2.1/queue-production-rollout.md
docs/version-2.1/queue-rollback-plan.md
docs/version-2.1/queue-local-development.md
```

Documentation must include:

* Existing queue audit
* Driver decision
* Redis queue connection
* Queue database and prefix
* Job inventory
* Queue mapping
* Priority order
* Retry standards
* Backoff standards
* Timeout standards
* Idempotency strategy
* Unique-job strategy
* Overlap-prevention strategy
* Worker groups
* Worker process count
* Supervisor/systemd configuration
* Deployment restart process
* Failed-job operations
* Monitoring
* Health-check usage
* Local development
* Production rollout
* Rollback
* Test results
* Known limitations
* Deferred items

Do not include secrets.

---

# 74. Completion Criteria

Phase 2.1-H is complete only when:

* Existing queue usage is audited.
* Redis queue readiness is verified.
* Queue namespaces are isolated.
* Named queues are defined.
* Queue priority order is documented.
* Jobs are classified.
* Retry standards are defined.
* Backoff standards are defined.
* Timeout and retry-after values are safe.
* Relevant jobs are idempotent.
* Duplicate processing is protected.
* Transaction-dependent jobs dispatch after commit.
* Worker groups are defined.
* Workers run under the correct application user.
* Supervisor or equivalent process management is configured.
* Graceful deployment restart is documented and verified.
* Failed jobs are stored and recoverable.
* Queue health diagnostics exist.
* At least one real probe job executes through the configured worker.
* Queue monitoring is documented.
* Scheduled publishing remains correct.
* Cache invalidation remains correct.
* Focused queue tests pass or failures are honestly reported.
* Full regression result is reported.
* No destructive queue or Redis commands are used.
* Required documentation is complete.

---

# 75. Deferred Items

Do not implement in this phase unless explicitly justified:

* AI News-Man processing
* AI image generation
* AI audio generation
* AI video generation
* Large media-transcoding infrastructure
* Multi-server worker cluster
* Autoscaling
* Kubernetes
* Kafka
* RabbitMQ
* Redis Cluster
* Redis Sentinel
* Search replacement
* Analytics event collection
* Image optimization from Phase 2.1-I

---

# 76. Required Completion Report Format

Return the completion report using this exact structure:

## 1. Executive Summary

## 2. Existing Queue Audit

## 3. Existing Job Inventory

## 4. Queue Driver Decision

## 5. Redis Queue Readiness

## 6. Queue Connection Configuration

## 7. Queue Database and Prefix Isolation

## 8. Queue Name Architecture

## 9. Queue Priority Strategy

## 10. Job Classification

## 11. Synchronous Versus Queued Decisions

## 12. Dispatch-After-Commit Implementation

## 13. Job Payload Review

## 14. Job Idempotency

## 15. Unique-Job Implementation

## 16. Overlap Prevention

## 17. Retry Policy

## 18. Backoff Policy

## 19. Timeout and Retry-After Verification

## 20. Worker Group Architecture

## 21. Worker Process Count

## 22. Supervisor or Process-Manager Configuration

## 23. Worker User and File Permissions

## 24. Worker Logging and Rotation

## 25. Graceful Deployment Restart

## 26. Failed-Job Configuration

## 27. Failed-Job Recovery Procedure

## 28. Scheduled Publishing Queue Verification

## 29. Notification Queue Verification

## 30. Import Queue Assessment

## 31. Media Queue Assessment

## 32. Cache and Queue Integration

## 33. Queue Rate Limiting

## 34. Database Load Protection

## 35. Queue Health Command

## 36. Queue Probe Result

## 37. Queue Monitoring

## 38. Horizon Decision

## 39. Local Development Support

## 40. Production Rollout Procedure

## 41. Rollback Procedure

## 42. Automated Tests Added or Updated

## 43. Focused Queue Test Results

## 44. Scheduled Publishing Test Results

## 45. Queue Security Test Results

## 46. Regression Test Results

## 47. Full Test-Suite Result

## 48. Performance Measurements

## 49. Backward-Compatibility Verification

## 50. Documentation Created

## 51. Files Created or Modified

## 52. Commands Executed

## 53. Risks and Open Questions

## 54. Deferred Items

## 55. Final Phase Decision

The final phase decision must be one of:

```text
COMPLETE
COMPLETE WITH CONDITIONS
INCOMPLETE
```

Explain the decision using verified evidence.

---

# 77. Strict Rules

* Audit before activating Redis queues.
* Do not activate queues without worker supervision.
* Do not run workers as root.
* Do not use unlimited retries.
* Do not omit job timeouts for long-running jobs.
* Ensure `retry_after` safely exceeds execution time.
* Make retryable jobs idempotent.
* Prevent duplicate scheduled publishing.
* Dispatch transaction-dependent jobs after commit.
* Do not serialize request objects or sensitive sessions.
* Do not expose queue monitoring publicly.
* Do not delete pending jobs casually.
* Do not flush failed jobs without review.
* Do not use `FLUSHALL`.
* Do not use `FLUSHDB`.
* Do not delete unknown Redis keys.
* Do not overload MySQL with excessive workers.
* Do not move every operation to queues.
* Do not make core publication state dependent on optional notifications.
* Do not alter roles or permissions.
* Do not alter editorial workflow rules.
* Do not alter imported posts.
* Do not alter imported media.
* Do not alter SEO metadata.
* Do not change slugs or public URLs.
* Do not break cache invalidation.
* Do not modify `.env` without deployment authority.
* Do not upgrade unrelated dependencies.
* Do not run destructive database commands.
* Do not claim a real worker test passed using only fakes.
* Clearly report skipped, environmental and pre-existing failures.
* Preserve backward compatibility.
