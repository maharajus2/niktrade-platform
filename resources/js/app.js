const setupWorkSidebar = () => {
    const app = document.querySelector('.nik-work-app');
    const toggle = document.querySelector('[data-sidebar-toggle]');

    if (!app || !toggle) {
        return;
    }

    const storageKey = 'nik-work-sidebar-collapsed';

    const applyState = (isCollapsed) => {
        app.classList.toggle('is-sidebar-collapsed', isCollapsed);
        toggle.setAttribute('aria-expanded', String(!isCollapsed));
        toggle.setAttribute(
            'aria-label',
            isCollapsed ? 'Развернуть боковую панель' : 'Свернуть боковую панель',
        );
    };

    applyState(localStorage.getItem(storageKey) === 'true');

    toggle.addEventListener('click', () => {
        const isCollapsed = !app.classList.contains('is-sidebar-collapsed');

        localStorage.setItem(storageKey, String(isCollapsed));
        applyState(isCollapsed);
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupWorkSidebar);
} else {
    setupWorkSidebar();
}
