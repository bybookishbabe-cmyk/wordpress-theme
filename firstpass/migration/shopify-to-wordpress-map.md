# Shopify to WordPress Data Map

Generated after exporting Shopify metaobjects.

## Primary WordPress Content Types

### Books

Source: `sss_library` (96 entries)

Suggested WordPress type: `bbb_book`

Fields:

- `title`
- `author`
- `cover`
- `spice_level`
- `darkness_level`
- `why_i_loved_it`
- `mini_note`
- `featured_in_newsletter_date`
- `amazon_link`
- `bookshop_link`
- `newsletter_url`
- `boyfriend_name`
- `boyfriend_type`
- `series_number`
- `read_as_standalone`
- `tension_score`
- `emotional_damage_score`
- `reread_badge`
- `yearning_level`
- `on_kindle_unlimited`
- `starter_pack`
- `private_shelf`
- `top_shelf`

Relationships:

- `tropes` -> `bbb_trope`
- `series` -> `bbb_series`
- `shelf` -> likely `bbb_genre` or `bbb_mood_pill` after inspection

### Newsletter Issues

Source: `newsletter_issue` (21 entries)

Suggested WordPress type: `bbb_newsletter_issue`

Fields:

- `title`
- `subtitle`
- `publish_date`
- `url`
- `preview`
- `book` -> `bbb_book`

### Quotes

Source: `sss_quote` (58 entries)

Suggested WordPress type: `bbb_quote`

Fields:

- `quote`
- `library_book` -> `bbb_book`

### Series

Source: `sss_series` (22 entries)

Suggested WordPress type: `bbb_series`

Fields:

- `title`
- `author`
- `books_in_series`
- `linked_blog_post`

Relationships:

- `sss_books` -> `bbb_book`

## Taxonomies or Supporting Content Types

### Tropes

Source: `sss_tropes` (37 entries)

Suggested WordPress taxonomy: `bbb_trope`

Fields:

- `name`
- `emoji`
- `description`

### Genres

Source: `sss_genres` (8 entries)

Suggested WordPress taxonomy: `bbb_genre`

Fields:

- `name`
- `emoji`
- `description`

### Mood Pills

Source: `sss_mood_pill` (16 entries)

Suggested WordPress taxonomy or post type: `bbb_mood`

Fields:

- `label`
- `emoji`
- `line`

## Society / Download Content

### Drops

Source: `sss_drop` (7 entries)

Suggested WordPress type: `bbb_society_drop`

Includes release dates, moodboard assets, product references, wallpaper files, calendar files, Canva URLs, and prompts.

### Canva Templates

Source: `sss_canva_templates` (6 entries)

Suggested WordPress type: `bbb_canva_template`

Fields:

- `title`
- `category`
- `preview`
- `description`
- `canva_link`
- `tags`

### Pinterest Pins

Source: `pinterest_pin` (5 entries)

Suggested WordPress type: `bbb_pin`

Fields:

- `title`
- `image`
- `pinterest_url`
- `download_file`
- `mood`

## Next Build Steps

1. Add WordPress custom post type and taxonomy registrations.
2. Add importer that reads `migration/exports/metaobjects/*.json`.
3. Import relationship targets first: tropes, genres, moods, series.
4. Import books and store Shopify GIDs as `_shopify_gid`.
5. Resolve relationships by Shopify GID.
6. Import newsletter issues, quotes, drops, Canva templates, and pins.
7. Download or hotlink media depending on final hosting decision.
