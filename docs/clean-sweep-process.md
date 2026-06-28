# Clean Sweep Process

Use the clean sweep path for every visible web/PWA fix, especially links, routes, homepage/member UI, quiz flows, navigation, and anything that can be cached.

## Why fixes came back

The site has several layers that can preserve old behavior:

- Theme templates and JS can contain duplicate links or fallback logic.
- `inc/page-router.php` can keep old virtual routes alive even after a button is changed.
- `inc/linking.php` can normalize old generated links into new ones, or miss them.
- WordPress/WP Engine/Cloudflare cache can serve older HTML/assets.
- The PWA service worker can keep older pages/assets until the cache name changes.
- Browser/PWA localStorage can keep old account-specific state unless keys are scoped.

## Required Sweep

For visible changes, do all of this before calling done:

1. Search for the old behavior across templates, PHP, JS, and routes with `rg`.
2. Fix the visible component and the route/link source of truth.
3. Add or update a redirect for old public URLs when a stale link may exist.
4. Scope browser/PWA storage by account when the behavior is account-specific.
5. Run PHP/JS syntax checks on touched files.
6. Deploy with a PWA version bump and WP cache flush.
7. Verify live URLs, not just local code.

## Command

Prefer:

```bash
bash scripts/clean-sweep-deploy.sh --expect-redirect /old/path/=/new/path/ -- file.php assets/js/file.js
```

Examples:

```bash
bash scripts/clean-sweep-deploy.sh \
  --expect-redirect /reader-quizzes/fictional-boyfriend/=/fictional-boyfriend-quiz/ \
  -- single-bbb_boyfriend.php inc/page-router.php inc/linking.php
```

```bash
bash scripts/clean-sweep-deploy.sh \
  --expect-contains /fictional-boyfriend-quiz/='who is your fictional boyfriend' \
  -- page-fictional-boyfriend-quiz.php assets/js/reader-quiz.js
```

The script lints touched PHP/JS, calls `scripts/push-files.sh` so the PWA cache name changes, flushes WP cache, runs live smoke/asset checks, then verifies declared redirects/content on the live site.
