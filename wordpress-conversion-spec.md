# Shopify → WordPress Conversion Spec
**Source:** `/wordpress-theme/` (ignoring `/firstpass/`)  
**Target:** WordPress with custom page templates, ACF Pro, and custom post types  
**Audience:** Codex (automated implementation agent)

---

## Reading Guide

Every entry follows this structure:

```
### WP Template: page-<slug>.php
Source template:  templates/page.<slug>.json
Primary section:  sections/<section-name>.liquid
Sections in order: (copied from template JSON "order" array)
Shared snippets:  (render calls inside the section)
CSS:              (asset_url stylesheet_tag calls)
JS:               (asset_url script_tag calls + external CDN)
Liquid variables: (all {{ ... }} and section.settings.* used)
WordPress replacement: (exact PHP/ACF/CPT equivalents)
Files Codex creates: (exact filenames)
Priority: P1 / P2 / P3
```

**Liquid → WordPress variable map (global):**

| Liquid | WordPress |
|--------|-----------|
| `page.title` | `get_the_title()` |
| `page.content` | `the_content()` / `get_the_content()` |
| `shop.metaobjects.sss_library.values` | `WP_Query` on CPT `sss_book` |
| `shop.metaobjects.sss_series.values` | `WP_Query` on CPT `sss_series` |
| `shop.metaobjects.sss_quote.values` | `WP_Query` on CPT `sss_quote` |
| `article` (blog post) | `WP_Post` (post_type `post` or custom) |
| `blog.articles` | `WP_Query` on `post` or CPT |
| `collection.products` | WooCommerce `WC_Product_Query` |
| `customer.orders` | `wc_get_orders()` |
| `section.settings.*` | ACF options page field or page meta field |
| `block.settings.*` | ACF repeater sub-field |
| `shopify://pages/<handle>` | `get_permalink( get_page_by_path('<handle>') )` |
| `shopify://products/<handle>` | `wc_get_product_id_by_sku()` or slug lookup |
| `shopify://collections/<handle>` | WooCommerce product category archive URL |

**Supabase:** The theme uses Supabase (`https://efmrfxsmgbeikfgtrxjv.supabase.co`) for bookshelf saves, read tracking, newsletter submissions, boyfriend votes, and member-gated data. In WordPress, the Supabase client (`@supabase/supabase-js@2`) loads from CDN and must be enqueued in `functions.php`. Anon key (safe for client): `eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...` (full JWT in source files). The secondary publishable key `sb_publishable_iwjASe3QwixdDvHovaXZBQ_gbXU0Utk` is used by newsletter-submissions.

**sss-folder-tabs section:** Appears on nearly every SSS member page. Renders a horizontal tab bar of icon+label+sub-label links. Implement as `inc/components/sss-folder-tabs.php` accepting an array of tab objects. URL mapping from `shopify://pages/<handle>` → `get_permalink()` resolved at render time.

---

## GROUP 1 — Generic Static Pages

**What belongs here:** Pages that render standard CMS content (WYSIWYG), a contact form, a redirect, or a simple branded section that doesn't pull from a data source. No metaobjects, no Supabase, no product queries.

**Priority:** P1 — port first to establish the base layout and shared includes.

---

### 1.1 Default / Generic Page

**Source template:** `templates/page.json`  
**Sections in order:** `main` (main-page), `rich_text_X6cXWA` (rich-text), `image_with_text_nRLUAF` (image-with-text), `collapsible_content_fCbEc3` (collapsible-content)

**Primary section DOM — `sections/main-page.liquid`:**
```html
<div class="page-width page-width--narrow section-{id}-padding">
  <h1 class="main-page-title page-title h0">{{ page.title }}</h1>
  <div class="rte">{{ page.content }}</div>
</div>
```

**CSS:** `section-main-page.css`  
**JS:** none  
**Liquid variables:** `page.title`, `page.content`, `section.settings.padding_top`, `section.settings.padding_bottom`, `settings.animations_reveal_on_scroll`

**Additional sections on this template:**
- `rich-text`: heading + body text + optional CTA button — hardcoded content in JSON
- `image-with-text`: `image: shopify://shop_images/aboutme2.png`, heading "Smut CEO", body text — all static
- `collapsible-content`: 4 accordion rows — all static text

**WordPress replacement:**
- `page.title` → `get_the_title()`
- `page.content` → `the_content()`
- `rich-text` blocks → ACF `flexible_content` field with a `rich_text` layout (heading, text, button_label, button_url)
- `image-with-text` → ACF `image_with_text` layout (image, heading, body, layout: image_first|image_second)
- `collapsible-content` → ACF `accordion` repeater (heading, content)

**Files Codex creates:**
- `page-default.php` — WordPress page template registering as "Default Page"
- `inc/sections/rich-text.php`
- `inc/sections/image-with-text.php`
- `inc/sections/collapsible-content.php`
- `assets/css/section-main-page.css` — copy verbatim from source

---

### 1.2 Our Story / About

**Source template:** `templates/page.our-story.json`  
**Sections in order:** `main` (disabled), `rich_text` (disabled), `image_with_text` (disabled), `collapsible_content` (disabled), `bbb_our_story_RJMFWg` (bbb-our-story)

Note: Only `bbb-our-story` renders; all others are `disabled: true`.

**Primary section:** `sections/bbb-our-story.liquid`

**Section settings:**
```
hero_kicker:              "why i started this"
hero_title:               "i wanted reading to feel as beautiful as it felt."
hero_image:               shopify://shop_images/IMG_0674.heic
shelf_kicker:             "my shelf, lately"
shelf_title:              "the society's classics shelf"
favorite_boyfriend_kicker:"favorite book boyfriend"
favorite_boyfriend_title: "the fictional man i would defend in a group chat"
favorite_boyfriend_image: shopify://shop_images/aaron_warner.jpg
favorite_boyfriend_book_handle: "shatter-me"
quiz_label:               "take the quiz →"
quiz_url:                 (url)
closing_kicker:           "thank you for being here"
closing_title:            "if you made it this far, stay a while."
society_label:            "join the smut and sentiment society →"
society_url:              (url)
accent:                   "#b91c1c"
```

**WordPress replacement:**
- All `section.settings.*` → ACF page fields under "Our Story"
- `hero_image` / `favorite_boyfriend_image` → ACF Image field (returns URL)
- `favorite_boyfriend_book_handle` → ACF Post Object → CPT `sss_book`
- Shelf → reuse `inc/sections/society-classics-shelf.php`
- `accent` → inline CSS custom property `--bbb-accent`

**Files Codex creates:**
- `page-our-story.php`
- `inc/sections/bbb-our-story.php`
- `acf-groups/our-story.json`

**Priority:** P1

---

### 1.3 Contact

**Source template:** `templates/page.contact.json`  
**Sections in order:** `main` (main-page, NOT disabled), `form` (contact-form), `sss_moodboard_playlist_zEJzbr` (sss-moodboard-playlist, no settings)

**Section settings (contact-form):** `heading: ""`, `heading_size: "h1"`, `color_scheme: "scheme-1"`, padding 36px top/bottom.

**WordPress replacement:**
- Contact form → WPForms or Contact Form 7 shortcode embedded in template
- `sss-moodboard-playlist` → static decorative section (Phase 2)

**Files Codex creates:**
- `page-contact.php`
- `inc/sections/contact-form.php`

**Priority:** P1

---

### 1.4 Privacy Policy

**Source template:** `templates/page.privacy-policy.json`  
**Sections:** `main` (main-page), standard WP page content

**WordPress replacement:** Use default `page-default.php`. Assign WordPress's built-in Privacy Policy page role.

**Files Codex creates:** No new file — reuses `page-default.php`

**Priority:** P1

---

### 1.5 Reading List (Redirect)

**Source template:** `templates/page.reading-list.json`  
**Sections:** `redirect` (custom-liquid)

**Liquid content (verbatim):**
```html
<meta http-equiv="refresh" content="0; url=/blogs/curated-romance-guides">
<script>window.location.replace("/blogs/curated-romance-guides");</script>
```

**WordPress replacement:**
```php
// inc/redirects.php
add_action('template_redirect', function() {
    if (is_page('reading-list')) {
        wp_redirect(get_permalink(get_page_by_path('curated-romance-guides')), 301);
        exit;
    }
});
```

**Files Codex creates:** Entry in `inc/redirects.php` included from `functions.php`

**Priority:** P1

---

### 1.6 For Readers Hub

**Source template:** `templates/page.for-readers.json`  
**Sections in order:** `main` (disabled), `for_readers_Cedtfg` (for-readers)

**Section settings:**
```
kicker:           "for readers"
title:            "if you're here, you probably love fictional men a little too much"
subtext:          "book recs, quizzes, and soft chaos for the romantically inclined."
reading_list_url: "/pages/reading-list"
quiz_url:         "/pages/reader-quizes"
```

**Full DOM — `sections/for-readers.liquid`:**
```html
<section class="sss-readers" id="sss-readers-{id}">

  <!-- Emoji rain (decorative, CSS animated) -->
  <div class="sss-readers__emojiRain" aria-hidden="true">
    <!-- multiple <span> elements with emoji characters, CSS keyframe animation -->
  </div>

  <!-- Header -->
  <div class="sss-readers__head">
    <p class="sss-readers__kicker">{{ section.settings.kicker }}</p>
    <h1 class="sss-readers__title">{{ section.settings.title }}</h1>
    <p class="sss-readers__sub">{{ section.settings.subtext }}</p>
  </div>

  <!-- Two-card grid -->
  <div class="sss-readers__grid">

    <!-- Card 1: Reading list -->
    <a class="sss-readers__card" href="{{ section.settings.reading_list_url }}">
      <div class="sss-readers__cardInner">
        <p class="sss-readers__cardKicker">the reading list</p>
        <h2 class="sss-readers__cardTitle">books worth your time</h2>
        <p class="sss-readers__cardSub">curated romance reads, sorted by vibe.</p>
        <span class="sss-readers__cardCta">browse the list →</span>
      </div>
    </a>

    <!-- Card 2: Quiz (pink accent) -->
    <a class="sss-readers__card sss-readers__card--pink" href="{{ section.settings.quiz_url }}">
      <div class="sss-readers__cardInner">
        <p class="sss-readers__cardKicker">the quiz</p>
        <h2 class="sss-readers__cardTitle">find your next read</h2>
        <p class="sss-readers__cardSub">answer five questions. get your perfect book.</p>
        <span class="sss-readers__cardCta">take the quiz →</span>
      </div>
    </a>

  </div>
</section>
```

**CSS:** Fully inline in section (bg `#0b0b0b`; emoji rain uses `@keyframes sssRain`). No external CSS file.  
**JS:** None.  
**Liquid variables:** `section.settings.kicker`, `section.settings.title`, `section.settings.subtext`, `section.settings.reading_list_url`, `section.settings.quiz_url`

**WordPress replacement:**
- All `section.settings.*` → ACF page fields
- Emoji rain → copy inline `<style>` verbatim into `assets/css/for-readers.css`
- Card 2 class `.sss-readers__card--pink` is hardcoded — do NOT rename
- URLs → `get_permalink(get_page_by_path('reading-list'))` / `get_permalink(get_page_by_path('reader-quizes'))`

**Files Codex creates:**
- `page-for-readers.php`
- `inc/sections/for-readers.php`
- `assets/css/for-readers.css` (inline styles extracted)

**Priority:** P2

---

### 1.7 Customer Reviews

**Source template:** `templates/page.customerreviews.json`  
**Sections:** Judge.me reviews app embed

**WordPress replacement:** Replace with WooCommerce reviews shortcode or a third-party plugin (Yotpo, Judge.me WP plugin). Flag for manual integration.

**Files Codex creates:** `page-customer-reviews.php` with a `[reviews]` shortcode placeholder

**Priority:** P3 (third-party dependency)

---

### 1.8 Media Kit

**Source template:** `templates/page.media-kit.json`  
**Sections in order:** `main` (disabled), `bbb_media_kit` (bbb-media-kit)

**Block types and their settings:**

| Block type | Fields |
|------------|--------|
| `hero_stat` | `value`, `label` |
| `fact` | `label`, `value` |
| `number` | `value`, `label`, `text` |
| `platform` | `title`, `handle`, `text`, `badge` |
| `content_card` | `icon` (01–06), `title`, `text` |
| `package` | `number`, `type_label`, `title`, `text`, `includes` (newline-delimited), `featured` (bool) |
| `audience` | `label`, `title`, `text`, `tags` (comma-delimited) |
| `partner` | `title`, `text` |

**Block order:** `hero_stat` ×4, `fact` ×6, `number` ×4, `platform` ×3, `content_card` ×6, `package` ×3, `audience` ×2, `partner` ×6

**WordPress replacement:**
- ACF `flexible_content` field `media_kit_blocks`; each block type = one layout
- `includes` newline text → `explode("\n", $value)` for bullet rendering
- `featured: true` → adds `.media-kit__package--featured` class

**Files Codex creates:**
- `page-media-kit.php`
- `inc/sections/bbb-media-kit.php`
- `acf-groups/media-kit.json`

**Priority:** P2

---

### 1.9 Newsletter Submissions / Society Submissions

**Source templates:** `templates/page.newsletter-submissions.json`, `templates/page.society-submissions.json`  
**Section:** `newsletter-submissions-page`

**Section settings:**
```
kicker:           "the smut and sentiment society"
heading:          "get featured in the sunday newsletter"
submission_types: "bookish hot take\nquote i can't stop thinking about\nbook recommendation\nunpopular opinion"
submit_label:     "submit to the sunday newsletter →"
table_name:       "newsletter_submissions"
supabase_url:     "https://efmrfxsmgbeikfgtrxjv.supabase.co"
supabase_key:     "sb_publishable_iwjASe3QwixdDvHovaXZBQ_gbXU0Utk"
```

**WordPress replacement:**
- Keep Supabase endpoint. Enqueue `@supabase/supabase-js@2` from CDN.
- Form submission JS: `supabaseClient.from(table_name).insert({...})`
- `submission_types` → `explode("\n", $value)` for radio option list
- Store `supabase_url` / `supabase_key` in `wp-config.php` as constants

**Files Codex creates:**
- `page-newsletter-submissions.php`
- `inc/sections/newsletter-submissions-page.php`
- `assets/js/newsletter-submissions.js`

**Priority:** P2

---

### 1.10 SSS Newsletter Landing / Marketing Page

**Source template:** `templates/page.newslettertemplate.json`  
**Sections in order:**
1. `sss_page_intro` (sss-page-intro)
2. `image_banner_1` (image-banner)
3. `image_banner_2` (image-banner)
4. `sss_about_paid_interactive` (sss-about-paid-interactive)
5. `sss_newsletter_sneak_peek` (sss-newsletter-sneak-peek)
6. `sss_join_nudge` (sss-join-nudge)
7. `sss_quote_single` (sss-quote-single)
8. `sss_library_sneak_peek` (sss-library-sneak-peek)

This is the public-facing conversion/marketing page for the SSS subscription. No `main-page` wrapper.

**Section purposes:**
- `sss-page-intro` — hero with kicker, title, subtext, CTA button (join link)
- `image-banner` ×2 — full-bleed mood images
- `sss-about-paid-interactive` — animated expandable description of what SSS members get
- `sss-newsletter-sneak-peek` — shows a partial preview of a past newsletter
- `sss-join-nudge` — sticky/bottom CTA strip with subscription price and join button
- `sss-quote-single` — one highlighted member quote/testimonial
- `sss-library-sneak-peek` — teaser grid of 3–4 blurred/locked books from the library

**CSS:** Each section has inline styles; no shared external CSS file listed. Extract each section's `<style>` block to `assets/css/sss-newsletter-landing.css`.  
**JS:** None beyond inline.  
**No Supabase.** This page is entirely static marketing content.

**WordPress replacement:**
- All `section.settings.*` → ACF flexible content field `newsletter_landing_sections`
- Each section type = one layout in flexible content
- `sss-library-sneak-peek` teaser books → query first 4 `sss_book` posts; apply CSS blur filter via `.sss-sneak__locked` class
- Join link → hardcoded Substack URL `https://thesmutandsentimentsociety.substack.com/subscribe` or ACF URL field

**Files Codex creates:**
- `page-newslettertemplate.php`
- `inc/sections/sss-page-intro.php`
- `inc/sections/sss-about-paid-interactive.php`
- `inc/sections/sss-newsletter-sneak-peek.php`
- `inc/sections/sss-join-nudge.php`
- `inc/sections/sss-quote-single.php`
- `inc/sections/sss-library-sneak-peek.php`
- `assets/css/sss-newsletter-landing.css`
- `acf-groups/newsletter-landing.json`

**Priority:** P2

---

## GROUP 2 — Library / Book Pages

**What belongs here:** Pages that display books, series, tropes, spice levels, or review content. Data sources: Shopify metaobjects (`sss_library`, `sss_series`, `sss_quote`), Shopify blog articles, and Supabase. In WordPress, these map to custom post types.

**Custom post types Codex must register:**

| CPT slug | Replaces | Key ACF fields |
|----------|----------|----------------|
| `sss_book` | `shop.metaobjects.sss_library` | title, author, cover (image), why_i_loved_it, tropes (taxonomy → `sss_trope`), spice_level (int 1–5), shelf (taxonomy → `sss_shelf`), amazon_link, bookshop_link, starter_pack (bool), hide_from_library (bool), is_private (bool), month_featured (date YYYY-MM), series (post object → sss_series), series_number (int), standalone (bool), boyfriend_name, boyfriend_type, tension_score, emotional_damage_score, darkness_level, yearning_level, on_kindle_unlimited (bool), reread_badge (bool), mini_note, newsletter_url |
| `sss_series` | `shop.metaobjects.sss_series` | name, handle, books (relationship → sss_book), featured (bool) |
| `sss_quote` | `shop.metaobjects.sss_quote` | quote_text, book (post object → sss_book), author |

**Taxonomies Codex must register:**

| Taxonomy | Replaces | Used by |
|----------|----------|---------|
| `sss_shelf` | metaobject shelf field values | sss_book |
| `sss_trope` | metaobject trope list | sss_book |
| `sss_spice` | spice_level buckets | sss_book |
| `book_review_category` | blog category filter | WP posts |

**Shared snippets:**

| Snippet | Liquid file | WordPress equivalent |
|---------|-------------|----------------------|
| `sss-book-card` | `snippets/sss-book-card.liquid` | `inc/components/sss-book-card.php` |
| `sss-book-visible` | `snippets/sss-book-visible.liquid` | `!get_field('hide_from_library', $id)` |
| `sss-book-private` | `snippets/sss-book-private.liquid` | `get_field('is_private', $id)` |
| `sss-society-classics` | `snippets/sss-society-classics.liquid` | `inc/components/society-classics.php` |
| `sss-trending-shelf` | `snippets/sss-trending-shelf.liquid` | `inc/components/trending-shelf.php` |
| `sss-books-of-month` | `snippets/sss-books-of-month.liquid` | `inc/components/books-of-month.php` |
| `sss-mood-shelves` | `snippets/sss-mood-shelves.liquid` | `inc/components/mood-shelves.php` |
| `sss-full-archive` | `snippets/sss-full-archive.liquid` | `inc/components/full-archive.php` |
| `sss-library-modal` | `snippets/sss-library-modal.liquid` | `inc/components/library-modal.php` |
| `sss-you-might-like` | `snippets/sss-you-might-like.liquid` | `inc/components/you-might-like.php` |

**Priority:** P1 (CPT registration + book-card component must come before any library page)

---

### 2.1 Public Romance Library

**Source template:** `templates/page.library.json`  
**Sections in order:** `main` (disabled), `public_library_page_9pGe78` (public-library-page)

**Primary section:** `sections/public-library-page.liquid`

**Section settings:**
```
kicker:        "official library"
title:         "the romance library"
subtext:       "the official collection of romance books curated and catalogued..."
show_ranker:   true
show_monthly:  true
show_classics: true
rec_kicker:    "reader chemistry"
rec_title:     "what to read next"
rec_pick_title: "daggermouth"
rec_result_title: "until i die"
rec_link:      (url)
```

**Block type:** `shelf` (×5)
1. "💌 society classics" — `category_key: "society classics"`
2. "for the morally gray lovers" — `category_key: "morally gray lovers"`
3. "🗡 for the romantasy lovers" — `category_key: "romantasy girls"`
4. "🔥 five chili nights" — `category_key: "five chili nights"`
5. "☠️ extra dark" — `category_key: "extra dark"`

**Full DOM structure:**
```html
<section class="sss-lib sss-lib--public" id="sss-lib-{id}" data-sss-lib="public">
  <div class="sss-lib__wrap">

    <!-- Header -->
    <header class="sss-lib__head">
      <p class="sss-lib__kicker">{{ section.settings.kicker }}</p>
      <h1 class="sss-lib__title">{{ section.settings.title }}</h1>
      <p class="sss-lib__seoLine">made for romance readers by a romance reader.</p>
      <p class="sss-lib__sub">{{ section.settings.subtext }}</p>
      <a class="sss-lib__kuLink" href="https://amzn.to/4uZ8Y3a" target="_blank">
        on a kindle kick? try kindle unlimited →
      </a>
    </header>

    <!-- Society invite card -->
    <div class="sss-lib__societyInviteCard">
      <div class="sss-lib__societyInviteKicker">the private layer</div>
      <div class="sss-lib__societyInviteTitle">join the society for the weekly recommendation</div>
      <a href="https://thesmutandsentimentsociety.substack.com/subscribe" class="sss-lib__societyInviteBtn">
        enter the society
      </a>
    </div>

    <!-- Trending shelf (snippet) -->
    {% render 'sss-trending-shelf', books: jump_books, include_private_books: false %}

    <!-- Jump navigation -->
    <nav class="sss-lib__jumpNav">
      <div class="sss-lib__jumpTitle">choose where to begin</div>
      <div class="sss-lib__jumpLinks">
        <a href="#sssMyShelfSection">📚 your bookshelf</a>
        <a href="#society-classics">👑 classics</a>
        <a href="/pages/series-reading-orders">🔗 series</a>
        <a href="#starter-pack">✨ start here</a>
        <a href="#monthly">📅 books of the month</a>
        <a href="#moods">🖤 trope shelves</a>
        <a href="#archive">🗂️ full library</a>
      </div>
    </nav>

    <!-- Spice tease link -->
    <a class="sss-lib__spiceTease" href="/pages/romance-books-by-spice-level">...</a>

    <!-- Rec demo widget -->
    <a href="{{ section.settings.rec_link }}" class="bbb-homeRecDemo bbb-homeRecDemo--library">
      <!-- animated two-book "you picked → read this next" card -->
      <div class="bbb-homeRecDemo__pick">
        <img src="{rec_pick_cover}" alt="{{ section.settings.rec_pick_title }}">
        <span>{{ section.settings.rec_pick_title }}</span>
      </div>
      <div class="bbb-homeRecDemo__result">
        <img src="{rec_result_cover}" alt="{{ section.settings.rec_result_title }}">
        <span>{{ section.settings.rec_result_title }}</span>
      </div>
    </a>

    <!-- My shelf (Supabase-backed, JS-rendered) -->
    <div class="sss-lib__myshelf" id="sssMyShelfSection">
      <div class="sss-lib__myshelfActions">
        <button id="sssExportNotes">copy list</button>
        <button id="sssEmailShelf">email to self</button>
      </div>
      <div class="sss-lib__grid" id="sssMyShelfGrid"></div>
    </div>

    <!-- Society classics snippet -->
    {% render 'sss-society-classics', books: all_books, include_private_books: false %}

    <!-- Starter pack -->
    <div id="starter-pack" class="sss-lib__starter">
      <div class="sss-lib__grid">
        {% for book in starter_books %}
          {% render 'sss-book-card', book: book %}
        {% endfor %}
      </div>
    </div>

    <!-- Books of month snippet -->
    {% render 'sss-books-of-month', books: all_books, include_private_books: false %}

    <!-- Mood shelves -->
    <div id="moods" class="sss-lib__moods">
      {% render 'sss-mood-shelves', books: all_books, include_private_books: false %}
    </div>

    <!-- Full archive -->
    {% render 'sss-full-archive', books: all_books %}

  </div>

  {% render 'sss-library-modal' %}
  <div id="sssNotepad" hidden>...</div>
  <div id="sssFloatingShare">...</div>
  <div id="sssBackToTop">...</div>
  <div id="sssTropePopup" class="sss-tropePopup" hidden>...</div>
</section>
```

**CSS:** `sss-library.css` (copy verbatim) + inline `<style>` scoped to `#sss-lib-{id}` for rec demo  
**JS:** `sss-library.js`, Supabase CDN `https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2`  
**Fonts:** `'Cormorant'` serif, `'Kaushan Script'` cursive

**WordPress replacement:**
```php
$all_books = new WP_Query([
  'post_type'      => 'sss_book',
  'posts_per_page' => -1,
  'orderby'        => 'title',
  'order'          => 'ASC',
  'meta_query'     => [
    ['key' => 'hide_from_library', 'value' => '1', 'compare' => '!='],
    ['key' => 'is_private',        'value' => '1', 'compare' => '!='],
  ],
]);

// Rec demo covers: look up by title
$pick_book   = get_posts(['post_type'=>'sss_book','title'=>$settings['rec_pick_title'],'numberposts'=>1]);
$result_book = get_posts(['post_type'=>'sss_book','title'=>$settings['rec_result_title'],'numberposts'=>1]);

// Shelf tabs: foreach block, query by term slug
$shelf_books = new WP_Query([
  'post_type' => 'sss_book',
  'tax_query' => [['taxonomy'=>'sss_shelf','field'=>'slug','terms'=>$block['category_key']]],
]);
```

**ACF fields (field group "Library Page Settings"):**
`kicker`, `title`, `subtext`, `show_ranker`, `show_monthly`, `show_classics`, `rec_kicker`, `rec_title`, `rec_pick_title`, `rec_result_title`, `rec_link`, `shelves` repeater → `shelf_title`, `shelf_description`, `shelf_category_key`

**Files Codex creates:**
- `page-library.php`
- `inc/sections/public-library-page.php`
- `inc/components/sss-book-card.php`
- `inc/components/society-classics.php`
- `inc/components/trending-shelf.php`
- `inc/components/books-of-month.php`
- `inc/components/mood-shelves.php`
- `inc/components/full-archive.php`
- `inc/components/library-modal.php`
- `assets/css/sss-library.css` (copy verbatim)
- `assets/js/sss-library.js` (port, replacing Liquid var injections with `wp_localize_script` data)
- `inc/cpt/sss-book.php` (CPT + taxonomy registration)
- `acf-groups/sss-book.json`

**Priority:** P1

---

### 2.2 Page Shelf (Generic Shelf View)

**Source template:** `templates/page.shelf.json`  
**Sections:** `main` → `page-shelf` only

**Primary section:** `sections/page-shelf.liquid`

**Key Liquid pattern — shelf resolved from page metafield:**
```liquid
{% assign shelf = page.metafields.custom.shelf.value %}
{% assign shelf_handle = shelf.system.handle %}
{% assign shelf_name   = shelf.name | downcase %}
```

**DOM structure:**
```html
<section class="sss-lib sss-lib--shelf" data-sss-lib="public">
  <div class="sss-lib__wrap">

    <!-- Header -->
    <header class="sss-tropeTop">
      <div class="sss-tropeTop__left">
        <p class="sss-lib__kicker">{{ shelf.emoji }} {{ shelf.name }}</p>
        <h1 class="sss-lib__title">{{ shelf.name }}</h1>
        <p class="sss-lib__sub">{{ shelf.description }}</p>
      </div>
    </header>

    <!-- Filtered book grid -->
    <div class="sss-lib__grid sss-lib__grid--browsePage" id="sssShelfGrid">
      {% for book in shelf_books %}
        {% render 'sss-book-card', book: book %}
      {% endfor %}
    </div>

    <!-- You might like (mobile instance) -->
    {% render 'sss-you-might-like', books: all_books, current_shelf: shelf_handle, placement: 'mobile' %}

    <!-- You might like (desktop instance) -->
    {% render 'sss-you-might-like', books: all_books, current_shelf: shelf_handle, placement: 'desktop' %}

  </div>

  <!-- Floating save toast -->
  <div id="sssSaveToast" class="sss-lib__saveToast" hidden>saved to your shelf</div>

  <!-- Float share button -->
  <div id="sssFloatingShare">...</div>

  {% render 'sss-library-modal' %}
</section>
```

**Filter logic:**
```liquid
{% for book in shop.metaobjects.sss_library.values %}
  {% assign book_shelf = book.shelf.value %}
  {% if book_shelf.system.handle == shelf_handle
     or book_shelf.name | downcase == shelf_name %}
    <!-- render book card -->
  {% endif %}
{% endfor %}
```

**CSS:** `sss-library.css` + `sss-you-might-like.css`  
**JS:** Supabase CDN + `sss-library.js` (deferred)

**WordPress replacement:**
- Shelf determined from page ACF field `shelf_term` (taxonomy term object for `sss_shelf`)
- `page.metafields.custom.shelf.value` → `get_field('shelf_term', $post->ID)` (ACF taxonomy field)
- Filter: `WP_Query` with `tax_query` matching `sss_shelf` term slug
- `sss-you-might-like` snippet → `inc/components/you-might-like.php` — renders books from same shelf excluding those already shown
```php
$shelf_term = get_field('shelf_term', get_the_ID()); // returns WP_Term
$shelf_books = new WP_Query([
  'post_type' => 'sss_book',
  'tax_query' => [['taxonomy'=>'sss_shelf','field'=>'term_id','terms'=>$shelf_term->term_id]],
  'meta_query' => [['key'=>'hide_from_library','value'=>'1','compare'=>'!=']],
  'posts_per_page' => -1,
]);
```

**Files Codex creates:**
- `page-shelf.php`
- `inc/sections/page-shelf.php`
- `inc/components/you-might-like.php`
- `assets/css/sss-you-might-like.css` (copy verbatim)

**Priority:** P2

---

### 2.3 Book Reviews Index

**Source template:** `templates/page.book-reviews.json`  
**Sections in order:** `main` (disabled), `book_reviews` (book-reviews-page)

**Section settings:**
```
blog:             "curated-romance-guides"
articles_per_page: 20
kicker:           "bybookishbabe book reviews"
heading:          "book reviews"
trending_kicker:  "latest book review"
trending_heading: "trending now"
archive_kicker:   "read, rated, recommended"
archive_heading:  "all book reviews"
```

**WordPress replacement:**
- `blog: "curated-romance-guides"` → WP posts in category `curated-romance-guides`
- `articles_per_page: 20` → `posts_per_page` in `WP_Query`
- "trending now" → most recent or `is_sticky` post

**Files Codex creates:**
- `page-book-reviews.php`
- `inc/sections/book-reviews-page.php`
- `inc/components/article-trope-book-card.php`

**Priority:** P1

---

### 2.4 Books Like Directory

**Source template:** `templates/page.books-like-directory.json`  
**Sections in order:** `main` (disabled), `directory` (books-like-directory-page)

**Section settings:** `kicker: "reading guides"`, `heading: "books like x"`, `subtext`, `footer_note`

**WordPress replacement:** `WP_Query` on posts in category `books-like`.

**Files Codex creates:**
- `page-books-like-directory.php`
- `inc/sections/books-like-directory-page.php`
- `inc/components/blog-book-card.php`

**Priority:** P2

---

### 2.5 Books Like (Individual Page)

**Source template:** `templates/page.books-like.json`  
**Sections in order:** `main` (disabled), `books_like` (books-like-page, no settings)

**WordPress replacement:** Single post template `single-books-like.php` or page template reading `get_the_content()` plus ACF fields: `source_book`, `rec_books` (repeater).

**Files Codex creates:** `page-books-like.php`, `inc/sections/books-like-page.php`

**Priority:** P2

---

### 2.6 Library by Trope

**Source template:** `templates/page.trope.json`  
**Sections:** `trope_page` → `library-trope-page` only (no `main-page`)

**WordPress replacement:** `taxonomy-sss_trope.php` (taxonomy archive) OR `page-trope.php` reading `?trope=` param.

**Files Codex creates:** `taxonomy-sss_trope.php`

**Priority:** P2

---

### 2.7 Library by Spice Level

**Source template:** `templates/page.spice.json`  
**Sections:** `main` → `page-spice` only (no `main-page` wrapper)

**Primary section:** `sections/page-spice.liquid`

**Full DOM structure:**
```html
<section class="sss-lib sss-lib--spicePage" id="sss-lib-{id}" data-sss-lib="public">
  <div class="sss-lib__wrap">

    <!-- Header -->
    <header class="sss-tropeTop">
      <div class="sss-tropeTop__left">
        <p class="sss-lib__kicker">browse by spice</p>
        <h1 class="sss-lib__title">romance books by spice level</h1>
      </div>
      <div class="sss-tropeTop__right">
        <!-- Society invite card (same as public library) -->
        <div class="sss-lib__societyInviteCard">...</div>
      </div>
    </header>

    <!-- Spice pill navigation -->
    <nav class="sss-spiceNav">
      <button class="sss-spiceNav__pill" data-spice-filter="1">🌶 barely there</button>
      <button class="sss-spiceNav__pill" data-spice-filter="2">🌶🌶 warming up</button>
      <button class="sss-spiceNav__pill" data-spice-filter="3">🌶🌶🌶 medium heat</button>
      <button class="sss-spiceNav__pill" data-spice-filter="4">🌶🌶🌶🌶 getting hot</button>
      <button class="sss-spiceNav__pill" data-spice-filter="5">🌶🌶🌶🌶🌶 five chili nights</button>
    </nav>

    <!-- Count display -->
    <p class="sss-lib__spiceCount">
      showing <span id="sssSpiceCount">0</span> books
    </p>

    <!-- Book grid (all books with spice_level > 0, filtered by JS) -->
    <div class="sss-lib__grid sss-lib__grid--spicePage" id="sssSpiceGrid">
      {% for book in all_books %}
        {% if book.spice_level.value > 0 %}
          <div class="sss-lib__card" data-spice="{{ book.spice_level.value }}">
            {% render 'sss-book-card', book: book %}
          </div>
        {% endif %}
      {% endfor %}
    </div>

    <!-- Action links -->
    <div class="sss-lib__spiceActions">
      <a href="/pages/romance-library">← back to full library</a>
      <a href="https://thesmutandsentimentsociety.substack.com/subscribe">join the society →</a>
    </div>

  </div>
  {% render 'sss-library-modal' %}
</section>
```

**Inline CSS (extract to `assets/css/page-spice.css`):**
```css
.sss-spiceNav { display: flex; gap: 8px; flex-wrap: wrap; margin: 24px 0; }
.sss-spiceNav__pill {
  border: 1px solid rgba(246,246,246,0.2);
  background: transparent;
  color: #f6f6f6;
  padding: 8px 16px;
  border-radius: 100px;
  cursor: pointer;
  transition: background 0.2s;
}
.sss-spiceNav__pill.is-active,
.sss-spiceNav__pill:hover { background: rgba(246,246,246,0.1); }
```

**JS pattern (extract to `assets/js/page-spice.js`):**
```js
// Reads ?spice= URL param; default 3
// applySpice(level): filters .sss-lib__card by data-spice attribute
// updates #sssSpiceCount
// updates active pill class
// sets URL with history.replaceState({ spice: level }, '', '?spice=' + level)

document.addEventListener('DOMContentLoaded', function() {
  const params = new URLSearchParams(window.location.search);
  const initial = parseInt(params.get('spice')) || 3;
  applySpice(initial);

  document.querySelectorAll('[data-spice-filter]').forEach(btn => {
    btn.addEventListener('click', function() {
      applySpice(parseInt(this.dataset.spiceFilter));
    });
  });

  function applySpice(level) {
    const cards = document.querySelectorAll('#sssSpiceGrid .sss-lib__card');
    let count = 0;
    cards.forEach(card => {
      const show = parseInt(card.dataset.spice) === level;
      card.hidden = !show;
      if (show) count++;
    });
    document.getElementById('sssSpiceCount').textContent = count;
    document.querySelectorAll('[data-spice-filter]').forEach(b =>
      b.classList.toggle('is-active', parseInt(b.dataset.spiceFilter) === level)
    );
    history.replaceState({ spice: level }, '', '?spice=' + level);
  }
});
```

**CSS:** `sss-library.css` + inline styles (extract to `page-spice.css`)  
**JS:** `sss-library.js` + inline script (extract to `page-spice.js`) + Supabase CDN

**WordPress replacement:**
- All books rendered server-side with `data-spice="{{ get_field('spice_level', $id) }}"` attribute
- `applySpice()` JS is verbatim — no PHP changes needed
- `?spice=` URL param handled entirely client-side
- Spice level stored in ACF `spice_level` (int) on `sss_book`

```php
$spice_books = new WP_Query([
  'post_type'      => 'sss_book',
  'posts_per_page' => -1,
  'meta_query'     => [
    ['key' => 'spice_level', 'value' => '0', 'compare' => '>'],
    ['key' => 'hide_from_library', 'value' => '1', 'compare' => '!='],
  ],
]);
```

**Files Codex creates:**
- `page-spice.php`
- `inc/sections/page-spice.php`
- `assets/css/page-spice.css`
- `assets/js/page-spice.js`

**Priority:** P2

---

### 2.8 Series Reading Orders

**Source template:** `templates/page.series-reading-orders.json`  
**Section settings:**
```
kicker:                 "reading guides"
heading:                "series reading orders"
featured_series_handle: ""
series_page_path:       "/pages/series"
```

**WordPress replacement:**
```php
$all_series = new WP_Query([
  'post_type'      => 'sss_series',
  'posts_per_page' => -1,
  'orderby'        => 'title',
]);
```

**Files Codex creates:** `page-series-reading-orders.php`, `inc/sections/series-reading-orders-page.php`, `inc/cpt/sss-series.php`, `acf-groups/sss-series.json`

**Priority:** P2

---

### 2.9 Individual Series Page

**Source template:** `templates/page.sss-series-page.json`  
**Sections:** `main` → `sss-series-page` only

**WordPress replacement:** `single-sss_series.php`

**Files Codex creates:** `single-sss_series.php`, `inc/sections/sss-series-page.php`

**Priority:** P2

---

### 2.10 SSS Series Listing

**Source template:** `templates/page.sss-series.json`  
**Sections:** `main` only (main-page — empty/placeholder page)

**WordPress replacement:** Reuse `page-default.php`.

**Priority:** P3

---

### 2.11 Weekly Obsession Dossier

**Source template:** `templates/page.weekly-obsession.json`  
**Section settings:** `kicker: "weekly obsession"`, `fallback_title: "this week's obsession"`, `related_limit: 3`

**WordPress replacement:** Query most recent post in category `weekly-obsession`. Fallback title if none.

**Files Codex creates:** `page-weekly-obsession.php`, `inc/sections/weekly-obsession-dossier.php`

**Priority:** P2

---

### 2.12 Customer Bookshelf

**Source template:** `templates/page.my-bookshelf.json`  
**Sections:** `customer_bookshelf_page` → `customer-bookshelf-page` only

**WordPress replacement:**
- `is_user_logged_in()` check — redirect if not
- JS fetches from Supabase using `wp_get_current_user()->user_email`
- Renders cards into `#sssMyShelfGrid`

**Files Codex creates:** `page-my-bookshelf.php`, `inc/sections/customer-bookshelf-page.php`, `assets/js/customer-bookshelf.js`

**Priority:** P2

---

### 2.13 Bookshelf Weekly Preview (Admin Email Tool)

**Source template:** `templates/page.bookshelf-weekly-preview.json`  
**Section:** `bookshelf-weekly-preview`

**Section settings:** none. Root element `[data-bbb-weekly-preview]` with `data-supabase-url` and `data-supabase-key` attributes injected at render time.

**Full Supabase RPC pattern:**
```js
// On page load, calls POST /rest/v1/rpc/get_latest_bookshelf_weekly_preview
const { data, error } = await supabase.rpc('get_latest_bookshelf_weekly_preview');
```

**Response shape:**
```json
{
  "status": "approved|draft",
  "week_of": "2025-06-01",
  "scheduled_send_at": "2025-06-04T09:00:00Z",
  "approved_by": "autumn",
  "notes": "...",
  "free_sample": { ...EmailSample },
  "paid_sample": { ...EmailSample },
  "empty_shelf_sample": { ...EmailSample }
}
```

**EmailSample shape:**
```json
{
  "subject_line": "...",
  "preview_text": "...",
  "intro_copy": "...",
  "latest_books": [{ "title": "...", "author": "...", "cover_url": "...", "link": "..." }],
  "recommended_book": { "title": "...", "author": "...", "reason": "...", "url": "..." },
  "support_link": { "title": "...", "description": "...", "url": "...", "links": [] },
  "newsletter_sneak_peek": { "title": "...", "body": "..." }
}
```

**DOM structure:**
```html
<div data-bbb-weekly-preview
     data-supabase-url="https://efmrfxsmgbeikfgtrxjv.supabase.co"
     data-supabase-key="{{ supabase_anon_key }}">

  <!-- Three email preview cards (one per sample type) -->
  <div class="bbb-weekly__cards">
    <article class="bbb-weekly__card" data-sample="free">
      <h3>Free subscriber version</h3>
      <div class="bbb-weekly__subjectLine"><!-- JS-populated --></div>
      <div class="bbb-weekly__previewText"><!-- JS-populated --></div>
      <button class="bbb-weekly__copySubject" data-target="free-subject">copy subject</button>
    </article>
    <article class="bbb-weekly__card" data-sample="paid">
      <h3>Paid subscriber version</h3>
      <!-- same structure -->
    </article>
    <article class="bbb-weekly__card" data-sample="empty">
      <h3>Empty shelf fallback</h3>
      <!-- same structure -->
    </article>
  </div>

  <!-- Full HTML email builder output -->
  <div class="bbb-weekly__emailBuilder">
    <textarea id="bbbEmailHtml" readonly><!-- buildEmailHtml() output --></textarea>
    <button id="bbbCopyHtml">copy full email HTML</button>
    <button id="bbbCopyPreviewText">copy preview text</button>
  </div>

  <!-- Status strip -->
  <div class="bbb-weekly__status">
    Status: <span data-weekly-status></span> |
    Week of: <span data-weekly-date></span> |
    Approved by: <span data-weekly-approver></span>
  </div>
</div>
```

**CSS:** Fully inline (bg `#060606`). Extract to `assets/css/bookshelf-weekly-preview.css`.  
**JS:** `loadPreview()` async function calls Supabase RPC, then calls `buildEmailHtml()` to construct full email HTML string. Copy buttons use `navigator.clipboard.writeText()`.

**WordPress replacement:**
- Admin-only page: `if (!current_user_can('manage_options')) { wp_redirect(home_url()); exit; }`
- `data-supabase-url` and `data-supabase-key` → `wp_localize_script` constants
- Supabase RPC call is unchanged — this is a read-only admin tool
- `buildEmailHtml()` function → copy verbatim from Liquid section source

**Files Codex creates:**
- `page-bookshelf-weekly-preview.php`
- `inc/sections/bookshelf-weekly-preview.php`
- `assets/css/bookshelf-weekly-preview.css`
- `assets/js/bookshelf-weekly-preview.js` (loadPreview + buildEmailHtml)

**Priority:** P3 (admin tool)

---

## GROUP 3 — Quiz Pages

**What belongs here:** Fully self-contained interactive JS experiences. No metaobject queries during render (data embedded at build time or fetched client-side). The Liquid sections contain inline `<style>` and `<script>` blocks with all quiz logic.

**Shared approach:**
- WordPress template wraps section in site layout
- Copy inline `<style>` to `assets/css/<quiz-name>.css`
- Copy inline `<script>` to `assets/js/<quiz-name>.js`
- Enqueue both conditionally: `is_page_template('page-<slug>.php')`

**Priority:** P2 for all quizzes

---

### 3.1 Fictional Boyfriend Quiz

**Source template:** `templates/page.fictional-boyfriend-quiz.json`  
**Sections in order:** `main` (disabled), `fictional_boyfriend_quiz` (fictional-boyfriend-quiz)

**DOM:**
```html
<section class="fbb-quiz">
  <div class="fbb-quiz__wrap">
    <div class="fbb-quiz__slide" data-slide="1">
      <p class="fbb-quiz__question">...</p>
      <div class="fbb-quiz__options">
        <button class="fbb-quiz__option" data-value="...">...</button>
      </div>
    </div>
    <!-- × 5 slides -->
    <div class="fbb-quiz__result" hidden>
      <h2 class="fbb-quiz__resultTitle">your fictional boyfriend is...</h2>
      <div class="fbb-quiz__resultCard">
        <img class="fbb-quiz__resultCover" src="..." alt="...">
        <div class="fbb-quiz__resultInfo">
          <p class="fbb-quiz__resultName">...</p>
          <p class="fbb-quiz__resultBook">...</p>
          <p class="fbb-quiz__resultDesc">...</p>
        </div>
      </div>
      <button class="fbb-quiz__retake">retake the quiz</button>
    </div>
  </div>
</section>
```

No Liquid variables — all content hardcoded.

**Files Codex creates:**
- `page-fictional-boyfriend-quiz.php`
- `inc/sections/fictional-boyfriend-quiz.php`
- `assets/css/fictional-boyfriend-quiz.css`
- `assets/js/fictional-boyfriend-quiz.js`

---

### 3.2 Reader Mood Quiz

**Source template:** `templates/page.reader-mood-quiz.json`  
**Sections in order:** `main` (disabled), `reader_mood_quiz` (reader-mood-quiz)

**DOM:**
```html
<section class="reader-mood-quiz">
  <div class="rmq__wrap">
    <div class="rmq__slide" data-slide="1">...</div>
    <!-- × 5 slides -->
    <div class="rmq__result" hidden>
      <p class="rmq__resultMood">...</p>
      <div class="rmq__resultBooks"><!-- 2–3 book cards --></div>
    </div>
  </div>
</section>
```

No Liquid variables — all content hardcoded.

**Files Codex creates:**
- `page-reader-mood-quiz.php`
- `inc/sections/reader-mood-quiz.php`
- `assets/css/reader-mood-quiz.css`
- `assets/js/reader-mood-quiz.js`

---

### 3.3 Reader Quizzes Dashboard

**Source template:** `templates/page.reader-quizes.json`  
**Sections in order:** `main` (disabled), `reader_quizzes_dashboard` (reader-quizzes-dashboard)

**DOM:**
```html
<section class="reader-quizzes">
  <div class="rqd__grid">
    <article class="rqd__card">
      <h3 class="rqd__cardTitle">fictional boyfriend quiz</h3>
      <p class="rqd__cardSub">...</p>
      <a class="rqd__cardBtn" href="/pages/fictional-boyfriend-quiz">take the quiz →</a>
    </article>
    <!-- × N quiz cards -->
  </div>
</section>
```

No dynamic Liquid variables. Card URLs → `get_permalink(get_page_by_path('<quiz-slug>'))`.

**Files Codex creates:**
- `page-reader-quizzes.php`
- `inc/sections/reader-quizzes-dashboard.php`

---

### 3.4 What to Read Next (Recommendation Engine)

**Source template:** `templates/page.what-to-read-next.json`

**Section settings:**
```
kicker:           "reader rec engine"
title:            "what to read next"
panel_label:      "start with a book you already loved"
note_one_pill:    "closest match"
note_one_title:   "same shelf + shared tropes"
note_two_pill:    "trope-first"
note_two_title:   "two tropes still hitting"
note_three_pill:  "mood read"
note_three_title: "one trope + same spice energy"
results_title:    "here's where I'd send you next"
card_one_pill:    "same shelf + 2 tropes"
card_two_pill:    "2 trope match"
card_three_pill:  "1 trope + spice mood"
accent_color:     "#a4303b"
```

**Algorithm (JS, client-side):**
1. User selects source book from `<select>` populated with all `sss_book` titles
2. Rec #1: same `sss_shelf` + highest trope overlap (≥2 matching tropes)
3. Rec #2: any shelf + 2 matching tropes
4. Rec #3: 1 matching trope + same `spice_level` bucket

**WordPress replacement:**
- All `section.settings.*` → ACF page fields
- Book dropdown: populated via `/wp-json/bbb/v1/books` REST endpoint
- `accent_color` → inline `--bbb-accent` CSS custom property

**Files Codex creates:**
- `page-what-to-read-next.php`
- `inc/sections/bbb-what-to-read-next.php`
- `assets/js/bbb-what-to-read-next.js`
- `inc/api/books-endpoint.php`
- `acf-groups/what-to-read-next.json`

**Priority:** P2

---

## GROUP 4 — Shop / Product-Adjacent Pages

**What belongs here:** Pages displaying digital/physical products or a shop hub. WooCommerce replaces all Shopify collections and products.

**WooCommerce equivalents:**

| Shopify | WooCommerce |
|---------|-------------|
| `collection.<handle>` | product category slug |
| `collection.products` | `WC_Product_Query` with `category` |
| `product.handle` | `$product->get_slug()` |
| `product.title` | `$product->get_name()` |
| `product.price` | `$product->get_price_html()` |
| `product.featured_image` | `get_the_post_thumbnail_url($id)` |
| `product.url` | `get_permalink($id)` |

**Priority:** P1 for shop hub, P2 for detail pages.

---

### 4.1 Shop Hub

**Source template:** `templates/page.shop.json`  
**Section:** `bbb-shop-hub`

**Section settings:**
```
kicker:                   "digital shop"
title:                    "curate your bybookishbabe vault"
primary_cta_label:        "unlock for $27"
my_vault_url:             shopify://pages/my-vault
insert_vault_url:         shopify://pages/my-kindle-inserts
insert_buy_url:           shopify://products/printable-kindle-insert-vault
printable_inserts_collection: "printable-kindle-inserts"
templates_collection:     "bookish-templates"
inserts_limit:            4
templates_limit:          4
```

**WordPress replacement:**
```php
$inserts = wc_get_products(['category'=>['printable-kindle-inserts'],'limit'=>4,'status'=>'publish']);
$templates = wc_get_products(['category'=>['bookish-templates'],'limit'=>4,'status'=>'publish']);
$my_vault_url = get_permalink(get_page_by_path('my-vault'));
$insert_buy_url = get_permalink(wc_get_product_id_by_slug('printable-kindle-insert-vault'));
```

**Files Codex creates:** `page-shop.php`, `inc/sections/bbb-shop-hub.php`, `acf-groups/shop-hub.json`

---

### 4.2 Art Prints

**Source template:** `templates/page.artprints.json`  
**Section settings:** `collection: "art-prints"`, `products_to_show: 25`, `columns_desktop: 4`

**WordPress replacement:** `wc_get_products(['category'=>['art-prints'],'limit'=>25])`

**Files Codex creates:** `page-artprints.php`, `inc/sections/featured-collection.php`

---

### 4.3 Kindle Inserts Shop

**Source template:** `templates/page.kindle-inserts.json`  
**Sections in order:** `main` (disabled), `inserts-header`, `ki-style-shelf`, `pair-and-save` (disabled)

**inserts-header settings:**
```
meta_line:        "kindle inserts — vol. i"
printable_link:   shopify://collections/printable-kindle-inserts
physical_link:    shopify://collections/kindle-inserts
```

**ki-style-shelf blocks (type: `style`):**
```
style #1: collection="smutty",       pill_label="smutty"
style #2: collection="dark-romance", pill_label="dark romance"
style #3: collection="soft-romance", pill_label="soft romance"
style #4: collection="aesthetic",    pill_label="aesthetic"
```

**DOM — ki-style-shelf:**
```html
<div class="ki-shelf">
  <div class="ki-shelf__pills">
    {% for block in section.blocks %}
      <button class="ki-shelf__pill" data-collection="{{ block.settings.collection }}">
        {{ block.settings.pill_label }}
      </button>
    {% endfor %}
  </div>
  <div class="ki-shelf__grid" data-active-collection="smutty">
    <!-- Products per collection, shown/hidden by JS -->
  </div>
</div>
```

**WordPress replacement:** Each `collection` → WooCommerce product category. Pills filter by `data-collection` via JS.

**Files Codex creates:**
- `page-kindle-inserts.php`
- `inc/sections/inserts-header.php`
- `inc/sections/ki-style-shelf.php`
- `assets/js/ki-style-shelf.js`

---

### 4.4 Kindle Insert Vault

**Source template:** `templates/page.kindle-insert-vault.json`  
**Section settings:** `buy_vault_url: shopify://products/printable-kindle-insert-vault`, `public_preview_mode: true`

**WordPress replacement:**
- Purchase gate: `wc_customer_bought_product()` check
- `public_preview_mode: true` → show first N freely, gate the rest
- `buy_vault_url` → `get_permalink(wc_get_product_id_by_slug('printable-kindle-insert-vault'))`

**Files Codex creates:** `page-kindle-insert-vault.php`, `inc/sections/bbb-kindle-insert-vault.php`

---

### 4.5 Digital Products Template

**Source template:** `templates/page.digitalproductstemplate.json`  
**Section settings:** `collection-list` block with `collection: "printable-kindle-inserts"`

**WordPress replacement:** Render WooCommerce product categories as collection cards.

**Files Codex creates:** `page-digital-products.php`, `inc/sections/collection-list.php`

---

### 4.6 Bookish Templates

**Source template:** `templates/page.bookish-templates.json`  
**Section settings:**
```
accent:     "#b91c1c"
collection: "bookish-templates"
limit:      6
show_price: true
```

**WordPress replacement:** `wc_get_products(['category'=>['bookish-templates'],'limit'=>6])`

**Files Codex creates:** `page-bookish-templates.php`, `inc/sections/bbb-bookish-templates.php`, `acf-groups/bookish-templates.json`

---

### 4.7 My Vault (Purchased Digital Products)

**Source template:** `templates/page.my-vault.json`

**WordPress replacement:**
- `is_user_logged_in()` → redirect if not
- `wc_customer_bought_product(email, id, product_id)` to show unlock vs. open vault CTA

**Files Codex creates:** `page-my-vault.php`, `inc/sections/bbb-my-vault.php`

---

### 4.8 SSS Printable Kindle (Member Inserts)

**Source template:** `templates/page.sss-printable-kindle.json`  
**Sections in order:**
1. `main` (disabled)
2. `sss_folder_tabs_*` (sss-folder-tabs — 7 tabs)
3. `sss_printable_library_*` (sss-printable-library)

**sss-printable-library settings:**
```
open_first_drop: false
```

**Section purpose:** Member-gated version of the inserts vault. Shows Dropbox-linked PDF files grouped as "drops" (monthly batches). `open_first_drop: false` means no accordion is pre-opened.

**WordPress replacement:**
- SSS member gate: `bbb_is_sss_member()` → redirect if not
- Each "drop" is an ACF repeater entry with: `drop_label` (text), `drop_date` (date), `files` (sub-repeater) → each file: `file_label`, `dropbox_url`, `file_type`
- Open/collapse behavior: vanilla JS accordion, class `.is-open` on active drop

**Files Codex creates:**
- `page-sss-printable-kindle.php`
- `inc/sections/sss-printable-library.php`
- `acf-groups/sss-printable-library.json`

**Priority:** P2

---

### 4.9 SSS Freebies

**Source template:** `templates/page.sss-freebies.json`  
**Sections in order:**
1. `main` (disabled)
2. `sss_folder_tabs_*` (sss-folder-tabs — 7 tabs)
3. `sss_freebies_*` (sss-freebies)

**sss-freebies section blocks (type: `freebie`, ×2 defined):**
```
block #1:
  title:       (freebie name)
  description: (short text)
  file_url:    https://www.dropbox.com/... (direct Dropbox download link)
  category:    "printable_distractions"

block #2:
  title:       (freebie name)
  file_url:    https://www.dropbox.com/...
  category:    "phone_screen_aesthetics"
```

**5 category values used across blocks:**
- `printable_distractions`
- `phone_screen_aesthetics`
- `reader_life_essentials`
- `cozy_creative`
- `seasonal_limited`

**DOM:**
```html
<section class="sss-lib sss-lib--freebies" data-sss-lib="society">
  <div class="sss-lib__wrap">

    <!-- Category filter pills -->
    <div class="sss-freebies__categories">
      <button class="sss-freebies__catPill is-active" data-category="all">all</button>
      <button class="sss-freebies__catPill" data-category="printable_distractions">printable distractions</button>
      <button class="sss-freebies__catPill" data-category="phone_screen_aesthetics">phone & screen aesthetics</button>
      <button class="sss-freebies__catPill" data-category="reader_life_essentials">reader life essentials</button>
      <button class="sss-freebies__catPill" data-category="cozy_creative">cozy + creative</button>
      <button class="sss-freebies__catPill" data-category="seasonal_limited">seasonal / limited</button>
    </div>

    <!-- Freebie grid -->
    <div class="sss-freebies__grid">
      {% for block in section.blocks %}
        <div class="sss-freebies__item" data-category="{{ block.settings.category }}">
          <h3 class="sss-freebies__title">{{ block.settings.title }}</h3>
          <p class="sss-freebies__desc">{{ block.settings.description }}</p>
          <a class="sss-freebies__download"
             href="{{ block.settings.file_url }}"
             target="_blank"
             download>
            download →
          </a>
        </div>
      {% endfor %}
    </div>

  </div>
</section>
```

**WordPress replacement:**
- SSS member gate: `bbb_is_sss_member()` → redirect if not
- Each freebie block → ACF repeater `freebies` with sub-fields: `title`, `description`, `file_url` (url), `category` (select)
- Category filter pills → JS filters `.sss-freebies__item` by `data-category`
- `file_url` Dropbox links → keep as-is (external links)

**Files Codex creates:**
- `page-sss-freebies.php`
- `inc/sections/sss-freebies.php`
- `acf-groups/sss-freebies.json`
- `assets/js/sss-freebies.js` (category filter)

**Priority:** P2

---

## GROUP 5 — Society / Member Pages

**What belongs here:** All pages inside the Smut & Sentiment Society member portal. Gated by SSS membership. Share the `sss-folder-tabs` navigation and dark theme (`background: #0b0b0b; color: #f6f6f6`).

**Membership gate:**
```php
// inc/bbb-helpers.php
function bbb_is_sss_member(): bool {
  if (!is_user_logged_in()) return false;
  $user = wp_get_current_user();
  return in_array('sss_member', $user->roles)
      || wc_memberships_is_user_active_member(get_current_user_id(), 'smut-sentiment-society');
}
// In templates: if (!bbb_is_sss_member()) { wp_redirect('/join'); exit; }
```

**sss-folder-tabs tab map:**

| Tab label | icon | Target page slug |
|-----------|------|-----------------|
| main page | 📁 | society-library |
| library | 📚 | sss-library-page |
| made for you | ✨ | sss-made-for-you |
| printable inserts | 🖨️ | sss-printable-kindle-inserts |
| bookish templates | ⌨️ | sss-canva-templates |
| free extras | 🎁 | sss-freebies |
| quote library | " | sss-quote-wall |
| private shelf | 🔓 | sss-private-shelf |

**CSS:** `sss-folder-tabs.css` — copy verbatim from source  
**Dark theme:** All SSS pages: `background: #0b0b0b; color: #f6f6f6`

---

### 5.1 Society Main Dashboard

**Source template:** `templates/page.societylibrary.json`  
**Sections in order:** `main` (disabled), `society_member_dashboard` (custom-liquid), `sss_folder_tabs_mainPage`, `sss_made_for_you_mainPage`

**Verbatim DOM from custom-liquid block:**
```html
<section class="sss-memberdash">
  <div class="sss-memberdash__wrap">
    <div class="sss-memberdash__intro">
      <div class="sss-memberdash__sparkles" aria-hidden="true">
        <span>✦</span><!-- × 12 -->
      </div>
      <p class="sss-memberdash__kicker">classified for members</p>
      <h1 class="sss-memberdash__title">welcome back to the society.</h1>
      <p class="sss-memberdash__sub">your recommendations, your books, your monthly drop.</p>
    </div>
    <div class="sss-memberdash__grid">
      <article class="sss-memberdash__card">
        <p class="sss-memberdash__cardKicker">start here</p>
        <h2 class="sss-memberdash__cardTitle">step 1. favorite books first</h2>
        <div class="sss-memberdash__stepLine">
          <p>favorite the books you love in the library.</p>
          <a href="/pages/sss-library-page" class="sss-memberdash__btn sss-memberdash__btn--ghost">
            favorite books in the library
          </a>
        </div>
        <div class="sss-memberdash__stepLine">
          <p>step 2. open made for you...</p>
          <a href="/pages/made-for-you" class="sss-memberdash__btn">open made for you</a>
        </div>
      </article>
      <article class="sss-memberdash__card">
        <p class="sss-memberdash__cardKicker">this month inside the society</p>
        <h2 class="sss-memberdash__cardTitle">the current obsession, all in one place</h2>
        <div class="sss-memberdash__stackLinks">
          <button data-memberdash-target="moodboard">current drop atmosphere</button>
          <button data-memberdash-target="ritual">member ritual for the month</button>
          <button data-memberdash-target="reset">monthly reset vibes</button>
        </div>
      </article>
      <article class="sss-memberdash__card">
        <p class="sss-memberdash__cardKicker">member archive</p>
        <h2 class="sss-memberdash__cardTitle">for kindle, pinterest, and bookish printable lovers</h2>
        <div class="sss-memberdash__stackLinks">
          <a href="/pages/sss-printable-kindle-inserts">open inserts</a>
          <a href="/pages/sss-canva-templates">open templates</a>
          <a href="/pages/sss-quote-wall">visit the quote library</a>
        </div>
      </article>
    </div>
  </div>
</section>
<script>
// data-memberdash-target scroll targets:
// moodboard → #sss-moodboard   (stable WP ID replacing shopify section ID)
// ritual    → #sss-cipher
// reset     → #sss-journal
document.querySelectorAll('[data-memberdash-target]').forEach(btn => {
  btn.addEventListener('click', function() {
    const map = { moodboard: 'sss-moodboard', ritual: 'sss-cipher', reset: 'sss-journal' };
    const el = document.getElementById(map[this.dataset.memberdashTarget]);
    if (el) el.scrollIntoView({ behavior: 'smooth' });
  });
});
</script>
```

**Note:** In WordPress, replace Shopify dynamic section IDs (e.g., `[id*="__sss_moodboard_playlist_c4GjcH"]`) with stable IDs (`#sss-moodboard`, `#sss-cipher`, `#sss-journal`). Update the scroll-target JS accordingly.

**Files Codex creates:**
- `page-societylibrary.php`
- `inc/sections/sss-member-dashboard.php`
- `inc/components/sss-folder-tabs.php`
- `assets/css/sss-memberdash.css`
- `assets/css/sss-folder-tabs.css`

---

### 5.2 SSS Monthly Staging (Full Monthly Drop)

**Source templates:** `templates/page.ssslibrary.json` and `templates/page.sss-monthly-staging.json` (identical — one live, one staging)

**Sections in order:**
1. `main` (disabled)
2. `society_member_dashboard` (custom-liquid — `sss-member-dashboard-core` snippet)
3. `sss_folder_tabs` (sss-folder-tabs — 8 tabs)
4. `society_monthly_intro` (custom-liquid — inline HTML)
5. `sss_auto_mood_pills` (sss-auto-mood-pills)
6. `sss_moodboard_playlist` (sss-moodboard-playlist) → assign `id="sss-moodboard"` in WP
7. `sss_society_journal` (sss-society-journal) → assign `id="sss-journal"` in WP
8. `sss_picker` (**disabled**)
9. `sss_auto_exclusive` (sss-auto-exclusive)
10. `sss_society_cipher` (**disabled**) → assign `id="sss-cipher"` in WP when re-enabled
11. `sss_word_search` (sss-word-search)
12. `sss_wallpaper_drop` (**disabled**)
13. `sss_monthly_calendar` (sss-monthly-calendar)

**society_monthly_intro (verbatim HTML):**
```html
<section class="sss-monthlydashIntro">
  <div class="sss-monthlydashIntro__wrap">
    <p class="sss-monthlydashIntro__kicker">this month inside the society</p>
    <h2 class="sss-monthlydashIntro__title">
      the current drop, your rituals, and the little things worth lingering in.
    </h2>
    <p class="sss-monthlydashIntro__sub">everything below should feel like yours - because it is.</p>
  </div>
</section>
```

**Key section settings:**
```
sss-word-search:
  kicker:       "member ritual"
  title:        "a little ritual before you leave."
  words:        "almost\nforever\nunfinished\ntiming\nfate\nmemory\nlinger\npause\nagain\nwait\nsomeday\nreturn\necho\nstill\nalways"
  unlock_title: "you did it, pretty."
  unlock_btn:   "claim your surprise ↗"

sss-society-cipher (disabled — Phase 2):
  words:   "slowburn,yearning,devotion,tension,finally||petals,bloom,linger,glance,softly||..."
  accent:  "#ff69b4"

sss-monthly-calendar:
  subtitle: "a month of chaos, crushes, and fictional men"

sss-wallpaper-drop (disabled — Phase 2):
  lockscreen_url: https://www.dropbox.com/... (Dropbox link)
```

**`drop` / `preview_drop_handle` pattern:**  
In Shopify, each monthly drop has a handle (e.g., `"2025-06"`). In WordPress → ACF options field `current_drop_handle` (string): `get_field('current_drop_handle', 'option')`

**WordPress replacement:**
- `words` newline text → `explode("\n", $words)` for word-search grid
- `sss-word-search` unlock event → JS dispatches to Supabase or triggers confetti animation
- `sss-monthly-calendar` → ACF repeater or Supabase `calendar_events` table by month

**Files Codex creates:**
- `page-ssslibrary.php`
- `page-sss-monthly-staging.php`
- `inc/sections/sss-monthly-intro.php`
- `inc/sections/sss-auto-mood-pills.php`
- `inc/sections/sss-moodboard-playlist.php`
- `inc/sections/sss-society-journal.php`
- `inc/sections/sss-auto-exclusive.php`
- `inc/sections/sss-word-search.php`
- `inc/sections/sss-monthly-calendar.php`
- `assets/css/sss-monthly.css`
- `assets/js/sss-word-search.js`

---

### 5.3 SSS Library Books Page

**Source template:** `templates/page.sss-library.json`  
**Sections in order:**
1. `main` (disabled)
2. `sss_folder_tabs` (sss-folder-tabs — 8 tabs)
3. `sss_read_tracker_calendar` (sss-read-tracker-calendar)
4. `sss_library_page` (sss-library-page)

**sss-library-page settings:**
```
kicker:  "classified for members"
title:   "the society library"
subtext: "the books that ruined us - and we'd let them do it again."
```

**Shelf blocks (type: `shelf` ×7 + `private_shelf` ×1):**
1. `morally gray lovers` — "🖤 for the morally gray lovers"
2. `romantasy girls` — "🗡 for the romantasy lovers"
3. `soft-hearted` — "🌷 for the soft-hearted"
4. `athletes` — "🏒 for the delusional about athletes"
5. `five chili nights` — "🔥 five chili nights"
6. `society classics` — "💌 society classics"
7. `extra dark` — "☠️ extra dark"
8. `private shelf` — "the private shelf" (private_shelf type)

**ADDITIONAL: Book Finder Widget (from `sections/sss-library-page.liquid`):**
```html
<!-- Book Finder -->
<div class="sss-lib__finder" id="sssReadFinder">
  <h3 class="sss-lib__finderTitle">find your next read</h3>
  <div class="sss-lib__finderForm">
    <!-- Dropdown 1: shelf filter -->
    <select id="sssFinderShelf" class="sss-lib__finderSelect">
      <option value="">any shelf</option>
      {% for shelf_name in visible_shelves %}
        <option value="{{ shelf_name }}">{{ shelf_name }}</option>
      {% endfor %}
    </select>
    <!-- Dropdown 2: trope filter -->
    <select id="sssFinderTrope" class="sss-lib__finderSelect">
      <option value="">any trope</option>
      {% for trope in visible_tropes %}
        <option value="{{ trope }}">{{ trope }}</option>
      {% endfor %}
    </select>
    <!-- Dropdown 3: spice filter -->
    <select id="sssFinderSpice" class="sss-lib__finderSelect">
      <option value="">any spice</option>
      <option value="1">🌶</option>
      <option value="2">🌶🌶</option>
      <option value="3">🌶🌶🌶</option>
      <option value="4">🌶🌶🌶🌶</option>
      <option value="5">🌶🌶🌶🌶🌶</option>
    </select>
    <button id="sssFinderSubmit" class="sss-lib__finderBtn">find a book</button>
  </div>
  <!-- Result card (JS-populated) -->
  <div class="sss-lib__finderResult" id="sssFinderResult" hidden>
    <img class="sss-lib__finderCover" id="sssFinderCover" src="" alt="">
    <div class="sss-lib__finderInfo">
      <p class="sss-lib__finderBookTitle" id="sssFinderBookTitle"></p>
      <p class="sss-lib__finderAuthor" id="sssFinderAuthor"></p>
      <p class="sss-lib__finderWhy" id="sssFinderWhy"></p>
    </div>
  </div>
</div>
```

**Inline JSON data block (injected into page for JS):**
```html
<script type="application/json" id="sssFinderData">
[
  {
    "handle": "book-slug",
    "title": "Book Title",
    "author": "Author Name",
    "cover": "https://cdn.shopify.com/...",
    "why": "why i loved it text",
    "mini": "one-line teaser",
    "shelf": "morally gray lovers",
    "tropes": ["enemies to lovers", "slow burn", "dark hero"]
  },
  ...all visible non-private books...
]
</script>
```

**ADDITIONAL: Boyfriend Votes Widget:**
```html
<div class="sss-lib__votes" id="sssBfVotes">
  <h3>this month's fictional boyfriend vote</h3>

  <!-- Nominee input -->
  <input type="text" id="sssBfVoteInput" placeholder="nominate a boyfriend">
  <button id="sssBfVoteSubmit">vote</button>

  <!-- Results (JS-populated after vote) -->
  <div class="sss-lib__voteResults" id="sssBfResults" hidden>
    <h4 class="sss-lib__voteWinner" id="sssBfWinner"></h4>
    <ul class="sss-lib__voteList" id="sssBfList"></ul>
  </div>
</div>
```

**Boyfriend votes JS (Supabase):**
```js
// Submit vote
const { error } = await supabase
  .from('boyfriend_votes')
  .insert([{ name: inputValue }]);

// Fetch current standings
const { data } = await supabase
  .from('boyfriend_votes_current_month')
  .select('name, vote_count')
  .order('vote_count', { ascending: false });

// Render winner (data[0]) with animated reveal
```

**WordPress replacement for sss-library-page:**
- Member gate: `bbb_is_sss_member()`
- `sssFinderData` JSON → `wp_localize_script('sss-library-member', 'SSSFinderData', bbb_get_books_for_finder())`
- `bbb_get_books_for_finder()` returns PHP array: all visible non-private books with handle, title, author, cover, why, mini, shelf (slug), tropes (array of term names)
- Dropdown options for shelves/tropes → derived from `SSSFinderData` in JS (or pre-populated from PHP)
- Boyfriend votes → Supabase tables `boyfriend_votes` and `boyfriend_votes_current_month` stay unchanged

**Files Codex creates:**
- `page-sss-library.php`
- `inc/sections/sss-library-page.php`
- `inc/sections/sss-read-tracker-calendar.php`
- `assets/js/sss-library-member.js` (Book Finder algorithm + boyfriend votes)
- `assets/js/sss-read-tracker.js`

**Priority:** P1

---

### 5.4 Made for You (Personalized Recommendations)

**Source template:** `templates/page.sss-made-for-you.json`  
**Sections in order:** `main` (disabled), `sss_folder_tabs` (7 tabs), `sss_made_for_you_main` (sss-made-for-you)

**sss-made-for-you settings:**
```
kicker:  "private reader file"
title:   "made for you, properly."
subtext: "your taste, your spice, your finished shelf, and the books most likely to hit next."
```

**6-Question Quiz DOM (`.sss-mfy__track`, slides `data-mfy-step="0"` through `"5"`):**

```html
<section class="sss-lib sss-lib--mfy-page" data-sss-lib="society">
  <div class="sss-lib__wrap">

    <!-- Quiz track -->
    <div class="sss-mfy__track">

      <!-- Step 0: Name input -->
      <div class="sss-mfy__slide" data-mfy-step="0">
        <p class="sss-mfy__q">what do I call you?</p>
        <input type="text" class="sss-mfy__nameInput" id="sssReaderName" placeholder="first name">
        <button class="sss-mfy__next">next →</button>
      </div>

      <!-- Step 1: Craving (5 options) -->
      <div class="sss-mfy__slide" data-mfy-step="1">
        <p class="sss-mfy__q">what are you craving right now?</p>
        <div class="sss-mfy__options">
          <button class="sss-mfy__opt" data-craving="chaos">chaos + tension</button>
          <button class="sss-mfy__opt" data-craving="soft">soft landing</button>
          <button class="sss-mfy__opt" data-craving="dark">something dark</button>
          <button class="sss-mfy__opt" data-craving="epic">an epic love</button>
          <button class="sss-mfy__opt" data-craving="escapism">pure escapism</button>
        </div>
      </div>

      <!-- Step 2: Payoff (5 options) -->
      <div class="sss-mfy__slide" data-mfy-step="2">
        <p class="sss-mfy__q">what kind of payoff do you need?</p>
        <div class="sss-mfy__options">
          <button class="sss-mfy__opt" data-payoff="angst">earned HEA after angst</button>
          <button class="sss-mfy__opt" data-payoff="spicy">all the spice</button>
          <button class="sss-mfy__opt" data-payoff="slow">slow burn that wrecks you</button>
          <button class="sss-mfy__opt" data-payoff="sweet">sweet and easy</button>
          <button class="sss-mfy__opt" data-payoff="bittersweet">bittersweet is fine</button>
        </div>
      </div>

      <!-- Step 3: Boyfriend hook (5 options) -->
      <div class="sss-mfy__slide" data-mfy-step="3">
        <p class="sss-mfy__q">what gets you attached to a book boyfriend?</p>
        <div class="sss-mfy__options">
          <button class="sss-mfy__opt" data-boyfriend_hook="morally-gray">morally gray with a soft spot for her</button>
          <button class="sss-mfy__opt" data-boyfriend_hook="protective">obsessively protective</button>
          <button class="sss-mfy__opt" data-boyfriend_hook="golden">golden boy who's secretly wrecked</button>
          <button class="sss-mfy__opt" data-boyfriend_hook="villain">villain energy done right</button>
          <button class="sss-mfy__opt" data-boyfriend_hook="soft">quietly devoted and kind</button>
        </div>
      </div>

      <!-- Step 4: Dynamic (6 options) -->
      <div class="sss-mfy__slide" data-mfy-step="4">
        <p class="sss-mfy__q">what's the relationship dynamic you want?</p>
        <div class="sss-mfy__options">
          <button class="sss-mfy__opt" data-boyfriend_dynamic="enemies">enemies to lovers</button>
          <button class="sss-mfy__opt" data-boyfriend_dynamic="forced">forced proximity</button>
          <button class="sss-mfy__opt" data-boyfriend_dynamic="grumpy-sunshine">grumpy + sunshine</button>
          <button class="sss-mfy__opt" data-boyfriend_dynamic="second-chance">second chance</button>
          <button class="sss-mfy__opt" data-boyfriend_dynamic="forbidden">forbidden</button>
          <button class="sss-mfy__opt" data-boyfriend_dynamic="fated">fated mates / destiny</button>
        </div>
      </div>

      <!-- Step 5: Theme/aesthetic (6 options) -->
      <div class="sss-mfy__slide" data-mfy-step="5">
        <p class="sss-mfy__q">what's the aesthetic?</p>
        <div class="sss-mfy__options">
          <button class="sss-mfy__opt" data-theme="dark-academia">dark academia</button>
          <button class="sss-mfy__opt" data-theme="romantasy">romantasy world</button>
          <button class="sss-mfy__opt" data-theme="contemporary">contemporary / real world</button>
          <button class="sss-mfy__opt" data-theme="small-town">small town cozy</button>
          <button class="sss-mfy__opt" data-theme="gothic">gothic + atmospheric</button>
          <button class="sss-mfy__opt" data-theme="sports">sports + competitive</button>
        </div>
      </div>

    </div><!-- end .sss-mfy__track -->

    <!-- Results panels (shown after quiz) -->
    <div class="sss-mfy__results" hidden>

      <!-- Panel 1: Reader core profile -->
      <div class="sss-mfy__panel sss-mfy__panel--reader">
        <p class="sss-mfy__panelKicker">your reader profile</p>
        <h2 class="sss-mfy__panelTitle" id="sssReaderCore"></h2>
      </div>

      <!-- Panel 2: Fictional boyfriend -->
      <div class="sss-mfy__panel sss-mfy__panel--boyfriend">
        <p class="sss-mfy__panelKicker">your fictional boyfriend is</p>
        <h2 class="sss-mfy__panelTitle" id="sssBfResult"></h2>
        <img class="sss-mfy__bfCover" id="sssBfCover" src="" alt="">
      </div>

      <!-- Panel 3: Next read recommendation -->
      <div class="sss-mfy__panel sss-mfy__panel--nextread">
        <p class="sss-mfy__panelKicker">your next read</p>
        <h2 class="sss-mfy__panelTitle" id="sssNextRead"></h2>
        <img class="sss-mfy__recCover" id="sssRecCover" src="" alt="">
      </div>

      <!-- Addons -->
      <div class="sss-mfy__addons">

        <!-- Hard nos (8 chips) -->
        <div class="sss-mfy__hardNos">
          <p>select your hard nos:</p>
          <div class="sss-mfy__chips">
            <button class="sss-mfy__chip" data-no="cheating">cheating</button>
            <button class="sss-mfy__chip" data-no="cliffhanger">cliffhanger ending</button>
            <button class="sss-mfy__chip" data-no="tragedy">tragedy</button>
            <button class="sss-mfy__chip" data-no="love-triangle">love triangle</button>
            <button class="sss-mfy__chip" data-no="miscommunication">miscommunication trope</button>
            <button class="sss-mfy__chip" data-no="graphic-violence">graphic violence</button>
            <button class="sss-mfy__chip" data-no="noncon">noncon</button>
            <button class="sss-mfy__chip" data-no="dubcon">dubcon</button>
          </div>
        </div>

        <!-- Spice dial -->
        <div class="sss-mfy__spiceDial">
          <p>spice preference:</p>
          <input type="range" id="sssSpiceDial" min="1" max="5" value="3">
          <div class="sss-mfy__spiceButtons">
            <button data-spice="1">🌶</button>
            <button data-spice="2">🌶🌶</button>
            <button data-spice="3">🌶🌶🌶</button>
            <button data-spice="4">🌶🌶🌶🌶</button>
            <button data-spice="5">🌶🌶🌶🌶🌶</button>
          </div>
        </div>

        <!-- Favorite book search -->
        <div class="sss-mfy__favSearch">
          <p>add a book you've already loved:</p>
          <input type="text" id="sssFavBookSearch" placeholder="search the library...">
          <div class="sss-mfy__favResults" id="sssFavResults"></div>
        </div>

      </div><!-- end .sss-mfy__addons -->

      <!-- Quote spotlight -->
      <div class="sss-mfy__quoteSpot" id="sssQuoteSpot"></div>

      <!-- Saved quotes -->
      <div class="sss-mfy__savedQuotes" id="sssSavedQuotes"></div>

      <!-- Read shelf with trope insights -->
      <div class="sss-mfy__readShelf" id="sssReadShelf"></div>

    </div><!-- end .sss-mfy__results -->

  </div>
</section>
```

**Inline JSON data blocks (injected at page render):**
```html
<!-- All visible books excluding current-month featured -->
<script type="application/json" id="sssMadeForYouData">
[{
  "handle": "...", "title": "...", "author": "...", "cover": "...",
  "shelf": "...", "tropes": [...], "spice": 3, "tension": 4,
  "damage": 3, "darkness": 2, "yearning": 5, "ku": false
}]
</script>

<!-- All quotes cross-referenced to visible books -->
<script type="application/json" id="sssMadeForYouQuotes">
[{
  "quote": "...", "book_handle": "...", "book_title": "...",
  "author": "...",
  "theme": "gray|blue|red|yellow"
}]
</script>
```

**Theme color logic for quotes:**
- `hl--blue` → `romantasy` / `fantasy` shelf
- `hl--red` → `dark` / `morally-gray` / `private` / `five-chili` / `extra-dark` shelf
- `hl--yellow` → `soft` / `sentimental` / `classics` / `starter` shelf
- `hl--gray` → default/everything else

**WordPress replacement:**
- Member gate: `bbb_is_sss_member()`
- `sssMadeForYouData` → `wp_localize_script('sss-made-for-you', 'SSSmfyData', bbb_get_mfy_books())`
- `sssMadeForYouQuotes` → `wp_localize_script('sss-made-for-you', 'SSSmfyQuotes', bbb_get_mfy_quotes())`
- `bbb_get_mfy_books()` → queries all `sss_book` where `hide_from_library != 1` AND `is_private != 1`, excludes current month featured book; returns array with all required fields
- `bbb_get_mfy_quotes()` → queries all `sss_quote`, joins with `sss_book` CPT, assigns theme based on shelf taxonomy term slug
- All quiz logic + recommendation algorithm → copy JS verbatim into `sss-made-for-you.js`

**Files Codex creates:**
- `page-sss-made-for-you.php`
- `inc/sections/sss-made-for-you.php`
- `assets/js/sss-made-for-you.js`

---

### 5.5 Private Shelf

**Source template:** `templates/page.sss-private-shelf.json`

**sss-private-shelf-section settings:**
```
kicker:      "between us"
title:       "the private shelf"
description: "member-only books, private notes, and the recs i keep tucked inside the society."
group_by:    "shelf"
```

**WordPress replacement:**
- Member gate required
- Query: `sss_book` where `is_private = true`, grouped by `sss_shelf` term
- `group_by: "shelf"` → iterate `get_terms('sss_shelf')`, then `WP_Query` per term

**Files Codex creates:** `page-sss-private-shelf.php`, `inc/sections/sss-private-shelf-section.php`

---

### 5.6 Quote Wall

**Source template:** `templates/page.sss-quote-wall.json`

**sss-quote-wall settings:**
```
kicker:  "sss quote library"
title:   "lines that ruined me (in a good way)."
subtext: "tap a quote to revisit the rec."
new_tab: false
```

**Full DOM — `sections/sss-quote-wall.liquid`:**
```html
<section class="sss-qw" data-sss-quote-wall data-sss-lib="public">
  <div class="sss-qw__wrap">

    <!-- Header -->
    <header class="sss-qw__head">
      <p class="sss-qw__kicker">{{ section.settings.kicker }}</p>
      <h1 class="sss-qw__title">{{ section.settings.title }}</h1>
      <p class="sss-qw__sub">{{ section.settings.subtext }}</p>
    </header>

    <!-- Feature card (static) -->
    <div class="sss-qw__featureCard">
      <p class="sss-qw__featureText">save the lines you want to keep.</p>
    </div>

    <!-- Search input -->
    <div class="sss-qw__search">
      <input type="text" data-qw-search placeholder="search quotes, books, authors...">
    </div>

    <!-- Count display -->
    <p class="sss-qw__count">
      <span data-qw-count>0</span> quotes
    </p>

    <!-- Quote list -->
    <div class="sss-qw__list" data-qw-list>

      {% for quote in shop.metaobjects.sss_quote.values %}
        {% assign book = quote.book.value %}
        {% assign shelf_handle = book.shelf.value.system.handle | downcase %}

        <!-- Determine highlight color based on shelf -->
        {% if shelf_handle contains 'romantasy' or shelf_handle contains 'fantasy' %}
          {% assign hl_class = 'hl--blue' %}
        {% elsif shelf_handle contains 'dark' or shelf_handle contains 'morally-gray'
               or shelf_handle contains 'five-chili' or shelf_handle contains 'extra-dark'
               or shelf_handle contains 'private' %}
          {% assign hl_class = 'hl--red' %}
        {% elsif shelf_handle contains 'soft' or shelf_handle contains 'classics'
               or shelf_handle contains 'starter' %}
          {% assign hl_class = 'hl--yellow' %}
        {% else %}
          {% assign hl_class = 'hl--gray' %}
        {% endif %}

        <div class="qw-item{% cycle ' is-left', ' is-right' %}"
             data-qw-quote="{{ quote.quote_text | escape }}"
             data-qw-book="{{ book.title | escape }}"
             data-qw-author="{{ book.author.value | escape }}"
             data-qw-shelf="{{ shelf_handle }}"
             style="--d: {{ forloop.index | times: 0.05 }}s">

          <!-- Card surface (click to open book detail) -->
          <button class="qw-cardSurface" data-qw-open
                  data-book-handle="{{ book.system.handle }}"
                  data-book-title="{{ book.title | escape }}"
                  data-book-author="{{ book.author.value | escape }}"
                  data-book-cover="{{ book.cover.value | img_url: '300x' }}"
                  data-book-shelf="{{ shelf_handle }}">

            <!-- Paper card -->
            <div class="qw-paper">
              <p class="qw-paper__meta">{{ book.title | escape }} — {{ book.author.value | escape }}</p>
              <blockquote class="qw-paper__quote">
                <span class="hl {{ hl_class }}">{{ quote.quote_text }}</span>
              </blockquote>
            </div>

          </button>

          <!-- Action buttons -->
          <div class="qw-item__actions">
            <button class="qw-item__save" data-qw-save
                    data-quote="{{ quote.quote_text | escape }}"
                    data-book="{{ book.title | escape }}">
              save quote
            </button>
            <button class="qw-item__note" data-qw-note
                    data-text="{{ quote.quote_text | escape }}">
              copy
            </button>
          </div>

        </div>
      {% endfor %}

    </div><!-- end data-qw-list -->

  </div>

  <!-- Save toast notification -->
  <div class="sss-qw__toast" data-qw-toast hidden>quote saved</div>

</section>
```

**Highlight class map:**

| Class | Shelf match |
|-------|-------------|
| `hl--blue` | `romantasy`, `fantasy` |
| `hl--red` | `dark`, `morally-gray`, `five-chili`, `extra-dark`, `private` |
| `hl--yellow` | `soft`, `classics`, `starter`, `sentimental` |
| `hl--gray` | everything else (default) |

**IntersectionObserver scroll reveal (extract to JS):**
```js
// Each .qw-item gets staggered animation via CSS var --d (delay)
// Observer fires once per item on first intersection
const observer = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add('is-visible');
      observer.unobserve(entry.target);
    }
  });
}, { threshold: 0.1 });
document.querySelectorAll('.qw-item').forEach(el => observer.observe(el));
```

**Search filter (extract to JS):**
```js
document.querySelector('[data-qw-search]').addEventListener('input', function() {
  const query = this.value.toLowerCase();
  const items = document.querySelectorAll('[data-qw-list] .qw-item');
  let count = 0;
  items.forEach(item => {
    const text = [
      item.dataset.qwQuote, item.dataset.qwBook, item.dataset.qwAuthor
    ].join(' ').toLowerCase();
    const match = !query || text.includes(query);
    item.hidden = !match;
    if (match) count++;
  });
  document.querySelector('[data-qw-count]').textContent = count;
});
```

**Save-to-MFY action:**
```js
document.querySelectorAll('[data-qw-save]').forEach(btn => {
  btn.addEventListener('click', function() {
    window.sssQuoteStorage.toggleSavedQuote({
      quote: this.dataset.quote,
      book: this.dataset.book
    });
    document.querySelector('[data-qw-toast]').hidden = false;
    setTimeout(() => document.querySelector('[data-qw-toast]').hidden = true, 2000);
  });
});
```

**CSS:** Fully inline (722-line section). Extract to `assets/css/sss-quote-wall.css`.  
**JS:** Inline; extract to `assets/js/sss-quote-wall.js`. Uses `window.sssQuoteStorage` (defined in `sss-library.js`). Also uses Supabase CDN + `sss-library.js`.

**WordPress replacement:**
```php
$quotes = new WP_Query([
  'post_type'      => 'sss_quote',
  'posts_per_page' => -1,
  'orderby'        => 'rand',
]);
// For each quote:
$book = get_field('book', $quote->ID); // returns WP_Post (sss_book)
$shelf_terms = get_the_terms($book->ID, 'sss_shelf');
$shelf_slug = $shelf_terms ? $shelf_terms[0]->slug : '';

// Determine hl_class from $shelf_slug:
$hl_class = 'hl--gray';
if (str_contains($shelf_slug, 'romantasy') || str_contains($shelf_slug, 'fantasy')) {
    $hl_class = 'hl--blue';
} elseif (str_contains($shelf_slug, 'dark') || str_contains($shelf_slug, 'morally-gray')
       || str_contains($shelf_slug, 'five-chili') || str_contains($shelf_slug, 'extra-dark')) {
    $hl_class = 'hl--red';
} elseif (str_contains($shelf_slug, 'soft') || str_contains($shelf_slug, 'classics')
       || str_contains($shelf_slug, 'starter')) {
    $hl_class = 'hl--yellow';
}
```

**Files Codex creates:**
- `page-sss-quote-wall.php`
- `inc/sections/sss-quote-wall.php`
- `inc/cpt/sss-quote.php`
- `acf-groups/sss-quote.json`
- `assets/css/sss-quote-wall.css`
- `assets/js/sss-quote-wall.js`

---

### 5.7 SSS Canva Templates (Member)

**Source template:** `templates/page.sss-canva-templates.json`

**sss-canva-library settings:**
```
kicker:              "bookish templates"
title:               "make it reader-core."
collection:          "bookish-templates"
show_category_chips: true
open_new_tab:        true
```

**WordPress replacement:**
- Member gate required
- `collection: "bookish-templates"` → WooCommerce category `bookish-templates`
- `show_category_chips: true` → category filter pills (same JS pattern as ki-style-shelf)
- `open_new_tab: true` → product links use `target="_blank"`

**Files Codex creates:** `page-sss-canva-templates.php`, `inc/sections/sss-canva-library.php`

---

### 5.8 Society Newsletter Submissions

*(Same section as §1.9 — uses identical `newsletter-submissions-page` section)*

**Source template:** `templates/page.society-submissions.json`  
**Files Codex creates:** `page-society-submissions.php` (includes same section as §1.9)

---

### 5.9 Quote Audit Tool (Admin)

**Source template:** `templates/page.quote-audit.json`

**Section settings:**
```
kicker: "admin audit"
title:  "sss quote coverage"
```

**Full DOM — `sections/quote-audit.liquid` (pure Liquid, NO JavaScript):**
```html
<section class="qa-audit">

  <!-- Stats grid (4 columns) -->
  <div class="qa-audit__stats">
    <div class="qa-audit__stat">
      <span class="qa-audit__statVal">{{ visible_books }}</span>
      <span class="qa-audit__statLbl">visible books</span>
    </div>
    <div class="qa-audit__stat">
      <span class="qa-audit__statVal">{{ matched_count }}</span>
      <span class="qa-audit__statLbl">books with quotes</span>
    </div>
    <div class="qa-audit__stat">
      <span class="qa-audit__statVal">{{ missing_count }}</span>
      <span class="qa-audit__statLbl">missing quotes</span>
    </div>
    <div class="qa-audit__stat">
      <span class="qa-audit__statVal">{{ multi_count }}</span>
      <span class="qa-audit__statLbl">books with 2+ quotes</span>
    </div>
  </div>

  <!-- Summary grid (2-column: genre + trope) -->
  <div class="qa-audit__summaryGrid">

    <!-- Genre coverage table -->
    <div class="qa-audit__summaryBlock">
      <h3>coverage by genre/shelf</h3>
      <table>
        <tr><th>shelf</th><th>books</th><th>with quotes</th></tr>
        {% for genre in genre_counts %}
          <tr>
            <td>{{ genre.name }}</td>
            <td>{{ genre.total }}</td>
            <td>{{ genre.with_quotes }}</td>
          </tr>
        {% endfor %}
      </table>
    </div>

    <!-- Trope coverage table -->
    <div class="qa-audit__summaryBlock">
      <h3>most quoted tropes</h3>
      <table>
        <tr><th>trope</th><th>quote count</th></tr>
        {% for trope in trope_counts %}
          <tr>
            <td>{{ trope.name }}</td>
            <td>{{ trope.count }}</td>
          </tr>
        {% endfor %}
      </table>
    </div>

  </div>

  <!-- Missing books full-width panel -->
  <div class="qa-audit__missing">
    <h3>books missing quotes ({{ missing_count }})</h3>
    <table>
      <tr><th>title</th><th>author</th><th>shelf</th><th>boyfriend</th><th>handle</th></tr>
      {% for book in missing_books %}
        <tr>
          <td>{{ book.title }}</td>
          <td>{{ book.author }}</td>
          <td>{{ book.shelf }}</td>
          <td>{{ book.boyfriend }}</td>
          <td><code>{{ book.handle }}</code></td>
        </tr>
      {% endfor %}
    </table>
  </div>

</section>
```

**Liquid cross-reference logic (verbatim pattern):**
```liquid
{% assign all_library = shop.metaobjects.sss_library.values %}
{% assign all_quotes  = shop.metaobjects.sss_quote.values %}
{% assign visible_books = 0 %}
{% assign matched_count = 0 %}
{% assign missing_count = 0 %}
{% assign multi_count   = 0 %}

{% for book in all_library %}
  {% unless book.hide_from_library.value %}
    {% assign visible_books = visible_books | plus: 1 %}
    {% assign book_quote_count = 0 %}
    {% for quote in all_quotes %}
      {% if quote.book.value.system.handle == book.system.handle %}
        {% assign book_quote_count = book_quote_count | plus: 1 %}
      {% endif %}
    {% endfor %}
    {% if book_quote_count > 0 %}
      {% assign matched_count = matched_count | plus: 1 %}
    {% else %}
      {% assign missing_count = missing_count | plus: 1 %}
      <!-- append to missing_books array -->
    {% endif %}
    {% if book_quote_count >= 2 %}
      {% assign multi_count = multi_count | plus: 1 %}
    {% endif %}
  {% endunless %}
{% endfor %}
```

**CSS:** Fully inline (bg `#070709`, font: Cormorant Garamond). Extract to `assets/css/quote-audit.css`.  
**JS:** None — pure server-side render.

**WordPress replacement:**
```php
// Admin-only gate:
if (!current_user_can('manage_options')) { wp_redirect(home_url()); exit; }

// Cross-reference CPTs:
$all_books  = get_posts(['post_type'=>'sss_book','numberposts'=>-1,'suppress_filters'=>false]);
$all_quotes = get_posts(['post_type'=>'sss_quote','numberposts'=>-1]);

$quoted_book_ids = [];
foreach ($all_quotes as $q) {
    $book = get_field('book', $q->ID);
    if ($book) $quoted_book_ids[] = $book->ID;
}
$quote_counts = array_count_values($quoted_book_ids);

$visible = $matched = $missing = $multi = 0;
$missing_books = [];

foreach ($all_books as $book) {
    if (get_field('hide_from_library', $book->ID)) continue;
    $visible++;
    $count = $quote_counts[$book->ID] ?? 0;
    if ($count > 0) { $matched++; }
    else {
        $missing++;
        $shelf_terms = get_the_terms($book->ID, 'sss_shelf');
        $missing_books[] = [
            'title'     => $book->post_title,
            'author'    => get_field('author', $book->ID),
            'shelf'     => $shelf_terms ? $shelf_terms[0]->name : '',
            'boyfriend' => get_field('boyfriend_name', $book->ID),
            'handle'    => $book->post_name,
        ];
    }
    if ($count >= 2) $multi++;
}
```

**Files Codex creates:**
- `page-quote-audit.php`
- `inc/sections/quote-audit.php`
- `assets/css/quote-audit.css`

**Priority:** P3 (internal admin tool)

---

### 5.10 Preview / Admin Staging Page

**Source template:** `templates/page.preview.json`  
**Sections:** ALL sections disabled EXCEPT:
- `sss_cover_url_audit` (sss-cover-url-audit)
- `sss_trope_audit` (sss-trope-audit)

**preview_drop_handle setting:** `"in-between-almost-and-forever"` (used to preview a specific drop's content before publishing)

**Purpose:** Admin-only staging page to check cover image URLs and trope coverage for a specific upcoming drop before it goes live.

**WordPress replacement:**
- Admin-only gate: `current_user_can('manage_options')`
- `preview_drop_handle` → ACF page field `preview_drop_handle` (text)
- `sss-cover-url-audit` → for each book in the drop, check `get_field('cover', $id)` is a valid non-empty URL, display pass/fail table
- `sss-trope-audit` → for each book in the drop, list trope terms and flag any books with 0 tropes

**Files Codex creates:**
- `page-preview.php`
- `inc/sections/sss-cover-url-audit.php`
- `inc/sections/sss-trope-audit.php`

**Priority:** P3 (internal admin tool)

---

## Shared Infrastructure Codex Must Create

### `functions.php` additions
```php
// 1. Enqueue base CSS/JS (sss-library.css, sss-library.js, Supabase CDN, fonts)
// 2. Register CPTs: sss_book, sss_series, sss_quote
// 3. Register taxonomies: sss_shelf, sss_trope, sss_spice, book_review_category
// 4. Register custom user role: sss_member
// 5. Register REST endpoints: /wp-json/bbb/v1/books, /wp-json/bbb/v1/shelf
// 6. wp_localize_script for Supabase credentials and book data
// 7. Include: inc/redirects.php, inc/bbb-helpers.php, inc/api/*.php, inc/cpt/*.php
```

### `inc/bbb-helpers.php`
```php
function bbb_is_sss_member(): bool {
  if (!is_user_logged_in()) return false;
  $user = wp_get_current_user();
  return in_array('sss_member', $user->roles)
      || (function_exists('wc_memberships_is_user_active_member')
          && wc_memberships_is_user_active_member(get_current_user_id(), 'smut-sentiment-society'));
}

function bbb_resolve_page_url(string $slug): string {
  return get_permalink(get_page_by_path($slug)) ?: home_url('/' . $slug);
}

function bbb_get_book_cover_url(int $post_id): string {
  $field = get_field('cover', $post_id);
  return is_array($field) ? ($field['url'] ?? '') : ($field ?: '');
}

function bbb_get_all_books_json(): array {
  $books = get_posts(['post_type'=>'sss_book','numberposts'=>-1,'suppress_filters'=>false]);
  $out = [];
  foreach ($books as $b) {
    if (get_field('hide_from_library', $b->ID)) continue;
    $shelf_terms = get_the_terms($b->ID, 'sss_shelf') ?: [];
    $trope_terms = get_the_terms($b->ID, 'sss_trope') ?: [];
    $out[] = [
      'id'          => $b->ID,
      'title'       => $b->post_title,
      'slug'        => $b->post_name,
      'author'      => get_field('author', $b->ID),
      'cover_url'   => bbb_get_book_cover_url($b->ID),
      'shelf'       => $shelf_terms ? $shelf_terms[0]->slug : '',
      'tropes'      => array_map(fn($t) => $t->name, $trope_terms),
      'spice_level' => (int) get_field('spice_level', $b->ID),
      'is_private'  => (bool) get_field('is_private', $b->ID),
      'starter_pack'=> (bool) get_field('starter_pack', $b->ID),
      'on_ku'       => (bool) get_field('on_kindle_unlimited', $b->ID),
    ];
  }
  return $out;
}
```

### `inc/api/books-endpoint.php`
```php
// REST: GET /wp-json/bbb/v1/books
// Returns: [{id, title, slug, shelf, tropes[], spice_level, cover_url, is_private, starter_pack}]
// Used by: What to Read Next, Made for You, Library client JS
register_rest_route('bbb/v1', '/books', [
  'methods'  => 'GET',
  'callback' => fn() => rest_ensure_response(bbb_get_all_books_json()),
  'permission_callback' => '__return_true',
]);
```

### `inc/api/shelf-endpoint.php`
```php
// REST: GET  /wp-json/bbb/v1/shelf?email=... (proxies Supabase saved shelf)
// REST: POST /wp-json/bbb/v1/shelf (saves book to Supabase shelf)
// Uses: SUPABASE_URL and SUPABASE_ANON_KEY constants from wp-config.php
```

### Font enqueuing (`functions.php`)
```php
// Enqueue Google Fonts: Cormorant (weight 300-700 italic), Kaushan Script, Libre Baskerville
add_action('wp_enqueue_scripts', function() {
  wp_enqueue_style('bbb-fonts',
    'https://fonts.googleapis.com/css2?family=Cormorant:ital,wght@0,300;0,400;0,600;0,700;1,400;1,600&family=Kaushan+Script&family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&display=swap',
    [], null
  );
});
```

### `wp_localize_script` data shape
```php
wp_localize_script('sss-library', 'BBBLibraryData', [
  'books'       => bbb_get_all_books_json(),
  'supabaseUrl' => SUPABASE_URL,
  'supabaseKey' => SUPABASE_ANON_KEY,
  'currentUser' => is_user_logged_in() ? wp_get_current_user()->user_email : null,
  'isMember'    => bbb_is_sss_member(),
  'ajaxUrl'     => admin_url('admin-ajax.php'),
  'nonce'       => wp_create_nonce('bbb_shelf'),
  'homeUrl'     => home_url('/'),
]);
```

### CSS files to copy verbatim from source `assets/`:
```
sss-library.css
sss-folder-tabs.css
section-main-page.css
sss-you-might-like.css
```

### JS files to port (replace Liquid var injections with wp_localize_script data):
```
sss-library.js              → replace {{ book.* }} with window.BBBLibraryData
sss-library-member.js       → member variant (Book Finder + boyfriend votes)
bbb-what-to-read-next.js
sss-read-tracker.js
sss-made-for-you.js
sss-quote-wall.js
customer-bookshelf.js
newsletter-submissions.js
ki-style-shelf.js
page-spice.js
fictional-boyfriend-quiz.js
reader-mood-quiz.js
sss-freebies.js
sss-word-search.js
bookshelf-weekly-preview.js
```

---

## Priority Matrix

| Priority | Group | Templates | Blocking dependency |
|----------|-------|-----------|---------------------|
| **P1** | Infrastructure | CPT registration, ACF groups, member gate, REST endpoints, sss-book-card | Nothing |
| **P1** | Group 1 | page-default, page-our-story, page-contact, page-reading-list (redirect) | None |
| **P1** | Group 2 | page-library (public), page-book-reviews | CPT `sss_book` |
| **P1** | Group 5 | sss-folder-tabs, sss-member-dashboard, page-societylibrary, page-sss-library | CPT + member gate |
| **P2** | Group 1 | page-for-readers, page-media-kit, page-newsletter-submissions, page-newslettertemplate | None |
| **P2** | Group 2 | page-shelf, page-spice, page-trope, page-series-*, page-books-like-*, page-weekly-obsession, page-my-bookshelf | CPT `sss_book` |
| **P2** | Group 3 | All quiz pages (self-contained) | None |
| **P2** | Group 4 | page-shop, page-kindle-inserts, page-bookish-templates, page-artprints | WooCommerce |
| **P2** | Group 4 | page-sss-printable-kindle, page-sss-freebies | Member gate |
| **P2** | Group 5 | page-sss-made-for-you, page-sss-private-shelf, page-sss-quote-wall, page-sss-canva-templates | Member gate + CPTs |
| **P3** | Group 1 | page-customer-reviews | Third-party dep |
| **P3** | Group 2 | page-sss-series (empty), page-bookshelf-weekly-preview | Admin tool |
| **P3** | Group 5 | page-quote-audit, page-preview | Admin tools |

---

## Class-Name Preservation Rules

Do not rename any CSS class. Copy all source CSS verbatim. Classes Codex must preserve:

| Prefix | Context |
|--------|---------|
| `.sss-lib__*` | All library pages |
| `.sss-memberdash__*` | Member dashboard |
| `.sss-tabs__*` / `.sss-folder-tabs__*` | SSS navigation tabs |
| `.sss-mfy__*` | Made for You quiz and results |
| `.sss-qw__*` / `.qw-*` | Quote wall |
| `.sss-readers__*` | For Readers hub |
| `.sss-tropeTop__*` | Trope/spice/shelf page headers |
| `.sss-spiceNav__*` | Spice filter pills |
| `.sss-freebies__*` | Freebies page |
| `.sss-monthlydashIntro__*` | Monthly drop intro |
| `.bbb-*` | By Bookish Babe branded sections |
| `.ki-*` | Kindle inserts shelf |
| `.qa-audit__*` | Quote audit admin tool |
| `.hl--blue` `.hl--red` `.hl--yellow` `.hl--gray` | Quote highlight classes |
| `.rte` | Rich text editor output |
| `.page-width` `.page-width--narrow` | Layout containers |
| `.h0` `.h1` `.hxl` `.hxxl` | Heading size modifiers |
| `data-sss-lib="public"` / `data-sss-lib="society"` | JS behavior flags |
| `data-qw-*` | Quote wall JS hooks |
| `data-mfy-step` | MFY quiz step tracking |
| `data-spice-filter` | Spice page filter |
| `data-memberdash-target` | Dashboard scroll targets |

All `data-*` attributes that drive JavaScript behavior must also be preserved verbatim. These are functional selectors, not just styling hooks.
