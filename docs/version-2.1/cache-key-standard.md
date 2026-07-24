# Cache key standard

`App\Support\CacheKey` produces `application-prefix:environment:domain:resource:scope:identifier:variant:version[:parameter-hash]`. Parameters are recursively sorted before hashing. Keys never contain credentials, emails, tokens, session IDs, or raw unbounded URLs. All new cache consumers must use this builder.
