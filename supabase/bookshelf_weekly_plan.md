# Bookshelf Weekly Email System

This is the Friday bookshelf email layer for bybookishbabe.

It is built around:

- `Supabase` for subscriber state, latest saves, preview samples, and the send queue
- `Shopify + Flow` for timing and orchestration
- a theme preview page at `/pages/bookshelf-weekly-preview`

The preview path is now Supabase-only:

- the Edge Function generates weekly preview data
- the preview page reads the latest campaign from Supabase RPC
- no Shopify Admin API app is required for preview generation

## Goal

Send a weekly bookshelf email every Friday at 4:00 PM Pacific that feels personal, light, and genuinely useful.

### Free readers get

- their latest 3 saved books
- 1 book recommendation based on those saves
- 1 supporting click:
  - a blog guide
  - a quiz
  - or a trope / genre page

### Paid society readers get

Everything above, plus:

- a sneak peek of what Sunday’s society newsletter will be about

### Empty-shelf readers get

If they signed up but have not saved books yet:

- an empty-shelf note
- starter trope / genre links
- 1–2 blog links or a quiz to pull them back in

## Paid vs free

The weekly system should treat a subscriber as paid when they have used the society key.

### Data rule

Use these fields on `bookshelf_subscribers`:

- `access_tier`
- `society_key_used_at`
- `society_key_source`

Preferred logic:

- if `society_key_used_at` is present -> paid / society
- otherwise -> free

## Friday workflow

### 1. Friday morning preview run

Flow kicks off the preparation job on Friday morning.

The prep job should:

1. create or update the current week’s row in `bookshelf_weekly_campaigns`
2. generate preview samples:
   - `free_personalized`
   - `paid_personalized`
   - `empty_shelf`
3. write those samples into:
   - `bookshelf_weekly_preview_samples`
4. refresh `bookshelf_weekly_send_queue`
5. mark campaign status as `draft`

Current implementation target:

- `POST https://<project-ref>.supabase.co/functions/v1/bookshelf-weekly-preview`

That function now handles:

- subscriber pull from Supabase
- paid/free split from `society_key_used_at`
- latest 3 active saved books
- one curated recommendation chosen from cluster logic
- queue refresh in `bookshelf_weekly_send_queue`
- preview samples for the preview page

Recommended deploy:

```bash
supabase functions deploy bookshelf-weekly-preview --no-verify-jwt
```

Recommended auth:

- pass `x-admin-secret` from Shopify Flow
- compare it against `BOOKSHELF_WEEKLY_ADMIN_SECRET`

The preview page reads the latest campaign through:

- `public.get_latest_bookshelf_weekly_preview()`

### 2. Human approval

Friday morning, review:

- the free sample
- the paid sample
- the empty-shelf sample
- the paid sneak peek copy

Then either:

- approve it
- tweak the paid sneak peek / notes
- or skip the week

### 3. Friday 4:00 PM Pacific send

Flow should only send if the campaign is approved.

Required check:

- `bookshelf_weekly_campaigns.status = 'approved'`

If not approved:

- do not send
- optionally mark the campaign as `skipped`

## Recommendation logic

### Subscribers with saved books

Use the 3 most recently saved active books.

Then:

1. exclude books already saved
2. exclude later books in interconnected series unless:
   - book 1
   - or marked standalone-safe
3. infer a cluster from saved-book titles, shelf names, and tropes
4. pick 1 curated recommendation for that cluster
5. pick 1 supporting click:
   - blog guide first when strong
   - otherwise trope / genre page
   - otherwise quiz

### Empty shelf fallback

If `latest_books` is empty:

- do not try to force a book rec
- send:
  - a “your shelf is empty” intro
  - 3–6 trope / genre links
  - 1–2 blog / quiz links

## Preview page

Theme template:

- `templates/page.bookshelf-weekly-preview.json`

Theme section:

- `sections/bookshelf-weekly-preview.liquid`

Snippet:

- `snippets/bookshelf-weekly-preview-card.liquid`

The preview page now calls Supabase directly:

- `POST /rest/v1/rpc/get_latest_bookshelf_weekly_preview`
- using the store’s Supabase publishable key

### Suggested preview payload

```json
{
  "week_of": "2026-04-24",
  "status": "draft",
  "scheduled_send_at": "2026-04-24T16:00:00-07:00",
  "approved_by": "",
  "notes": "swap in this week’s sunday tease before approval",
  "free_sample": {
    "subject_line": "your next shelf pick is here",
    "preview_text": "3 recent saves, 1 next read, 1 thing to click next.",
    "intro_copy": "your latest shelf picks are looking a little romantically unwell in the best way.",
    "latest_books": [
      { "book_title": "The Ever King", "author": "L.J. Andrews" },
      { "book_title": "A Court This Cruel & Lovely", "author": "Stacia Stark" },
      { "book_title": "Quicksilver", "author": "Callie Hart" }
    ],
    "recommended_book": {
      "title": "The Ever King",
      "reason": "dark fantasy pull, villain energy, and obsession that actually commits."
    },
    "support_link": {
      "title": "villain gets the girl",
      "description": "browse the full trope page",
      "url": "https://bybookishbabe.com/pages/villain-gets-the-girl-romance-books"
    }
  },
  "paid_sample": {
    "subject_line": "your shelf this week + a little sunday preview",
    "preview_text": "your next read plus a private look at what’s coming sunday.",
    "intro_copy": "your shelf is chaos, so naturally I pulled one more thing for you.",
    "latest_books": [],
    "recommended_book": {},
    "support_link": {},
    "newsletter_sneak_peek": {
      "title": "inside sunday’s note",
      "body": "we’re talking about the kind of dark romantasy that feels like obsession dressed up as destiny."
    }
  },
  "empty_shelf_sample": {
    "subject_line": "your shelf is empty, so start here",
    "preview_text": "pick a trope, pick a mood, and let’s get your shelf moving.",
    "intro_copy": "you signed up, but your shelf is still empty. let’s fix that.",
    "latest_books": [],
    "recommended_book": {},
    "support_link": {
      "title": "start with a lane that already sounds like you",
      "description": "pick a trope, genre, or quiz below and let the shelf build itself from there.",
      "url": "https://bybookishbabe.com/pages/library"
    }
  }
}
```

## Supabase tables involved

Existing:

- `bookshelf_subscribers`
- `bookshelf_saved_books`
- `bookshelf_book_statuses`
- `bookshelf_weekly_recs`

New:

- `bookshelf_weekly_campaigns`
- `bookshelf_weekly_preview_samples`
- `bookshelf_weekly_send_queue`
- view: `bookshelf_latest_three_books`
- RPC: `public.get_latest_bookshelf_weekly_preview()`

See:

- `supabase/bookshelf_weekly_email_automation.sql`

## Shopify Flow playbook

See:

- `supabase/bookshelf_weekly_flow.md`

## First live implementation order

1. run the SQL in:
   - `supabase/bookshelf_signup.sql`
   - `supabase/bookshelf_weekly_email_automation.sql`
2. deploy the preview generator function
3. create a Shopify page using:
   - template `page.bookshelf-weekly-preview`
4. add the Friday 9 AM and Friday 4 PM Flow schedules
5. replace the 9 AM placeholder with an HTTP request to the edge function
6. review the preview page
7. approve the campaign
8. wire the send job, then let Flow send at 4:00 PM Pacific only when approved
