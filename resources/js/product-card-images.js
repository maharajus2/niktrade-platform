// Ignore transparent canvas margins, without changing the original image.
export const findVisibleBounds = ({ data, width, height }, alphaThreshold = 8) => {
    let left = width;
    let top = height;
    let right = -1;
    let bottom = -1;

    for (let y = 0; y < height; y++) {
        for (let x = 0; x < width; x++) {
            if (data[(y * width + x) * 4 + 3] <= alphaThreshold) continue;

            left = Math.min(left, x);
            top = Math.min(top, y);
            right = Math.max(right, x);
            bottom = Math.max(bottom, y);
        }
    }

    return right < left ? null : {
        x: left / width,
        y: top / height,
        width: (right - left + 1) / width,
        height: (bottom - top + 1) / height,
    };
};

export const fitVisibleImage = (bounds, imageWidth, imageHeight, frameWidth, frameHeight) => {
    const visibleWidth = imageWidth * bounds.width;
    const visibleHeight = imageHeight * bounds.height;
    const scale = Math.min(frameWidth / visibleWidth, frameHeight / visibleHeight);

    return {
        width: imageWidth * scale,
        height: imageHeight * scale,
        left: (frameWidth - visibleWidth * scale) / 2 - imageWidth * bounds.x * scale,
        top: (frameHeight - visibleHeight * scale) / 2 - imageHeight * bounds.y * scale,
    };
};

const setupProductCardImages = () => {
    const measured = new Map();
    const frames = new WeakMap();
    const fullImage = { x: 0, y: 0, width: 1, height: 1 };
    const place = (frame) => {
        const state = frames.get(frame);
        const rect = frame.getBoundingClientRect();
        if (!state?.bounds || !rect.width || !rect.height) return;

        const placement = fitVisibleImage(state.bounds, state.image.naturalWidth, state.image.naturalHeight, rect.width, rect.height);
        for (const [property, value] of Object.entries(placement)) {
            frame.style.setProperty(`--photo-${property}`, `${value}px`);
        }
        frame.classList.add('is-normalized');
    };
    const observer = new ResizeObserver(entries => entries.forEach(entry => place(entry.target)));

    for (const frame of document.querySelectorAll('.v3-card-photo')) {
        const image = frame.querySelector('img');
        if (!image) continue;

        const measure = async () => {
            if (!image.naturalWidth || !image.naturalHeight) return;
            const source = image.currentSrc || image.src;
            if (!measured.has(source)) {
                measured.set(source, Promise.resolve().then(async () => {
                    let drawable = image;
                    const url = new URL(source, location.href);
                    if (url.origin !== location.origin) {
                        // The www and canonical hosts serve the same public files.
                        // Read an alias through this origin so canvas is not tainted.
                        const sameSite = url.hostname.replace(/^www\./, '') === location.hostname.replace(/^www\./, '');
                        drawable = new Image();
                        drawable.crossOrigin = 'anonymous';
                        drawable.src = sameSite ? `${location.origin}${url.pathname}${url.search}` : source;
                        await drawable.decode();
                    }
                    const ratio = Math.min(1, 512 / Math.max(image.naturalWidth, image.naturalHeight));
                    const canvas = document.createElement('canvas');
                    canvas.width = Math.max(1, Math.round(image.naturalWidth * ratio));
                    canvas.height = Math.max(1, Math.round(image.naturalHeight * ratio));
                    const context = canvas.getContext('2d', { willReadFrequently: true });
                    context.drawImage(drawable, 0, 0, canvas.width, canvas.height);
                    const bounds = findVisibleBounds(context.getImageData(0, 0, canvas.width, canvas.height));
                    canvas.width = canvas.height = 0;
                    return bounds ?? fullImage;
                }).catch(error => {
                    // For cross-origin images without CORS, retain the normal fit.
                    console.warn('Could not measure product photo; using its original bounds.', error);
                    return fullImage;
                }));
            }
            const bounds = await measured.get(source);
            if ((image.currentSrc || image.src) !== source) return;
            frames.set(frame, { image, bounds });
            place(frame);
        };
        image.addEventListener('load', measure);
        if (image.complete) measure();
        observer.observe(frame);
    }
};

if (typeof document !== 'undefined') setupProductCardImages();
