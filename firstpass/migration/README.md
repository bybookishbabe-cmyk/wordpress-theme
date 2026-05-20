# Shopify to WordPress Migration

This folder is for one-time migration exports and planning.

## Step 1: Add local Shopify credentials

Copy `.env.example` to `.env` and fill in:

- `SHOPIFY_SHOP_DOMAIN`
- `SHOPIFY_ADMIN_TOKEN`

The `.env` file is ignored by Git and should not be pushed.

## Step 2: Inspect metaobject definitions

```bash
npm run shopify:inspect
```

This creates:

```text
migration/exports/metaobject-definitions.json
```

## Step 3: Export metaobjects

```bash
npm run shopify:export
```

This creates:

```text
migration/exports/metaobjects/<type>.json
```

Those JSON files become the source for WordPress custom post types and import scripts.

## Step 4: Export Shopify pages and blog posts

```bash
npm run shopify:content
```

This creates:

```text
migration/exports/content/pages.json
migration/exports/content/blogs.json
migration/exports/content/blog-articles.json
```

Those JSON files become the source for WordPress pages and posts.

## Step 5: Create a WordPress import file

```bash
npm run shopify:wxr
```

This creates:

```text
migration/exports/wordpress-content-import.xml
```

Import that XML in WordPress under Tools > Import > WordPress.

## Step 6: Create a WordPress library import file

```bash
npm run shopify:library:wxr
```

This creates:

```text
migration/exports/wordpress-library-import-drafts.xml
```

Import that XML after the updated theme is installed. It creates draft Books and Quotes from Shopify metaobjects, preserving cover URLs, affiliate links, scores, genres, tropes, and series data.

## Step 7: Export Shopify products for WooCommerce

```bash
npm run shopify:products
```

This creates:

```text
migration/exports/products/shopify-products-full.json
migration/exports/products/digital-products.json
migration/exports/products/digital-products.csv
migration/exports/products/society-products.json
migration/exports/products/society-products.csv
migration/exports/products/society-products-free-for-members.json
migration/exports/products/society-products-free-for-members.csv
```

Import `digital-products.csv` in WordPress under Users > Society Products to create WooCommerce draft products. The legacy `society-products.csv` file is kept as an alias for the same digital-only import, so older notes still work. If you only want the products referenced by `sss_drop` first, import `society-products-free-for-members.csv`.

Shopify product exports usually do not include the actual files managed by a digital downloads app. Before publishing imported products, fill the `download_url` column with the final PDF/ZIP/Canva delivery URL for each product that should be downloadable. Rows with `download_missing` set to `yes` are safe to import as drafts, but they should not be published until the URL is filled. Rows with `society_free` set to `yes` are free in cart for paid Society members.
