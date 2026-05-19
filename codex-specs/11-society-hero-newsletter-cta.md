# Codex Spec · Task 11: Society Hero / Newsletter CTA
**Section:** `society-hero` → `sections/society-hero.liquid`
**Homepage position:** `society_hero_qen47C` (index.json order slot 8)

---

## Files to Create

| File | Purpose |
|------|---------|
| `template-parts/sections/section-society-hero.php` | Main section template |
| `inc/newsletter-issue-cpt.php` | `newsletter_issue` custom post type + ACF fields |
| `assets/css/section-society-hero.css` | All section CSS (exact class names) |
| `assets/js/section-society-hero.js` | Emoji rain JS |
| `inc/acf-society-hero-options.php` | ACF options page fields for section settings |

---

## WordPress Replacements for Liquid Variables

| Liquid | WordPress Replacement |
|--------|----------------------|
| `shop.metaobjects.newsletter_issue.values` | WP_Query on CPT `newsletter_issue`, ordered by `publish_date` ACF date field |
| `issue.publish_date.value` | ACF field `publish_date` (date picker, stored as `Ymd`) |
| `issue.url.value` | ACF field `issue_url` (URL field) |
| `issue.preview.value` | ACF field `preview_image` (image field, returns array) |
| `issue.label.value` | ACF field `issue_label` (text) |
| `issue.issue_no.value` | ACF field `issue_no` (number) |
| `issue.title.value` | `get_the_title()` on the post |
| `issue.subtitle.value` | ACF field `issue_subtitle` (textarea) |
| `section.settings.kicker` | ACF options field `sh_kicker` |
| `section.settings.title` | ACF options field `sh_title` |
| `section.settings.subtitle` | ACF options field `sh_subtitle` |
| `section.settings.society_title` | ACF options field `sh_society_title` |
| `section.settings.society_text` | ACF options field `sh_society_text` |
| `section.settings.society_url` | ACF options field `sh_society_url` |

---

## Latest Newsletter Query Logic

Liquid does this:
1. Loops all `newsletter_issue` metaobjects
2. Parses `publish_date` as unix timestamp
3. Adds 10 hours (36000s) = **live_ts** (10-hour delay after publish date)
4. Keeps the issue with the **highest `publish_date`** that is also `live_ts <= now`

**WordPress equivalent (PHP):**

```php
$now = current_time('timestamp');
$ten_hours = 10 * 60 * 60;

$issues = get_posts([
    'post_type'      => 'newsletter_issue',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
    'meta_key'       => 'publish_date',
    'orderby'        => 'meta_value',
    'order'          => 'DESC',
]);

$latest_issue = null;
$latest_ts    = 0;

foreach ( $issues as $issue ) {
    $raw = get_field( 'publish_date', $issue->ID ); // ACF returns 'Ymd'
    if ( ! $raw ) continue;
    $ts      = strtotime( $raw );
    $live_ts = $ts + $ten_hours;
    if ( $live_ts <= $now && $ts > $latest_ts ) {
        $latest_ts    = $ts;
        $latest_issue = $issue;
    }
}
```

---

## NEW Badge Logic

Show `.bbb-nc__badge` (`new`) if:
```
(now - live_ts) < 604800   // 7 days in seconds
```

```php
$is_new = false;
if ( $latest_issue ) {
    $live_ts = $latest_ts + $ten_hours;
    $diff    = $now - $live_ts;
    $is_new  = ( $diff < 604800 );
}
```

---

## Preview Image Logic

Liquid tries three fallbacks:
1. `issue.preview.value.url` — direct URL string
2. `issue.preview.value.preview_image` — image object with `.preview_image`
3. `issue.preview.value` — image object directly → `image_url: width: 900`

**WordPress equivalent:**
```php
$preview_img = '';
if ( $latest_issue ) {
    $img_field = get_field( 'preview_image', $latest_issue->ID ); // ACF image (array)
    if ( ! empty( $img_field['url'] ) ) {
        $preview_img = $img_field['url'];
    }
}
// Render at max-width 220px per CSS; no srcset needed.
```

---

## Exact DOM Structure

```html
<section class="bbb-newsletter-cta" id="bbb-newsletter-cta-society-hero">

  <!-- Emoji rain layer -->
  <div class="bbb-newsletter-cta__rain" aria-hidden="true"></div>

  <div class="bbb-newsletter-cta__wrap page-width">

    <!-- Header -->
    <header class="bbb-newsletter-cta__head">
      <p class="bbb-newsletter-cta__kicker">{sh_kicker}</p>
      <h2 class="bbb-newsletter-cta__title">{sh_title}</h2>
      <p class="bbb-newsletter-cta__sub">{sh_subtitle}</p>
    </header>

    <div class="bbb-newsletter-cta__grid">

      <!-- LEFT: latest newsletter edition -->
      <article class="bbb-nc bbb-nc--latest">
        <a class="bbb-nc__latestLink" href="{issue_url}" target="_blank" rel="noopener">
          <div class="bbb-nc__latest">
            <div class="bbb-nc__copy">

              <div class="bbb-nc__meta">
                <span>{publish_date formatted: "M j, Y"}</span>
                <span>issue {issue_no}</span>
              </div>

              <div class="bbb-nc__top">
                <p class="bbb-nc__kicker">{issue_label | fallback: "latest edition ✦"}</p>
                <!-- Conditional NEW badge -->
                {if is_new}
                <span class="bbb-nc__badge" aria-label="New">new</span>
                {/if}
              </div>

              <div class="bbb-nc__rule"></div>

              <h3 class="bbb-nc__title">{issue title}</h3>

              <p class="bbb-nc__desc">
                {issue_subtitle | fallback: "one book a week. quotes, recs, and reader-core chaos."}
              </p>

              {if preview_img}
              <div class="bbb-nc__img">
                <img src="{preview_img}" alt="{issue title}" loading="lazy">
              </div>
              {/if}

              <div class="bbb-nc__link bbb-nc__link--primary">
                read the latest newsletter →
              </div>

            </div>
          </div>
        </a>
      </article>

      <!-- RIGHT: society card -->
      <article class="bbb-nc bbb-nc--society">
        <a class="bbb-nc__societyLink" href="{sh_society_url}">
          <div class="bbb-nc__societyInner">
            <p class="bbb-nc__kicker">the society ♡</p>
            <h3 class="bbb-nc__title">{sh_society_title}</h3>
            <p class="bbb-nc__desc">{sh_society_text}</p>
            <div class="bbb-nc__link">learn more →</div>
            <p class="bbb-nc__fineprint">🖤 includes the archive, reading lists, and fictional-men problems (organized).</p>
          </div>
        </a>
      </article>

    </div><!-- /.bbb-newsletter-cta__grid -->
  </div><!-- /.bbb-newsletter-cta__wrap -->
</section>
```

> **No fallback state needed** if `latest_issue` is null — just omit the `<article class="bbb-nc bbb-nc--latest">` content. Optionally show: `<p class="bbb-nc__desc">no newsletter issues yet.</p>`

---

## CSS (copy verbatim into `assets/css/section-society-hero.css`)

```css
.bbb-newsletter-cta {
  position: relative;
  overflow: hidden;
  padding: clamp(44px, 6vw, 92px) 0;
  background: #0b0b0b;
  color: #f6f6f6;
}

.bbb-newsletter-cta__wrap { position: relative; z-index: 2; }

.bbb-newsletter-cta__head {
  max-width: 980px;
  margin: 0 auto 22px;
}
.bbb-newsletter-cta__kicker {
  margin: 0 0 10px;
  font-size: 11px;
  letter-spacing: .16em;
  text-transform: uppercase;
  opacity: .7;
}
.bbb-newsletter-cta__title {
  margin: 0;
  font-size: clamp(30px, 4.2vw, 52px);
  line-height: 1.02;
  font-weight: 400;
  text-transform: lowercase;
}
.bbb-newsletter-cta__sub {
  margin: 10px 0 0;
  max-width: 70ch;
  font-size: 14px;
  line-height: 1.65;
  color: rgba(246,246,246,.72);
  text-transform: lowercase;
}

.bbb-newsletter-cta__grid {
  max-width: 980px;
  margin: 26px auto 0;
  display: grid;
  grid-template-columns: 1.15fr .85fr;
  gap: 0;
  align-items: stretch;
}

.bbb-nc {
  position: relative;
  border-radius: 0;
  border: 1px solid rgba(255,255,255,.14);
  background: rgba(255,255,255,.04);
  box-shadow: 0 18px 46px rgba(0,0,0,.38);
  overflow: hidden;
}

.bbb-nc__latestLink {
  display: block;
  height: 100%;
  color: inherit;
  text-decoration: none;
}
.bbb-nc__societyLink {
  display: flex;
  height: 100%;
  color: inherit;
  text-decoration: none;
}

.bbb-nc::before {
  content: "";
  position: absolute;
  inset: -2px -2px auto -2px;
  height: 46px;
  background: linear-gradient(180deg, rgba(255,255,255,.05), transparent);
  pointer-events: none;
}

.bbb-nc__top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.bbb-nc__meta {
  display: flex;
  justify-content: space-between;
  gap: 10px;
  align-items: flex-end;
  font-size: 10px;
  letter-spacing: .14em;
  text-transform: uppercase;
  color: rgba(246,246,246,.58);
}

.bbb-nc__kicker {
  margin: 0;
  font-size: 10px;
  letter-spacing: .16em;
  text-transform: uppercase;
  color: rgba(246,246,246,.70);
}

.bbb-nc__badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 6px 10px;
  border-radius: 999px;
  font-size: 10px;
  letter-spacing: .14em;
  text-transform: uppercase;
  color: #111;
  background: rgba(255,255,255,.92);
}

.bbb-nc__rule {
  height: 1px;
  background: rgba(255,255,255,.10);
  margin: 12px 0;
}

.bbb-nc__latest {
  padding: 14px;
  display: block;
}

.bbb-nc__img {
  width: min(100%, 220px);
  margin-top: 12px;
  border-radius: 16px;
  border: 1px solid rgba(255,255,255,.16);
  overflow: hidden;
  background: rgba(255,255,255,.02);
}
.bbb-nc__img img {
  width: 100%;
  height: auto;
  aspect-ratio: 4/5;
  object-fit: cover;
  display: block;
  transform: scale(1.01);
}

.bbb-nc__title {
  margin: 0;
  font-size: 17px;
  line-height: 1.18;
  font-weight: 400;
  text-transform: lowercase;
  color: #fff;
}

.bbb-nc__desc {
  margin: 8px 0 0;
  font-size: 12px;
  line-height: 1.55;
  color: rgba(246,246,246,.76);
  text-transform: lowercase;
}

.bbb-nc__link {
  display: inline-block;
  margin-top: 12px;
  font-size: 11px;
  letter-spacing: .16em;
  text-transform: uppercase;
  text-decoration: none;
  color: #ff8ac7;
  border-bottom: 1px solid rgba(255,138,199,.75);
  padding-bottom: 2px;
}
.bbb-nc__link--primary {
  color: #ff8ac7;
  border-bottom-color: #ff8ac7;
}

.bbb-nc__fineprint {
  margin: 14px 18px 18px;
  font-size: 12.5px;
  line-height: 1.45;
  color: rgba(246,246,246,.60);
  text-transform: lowercase;
}

/* Society card */
.bbb-nc--society {
  padding: 0;
  border-top-right-radius: 22px;
  border-bottom-right-radius: 22px;
}
.bbb-nc__societyInner {
  padding: 18px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  min-height: 100%;
}
.bbb-nc--society .bbb-nc__title { font-size: 22px; }
.bbb-nc--society .bbb-nc__fineprint { margin: 14px 0 0; padding: 0; }

/* Latest card */
.bbb-nc--latest {
  max-width: none;
  justify-self: stretch;
  border-top-left-radius: 22px;
  border-bottom-left-radius: 22px;
  border-right-width: 0;
}

/* Emoji rain */
.bbb-newsletter-cta__rain {
  position: absolute;
  inset: 0;
  z-index: 1;
  pointer-events: none;
  overflow: hidden;
}
.bbb-rain-emoji {
  position: absolute;
  top: -40px;
  opacity: .22;
  filter: blur(0px) drop-shadow(0 10px 24px rgba(0,0,0,.35));
  animation: bbb-fall var(--dur, 22s) linear infinite;
  transform: translateY(-40px);
  font-size: var(--size, 20px);
}
@keyframes bbb-fall {
  to {
    transform:
      translateY(120vh)
      translateX(var(--drift, 0px))
      rotate(var(--rot, 0deg));
  }
}

/* Responsive */
@media (max-width: 860px) {
  .bbb-newsletter-cta__grid { grid-template-columns: 1fr; }
  .bbb-nc--latest {
    border-radius: 22px 22px 0 0;
    border-right-width: 1px;
    border-bottom-width: 0;
  }
  .bbb-nc--society { border-radius: 0 0 22px 22px; }
  .bbb-nc__img { width: min(100%, 260px); }
}
```

---

## Emoji Rain JS (copy verbatim into `assets/js/section-society-hero.js`)

```js
(function () {
  const section = document.getElementById('bbb-newsletter-cta-society-hero');
  if (!section) return;

  const rain = section.querySelector('.bbb-newsletter-cta__rain');
  if (!rain) return;

  const EMOJIS = ['📚', '🖤', '🤍', '📖', '✨'];
  const COUNT  = 26;

  rain.innerHTML = ''; // prevent duplicates on re-render

  for (let i = 0; i < COUNT; i++) {
    const el    = document.createElement('span');
    el.className = 'bbb-rain-emoji';
    el.textContent = EMOJIS[Math.floor(Math.random() * EMOJIS.length)];

    const left  = Math.random() * 100;
    const size  = 14 + Math.random() * 18;          // 14–32px
    const dur   = 18 + Math.random() * 22;          // 18–40s (slow)
    const delay = Math.random() * dur * -1;          // pre-stagger
    const drift = Math.random() * 120 - 60;         // -60..60px
    const rot   = (Math.random() * 140 - 70) + 'deg'; // -70..70deg

    el.style.left = left + '%';
    el.style.setProperty('--size',  size  + 'px');
    el.style.setProperty('--dur',   dur   + 's');
    el.style.setProperty('--drift', drift + 'px');
    el.style.setProperty('--rot',   rot);
    el.style.animationDelay = delay + 's';

    rain.appendChild(el);
  }
})();
```

> **Note:** The section `id` is hardcoded as `bbb-newsletter-cta-society-hero` in WordPress (replacing Shopify's `section.id` dynamic value). Keep it static.

---

## ACF Options Page Fields (`sh_` prefix)

Register these on an ACF options page (or as post meta on a dedicated page):

| Field Key | Label | Type | Default |
|-----------|-------|------|---------|
| `sh_kicker` | Kicker | text | `for the bookaholics who love romance` |
| `sh_title` | Title | text | `the smut & sentiment society` |
| `sh_subtitle` | Subtitle | text | `weekly letters, obsessive recs, and reader-core you pretend you're not addicted to.` |
| `sh_society_title` | Society box title | text | `inside the society` |
| `sh_society_text` | Society box text | textarea | `the archive. reading lists. the fictional men problem. a tasteful amount of chaos.` |
| `sh_society_url` | Society page URL | url | `/pages/smut-sentiment-society` |

---

## `newsletter_issue` CPT + ACF Fields

**CPT slug:** `newsletter_issue`
**Post title** = issue title (replaces `issue.title.value`)

| ACF Field Key | Label | Type | Notes |
|---------------|-------|------|-------|
| `publish_date` | Publish Date | date picker (return: `Ymd`) | Used for sorting + live_ts logic |
| `issue_url` | Issue URL | url | Link to Substack issue |
| `issue_no` | Issue Number | number | Displayed as "issue X" |
| `issue_label` | Label / Kicker | text | Fallback: "latest edition ✦" |
| `issue_subtitle` | Subtitle | textarea | Fallback: hardcoded string |
| `preview_image` | Preview Image | image (return: array) | `['url']` used directly |

---

## Society Card Settings (hardcoded in template)

These values come from `section.settings` in Shopify but should be ACF options in WordPress:

- **Kicker:** `the society ♡` ← hardcoded in DOM, not a setting
- **Fine print:** `🖤 includes the archive, reading lists, and fictional-men problems (organized).` ← hardcoded in DOM, not a setting
- **Title / text / URL:** from ACF options (`sh_society_title`, `sh_society_text`, `sh_society_url`)

---

## Enqueue in `functions.php`

```php
wp_enqueue_style(
    'section-society-hero',
    get_template_directory_uri() . '/assets/css/section-society-hero.css',
    [], '1.0'
);
wp_enqueue_script(
    'section-society-hero',
    get_template_directory_uri() . '/assets/js/section-society-hero.js',
    [], '1.0', true  // load in footer
);
```

---

## Do Not

- Do not rename any CSS classes
- Do not redesign the two-column layout
- Do not use Substack's embed widget — the section only links out to Substack
- Do not add a subscribe form to this section (it is a CTA card that links, not an inline form)
