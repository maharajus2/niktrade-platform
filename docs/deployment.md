# Deployment

## Frontend toolchain prerequisites

Production builds require a modern Node.js runtime.

Required:

- Node.js >= 22 LTS
- npm >= 10

Recommended minimum currently used for verification:

- Node.js 22.12.0
- npm 10.9.0

Do not deploy with Node.js 18. It is too old for the current Vite/Rolldown toolchain and will fail during `npm run build`.

## Current frontend package versions

The current lockfile was generated and verified with Node.js 22.12.0.

Installed versions in `package-lock.json`:

- vite 8.1.3
- @tailwindcss/vite 4.3.2
- tailwindcss 4.3.2
- laravel-vite-plugin 3.1.0

## Production build

Use the committed lockfile:

```bash
npm ci
npm run build
```

Before running the build, verify the runtime:

```bash
node -v
npm -v
```

The Node.js version must be 22 LTS or newer. If the server prints Node.js 18.x, upgrade Node before deploying frontend assets.

## Laravel deploy checklist

Typical deploy sequence:

```bash
git pull
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Do not skip the frontend build after changes to `resources/css`, `resources/js`, Vite config, or Filament theme assets.
