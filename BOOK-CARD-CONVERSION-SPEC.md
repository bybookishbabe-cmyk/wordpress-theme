# Book Card / Metaobject → WordPress Conversion Spec
> Codex-ready. Do not redesign. Preserve all Shopify classes and JS data-* attributes exactly.
> Source folder: `/wordpress-theme/` — ignore `/wordpress-theme/firstpass/`

---

## 1. Exact DOM

### 1A. Library Card (`sss-book-card.liquid` → `template-parts/book-card/library-card.php`)

```html
<button
  type="button"
  class="sss-lib__book[ sss-lib__book--mini]"
  data-handle="{slug}"
  data-title="{title}"
  data-author="{author}"
  data-cover="{cover_url}"
  data-amazon="{amazon_url}"
  data-bookshop="{bookshop_url}"
  data-shelf="{shelf_name}"
  data-private-shelf="true|false"
  data-spice="{spice_level}"
  data-tropes="{trope1}, {trope2}"
  data-tropes-display="{emoji} {trope1}, {emoji} {trope2}"
  data-trope-urls="{/pages/trope-handle-books}, ..."
  data-why="{why_i_loved_it}"
  data-newsletter="{newsletter_url}"
  data-mini="{mini_note}"
  data-series="{series_handle}"
  data-series-name="{series_title}"
  data-series-number="{series_number}"
  data-tension="{tension_score}"
  data-damage="{emotional_damage_score}"
  data-yearning="{yearning_level}"
  data-boyfriend="{boyfriend_type}"
  data-boyfriend-name="{boyfriend_name}"
  data-reread="{reread_badge}"
  data-standalone="true|false"
  data-ku="true|false"
  data-darkness="{darkness_level}"
>
  <div class="sss-lib__coverWrap">

    <span class="sss-lib__heart" data-heart role="button" aria-label="save to your bookshelf">
      <span class="sss-lib__heartIcon" data-heart-icon aria-hidden="true">♡</span>
      <span class="sss-lib__heartLabel" data-heart-label>save</span>
    </span>

    <!-- Conditional: if series_number is set -->
    <span
      class="sss-lib__seriesBadge[ sss-lib__seriesBadge--standalone]"
      data-series-url="/pages/series?series={series_handle}"
      aria-label="open series page for {series_title}"
    >{series_number}</span>

    <!-- Conditional: if spice_level > 0 -->
    <div class="sss-lib__floatSpice">🌶🌶🌶</div><!-- repeat spice_level times -->

    <!-- Conditional: if cover_url -->
    <img class="sss-lib__cover" src="{cover_url}" alt="{title}" loading="lazy">

  </div>
  <div class="sss-lib__under">
    <div class="sss-lib__name">{title}</div>
    <div class="sss-lib__author">{author}</div>
  </div>
</button>
```

**Mini variant:** add class `sss-lib__book--mini` to the `<button>`. Triggered by passing `$mini = true` to renderer.

---

### 1B. Article/Blog Card (`blog-book-card.liquid` → `template-parts/book-card/article-card.php`)

```html
<div
  class="article-book-card"
  data-book-preview
  data-handle="{slug}"
  data-title="{title}"
  data-author="{author}"
  data-cover="{cover_url}"
  data-amazon="{amazon_url}"
  data-bookshop="{bookshop_url}"
  data-newsletter="{newsletter_url}"
  data-spice="{spice_level}"
  data-ku="true|false"
  data-tropes="{trope1}, {trope2}"
  data-tropes-display="{emoji} {trope1}, {emoji} {trope2}"
  data-mini="{mini_note}"
  data-why="{why_i_loved_it}"
  data-series="{series_handle}"
  data-series-name="{series_title}"
  data-series-number="{series_number}"
>

  <div class="article-book-card__header">
    <!-- Conditional: if shelf -->
    <div class="article-book-card__genreRow">
      <span class="article-book-card__genreLine" aria-hidden="true"></span>
      <span class="article-book-card__genre">{shelf_name}</span>
    </div>
    <h3>{title}</h3>
    <!-- Conditional: if author -->
    <div class="article-book-card__author">{author}</div>
    <!-- Conditional: if series_title or series_number -->
    <div class="article-book-card__series">
      <!-- if series_number: -->#{series_number} • <!-- end if -->
      {series_title}<!-- if series_title --> series<!-- end if -->
    </div>
  </div>

  <div class="article-book-card__image">
    <button type="button" class="article-book-card__heart" data-blog-heart aria-label="save to your bookshelf">
      <span class="article-book-card__heartIcon" aria-hidden="true">♡</span>
      <span class="article-book-card__heartLabel">save</span>
    </button>
    <!-- Conditional: if spice_level -->
    <div class="article-book-card__spice">🌶<!-- repeat spice_level times --></div>
    <!-- Conditional: if cover_url -->
    <img src="{cover_url}" alt="{title}" loading="lazy">
  </div>

  <div class="article-book-card__content">

    <!-- Conditional: if mini_note -->
    <p class="book-pitch">{mini_note}</p>

    <!-- Conditional: if $show_why AND why_i_loved_it -->
    <p class="book-pitch book-pitch--why">
      <span class="book-pitch__label">why i loved it</span>
      {why_i_loved_it}
    </p>

    <!-- Conditional: if tropes -->
    <div class="article-book-card__tropes">
      <!-- foreach trope: -->
      <span class="article-book-card__trope" style="--trope-bg: {bg}; --trope-text: {text};">{trope_name}</span>
    </div>

    <div class="article-book-card__ratings">
      <!-- Conditional: if ku === true -->
      <span class="article-book-card__ku article-book-card__ku--yes">✓ on kindle unlimited</span>
      <!-- Conditional: if ku === false (explicitly false, not null) -->
      <span class="article-book-card__ku article-book-card__ku--no">✕ not on kindle unlimited</span>
    </div>

    <div class="article-book-card__buttons">
      <!-- Conditional: if amazon_url -->
      <a class="article-book-card__button article-book-card__button--amazon" href="{amazon_url}" target="_blank" rel="noopener">amazon</a>
      <!-- Conditional: if bookshop_url -->
      <a class="article-book-card__button article-book-card__button--bookshop" href="{bookshop_url}" target="_blank" rel="noopener">
        <span class="article-book-card__buttonText article-book-card__buttonText--full">support local bookshop</span>
        <span class="article-book-card__buttonText article-book-card__buttonText--short">bookshop</span>
      </a>
    </div>

  </div>
</div>
```

---

### 1C. Modal (`sss-library-modal.liquid` — keep as-is in PHP, no DOM changes)

Render once per page. JS reads `data-*` from the clicked card and populates these targets:

| `data-*` selector | Populated with |
|---|---|
| `[data-mtitle]` | `data-title` |
| `[data-mauthor]` | `"by " + data-author` |
| `[data-mcover]` | `data-cover` (src + alt) |
| `[data-mmini]` | `"quick summary: " + data-mini` |
| `[data-mtropes]` | `"tropes: " + data-tropes-display` |
| `[data-mwhy]` | `data-why` |
| `[data-mku]` | KU yes/no string + classes `is-yes` / `is-no` |
| `[data-mseries]` | series link, hidden if no series |
| `[data-mseries-order]` | `"book " + data-series-number` |
| `[data-mstandalone]` | Warning if series_number === "1" |
| `[data-amazon-btn]` | `data-amazon` (href) |
| `[data-bookshop-btn]` | `data-bookshop` (href) |
| `[data-mtension]` | `data-tension` |
| `[data-mdamage]` | `data-damage` |
| `[data-myearning]` | `data-yearning` |
| `[data-mboyfriend]` | `data-boyfriend` + `data-boyfriend-name` |
| `[data-mreread]` | `"🌶 " + data-spice + "/5 spice"` |
| `[data-modal-heart]` | heart save toggle |
| `[data-modal-share-btn]` | share button |
| `[data-close]` | close modal |

---

## 2. Full `data-*` Contract

All attributes the JS reads from `.sss-lib__book` (library grid) and `[data-book-preview]` (article cards):

```
data-handle          string   post slug; used as identity key for localStorage
data-title           string   escaped book title
data-author          string   escaped author name
data-cover           string   absolute image URL
data-amazon          string   full URL (may be empty string)
data-bookshop        string   full URL (may be empty string)
data-shelf           string   shelf/genre name (for display only)
data-private-shelf   "true"|"false"  → drives isPrivateBookData()
data-spice           "1"–"5"|""
data-tropes          "Trope One, Trope Two"       comma-separated names (no emoji)
data-tropes-display  "emoji Trope One, Trope Two" comma-separated with emoji prefix
data-trope-urls      "/trope-handle-books, /..."  comma-separated, same order as data-tropes
data-why             string   escaped
data-newsletter      string   URL or empty
data-mini            string   escaped
data-series          string   series slug/handle
data-series-name     string   series title
data-series-number   string   "1", "2", etc.
data-tension         string|number
data-damage          string|number
data-yearning        string
data-boyfriend       string
data-boyfriend-name  string
data-reread          string
data-standalone      "true"|"false"
data-ku              "true"|"false"|""   empty = unknown; do not show badge if empty
data-darkness        string|number
```

**Article card only (not on library card):**
```
data-book-preview    attribute presence → JS binds click → opens modal
data-blog-heart      attribute presence → JS binds shelf save on .article-book-card__heart
```

**localStorage keys (do not rename):**
- `sssMyShelf` — array of saved book objects
- `sssShelf` — mirror of above (legacy)
- `sssBookStatuses` — object keyed by handle/title → `"read"|"reading"|"tbr"|"dnf"`
- `sssBookReactions` — object keyed by handle/title → reaction string
- `sssAnalyticsSessionId`, `sssAnalyticsExcluded`, `sssAnalyticsDailyVisit:{date}`

---

## 3. Metaobject Field → WordPress CPT/Meta/Taxonomy Mapping

### CPT: `bbb_book`  (slug: `book`)

| Shopify field | WordPress | Type |
|---|---|---|
| `book.system.handle` | post slug (`post_name`) | built-in |
| `book.title.value` | `post_title` | built-in |
| `book.author.value` | `_bbb_author` | post meta |
| `book.cover.value.url` | `_bbb_cover_url` | post meta (or featured image) |
| `book.amazon_link.value.url` | `_bbb_amazon_url` | post meta |
| `book.bookshop_link.value.url` | `_bbb_bookshop_url` | post meta |
| `book.spice_level.value` | `_bbb_spice` | post meta (int) |
| `book.tension_score.value` | `_bbb_tension` | post meta |
| `book.emotional_damage_score.value` | `_bbb_damage` | post meta |
| `book.yearning_level.value` | `_bbb_yearning` | post meta |
| `book.boyfriend_type.value` | `_bbb_boyfriend_type` | post meta |
| `book.boyfriend_name.value` | `_bbb_boyfriend_name` | post meta |
| `book.reread_badge.value` | `_bbb_reread` | post meta |
| `book.darkness_level.value` | `_bbb_darkness` | post meta |
| `book.mini_note.value` | `_bbb_mini_note` | post meta |
| `book.why_i_loved_it.value` | `_bbb_why` | post meta |
| `book.newsletter_url.value` | `_bbb_newsletter_url` | post meta |
| `book.on_kindle_unlimited.value` | `_bbb_ku` | post meta ("1"/"0"/"") |
| `book.read_as_standalone.value` | `_bbb_standalone` | post meta ("1"/"0") |
| `book.hide_from_library.value` | `_bbb_hide_from_library` | post meta ("1"/"0") |
| `book.featured_in_newsletter_date.value` | `_bbb_newsletter_date` | post meta (Y-m-d) |
| `book.series_number.value` | `_bbb_series_number` | post meta |
| `book.private_shelf.value` | `_bbb_private_shelf` | post meta ("1"/"0") |
| `book.series.value.system.handle` | `_bbb_series_handle` | post meta |
| `book.shelf.value.name` | term in taxonomy `bbb_shelf` | taxonomy |
| `book.tropes.value` (list) | terms in taxonomy `bbb_trope` | taxonomy |

### Taxonomy: `bbb_trope`
- term `name` → trope name
- term `slug` → trope handle (used for color lookup)
- term meta `trope_emoji` → emoji string

### Taxonomy: `bbb_shelf`
- term `name` → shelf name (e.g. "Contemporary Romance")
- term `slug` → shelf handle

### Taxonomy: `bbb_series`
- term `name` → series title
- term `slug` → series handle (matches `_bbb_series_handle`)

### CPT: `bbb_newsletter_issue` (if needed for import)
| Shopify field | WordPress | Type |
|---|---|---|
| handle | post slug | built-in |
| title/subject | `post_title` | built-in |
| publish date | `post_date` | built-in |
| url | `_bbb_newsletter_url` | post meta |

---

## 4. Visibility / Private Rules → PHP

### `sss-book-visible.liquid` → `inc/books/book-visibility.php`

```php
/**
 * Returns true if the book should be shown in the library.
 *
 * @param int  $post_id
 * @param bool $allow_hidden_from_library  Pass true to bypass hide_from_library check (e.g. admin previews).
 * @return bool
 */
function bbb_is_book_visible( int $post_id, bool $allow_hidden_from_library = false ): bool {
    if ( get_post_status( $post_id ) !== 'publish' ) {
        return false;
    }

    // Respect hide_from_library flag unless overridden
    if ( ! $allow_hidden_from_library ) {
        if ( get_post_meta( $post_id, '_bbb_hide_from_library', true ) === '1' ) {
            return false;
        }
    }

    // Newsletter unlock: books with a future date are hidden until 10:00 AM Pacific on that date.
    if ( ! $allow_hidden_from_library ) {
        $newsletter_date = get_post_meta( $post_id, '_bbb_newsletter_date', true );
        if ( $newsletter_date ) {
            // 10am Pacific = UTC+7 or UTC+8 depending on DST; use DateTimeZone.
            $tz      = new DateTimeZone( 'America/Los_Angeles' );
            $unlock  = new DateTime( $newsletter_date . ' 10:00:00', $tz );
            $now     = new DateTime( 'now', $tz );
            if ( $now < $unlock ) {
                return false;
            }
        }
    }

    return true;
}
```

### `sss-book-private.liquid` → `inc/books/book-visibility.php` (append)

```php
/**
 * Returns true if the book is on a private shelf.
 * Mirrors Shopify's multi-format private_shelf check.
 *
 * @param int $post_id
 * @return bool
 */
function bbb_is_book_private( int $post_id ): bool {
    $raw = get_post_meta( $post_id, '_bbb_private_shelf', true );
    if ( $raw === '' || $raw === null ) {
        return false;
    }
    $lower = strtolower( trim( (string) $raw ) );
    return in_array( $lower, [ '1', 'true', 'yes', 'private shelf' ], true );
}
```

---

## 5. Import / Export GraphQL Queries

Run these in Shopify Admin → GraphiQL app to export data before migration.

### Export: All `sss_library` books (paginated, 250/page)

```graphql
{
  metaobjects(type: "sss_library", first: 250) {
    edges {
      node {
        handle
        fields {
          key
          value
          type
          reference {
            ... on Metaobject {
              handle
              type
              fields { key value }
            }
            ... on MediaImage {
              image { url }
            }
          }
          references(first: 20) {
            edges {
              node {
                ... on Metaobject {
                  handle
                  type
                  fields { key value }
                }
              }
            }
          }
        }
      }
    }
    pageInfo { hasNextPage endCursor }
  }
}
```

For subsequent pages, add `after: "{endCursor}"` to the `metaobjects` args.

### Export: All `sss_trope` metaobjects

```graphql
{
  metaobjects(type: "sss_trope", first: 250) {
    edges {
      node {
        handle
        fields { key value }
      }
    }
  }
}
```

### Export: All series metaobjects

```graphql
{
  metaobjects(type: "sss_series", first: 250) {
    edges {
      node {
        handle
        fields { key value }
      }
    }
  }
}
```

### Export: Newsletter issues

```graphql
{
  metaobjects(type: "newsletter_issue", first: 250) {
    edges {
      node {
        handle
        fields { key value }
      }
    }
  }
}
```

---

## 6. Codex-Ready Renderer Functions

### `inc/books/book-renderers.php`

---

#### `bbb_get_book_data_attrs( int $post_id ): string`

Returns the full `data-*` attribute string for any book card.

```php
function bbb_get_book_data_attrs( int $post_id ): string {
    $slug    = get_post_field( 'post_name', $post_id );
    $title   = get_the_title( $post_id );
    $author  = get_post_meta( $post_id, '_bbb_author', true );
    $cover   = get_post_meta( $post_id, '_bbb_cover_url', true );
    $amazon  = get_post_meta( $post_id, '_bbb_amazon_url', true );
    $bookshop= get_post_meta( $post_id, '_bbb_bookshop_url', true );
    $mini    = get_post_meta( $post_id, '_bbb_mini_note', true );
    $why     = get_post_meta( $post_id, '_bbb_why', true );
    $newsletter = get_post_meta( $post_id, '_bbb_newsletter_url', true );
    $spice   = get_post_meta( $post_id, '_bbb_spice', true );
    $tension = get_post_meta( $post_id, '_bbb_tension', true );
    $damage  = get_post_meta( $post_id, '_bbb_damage', true );
    $yearning= get_post_meta( $post_id, '_bbb_yearning', true );
    $bftype  = get_post_meta( $post_id, '_bbb_boyfriend_type', true );
    $bfname  = get_post_meta( $post_id, '_bbb_boyfriend_name', true );
    $reread  = get_post_meta( $post_id, '_bbb_reread', true );
    $darkness= get_post_meta( $post_id, '_bbb_darkness', true );
    $ku_raw  = get_post_meta( $post_id, '_bbb_ku', true );
    $ku      = ( $ku_raw === '1' ) ? 'true' : ( ( $ku_raw === '0' ) ? 'false' : '' );
    $standalone_raw = get_post_meta( $post_id, '_bbb_standalone', true );
    $standalone = ( $standalone_raw === '1' ) ? 'true' : 'false';
    $private_raw = get_post_meta( $post_id, '_bbb_private_shelf', true );
    $private = bbb_is_book_private( $post_id ) ? 'true' : 'false';

    // Series
    $series_handle = get_post_meta( $post_id, '_bbb_series_handle', true );
    $series_number = get_post_meta( $post_id, '_bbb_series_number', true );
    $series_name   = '';
    if ( $series_handle ) {
        $series_term = get_term_by( 'slug', $series_handle, 'bbb_series' );
        if ( $series_term ) {
            $series_name = $series_term->name;
        }
    }

    // Shelf
    $shelf_terms = get_the_terms( $post_id, 'bbb_shelf' );
    $shelf_name  = ( $shelf_terms && ! is_wp_error( $shelf_terms ) ) ? $shelf_terms[0]->name : '';

    // Tropes
    $trope_terms   = get_the_terms( $post_id, 'bbb_trope' );
    $trope_names   = [];
    $trope_display = [];
    $trope_urls    = [];
    if ( $trope_terms && ! is_wp_error( $trope_terms ) ) {
        foreach ( $trope_terms as $t ) {
            $emoji = get_term_meta( $t->term_id, 'trope_emoji', true );
            $trope_names[]   = $t->name;
            $trope_display[] = trim( ( $emoji ? $emoji . ' ' : '' ) . $t->name );
            $trope_urls[]    = get_term_link( $t );
        }
    }

    $attrs = [
        'data-handle'        => $slug,
        'data-title'         => $title,
        'data-author'        => $author,
        'data-cover'         => $cover,
        'data-amazon'        => $amazon,
        'data-bookshop'      => $bookshop,
        'data-shelf'         => $shelf_name,
        'data-private-shelf' => $private,
        'data-spice'         => $spice,
        'data-tropes'        => implode( ', ', $trope_names ),
        'data-tropes-display'=> implode( ', ', $trope_display ),
        'data-trope-urls'    => implode( ', ', $trope_urls ),
        'data-why'           => $why,
        'data-newsletter'    => $newsletter,
        'data-mini'          => $mini,
        'data-series'        => $series_handle,
        'data-series-name'   => $series_name,
        'data-series-number' => $series_number,
        'data-tension'       => $tension,
        'data-damage'        => $damage,
        'data-yearning'      => $yearning,
        'data-boyfriend'     => $bftype,
        'data-boyfriend-name'=> $bfname,
        'data-reread'        => $reread,
        'data-standalone'    => $standalone,
        'data-ku'            => $ku,
        'data-darkness'      => $darkness,
    ];

    $parts = [];
    foreach ( $attrs as $key => $val ) {
        $parts[] = $key . '="' . esc_attr( $val ) . '"';
    }
    return implode( "\n  ", $parts );
}
```

---

#### `bbb_render_library_book_card( int $post_id, bool $mini = false ): string`

```php
function bbb_render_library_book_card( int $post_id, bool $mini = false ): string {
    if ( ! bbb_is_book_visible( $post_id ) ) {
        return '';
    }

    $cover   = get_post_meta( $post_id, '_bbb_cover_url', true );
    $title   = get_the_title( $post_id );
    $author  = get_post_meta( $post_id, '_bbb_author', true );
    $spice   = (int) get_post_meta( $post_id, '_bbb_spice', true );
    $series_handle = get_post_meta( $post_id, '_bbb_series_handle', true );
    $series_number = get_post_meta( $post_id, '_bbb_series_number', true );
    $standalone_raw = get_post_meta( $post_id, '_bbb_standalone', true );
    $is_standalone  = $standalone_raw === '1';

    $series_name = '';
    if ( $series_handle ) {
        $series_term = get_term_by( 'slug', $series_handle, 'bbb_series' );
        if ( $series_term ) $series_name = $series_term->name;
    }

    $mini_class   = $mini ? ' sss-lib__book--mini' : '';
    $data_attrs   = bbb_get_book_data_attrs( $post_id );
    $spice_html   = $spice > 0 ? '<div class="sss-lib__floatSpice">' . str_repeat( '🌶', $spice ) . '</div>' : '';
    $cover_html   = $cover ? '<img class="sss-lib__cover" src="' . esc_url( $cover ) . '" alt="' . esc_attr( $title ) . '" loading="lazy">' : '';

    $series_badge = '';
    if ( $series_number !== '' && $series_number !== null ) {
        $badge_class = 'sss-lib__seriesBadge' . ( $is_standalone ? ' sss-lib__seriesBadge--standalone' : '' );
        $series_badge = sprintf(
            '<span class="%s" data-series-url="/pages/series?series=%s" aria-label="open series page for %s">%s</span>',
            esc_attr( $badge_class ),
            esc_attr( $series_handle ),
            esc_attr( $series_name ),
            esc_html( $series_number )
        );
    }

    ob_start();
    ?>
<button
  type="button"
  class="sss-lib__book<?php echo esc_attr( $mini_class ); ?>"
  <?php echo $data_attrs; // already escaped ?>
>
  <div class="sss-lib__coverWrap">
    <span class="sss-lib__heart" data-heart role="button" aria-label="save to your bookshelf">
      <span class="sss-lib__heartIcon" data-heart-icon aria-hidden="true">♡</span>
      <span class="sss-lib__heartLabel" data-heart-label>save</span>
    </span>
    <?php echo $series_badge; ?>
    <?php echo $spice_html; ?>
    <?php echo $cover_html; ?>
  </div>
  <div class="sss-lib__under">
    <div class="sss-lib__name"><?php echo esc_html( $title ); ?></div>
    <div class="sss-lib__author"><?php echo esc_html( $author ); ?></div>
  </div>
</button>
    <?php
    return ob_get_clean();
}
```

---

#### `bbb_render_article_book_card( int $post_id, bool $show_why = false ): string`

```php
function bbb_render_article_book_card( int $post_id, bool $show_why = false ): string {
    $cover   = get_post_meta( $post_id, '_bbb_cover_url', true );
    $title   = get_the_title( $post_id );
    $author  = get_post_meta( $post_id, '_bbb_author', true );
    $spice   = (int) get_post_meta( $post_id, '_bbb_spice', true );
    $mini    = get_post_meta( $post_id, '_bbb_mini_note', true );
    $why     = get_post_meta( $post_id, '_bbb_why', true );
    $ku_raw  = get_post_meta( $post_id, '_bbb_ku', true );
    $amazon  = get_post_meta( $post_id, '_bbb_amazon_url', true );
    $bookshop= get_post_meta( $post_id, '_bbb_bookshop_url', true );
    $series_handle = get_post_meta( $post_id, '_bbb_series_handle', true );
    $series_number = get_post_meta( $post_id, '_bbb_series_number', true );
    $series_name   = '';
    if ( $series_handle ) {
        $t = get_term_by( 'slug', $series_handle, 'bbb_series' );
        if ( $t ) $series_name = $t->name;
    }

    $shelf_terms = get_the_terms( $post_id, 'bbb_shelf' );
    $shelf_name  = ( $shelf_terms && ! is_wp_error( $shelf_terms ) ) ? $shelf_terms[0]->name : '';

    $trope_terms = get_the_terms( $post_id, 'bbb_trope' );
    $data_attrs  = bbb_get_book_data_attrs( $post_id );

    ob_start();
    ?>
<div class="article-book-card" data-book-preview
  <?php echo $data_attrs; ?>>

  <div class="article-book-card__header">
    <?php if ( $shelf_name ) : ?>
    <div class="article-book-card__genreRow">
      <span class="article-book-card__genreLine" aria-hidden="true"></span>
      <span class="article-book-card__genre"><?php echo esc_html( $shelf_name ); ?></span>
    </div>
    <?php endif; ?>
    <h3><?php echo esc_html( $title ); ?></h3>
    <?php if ( $author ) : ?>
    <div class="article-book-card__author"><?php echo esc_html( $author ); ?></div>
    <?php endif; ?>
    <?php if ( $series_name || $series_number ) : ?>
    <div class="article-book-card__series">
      <?php if ( $series_number ) echo '#' . esc_html( $series_number ) . ' • '; ?>
      <?php echo esc_html( $series_name ); ?><?php if ( $series_name ) echo ' series'; ?>
    </div>
    <?php endif; ?>
  </div>

  <div class="article-book-card__image">
    <button type="button" class="article-book-card__heart" data-blog-heart aria-label="save to your bookshelf">
      <span class="article-book-card__heartIcon" aria-hidden="true">♡</span>
      <span class="article-book-card__heartLabel">save</span>
    </button>
    <?php if ( $spice > 0 ) : ?>
    <div class="article-book-card__spice"><?php echo str_repeat( '🌶', $spice ); ?></div>
    <?php endif; ?>
    <?php if ( $cover ) : ?>
    <img src="<?php echo esc_url( $cover ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy">
    <?php endif; ?>
  </div>

  <div class="article-book-card__content">
    <?php if ( $mini ) : ?>
    <p class="book-pitch"><?php echo esc_html( $mini ); ?></p>
    <?php endif; ?>
    <?php if ( $show_why && $why ) : ?>
    <p class="book-pitch book-pitch--why">
      <span class="book-pitch__label">why i loved it</span>
      <?php echo esc_html( $why ); ?>
    </p>
    <?php endif; ?>
    <?php if ( $trope_terms && ! is_wp_error( $trope_terms ) ) : ?>
    <div class="article-book-card__tropes">
      <?php foreach ( $trope_terms as $t ) :
        [ $bg, $fg ] = bbb_get_trope_colors( $t->slug );
      ?>
      <span class="article-book-card__trope" style="--trope-bg: <?php echo esc_attr( $bg ); ?>; --trope-text: <?php echo esc_attr( $fg ); ?>;">
        <?php echo esc_html( $t->name ); ?>
      </span>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <div class="article-book-card__ratings">
      <?php if ( $ku_raw === '1' ) : ?>
      <span class="article-book-card__ku article-book-card__ku--yes">✓ on kindle unlimited</span>
      <?php elseif ( $ku_raw === '0' ) : ?>
      <span class="article-book-card__ku article-book-card__ku--no">✕ not on kindle unlimited</span>
      <?php endif; ?>
    </div>
    <div class="article-book-card__buttons">
      <?php if ( $amazon ) : ?>
      <a class="article-book-card__button article-book-card__button--amazon" href="<?php echo esc_url( $amazon ); ?>" target="_blank" rel="noopener">amazon</a>
      <?php endif; ?>
      <?php if ( $bookshop ) : ?>
      <a class="article-book-card__button article-book-card__button--bookshop" href="<?php echo esc_url( $bookshop ); ?>" target="_blank" rel="noopener">
        <span class="article-book-card__buttonText article-book-card__buttonText--full">support local bookshop</span>
        <span class="article-book-card__buttonText article-book-card__buttonText--short">bookshop</span>
      </a>
      <?php endif; ?>
    </div>
  </div>

</div>
    <?php
    return ob_get_clean();
}
```

---

#### `bbb_render_book_card( int $post_id, array $args = [] ): string`

Unified entry point.

```php
/**
 * @param int   $post_id
 * @param array $args {
 *   @type string $context    'library'|'article'  Default: 'library'
 *   @type bool   $mini       Library mini variant. Default: false
 *   @type bool   $show_why   Article: show "why I loved it". Default: false
 * }
 */
function bbb_render_book_card( int $post_id, array $args = [] ): string {
    $context  = $args['context']  ?? 'library';
    $mini     = (bool) ( $args['mini']     ?? false );
    $show_why = (bool) ( $args['show_why'] ?? false );

    if ( $context === 'article' ) {
        return bbb_render_article_book_card( $post_id, $show_why );
    }
    return bbb_render_library_book_card( $post_id, $mini );
}
```

---

## 7. Files to Create

### `inc/books/book-cpt.php`
Registers CPT `bbb_book` and taxonomies `bbb_trope`, `bbb_shelf`, `bbb_series`.

```php
<?php
// inc/books/book-cpt.php

function bbb_register_book_cpt() {
    register_post_type( 'bbb_book', [
        'label'       => 'Books',
        'public'      => true,
        'has_archive' => true,
        'rewrite'     => [ 'slug' => 'books' ],
        'supports'    => [ 'title', 'thumbnail', 'custom-fields' ],
        'show_in_rest'=> true,
        'menu_icon'   => 'dashicons-book',
    ] );
}
add_action( 'init', 'bbb_register_book_cpt' );

function bbb_register_book_taxonomies() {
    foreach ( [ 'bbb_trope', 'bbb_shelf', 'bbb_series' ] as $tax ) {
        $labels = [
            'bbb_trope'  => 'Tropes',
            'bbb_shelf'  => 'Shelves',
            'bbb_series' => 'Series',
        ];
        register_taxonomy( $tax, 'bbb_book', [
            'label'        => $labels[ $tax ],
            'hierarchical' => false,
            'rewrite'      => [ 'slug' => str_replace( 'bbb_', '', $tax ) ],
            'show_in_rest' => true,
        ] );
    }
}
add_action( 'init', 'bbb_register_book_taxonomies' );
```

---

### `inc/books/book-renderers.php`
Contains: `bbb_get_book_data_attrs`, `bbb_render_library_book_card`, `bbb_render_article_book_card`, `bbb_render_book_card`.
Full function bodies are in Section 6 above.

---

### `inc/books/book-import.php`
WP-CLI command to import books from a JSON export of the Shopify GraphQL response.

```php
<?php
// inc/books/book-import.php
// Usage: wp bbb import-books --file=books.json

if ( defined( 'WP_CLI' ) && WP_CLI ) {
    WP_CLI::add_command( 'bbb import-books', function( $args, $assoc_args ) {
        $file = $assoc_args['file'] ?? '';
        if ( ! file_exists( $file ) ) {
            WP_CLI::error( "File not found: $file" );
        }
        $data  = json_decode( file_get_contents( $file ), true );
        $books = $data['data']['metaobjects']['edges'] ?? [];
        $count = 0;

        foreach ( $books as $edge ) {
            $node   = $edge['node'];
            $handle = $node['handle'];
            $fields = [];
            foreach ( $node['fields'] as $f ) {
                $fields[ $f['key'] ] = $f;
            }

            // Map to WP post
            $title = $fields['title']['value'] ?? $handle;

            // Upsert by slug
            $existing = get_page_by_path( $handle, OBJECT, 'bbb_book' );
            $post_id  = $existing ? $existing->ID : wp_insert_post( [
                'post_type'   => 'bbb_book',
                'post_status' => 'publish',
                'post_title'  => $title,
                'post_name'   => $handle,
            ] );
            if ( is_wp_error( $post_id ) ) {
                WP_CLI::warning( "Failed: $handle" );
                continue;
            }

            // Meta mapping (key in fields → WP meta key)
            $meta_map = [
                'author'                  => '_bbb_author',
                'spice_level'             => '_bbb_spice',
                'tension_score'           => '_bbb_tension',
                'emotional_damage_score'  => '_bbb_damage',
                'yearning_level'          => '_bbb_yearning',
                'boyfriend_type'          => '_bbb_boyfriend_type',
                'boyfriend_name'          => '_bbb_boyfriend_name',
                'reread_badge'            => '_bbb_reread',
                'darkness_level'          => '_bbb_darkness',
                'mini_note'               => '_bbb_mini_note',
                'why_i_loved_it'          => '_bbb_why',
                'series_number'           => '_bbb_series_number',
            ];
            foreach ( $meta_map as $shopify_key => $wp_meta ) {
                if ( isset( $fields[ $shopify_key ]['value'] ) ) {
                    update_post_meta( $post_id, $wp_meta, $fields[ $shopify_key ]['value'] );
                }
            }

            // Boolean fields
            foreach ( [ 'on_kindle_unlimited' => '_bbb_ku', 'read_as_standalone' => '_bbb_standalone', 'hide_from_library' => '_bbb_hide_from_library', 'private_shelf' => '_bbb_private_shelf' ] as $sk => $wk ) {
                if ( isset( $fields[ $sk ]['value'] ) ) {
                    update_post_meta( $post_id, $wk, $fields[ $sk ]['value'] === 'true' ? '1' : '0' );
                }
            }

            // URL fields (from reference.image.url or plain value)
            $cover_url = $fields['cover']['reference']['image']['url'] ?? '';
            if ( $cover_url ) update_post_meta( $post_id, '_bbb_cover_url', $cover_url );

            $amazon = $fields['amazon_link']['value'] ?? '';
            if ( $amazon ) update_post_meta( $post_id, '_bbb_amazon_url', $amazon );
            $bookshop = $fields['bookshop_link']['value'] ?? '';
            if ( $bookshop ) update_post_meta( $post_id, '_bbb_bookshop_url', $bookshop );
            $newsletter = $fields['newsletter_url']['value'] ?? '';
            if ( $newsletter ) update_post_meta( $post_id, '_bbb_newsletter_url', $newsletter );
            $nl_date = $fields['featured_in_newsletter_date']['value'] ?? '';
            if ( $nl_date ) update_post_meta( $post_id, '_bbb_newsletter_date', $nl_date );

            // Series handle
            $series_ref = $fields['series']['reference'] ?? null;
            if ( $series_ref ) {
                $sh = $series_ref['handle'] ?? '';
                $st = '';
                foreach ( $series_ref['fields'] ?? [] as $sf ) {
                    if ( $sf['key'] === 'title' ) $st = $sf['value'];
                }
                if ( $sh ) {
                    update_post_meta( $post_id, '_bbb_series_handle', $sh );
                    // Ensure taxonomy term exists
                    if ( ! term_exists( $sh, 'bbb_series' ) ) {
                        wp_insert_term( $st ?: $sh, 'bbb_series', [ 'slug' => $sh ] );
                    }
                    wp_set_object_terms( $post_id, $sh, 'bbb_series' );
                }
            }

            // Shelf
            $shelf_ref = $fields['shelf']['reference'] ?? null;
            if ( $shelf_ref ) {
                $shelf_name = '';
                foreach ( $shelf_ref['fields'] ?? [] as $sf ) {
                    if ( $sf['key'] === 'name' ) $shelf_name = $sf['value'];
                }
                if ( $shelf_name ) {
                    wp_set_object_terms( $post_id, $shelf_name, 'bbb_shelf' );
                }
            }

            // Tropes
            $trope_refs = $fields['tropes']['references']['edges'] ?? [];
            $trope_slugs = [];
            foreach ( $trope_refs as $te ) {
                $tn = $te['node'];
                $trope_handle = $tn['handle'] ?? '';
                $trope_name = '';
                $trope_emoji = '';
                foreach ( $tn['fields'] ?? [] as $tf ) {
                    if ( $tf['key'] === 'name' ) $trope_name = $tf['value'];
                    if ( $tf['key'] === 'emoji' ) $trope_emoji = $tf['value'];
                }
                if ( $trope_handle ) {
                    if ( ! term_exists( $trope_handle, 'bbb_trope' ) ) {
                        $ins = wp_insert_term( $trope_name ?: $trope_handle, 'bbb_trope', [ 'slug' => $trope_handle ] );
                        if ( ! is_wp_error( $ins ) && $trope_emoji ) {
                            add_term_meta( $ins['term_id'], 'trope_emoji', $trope_emoji, true );
                        }
                    }
                    $trope_slugs[] = $trope_handle;
                }
            }
            if ( $trope_slugs ) {
                wp_set_object_terms( $post_id, $trope_slugs, 'bbb_trope' );
            }

            $count++;
            WP_CLI::log( "Imported: $handle" );
        }
        WP_CLI::success( "Done. $count books imported." );
    } );
}
```

---

### `inc/books/book-visibility.php`
Contains `bbb_is_book_visible()` and `bbb_is_book_private()`. Full bodies in Section 4.

---

### `inc/books/trope-colors.php`
Exact port of `trope-pill-colors.liquid`.

```php
<?php
// inc/books/trope-colors.php

/**
 * Returns [bg_hex, text_hex] for a trope slug.
 * Default: ['#f3bfd5', '#4b112d']
 */
function bbb_get_trope_colors( string $slug ): array {
    $map = [
        'enemies-to-lovers'                       => [ '#f2a7ad', '#6e1422' ],
        'friends-to-lovers'                       => [ '#bfe3cb', '#144a31' ],
        'slow-burn'                               => [ '#f2c179', '#6a3700' ],
        'billionaire-romance'                     => [ '#bfdca0', '#365316' ],
        'billionaire'                             => [ '#bfdca0', '#365316' ],
        'second-chance'                           => [ '#cfbef5', '#4b2280' ],
        'forced-proximity'                        => [ '#a9cdf6', '#163f72' ],
        'grumpy-sunshine'                         => [ '#f2d35f', '#5f4700' ],
        'workplace-romance'                       => [ '#bfd0ef', '#274469' ],
        'fake-dating'                             => [ '#efb6d3', '#6e2147' ],
        'marriage-of-convenience'                 => [ '#dbc2a7', '#6c4221' ],
        'sports-romance'                          => [ '#9fd8e5', '#0f5064' ],
        'small-town'                              => [ '#c7d89b', '#405719' ],
        'brothers-best-friend'                    => [ '#ebb99c', '#71351a' ],
        'dark-romance'                            => [ '#b8a0d8', '#2f1646' ],
        'stalker-romance'                         => [ '#b8a0d8', '#2f1646' ],
        'stalker'                                 => [ '#b8a0d8', '#2f1646' ],
        'morally-gray-hero'                       => [ '#b9c1cb', '#26303b' ],
        'morally-gray-men'                        => [ '#b9c1cb', '#26303b' ],
        'morally-gray'                            => [ '#b9c1cb', '#26303b' ],
        'touch-her-and-die'                       => [ '#e596a8', '#641223' ],
        'one-bed'                                 => [ '#d8b9ea', '#55276f' ],
        'fated-mates'                             => [ '#e7acd1', '#74204f' ],
        'age-gap'                                 => [ '#c4d4ec', '#31486e' ],
        'single-dad'                              => [ '#b7dbc9', '#1f543b' ],
        'reverse-harem'                           => [ '#d7a8d7', '#651c58' ],
    ];
    return $map[ $slug ] ?? [ '#f3bfd5', '#4b112d' ];
}
```

---

### `template-parts/book-card/library-card.php`
Thin wrapper — calls the renderer.

```php
<?php
// template-parts/book-card/library-card.php
// Required: $args['post_id'] (int), optional: $args['mini'] (bool)
$post_id = (int) ( $args['post_id'] ?? 0 );
$mini    = (bool) ( $args['mini']    ?? false );
if ( ! $post_id ) return;
echo bbb_render_library_book_card( $post_id, $mini );
```

---

### `template-parts/book-card/article-card.php`
Thin wrapper — calls the renderer.

```php
<?php
// template-parts/book-card/article-card.php
// Required: $args['post_id'] (int), optional: $args['show_why'] (bool)
$post_id  = (int)  ( $args['post_id']  ?? 0 );
$show_why = (bool) ( $args['show_why'] ?? false );
if ( ! $post_id ) return;
echo bbb_render_article_book_card( $post_id, $show_why );
```

---

### How to call template parts (from theme templates):

```php
// Library card
get_template_part( 'template-parts/book-card/library-card', null, [
    'post_id' => $post_id,
    'mini'    => false,
] );

// Article card with "why I loved it"
get_template_part( 'template-parts/book-card/article-card', null, [
    'post_id'  => $post_id,
    'show_why' => true,
] );
```

---

### `article-trope-book-card.liquid` equivalent in PHP

The Liquid snippet finds the Nth book matching a given trope. WordPress equivalent:

```php
/**
 * Render the Nth visible book matching a trope, in article context.
 *
 * @param string $trope_slug   Trope term slug.
 * @param int    $index        1-based index of matching book to render.
 * @param bool   $show_why
 */
function bbb_render_article_trope_book_card( string $trope_slug, int $index = 1, bool $show_why = false ): string {
    $query = new WP_Query( [
        'post_type'      => 'bbb_book',
        'post_status'    => 'publish',
        'posts_per_page' => 250,
        'tax_query'      => [ [
            'taxonomy' => 'bbb_trope',
            'field'    => 'slug',
            'terms'    => $trope_slug,
        ] ],
        'fields'         => 'ids',
    ] );

    $match_count = 0;
    foreach ( $query->posts as $pid ) {
        if ( ! bbb_is_book_visible( $pid ) ) continue;
        $match_count++;
        if ( $match_count === $index ) {
            return bbb_render_article_book_card( $pid, $show_why );
        }
    }
    return '';
}
```

---

## 8. Verification Checklist

- [ ] `bbb_book` CPT exists, visible in admin, and `post_name` matches original Shopify handle exactly
- [ ] All 25 meta keys present on a sample book post (check via WP Admin → Books → Edit → Custom Fields)
- [ ] `bbb_trope` taxonomy: all trope slugs match `trope-pill-colors.php` keys (no color fallback used unexpectedly)
- [ ] `bbb_shelf` taxonomy: shelf names display correctly in `article-book-card__genre`
- [ ] `bbb_series` taxonomy: terms resolve from `_bbb_series_handle` meta via `get_term_by( 'slug', ... )`
- [ ] `bbb_is_book_visible()`: book with `_bbb_hide_from_library = 1` does NOT render in library grid
- [ ] `bbb_is_book_visible()`: book with future `_bbb_newsletter_date` does NOT render before 10am Pacific
- [ ] `bbb_is_book_private()`: book with `_bbb_private_shelf = 1` outputs `data-private-shelf="true"`
- [ ] Library card: all 25 `data-*` attributes present in rendered HTML
- [ ] Library card: `data-ku` is `"true"`, `"false"`, or `""` (not `"1"` or `"0"`)
- [ ] Article card: `data-book-preview` attribute present → modal JS binds click
- [ ] Article card: trope pills render with correct `--trope-bg` / `--trope-text` CSS vars
- [ ] KU badge only renders when `_bbb_ku` is explicitly `1` or `0` — not when empty
- [ ] `sssMyShelf` localStorage key still written by `blog-system.js` and `sss-library.js` (no renames needed — JS is unchanged)
- [ ] `bbb_render_article_trope_book_card( 'slow-burn', 2 )` returns the second visible slow-burn book
- [ ] Import script: run on 3 books, verify all meta + taxonomy terms import correctly before full run
- [ ] No classes renamed in output HTML vs. original Shopify snippets
