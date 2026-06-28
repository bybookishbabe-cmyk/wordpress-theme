# Recommendation Engine Audit

## Why This Exists

A reader can ask for a mafia/spicy romance book and still get a top recommendation that does not feel mafia or spicy because the site currently has several recommendation systems with different definitions of "match." Some treat mafia as a trope. Some fold it into dark/chaos. Some treat spice as a light bonus. Some use hardcoded cluster picks.

The goal is to condense these into one source-of-truth recommendation engine, then have every surface call that engine with a different intent.

## Current Recommendation Systems

### 1. Made For You Dashboard

Primary files:
- `page-sss-made-for-you.php`
- `assets/js/sss-library.js`

Important functions:
- `bbb_made_for_you_books()`
- `scoreBook(book)`
- `scoreQuizOnlyBook(book)`
- `scoreBoyfriendMatch(book)`
- `scoreReadShelfRecommendation(book, topTropes)`
- `renderRecommendations(answeredCount)`
- `renderReadShelf()`

Inputs:
- quiz answers: `craving`, `payoff`, `boyfriend_hook`, `boyfriend_dynamic`, `theme`
- add-ons: `spice_dial`, `favorite_trope`, `hard_nos`, favorite book
- local shelf state: saved, TBR, reading, read, DNF
- local reactions: obsessed, liked it, not for me
- book metadata: shelf, tropes, spice, tension, damage, darkness, yearning, boyfriend type, manual `most_like`

Strength:
- Most personalized path.
- Has negative signals and excludes read/DNF books.
- Uses manual `most_like` relationships.

Main risk:
- Intent is implicit and spread through many profile maps. If a user says "mafia + spicy," no single explicit constraint guarantees the top rec must be both mafia and spicy.
- `yearning` is cast to int in `page-sss-made-for-you.php`, which may flatten non-numeric yearning labels.

### 2. What To Read Next

Primary files:
- `page-what-to-read-next.php`
- `assets/js/bbb-what-to-read-next.js`
- legacy Shopify version: `sections/bbb-what-to-read-next.liquid`

Important functions:
- `buildSeedBook(anchor, answers)`
- `scoreCandidate(baseBook, candidate)`
- `getMatches(books, selected, rotationStep)`

Inputs:
- optional anchor book
- quiz answers: vibe, heat, darkness, KU
- book metadata: tropes, shelf, spice, darkness, boyfriend, manual `mostLike`

Strength:
- The newer WP JS is a good source-of-truth candidate because it already supports both "start from a book" and "build from scratch."
- It weights manual matches, shared tropes, preferred tropes, shelf, spice/darkness distance, boyfriend, and KU.

Main risk:
- It creates a seed book from broad buckets like `danger`, where mafia is only one trope among several.
- It selects three lanes, not necessarily the absolute best match for the user's full explicit intent.
- The older Liquid version is less complete and should be retired or kept as legacy only.

### 3. Books Like Pages

Primary files:
- `page-books-like.php`
- `inc/books/books-like-helpers.php`
- legacy Shopify version: `sections/books-like-page.liquid`

Important functions:
- `bbb_books_like_score(source, candidate)`
- `bbb_books_like_recommendations(source_id)`

Inputs:
- source book
- manual `most_like`
- shelf, boyfriend type, shared tropes, spice, darkness, tension, damage, yearning, KU

Strength:
- Clean deterministic scorer.
- Strong manual override.
- Filters sequels unless standalone and avoids same series.

Main risk:
- It only answers "similar to this book," not "match this reader's declared intent."
- Legacy Liquid version has similar but separate scoring, so the same page family can drift.

### 4. Public Reader Quizzes

Primary files:
- `page-reader-mood-quiz.php`
- `page-fictional-boyfriend-quiz.php`
- `assets/js/reader-quiz.js`
- legacy Shopify mood quiz: `sections/reader-mood-quiz.liquid`
- legacy Shopify boyfriend quiz: `sections/fictional-boyfriend-quiz.liquid`

Important functions:
- `profileFor(type, scores)`
- `scoreBook(book, profile, scores, index)`
- legacy mood: `cardScore(card, config)`, `pickBooks(config)`

Inputs:
- quiz score buckets
- generated profile tags
- book metadata text, spice, darkness, damage, tension

Strength:
- Lightweight and fast.
- Good for public quiz entertainment.

Main risk:
- Broad text matching. Mafia/spicy can be overwhelmed by dark, chaos, or general trope tags.
- Legacy mood quiz uses its own config and does not share the Made For You / What To Read Next logic.

### 5. Next Read Finder Inside Library JS

Primary file:
- `assets/js/sss-library.js`

Important functions:
- `initReadFinder()`
- `pickBook()`

Inputs:
- shelf select
- trope one
- optional trope two

Strength:
- Simple constrained picker.

Main risk:
- It is random within progressively broader pools. It does not score, so top rec can be any book inside the first matching pool.
- It only knows shelf/trope, not spice/darkness/hard-nos/saved status.

### 6. Blog Read Next / Specific Links

Primary files:
- `inc/shortcodes/sss-readnext-shortcode.php`
- legacy Shopify snippets: `snippets/blog-read-next.liquid`, `snippets/blog-specific-map.liquid`

Important functions:
- `sss_detect_cluster(context)`
- `sss_specific_link_clusters()`
- `sss_find_readnext_book(anchor, cluster)`

Inputs:
- article guide category/trope
- anchor book from article
- broad cluster maps

Strength:
- Useful editorial linking surface.

Main risk:
- Cluster detection has ordering issues. For example, `obsession` can become `stalker` before a more specific `darkobsession` branch.
- It has its own cluster vocabulary and copy.

### 7. Society Recommends

Primary file:
- `inc/blog-society-recommendations.php`

Important functions:
- `bbb_society_recommendations_items(post_id)`
- `bbb_render_society_recommendations(post_id)`

Inputs:
- manual editor-selected posts/pages

Strength:
- True editorial control.

Main risk:
- It is manual linking, not an engine. It should remain editorial, but use the same taxonomy/intents for quick picks later.

### 8. Weekly Email / Bookshelf Weekly

Primary files:
- `supabase/functions/bookshelf-weekly-preview/index.ts`
- `bbb-quote-publisher/src/bookshelf-weekly-email.mjs`

Important functions:
- Supabase preview: `inferCluster()`, `buildRecommendedBook(cluster, latestBooks)`
- Quote publisher: `scoreRecommendation(candidate, sourceBooks)`, `buildRecommendedBook(catalog, latestBooks)`

Inputs:
- latest saved books
- catalog metadata
- cluster maps

Strength:
- Quote publisher version ranks against the catalog.

Main risk:
- Supabase preview returns hardcoded cluster recs, so it can sound personalized while being generic.
- Cluster order has the same specificity issue as blog read-next.

### 9. Homepage / Demo Recommendations

Primary files:
- `template-parts/library/library-rec-demo.php`
- `template-parts/home/personalized-shelf-week.php`

Inputs:
- options or selected book variables

Strength:
- Good marketing/demo modules.

Main risk:
- Not real recommendation logic. Should be labeled as editorial/demo or populated from canonical engine snapshots.

## Core Problem

The site does not have one canonical definition for:

- intent: what the user explicitly asked for right now
- affinity: what their shelf/history implies
- constraints: hard requirements that a top rec must satisfy
- boosts: nice-to-have signals
- exclusions: already read, DNF, sequels, hard-nos, private/hidden
- explanation: why the result was chosen

Right now, "mafia + spicy" can become:

- mafia as one trope inside dark/chaos
- spicy as a small boost
- dark/obsession as stronger than mafia
- same shelf/shared tropes as stronger than spice
- random pick from a matching pool
- hardcoded broad cluster recommendation

That is why the top result can feel wrong.

## Recommended Source Of Truth

Create one canonical recommendation module with these concepts:

### Canonical Book Shape

Every surface should normalize books into:

- `handle`
- `title`
- `author`
- `shelf`
- `shelfSlug`
- `tropes`
- `tropeHandles`
- `spice`
- `darkness`
- `tension`
- `damage`
- `yearning`
- `boyfriendType`
- `series`
- `seriesNumber`
- `standalone`
- `ku`
- `private`
- `hidden`
- `mostLike`

### Canonical Request Shape

Every surface should pass:

- `intent`: `quiz`, `books_like`, `mood`, `next_read`, `weekly_email`, `editorial`
- `requiredTropes`
- `preferredTropes`
- `requiredShelf`
- `preferredShelf`
- `minSpice`
- `maxSpice`
- `minDarkness`
- `maxDarkness`
- `boyfriendTypes`
- `anchorBooks`
- `readBooks`
- `savedBooks`
- `reactions`
- `hardNos`
- `kuOnly`
- `limit`
- `diversityMode`

For the mafia/spicy case, the request should be explicit:

```json
{
  "requiredTropes": ["mafia romance"],
  "minSpice": 4,
  "preferredShelf": "dark romance",
  "intent": "next_read"
}
```

Top results must satisfy required constraints first. Only then should boosts decide order.

### Scoring Order

1. Exclude invalid books:
   - read/DNF when appropriate
   - hidden/private unless the surface is allowed
   - non-standalone sequels unless user asked for series
   - hard-nos

2. Enforce hard constraints:
   - required trope/shelf
   - min/max spice
   - min/max darkness
   - KU-only

3. Score boosts:
   - manual `mostLike`
   - shared anchor tropes
   - preferred tropes
   - shelf match
   - boyfriend type
   - spice/darkness/tension/damage closeness
   - reactions and read shelf patterns

4. Diversify if needed:
   - avoid repeating same author/series/shelf in a 3-card set unless requested

5. Return explanations from the actual winning signals:
   - "mafia romance + 4/5 spice"
   - "same dark romance shelf"
   - "shares forced proximity with your saved books"

## Condensation Plan

### Phase 1: Canonicalize Definitions

Add shared definitions for:
- trope aliases: mafia, mafia romance, bratva, cartel
- shelf aliases: dark romance, extra dark, morally gray lovers
- boyfriend type aliases
- mood/intents
- cluster order, with specific clusters before broad ones

### Phase 2: Build One Scorer

Add a canonical scorer in the WP theme first:
- PHP helper for server-rendered pages and shortcodes
- JS wrapper for interactive pages

Best initial WP location:
- `inc/books/recommendation-engine.php`
- `assets/js/bbb-rec-engine.js`

The PHP and JS can share a JSON config for weights/aliases:
- `data/recommendation-engine-config.json`

### Phase 3: Migrate Surfaces

Priority order:

1. `page-what-to-read-next.php` / `assets/js/bbb-what-to-read-next.js`
2. `page-sss-made-for-you.php` / `assets/js/sss-library.js`
3. `assets/js/reader-quiz.js`
4. `inc/books/books-like-helpers.php`
5. `inc/shortcodes/sss-readnext-shortcode.php`
6. Supabase weekly preview and quote publisher
7. legacy Shopify Liquid files, if still used

### Phase 4: Retire Duplicate Logic

After migration:
- keep editorial/manual recommendation admin as-is
- keep template rendering as-is
- remove or mark legacy scoring functions as wrappers around the canonical engine

## Immediate Bug-Level Fixes To Consider

1. Add hard constraints for "specific" reader requests in What To Read Next.
   - If user chooses danger + spicy/feral, require dark/mafia/touch-her style tropes and minimum spice.

2. Add mafia aliases across all surfaces:
   - `mafia`, `mafia romance`, `bratva`, `cartel`, `organized crime`

3. Put specific cluster checks before broad checks:
   - `darkobsession` before `stalker`
   - `darkromantasy` before `romantasy`
   - `mafia` before generic `dark`

4. Make spice a hard filter when the UI wording says "spicy" or "feral."
   - spicy: `minSpice = 4`
   - feral/wreck me: `minSpice = 5`

5. Add a debug mode that shows why a book won:
   - constraints passed
   - score breakdown
   - exclusions

## Suggested Target Behavior

If a user chooses mafia + spicy:

- first results must include mafia or a strong mafia alias
- first results must meet the requested spice threshold
- if no exact matches exist, the UI should say it broadened the search:
  - "I could not find mafia + 4/5 spice, so I widened to dark romance + high spice."

The engine should never silently hand back a soft contemporary or generic dark romance while presenting it as the top mafia/spicy match.
