// Filament's Select utility currently hardcodes these accessible labels.
// Translate only matching labels; selection and search behavior stay native.
let selectLabelsObserver;

const localizeSelectLabels = () => {
    selectLabelsObserver?.disconnect();

    const translations = document.getElementById('admin-select-translations');

    if (!translations || !document.body.classList.contains('fi-panel-admin')) {
        return;
    }

    const labels = new Map(Object.entries(JSON.parse(translations.textContent)));
    const translate = (element) => {
        if (!element.matches('input, button.fi-select-input-value-remove-btn')) {
            return;
        }

        const label = element.getAttribute('aria-label');
        const translated = labels.get(label)
            ?? (label?.startsWith('Remove ') && labels.has('Remove')
                ? `${labels.get('Remove')} ${label.slice(7)}`
                : null);

        if (translated) {
            element.setAttribute('aria-label', translated);
        }
    };
    const translateTree = (root) => {
        if (!(root instanceof Element)) {
            return;
        }

        translate(root);
        root.querySelectorAll('[aria-label]').forEach(translate);
    };

    translateTree(document.body);
    selectLabelsObserver = new MutationObserver((mutations) => {
        for (const mutation of mutations) {
            if (mutation.type === 'attributes') {
                translate(mutation.target);
            } else {
                mutation.addedNodes.forEach(translateTree);
            }
        }
    });
    selectLabelsObserver.observe(document.body, {
        subtree: true,
        childList: true,
        attributes: true,
        attributeFilter: ['aria-label'],
    });
};

localizeSelectLabels();
document.addEventListener('livewire:navigated', localizeSelectLabels);

// Keep collapsed navigation hints below the page header, including the taller
// HR header. Delegation also covers links replaced during Livewire navigation.
const positionSidebarTooltip = (event) => {
    const link = event.target.closest('.nik-work-nav-link');

    if (!link?._tippy) {
        return;
    }

    link._tippy.setProps({
        zIndex: 130,
        offset: ({ reference, popper, placement }) => {
            if (!link.closest('.is-sidebar-collapsed') || !placement.startsWith('right')) {
                return [0, 12];
            }

            const headers = document.querySelectorAll('.nik-work-header, .nik-directory-head, .nik-work-legacy-actions');
            const bottom = Math.max(0, ...Array.from(headers, (header) => {
                const rect = header.getBoundingClientRect();

                return rect.width && rect.height ? rect.bottom : 0;
            }));

            return [Math.max(0, bottom + 8 - reference.y - (reference.height - popper.height) / 2), 12];
        },
    });
};

document.addEventListener('pointerover', positionSidebarTooltip, true);
document.addEventListener('focus', positionSidebarTooltip, true);
