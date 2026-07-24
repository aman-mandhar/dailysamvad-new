# Daily Samvad — Version 2.1

## Phase 2.1-F: Redis Foundation and Safe Activation

You are working on the existing Daily Samvad Laravel application.

The application currently uses:

* Laravel 13
* Filament 5
* Livewire 4
* Spatie Laravel Permission
* MySQL
* Database-backed cache and queue configuration unless already changed
* Permission-driven RBAC established in Phase 2.1-B
* Editorial workflow completed in Phase 2.1-C
* Dynamic role dashboards completed in Phase 2.1-D
* Dashboard UI/UX redesign completed in Phase 2.1-E

The Version 2.1 baseline audit confirmed that Redis-related Laravel configuration exists, but the required Redis client was not available at audit time.

Confirmed baseline finding:

```text
ext-redis missing
Predis missing
```

This phase must establish a safe, testable and production-ready Redis foundation.

Do not implement full-page caching or broad application cache optimization in this phase.

Those items belong to Phase 2.1-G.

---

# 1. Primary Objective

Install, configure, verify and safely activate Redis as an application infrastructure service for Daily Samvad.

The implementation must establish Redis readiness for:

* Application cache
* Cache locks
* Rate limiting
* Session storage where explicitly approved
* Queue transport in a later phase
* Scheduler coordination
* Atomic operations
* Dashboard metric caching in a later phase
* Full-page caching in a later phase
* Search and analytics support where appropriate in future phases

This phase must focus on Redis infrastructure, connectivity, configuration, isolation, diagnostics, failure handling and verification.

Do not move every subsystem to Redis automatically.

---

# 2. Existing Baseline

Before modifying anything, verify the current state.

Audit:

* Current PHP version
* Current Laravel version
* Current Redis-related Composer packages
* Installed PHP extensions
* Current cache driver
* Current session driver
* Current queue connection
* Current rate limiter configuration
* Current Redis configuration in `config/database.php`
* Current cache configuration in `config/cache.php`
* Current session configuration in `config/session.php`
* Current queue configuration in `config/queue.php`
* Current environment-variable references
* Current Redis server availability
* Current Redis service status
* Current Redis authentication settings
* Current Redis bind address
* Current Redis port
* Current Redis persistence mode
* Current Redis memory policy
* Current database numbering strategy
* Existing Redis key usage
* Existing Laravel cache locks
* Existing application code that assumes database cache
* Existing tests that depend on cache, session or queue configuration

Do not assume Redis is absent merely because the PHP client is missing.

---

# 3. Protected Boundaries

Do not disturb:

* Existing users
* Existing passwords
* Existing role assignments
* Existing direct permissions
* Existing policies
* Existing editorial workflow
* Existing workflow history
* Existing reviewer assignments
* Existing scheduled publishing
* Imported WordPress posts
* Imported media
* Featured-media mappings
* SEO metadata
* Slugs
* Public routes
* Legacy redirects
* Existing publication dates
* Existing queue jobs
* Existing session data without a migration plan
* Existing cache-dependent behavior
* Existing database data
* Production secrets
* Deployment configuration outside the documented scope

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

Do not expose Redis publicly.

Do not disable Redis authentication or protected mode without a documented security reason.

---

# 4. Scope of This Phase

This phase includes:

* Redis server audit
* Redis server installation where required
* PHP Redis client installation
* Laravel Redis client selection
* Secure Redis configuration
* Application key namespace strategy
* Separate logical database strategy
* Redis connectivity verification
* Cache-store verification
* Cache-lock verification
* Rate-limiter compatibility verification
* Session-store readiness assessment
* Queue-store readiness assessment
* Scheduler-lock readiness
* Redis health checks
* Failure handling
* Logging
* Monitoring recommendations
* Automated tests
* Operational documentation

This phase does not include:

* Full-page caching
* Route-response caching
* Fragment caching strategy
* Cache warming
* Cache invalidation architecture for content
* Moving production queues to Redis
* Horizon installation
* General queue optimization
* Search-engine replacement
* Analytics collection
* Image optimization
* Public frontend redesign
* News-Man implementation

---

# 5. Redis Client Decision

Choose one supported Laravel Redis client.

Preferred order:

```text
1. PhpRedis extension
2. Predis package only when PhpRedis cannot be used
```

Use PhpRedis where the hosting environment supports it.

Reasons:

* Native extension
* Better performance
* Lower PHP-level overhead
* Common Laravel production usage

Do not install both clients unless there is a clear requirement.

Document the final choice.

---

# 6. PHP Extension Verification

Verify whether PhpRedis is already loaded.

Suggested commands:

```bash
php -m | grep -i redis
php --ri redis
php -i | grep -i redis
```

Also verify the PHP binary used by:

* CLI
* PHP-FPM
* Web server

Do not assume CLI and FPM use the same configuration.

Confirm the exact PHP version.

---

# 7. PhpRedis Installation

If PhpRedis is missing, install it using the supported package-management method for the server.

The installation method must match:

* Operating system
* PHP version
* PHP-FPM version
* CloudPanel or server-management conventions
* Existing package repository

After installation:

* Enable the extension
* Restart the correct PHP-FPM service
* Verify CLI loading
* Verify FPM loading
* Verify the web application sees the extension

Do not restart unrelated services unnecessarily.

Do not use unsupported PECL compilation if a stable OS package exists.

---

# 8. Predis Fallback

Use Predis only when PhpRedis cannot be installed safely.

If Predis is selected:

```bash
composer require predis/predis
```

Do not run `composer update`.

Use a targeted Composer install.

Document why Predis was selected.

---

# 9. Redis Server Installation

Verify whether the Redis server is already installed.

Suggested commands:

```bash
redis-server --version
redis-cli --version
systemctl status redis-server
systemctl status redis
```

If missing, install Redis using the supported operating-system package.

After installation:

* Enable the service
* Start the service
* Verify service status
* Verify local connectivity
* Verify restart persistence

Do not expose port `6379` publicly.

---

# 10. Redis Security

Redis must remain accessible only to trusted local services unless a documented private-network architecture requires otherwise.

Verify:

```text
bind address
protected mode
firewall rules
authentication
Unix socket option
TLS requirements
service user
file permissions
```

Recommended local deployment:

```text
bind 127.0.0.1 ::1
protected-mode yes
```

Do not bind Redis to:

```text
0.0.0.0
```

unless protected by a verified private-network and firewall configuration.

Never commit Redis passwords to Git.

Never print secrets in the completion report.

---

# 11. Redis Authentication

Audit whether Redis authentication is required.

Possible approaches:

```text
ACL user
requirepass
local-only protected access
Unix socket
```

Prefer Redis ACLs where supported and operationally appropriate.

If authentication is configured:

* Store credentials only in environment variables
* Do not hardcode credentials
* Do not log credentials
* Do not place secrets in documentation
* Verify wrong credentials fail
* Verify correct credentials succeed

---

# 12. Laravel Redis Configuration

Audit:

```text
config/database.php
```

Ensure Redis configuration is compatible with Laravel 13 and the chosen client.

Expected environment-driven values may include:

```text
REDIS_CLIENT
REDIS_HOST
REDIS_PASSWORD
REDIS_PORT
REDIS_DB
REDIS_CACHE_DB
REDIS_PREFIX
```

Use only variables actually needed by the application.

Do not modify `.env.example` with real secrets.

Document all required environment keys.

---

# 13. Redis Connection Names

Use clear Redis connection names.

Recommended structure:

```php
'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'),

    'options' => [
        'cluster' => env('REDIS_CLUSTER', 'redis'),
        'prefix' => env(
            'REDIS_PREFIX',
            Str::slug((string) env('APP_NAME', 'laravel')).'-database-'
        ),
    ],

    'default' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'username' => env('REDIS_USERNAME'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_DB', '0'),
    ],

    'cache' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'username' => env('REDIS_USERNAME'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_CACHE_DB', '1'),
    ],
],
```

Adapt to the existing project.

Do not overwrite custom configuration blindly.

---

# 14. Logical Database Strategy

Define a clear Redis logical-database strategy.

Recommended baseline:

```text
0 = default application operations
1 = application cache
2 = sessions, only if later approved
3 = queues, reserved for Phase 2.1-H
4 = rate limits or specialized locks where justified
```

Logical databases are not a replacement for access control.

Use key prefixes in addition to database separation.

Document the final allocation.

---

# 15. Key Prefix Strategy

Every Redis key must be namespaced.

Recommended prefix components:

```text
application
environment
subsystem
```

Example:

```text
dailysamvad:production:cache:
dailysamvad:production:session:
dailysamvad:production:queue:
```

Avoid collisions with:

* Other Laravel applications
* Staging
* Development
* WordPress
* Other domains hosted on the same VPS

Do not rely only on Laravel's default application-name slug if multiple environments share Redis.

---

# 16. Environment Separation

Production, staging, testing and local development must not share the same Redis keys.

Verify distinct:

* Prefixes
* Logical databases
* Redis instances where applicable
* Test configuration

Automated tests must not touch production Redis.

---

# 17. Cache Store Configuration

Audit the existing cache store.

Add or verify a Redis cache store in:

```text
config/cache.php
```

Example:

```php
'redis' => [
    'driver' => 'redis',
    'connection' => 'cache',
    'lock_connection' => 'default',
],
```

Adapt to Laravel 13 conventions and the existing project.

Do not switch the production default cache store until connectivity and regression tests pass.

---

# 18. Safe Cache Activation

Use a staged activation process.

Recommended order:

```text
1. Install and verify Redis server
2. Install and verify PHP client
3. Verify Laravel connection
4. Verify dedicated cache store explicitly
5. Test cache writes and reads
6. Test expiry
7. Test forget
8. Test locks
9. Test tagged-cache support where applicable
10. Run focused tests
11. Change default cache store only when approved
```

Do not clear the existing cache store globally without need.

---

# 19. Connectivity Verification

Verify Redis directly.

Suggested commands:

```bash
redis-cli ping
redis-cli INFO server
redis-cli INFO memory
redis-cli INFO persistence
redis-cli INFO clients
```

Expected connectivity:

```text
PONG
```

Verify through Laravel:

```bash
php artisan tinker
```

Example checks:

```php
Redis::connection()->ping();

Cache::store('redis')->put('redis-health-check', 'ok', 60);

Cache::store('redis')->get('redis-health-check');

Cache::store('redis')->forget('redis-health-check');
```

Use temporary namespaced keys.

Remove test keys after verification.

---

# 20. Cache TTL Verification

Verify:

* Permanent entries
* Short TTL
* Expiry
* Forget
* Increment
* Decrement
* Add-if-absent
* Remember
* Remember-forever
* Lock timeout

Do not rely only on a successful `PING`.

---

# 21. Atomic Locks

Verify Laravel cache locks work with Redis.

Test:

```php
$lock = Cache::store('redis')->lock('phase-2.1-f-lock-test', 10);
```

Verify:

* First lock acquisition succeeds
* Duplicate acquisition fails
* Release succeeds
* Expired lock recovers
* Owner-safe release works where applicable

This is important for:

* Scheduled publishing
* Cache rebuilding
* Queue coordination
* Preventing duplicate jobs
* Future analytics aggregation

---

# 22. Scheduler Lock Readiness

Audit scheduled tasks using:

```text
withoutOverlapping
onOneServer
```

Verify the selected cache store supports the required locking behavior.

Do not change all scheduled tasks automatically.

Document which tasks would benefit from Redis locks.

Do not alter editorial scheduling behavior unless required for compatibility.

---

# 23. Rate Limiter Verification

Audit Laravel rate limiters.

Verify Redis compatibility for:

* Login
* Password reset
* Registration
* API routes
* Public search
* Import dashboard
* Admin actions
* News-Man endpoints where they exist later

Do not change existing limits without evidence.

Verify rate-limit keys are environment-prefixed.

---

# 24. Session Store Assessment

Audit the current session driver.

Do not switch sessions to Redis automatically.

Document:

* Current driver
* Current session table usage
* Session lifetime
* Concurrent-session behavior
* Deployment implications
* Logout implications
* Migration risks
* Rollback plan

Only change `SESSION_DRIVER` to Redis if explicitly required by the phase implementation and safely tested.

If sessions remain database-backed, state this clearly.

---

# 25. Queue Readiness Assessment

Audit the current queue connection.

Do not move queues to Redis in this phase.

Document:

* Current queue driver
* Existing jobs
* Failed-job handling
* Worker configuration
* Retry behavior
* Timeout behavior
* Redis queue readiness
* Required changes for Phase 2.1-H

Reserve queue-specific Redis database and prefix values without activating them.

---

# 26. Redis Persistence

Audit Redis persistence configuration.

Possible modes:

```text
RDB snapshots
AOF
RDB + AOF
No persistence
```

The correct mode depends on intended use.

For cache-only usage:

* Persistence may be optional
* Restart-related cache loss may be acceptable

For sessions or queues:

* Persistence requirements are stronger

Document the chosen persistence strategy.

Do not change persistence blindly.

---

# 27. Memory Configuration

Audit:

```text
maxmemory
maxmemory-policy
used_memory
used_memory_peak
mem_fragmentation_ratio
evicted_keys
expired_keys
```

Recommended policy depends on usage.

For cache-only Redis:

```text
allkeys-lru
allkeys-lfu
volatile-lru
```

may be appropriate.

For queues or sessions sharing the same Redis instance, eviction requires much greater caution.

Do not configure an eviction policy that can silently delete queue or session data.

Document the chosen strategy.

---

# 28. Shared Redis Instance Risk

If cache, sessions and queues share one Redis server, document the risk.

At minimum separate them by:

* Logical database
* Prefix
* Monitoring
* Memory planning

Prefer separate Redis instances for high-risk workloads when operationally justified.

Do not over-engineer the current single-server deployment without evidence.

---

# 29. Redis Timeout Configuration

Audit and configure reasonable values for:

* Connection timeout
* Read timeout
* Retry interval
* Persistent connections
* Backoff
* Maximum retries

Avoid infinite blocking.

Do not enable persistent connections unless tested with the current PHP-FPM environment.

---

# 30. Failure Handling

Redis must not cause an uncontrolled application outage.

Audit behavior when Redis is:

* Unreachable
* Restarting
* Misconfigured
* Authentication-failing
* Memory-constrained
* Timing out

Define which operations may safely fall back and which must fail explicitly.

Examples:

```text
Optional dashboard cache:
May fall back to database query

Atomic workflow lock:
Must not silently bypass lock

Session storage:
Requires deliberate failover strategy

Queue transport:
Must not silently drop jobs
```

Do not implement unsafe blanket fallback behavior.

---

# 31. Health Check

Create a safe Redis health-check mechanism.

Possible implementation:

```text
Artisan command
Application service
System-health widget integration
Diagnostic endpoint restricted to authorized staff
```

The health check should verify:

* Connection
* Ping
* Cache write
* Cache read
* Cache delete
* Lock support
* Latency where practical
* Selected Redis client
* Server version where safe

Do not expose:

* Password
* Raw configuration
* Sensitive key names
* Full server INFO output publicly

---

# 32. Suggested Artisan Command

Consider creating:

```bash
php artisan redis:health
```

Recommended output:

```text
Client
Connection
Ping
Cache write
Cache read
Cache delete
Lock acquisition
Latency
Final status
```

Support machine-readable output only if useful.

Exit non-zero on failure.

Do not modify data beyond temporary namespaced health-check keys.

---

# 33. Application Health Integration

If an internal system-health dashboard already exists, add Redis health there only when authorized.

Requirements:

* Permission-protected
* No secret exposure
* Clear healthy/degraded/unavailable status
* Last checked timestamp
* Non-blocking where appropriate
* Avoid checking Redis excessively on every page load

Do not create a new full system-health module if none exists unless required.

---

# 34. Logging

Log Redis failures with useful context.

Do not log:

* Passwords
* Authentication strings
* Full connection URLs
* Session payloads
* User-sensitive cached values
* Queue payload contents unnecessarily

Use structured context where practical.

---

# 35. Monitoring Recommendations

Document recommended production monitoring for:

```text
service availability
memory usage
evicted keys
connected clients
blocked clients
hit rate
miss rate
expired keys
latency
slow log
rejected connections
persistence errors
replication status if used
```

Do not add a heavy monitoring platform in this phase.

---

# 36. Redis Slow Log

Audit:

```bash
redis-cli SLOWLOG LEN
redis-cli SLOWLOG GET 10
```

Do not expose command arguments containing sensitive data.

Document a safe operational process.

---

# 37. Backup and Restore Considerations

For cache-only use, Redis backup may not be required.

For future queue or session use, document:

* Persistence
* Restart behavior
* Recovery
* Backup relevance
* Data-loss expectations

Do not treat Redis cache as a source of truth.

---

# 38. Cache Data Classification

Document what may and may not be stored in Redis.

Suitable candidates:

```text
Computed dashboard totals
Public content fragments
Rate-limit counters
Temporary locks
Search suggestions
Short-lived metadata
```

Sensitive or unsuitable without controls:

```text
Plain-text passwords
Authentication secrets
Private editorial notes without need
Unencrypted personal data
Permanent source-of-truth records
Large binary media
```

---

# 39. Serialization and Compression

Audit PhpRedis serializer configuration.

Possible serializers:

```text
PHP
JSON
IGBINARY
MSGPACK
```

Do not enable optional serializers unless installed and tested.

Do not introduce compression without performance evidence.

Preserve Laravel compatibility.

---

# 40. Redis Cluster and Sentinel

Do not implement Redis Cluster or Sentinel for the current single-server deployment unless the existing infrastructure already requires it.

Document them as future options only.

Do not overcomplicate Version 2.1.

---

# 41. Local Development

Provide a supported local-development path.

Possible environments:

```text
Windows with Laragon
WSL
Docker
Native Redis-compatible service
Predis fallback
```

Document the verified local approach.

Do not force every developer to use production credentials.

---

# 42. Windows/Laragon Considerations

The project is also developed locally using Laragon on Windows.

Document:

* Whether Redis runs under Windows, WSL or Docker
* How Laravel connects locally
* Whether PhpRedis is available in local PHP
* Safe Predis fallback if required
* Environment-specific client differences
* How tests behave without Redis

Avoid undocumented platform assumptions.

---

# 43. Testing Environment

Automated tests must not depend on production Redis.

Recommended strategies:

```text
array cache for unrelated tests
dedicated Redis test database for Redis integration tests
unique test prefix
explicit Redis test group
```

Test keys must be cleaned up safely.

Never use:

```bash
FLUSHALL
FLUSHDB
```

in automated tests.

---

# 44. Configuration Cache Compatibility

Verify Redis configuration works with:

```bash
php artisan config:cache
```

Do not run broad cache-clearing commands casually.

If configuration cache must be rebuilt, use a documented controlled process.

Verify no closure or non-serializable configuration breaks caching.

---

# 45. Deployment Procedure

Document a safe production rollout.

Recommended sequence:

```text
1. Record current environment and service state
2. Back up relevant configuration files
3. Install Redis server if required
4. Secure Redis
5. Install PHP Redis client
6. Restart correct PHP-FPM service
7. Verify CLI and FPM
8. Deploy application configuration
9. Set environment values
10. Rebuild configuration cache safely
11. Run Redis health check
12. Test explicit Redis cache store
13. Run focused application tests
14. Monitor logs and Redis metrics
15. Activate default cache store only when approved
```

Include rollback steps.

---

# 46. Rollback Plan

Document rollback for:

* Redis server failure
* PHP extension failure
* Laravel connection failure
* Cache-store regression
* Session regression
* Rate-limiter regression
* Configuration-cache failure

Possible rollback actions:

```text
Restore previous cache store
Restore prior environment values
Restore prior config files
Disable Redis-specific features
Restart PHP-FPM
Revert application commit
```

Do not delete Redis data as a default rollback action.

---

# 47. Production Environment Variables

Document required variables without secret values.

Example:

```text
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_USERNAME=
REDIS_PASSWORD=
REDIS_DB=0
REDIS_CACHE_DB=1
REDIS_PREFIX=dailysamvad:production:
```

Do not commit production credentials.

Do not alter `.env` without explicit deployment authority.

---

# 48. Code Quality

Redis usage must be centralized where practical.

Avoid:

* Raw Redis calls scattered across controllers
* Hardcoded prefixes
* Hardcoded database numbers
* Permanent keys without expiry where not required
* Unbounded sets or lists
* Large cached payloads
* Hidden Redis dependencies
* Silent exceptions
* Cache-as-database design

Prefer Laravel abstractions unless raw Redis functionality is justified.

---

# 49. Cache Key Standards

Create a documented cache-key standard.

Recommended format:

```text
domain:resource:identifier:version
```

Examples:

```text
dashboard:editor:metrics:v1
post:123:public-summary:v1
seo:post:123:health:v1
```

The environment/application prefix should be added centrally.

Do not include sensitive data in key names.

---

# 50. TTL Standards

Document standard TTL categories.

Example:

```text
Very short:
30–60 seconds

Short:
5 minutes

Medium:
15–60 minutes

Long:
6–24 hours

Permanent:
Only where explicit invalidation exists
```

This phase should define standards but not broadly cache the application.

---

# 51. Cache Invalidation Boundary

Do not implement full cache invalidation architecture in this phase.

Document future invalidation triggers for Phase 2.1-G, such as:

```text
Post published
Post updated
Post archived
Category updated
Tag updated
Media replaced
SEO metadata changed
Navigation changed
Homepage configuration changed
```

---

# 52. Required Automated Tests

Create focused Redis infrastructure tests.

## 52.1 Configuration Tests

Verify:

* Selected client is valid
* Redis connections exist
* Cache store references correct Redis connection
* Prefix is environment-specific
* Test environment does not use production prefix
* Queue remains on existing driver
* Session remains unchanged unless explicitly approved

## 52.2 Connectivity Tests

Verify:

* Laravel can connect
* Ping succeeds
* Wrong connection configuration fails clearly
* Health command exits correctly

Use environment-aware test skipping where Redis is unavailable.

Do not report skipped integration tests as passed verification.

## 52.3 Cache Tests

Verify:

* Put
* Get
* Forget
* TTL
* Add
* Increment
* Remember
* Lock acquisition
* Lock release
* Lock expiry

## 52.4 Isolation Tests

Verify:

* Test keys use test prefix
* Cache keys do not collide across environments
* Default and cache connections are separated as configured

## 52.5 Failure Tests

Where practical, verify:

* Unavailable Redis produces controlled failure
* Optional cache fallback works only where designed
* Lock-dependent operations do not silently bypass locking

## 52.6 Regression Tests

Verify:

* Login remains functional
* Filament access remains functional
* Editorial workflow remains functional
* Scheduled publishing remains functional
* Public routes remain functional
* Imported posts remain unchanged
* SEO metadata remains unchanged
* Media mappings remain unchanged
* Existing queue behavior remains unchanged
* Existing sessions remain valid where applicable

---

# 53. Test Execution

Run focused tests first.

Suggested commands:

```bash
php artisan test --filter=Redis
php artisan test --filter=Cache
php artisan test --filter=Lock
php artisan redis:health
```

Adapt to the actual test structure.

Then run relevant regressions.

Finally run:

```bash
php artisan test
```

Clearly distinguish:

```text
passed
failed
skipped
environmental failure
pre-existing failure
new regression
```

Do not claim Redis integration tests passed if Redis was unavailable.

---

# 54. Operational Verification Commands

Use only commands appropriate to the verified environment.

Possible commands:

```bash
php -v
php -m | grep -i redis
php --ri redis
redis-server --version
redis-cli --version
redis-cli ping
systemctl status redis-server
systemctl status php8.3-fpm
php artisan about
php artisan redis:health
php artisan config:show database.redis
php artisan config:show cache
```

Do not reveal secrets in command output.

---

# 55. Documentation Deliverables

Create or update:

```text
docs/version-2.1/phase-2.1-f-redis-foundation.md
docs/version-2.1/redis-client-decision.md
docs/version-2.1/redis-security-baseline.md
docs/version-2.1/redis-database-and-prefix-map.md
docs/version-2.1/redis-health-check-runbook.md
docs/version-2.1/redis-deployment-runbook.md
docs/version-2.1/redis-rollback-plan.md
docs/version-2.1/redis-monitoring-checklist.md
docs/version-2.1/redis-local-development.md
docs/version-2.1/redis-readiness-for-cache-queue-session.md
```

Documentation must include:

* Existing-state audit
* Selected client
* Installation method
* Redis server version
* Security configuration
* Connection architecture
* Database allocation
* Prefix strategy
* Cache-store configuration
* Session decision
* Queue decision
* Lock verification
* Rate-limiter verification
* Persistence strategy
* Memory policy
* Health-check usage
* Deployment procedure
* Rollback procedure
* Test results
* Known limitations
* Deferred items

Do not include credentials or secret values.

---

# 56. Completion Criteria

Phase 2.1-F is complete only when:

* Redis server availability is verified.
* Redis is not publicly exposed.
* A supported PHP Redis client is installed and verified.
* Laravel connects successfully.
* Dedicated Redis connections are configured.
* Environment-specific prefixes are configured.
* Logical Redis database allocation is documented.
* Explicit Redis cache-store operations work.
* TTL behavior is verified.
* Atomic locks work.
* Rate-limiter compatibility is verified.
* Scheduler-lock readiness is verified.
* Session migration decision is documented.
* Queue migration decision is documented.
* Production deployment steps are documented.
* Rollback steps are documented.
* Redis health check exists.
* Focused Redis tests are added and executed.
* Existing application behavior remains compatible.
* No destructive Redis command is used.
* Full-page caching is not implemented.
* Redis queues are not activated.
* Required documentation is complete.

---

# 57. Deferred Items

Do not implement in this phase:

* Full-page caching
* Public response caching
* Fragment caching rollout
* Cache warming
* Broad cache invalidation
* Redis queue activation
* Laravel Horizon
* Queue-worker redesign
* Search-engine integration
* Analytics event collection
* Media optimization
* Image conversion
* Public frontend redesign
* News-Man integration
* Redis Cluster
* Redis Sentinel
* Multi-server failover

---

# 58. Required Completion Report Format

Return the completion report using this exact structure:

## 1. Executive Summary

## 2. Existing Redis Audit

## 3. PHP and Redis Client Audit

## 4. Redis Server Audit

## 5. Redis Client Decision

## 6. Redis Installation

## 7. Redis Security Configuration

## 8. Laravel Redis Configuration

## 9. Redis Connection Architecture

## 10. Logical Database Allocation

## 11. Redis Key Prefix Strategy

## 12. Environment Isolation

## 13. Cache Store Configuration

## 14. Redis Connectivity Verification

## 15. Cache Read/Write Verification

## 16. TTL Verification

## 17. Atomic Lock Verification

## 18. Scheduler Lock Readiness

## 19. Rate Limiter Verification

## 20. Session Store Decision

## 21. Queue Store Decision

## 22. Persistence Configuration

## 23. Memory and Eviction Policy

## 24. Timeout and Retry Configuration

## 25. Failure Handling

## 26. Redis Health Check

## 27. Logging and Monitoring

## 28. Local Development Support

## 29. Production Deployment Procedure

## 30. Rollback Procedure

## 31. Automated Tests Added or Updated

## 32. Focused Redis Test Results

## 33. Regression Test Results

## 34. Full Test-Suite Result

## 35. Backward-Compatibility Verification

## 36. Documentation Created

## 37. Files Created or Modified

## 38. Commands Executed

## 39. Risks and Open Questions

## 40. Deferred Items

## 41. Final Phase Decision

The final phase decision must be one of:

```text
COMPLETE
COMPLETE WITH CONDITIONS
INCOMPLETE
```

Explain the decision using verified evidence.

---

# 59. Strict Rules

* Audit before changing configuration.
* Use a supported Laravel Redis client.
* Prefer PhpRedis where safely available.
* Do not install both clients without justification.
* Do not expose Redis publicly.
* Do not commit credentials.
* Do not print secrets.
* Do not use `FLUSHALL`.
* Do not use `FLUSHDB`.
* Do not delete unknown Redis keys.
* Do not modify unrelated server services.
* Do not move queues to Redis.
* Do not move sessions to Redis without explicit justification and tests.
* Do not implement full-page caching.
* Do not implement broad application caching.
* Do not alter editorial workflow.
* Do not alter roles or permissions.
* Do not modify imported content.
* Do not change slugs or public URLs.
* Do not alter SEO metadata.
* Do not alter featured-media mappings.
* Do not run destructive database commands.
* Do not upgrade unrelated dependencies.
* Do not alter `.env` without deployment authority.
* Do not claim Redis is operational based only on `PING`.
* Do not claim tests passed unless executed successfully.
* Clearly report skipped or environment-dependent tests.
* Preserve backward compatibility.
