# Cache warming runbook

`php artisan cache:architecture warm` performs bounded sitemap-index warming only. It does not flush, enumerate, or delete unknown keys. Future warming targets must be explicitly allowlisted, bounded, public, and anonymous-safe. Run warming after deployment or controlled invalidation, never as an unbounded crawler.
