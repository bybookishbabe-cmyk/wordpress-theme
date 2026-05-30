#!/usr/bin/env bash
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
LOCAL_THEME="${BBB_LOCALWP_THEME:-/Users/autumnmarie/Local Sites/bybookishbabe/app/public/wp-content/themes/wordpress-theme}"
BACKUP_ROOT="${BBB_LOCALWP_BACKUP_ROOT:-/Users/autumnmarie/Documents/wordpress-theme-local-backups}"
STAMP="$(date +%Y%m%d%H%M%S)"
BACKUP_PATH="${BACKUP_ROOT}/wordpress-theme-localwp-before-link-${STAMP}"

mkdir -p "${BACKUP_ROOT}"

if [ -L "${LOCAL_THEME}" ]; then
  CURRENT_TARGET="$(readlink "${LOCAL_THEME}")"
  if [ "${CURRENT_TARGET}" = "${REPO_ROOT}" ]; then
    echo "LocalWP already points at ${REPO_ROOT}"
    exit 0
  fi
fi

if [ -e "${LOCAL_THEME}" ]; then
  echo "Backing up current LocalWP theme to ${BACKUP_PATH}"
  mv "${LOCAL_THEME}" "${BACKUP_PATH}"
fi

echo "Linking ${LOCAL_THEME} -> ${REPO_ROOT}"
ln -s "${REPO_ROOT}" "${LOCAL_THEME}"
echo "Done."

