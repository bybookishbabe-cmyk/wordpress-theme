# By Bookish Babe Shopify-Faithful Port

This repository has been reset around a simpler migration strategy:

- `firstpass/` contains the previous WordPress rebuild attempt.
- The repository root contains a direct copy of the Shopify theme folders:
  `assets/`, `config/`, `layout/`, `locales/`, `sections/`, `snippets/`, `supabase/`, and `templates/`.
- `style.css`, `functions.php`, and `index.php` are minimal WordPress bootstrap files so the theme remains installable while the Shopify files are converted.

## Rule For The New Pass

Do not redesign Shopify components in WordPress.

Convert Shopify Liquid files into PHP template parts while preserving:

- DOM structure
- Class names
- Data attributes
- CSS/JS asset contracts
- Section order from `templates/index.json`

Only replace platform-specific data access:

- Shopify `customer` -> WordPress user
- Shopify `routes` -> WordPress/WooCommerce URLs
- Shopify `shop.metaobjects` -> WordPress CPTs/taxonomies
- Shopify `blogs/articles` -> WordPress posts/categories
- Shopify `/pages/...` URLs -> WordPress permalinks

## Source Of Truth

The repo should be the one place where theme code lives:

```text
/Users/autumnmarie/Documents/wordpress-theme
```

LocalWP should preview that same folder through a symlink:

```text
/Users/autumnmarie/Local Sites/bybookishbabe/app/public/wp-content/themes/wordpress-theme
```

If the LocalWP copy ever gets replaced by a real folder again, relink it with:

```bash
./scripts/link-localwp-theme.sh
```

That script backs up the existing LocalWP theme folder before making the symlink.

Before deploying, run the workflow check:

```bash
./scripts/check-workflow.sh
```

It verifies that LocalWP is linked to this repo, the local site responds, the live site responds, key live pages do not show WordPress critical errors or maintenance mode, WP Engine SSH is ready, and the repo status is visible before anything is pushed.

## Safer Theme Updates

Avoid updating this theme through the WordPress admin theme uploader. If an admin update times out, WordPress can leave a `.maintenance` file behind and the public site shows:

`Briefly unavailable for scheduled maintenance. Check back in a minute.`

Use the deploy helper instead:

```bash
./scripts/deploy-theme.sh
```

The helper lints PHP when a PHP binary is available, checks JavaScript syntax in `assets/js`, runs live smoke checks before deploy, builds a theme ZIP locally, uploads it to WP Engine over SSH, expands it into a temporary theme directory, then swaps the complete directory into place. It removes a stale `.maintenance` file during cleanup, purges WP Engine cache, and runs the live smoke checks again after deploy.

For checkout/cart changes, run the browser cart smoke test too:

```bash
node scripts/smoke-live-cart.mjs
```

That test opens the live shop in a mobile-sized browser, adds one product variant to cart, verifies only the tapped card changes, then confirms checkout contains the selected variant.

When asking Codex for a site change, use this workflow:

```text
Please make this change locally first, run the workflow/cart checks if it touches shop or checkout, show me what changed, then deploy with ./scripts/deploy-theme.sh only after the checks pass.
```

If the site is already stuck in maintenance mode, run:

```bash
./scripts/clear-wp-maintenance.sh
```

Defaults can be overridden when needed:

```bash
BBB_WPENGINE_SSH=wpengine-bybookishbabe \
BBB_WPENGINE_WP_ROOT=sites/bybookishbabe \
BBB_THEME_SLUG=wordpress-theme \
./scripts/deploy-theme.sh
```
