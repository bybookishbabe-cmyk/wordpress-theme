#!/usr/bin/env bash
set -euo pipefail

REMOTE="${BBB_WPENGINE_SSH:-wpengine-bybookishbabe}"
WP_ROOT="${BBB_WPENGINE_WP_ROOT:-sites/bybookishbabe}"

echo "Checking for stuck WordPress maintenance mode on ${REMOTE}:${WP_ROOT}"

ssh "${REMOTE}" "if [ -f '${WP_ROOT}/.maintenance' ]; then rm '${WP_ROOT}/.maintenance' && echo 'Removed ${WP_ROOT}/.maintenance'; else echo 'No .maintenance file found.'; fi"

