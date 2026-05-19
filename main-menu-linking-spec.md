# WordPress Conversion Spec — Main Menu + Page Linking
**bybookishbabe-theme → WordPress**
Generated from: `sections/header.liquid`, `snippets/header-dropdown-menu.liquid`, `snippets/header-drawer.liquid`, `templates/index.json`, `config/settings_data.json`, `sections/header-group.json`, `sections/footer-group.json`, and existing WordPress theme files.

---

## 1. Menu Source & Active Handle

| Property | Value |
|---|---|
| Active menu handle | `main-menu` |
| Source setting | `section.settings.menu` → default `"main-menu"` |
| Confirmed in | `sections/header-group.json` → `"menu":"main-menu"` |
| Also used in | `sections/footer-group.json` footer link_list block |
| Desktop type | `dropdown` (`menu_type_desktop: "dropdown"`) |
| Mobile trigger | `header-drawer` at `data-breakpoint="tablet"` (≤ 989 px) |
| WP menu location slug | `main-menu` (registered in `functions.php` `register_nav_menus`) |

### ⚠️ Critical Gap — Menu Tree Not in Theme Files

Shopify stores navigation link lists in the admin database. The theme only references the handle. **The actual item list, titles, URLs, and hierarchy were not exported with the theme files.** Everything in Section 2 is reconstructed from indirect evidence.

**To get the real menu tree:** Shopify Admin → Online Store → Navigation → "Main menu" → copy items manually, or run `shopify theme pull` with the Shopify CLI which includes a `navigation/` export.

---

## 2. Menu Tree (Reconstructed Skeleton)

Confidence levels: **[CONFIRMED]** = URL appears explicitly in a theme file. **[INFERRED]** = derived from page template filenames or site structure.

```
Main Menu (handle: main-menu)
│
├── Library [CONFIRMED — shopify://pages/library, hero CTA in index.json]
│   ├── Browse All [INFERRED] → /library/
│   ├── Sports Romance [CONFIRMED — shopify://pages/sports-romance-books] → /sports-romance-books/
│   ├── Enemies to Lovers [CONFIRMED — shopify://pages/enemies-to-lovers] → /enemies-to-lovers/
│   ├── Slow Burn [CONFIRMED — shopify://pages/slow-burn-books] → /slow-burn-books/
│   └── Dark Romance [CONFIRMED as blog post — shopify://blogs/curated-romance-guides/the-best-dark-romance-books-with-morally-gray-men-that-will-ruin-you]
│       → /curated-romance-guides/the-best-dark-romance-books-with-morally-gray-men-that-will-ruin-you/
│
├── Reading Guides [INFERRED — blog handle curated-romance-guides, referenced in page.for-readers.json]
│   → /curated-romance-guides/
│
├── The Society [CONFIRMED — society_url: https://bybookishbabe.com/pages/smut-sentiment-society]
│   ├── What's Inside [INFERRED] → /smut-sentiment-society/
│   └── Freebies [INFERRED — templates/page.sss-freebies.json exists] → /sss-freebies/
│
├── Shop [CONFIRMED — shopify://collections/all, image-banner hero button]
│   ├── Kindle Inserts [CONFIRMED — templates/page.kindle-inserts.json] → /kindle-inserts/
│   └── Art Prints [CONFIRMED — templates/page.artprints.json] → /art-prints/
│
└── For Readers [CONFIRMED — templates/page.for-readers.json]
    ├── Reader Quiz [CONFIRMED — page.for-readers.json links /pages/reader-quizes] → /reader-quizes/
    └── What to Read Next [CONFIRMED — templates/page.what-to-read-next.json] → /what-to-read-next/
```

**Unresolved:** Exact top-level titles, whether "Dark Romance" is a menu item or only a trope card link, whether "The Society" has additional sub-items (sss-series, sss-made-for-you, sss-quote-wall). Verify all against Shopify admin export.

### Header Icon Links (Not in Menu — Hardcoded in `sections/header.liquid`)

These live outside the nav list and are already implemented in the WP header template. Documented here for completeness.

| Element | Shopify URL | WordPress URL | Notes |
|---|---|---|---|
| Vault icon | `/pages/my-vault` | `/my-vault/` | Hardcoded `<a href="/pages/my-vault">` in header.liquid |
| SSS Substack icon | `https://thesmutandsentimentsociety.substack.com/subscribe` | same (external) | `target="_blank" rel="noopener"` |
| Account icon | `routes.account_url` / `routes.account_login_url` | `wc_get_account_endpoint_url('dashboard')` / `wp_login_url()` | Already in WP header |
| Cart icon | `routes.cart_url` | `wc_get_cart_url()` | Already in WP header |
| Reader Bookshelf | `{% render 'reader-bookshelf-access' %}` | `/my-bookshelf/` | `BBBSiteData.BBBReaderAccount.bookshelfUrl` |

---

## 3. Shopify → WordPress URL Mapping Table

All rules are implemented in `inc/linking.php` → `bbb_resolve_shopify_url()`.

| Shopify URL Pattern | WordPress URL | Notes |
|---|---|---|
| `shopify://pages/{slug}` | `/{slug}/` | Via `bbb_page_url($slug)` — tries DB lookup first |
| `/pages/{slug}` | `/{slug}/` | Same resolution |
| `https://bybookishbabe.com/pages/{slug}` | `/{slug}/` | Domain stripped |
| `shopify://blogs/{blog}/{article}` | `/{blog}/{article}/` | Blog post permalink |
| `shopify://blogs/{blog}` | `/{blog}/` | Blog archive |
| `/blogs/{blog}/{article}` | `/{blog}/{article}/` | Same |
| `/blogs/{blog}` | `/{blog}/` | Same |
| `shopify://collections/all` | `/shop/` | WooCommerce shop root |
| `shopify://collections/{handle}` | `/product-category/{handle}/` | WooCommerce product category |
| `/collections/all` | `/shop/` | Same |
| `/collections/{handle}` | `/product-category/{handle}/` | Same |
| `shopify://products/{handle}` | `/product/{handle}/` | WooCommerce product permalink |
| `/products/{handle}` | `/product/{handle}/` | Same |
| `/cart` | `wc_get_cart_url()` | Falls back to `/cart/` |
| `/account` | `wc_get_account_endpoint_url('dashboard')` | Falls back to `/my-account/` |
| `/account/login` | `wp_login_url()` | Same |
| `https://bybookishbabe.com/blogs/{blog}/{article}` | `/{blog}/{article}/` | Domain stripped |
| External URLs (other domains) | verbatim | Returned unchanged |
| Empty string | `/` | `home_url('/')` |

### Known Specific Mappings (Confirmed in Source Files)

| Shopify URL | WordPress URL | Source |
|---|---|---|
| `shopify://pages/library` | `/library/` | index.json hero CTA |
| `shopify://pages/sports-romance-books` | `/sports-romance-books/` | index.json trope block |
| `shopify://pages/enemies-to-lovers` | `/enemies-to-lovers/` | index.json trope block |
| `shopify://pages/slow-burn-books` | `/slow-burn-books/` | index.json trope block |
| `shopify://collections/all` | `/shop/` | index.json image-banner button |
| `shopify://blogs/curated-romance-guides/the-best-dark-romance-books-with-morally-gray-men-that-will-ruin-you` | `/curated-romance-guides/the-best-dark-romance-books-with-morally-gray-men-that-will-ruin-you/` | index.json trope block |
| `https://bybookishbabe.com/pages/smut-sentiment-society` | `/smut-sentiment-society/` | index.json society_hero section |
| `https://thesmutandsentimentsociety.substack.com/subscribe` | same (external) | index.json hero CTA + header.liquid |
| `/pages/my-vault` | `/my-vault/` | header.liquid hardcoded icon |
| `/pages/reading-list` | `/curated-romance-guides/` (301 redirect) | inc/redirects.php |
| `/pages/reader-quizes` | `/reader-quizes/` | page.for-readers.json |

---

## 4. Required WordPress Pages Checklist

Pages are grouped by creation method. All slugs are Shopify page handles.

### A — Must Exist as WordPress Pages (page post type)

| Page Title | Slug | Source | Template | Creation Method |
|---|---|---|---|---|
| Library | `library` | `shopify://pages/library` (hero CTA) | `page.library.json` → custom section | Create WP page + assign template |
| My Vault | `my-vault` | `/pages/my-vault` (header hardcoded) | Unknown — check Shopify | Create WP page |
| Smut & Sentiment Society | `smut-sentiment-society` | `https://bybookishbabe.com/pages/smut-sentiment-society` | `page.for-readers.json` links it | Create WP page |
| Sports Romance Books | `sports-romance-books` | `shopify://pages/sports-romance-books` | `page.trope.json` | Create WP page (trope template) |
| Enemies to Lovers | `enemies-to-lovers` | `shopify://pages/enemies-to-lovers` | `page.trope.json` | Create WP page (trope template) |
| Slow Burn Books | `slow-burn-books` | `shopify://pages/slow-burn-books` | `page.trope.json` | Create WP page (trope template) |
| For Readers | `for-readers` | `templates/page.for-readers.json` | Custom section | Create WP page |
| What to Read Next | `what-to-read-next` | `templates/page.what-to-read-next.json` | Custom section | Create WP page |
| Reader Quizzes | `reader-quizes` | `page.for-readers.json` links it | Unknown | Create WP page |
| Kindle Inserts | `kindle-inserts` | `templates/page.kindle-inserts.json` | Custom section | Create WP page |
| Art Prints | `art-prints` | `templates/page.artprints.json` | Custom section | Create WP page |
| Weekly Obsession | `weekly-obsession` | `templates/page.weekly-obsession.json` | Custom section | Create WP page |
| Privacy Policy | `privacy-policy` | `templates/page.privacy-policy.json` | WP built-in | Use WP privacy policy page |
| Contact | `contact` | `templates/page.contact.json` | Custom contact section | Create WP page |
| Bookish Templates | `bookish-templates` | `templates/page.bookish-templates.json` | Custom section | Create WP page |
| Customer Reviews | `customer-reviews` | `templates/page.customerreviews.json` | Custom section | Create WP page |

### B — Society-Gated Pages (slugs from `functions.php` `bbb_society_gate_check()`)

These slugs are gated by `bbb_reader_is_society()`. WordPress pages must exist with these **exact slugs** for the gate check to trigger.

| Slug | Notes |
|---|---|
| `society-library` | SSS member library — assign society gate |
| `sss-library-page` | Alternate library slug — assign society gate |
| `sss-private-shelf` | Private shelf — assign society gate |
| `sss-made-for-you` | Made-for-you page (`templates/page.sss-made-for-you.json`) |
| `sss-printable-kindle-inserts` | Printable inserts (`templates/page.sss-printable-kindle.json`) |
| `sss-canva-templates` | Canva templates |
| `sss-quote-wall` | Quote wall (`templates/page.sss-quote-wall.json`) |
| `sss-freebies` | Freebies (`templates/page.sss-freebies.json`) |

### C — Pages with Existing Redirects (do not create, handled by `inc/redirects.php`)

| Slug | Redirects To | Rule in |
|---|---|---|
| `reading-list` | `/curated-romance-guides/` | `inc/redirects.php` |

### D — Blog Archives (WordPress category or custom post type archive — NOT WP pages)

| Shopify Blog Handle | WordPress Equivalent | Notes |
|---|---|---|
| `curated-romance-guides` | Custom WP category/archive at `/curated-romance-guides/` | Posts go here via the standard blog CPT or a custom blog taxonomy |

### E — Other Shopify Pages Found in Templates (verify if needed in nav)

Templates exist for these page slugs — create WP pages only if they appear in the Shopify navigation export:

`sss-series`, `sss-series-page`, `spice`, `preview`, `newslettertemplate`, `digitalproductstemplate`, `quote-audit`, `shelf`

### F — Additional URLs Referenced in `functions.php`

| URL | WP Path | Source |
|---|---|---|
| Bookshelf | `/my-bookshelf/` | `BBBSiteData.BBBReaderAccount.bookshelfUrl` |
| My Account | WooCommerce `wc_get_page_permalink('myaccount')` → `/my-account/` | Standard WooCommerce |

---

## 5. Current WordPress Implementation — Gap Analysis

### Already Done ✅

| File | Status | Notes |
|---|---|---|
| `template-parts/header/header-dropdown-menu.php` | ✅ Complete | Faithful Shopify Dawn port with correct class names, `aria-current`, `header__active-menu-item`, all three levels |
| `template-parts/header/header-drawer.php` | ✅ Complete | Faithful port with `menu-drawer__menu-item--active`, `aria-current`, back-button, utility links |
| `inc/header-functions.php` | ✅ Complete | Tree builder, `bbb_menu_item_handle()`, `bbb_menu_item_is_current()`, `bbb_menu_item_child_active()` |
| `inc/bbb-helpers.php` | ✅ Has `bbb_resolve_page_url()` | Partial — slug-to-permalink lookup exists but Shopify URL resolver did not |
| `inc/redirects.php` | ✅ `/reading-list` → `/curated-romance-guides/` | One redirect already in place |
| `functions.php` | ✅ `register_nav_menus` | Registers `main-menu` and `footer-policies` locations |

### Gaps Filled by This Spec ✅ (now done)

| Gap | Fix |
|---|---|
| Menu invisible if no WP menu assigned | `inc/header-functions.php` patched: falls back to `bbb_get_fallback_menu_tree()` |
| No `bbb_resolve_shopify_url()` | Created `inc/linking.php` |
| No `bbb_page_url()` | Created in `inc/linking.php` (wrapper around DB lookup + home_url fallback) |
| No `bbb_get_fallback_main_menu()` | Created `inc/main-menu.php` |
| `linking.php` and `main-menu.php` not loaded | `functions.php` patched with `require_once` at top |

### Still Unresolved ⚠️

| Issue | Action Required |
|---|---|
| Actual Shopify main-menu items unknown | Export from Shopify Admin → Online Store → Navigation → Main menu |
| Fallback menu is a skeleton | Replace `bbb_get_fallback_main_menu()` contents with real items after export |
| `my-vault` page content unknown | Check if Shopify has a `/pages/my-vault` page template; create WP equivalent |
| SSS sub-menu items unclear | Verify in Shopify admin whether The Society top item has children |
| `reader-quizes` slug typo | The Shopify URL is `/pages/reader-quizes` (no 'z' in "quiz" but missing 'z' at end). Match Shopify exactly — do NOT "fix" the slug in WP or links will break. |

---

## 6. Files Modified / Created

### New Files

#### `inc/linking.php` (new)

Contains:
- `bbb_resolve_shopify_url( string $url ): string` — master resolver for any Shopify URL format
- `bbb_resolve_shopify_path( string $path ): string` — internal helper
- `bbb_page_url( string $slug ): string` — slug → WP permalink with DB fallback

#### `inc/main-menu.php` (new)

Contains:
- `bbb_get_fallback_main_menu(): array` — hardcoded skeleton menu (replace with Shopify export)
- `bbb_fallback_item_to_post( array $item, array $child_posts ): WP_Post` — converts array item to WP_Post-shaped object
- `bbb_get_fallback_menu_tree(): array` — full tree of WP_Post objects, compatible with existing template-parts

### Modified Files

#### `inc/header-functions.php` — patch to `bbb_get_header_menu_items()`

Old behavior: returned `array()` when no WP menu was assigned.
New behavior: falls back to `bbb_get_fallback_menu_tree()`.

```php
// BEFORE (returns empty — menu invisible)
if (!$items) {
    return array();
}

// AFTER (falls back to hardcoded)
if ( $items ) {
    // ... existing tree builder ...
    return $tree;
}
return function_exists( 'bbb_get_fallback_menu_tree' ) ? bbb_get_fallback_menu_tree() : array();
```

#### `functions.php` — two new require_once lines

Added before the existing `require_once get_theme_file_path('inc/header-functions.php')`:

```php
require_once get_theme_file_path('inc/linking.php');
require_once get_theme_file_path('inc/main-menu.php');
```

Load order matters: `linking.php` and `main-menu.php` must load before `header-functions.php` because `bbb_get_header_menu_items()` now calls `bbb_get_fallback_menu_tree()`.

### No Changes Needed

| File | Reason |
|---|---|
| `template-parts/header/header-dropdown-menu.php` | Already correct and Shopify-faithful |
| `template-parts/header/header-drawer.php` | Already correct and Shopify-faithful |
| `template-parts/header/header-search.php` | Not in scope |
| `template-parts/header/reader-bookshelf-access.php` | Not in scope |

---

## 7. Active Class Behavior Reference

These class names are preserved exactly from Shopify Dawn and are already implemented in both template-parts. Documented here for Codex reference.

### Desktop Dropdown (`header-dropdown-menu.php`)

| State | Class / Attribute | Applied To |
|---|---|---|
| Top-level item with active child | `header__active-menu-item` on inner `<span>` | `bbb_menu_item_child_active()` → `current-menu-ancestor` or `current-menu-parent` |
| Child/grandchild is current page | `list-menu__item--active` on `<a>` | `bbb_menu_item_is_current()` → `current-menu-item` |
| Current page (any level) | `aria-current="page"` on `<a>` | Same condition |
| Top-level item is current page | `header__active-menu-item` on inner `<span>` | `bbb_menu_item_is_current()` |

### Mobile Drawer (`header-drawer.php`)

| State | Class / Attribute | Applied To |
|---|---|---|
| Top-level has active child | `menu-drawer__menu-item--active` on `<summary>` | `bbb_menu_item_child_active()` |
| Child is current page | `menu-drawer__menu-item--active` on `<a>` | `bbb_menu_item_is_current()` |
| Current page (any level) | `aria-current="page"` on `<a>` | Same condition |

### DOM IDs (preserved from Shopify)

| Element | ID Pattern | Example |
|---|---|---|
| Desktop top-level `<details>` | `Details-HeaderMenu-{index}` | `Details-HeaderMenu-1` |
| Desktop top-level `<summary>` | `HeaderMenu-{handle}` | `HeaderMenu-library` |
| Desktop child `<a>` | `HeaderMenu-{parent-handle}-{child-handle}` | `HeaderMenu-library-browse-all` |
| Desktop submenu `<ul>` | `HeaderMenu-MenuList-{index}` | `HeaderMenu-MenuList-1` |
| Drawer container `<details>` | `Details-menu-drawer-container` | (single, no index) |
| Drawer top-level `<details>` | `Details-menu-drawer-menu-item-{index}` | `Details-menu-drawer-menu-item-1` |
| Drawer top-level `<summary>` | `HeaderDrawer-{handle}` | `HeaderDrawer-library` |
| Drawer submenu `<div>` | `link-{handle}` | `link-library` |
| Drawer child submenu `<div>` | `childlink-{handle}` | `childlink-browse-all` |

---

## 8. Codex Step-by-Step Instructions

### Step 1 — Load new files (already done)

`functions.php` already has:
```php
require_once get_theme_file_path('inc/linking.php');
require_once get_theme_file_path('inc/main-menu.php');
```
Verify these appear **before** the `inc/header-functions.php` require.

### Step 2 — Replace skeleton menu with Shopify export

1. Export "Main menu" from Shopify Admin → Online Store → Navigation.
2. Open `inc/main-menu.php`.
3. Replace the array returned by `bbb_get_fallback_main_menu()` with the real items, converting each Shopify URL using `bbb_resolve_shopify_url()` or `bbb_page_url()`.

Example of a replacement item:
```php
array(
    'title'    => 'Library',                        // exact label from Shopify admin
    'url'      => bbb_page_url( 'library' ),        // resolves via DB or home_url fallback
    'children' => array(
        array(
            'title'    => 'Sports Romance',
            'url'      => bbb_page_url( 'sports-romance-books' ),
            'children' => array(),
        ),
    ),
),
```

### Step 3 — Create required WordPress pages

Use the checklist in Section 4 to create all pages. Key slugs that **must match Shopify exactly**:

- `library`
- `my-vault`
- `smut-sentiment-society`
- `sports-romance-books`
- `enemies-to-lovers`
- `slow-burn-books`
- `for-readers`
- `what-to-read-next`
- `reader-quizes` ← note: keep this exact spelling (matches Shopify source)
- `kindle-inserts`

Society-gated page slugs from `functions.php` must also exist:
`society-library`, `sss-library-page`, `sss-private-shelf`, `sss-made-for-you`, `sss-printable-kindle-inserts`, `sss-canva-templates`, `sss-quote-wall`, `sss-freebies`

### Step 4 — Assign WP admin menu (optional, overrides fallback)

Once WP pages exist, go to Appearance → Menus in WP admin. Create a menu, add items, and assign it to the "Main Navigation" location (`main-menu`). The fallback in `inc/main-menu.php` is automatically skipped once a WP menu is assigned.

### Step 5 — Use bbb_resolve_shopify_url() for any new links

Any template, section, or widget that contains a Shopify URL should pass it through:
```php
$url = bbb_resolve_shopify_url( 'shopify://pages/library' );
// → https://yourdomain.com/library/
```

---

## 9. Unresolved Items

These require the Shopify admin navigation export before they can be resolved:

| Item | What's Needed |
|---|---|
| Exact top-level menu titles | Shopify Admin → Navigation → Main menu |
| Whether Library sub-items are in the menu | Same |
| Whether "Dark Romance" is a top-level item or only a trope card | Same |
| Full The Society sub-menu | Same |
| Any additional trope pages not mentioned in index.json | Same |
| `my-vault` page content | Shopify page content export or manual migration |
| SSS series pages | Check if they appear in navigation |
| Footer policy links | Shopify Admin → Navigation → "Footer" menu if separate from main-menu |
