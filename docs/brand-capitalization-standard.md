# bybookishbabe capitalization standard

This is the site-wide source of truth for capitalization in theme code, content imports, SEO fields, generated copy, and future cleanup work.

## Core rule

Capitalize searchable proper nouns everywhere. Keep bybookishbabe voice copy lowercase when it is not a searchable proper noun.

If a reader would search the exact thing with capital letters, preserve that capitalization even inside an otherwise lowercase sentence.

## Always capitalize

These are proper nouns and must keep their correct capitalization in visible copy, H1s, SEO titles, meta descriptions, Open Graph/Twitter metadata, JSON-LD/schema, image alt text, imports, and generated content.

- Book titles: `The Right Move`, `Fourth Wing`, `Shatter Me`
- Author names: `Liz Tomforde`, `H.D. Carlton`, `Tahereh Mafi`
- Series names: `Windy City series`, `Shatter Me series`
- Character names and fictional men: `Ryan Shay`, `Aaron Warner`, `Xaden Riorson`
- Place, platform, brand, publisher, and product names: `Amazon`, `Bookshop.org`, `Kindle Unlimited`, `BookTok`, `WordPress`, `Substack`
- Acronyms and initialisms: `PDF`, `SEO`, `H.D. Carlton`

## Usually lowercase

These are part of the bybookishbabe voice and can stay lowercase unless they contain a proper noun from the list above.

- Navigation labels: `the library`, `browse by trope`, `book reviews`
- UI labels and buttons: `read free`, `open the library`, `save to shelf`
- Section headings that are pure brand voice: `what the society is reading`
- Trope, genre, mood, and personality tags: `forced proximity`, `fake dating`, `slow burn`, `morally gray`
- Body prose when no proper noun is present
- The site name: `bybookishbabe`

## H1 and SEO rule

Use sentence-style bybookishbabe copy, but preserve proper nouns inside it.

Correct:

- `books like Fourth Wing but darker`
- `Ryan Shay fictional boyfriend - The Right Move | bybookishbabe`
- `Shatter Me series reading order - Tahereh Mafi`
- `read free on Kindle Unlimited`
- `buy on Amazon`
- `prefer indie? Bookshop.org`

Incorrect:

- `books like fourth wing but darker`
- `ryan shay fictional boyfriend - the right move`
- `shatter me series reading order - tahereh mafi`
- `read free on kindle unlimited`
- `buy on amazon`
- `prefer indie? bookshop.org`

## Slugs and URLs

Lowercase slugs are not capitalization violations. Keep URLs lowercase unless there is a separate SEO, redirect, or information architecture reason to change them.

Capitalization cleanup must not rewrite slugs automatically.

Example:

- Visible title: `books like Fourth Wing but darker`
- URL slug: `books-like-fourth-wing-but-darker`

## CSS rule

Do not put `text-transform: lowercase` on containers that can render book titles, author names, series names, character names, platform names, or SEO-relevant headings.

Use lowercase transforms only on known brand-voice elements such as nav labels, small UI labels, eyebrow text, generic buttons, and mood/trope chips. When a component mixes dynamic book data with UI labels, keep the data untransformed and apply lowercase styling only to the label elements.

## Enforcement points

The theme currently enforces this standard through:

- Runtime helpers in `inc/seo-lowercase.php`
- Book/title helpers in `inc/bbb-helpers.php`
- Local audit script: `scripts/audit-brand-capitalization.php`
- Local cleanup script: `scripts/apply-brand-capitalization.php`

The cleanup script must not be run casually. Audit first, review categories, back up content, then apply only after the expected changes are understood.

## Future-change checklist

Before shipping content or theme changes, check:

- Book titles, author names, character names, series names, and brand names are capitalized in visible copy.
- SEO titles and meta descriptions preserve proper nouns.
- Social share titles/descriptions and image alt text preserve proper nouns.
- Dynamic book/author/series fields are not inside a broad lowercase CSS container.
- Lowercase slugs are ignored unless a URL change is intentionally planned.
