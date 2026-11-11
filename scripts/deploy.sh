#!/usr/bin/env bash
set -Eeuo pipefail

# Invoke explicitly with the reviewed, pushed commit; never builds an arbitrary HEAD.
cd /opt/niktrade-platform
if [[ $(id -u) -eq 0 ]]; then
    exec runuser -u niktrade -- bash "$0" "$@"
fi
[[ $(id -un) == niktrade ]] || { echo 'Run as niktrade.' >&2; exit 1; }
[[ ${1:-} =~ ^[a-f0-9]{40}$ ]] || { echo 'Usage: deploy.sh FULL_COMMIT [REMOTE_BRANCH]' >&2; exit 1; }
commit=$1
branch=${2:-develop}
[[ $branch != -* ]] || exit 1
[[ $(node -p 'Number(process.versions.node.split(".")[0])') -ge 22 ]] || { echo 'Node.js 22+ required.' >&2; exit 1; }
[[ -z $(git status --porcelain --untracked-files=no) ]] || { echo 'Tracked files must be clean.' >&2; exit 1; }
exec 9>storage/deploy.lock
flock -n 9 || { echo 'Another deployment is running.' >&2; exit 1; }
git fetch origin "$branch"
git merge-base --is-ancestor "$commit" FETCH_HEAD
previous=$(git rev-parse HEAD)
git merge-base --is-ancestor "$previous" "$commit"
backup=$(mktemp -d "$PWD/storage/app/deploy-XXXXXXXX")
had_build=false
[[ ! -d public/build ]] || { cp -a public/build "$backup/previous-build"; had_build=true; }

rollback() {
    trap - ERR
    git reset --hard "$previous"
    # Paths are fixed under the verified application root, never supplied by input.
    [[ ! -d public/build ]] || mv public/build "$backup/failed-build"
    if $had_build; then cp -a "$backup/previous-build" public/build; fi
    php artisan optimize:clear
    php artisan up
    echo "Deployment failed; restored $previous. Details: $backup" >&2
    exit 1
}
php artisan down --retry=60
trap rollback ERR
git merge --ff-only "$commit"
npm ci --include=dev
npm run build -- --outDir "$backup/build"
node scripts/verify-build.mjs "$backup/build" "$commit"
[[ $(git rev-parse HEAD) == "$commit" ]]
[[ ! -d public/build ]] || mv public/build "$backup/replaced-build"
mv "$backup/build" public/build
php artisan optimize:clear
php artisan up
trap - ERR
echo "Deployed $commit; verified manifest and assets; rollback files: $backup"
