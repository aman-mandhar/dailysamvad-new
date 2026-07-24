# Performance Baseline

Audit date: 2026-07-24. This document contains only measurements actually made and static query observations. No production load test, cache clear, session-producing HTTP request, or analytics event was performed.

## Measured environment

- PHP 8.3.16; Laravel 13.20.0; local environment; debug enabled.
- Runtime configuration reported config, event, route, view, Filament component, and Blade icon caches as cached.
- Database driver: MySQL. Cache, queue, and session drivers: database.
- Data volume: 100 posts, 84 media, 5 users, 0 post visits.
- Built first-party assets: application CSS 77,451 bytes; application JS 4,904 bytes; font CSS 2,352 bytes. Six local Instrument Sans font files total 116,068 bytes. Values are filesystem sizes, not compressed transfer sizes.

## Representative-page metrics

| Page | Response time | Query count | Memory | Response size | Cache state | Result |
|---|---:|---:|---:|---:|---|---|
| Homepage | Not measured | Not measured | Not measured | Not measured | No page cache identified | HTTP measurement intentionally skipped to avoid session/database writes |
| Article | Not measured | Not measured | Not measured | Not measured | Sitemap cache is unrelated | Same limitation |
| Category archive | Not measured | Not measured | Not measured | Not measured | No page cache identified | Same limitation |
| Tag archive | Not measured | Not measured | Not measured | Not measured | No page cache identified | Same limitation |
| Search | Not measured | Not measured | Not measured | Not measured | No search cache identified | Same limitation |
| Filament dashboard | Not measured | Structurally 4–8 metric aggregates plus framework queries, depending on role | Not measured | Not measured | No widget cache | No authenticated read-only fixture was created |
| Filament posts list | Not measured | Not measured | Not measured | Not measured | No list cache | No authenticated read-only fixture was created |

## Static performance observations

- Homepage queries explicitly eager-load authors, primary categories, and featured media. It performs separate hero, fallback, latest, featured, category-block, category-section, sidebar, and advertisement work. Category blocks and sections are bounded, but repeated published-post datasets are uncached.
- Article queries eager-load all displayed relationships and use bounded related/navigation queries. Content word-counting and content-block composition run synchronously per request.
- Archive pages paginate 12 records and eager-load primary category/media.
- Search applies leading/trailing wildcard `LIKE` across five text columns. Those predicates cannot use ordinary B-tree indexes effectively and will degrade with content volume.
- Dashboard aggregate queries are individually simple but uncached and repeated on each render.
- Public images use a responsive component with async decoding, dimensions where metadata exists, lazy loading by default, and eager/high-priority loading for hero/article lead imagery.
- Generated derivatives can be consumed from media metadata, but no installed image processor or active conversion pipeline was found.
- Vite produces one small JS bundle and one CSS bundle. Fonts are self-hosted by the build plugin. No third-party analytics scripts were found.

## Test timing evidence

- Full suite attempt: 91.388 seconds until stop-on-failure; 16 tests passed and the 17th failed with HTTP 419.
- Focused importer suites executed immediately before this audit: 28 tests and 129 assertions passed in 84.612 seconds.

## Next safe measurement prerequisites

Before Phase 2.1 performance work, establish an isolated benchmark database/session store and an authenticated non-mutating fixture, disable test interference from cached local configuration through an approved procedure, then instrument query count/time and response size. These are recommendations, not performed changes.
