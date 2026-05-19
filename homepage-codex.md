# Homepage Section Codex — Shopify → WordPress Implementation Notes

**Source:** `templates/index.json` + all section `.liquid` files  
**Purpose:** Section-by-section implementation spec for the WordPress conversion.  
**Do not edit WordPress files until each section is checked off here.**

---

## Section Order (Active)

Position numbers match `index.json` order array. Disabled sections are listed at the end.

| # | Section Key | Schema Name | File |
|---|-------------|-------------|------|
| 1 | `hero-smut-sentiment` | Hero (Smut & Sentiment) | `sections/hero-smut-sentiment.liquid` |
| 2 | `homepage-weekly-obsession` | Homepage Weekly Obsession | `sections/homepage-weekly-obsession.liquid` |
| 3 | `trending-romance-reads` | Trending Romance Reads | `sections/trending-romance-reads.liquid` |
| 4 | `browse-by-trope` | Browse by Trope | `sections/browse-by-trope.liquid` |
| 5 | `featured-romance-lists` | Featured Romance Lists | `sections/featured-romance-lists.liquid` |
| 6 | `homepage-library-preview` | Society Obsessed Preview | `sections/homepage-library-preview.liquid` |
| 7 | `society-hero` | newsletter cta | `sections/society-hero.liquid` |
| 8 | `bbb-quiz-nudge` | bbb · Quiz nudge | `sections/bbb-quiz-nudge.liquid` |
| 9 | `custom-liquid` (Threads) | — (inline HTML in JSON) | `templates/index.json` inline |
| 10 | `homepage-book-boyfriends` | Homepage Book Boyfriends | `sections/homepage-book-boyfriends.liquid` |
| 11 | `bbb-connect-cards` | Connect Cards | `sections/bbb-connect-cards.liquid` |

**Disabled (skip for now):** `image_banner`, `sss-join-nudge`, `custom-liquid` Bestsellers, `custom-liquid` Review Banner, `bbb-new-this-month`

---

---

## Section 1 — Hero (Smut & Sentiment)

**File:** `sections/hero-smut-sentiment.liquid`

### Settings / Default Text
| Setting | Default |
|---------|---------|
| `heading` | "smut meets sentiment" |
| `mini_text` | "for soft hearts with sinful taste." |
| `subtitle` | "morally gray men delivered every sunday 🖤" |
| Primary CTA label | "explore the library" |
| Primary CTA URL | `/pages/library` |
| Secondary CTA label | "join the society" |
| Secondary CTA URL | Substack subscribe link |

### Dynamic Data Query
**None.** Fully static HTML + CSS. All text is editable via Shopify section settings but has no database dependency.

### CSS Classes
`.hero-custom`, `.hero-overlay`, `.hero-frame`, `.hero-heading`, `.hero-cursive`, `.hero-mini`, `.hero-subtitle`, `.hero-buttons`, `.btn-primary`, `.btn-secondary`

**Animation:** `@keyframes bbbHeroFade` — `opacity: 0 → 1`, `translateY: 1.6rem → 0`  
Stagger delays: heading `0.1s`, mini `0.28s`, subtitle `0.44s`, buttons `0.6s`

### JS Behavior
None. Pure CSS animation on page load.

### WordPress Implementation
- **Type:** ACF Options Page block OR hard-coded template part `template-parts/home/hero.php`
- **Fields (ACF):** `hero_heading` (text), `hero_mini` (text), `hero_subtitle` (text), `hero_cta_primary_label`, `hero_cta_primary_url`, `hero_cta_secondary_label`, `hero_cta_secondary_url`
- **CSS:** Copy the `<style>` block verbatim into `assets/css/home-hero.css`; enqueue on front page only
- **Font dependency:** Uses "Cormorant" or "Libre Baskerville" serif — confirm font stack is loaded in `functions.php`
- **Priority:** Build first. Zero dependencies, sets page tone.

---

---

## Section 2 — Homepage Weekly Obsession

**File:** `sections/homepage-weekly-obsession.liquid`

### Settings / Default Text
| Setting | Default |
|---------|---------|
| `kicker` | "weekly obsession" |
| `section_kicker` | "from the society" |
| `section_title` | "weekly obsession" |
| `section_subtitle` | "the book currently taking over the smut & sentiment society." |
| `obsession_url` | `/pages/weekly-obsession` (fallback) |

### Dynamic Data Query
```liquid
-- Finds the most recent newsletter_issue where publish_date + 36000s (10h) <= now
-- Resolves: current_issue.book.value OR current_issue.library_book.value
-- Renders: cover image (responsive srcset), spice level (🌶️ per level), shelf name, up to 3 trope pills
-- Trope pills use trope-pill-colors snippet for inline CSS variables (--trope-bg, --trope-text)
-- Hides entire section if no live issue or no featured book
```

**WordPress equivalent query:**
```php
// Get the most recently published bbb_newsletter post where 
// publish_date meta + 36000 <= current timestamp
$args = [
  'post_type'      => 'bbb_newsletter',
  'posts_per_page' => 1,
  'meta_query'     => [[
    'key'     => 'publish_date',
    'value'   => time() - 36000,
    'compare' => '<=',
    'type'    => 'NUMERIC',
  ]],
  'orderby'  => 'meta_value_num',
  'meta_key' => 'publish_date',
  'order'    => 'DESC',
];
$issues = get_posts($args);
// Then get ACF relationship field: get_field('featured_book', $issue->ID)
// Fallback: get_field('library_book', $issue->ID)
```

### CSS Classes
`.bbb-home-obsession`, `.bbb-home-obsession__inner`, `.bbb-home-obsession__sparkles`, `.bbb-home-obsession__head`, `.bbb-home-obsession__sectionKicker`, `.bbb-home-obsession__sectionTitle`, `.bbb-home-obsession__sectionSub`, `.bbb-home-obsession__row`, `.bbb-home-obsession__coverLink`, `.bbb-home-obsession__coverWrap`, `.bbb-home-obsession__cover`, `.bbb-home-obsession__spice`, `.bbb-home-obsession__meta`, `.bbb-home-obsession__shelf`, `.bbb-home-obsession__line`, `.bbb-home-obsession__tropes`, `.bbb-home-obsession__trope`, `.bbb-home-obsession__copy`, `.bbb-home-obsession__kicker`, `.bbb-home-obsession__title`, `.bbb-home-obsession__sub`

**Decoration:** `::after` pseudo-element on `.bbb-home-obsession__inner` draws a pink angled gradient bar. `.bbb-home-obsession__sparkles` contains 12 `✦ ✧ ⋆` spans with individual `@keyframes bbbHomeObsessionSparkle` delays.

**Background:** `#0b0b0b` (dark). Section divider is a pink gradient rule above it.

**Grid:** `grid-template-columns: minmax(250px, 280px) minmax(320px, 520px)` — cover left, text right. Mobile: stacks to 1 column with copy on top, cover below.

### JS Behavior
None. All rendering is server-side Liquid / PHP.

### WordPress Implementation
- **Type:** Template part `template-parts/home/weekly-obsession.php`
- **ACF fields on `bbb_newsletter`:** `publish_date` (date/timestamp), `featured_book` (relationship → `bbb_book`), `library_book` (relationship → `bbb_book` fallback), `title`, `subtitle`, `issue_no`, `label`, `url`
- **ACF fields on `bbb_book`:** `cover` (image), `spice_level` (number), `shelf` (taxonomy `bbb_shelf`), `tropes` (taxonomy `bbb_trope`)
- **Trope pill colors:** Port `trope-pill-colors.liquid` as a PHP function `bbb_get_trope_colors($handle)` returning `['bg' => '#hex', 'text' => '#hex']`; inline as `style="--trope-bg: ...; --trope-text: ...;"` on each pill
- **Section visibility:** Wrap entire output in `if ($current_issue && $featured_book)` check
- **Image:** Use `wp_get_attachment_image_srcset()` for responsive cover; sizes attr `"(max-width: 749px) 78vw, 280px"`
- **Spice:** Loop `spice_level` times, output 🌶️ each iteration
- **CSS:** Port `<style>` verbatim → `assets/css/home-weekly-obsession.css`

---

---

## Section 3 — Trending Romance Reads

**File:** `sections/trending-romance-reads.liquid`

### Settings / Default Text
| Setting | Default |
|---------|---------|
| `kicker` | (set in index.json settings) |
| `title` | (set in index.json settings) |

### Dynamic Data Query
This is the most complex server-side section. Two data passes:

**Pass 1 — Current month Sunday mapping:**
```
Counts Sundays in current month.
Maps books from sss_library to newsletter issue type by which Sunday slot they occupy:
  1st Sunday = "smutty"
  2nd Sunday = "sentimental"
  3rd Sunday = "trope report"
  4th Sunday = "extra extra" (only if 5 Sundays that month) OR "chapter's end"
  5th Sunday = "chapter's end"

For each Sunday slot, finds the library book whose newsletter_issue date matches that Sunday.
Renders sss-book-card (mini:true) for matched books.
Renders a shimmer placeholder card for future Sunday slots (shows "revealing [date]").
```

**Pass 2 — Supabase JS reorder:**
After DOM is painted, inline `<script>` queries Supabase `book_saves_recent_rollup` table, reorders cards by save count, dispatches `sss:trending-updated` event.

**WordPress equivalent:**
```php
// PHP: compute Sundays in current month, map to newsletter issues
function bbb_get_current_month_sundays(): array { ... }

// For each Sunday, query:
$args = [
  'post_type'  => 'bbb_newsletter',
  'meta_query' => [['key' => 'publish_date', 'value' => $sunday_timestamp, 'compare' => '=']],
];
// Then get linked bbb_book → render card or shimmer placeholder

// JS: same Supabase reorder logic as sss-library.js loadTrending()
// Queries book_saves_recent_rollup, reorders DOM cards by saves_last_30_days
```

### CSS Classes
`.bbb-trending`, `.bbb-trending__inner`, `.bbb-trending__head`, `.bbb-trending__kicker`, `.bbb-trending__title`, `.bbb-trending__sub`, `.bbb-trending__row`, `.bbb-trending__book`, `.bbb-trending__placeholder`, `.bbb-trending__cta`, `.bbb-trending__ctaSecondary`

**Placeholder shimmer:** `.bbbRevealShimmer` — `@keyframes bbbRevealShimmer` gradient sweep animation. Shows "revealing [date]" text for future slots.

**Cascade stagger:** JS `IntersectionObserver` — when `.bbb-trending__row` enters viewport, iterates child cards and applies `opacity: 1; transform: translateY(0)` with 120ms delay between each. Threshold: 0.25.

**Data attribute:** `data-sss-lib="public"` on `.bbb-trending__row` — this is the init hook for `sss-library.js`.

### JS Behavior
1. `sss-library.js` inits on `[data-sss-lib="public"]` — binds book card clicks → open modal, heart saves, etc.
2. Inline Supabase JS reorders cards by trending save counts
3. IntersectionObserver stagger animation (120ms per card, threshold 0.25)
4. Loads: `sss-library.css`, Supabase CDN, `sss-library.js`

### WordPress Implementation
- **Type:** Template part `template-parts/home/trending.php` + enqueue `sss-library.js`
- **PHP logic:** `bbb_get_current_month_sundays()` helper function; loop Sundays; for each, query `bbb_newsletter` by `publish_date` meta; get linked `bbb_book`; render `template-parts/book-card.php` (mini mode) or shimmer placeholder
- **Book card:** Must output all 20+ `data-*` attributes (see `sss-book-card.liquid` full attribute list)
- **Supabase reorder:** Port inline `<script>` from Liquid verbatim (replace Supabase credentials with `wp_localize_script` vars)
- **Init hook:** Ensure `.bbb-trending__row` has `data-sss-lib="public"` for library JS
- **Modal:** Include `template-parts/library-modal.php` (one per page) — equivalent of `sss-library-modal.liquid`
- **Stagger JS:** Port IntersectionObserver stagger into `assets/js/home-trending.js` or add to `sss-library.js` init

---

---

## Section 4 — Browse by Trope

**File:** `sections/browse-by-trope.liquid`

### Settings / Default Text
| Setting | Default |
|---------|---------|
| `kicker` | "romance navigation" |
| `title` | "browse by trope" |
| `subtext` | "find your next obsession by trope." |

**Block settings (per trope card):** `title` (trope name), `emoji` (for falling animation), `link` (blog post URL)

**Spice callout (hardcoded):**  
- Text: "want the exact spice level? browse romance by spice level →"
- Link: `/pages/romance-books-by-spice-level`

### Dynamic Data Query
**None.** All trope cards are manually configured as Shopify blocks (equivalent to ACF repeater rows). No database query.

### CSS Classes
**Section:** `.bbb-tropes`, `.bbb-tropes__inner`, `.bbb-tropes__row`, `.bbb-tropes__titleWrap`, `.bbb-tropes__kicker`, `.bbb-tropes__title`, `.bbb-tropes__grid`

**Spice callout:** `.bbb-spiceCallout`, `.bbb-spiceCallout__rain`, `.bbb-spiceCallout__kicker`, `.bbb-spiceCallout__text`

**Trope cards:** `.bbb-trope-card`, `.bbb-trope-card__label`, `.bbb-trope-card__title`, `.bbb-trope-card__arrow`, `.bbb-emoji-rain`

**Animations:**
- `@keyframes tropeFall` — card emoji float down on hover
- `@keyframes tropeNudge` — first card nudges left→right on load (2×, 1s delay) as scroll hint
- `@keyframes tropeHighlight` — `.bbb-tropes__title::after` pink underline grows from 0 → 60% width
- `@keyframes bbbSpiceFall` — 5 🌶 emojis fall across spice callout banner

**Card size:** `flex: 0 0 150px; height: 190px` — horizontal scroll on mobile

### JS Behavior
Inline `<script>`: for each `.bbb-trope-card`, reads `data-emoji`, creates 10 `<span>` elements inside `.bbb-emoji-rain`, randomizes `left` position and `animationDuration` (4–8s) and `animationDelay` (0–4s). Runs on page load (no scroll trigger).

### WordPress Implementation
- **Type:** ACF block `bbb/trope-browse` OR template part with ACF Options Page repeater
- **ACF repeater `trope_cards`:** subfields `title`, `emoji`, `link`
- **PHP:** Loop repeater rows → output `.bbb-trope-card` links
- **Spice callout:** Hard-code the banner HTML; make URL configurable via ACF Options if needed
- **JS:** Copy the emoji-rain inline `<script>` into `assets/js/home-tropes.js`; enqueue on front page
- **CSS:** Port verbatim → `assets/css/home-tropes.css`
- **Note:** Title uses `position: relative` for the `::after` underline — ensure no parent `overflow: hidden` clips it

---

---

## Section 5 — Featured Romance Lists

**File:** `sections/featured-romance-lists.liquid`

### Settings / Default Text
| Setting | Default |
|---------|---------|
| `kicker` | "reader favorites" |
| `title` | "featured romance lists" |
| `subtext` | "quick romance reading lists for when you're not sure what to read next." |
| `blog` | (Shopify blog handle, e.g. `"romance-lists"`) |

### Dynamic Data Query
```liquid
{% assign posts = blogs[blog_handle].articles %}
{% for article in posts limit: 5 %}
  -- Renders article.url and article.title
{% endfor %}
```
Pulls the 5 most recent posts from a specific Shopify blog. No metadata beyond URL and title.

**WordPress equivalent:**
```php
$args = [
  'post_type'      => 'post',
  'category_name'  => 'romance-lists', // or custom taxonomy term
  'posts_per_page' => 5,
  'orderby'        => 'date',
  'order'          => 'DESC',
];
$lists = get_posts($args);
```

### CSS Classes
`.bbb-romance-lists`, `.bbb-romance-lists__inner`, `.bbb-romance-lists__head`, `.bbb-romance-lists__kicker`, `.bbb-romance-lists__title`, `.bbb-romance-lists__sub`, `.bbb-romance-lists__shelf`, `.bbb-romance-lists__spine`, `.bbb-romance-lists__number`, `.bbb-romance-lists__name`, `.bbb-romance-lists__cta`

**Animations:**
- `@keyframes listsHeadDrop` — header slides down from `translateY(-2.4rem)` opacity 0 → 1 (0.75s)
- `@keyframes spineDrop` — each spine drops from `translateY(-28px) scale(.96)` to normal; staggered 0.1s per item (5 items = delays .1s through .5s)

**Hover:** `.bbb-romance-lists__spine:hover` — `translateX(4px)`, pink border `#ff8ac7`, pink box-shadow

### JS Behavior
None. Pure CSS animations triggered on page load (no scroll trigger — animations fire immediately when element renders).

### WordPress Implementation
- **Type:** Template part `template-parts/home/romance-lists.php`
- **Data source:** Either a dedicated WP category `romance-lists` or a custom post type `bbb_list`; query 5 most recent posts
- **CTA link:** `/blog/romance-lists` (or equivalent archive URL) — make configurable in ACF Options
- **CSS:** Port verbatim → `assets/css/home-romance-lists.css`
- **Note:** The "stacking" visual is achieved purely by overlapping negative `margin-top: -6px` on `.bbb-romance-lists__spine` — no special rendering logic needed

---

---

## Section 6 — Society Obsessed Preview (Library Preview)

**File:** `sections/homepage-library-preview.liquid`  
**Schema name:** "Society Obsessed Preview"

### Settings / Default Text
| Setting | Default |
|---------|---------|
| `demo_kicker` | "what to read next" |
| `demo_title` | "for the bookaholics who love romance" |
| `demo_subtext` | "pick one book and watch the next recommendation slide into place based on shelf chemistry, tropes, and mood." |
| `demo_button` | "try the rec engine →" |
| `demo_link` | `https://bybookishbabe.com/pages/what-to-read-next` |
| `demo_pick_title` | "daggermouth" |
| `demo_pick_meta` | "dystopian romance + enemies to lovers" |
| `demo_result_title` | "until i die" |
| `demo_result_meta` | "closest match • dystopian romance + enemies to lovers" |
| `demo_pick_cover` | (image picker — book cover image) |
| `demo_result_cover` | (image picker — book cover image) |

### Dynamic Data Query
**Two passes:**

**Pass 1 — Demo book covers (server-side):**
Looks up `demo_pick_title` and `demo_result_title` by title/handle in `sss_library` metaobjects to resolve cover image URLs if no image picker image is set.

**Pass 2 — Top shelf books (server-side):**
```liquid
Paginate sss_library by 250.
Filter: sss-book-visible == true AND sss-book-private != true AND book.top_shelf.value == true
Render first 5 matching books as sss-book-card (mini:true) into #sssPreviewTrending
```

**Pass 3 — Supabase trending reorder (client-side JS):**
```js
// Queries book_saves_recent_rollup for top 5 by saves_last_30_days
// Reorders DOM cards in #sssPreviewTrending to match trending order
// Falls back to top_shelf order if Supabase fails
```

**WordPress equivalents:**
```php
// Pass 1: get_posts for bbb_book matching demo title, get ACF cover field
// Pass 2: get_posts for bbb_book where ACF field top_shelf == true, limit 5
// Pass 3: same Supabase JS inline script, localize credentials via wp_localize_script
```

### CSS Classes
**Demo widget:** `.bbb-homeRecDemo`, `.bbb-homeRecDemo__copy`, `.bbb-homeRecDemo__kicker`, `.bbb-homeRecDemo__title`, `.bbb-homeRecDemo__sub`, `.bbb-homeRecDemo__cta`, `.bbb-homeRecDemo__stage`, `.bbb-homeRecDemo__book`, `.bbb-homeRecDemo__book--picked`, `.bbb-homeRecDemo__book--result`, `.bbb-homeRecDemo__label`, `.bbb-homeRecDemo__meta`, `.bbb-homeRecDemo__bookTitle`, `.bbb-homeRecDemo__bookLine`

**Library preview wrapper:** `.sss-lib--preview` (modifier on `.sss-lib`), `.sss-lib__archiveHead`, `.sss-lib__archiveKicker`, `.sss-lib__archiveTitle`, `.sss-lib__archiveSub`, `.sss-lib__shelf`, `.sss-lib__shelfRow`, `.sss-lib__previewLink`

**Animation:** `@keyframes bbbHomeRecSlide` — "result" book slides in from `translate(34px, 18px) rotate(-4deg) opacity .22` → resting position; loop: 0–18% at offset, 34–100% at rest (creates a "sliding in" peek effect).

**Mobile stacking:** On mobile, the 5 book cards overlap with negative `margin-left: -20px` and alternating `rotate/translateY` transforms creating a fan/stack visual.

### JS Behavior
1. `sss-library.js` inits on `data-sss-lib="public"` — full book card + modal interaction
2. Inline `loadPreviewTrending()` — async Supabase fetch → reorders `#sssPreviewTrending` cards
3. Dispatches `sssPreviewReady` custom event; listener in same script does the reorder

### WordPress Implementation
- **Type:** Template part `template-parts/home/library-preview.php`
- **ACF fields on `bbb_book`:** `top_shelf` (true/false), `cover`, `title`, `author`, `tropes`, plus all other card data-attrs
- **Demo widget:** Make `demo_pick_title`, `demo_result_title`, cover images, copy text all editable via ACF Options Page fields
- **Book cards:** Render `template-parts/book-card.php` (mini mode) with `data-sss-lib="public"` on row container
- **Supabase reorder:** Port `loadPreviewTrending()` into `assets/js/home-library-preview.js`; credentials via `wp_localize_script('bbb_supabase', 'bbbSupabase', ['url' => ..., 'key' => ...])`
- **Modal:** Include `template-parts/library-modal.php` (deduplicate — only one modal per page needed)
- **CSS:** Port modifier styles for `.sss-lib--preview` into `assets/css/home-library-preview.css`

---

---

## Section 7 — Society Hero / Newsletter CTA

**File:** `sections/society-hero.liquid`  
**Schema name:** "newsletter cta"

### Settings / Default Text
| Setting | Default |
|---------|---------|
| `kicker` | "for the bookaholics who love romance" |
| `title` | "the smut & sentiment society" |
| `subtitle` | "weekly letters, obsessive recs, and reader-core you pretend you're not addicted to." |
| `society_title` | "inside the society" |
| `society_text` | "the archive. reading lists. the fictional men problem. a tasteful amount of chaos." |
| `society_url` | `/pages/smut-sentiment-society` |

### Dynamic Data Query
```liquid
-- Iterates all newsletter_issue metaobjects
-- Finds latest where publish_date + 36000 <= now (same 10h logic as section 2)
-- Resolves: latest_issue.title, .subtitle, .publish_date, .issue_no, .label, .url, .preview (image)
-- is_new = true if live_ts is within last 7 days (604800s) → shows "new" badge
```

**WordPress equivalent:**
```php
// Same query as Section 2's newsletter fetch
// Additional: check if post_date within last 7 days → $is_new = true
$diff = time() - (get_field('publish_date', $issue->ID) + 36000);
$is_new = $diff < 604800;
```

### CSS Classes
**Section:** `.bbb-newsletter-cta`, `.bbb-newsletter-cta__wrap`, `.bbb-newsletter-cta__head`, `.bbb-newsletter-cta__kicker`, `.bbb-newsletter-cta__title`, `.bbb-newsletter-cta__sub`, `.bbb-newsletter-cta__grid`, `.bbb-newsletter-cta__rain`

**Cards:** `.bbb-nc`, `.bbb-nc--latest`, `.bbb-nc--society`, `.bbb-nc__latestLink`, `.bbb-nc__societyLink`, `.bbb-nc__latest`, `.bbb-nc__meta`, `.bbb-nc__top`, `.bbb-nc__kicker`, `.bbb-nc__badge`, `.bbb-nc__rule`, `.bbb-nc__img`, `.bbb-nc__title`, `.bbb-nc__desc`, `.bbb-nc__link`, `.bbb-nc__link--primary`, `.bbb-nc__fineprint`, `.bbb-nc__societyInner`

**Layout:** 2-column grid `1.15fr .85fr` — latest newsletter left, society CTA right. Stacks to 1 column below 860px. Left card has `border-radius: 22px 0 0 22px`; right card `0 22px 22px 0`.

**Emoji rain:** `.bbb-newsletter-cta__rain` div populated by JS with 26 `.bbb-rain-emoji` spans. Each has CSS custom properties `--dur`, `--size`, `--drift`, `--rot`. `@keyframes bbb-fall` translates to `120vh` with drift and rotation.

**Emojis used:** `['📚','🖤','🤍','📖','✨']`

### JS Behavior
Inline IIFE immediately after section HTML:
- Selects `.bbb-newsletter-cta__rain` within section
- Creates 26 emoji spans with randomized: `left` (0–100%), `--size` (14–32px), `--dur` (18–40s), `--drift` (-60 to 60px), `--rot` (-70 to 70deg), `animationDelay` (negative = pre-offset so rain is already falling on load)

### WordPress Implementation
- **Type:** Template part `template-parts/home/newsletter-cta.php`
- **ACF fields on `bbb_newsletter`:** `publish_date`, `title`, `subtitle`, `issue_no`, `label`, `url` (external Substack URL), `preview` (image field)
- **"New" badge:** `if (time() - (get_field('publish_date') + 36000) < 604800)`
- **Society card:** Static copy editable via ACF Options Page: `newsletter_society_title`, `newsletter_society_text`, `newsletter_society_url`
- **Emoji rain JS:** Port the inline IIFE into `assets/js/newsletter-rain.js`; enqueue on front page and any page using this section
- **CSS:** Port verbatim → `assets/css/home-newsletter-cta.css`
- **Accessibility note:** Rain div has `aria-hidden="true"` — preserve this

---

---

## Section 8 — Quiz Nudge

**File:** `sections/bbb-quiz-nudge.liquid`

### Settings / Default Text
| Setting | Default |
|---------|---------|
| `kicker` | "reader quiz" |
| `script_word` | "who's your" |
| `main_word` | "fictional boyfriend?" |
| `subtext` | "answer five questions. meet your fictional boyfriend. get a book you'll ruin your sleep over." |
| `button_label` | "take the quiz →" |
| `tiny_note` | "quick. dramatic. painfully accurate." |
| `quiz_url` | `/pages/reader-quizes` (fallback) |
| `accent` | `#ff8ac7` |

### Dynamic Data Query
**None.** Fully static CTA. Only has `request.page_type == 'index'` guard to limit to homepage.

### CSS Classes
`.bbb-quiz-nudge`, `.bbb-quiz-nudge__wrap`, `.bbb-quiz-nudge__left`, `.bbb-quiz-nudge__kicker`, `.bbb-quiz-nudge__title`, `.bbb-quiz-nudge__script`, `.bbb-quiz-nudge__main`, `.bbb-quiz-nudge__sub`, `.bbb-quiz-nudge__right`, `.bbb-quiz-nudge__btn`, `.bbb-quiz-nudge__tiny`

**Title structure:** Two `<span>` elements — `.bbb-quiz-nudge__script` (italic serif, 26px) + `.bbb-quiz-nudge__main` (lowercase, 20px) displayed `align-items: baseline` side by side.

**Background pattern:** `::before` pseudo-element with `radial-gradient` + `repeating-linear-gradient` horizontal rule lines (creates notebook paper effect).

**Button:** accent color `#ff8ac7` background, hover `translateY(-2px)` + wider `letter-spacing`.

### JS Behavior
None.

### WordPress Implementation
- **Type:** Template part `template-parts/home/quiz-nudge.php`
- **ACF Options Page fields:** `quiz_kicker`, `quiz_script_word`, `quiz_main_word`, `quiz_subtext`, `quiz_button_label`, `quiz_tiny_note`, `quiz_url`, `quiz_accent_color`
- **Page guard:** In PHP: `if (is_front_page())` — only render on homepage
- **CSS:** Port verbatim → `assets/css/home-quiz-nudge.css`; note the `accent` color is output as an inline style on the button — replicate with PHP `echo esc_attr(get_field('quiz_accent_color'))`

---

---

## Section 9 — Threads Social Post (Custom Liquid)

**Source:** Inline HTML in `templates/index.json` under the third `custom-liquid` section entry.

### Settings / Default Text
This section embeds a Threads social post embed widget. It is a static HTML embed (third-party `<blockquote>` + `<script>` tag from Threads/Instagram) with no Shopify-specific data.

### Dynamic Data Query
**None.** Static third-party embed.

### CSS Classes
Depends on the embedded post's own markup. The wrapping `<div>` in the Liquid may have a `bbb-threads` class.

### JS Behavior
Third-party Threads embed script (`//www.threads.net/embed.js` or equivalent). Loads asynchronously.

### WordPress Implementation
- **Type:** ACF block or Custom HTML widget block in the block editor
- **Implementation:** Use a standard WordPress Custom HTML block or a `bbb/social-embed` ACF block with a `post_embed_url` field
- **Note:** Threads embed markup can be pasted directly. If updating, replace the `<blockquote>` src. No PHP logic needed.
- **Priority:** Low — cosmetic, easily replaceable.

---

---

## Section 10 — Homepage Book Boyfriends

**File:** `sections/homepage-book-boyfriends.liquid`

### Settings / Default Text
| Setting | Default |
|---------|---------|
| `kicker` | "society logic" |
| `title` | "if you love one book boyfriend, you'll want these too" |
| `subtext` | "a rotating stack of fictional men and the books that match their energy." |
| `max_slides` | `4` (range: 2–8) |

### Dynamic Data Query
This is the second most complex section after Trending.

```liquid
-- Iterates sss_library, filters:
    sss-book-visible == true
    book.boyfriend_name.value != blank
    book.boyfriend_type.value != blank
-- Takes first N (max_slides) matching as "lead" books (one per slide)

-- For each lead book, finds "similar" books:
    sss-book-visible == true
    NOT the lead book itself
    boyfriend_type.value != blank
    Matches IF: boyfriend_type == lead's boyfriend_type
                OR same shelf as lead book
-- Takes first 2 similar matches

-- Each slide: data-boyfriend-slide, .is-active on first
-- Renders: sss-book-card (mini) for lead + up to 2 similar books
-- Text: "if [boyfriend_name] is your type" / "[boyfriend_type] energy"
-- Also includes: sss-library-modal snippet
```

**WordPress equivalent:**
```php
// Query bbb_book where boyfriend_name != '' AND boyfriend_type != ''
$leads = get_posts([
  'post_type' => 'bbb_book',
  'meta_query' => [
    ['key' => 'boyfriend_name', 'compare' => 'EXISTS'],
    ['key' => 'boyfriend_type', 'compare' => 'EXISTS'],
  ],
  'posts_per_page' => $max_slides,
]);

// For each lead, find similar:
// Same boyfriend_type OR same bbb_shelf taxonomy term
// Exclude lead book itself
// Limit 2
```

### CSS Classes
**Section:** `.bbb-boyfriends`, `.bbb-boyfriends__inner`, `.bbb-boyfriends__head`, `.bbb-boyfriends__kicker`, `.bbb-boyfriends__title`, `.bbb-boyfriends__sub`

**Rotator:** `.bbb-boyfriends__rotator` (`#bbbBoyfriendRotator`), `.bbb-boyfriends__slide` (hidden by default), `.bbb-boyfriends__slide.is-active` (displayed)

**Slide interior:** `.bbb-boyfriends__label` (large "if [name] is your type" text; name in `.bbb-boyfriends__label span` = pink Kaushan Script), `.bbb-boyfriends__type`, `.bbb-boyfriends__row`, `.bbb-boyfriends__lead`, `.bbb-boyfriends__arrow`, `.bbb-boyfriends__similar`, `.bbb-boyfriends__similarItem`, `.bbb-boyfriends__similarItem--wiggle`, `.bbb-boyfriends__meta`

**Animations:**
- `@keyframes bbbBoyfriendFade` — active slide fades in `opacity:0; translateY(8px) → normal` (0.45s)
- `@keyframes bbbArrowNudge` — arrow pings right (+3px) on loop (3.2s)
- `@keyframes bbbBoyfriendWiggle` — first similar card wobbles slightly (5.5s loop, 1.2s delay)

**Font:** `.bbb-boyfriends__label span` uses `'Kaushan Script', cursive` for the boyfriend name — ensure this font is loaded.

### JS Behavior
Inline IIFE rotator:
- Selects all `[data-boyfriend-slide]` slides
- Auto-advances every **18 seconds** (`setInterval(next, 18000)`)
- Pauses on `mouseenter` / resumes on `mouseleave`
- Permanently stops on `pointerdown`, `click`, or `focusin` (user interaction)
- `show(nextIndex)` toggles `.is-active` class; triggers `bbbBoyfriendFade` CSS animation

Also loads: `sss-library.css`, Supabase CDN, `sss-library.js` (for book card + modal interaction on `data-sss-lib="public"`)

### WordPress Implementation
- **Type:** Template part `template-parts/home/book-boyfriends.php`
- **ACF fields on `bbb_book`:** `boyfriend_name` (text), `boyfriend_type` (text), plus all standard card fields
- **Lead query:** `bbb_book` posts where both `boyfriend_name` and `boyfriend_type` ACF fields are non-empty; limit to `max_slides` (set via ACF Options)
- **Similar query:** For each lead, query books with matching `boyfriend_type` OR same `bbb_shelf` taxonomy term; exclude lead; limit 2
- **Book cards:** Render with `template-parts/book-card.php` (mini mode); output all `data-*` attributes
- **Rotator JS:** Port the inline IIFE into `assets/js/home-boyfriends.js`; 18s interval
- **Modal:** Section ends with `{% render 'sss-library-modal' %}` — include `template-parts/library-modal.php` here (or rely on single page-level include)
- **Font:** Ensure `Kaushan Script` is loaded via Google Fonts in `functions.php` alongside other theme fonts
- **`data-sss-lib`:** Section wrapper needs `data-sss-lib="public"` for library JS init

---

---

## Section 11 — Connect Cards

**File:** `sections/bbb-connect-cards.liquid`

### Settings / Default Text
| Setting | Default |
|---------|---------|
| `script_word` | "come" |
| `main_word` | "closer" |
| `subtext` | "book recs, newsletter notes, and all the reader-life chaos in the places i actually post." |

**Block defaults (TikTok card):**  
- `platform_label`: "tiktok", `handle`: "@bybookishbabe", `account`: "daily", `category`: "book recs · reading vlogs · library chaos", `status`: "fast takes and reader spirals", `link`: `https://www.tiktok.com/@bybookishbabe`

**Block defaults (Substack card):**  
- `platform_label`: "substack", `handle`: "the smut & sentiment society", `tier`: "free or society", `sundays`: "one curated romance recommendation", `perks`: "private notes · polls · extra reader bits", `link`: Substack subscribe URL

**Block defaults (Instagram card):**  
- `platform_label`: "instagram", `handle`: "@bybookishbabe", `style`: "shelf styling · current reads · pretty proof", `vibe`: "books, mood, and newsletter-adjacent things", `status`: "slower reader life updates", `link`: `https://www.instagram.com/bybookishbabe/`

### Dynamic Data Query
**None.** All content is manually configured. No database queries.

### CSS Classes
**Section:** `.bbb-connect-cards`, `.bbb-connect-cards__inner`, `.bbb-connect-cards__header`, `.bbb-connect-cards__title`, `.bbb-lets` (Great Vibes script font, large), `.bbb-word`, `.bbb-connect-cards__sub`, `.bbb-card-wrap`, `.bbb-card-row`, `.bbb-swipe-hint`, `.bbb-dot`, `.bbb-hint-text`

**Cards:** `.libcard`, `.libcard.is-substack`, `.libcard__paper`, `.libcard__hole`, `.libcard__head`, `.libcard__platform`, `.libcard__ico`, `.libcard__handle`, `.libcard__body`, `.libcard__row`

**Layout:** Desktop 3-column grid. Mobile: horizontal scroll `flex-wrap: nowrap; overflow-x: auto; scroll-snap-type: x mandatory` — cards `flex: 0 0 72%`. Swipe hint (dots + text) shown on mobile only.

**Hover effect:** `.libcard:hover .libcard__paper` → background becomes `#ff8ac7`, `background-image: none`, all text stays `#111`. Very bold brand moment.

**Animations:**
- `.bbb-connect-cards__inner`: slides in from `translateX(-4.2rem)` opacity 0 → 1 (0.9s cubic-bezier)
- Each `.libcard`: fades + slides up from `translateY(26px)` with `--bbb-card-delay` CSS variable; TikTok: 0.45s, Substack: 0.82s, Instagram: 1.19s

**Fonts:** `Great Vibes` (script word), `Cormorant` (body) — loaded via `@import` within the style block

**CSS scoping:** All rules scoped to `#bbb-connect-{{ sid }}` to avoid conflicts.

### JS Behavior
None — CSS-only interactions. Swipe on mobile is native browser scroll-snap.

### WordPress Implementation
- **Type:** Template part `template-parts/home/connect-cards.php`
- **ACF Options fields:** `connect_script_word`, `connect_main_word`, `connect_subtext`; plus per-platform settings: `tiktok_handle`, `tiktok_link`, `tiktok_account`, `tiktok_category`, `tiktok_status`; same pattern for Substack and Instagram
- **Section ID scoping:** Replace `{{ section.id }}` with `bbb-home` (or generate a unique ID via `uniqid()` if section is reused)
- **SVG icons:** Port the inline SVG paths verbatim — TikTok, Substack envelope, Instagram icons
- **Fonts:** Add `Great Vibes` and `Cormorant` to the existing Google Fonts enqueue in `functions.php`
- **CSS:** Port verbatim → `assets/css/home-connect-cards.css`; replace `{{ sid }}` references with static class or ID

---

---

## Disabled Sections (Reference Only)

These sections exist in `index.json` but have `"disabled": true`. Do not implement until enabled.

| Section Key | What It Was |
|-------------|-------------|
| `image_banner` | Full-width image hero (Shopify native) — replaced by hero-smut-sentiment |
| `sss-join-nudge` | Society join CTA — functionality covered by society-hero section |
| `bbb-new-this-month` | "New this month" book shelf row — likely superseded by trending section |
| `custom-liquid` Bestsellers | External bestseller list embed (Amazon?) — TBD |
| `custom-liquid` Review Banner | Static review pull-quote banner — TBD |

---

---

## Global Implementation Notes

### Assets to Port
Every section that needs it loads these two assets — they are **global** and should be enqueued site-wide (or at minimum on any page using book cards):

- `sss-library.css` → `assets/css/sss-library.css`
- `sss-library.js` → `assets/js/sss-library.js`
- Supabase CDN: `https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2`

### Library JS Init Pattern
Any section using `sss-library.js` must have `data-sss-lib="public"` on its root container. The JS reads this attribute to scope all event listeners and init behavior. If a section contains book cards and a modal, `data-sss-lib` must be present.

### Modal — One Per Page
`sss-library-modal.liquid` must be rendered **once per page** — not once per section. In WordPress, add `get_template_part('template-parts/library-modal')` at the bottom of `page-home.php` (or `footer.php` before `</body>`). Multiple modals on the same page will conflict.

### Supabase Credentials
Do **not** hard-code credentials in JS files. Use `wp_localize_script`:
```php
wp_localize_script('bbb-library', 'bbbSupabase', [
  'url' => 'https://efmrfxsmgbeikfgtrxjv.supabase.co',
  'key' => 'sb_publishable_iwjASe3QwixdDvHovaXZBQ_gbXU0Utk',
]);
```
Then reference `bbbSupabase.url` and `bbbSupabase.key` in JS.

### `window.BBBReaderAccount` Replacement
Shopify injects `window.BBBReaderAccount` from Liquid. In WordPress, inject this from PHP in `wp_head` or via `wp_localize_script`:
```php
$user = wp_get_current_user();
$is_society = in_array('bbb_society_member', $user->roles);
wp_localize_script('bbb-global', 'BBBReaderAccount', [
  'loggedIn'     => is_user_logged_in(),
  'customerId'   => $user->ID,
  'email'        => $user->user_email,
  'firstName'    => $user->first_name,
  'isSociety'    => $is_society,
  'bookshelfUrl' => '/my-bookshelf',
  'accountUrl'   => '/my-account',
  'loginUrl'     => wp_login_url(),
]);
```

### Fonts Required
All fonts must be enqueued in `functions.php`:
- `Libre Baskerville` (body serif)
- `Cormorant` (display serif)
- `Kaushan Script` (boyfriend name cursive)
- `Great Vibes` (connect cards script word)

### Background Color Convention
All homepage sections use `background: #0b0b0b` (near-black). Text defaults to `#f6f6f6`. Pink accent: `#ff8ac7`. Ensure global `body` background is set to `#0b0b0b` for the homepage.

### Recommended Build Order
1. **Hero** (Section 1) — no dependencies, sets visual baseline
2. **Connect Cards** (Section 11) — no dependencies, tests font/CSS enqueue
3. **Quiz Nudge** (Section 8) — no dependencies
4. **Browse by Trope** (Section 4) — JS emoji rain only
5. **Featured Romance Lists** (Section 5) — simple WP_Query
6. **Newsletter CTA / Society Hero** (Section 7) — requires `bbb_newsletter` CPT + ACF
7. **Weekly Obsession** (Section 2) — requires `bbb_newsletter` + `bbb_book` relationship
8. **Library Preview** (Section 6) — requires `bbb_book` CPT, `top_shelf` field, Supabase JS
9. **Trending** (Section 3) — requires `bbb_newsletter` Sunday mapping + Supabase
10. **Book Boyfriends** (Section 10) — requires `boyfriend_name/type` ACF fields + library JS
11. **Threads Embed** (Section 9) — static embed, add last
