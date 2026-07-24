# Existing audit

Public `/search?q=` was backed by `ArchivePageQuery` and escaped LIKE predicates. Filament resources retain their policy-scoped searchable columns.
