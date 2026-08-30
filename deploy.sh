#!/usr/bin/env bash
# Deploy sparklife-theme + cc-fields + the MyMomo connector over SFTP.
# Usage: ./deploy.sh /path/to/sftp.env     (env: SFTP_HOST/PORT/USER/PASS)
#
# WPStaq is SFTP-only; the login lands at the account home and WordPress lives
# under wordpress/, so wp-content is wordpress/wp-content. Add/update only
# (no --delete) so nothing else on the host is touched.
set -euo pipefail

ENV_FILE="${1:?usage: deploy.sh /path/to/sftp.env}"
# shellcheck disable=SC1090
source "$ENV_FILE"


# Refuse to deploy to the wrong site. The WPStaq SFTP username is the site
# identifier, and this value is hardcoded here rather than read from the env
# file, so a wrong or copied sftp.env cannot defeat it. Fails closed: if this
# ever needs changing, change it deliberately.
EXPECTED_USER="sparklife"
if [ "${SFTP_USER:-}" != "$EXPECTED_USER" ]; then
  echo "REFUSING TO DEPLOY." >&2
  echo "  env file points at SFTP user '${SFTP_USER:-<unset>}'" >&2
  echo "  this repo deploys to        '$EXPECTED_USER'" >&2
  echo "  Check which sftp.env you passed." >&2
  exit 1
fi
ROOT="$(cd "$(dirname "$0")" && pwd)"
LOCAL="$ROOT/wp-content"
REMOTE_BASE="${REMOTE_BASE:-wordpress/wp-content}"

command -v lftp >/dev/null || { echo "lftp not installed (brew install lftp)"; exit 1; }

# Rebuild the minified CSS/JS the theme serves. Run every time so the built
# files can never lag behind the commented sources.
python3 "$ROOT/tools/build-assets.py"

lftp -u "$SFTP_USER","$SFTP_PASS" -p "$SFTP_PORT" "sftp://$SFTP_HOST" <<EOF
set sftp:auto-confirm yes
set net:max-retries 2
set net:timeout 25
set mirror:parallel-transfer-count 4
mirror -R --no-perms --overwrite --verbose --exclude-glob .DS_Store "$LOCAL/themes/sparklife-theme/" "$REMOTE_BASE/themes/sparklife-theme/"
mirror -R --no-perms --overwrite --verbose --exclude-glob .DS_Store "$LOCAL/plugins/cc-fields/" "$REMOTE_BASE/plugins/cc-fields/"
mirror -R --no-perms --overwrite --verbose --exclude-glob .DS_Store "$LOCAL/plugins/virtual-office-ai-connector/" "$REMOTE_BASE/plugins/virtual-office-ai-connector/"
bye
EOF

echo "✓ Deployed sparklife-theme + cc-fields + MyMomo connector to $SFTP_HOST:$REMOTE_BASE"
echo "  Next: activate the theme + both plugins, run CC Fields → Seed / Rebuild, clear the page cache."
