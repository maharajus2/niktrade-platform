import test from 'node:test';
import assert from 'node:assert/strict';
import { findVisibleBounds, fitVisibleImage } from '../../resources/js/product-card-images.js';

const rectangle = (width, height, x, y, w, h, alpha = 255) => {
    const data = new Uint8ClampedArray(width * height * 4);
    for (let row = y; row < y + h; row++) for (let col = x; col < x + w; col++) data[(row * width + col) * 4 + 3] = alpha;
    return { width, height, data };
};

test('different transparent margins produce the same visible bottle height', () => {
    for (const fixture of [rectangle(50, 100, 0, 0, 50, 100), rectangle(150, 180, 50, 40, 50, 100)]) {
        const bounds = findVisibleBounds(fixture);
        const fit = fitVisibleImage(bounds, fixture.width, fixture.height, 180, 132);
        assert.equal(Math.round(fit.height * bounds.height), 132);
        assert.equal(Math.round(fit.width * bounds.width), 66);
        assert.equal(Math.round(fit.top + bounds.y * fit.height), 0);
        assert.equal(Math.round(fit.left + bounds.x * fit.width), 57);
        assert.ok(Math.abs(fit.width / fit.height - fixture.width / fixture.height) < 1e-10);
    }
});

test('opaque white packaging is retained; only alpha margins are ignored', () => {
    const fixture = rectangle(100, 100, 20, 10, 50, 80);
    for (let i = 0; i < fixture.data.length; i += 4) fixture.data[i] = fixture.data[i + 1] = fixture.data[i + 2] = 255;
    assert.deepEqual(findVisibleBounds(fixture), { x: .2, y: .1, width: .5, height: .8 });
});

test('wide and opaque images fit without distortion or visible clipping', () => {
    const bounds = findVisibleBounds(rectangle(200, 40, 0, 0, 200, 40));
    const fit = fitVisibleImage(bounds, 200, 40, 100, 132);
    assert.deepEqual(fit, { width: 100, height: 20, left: 0, top: 56 });
});

test('empty images have no visible bounds and faint transparent noise is ignored', () => {
    assert.equal(findVisibleBounds(rectangle(20, 20, 0, 0, 0, 0)), null);
    const fixture = rectangle(20, 20, 5, 5, 10, 10);
    fixture.data[3] = 1;
    assert.deepEqual(findVisibleBounds(fixture), { x: .25, y: .25, width: .5, height: .5 });
});
