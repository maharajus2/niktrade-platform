import { test } from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import crypto from 'node:crypto';

test('deployment binds a complete manifest to the reviewed commit and rejects missing assets', () => {
    const directory = fs.mkdtempSync(path.join(os.tmpdir(), 'niktrade-build-test-'));
    const commit = '1'.repeat(40);
    try {
        const manifest = Object.fromEntries([
            'resources/css/app.css', 'resources/js/app.js', 'resources/js/product-gallery.js',
            'resources/css/filament/admin/theme.css',
        ].map((entry, index) => [entry, { file: `asset-${index}.css`, isEntry: true }]));
        for (const { file } of Object.values(manifest)) fs.writeFileSync(path.join(directory, file), '/* fixture */');
        const manifestPath = path.join(directory, 'manifest.json');
        fs.writeFileSync(manifestPath, JSON.stringify(manifest));
        const run = () => spawnSync(process.execPath, ['scripts/verify-build.mjs', directory, commit], { encoding: 'utf8' });
        assert.equal(run().status, 0);
        const meta = JSON.parse(fs.readFileSync(path.join(directory, 'build-meta.json'), 'utf8'));
        assert.equal(meta.commit, commit);
        assert.equal(meta.manifestSha256, crypto.createHash('sha256').update(fs.readFileSync(manifestPath)).digest('hex'));
        fs.unlinkSync(path.join(directory, 'asset-3.css'));
        assert.notEqual(run().status, 0, 'incomplete theme must never be published');
    } finally {
        fs.rmSync(directory, { recursive: true, force: true });
    }
});
