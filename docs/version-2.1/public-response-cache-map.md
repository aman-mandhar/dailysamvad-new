# Public response cache map

Eligible when the feature flag is enabled: anonymous GET/HEAD homepage, published article, category, tag, author, date archive, static page, and feed routes. Excluded: authenticated/session-bearing requests, POST/PUT/PATCH/DELETE, search, login/register/password flows, previews, legacy redirects, `/dashboard`, `/account`, Filament/admin, Livewire, JSON, and streamed sitemap responses. Sitemaps retain their existing body-level cache.
