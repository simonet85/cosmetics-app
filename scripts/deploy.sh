#!/usr/bin/env bash
#
# Deploy the app to production over SSH (Hostinger shared hosting).
#
#   scripts/deploy.sh --dry-run     show what would happen, change nothing
#   scripts/deploy.sh               deploy origin/main
#
# What it does, in this order, and stops at the first failure:
#   1. checks that you are on an up-to-date, clean main and builds the front-end assets locally
#      (the server has no Node, so public/build is built here and uploaded);
#   2. checks the server: SSH, database access, disk space;
#   3. puts the site in maintenance mode;
#   4. dumps the database to ~/backups on the server (kept, mode 600);
#   5. updates the code (fast-forward only), installs Composer dependencies, uploads the assets;
#   6. runs the migrations, rebuilds the caches, brings the site back up;
#   7. smoke-tests the public pages and the scheduler.
#
# If a step fails after the code was updated, the script prints how to go back (code and assets).
# The database is NOT rolled back automatically: restore the dump it printed if a migration went wrong.
#
# Requires an SSH key already authorised on the server and Docker (or Node) on this machine.

set -euo pipefail

SSH_TARGET="${DEPLOY_SSH:-u104407086@82.25.113.207}"
SSH_PORT="${DEPLOY_PORT:-65002}"
REMOTE_APP="${DEPLOY_PATH:-domains/klab-consulting.com/laravel}"   # relative to the remote $HOME
SITE_URL="${DEPLOY_SITE:-https://klab-consulting.com}"
BRANCH="main"

DRY_RUN=0
[ "${1:-}" = "--dry-run" ] && DRY_RUN=1

say()  { printf '\n\033[1m== %s\033[0m\n' "$*"; }
note() { printf '   %s\n' "$*"; }
die()  { printf '\n\033[31mSTOPPED: %s\033[0m\n' "$*" >&2; exit 1; }

remote() { ssh -p "$SSH_PORT" -o BatchMode=yes -o ConnectTimeout=20 "$SSH_TARGET" "cd \"\$HOME/$REMOTE_APP\" && $*"; }

cd "$(dirname "$0")/.."

# ------------------------------------------------------------------ 1. local checks and assets
say "1. Local checks"
[ "$(git rev-parse --abbrev-ref HEAD)" = "$BRANCH" ] || die "not on $BRANCH (merge your pull request first, then 'git checkout $BRANCH && git pull')"
if ! git diff --quiet || ! git diff --cached --quiet; then die "uncommitted changes in the working tree"; fi
git fetch -q origin "$BRANCH"
[ "$(git rev-parse HEAD)" = "$(git rev-parse "origin/$BRANCH")" ] || die "local $BRANCH differs from origin/$BRANCH (git pull / git push first)"
note "deploying $(git log --oneline -1)"

say "1b. Build the front-end assets (public/build)"
if [ "$DRY_RUN" = 1 ]; then
  note "[dry-run] npm run build (through the node container or local Node)"
else
  if command -v docker >/dev/null 2>&1 && [ -f docker-compose.yml ]; then
    docker compose run --rm --no-deps node sh -c "npm ci --no-audit --no-fund && npm run build"
  else
    npm ci --no-audit --no-fund && npm run build
  fi
  [ -f public/build/manifest.json ] || die "the build did not produce public/build/manifest.json"
fi

# ------------------------------------------------------------------ 2. remote preflight
say "2. Server preflight"
remote 'echo "   host: $(hostname)  php: $(php -r "echo PHP_VERSION;")  commit: $(git rev-parse --short HEAD)"' || die "cannot reach the server over SSH"
remote 'git diff --quiet && git diff --cached --quiet' || die "the server's working tree has local changes; look at them first (git status on the server)"
remote 'php artisan migrate:status >/dev/null 2>&1' || die "the application cannot reach its database (check DB_* in the server .env). Nothing was changed."
remote 'echo "   disk free: $(df -h . | tail -1 | awk "{print \$4}")"'
PREV_COMMIT="$(remote 'git rev-parse HEAD')"
note "current server commit: ${PREV_COMMIT:0:7}"
PENDING="$(remote 'php artisan migrate:status 2>/dev/null | grep -c "Pending" || true')"
note "migrations waiting to run: $PENDING"

if [ "$DRY_RUN" = 1 ]; then
  say "Dry run: the steps below would run"
  note "3. php artisan down"
  note "4. mysqldump -> ~/backups/cosmetics-<date>.sql.gz (credentials read from the server .env, never printed)"
  note "5. git merge --ff-only origin/$BRANCH ; composer install --no-dev ; upload public/build"
  note "6. php artisan migrate --force ; config/route/view cache ; php artisan up"
  note "7. smoke test $SITE_URL/ /shop /login ; php artisan schedule:list"
  exit 0
fi

rollback_hint() {
  printf '\n\033[33mTo go back to the previous code and assets:\033[0m\n'
  printf "  ssh -p %s %s 'cd \"\$HOME/%s\" && git reset --hard %s && rm -rf public/build && mv public/build.prev public/build; php artisan up'\n" "$SSH_PORT" "$SSH_TARGET" "$REMOTE_APP" "${PREV_COMMIT:0:7}"
  [ -n "${BACKUP_FILE:-}" ] && printf '  Database dump taken before the migrations: %s (on the server)\n' "$BACKUP_FILE"
}
# shellcheck disable=SC2154 # rc is set inside the trap string
trap 'rc=$?; if [ $rc -ne 0 ]; then rollback_hint; printf "\nThe site may still be in maintenance mode: php artisan up\n"; fi' EXIT

# ------------------------------------------------------------------ 3-4. maintenance mode and database backup
say "3. Maintenance mode"
remote 'php artisan down --retry=60 >/dev/null'

say "4. Database backup"
BACKUP_FILE="$(remote 'bash -s' <<'REMOTE'
set -euo pipefail
mkdir -p "$HOME/backups"; chmod 700 "$HOME/backups"
val() { grep -E "^$1=" .env | head -1 | cut -d= -f2- | sed -E 's/^"(.*)"$/\1/; s/^'"'"'(.*)'"'"'$/\1/'; }
out="$HOME/backups/cosmetics-$(date +%Y%m%d-%H%M%S).sql.gz"
MYSQL_PWD="$(val DB_PASSWORD)" mysqldump --single-transaction --no-tablespaces -h "$(val DB_HOST)" -P "$(val DB_PORT)" -u "$(val DB_USERNAME)" "$(val DB_DATABASE)" | gzip > "$out"
chmod 600 "$out"
[ "$(stat -c %s "$out")" -gt 1000 ] || { echo "backup looks empty" >&2; exit 1; }
echo "$out"
REMOTE
)"
note "saved: $BACKUP_FILE"

# ------------------------------------------------------------------ 5. code and assets
say "5. Update the code"
remote "git fetch -q origin $BRANCH && git merge --ff-only origin/$BRANCH"
remote 'composer install --no-dev --optimize-autoloader --no-interaction --no-progress 2>&1 | tail -3'

say "5b. Upload the assets"
remote 'rm -rf public/build.prev; [ -d public/build ] && mv public/build public/build.prev || true'
tar -C public -czf - build | ssh -p "$SSH_PORT" -o BatchMode=yes "$SSH_TARGET" "cd \"\$HOME/$REMOTE_APP/public\" && tar -xzf -"
remote 'test -f public/build/manifest.json' || die "assets were not uploaded correctly"

# ------------------------------------------------------------------ 6. migrate, caches, back up
say "6. Migrations and caches"
remote 'php artisan migrate --force'
remote 'php artisan storage:link >/dev/null 2>&1 || true'
remote 'php artisan config:clear >/dev/null && php artisan route:clear >/dev/null && php artisan view:clear >/dev/null && php artisan config:cache && php artisan route:cache && php artisan view:cache'
remote 'php artisan up'

# ------------------------------------------------------------------ 7. smoke tests
say "7. Smoke tests"
fail=0
for path in / /shop /login; do
  code="$(curl -s -o /dev/null -m 30 -w '%{http_code}' "$SITE_URL$path")"
  note "$SITE_URL$path -> HTTP $code"
  [ "$code" = "200" ] || fail=1
done
remote 'php artisan schedule:list 2>&1 | grep -c "moneyfusion:check-pending" | sed "s/^/   scheduled MoneyFusion checks: /"'
[ "$fail" = 0 ] || die "a public page did not answer 200"

say "Done: $(git log --oneline -1)"
note "Remember: hPanel must run 'php artisan schedule:run' every minute (Advanced > Cron Jobs)."
trap - EXIT
