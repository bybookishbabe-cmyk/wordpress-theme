#!/usr/bin/env zsh
set -euo pipefail

REPO_ROOT="${0:A:h:h}"
LOCAL_THEME="${BBB_LOCALWP_THEME:-/Users/autumnmarie/Local Sites/bybookishbabe/app/public/wp-content/themes/wordpress-theme}"
LOCAL_URL="${BBB_LOCAL_URL:-http://bybookishbabe.local}"
LIVE_URL="${BBB_LIVE_URL:-https://bybookishbabe.com}"
REMOTE="${BBB_WPENGINE_SSH:-wpengine-bybookishbabe}"
FAILURES=0

ok() {
  printf 'OK   %s\n' "$1"
}

warn() {
  printf 'WARN %s\n' "$1"
}

fail() {
  printf 'FAIL %s\n' "$1"
  FAILURES=$((FAILURES + 1))
}

check_local_link() {
  if [ ! -L "${LOCAL_THEME}" ]; then
    fail "LocalWP theme is not a symlink: ${LOCAL_THEME}"
    return
  fi

  local target
  target="$(readlink "${LOCAL_THEME}")"

  if [ "${target}" = "${REPO_ROOT}" ]; then
    ok "LocalWP previews the repo directly"
  else
    fail "LocalWP theme points to ${target}, expected ${REPO_ROOT}"
  fi
}

check_url() {
  local label="$1"
  local url="$2"
  local http_status

  http_status="$(/bin/zsh -lc 'curl -sS -I -L -o /dev/null -w "%{http_code}" "$1"' _ "${url}" || true)"

  if [ "${http_status}" = "200" ]; then
    ok "${label} responds 200: ${url}"
  else
    fail "${label} responds ${http_status:-unreachable}: ${url}"
  fi
}

check_ssh() {
  if ssh -o BatchMode=yes -o ConnectTimeout=8 "${REMOTE}" 'true' >/dev/null 2>&1; then
    ok "WP Engine SSH is ready: ${REMOTE}"
  else
    warn "WP Engine SSH is not ready yet: ${REMOTE}"
    warn "Add /Users/autumnmarie/.ssh/wpengine_codex_ed25519.pub to WP Engine SSH keys, then rerun this check"
  fi
}

check_git() {
  local git_status
  git_status="$(git -C "${REPO_ROOT}" status --short)"

  if [ -z "${git_status}" ]; then
    ok "Repo has no uncommitted changes"
  else
    warn "Repo has uncommitted changes"
    printf '%s\n' "${git_status}"
  fi
}

check_local_link
check_url "LocalWP" "${LOCAL_URL}"
check_url "Live" "${LIVE_URL}/?bbb_cache_bust=workflow-check"
if [ -x "${REPO_ROOT}/scripts/live-smoke-check.sh" ]; then
  "${REPO_ROOT}/scripts/live-smoke-check.sh"
fi
if [ -x "${REPO_ROOT}/scripts/live-asset-check.sh" ]; then
  "${REPO_ROOT}/scripts/live-asset-check.sh"
fi
check_ssh
check_git

if [ "${FAILURES}" -gt 0 ]; then
  printf '\n%s workflow check(s) failed.\n' "${FAILURES}"
  exit 1
fi

printf '\nWorkflow checks passed.\n'
