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

        const updateZoom = (event) => {
            const imageRect = mainImage.getBoundingClientRect();
            const linkRect = mainLink.getBoundingClientRect();
            const x = Math.min(Math.max(event.clientX - imageRect.left, 0), imageRect.width);
            const y = Math.min(Math.max(event.clientY - imageRect.top, 0), imageRect.height);
            const lensX = event.clientX - linkRect.left;
            const lensY = event.clientY - linkRect.top;
            const lensRadius = zoomLens.offsetWidth / 2 || 84;

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

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupProductGallery);
} else {
    setupProductGallery();
}
