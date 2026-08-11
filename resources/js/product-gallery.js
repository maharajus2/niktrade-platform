const setupProductGallery = () => {
    const gallery = document.querySelector('.nik-product-gallery');

    if (!gallery) {
        return;
    }

    const mainLink = gallery.querySelector('[data-product-main-link]');
    const mainImage = gallery.querySelector('[data-product-main-image]');
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
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupProductGallery);
} else {
    setupProductGallery();
}
