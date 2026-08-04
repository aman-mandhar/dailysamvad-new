export function shorterColumnIndex(heights) {
    if (heights.length !== 2 || heights[0] === heights[1]) {
        return null;
    }

    return heights[0] < heights[1] ? 0 : 1;
}

export function initializeStickyColumns(documentObject = document, windowObject = window) {
    const desktop = windowObject.matchMedia('(min-width: 1024px)');

    documentObject.querySelectorAll('[data-sticky-columns]').forEach((layout) => {
        const columns = [...layout.querySelectorAll(':scope > [data-sticky-column]')];

        if (columns.length !== 2) {
            return;
        }

        const update = () => {
            columns.forEach((column) => column.classList.remove('is-sticky-column'));

            if (! desktop.matches) {
                return;
            }

            const index = shorterColumnIndex(columns.map((column) => column.getBoundingClientRect().height));

            if (index !== null) {
                columns[index].classList.add('is-sticky-column');
            }
        };

        const observer = new ResizeObserver(update);
        columns.forEach((column) => observer.observe(column));
        desktop.addEventListener('change', update);
        update();
    });
}
