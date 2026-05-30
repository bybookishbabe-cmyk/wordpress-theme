#!/usr/bin/env zsh
set -euo pipefail

LIVE_URL="${BBB_LIVE_URL:-https://bybookishbabe.com}"
SMOKE_TOKEN="${BBB_SMOKE_TOKEN:-$(date +%s)}"

typeset -a PATHS
PATHS=(
  "/"
  "/shop/"
  "/checkout/"
  "/account/"
  "/my-bookshelf/"
  "/library/"
  "/book-reviews/"
  "/series-reading-orders/"
  "/weekly-obsession/"
  "/reader-quizzes/"
)

failures=0

check_path() {
  local path="$1"
  local url="${LIVE_URL}${path}?bbb_smoke=${SMOKE_TOKEN}"
  local body
  local http_status
  local attempt

  body="$(/usr/bin/mktemp)"
  http_status="000"

  for attempt in 1 2 3; do
    : > "${body}"
    http_status="$(/usr/bin/curl -sS -L -o "${body}" -w "%{http_code}" "${url}" || true)"
    if [[ "${http_status}" != "000" && -n "${http_status}" ]]; then
      break
    fi
    sleep 1
  done

  if [[ "${http_status}" == "000" || -z "${http_status}" ]]; then
    printf 'FAIL unreachable: %s\n' "${LIVE_URL}${path}"
    failures=$((failures + 1))
    /bin/rm -f "${body}"
    return
  fi

  if [[ "${http_status}" -ge 500 ]]; then
    printf 'FAIL HTTP %s: %s\n' "${http_status}" "${LIVE_URL}${path}"
    failures=$((failures + 1))
  elif /usr/bin/grep -qi 'There has been a critical error on this website' "${body}"; then
    printf 'FAIL WordPress critical error: %s\n' "${LIVE_URL}${path}"
    failures=$((failures + 1))
  elif /usr/bin/grep -qi 'Briefly unavailable for scheduled maintenance' "${body}"; then
    printf 'FAIL maintenance mode: %s\n' "${LIVE_URL}${path}"
    failures=$((failures + 1))
  elif [[ "${http_status}" -ge 400 ]]; then
    printf 'FAIL HTTP %s: %s\n' "${http_status}" "${LIVE_URL}${path}"
    failures=$((failures + 1))
  else
    printf 'OK   HTTP %s: %s\n' "${http_status}" "${LIVE_URL}${path}"
  fi

  /bin/rm -f "${body}"
}

for path in "${PATHS[@]}"; do
  check_path "${path}"
done

if [[ "${failures}" -gt 0 ]]; then
  printf '\n%s live smoke check(s) failed.\n' "${failures}"
  exit 1
fi

printf '\nLive smoke checks passed.\n'
