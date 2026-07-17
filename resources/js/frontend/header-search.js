export const initializeHeaderSearch = () => {
    const panel = document.querySelector('[data-search-panel]');
    const triggers = [...document.querySelectorAll('[data-search-trigger]')];
    const closeButton = panel?.querySelector('[data-search-close]');
    const input = panel?.querySelector('[data-search-input]');
    let activeTrigger = null;

    const setTriggersExpanded = (expanded) => {
        triggers.forEach((trigger) => trigger.setAttribute('aria-expanded', String(expanded)));
    };

    const close = (restoreFocus = true) => {
        if (!panel) return;
        panel.hidden = true;
        setTriggersExpanded(false);
        if (restoreFocus) activeTrigger?.focus();
    };

    triggers.forEach((trigger) => trigger.addEventListener('click', () => {
        if (!panel) return;
        const shouldOpen = panel.hidden;
        activeTrigger = trigger;
        panel.hidden = !shouldOpen;
        setTriggersExpanded(shouldOpen);
        if (shouldOpen) window.requestAnimationFrame(() => input?.focus());
    }));

    closeButton?.addEventListener('click', () => close());

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && panel && !panel.hidden) close();
    });
};
