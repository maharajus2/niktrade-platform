import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';

const [directory, commit] = process.argv.slice(2);
if (!directory || !/^[a-f0-9]{40}$/.test(commit ?? '')) {
    throw new Error('Usage: verify-build.mjs BUILD_DIRECTORY FULL_COMMIT');
}
const hash = (file) => crypto.createHash('sha256').update(fs.readFileSync(file)).digest('hex');
const manifestPath = path.join(directory, 'manifest.json');
const manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf8'));
for (const entry of ['resources/css/app.css', 'resources/js/app.js', 'resources/js/product-gallery.js', 'resources/css/filament/admin/theme.css']) {
    if (!manifest[entry]?.isEntry) throw new Error(`Missing Vite entry: ${entry}`);
}
for (const entry of Object.values(manifest)) {
    for (const file of [entry.file, ...(entry.css ?? []), ...(entry.assets ?? [])]) {
        if (!file || !path.resolve(directory, file).startsWith(path.resolve(directory) + path.sep)
            || !fs.existsSync(path.join(directory, file))) {
            throw new Error(`Missing or invalid built asset: ${file}`);
        }
    }
}
fs.writeFileSync(path.join(directory, 'build-meta.json'), JSON.stringify({
    commit,
    manifestSha256: hash(manifestPath),
    lockfileSha256: hash('package-lock.json'),
    node: process.versions.node,
}, null, 2) + '\n');
console.log(`Verified ${Object.keys(manifest).length} manifest records for ${commit}`);
