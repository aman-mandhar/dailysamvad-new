# Cache security checklist

- Cache keys are centrally built and environment-prefixed.
- Public response caching is limited to anonymous safe GET/HEAD routes.
- Authenticated, session, admin, Filament, Livewire, search, preview, and legacy routes bypass.
- Dashboard keys include user and page scope.
- No CSRF/session/auth state is cached publicly.
- No unknown keys are scanned or deleted.
- No flush commands are used.
- Redis failures fall back to uncached public rendering or authorized database queries.
- Correctness-critical workflow locking is not silently bypassed.
