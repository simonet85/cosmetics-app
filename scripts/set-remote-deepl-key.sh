#!/usr/bin/env bash
#
# Sets DEEPL_API_KEY in the PRODUCTION .env. Run it yourself: the key is a secret and this script is the only
# place it travels.
#
#   bash scripts/set-remote-deepl-key.sh
#
# It asks for the key without showing it (or reads it from the first line of a file given as argument), sends it
# to the server over SSH through standard input (never on a command line, never printed), keeps a copy of the old
# .env (.env.bak-before-deepl-<date>, mode 600), replaces the DEEPL_API_KEY line (or adds it), rebuilds the
# configuration cache and asks DeepL for the account usage to prove the key works.

set -euo pipefail

SSH_TARGET="${DEPLOY_SSH:-u104407086@82.25.113.207}"
SSH_PORT="${DEPLOY_PORT:-65002}"
REMOTE_APP="${DEPLOY_PATH:-domains/klab-consulting.com/laravel}"

if [ -n "${1:-}" ]; then
  [ -f "$1" ] || { echo "file not found: $1" >&2; exit 1; }
  key="$(head -n 1 "$1" | tr -d '\r\n ')"
else
  read -rsp "DeepL API key (hidden): " key
  echo
fi
[ -n "$key" ] || { echo "empty key, nothing changed" >&2; exit 1; }

b64="$(printf '%s' "$key" | base64 | tr -d '\n')"

echo "Updating DEEPL_API_KEY on the server (the old .env is kept as .env.bak-before-deepl-<date>)..."
{
  printf '%s\n' "$b64"
  cat <<'PHP'
<?php
$key = base64_decode((string) getenv('B64'), true);
if ($key === false || $key === '') { exit("could not read the key\n"); }

$backup = '.env.bak-before-deepl-'.date('Ymd-His');
if (! copy('.env', $backup)) { exit("backup failed, nothing changed\n"); }
chmod($backup, 0600);

$content = file_get_contents('.env');
$line = 'DEEPL_API_KEY="'.addcslashes($key, '"\$').'"';
$count = 0;
$updated = preg_replace_callback('/^DEEPL_API_KEY=.*$/m', fn () => $line, $content, -1, $count);
if ($count === 0) { $updated = rtrim($content, "\n")."\n".$line."\n"; }
if ($count > 1) { exit("found $count DEEPL_API_KEY lines; nothing changed\n"); }

file_put_contents('.env', $updated);
echo "backup: $backup\n";
echo $count === 1 ? "DEEPL_API_KEY line replaced\n" : "DEEPL_API_KEY line added\n";
PHP
} | ssh -p "$SSH_PORT" -o BatchMode=yes "$SSH_TARGET" "cd \"\$HOME/$REMOTE_APP\" && read -r B64 && export B64 && php"

echo
echo "Rebuilding the configuration cache and checking the key (usage only, nothing is translated)..."
ssh -p "$SSH_PORT" -o BatchMode=yes "$SSH_TARGET" "cd \"\$HOME/$REMOTE_APP\" && php artisan config:cache 2>&1 | tail -1 && php artisan deepl:usage 2>&1 | tail -6"
