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
