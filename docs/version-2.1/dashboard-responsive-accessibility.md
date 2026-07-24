# Responsive and accessibility guide

The dashboard uses mobile-first breakpoints: single-column cards by default, two columns at `sm`, and four columns at `xl`. Activity rows stack on small screens and align horizontally on larger screens. Text remains readable in light/dark themes and numeric values use tabular figures.

Each page has one labelled heading, labelled metric/activity regions, an ordered activity list, escaped Blade output, visible empty states, and live loading feedback. Decorative loading skeletons are hidden from assistive technology. Navigation remains keyboard-operable through native Filament components.
