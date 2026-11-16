#!/usr/bin/env bash
set -Eeuo pipefail

[[ $(id -u) -eq 0 ]] || { echo 'Run as root to repair both PHP-FPM and deploy-user ACLs.' >&2; exit 1; }
cd /opt/niktrade-platform
command -v getfacl >/dev/null
command -v setfacl >/dev/null
for root in storage/app/public storage/app/private; do
    [[ $(readlink -f "$root") == "/opt/niktrade-platform/$root" ]] || { echo 'Unexpected upload root.' >&2; exit 1; }
done

backup="storage/app/upload-permissions-$(date +%Y%m%d%H%M%S)"
mkdir -m 700 "$backup"
getfacl -R -p storage/app/public storage/app/private > "$backup/acls.restore"

for root in storage/app/public storage/app/private; do
    find "$root" -type d -exec setfacl -m u:www-data:rwx,u:niktrade:rwx,m::rwx,d:u:www-data:rwx,d:u:niktrade:rwx,d:m::rwx {} +
    find "$root" -type f -exec setfacl -m u:www-data:rw,u:niktrade:rw,m::rw {} +
done

for user in www-data niktrade; do
    while IFS= read -r directory; do
        runuser -u "$user" -- test -w "$directory"
    done < <(find storage/app/public storage/app/private -type d)
done

echo "Upload permissions repaired; previous ACLs: $backup/acls.restore"
