# Cache invalidation map

`CacheInvalidationService` targets post, homepage, archive, dashboard, taxonomy, author, media, SEO, and sitemap version families. Post observer changes invalidate publication, archive, homepage, dashboard, and sitemap surfaces. Category/tag/user/media observers invalidate related public families and sitemaps. Invalidation is idempotent and never flushes unknown keys.
