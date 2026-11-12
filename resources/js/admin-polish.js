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
