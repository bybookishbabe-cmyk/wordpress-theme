#!/usr/bin/env bash
set -euo pipefail

REMOTE="${BBB_WPENGINE_SSH:-wpengine-bybookishbabe}"
WP_ROOT="${BBB_WPENGINE_WP_ROOT:-sites/bybookishbabe}"
THEME_SLUG="${BBB_THEME_SLUG:-wordpress-theme}"
REMOTE_THEMES_DIR="${WP_ROOT}/wp-content/themes"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
RELEASE_ID="$(date +%Y%m%d%H%M%S)"
LOCAL_BUILD_DIR="${TMPDIR:-/tmp}/bbb-theme-deploy-${RELEASE_ID}"
LOCAL_ZIP="${LOCAL_BUILD_DIR}/${THEME_SLUG}-${RELEASE_ID}.zip"
REMOTE_ZIP="${WP_ROOT}/${THEME_SLUG}-${RELEASE_ID}.zip"
REMOTE_STAGE="${REMOTE_THEMES_DIR}/${THEME_SLUG}.deploying-${RELEASE_ID}"
REMOTE_CURRENT="${REMOTE_THEMES_DIR}/${THEME_SLUG}"
REMOTE_BACKUP="${REMOTE_THEMES_DIR}/${THEME_SLUG}.backup-${RELEASE_ID}"

cleanup_local() {
  rm -rf "${LOCAL_BUILD_DIR}"
}
trap cleanup_local EXIT

require_command() {
  if ! command -v "$1" >/dev/null 2>&1; then
    echo "Missing required command: $1" >&2
    exit 1
  fi
}

lint_php() {
  local php_bin="${BBB_PHP_BIN:-php}"

  if ! command -v "${php_bin}" >/dev/null 2>&1; then
    echo "Skipping PHP lint because ${php_bin} is not available. Set BBB_PHP_BIN to Local's PHP binary to enable it."
    return
  fi

  echo "Linting PHP files..."
  find "${REPO_ROOT}" \
    -path "${REPO_ROOT}/.git" -prune -o \
    -path "${REPO_ROOT}/vendor" -prune -o \
    -path "${REPO_ROOT}/node_modules" -prune -o \
    -name '*.php' -print0 \
    | xargs -0 -n 1 "${php_bin}" -l >/dev/null
}

lint_js() {
  local node_bin="${BBB_NODE_BIN:-node}"

  if ! command -v "${node_bin}" >/dev/null 2>&1; then
    echo "Skipping JS syntax checks because ${node_bin} is not available."
    return
  fi

  echo "Checking JS syntax..."
  find "${REPO_ROOT}/assets/js" \
    -name '*.js' -print0 \
    | xargs -0 -n 1 "${node_bin}" --check >/dev/null
}

smoke_live() {
  if [ "${BBB_SKIP_LIVE_SMOKE:-0}" = "1" ]; then
    echo "Skipping live smoke checks because BBB_SKIP_LIVE_SMOKE=1."
    return
  fi

  "${SCRIPT_DIR}/live-smoke-check.sh"
  "${SCRIPT_DIR}/live-asset-check.sh"

  if [ "${BBB_SKIP_CART_SMOKE:-0}" = "1" ]; then
    echo "Skipping cart smoke because BBB_SKIP_CART_SMOKE=1."
    return
  fi

  "${BBB_NODE_BIN:-node}" "${SCRIPT_DIR}/smoke-live-cart.mjs"
}

build_zip() {
  echo "Building theme ZIP..."
  mkdir -p "${LOCAL_BUILD_DIR}"

  (
    cd "${REPO_ROOT}"
    zip -qr "${LOCAL_ZIP}" . \
      -x '.git/*' \
      -x '.DS_Store' \
      -x '.env' \
      -x '*/.env' \
      -x 'node_modules/*' \
      -x 'vendor/*' \
      -x 'supabase/.temp/*' \
      -x 'screenshot.png' \
      -x 'scripts/*'
  )
}

deploy_remote() {
  echo "Uploading ${LOCAL_ZIP} to ${REMOTE}:${REMOTE_ZIP}"
  rsync -av "${LOCAL_ZIP}" "${REMOTE}:${REMOTE_ZIP}"

  echo "Deploying ${THEME_SLUG} to ${REMOTE}:${REMOTE_CURRENT}"
  ssh "${REMOTE}" "set -euo pipefail;
    cleanup() {
      rm -f '${REMOTE_ZIP}';
      rm -rf '${REMOTE_STAGE}';
      rm -f '${WP_ROOT}/.maintenance';
    };
    trap cleanup EXIT;

    mkdir -p '${REMOTE_STAGE}';
    unzip -q '${REMOTE_ZIP}' -d '${REMOTE_STAGE}';
    test -f '${REMOTE_STAGE}/style.css';
    test -f '${REMOTE_STAGE}/functions.php';

    if [ -d '${REMOTE_CURRENT}' ] || [ -L '${REMOTE_CURRENT}' ]; then
      mv '${REMOTE_CURRENT}' '${REMOTE_BACKUP}';
    fi;

    mv '${REMOTE_STAGE}' '${REMOTE_CURRENT}';
    rm -f '${WP_ROOT}/.maintenance';

    echo 'Theme deployed.';
    echo 'Previous theme backup: ${REMOTE_BACKUP}';
  "
}

purge_remote_cache() {
  echo "Purging WP Engine cache..."
  ssh "${REMOTE}" "cd '${WP_ROOT}'; wp cache flush"
}

require_command zip
require_command rsync
require_command ssh

lint_php
lint_js
echo "Running pre-deploy live smoke checks..."
smoke_live
build_zip
deploy_remote
purge_remote_cache
echo "Running post-deploy live smoke checks..."
smoke_live

echo "Done. Verify the site with:"
echo "curl -I -L 'https://bybookishbabe.wpenginepowered.com/?bbb_cache_bust=${RELEASE_ID}'"
