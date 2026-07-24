# Dashboard UI component guide

The shared `role-dashboard.blade.php` view is intentionally presentation-only. It renders:

- a responsive workspace header with one `h1`;
- metric cards with tabular numerals and readable labels;
- permission-filtered quick-action buttons;
- a semantic recent-activity ordered list;
- Livewire loading indicator and skeleton placeholders;
- explicit empty states.

Filament section and button components provide theme-consistent rendering. Tailwind responsive utilities use one-column mobile, two-column tablet, and four-column wide layouts. Phase 2.1-E does not introduce a new color system or override Filament internals, leaving the architecture ready for later visual refinement.
