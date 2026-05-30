#!/usr/bin/env zsh
set -euo pipefail

LIVE_URL="${BBB_LIVE_URL:-https://bybookishbabe.com}"
ASSET_TOKEN="${BBB_ASSET_TOKEN:-$(date +%s)}"

failures=0

check_route_assets() {
  local path="$1"
  shift

  local url="${LIVE_URL}${path}?bbb_asset_check=${ASSET_TOKEN}"
  local body
  local http_status
  local required
  local route_failures=0

  body="$(/usr/bin/mktemp)"
  http_status="$(/usr/bin/curl -sS -L -o "${body}" -w "%{http_code}" "${url}" || true)"

  if [[ "${http_status}" -ge 400 || "${http_status}" == "000" || -z "${http_status}" ]]; then
    printf 'FAIL assets HTTP %s: %s\n' "${http_status:-000}" "${LIVE_URL}${path}"
    failures=$((failures + 1))
    /bin/rm -f "${body}"
    return
  fi

  if /usr/bin/grep -qi 'There has been a critical error on this website' "${body}"; then
    printf 'FAIL assets critical error: %s\n' "${LIVE_URL}${path}"
    failures=$((failures + 1))
    /bin/rm -f "${body}"
    return
  fi

  for required in "$@"; do
    if ! /usr/bin/grep -q "${required}" "${body}"; then
      printf 'FAIL missing asset %-34s %s\n' "${required}" "${LIVE_URL}${path}"
      failures=$((failures + 1))
      route_failures=$((route_failures + 1))
    fi
  done

  if [[ "${route_failures}" -eq 0 ]]; then
    printf 'OK   assets: %s\n' "${LIVE_URL}${path}"
  fi

  /bin/rm -f "${body}"
}

check_route_assets "/member-dashboard/" \
  "assets/css/sss-library.css" \
  "assets/css/sss-folder-tabs.css" \
  "assets/css/sss-memberdash.css" \
  "assets/js/sss-library.js" \
  "assets/js/sss-memberdash.js" \
  "assets/js/sss-library-member.js"

check_route_assets "/library/" \
  "assets/css/sss-library.css" \
  "assets/css/sss-folder-tabs.css" \
  "assets/css/sss-memberdash.css" \
  "assets/js/sss-library.js"

check_route_assets "/my-bookshelf/" \
  "assets/css/my-bookshelf.css" \
  "assets/js/my-bookshelf.js" \
  "assets/css/sss-library.css" \
  "assets/js/sss-library.js"

check_route_assets "/account/" \
  "assets/css/my-bookshelf.css" \
  "assets/css/sss-library.css" \
  "assets/js/sss-library.js"

check_route_assets "/shop/" \
  "assets/css/shop-page.css" \
  "assets/js/shop-edd-cart.js"

check_route_assets "/reader-quizzes/" \
  "assets/css/reader-quizzes.css" \
  "assets/css/sss-library.css"

check_route_assets "/what-to-read-next/" \
  "assets/css/bbb-what-to-read-next.css" \
  "assets/js/bbb-what-to-read-next.js" \
  "assets/css/sss-library.css" \
  "assets/js/sss-library.js"

check_route_assets "/romance-books-by-spice-level/" \
  "assets/css/page-spice.css" \
  "assets/js/page-spice.js" \
  "assets/css/sss-library.css" \
  "assets/js/sss-library.js"

check_route_assets "/fake-dating-romance-books/" \
  "assets/css/sss-library.css" \
  "assets/js/sss-library.js"

check_route_assets "/captor-captive-romance-books/" \
  "assets/css/sss-library.css" \
  "assets/js/sss-library.js"

check_route_assets "/series-reading-orders/" \
  "assets/sss-series.css" \
  "assets/css/sss-library.css" \
  "assets/js/sss-library.js"

check_route_assets "/weekly-obsession/" \
  "assets/css/weekly-obsession.css" \
  "assets/css/sss-library.css" \
  "assets/js/sss-library.js"

check_route_assets "/popular-pages/" \
  "assets/css/popular-pages.css" \
  "assets/js/popular-pages.js"

check_route_assets "/sss-quote-wall/" \
  "assets/css/sss-quote-wall.css" \
  "assets/css/sss-library.css" \
  "assets/js/sss-library.js"

check_route_assets "/book-reviews/" \
  "assets/css/book-reviews-page.css"

check_route_assets "/books-like/" \
  "assets/css/books-like.css" \
  "assets/css/sss-library.css" \
  "assets/js/sss-library.js"

check_route_assets "/if-you-liked-pages/" \
  "assets/css/books-like.css" \
  "assets/css/sss-library.css" \
  "assets/js/sss-library.js"

check_route_assets "/if-you-liked-pages/if-you-liked-insatiable/" \
  "assets/css/books-like.css" \
  "assets/css/sss-library.css" \
  "assets/js/sss-library.js"

if [[ "${failures}" -gt 0 ]]; then
  printf '\n%s live asset check(s) failed.\n' "${failures}"
  exit 1
fi

printf '\nLive asset checks passed.\n'
