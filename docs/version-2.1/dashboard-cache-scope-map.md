# Dashboard cache scope map

Dashboard metric keys include domain, page class, user ID, version, and environment. Reporter/contributor data remains own-post scoped; reviewer data remains assigned-post scoped; editor/admin data follows existing authorized scopes; SEO/media/analytics data follows existing permissions. Dashboard caching is disabled by default and falls back to authorized database queries when unavailable.
