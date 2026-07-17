const setExpanded = (element, expanded) => {
    element?.setAttribute('aria-expanded', String(expanded));
};

export const initializeNavigation = () => {
    const menu = document.querySelector('[data-mobile-menu]');
    const menuTrigger = document.querySelector('[data-mobile-menu-trigger]');
    const menuLabel = menuTrigger?.querySelector('[data-menu-label]');
    const submenuTrigger = menu?.querySelector('[data-submenu-trigger]');
    const submenu = submenuTrigger ? document.getElementById(submenuTrigger.getAttribute('aria-controls')) : null;
    const dropdownItems = [...document.querySelectorAll('.ds-nav-item.has-dropdown')];

    const closeDropdowns = () => {
        dropdownItems.forEach((item) => {
            item.classList.remove('is-open');
            setExpanded(item.querySelector('.ds-nav-dropdown-trigger'), false);
        });
    };

    dropdownItems.forEach((item) => {
        const trigger = item.querySelector('.ds-nav-dropdown-trigger');
        trigger?.addEventListener('click', (event) => {
            event.preventDefault();
            const shouldOpen = !item.classList.contains('is-open');
            closeDropdowns();
            item.classList.toggle('is-open', shouldOpen);
            setExpanded(trigger, shouldOpen);
        });
    });

    const closeMenu = (restoreFocus = false) => {
        if (!menu || !menuTrigger) return;
        menu.hidden = true;
        setExpanded(menuTrigger, false);
        if (menuLabel) menuLabel.textContent = 'Open main menu';
        if (restoreFocus) menuTrigger.focus();
    };

    menuTrigger?.addEventListener('click', () => {
        const shouldOpen = menu?.hidden ?? false;
        if (!menu) return;
        menu.hidden = !shouldOpen;
        setExpanded(menuTrigger, shouldOpen);
        if (menuLabel) menuLabel.textContent = shouldOpen ? 'Close main menu' : 'Open main menu';
    });

    menu?.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => closeMenu()));

    submenuTrigger?.addEventListener('click', () => {
        if (!submenu) return;
        const shouldOpen = submenu.hidden;
        submenu.hidden = !shouldOpen;
        setExpanded(submenuTrigger, shouldOpen);
    });

    document.addEventListener('click', (event) => {
        if (!dropdownItems.some((item) => item.contains(event.target))) closeDropdowns();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        closeDropdowns();
        if (menu && !menu.hidden) closeMenu(true);
    });
};
