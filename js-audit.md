# By Bookish Babe — JavaScript Audit
## Book Cards · Saved Books · Bookshelf · Book Detail Modal

**Audit date:** 2026-05-17  
**Source files inspected:** `assets/sss-library.js` (~3700 lines), `assets/blog-system.js` (~600 lines), `assets/bookshelf-signup.js` (~560 lines), `layout/theme.liquid` (inline script)

---

## 1. File Inventory

| File | Size | What it owns |
|---|---|---|
| `assets/sss-library.js` | ~3700 lines | Library page: book card clicks, modal open/close, heart save/unsave, status (read/tbr/dnf), reactions, quote saves, trending shelf (Supabase), Made-for-You quiz engine, shelf section render, analytics |
| `assets/blog-system.js` | ~600 lines | Blog/article pages: inline heart `[data-book-heart]`, book preview popup `[data-book-preview]`, signoff animation, share button, scroll reveal |
| `assets/bookshelf-signup.js` | ~560 lines | Signup modal triggered at save counts 2 + 5; Supabase subscriber + saved-book + status sync; post-redirect pending submission |
| `layout/theme.liquid` (inline `<script>`) | ~90 lines | Injects `window.BBBReaderAccount` from Shopify Liquid customer object; `bbbPostLoginReturn` redirect handler |

---

## 2. localStorage Keys — Complete Map

| Key | Format | Written by | Read by | Purpose |
|---|---|---|---|---|
| `sssMyShelf` | JSON array of book objects | `sss-library.js` · `blog-system.js` | All three files | **Primary** saved-books shelf. Every heart save/unsave writes here. |
| `sssShelf` | JSON array (mirror) | `blog-system.js` only | `bookshelf-signup.js` (fallback) | Legacy mirror of `sssMyShelf`. Always written in sync with it. Safe to read from if `sssMyShelf` is empty. |
| `sssBookStatuses` | JSON object `{bookKey: status}` | `sss-library.js` · `bookshelf-signup.js` | `sss-library.js` · `bookshelf-signup.js` | Per-book read/reading/tbr/dnf status. Key = `handle \|\| title` lowercased. |
| `sssBookReactions` | JSON object `{bookKey: reaction}` | `sss-library.js` | `sss-library.js` | Per-book reaction after reading: `obsessed`, `liked_it`, `not_for_me`. |
| `sssSavedQuotes` | JSON array of quote objects | `sss-library.js` | `sss-library.js` | Quotes saved from the quote wall. |
| `sssQuoteNotes` | JSON array (max 60) | `sss-library.js` | clipboard export only | Text copies of saved quotes for clipboard/email export. |
| `bbbBookshelfSignupState` | JSON `{shownAt[], subscribed, email, lastCount}` | `bookshelf-signup.js` | `bookshelf-signup.js` | Tracks popup trigger history and subscription state. |
| `sssAnalyticsSessionId` | string | `sss-library.js` · `bookshelf-signup.js` | Both | Persistent anonymous session ID for Supabase event tracking. |
| `sssAnalyticsExcluded` | string `"true"` | `sss-library.js` | `sss-library.js` | Opt-out of analytics (append `?analytics=off` to any URL). |
| `sssAnalyticsDailyVisit:YYYY-MM-DD` | string `"true"` | `sss-library.js` | `sss-library.js` | Dedup flag for daily visit event; keyed by Pacific date. |
| `bbbPostLoginReturn` | JSON `{path, createdAt}` | `theme.liquid` inline script | `theme.liquid` inline script | Post-login redirect target; expires after 15 min. |
| `sssMadeForYouProfile:[sectionId]` | JSON object (quiz answers + profile) | `sss-library.js` MFY section | `sss-library.js` MFY section | Made-for-You quiz state, stored per `[data-sss-lib]` element ID. |

### sessionStorage Keys

| Key | Format | Purpose |
|---|---|---|
| `bbbBookshelfSignupPending` | JSON object | Queued signup submission payload (survives form-POST page reload). Processed on return page. |

---

## 3. Custom Events (dispatched on `document`)

| Event name | Dispatched by | Payload | Listeners |
|---|---|---|---|
| `sss:bookshelf-updated` | `setShelf()` in any file | `{ count }` | `blog-system.js` → re-syncs inline hearts + preview modal heart; `bookshelf-signup.js` → updates `state.lastCount` |
| `bbb:shelf-saved` | `toggleSave()`, blog heart click, preview modal heart | `{ count, bookTitle, bookHandle, book, source }` | `bookshelf-signup.js` → triggers popup at count 2/5; syncs Supabase subscriber + saved-book record |
| `bbb:shelf-unsaved` | `toggleSave()`, shelf unsave, preview modal | `{ count, bookTitle, bookHandle, book, source }` | `bookshelf-signup.js` → marks Supabase `is_active = false` |
| `bbb:book-status-changed` | `setBookStatus()` | `{ key, status, book, source }` | `bookshelf-signup.js` → syncs all statuses to Supabase |
| `bbb:book-statuses-updated` | `setBookStatuses()` | `{ statuses }` | Internal only |
| `sss:quote-saves-updated` | `setSavedQuotes()` | `{ count }` | MFY section → re-renders saved quotes panel |
| `bbb:open-shelf-signup` | any external trigger | none | `bookshelf-signup.js` → opens modal |

---

## 4. Global `window` Objects

| Object | Set by | Shape | Notes |
|---|---|---|---|
| `window.BBBReaderAccount` | `theme.liquid` Liquid inline script | `{ loggedIn: bool, customerId: number\|null, email: string, firstName: string, isSociety: bool, bookshelfUrl: string, accountUrl: string, loginUrl: string }` | **Shopify-specific** — must be replaced in WP with PHP outputting equivalent from `wp_get_current_user()` and role check |
| `window.BBBShelfSignup` | `bookshelf-signup.js` | `{ open() }` | Public API to manually open signup modal |
| `window.sssQuoteStorage` | `sss-library.js` | `{ getSavedQuotes, isQuoteSaved, toggleSavedQuote, addQuoteNote }` | Cross-module quote API |
| `window.sssRenderModalBookStatus` | `sss-library.js` | `function(modal, bookData)` | Called by `blog-system.js` preview popup to apply status controls; avoids direct import |
| `window.sssSyncBookStatusUI` | `sss-library.js` | `function()` | Public: re-sync all card status ribbons |
| `window.sssAnalytics` | `sss-library.js` | `{ exclude(), include(), isExcluded() }` | Analytics opt-in/out API |
| `window.shopUrl` | `theme.liquid` | string | Shopify store origin URL — **remove; use WordPress `home_url()` instead** |
| `window.routes` | `theme.liquid` | `{ cart_add_url, cart_change_url, cart_update_url, cart_url, predictive_search_url }` | Shopify cart routes — **replace with WooCommerce equivalents** |

---

## 5. Key JS Functions — What Each Does

### 5a. sss-library.js — Storage Layer

```
getShelf()              → parse localStorage.sssMyShelf; return [] on error
setShelf(data)          → write sssMyShelf; dispatch sss:bookshelf-updated
getBookStatuses()       → parse localStorage.sssBookStatuses; return {}
setBookStatuses(data)   → write sssBookStatuses; dispatch bbb:book-statuses-updated
getBookReactions()      → parse localStorage.sssBookReactions; return {}
setBookReactions(data)  → write sssBookReactions (no event)
getSavedQuotes()        → parse localStorage.sssSavedQuotes; return []
setSavedQuotes(data)    → write sssSavedQuotes; dispatch sss:quote-saves-updated
toggleSavedQuote(q)     → add or remove from sssSavedQuotes; return new saved state
isQuoteSaved(q)         → boolean; key = title::text
getBookStatusKey(book)  → normalize (handle || title) to lowercase string
getBookStatus(book)     → look up by getBookStatusKey from sssBookStatuses
setBookStatus(book, s)  → set status; dispatch bbb:book-status-changed
getBookReaction(book)   → look up from sssBookReactions
setBookReaction(book,r) → set reaction (no event)
```

### 5b. sss-library.js — Heart / Save Layer

```
toggleSave(heartEl, bookBtn)
  - Reads all 20+ data-* attrs from bookBtn
  - Finds book in shelf by title
  - If exists: remove; dispatch bbb:shelf-unsaved; applyHeartSavedState(false)
  - If not: push; trackBookSave(); showSaveToast(); dispatch bbb:shelf-saved; applyHeartSavedState(true)
  - Calls setShelf() + renderMyShelf()

applyHeartSavedState(heartEl, isSaved)
  - Toggles class "is-saved" on heartEl
  - Updates [data-heart-icon] textContent ♡/♥
  - Updates [data-heart-label] textContent "save"/"saved"
  - Updates aria-label

syncAllLibraryHearts()
  - Queries ALL .sss-lib__book [data-heart] on page
  - Matches by book.title === card.dataset.title
  - Calls applyHeartSavedState for each
```

### 5c. sss-library.js — Book Status / Ribbon Layer

```
applyBookStatusToCard(card)
  - Looks up status for card.dataset.{handle,title}
  - If status: insert/update .sss-lib__statusRibbon[data-book-status-ribbon] inside .sss-lib__coverWrap
  - If no status: remove ribbon

ensureStatusRibbon(target)
  - Creates .sss-lib__statusRibbon if absent; returns element

ensureModalStatusControls(modal)
  - Injects read/reading/tbr/dnf + obsessed/liked_it/not_for_me buttons if absent
  - Injects inside .sss-lib__mbelow (before .sss-lib__mcta)
  - Binds [data-status-option] clicks → setBookStatus + syncBookStatusUI
  - Binds [data-reaction-option] clicks → setBookReaction + syncBookStatusUI

renderModalBookStatus(modal, bookData)
  - Sets modal.__currentBook = bookData
  - Applies status ribbon inside .sss-lib__mcoverFrame
  - Shows/hides [data-modal-reaction-controls] (only if read or dnf)
  - Marks active [data-status-option] + [data-reaction-option] buttons

syncBookStatusUI()
  - Calls applyBookStatusToCard on ALL .sss-lib__book elements
  - Calls renderModalBookStatus on ALL .sss-lib__modal elements that have __currentBook
```

### 5d. sss-library.js — Modal Open/Close

```
init() → runs for each [data-sss-lib] root
  
  getModalBookData(btn)
    - Returns plain object of all 20+ book fields from btn.dataset

  openModal(data)
    - sanitizeBookDataForLibraryType: clears shelf if non-society + private book
    - trackSiteEvent("book_modal_opened")
    - Populates: [data-mtitle], [data-mauthor], [data-mcover]
    - Populates: [data-mmini], [data-mtropes], [data-mwhy]
    - Populates: [data-mku], [data-mdarkness]
    - Populates: [data-mseries] with link; [data-mseries-order]; [data-mstandalone]
    - Shows/hides [data-amazon-btn], [data-bookshop-btn], [data-newsletter-btn]
    - Society only: populates [data-mtension], [data-mdamage], [data-myearning], [data-mreread]
    - Calls renderModalBookStatus(modal, data)
    - Binds [data-modal-heart] onclick → toggleSave(fakeBtn)
    - Binds [data-modal-share-btn] onclick → navigator.share || clipboard
    - Sets modal.hidden = false; aria-hidden="false"; document.documentElement overflow=hidden

  closeModal()
    - modal.hidden = true; aria-hidden="true"; overflow=""
    - Bound to: all [data-close] elements + document keydown Escape

Book click binding (per root):
  root.addEventListener('click', ...) catches .sss-lib__book[data-title] clicks
  btn.__sssModalBound = true prevents duplicate binding
  Ignores clicks on [data-heart] and .sss-lib__seriesBadge

Heart binding (per root):
  root.querySelectorAll('[data-heart]') → each bound to toggleSave()
  Initial state set from getShelf()

Series badge binding:
  .sss-lib__seriesBadge[data-series-url] → navigates to series page on click
```

### 5e. sss-library.js — My Shelf Section

```
renderMyShelf()
  - Reads getShelf()
  - Shows #sssMyShelfSection (hidden by default until first save)
  - Renders last 3 books (or all in swipeable mode if ≥5 saved)
  - Injects full .sss-lib__book HTML with all data-attrs via hydrateShelfBook()
  - Adds placeholder "book here" cards when <3 saved
  - Binds unsave hearts on rendered cards
  - Calls initMobileGridPagination()

hydrateShelfBook(book)
  - Tries to find richer data from page DOM (matching card) or window.books array
  - Returns enriched book object or original if no match

buildShelfText() → plain-text list for clipboard/email
openNotepad() → populate #sssNotepadBody; show #sssNotepad
closeNotepad() → hide #sssNotepad
showSaveToast()
  - Shows #sssSaveToast for 3 seconds
  - #sssToastShelfLink → scrolls to #sssMyShelfSection or navigates to /pages/sss-library-page?shelf=open
```

### 5f. sss-library.js — Trending Shelf (Supabase)

```
loadTrending()
  - Queries Supabase book_saves_recent_rollup (7-day + 30-day saves)
  - Falls back to book_saves_all_time_rollup if <5 results
  - Matches top 5 books against DOM cards (.sss-lib__book) by handle/title
  - Clones matched cards into #sssTrendingRow
  - Normalizes topshelf variant CSS classes to standard book card classes
```

### 5g. sss-library.js — Made-for-You Quiz Engine (large block)

```
scoreBook(book)
  - Weights: craving profile (slow burn, obsession, etc.) + payoff profile + fictional man type
  - Bonus: matching tropes, shelf genre, spice level, boyfriend type
  - Penalty: already read or dnf'd (-999)
  - Factored in: saved shelf, user reactions, favorite book overlap

loadProfile() / saveProfile()
  - localStorage key: 'sssMadeForYouProfile:' + [data-sss-lib element ID]

syncAnswerUI() → highlight selected answer buttons
isProfileComplete() → all 6 questions answered
showResults() / hideResults() → toggle quiz vs results panels
syncResultStepUI() → manage result panel visibility + customize + quote + read shelf
resetMadeForYou() → clear profile + reset UI
deriveBoyfriendTypeFromQuiz(profile) → infer fictional man archetype from answers
```

### 5h. blog-system.js — Blog Inline Hearts

```
getShelf() / setShelf()
  - Same localStorage key sssMyShelf
  - setShelf writes BOTH sssMyShelf and sssShelf

syncInlineBlogHearts()
  - Queries [data-book-heart] elements
  - Matches by heart.dataset.title against shelf
  - Updates .saved class + innerHTML ♡/♥

Heart click on [data-book-heart]:
  - book = { title, author, cover, amazon, bookshop } from data-*
  - Toggle save/unsave by title match
  - Dispatches bbb:shelf-saved or bbb:shelf-unsaved
  - Shows #bbbShelfPopup for 4 seconds if defined
  - Note: DOES NOT save handle, spice, tropes, etc. (minimal object vs library full object)
```

### 5i. blog-system.js — Book Preview Popup

```
Card selector: [data-book-preview]
Modal: #bbbBookPreview
Close: #bbbPreviewClose + [data-close] inside modal

Card click → populate modal:
  - Reads: title, author, cover, amazon, bookshop, newsletter, spice, tropes, tropesDisplay,
            mini, why, ku, series, seriesName, seriesNumber, handle from card.dataset
  - Fills: [data-mtitle], [data-mauthor], [data-mcover]
  - Fills: [data-mmini], [data-mtropes], [data-mwhy], [data-mku], [data-mreread]
  - Fills: [data-mseries]/[data-mseries-order]/[data-mstandalone]
  - Shows/hides [data-amazon-btn] [data-bookshop-btn]
  - Sets modal.style.display = "flex"; modal.hidden = false; aria-hidden="false"
  - Calls window.sssRenderModalBookStatus if available (from sss-library.js)

Modal heart [data-modal-heart]:
  - Key = handle || title (normalized)
  - Toggle save/unsave in sssMyShelf
  - Dispatches bbb:shelf-saved / bbb:shelf-unsaved
  - Calls syncPreviewInlineHearts + syncPreviewModalHeart

Share button #bbbPreviewShare:
  - navigator.share || clipboard; URL = location.origin + path + ?book=[title]

Cross-sync:
  - Listens sss:bookshelf-updated → syncInlineBlogHearts + syncPreviewModalHeart
  - Listens window.storage (sssMyShelf/sssShelf) → same syncs (cross-tab)
```

### 5j. bookshelf-signup.js — Signup Modal

```
Root: [data-bbb-shelf-signup]
Form: #BBBBookshelfSignupForm (action="/account" — SHOPIFY SPECIFIC)
Email input: #bbbShelfSignupEmail
Close buttons: [data-bbb-shelf-close]

Trigger thresholds: TRIGGERS = [2, 5] (save counts)
  - maybeTrigger(count) → called on every bbb:shelf-saved
  - Skips if: subscribed, logged in, or threshold already shown

openModal(triggerCount, options)
  - If logged in + manual: redirect to bookshelfUrl
  - Adds "is-open" to root; "bbb-shelf-signup-open" to html + body
  - Tracks "bookshelf_signup_popup_shown" to Supabase

closeModal()
  - Removes "is-open"; removes body classes
  - Tracks "bookshelf_signup_popup_dismissed"

Form submit handler:
  - queuePendingSubmission(email) → writes bbbBookshelfSignupPending to sessionStorage
  - Page redirects to Shopify /account (form POST)

On return (root.dataset.success === "true"):
  - syncPendingSubmission() → upserts subscriber + saved books + statuses to Supabase

Supabase tables written:
  - bookshelf_subscribers (upsert on email_normalized)
  - bookshelf_saved_books (upsert on email_normalized + book_key)
  - bookshelf_book_statuses (delete all + re-insert)
  - site_events (popup shown/dismissed/submitted)

BBBReaderAccount integration:
  - getReaderAccount() → window.BBBReaderAccount
  - Enriches subscriber record with Shopify customer ID, society tier, etc.
```

---

## 6. Required DOM Structure

### Book Card (every `.sss-lib__book` button)

```html
<button type="button" class="sss-lib__book"
  data-handle="slug-handle"
  data-title="Book Title"
  data-author="Author Name"
  data-cover="https://cdn.example.com/cover.jpg"
  data-amazon="https://amazon.com/..."
  data-bookshop="https://bookshop.org/..."
  data-shelf="morally-gray"
  data-private-shelf="false"
  data-spice="4"
  data-tropes="enemies-to-lovers, slow-burn"
  data-tropes-display="⚔️ enemies-to-lovers, 🕯️ slow-burn"
  data-trope-urls="/trope/enemies-to-lovers/, /trope/slow-burn/"
  data-why="I loved it because..."
  data-newsletter="https://site.com/posts/newsletter-issue"
  data-mini="One-sentence summary"
  data-series="series-handle"
  data-series-name="Series Name"
  data-series-number="2"
  data-tension="4"
  data-damage="3"
  data-yearning="2"
  data-boyfriend="cold-and-unreadable"
  data-boyfriend-name="Character Name"
  data-reread="true"
  data-standalone="false"
  data-ku="true"
  data-darkness="2"
>
  <div class="sss-lib__coverWrap">
    <!-- Heart button — JS binds click -->
    <span class="sss-lib__heart" data-heart role="button" aria-label="save to your bookshelf">
      <span class="sss-lib__heartIcon" data-heart-icon aria-hidden="true">♡</span>
      <span class="sss-lib__heartLabel" data-heart-label>save</span>
    </span>
    <!-- Series badge (optional) -->
    <span class="sss-lib__seriesBadge" data-series-url="/series/series-handle/">2</span>
    <!-- Spice float (optional) -->
    <div class="sss-lib__floatSpice">🌶🌶🌶🌶</div>
    <!-- Status ribbon: JS-inserted, do not pre-render -->
    <!-- <div class="sss-lib__statusRibbon" data-book-status-ribbon>read</div> -->
    <img class="sss-lib__cover" src="https://..." alt="Book Title" loading="lazy">
  </div>
  <div class="sss-lib__under">
    <div class="sss-lib__name">Book Title</div>
    <div class="sss-lib__author">Author Name</div>
  </div>
</button>
```

**Critical notes:**
- Must be a `<button>` element (JS uses `.click()` to open from URL `?book=` param)
- All `data-*` attributes must be present even if empty string — JS reads them unconditionally
- `data-handle` + `data-title` are the identity keys; the JS uses both for matching
- DO NOT pre-render `.sss-lib__statusRibbon` — JS inserts and manages it

### Book Detail Modal

```html
<div class="sss-lib__modal" hidden aria-hidden="true">
  <!-- Backdrop click closes modal -->
  <div class="sss-lib__backdrop" data-close></div>

  <div class="sss-lib__dialog" role="dialog" aria-modal="true" aria-label="Get this book">
    <!-- Share button -->
    <button class="sss-lib__mshare" type="button" data-modal-share-btn aria-label="Share this book">
      <span class="sss-lib__mshareIcon" aria-hidden="true">📲</span>
      <span class="sss-lib__mshareLabel" data-modal-share-label>share</span>
    </button>
    <!-- Close button -->
    <button class="sss-lib__x" type="button" data-close aria-label="Close">×</button>

    <div class="sss-lib__mhead">
      <div class="sss-lib__mkicker">book breakdown</div>
      <!-- data-mseries: public=div, society=<a> tag -->
      <div class="sss-lib__mseries" data-mseries hidden></div>
      <div class="sss-lib__mseriesOrder" data-mseries-order></div>
      <div class="sss-lib__mstandalone" data-mstandalone></div>
      <div class="sss-lib__mtitle" data-mtitle>—</div>
      <div class="sss-lib__mauthor" data-mauthor></div>
    </div>

    <div class="sss-lib__mbody">
      <div class="sss-lib__mcoverWrap">
        <div class="sss-lib__mcoverFrame">
          <!-- Modal heart — JS binds and manages -->
          <span class="sss-lib__heart sss-lib__heart--modal" data-modal-heart
                role="button" aria-label="save to your bookshelf">
            <span class="sss-lib__heartIcon" data-heart-icon aria-hidden="true">♡</span>
            <span class="sss-lib__heartLabel" data-heart-label>save</span>
          </span>
          <!-- Status ribbon: JS-inserted inside .sss-lib__mcoverFrame -->
          <img class="sss-lib__mcover" alt="" loading="lazy" data-mcover>
        </div>
      </div>

      <div class="sss-lib__mcontent">
        <div class="sss-lib__mmini" data-mmini></div>
        <div class="sss-lib__mcta">
          <a class="sss-lib__mbtn sss-lib__mbtn--amazon" href="#"
             target="_blank" rel="noopener" data-amazon-btn>ku/amazon</a>
          <a class="sss-lib__mbtn sss-lib__mbtn--bookshop" href="#"
             target="_blank" rel="noopener" data-bookshop-btn>support indie bookstore</a>
        </div>
        <div class="sss-lib__mku" data-mku></div>
      </div>

      <div class="sss-lib__mbelow">
        <!-- Status controls injected here by JS: [data-modal-status-controls] -->
        <div class="sss-lib__mmeta">
          <div class="sss-lib__mtropes" data-mtropes></div>
          <div class="sss-lib__mratings">
            <div class="sss-lib__mtension" data-mtension></div>  <!-- society only -->
            <div class="sss-lib__mdamage" data-mdamage></div>    <!-- society only -->
            <div class="sss-lib__myearning" data-myearning></div> <!-- society only -->
            <div class="sss-lib__mboyfriend" data-mboyfriend></div>
            <div class="sss-lib__mdarkness" data-mdarkness></div>
            <div class="sss-lib__mreread" data-mreread></div>
          </div>
          <div class="sss-lib__mwhy" data-mwhy></div>
        </div>
        <div class="sss-lib__mdisclaimer">
          some links may be affiliate links, so thank you for supporting the recs. &lt;3
        </div>
      </div>
    </div>
  </div>
</div>
```

**Critical notes:**
- Only ONE `.sss-lib__modal` per page (JS uses `document.querySelector`)
- Society modal variant: `data-mseries` must be an `<a>` tag, not a `<div>`
- JS injects `[data-modal-status-controls]` div into `.sss-lib__mbelow` — do not pre-render
- `overflow: hidden` is set on `document.documentElement` on open; removed on close
- Escape key always closes

### Library Root (init trigger)

```html
<!-- JS binds entire system to [data-sss-lib] root -->
<div data-sss-lib="public">  <!-- or "society" -->
  <!-- all .sss-lib__book cards and the modal live inside or adjacent to this -->
</div>
```

The `libraryType` value ("public" vs "society") controls:
- Whether private shelf books have their shelf name cleared
- Whether tension/damage/yearning show in modal
- Share URL path (`/pages/library` vs `/pages/sss-library-page`)

### My Shelf Section (inline on library page)

```html
<section id="sssMyShelfSection" hidden>
  <div id="sssMyShelfGrid" class="sss-lib__grid">
    <!-- JS renders .sss-lib__book cards here from localStorage -->
  </div>
</section>
<!-- Save toast -->
<div id="sssSaveToast">
  <a id="sssToastShelfLink" href="#">view your shelf</a>
</div>
<!-- Export tools -->
<button id="sssExportNotes">copy list</button>
<button id="sssEmailShelf">email</button>
<div id="sssNotepad" hidden>
  <div id="sssNotepadBody"></div>
  <button id="sssNotepadClose">close</button>
</div>
```

### Trending Row

```html
<section id="sssTrendingShelf">
  <div id="sssTrendingRow" class="sss-lib__shelfRow">
    <!-- JS clones book cards here from Supabase data -->
  </div>
</section>
```

### Bookshelf Signup Modal

```html
<!-- Root: must have data-bbb-shelf-signup; gets class "is-open" when active -->
<div data-bbb-shelf-signup data-success="{{ success_flag }}">
  <form id="BBBBookshelfSignupForm" action="/wp-login.php" method="post">
    <!-- WP: replace action with WP registration/login endpoint or AJAX handler -->
    <input id="bbbShelfSignupEmail" type="email" name="email" required>
    <button type="submit">save my shelf</button>
  </form>
  <!-- Close buttons -->
  <button data-bbb-shelf-close>×</button>
  <!-- Manual open triggers (elsewhere on page): -->
  <!-- <button data-bbb-shelf-open>open shelf</button> -->
</div>
```

### Blog Article Book Card (`[data-book-preview]`)

```html
<!-- Used in blog-system.js, NOT sss-library.js -->
<div class="article-book-card" 
  data-book-preview
  data-handle="book-handle"
  data-title="Book Title"
  data-author="Author"
  data-cover="https://..."
  data-amazon="https://..."
  data-bookshop="https://..."
  data-newsletter="https://..."
  data-spice="3"
  data-tropes="slow-burn, grumpy-sunshine"
  data-tropes-display="🕯️ slow-burn, ☀️ grumpy-sunshine"
  data-mini="Quick summary"
  data-why="Why I loved it"
  data-ku="true"
  data-series="series-handle"
  data-series-name="Series Name"
  data-series-number="1"
>
  <!-- Heart for inline blog save -->
  <button class="article-book-card__heart" data-blog-heart
    data-title="Book Title" data-author="Author" data-cover="..." data-amazon="..." data-bookshop="...">
    <span class="article-book-card__heartIcon">♡</span>
    <span class="article-book-card__heartLabel">save</span>
  </button>
  <img src="..." alt="...">
  <div>Book Title</div>
</div>

<!-- Preview modal (one per page) -->
<div id="bbbBookPreview" hidden aria-hidden="true" style="display:none;">
  <!-- Same data-m* slots as library modal -->
  <button id="bbbPreviewClose" data-close>×</button>
  <button id="bbbPreviewShare">📲</button>
  <span data-modal-heart class="...">
    <span class="preview-heartIcon">♡</span>
    <span class="preview-heartLabel">save</span>
  </span>
  <img data-mcover>
  <div data-mtitle></div>
  <div data-mauthor></div>
  <div data-mmini></div>
  <div data-mtropes></div>
  <div data-mwhy></div>
  <div data-mku></div>
  <div data-mreread></div>  <!-- shows spice in blog-system.js, not actual reread -->
  <div data-mseries></div>
  <div data-mseries-order></div>
  <div data-mstandalone></div>
  <a data-amazon-btn></a>
  <a data-bookshop-btn></a>
</div>
```

---

## 7. Supabase Dependencies

The Supabase project URL and publishable key are embedded in `sss-library.js` and `bookshelf-signup.js`:

```js
const SUPABASE_URL = "https://efmrfxsmgbeikfgtrxjv.supabase.co";
const SUPABASE_KEY = "sb_publishable_iwjASe3QwixdDvHovaXZBQ_gbXU0Utk";
```

This is a **publishable (anon) key** — safe to keep in frontend JS. Row-level security controls write access.

### Tables used

| Table | Operations | Used by |
|---|---|---|
| `site_events` | INSERT | `sss-library.js` analytics throughout |
| `book_saves` | INSERT | `sss-library.js` on every heart save |
| `book_saves_recent_rollup` | SELECT | `sss-library.js` trending shelf |
| `book_saves_all_time_rollup` | SELECT | `sss-library.js` trending shelf fallback |
| `bookshelf_subscribers` | UPSERT | `bookshelf-signup.js` on signup |
| `bookshelf_saved_books` | UPSERT + UPDATE | `bookshelf-signup.js` on save/unsave |
| `bookshelf_book_statuses` | DELETE + INSERT | `bookshelf-signup.js` on status change |

**Keep vs. replace decision:**
- `site_events`, `book_saves`, trending rollups → **keep as-is**; no WP equivalent needed
- `bookshelf_subscribers`, `bookshelf_saved_books`, `bookshelf_book_statuses` → **decision needed**: keep Supabase sync (just change the form action from Shopify `/account` to WP endpoint) OR migrate to WP user meta. Keeping Supabase is the lower-effort path.

---

## 8. Shopify-Specific Code to Remove / Replace

| Code location | What it is | WordPress replacement |
|---|---|---|
| `window.Shopify && Shopify.designMode` (sss-library.js:28, bookshelf-signup.js:22) | Theme editor check | Remove entirely, or replace with `window.bbbAdminPreview === true` set from WP |
| `window.BBBReaderAccount` (theme.liquid:341–350) | Shopify customer object injected by Liquid | Replace inline script in WP `wp_head` or `wp_footer` hook: output `window.BBBReaderAccount = { loggedIn: <?php echo is_user_logged_in() ? 'true':'false' ?>, ... }` |
| `window.routes` (theme.liquid:351–358) | Shopify cart route URLs | Replace with WooCommerce equivalents or remove if cart drawer not ported |
| `window.shopUrl` (theme.liquid:340) | `{{ request.origin }}` | Replace with `<?php echo home_url(); ?>` |
| `/account` form action (bookshelf-signup.js, form) | Shopify account route | Change to WP AJAX endpoint: `<?php echo admin_url('admin-ajax.php'); ?>` or WooCommerce register endpoint |
| `/pages/sss-library-page` URLs (sss-library.js, multiple) | Shopify page handle URL | Replace with WP slug, e.g., `/society-library/` |
| `/pages/library` URLs (sss-library.js, getShelfRedirectUrl) | Shopify page handle URL | Replace with WP slug, e.g., `/library/` |
| `/pages/series?series=` URL pattern (sss-library.js:1587) | Shopify query-string series page | Replace with WP rewrite, e.g., `/series/[handle]/` |
| `/pages/my-bookshelf` URL (theme.liquid, bookshelf-signup.js) | Shopify page handle | Replace with WP slug, e.g., `/my-bookshelf/` |
| `document.body.dataset.template` (throughout) | Shopify template name on body | Replace with WP body class or custom `data-template` attr set by PHP |
| `global.js`: entire `Shopify` namespace (lines 314–420) | `Shopify.bind`, `Shopify.setSelectorByValue`, `CountryProvinceSelector`, etc. | Remove; these are used only by `customer.js` for address forms → WooCommerce handles this |
| `product-model.js` | Shopify 3D model viewer (XR) | Remove entirely |
| `customer.js` | Shopify address management with `Shopify.CountryProvinceSelector` | Remove; use WooCommerce My Account address forms |
| `cart.js` · `cart-drawer.js` · `cart-notification.js` | Shopify cart AJAX | Remove; WooCommerce cart handles this |
| `localization-form.js` | Shopify localization/currency picker | Remove unless multi-currency needed |
| `animations.js:99` — `if (Shopify.designMode)` | Editor check | Remove guard; animations run unconditionally |
| `quick-add.js:37–38` — `Shopify.PaymentButton.init()` | Shopify payment buttons | Remove |
| `theme-editor.js` | Shopify section editor listeners | Remove entirely |
| `window.quickOrderListStrings` (theme.liquid) | Shopify i18n strings for bulk order | Remove unless bulk order ported |
| `window.variantStrings` (theme.liquid) | Shopify product variant i18n | Replace with WooCommerce strings if needed |
| `window.cartStrings` (theme.liquid) | Shopify cart error i18n | Replace with WooCommerce |
| `window.accessibilityStrings` (theme.liquid) | Shopify a11y i18n | Keep structure, replace Liquid placeholders with PHP strings |

---

## 9. Files to Keep (port as-is with minor surgery)

| File | Keep? | Changes needed |
|---|---|---|
| `assets/sss-library.js` | ✅ Keep, port | Remove `Shopify.designMode` check; replace hardcoded `/pages/*` URLs with WP URLs; Supabase client stays |
| `assets/blog-system.js` | ✅ Keep, port | No Shopify dependencies; just replace URL strings |
| `assets/bookshelf-signup.js` | ✅ Keep, port | Replace form `action="/account"` with WP endpoint; replace `BBBReaderAccount` source; remove `Shopify.designMode` check |
| `assets/animations.js` | ✅ Keep, minor | Remove `if (Shopify.designMode)` guard |
| `assets/global.js` | ⚠️ Partial | Keep: `MenuDrawer`, `HeaderDrawer`, `ModalDialog`, `SliderComponent`, `SlideshowComponent`, `trapFocus`, `debounce`, `throttle`, `getFocusableElements`. Remove: `Shopify.*` namespace, `QuantityInput` (WooCommerce handles), `BulkAdd`, `CartPerformance`, `ProductRecommendations`, `AccountIcon` |
| `assets/search-form.js` | ✅ Keep | No Shopify deps beyond URL config |
| `assets/predictive-search.js` | ⚠️ Evaluate | Uses `window.routes.predictive_search_url` (Shopify) → replace with WP search endpoint |
| `assets/magnify.js` | ✅ Keep | Pure JS image zoom; no dependencies |
| `assets/cart.js` · `cart-drawer.js` · `cart-notification.js` | ❌ Remove | WooCommerce replaces |
| `assets/product-model.js` · `product-modal.js` | ❌ Remove | Shopify-only |
| `assets/product-form.js` · `product-info.js` | ❌ Remove | WooCommerce replaces |
| `assets/customer.js` | ❌ Remove | WooCommerce My Account replaces |
| `assets/theme-editor.js` | ❌ Remove | Shopify theme editor only |
| `assets/localization-form.js` | ❌ Remove | Shopify localization only |
| `assets/quick-add.js` · `quick-add-bulk.js` | ❌ Remove | WooCommerce AJAX add-to-cart replaces |
| `assets/pubsub.js` | ✅ Keep | Generic pub/sub utility; no Shopify deps |

---

## 10. WordPress Implementation Checklist

- [ ] **Replace `window.BBBReaderAccount`** — Output from `wp_footer` hook in `functions.php`:
  ```php
  add_action('wp_footer', function() {
    $user = wp_get_current_user();
    $is_society = in_array('bbb_society_member', $user->roles);
    echo '<script>window.BBBReaderAccount = ' . json_encode([
      'loggedIn'    => is_user_logged_in(),
      'customerId'  => $user->ID ?: null,
      'email'       => $user->user_email ?: '',
      'firstName'   => $user->first_name ?: '',
      'isSociety'   => $is_society,
      'bookshelfUrl'=> home_url('/my-bookshelf/'),
      'accountUrl'  => wc_get_account_endpoint_url('dashboard'),
      'loginUrl'    => wp_login_url(get_permalink()),
    ]) . ';</script>';
  });
  ```
- [ ] **Replace hardcoded Shopify URLs** in `sss-library.js` — Create a `window.bbbUrls` config object injected from PHP:
  ```php
  window.bbbUrls = {
    library:        '<?= home_url('/library/') ?>',
    societyLibrary: '<?= home_url('/society-library/') ?>',
    myBookshelf:    '<?= home_url('/my-bookshelf/') ?>',
    seriesBase:     '<?= home_url('/series/') ?>',
  };
  ```
  Then find/replace 6 URL strings in `sss-library.js`.
- [ ] **Bookshelf signup form action** — Change from `/account` to WP AJAX endpoint; on success redirect back with `?bbb_shelf_signup=success`; check `root.dataset.success === "true"` still works.
- [ ] **Remove `Shopify.designMode` checks** (2 occurrences: sss-library.js:28, bookshelf-signup.js:22)
- [ ] **Remove `window.Shopify` namespace** from `global.js` (lines 314–420)
- [ ] **Enqueue Supabase client** — Add to `functions.php`: `wp_enqueue_script('supabase', 'https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2', [], null, true)`; must load before `sss-library.js` and `bookshelf-signup.js`
- [ ] **Set `data-sss-lib`** attribute correctly on library wrapper divs (public vs society)
- [ ] **Set `data-template`** on `<body>` to match what `document.body.dataset.template` reads (Shopify used template name; WP can set this from `get_post_type()` or page slug)
- [ ] **`bbbPostLoginReturn` redirect** — Replicate in WP: on login success hook, check sessionStorage key and redirect if fresh
- [ ] **Trending shelf** — No change needed; Supabase credentials are portable
- [ ] **Heart save object schema** — When blog-system.js saves a book, it only saves 5 fields (title/author/cover/amazon/bookshop). Library system saves 20+. On WP, ensure book cards on blog pages include all data-attrs so `hydrateShelfBook()` can enrich stored books when the full library page is visited.
