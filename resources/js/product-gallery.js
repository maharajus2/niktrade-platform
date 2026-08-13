const setupProductGallery = () => {
    const gallery = document.querySelector('.nik-product-gallery');

    if (!gallery) {
        return;
    }

    const mainLink = gallery.querySelector('[data-product-main-link]');
    const mainImage = gallery.querySelector('[data-product-main-image]');
    const zoomLens = gallery.querySelector('[data-product-zoom-lens]');
    const thumbs = [...gallery.querySelectorAll('[data-product-thumb]')];
    const prompt = gallery.querySelector('[data-product-image-prompt]');
    const openLink = gallery.querySelector('[data-product-image-open]');
    const cancelButton = gallery.querySelector('[data-product-image-cancel]');

    if (!mainLink || !mainImage || thumbs.length === 0) {
        return;
    }

    const selectThumb = (thumb) => {
        const imageSrc = thumb.dataset.imageSrc || thumb.href;
        const imageAlt = thumb.dataset.imageAlt || mainImage.alt;

        mainLink.href = imageSrc;
        mainImage.src = imageSrc;
        mainImage.alt = imageAlt;

        if (zoomLens) {
            zoomLens.style.backgroundImage = `url("${imageSrc}")`;
        }

        thumbs.forEach((item) => {
            const isActive = item === thumb;

            item.classList.toggle('is-active', isActive);

            if (isActive) {
                item.setAttribute('aria-current', 'true');
            } else {
                item.removeAttribute('aria-current');
            }
        });

        if (openLink) {
            openLink.href = imageSrc;
        }
    };

    thumbs.forEach((thumb) => {
        thumb.addEventListener('mouseenter', () => selectThumb(thumb));
        thumb.addEventListener('focus', () => selectThumb(thumb));
        thumb.addEventListener('click', (event) => {
            event.preventDefault();
            selectThumb(thumb);

            if (prompt) {
                prompt.hidden = false;
            }
        });
    });

    cancelButton?.addEventListener('click', () => {
        if (prompt) {
            prompt.hidden = true;
        }
    });

    if (zoomLens && window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
        const zoomScale = 2.25;

        const getContainedImageRect = () => {
            const imageRect = mainImage.getBoundingClientRect();
            const naturalRatio = mainImage.naturalWidth / mainImage.naturalHeight;
            const boxRatio = imageRect.width / imageRect.height;

            if (!naturalRatio || !boxRatio) {
                return imageRect;
            }

            if (boxRatio > naturalRatio) {
                const width = imageRect.height * naturalRatio;

                return {
                    left: imageRect.left + (imageRect.width - width) / 2,
                    top: imageRect.top,
                    width,
                    height: imageRect.height,
                };
            }

            const height = imageRect.width / naturalRatio;

            return {
                left: imageRect.left,
                top: imageRect.top + (imageRect.height - height) / 2,
                width: imageRect.width,
                height,
            };
        };

        const updateZoom = (event) => {
            const imageRect = getContainedImageRect();
            const linkRect = mainLink.getBoundingClientRect();
            const x = Math.min(Math.max(event.clientX - imageRect.left, 0), imageRect.width);
            const y = Math.min(Math.max(event.clientY - imageRect.top, 0), imageRect.height);
            const lensX = event.clientX - linkRect.left;
            const lensY = event.clientY - linkRect.top;
            const lensRadius = zoomLens.offsetWidth / 2 || 168;

            zoomLens.style.left = `${lensX}px`;
            zoomLens.style.top = `${lensY}px`;
            zoomLens.style.backgroundImage = `url("${mainImage.currentSrc || mainImage.src}")`;
            zoomLens.style.backgroundSize = `${imageRect.width * zoomScale}px ${imageRect.height * zoomScale}px`;
            zoomLens.style.backgroundPosition = `${lensRadius - x * zoomScale}px ${lensRadius - y * zoomScale}px`;
        };

        mainLink.addEventListener('mouseenter', (event) => {
            mainLink.classList.add('is-zooming');
            updateZoom(event);
        });
        mainLink.addEventListener('mousemove', updateZoom);
        mainLink.addEventListener('mouseleave', () => {
            mainLink.classList.remove('is-zooming');
        });
    }
};

const setupProductTabs = () => {
    const tabs = [...document.querySelectorAll('[data-product-tab]')];
    const panels = [...document.querySelectorAll('[data-product-tab-panel]')];

    if (tabs.length === 0 || panels.length === 0) {
        return;
    }

    const activateTab = (tab) => {
        const target = tab.dataset.productTab;

        tabs.forEach((item) => {
            const isActive = item === tab;

            item.classList.toggle('is-active', isActive);
            item.setAttribute('aria-selected', String(isActive));
            item.tabIndex = isActive ? 0 : -1;
        });

        panels.forEach((panel) => {
            panel.hidden = panel.dataset.productTabPanel !== target;
        });
    };

    tabs.forEach((tab, index) => {
        tab.tabIndex = tab.classList.contains('is-active') ? 0 : -1;

        tab.addEventListener('click', () => activateTab(tab));
        tab.addEventListener('keydown', (event) => {
            if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) {
                return;
            }

            event.preventDefault();

            const nextIndex = {
                ArrowLeft: (index - 1 + tabs.length) % tabs.length,
                ArrowRight: (index + 1) % tabs.length,
                Home: 0,
                End: tabs.length - 1,
            }[event.key];

            tabs[nextIndex].focus();
            activateTab(tabs[nextIndex]);
        });
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        setupProductGallery();
        setupProductTabs();
    });
} else {
    setupProductGallery();
    setupProductTabs();
}
