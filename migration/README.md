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
