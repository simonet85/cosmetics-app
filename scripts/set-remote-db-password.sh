#!/usr/bin/env bash
#
# Sets DB_PASSWORD in the PRODUCTION .env from your local credentials file.
#
#   bash scripts/set-remote-db-password.sh "../VARIABLES ET ACCES.txt"
#
# It reads the "Password:" line under "# MySQL Databases And Users cosmetics app" in that file, sends it to the
# server over SSH (standard input, never on a command line, never printed), keeps a copy of the old .env
# (.env.bak-before-dbpass-<date>), changes only the DB_PASSWORD line, then checks that the site answers.
# Run it yourself: the password is a secret and this script is the only place it travels.

set -euo pipefail

FILE="${1:-../VARIABLES ET ACCES.txt}"
SSH_TARGET="${DEPLOY_SSH:-u104407086@82.25.113.207}"
SSH_PORT="${DEPLOY_PORT:-65002}"
REMOTE_APP="${DEPLOY_PATH:-domains/klab-consulting.com/laravel}"
SITE_URL="${DEPLOY_SITE:-https://klab-consulting.com}"

[ -f "$FILE" ] || { echo "credentials file not found: $FILE" >&2; exit 1; }

pass="$(awk '/^# MySQL Databases And Users cosmetics app/{f=1;next} f&&/^Password:/{sub(/^Password:[ \t]*/,"");sub(/\r$/,"");print;exit}' "$FILE")"
[ -n "$pass" ] || { echo "no 'Password:' line found under the cosmetics app section" >&2; exit 1; }
b64="$(printf '%s' "$pass" | base64 | tr -d '\n')"

echo "Updating DB_PASSWORD on the server (the old .env is kept as .env.bak-before-dbpass-<date>)..."
{
  printf '%s\n' "$b64"
  cat <<'PHP'
<?php
$password = base64_decode((string) getenv('B64'), true);
if ($password === false || $password === '') { exit("could not read the password\n"); }

$backup = '.env.bak-before-dbpass-'.date('Ymd-His');
if (! copy('.env', $backup)) { exit("backup failed, nothing changed\n"); }

$content = file_get_contents('.env');
$updated = preg_replace_callback('/^DB_PASSWORD=.*$/m', fn () => 'DB_PASSWORD="'.addcslashes($password, '"\$').'"', $content, -1, $count);
if ($count !== 1) { exit("expected exactly one DB_PASSWORD line, found $count; nothing changed\n"); }

file_put_contents('.env', $updated);
echo "backup: $backup\n";
echo "DB_PASSWORD line replaced; other lines untouched: ", (count(array_diff(explode("\n", $updated), explode("\n", $content))) === 1 ? 'yes' : 'NO'), "\n";

// Same test the application will make
$env = [];
foreach (explode("\n", $updated) as $line) {
    if (preg_match('/^(DB_[A-Z]+)=(.*)$/', $line, $m)) { $env[$m[1]] = trim($m[2], "\"'"); }
}
try {
    new PDO('mysql:host='.$env['DB_HOST'].';dbname='.$env['DB_DATABASE'], $env['DB_USERNAME'], $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "database connection with the new password: OK\n";
} catch (Throwable $e) {
    echo "database connection with the new password: FAILED\n";
}
PHP
} | ssh -p "$SSH_PORT" -o BatchMode=yes "$SSH_TARGET" "cd \"\$HOME/$REMOTE_APP\" && read -r B64 && export B64 && php"

echo
echo "Public pages:"
for path in / /shop /login; do
  curl -s -o /dev/null -m 30 -w "  $SITE_URL$path -> HTTP %{http_code}\n" "$SITE_URL$path"
done
