# Shopify Flow Playbook: Bookshelf Weekly Email

This is the operational plan for the Friday bookshelf email using Shopify + Flow.

The weekly preview system is now:

- `Shopify Flow` for scheduling
- `Supabase Edge Functions` for preview generation
- `Supabase RPC` for preview-page reads

No Shopify Admin API app or shop metafield is required for the preview side.

## What Flow does

Flow is the scheduler and gatekeeper.

Use it for:

- Friday morning prep
- Friday 4:00 PM Pacific send
- optional test send to yourself later
- approval gating

Do **not** make Flow do the recommendation logic itself.

Let Supabase generate:

- latest 3 saved books
- recommended book
- supporting blog / trope / quiz link
- paid newsletter sneak peek slot

## Flow 1: Friday morning preview prep

### Suggested name

`Bookshelf Weekly Preview Prep`

### Schedule

- every Friday morning in `America/Los_Angeles`
- recommended: `9:00 AM`

### Goal

Create the current week’s preview payload and sample records.

### Suggested actions

1. trigger scheduled workflow
2. send an HTTP request to your Supabase Edge Function
3. that endpoint should:
   - create or update `bookshelf_weekly_campaigns`
   - write `bookshelf_weekly_preview_samples`
   - refresh `bookshelf_weekly_send_queue`
4. optionally send a note or test email to yourself later

### Preview generator endpoint

Deploy the function in:

- `supabase/functions/bookshelf-weekly-preview/index.ts`

Suggested deploy command:

```bash
supabase functions deploy bookshelf-weekly-preview --no-verify-jwt
```

Then call the public function URL:

- `POST https://<project-ref>.supabase.co/functions/v1/bookshelf-weekly-preview`

Suggested JSON body:

```json
{
  "teaseTitle": "this week inside the society",
  "teaseBody": "your paid-only sneak peek goes here before you approve the send.",
  "notes": "optional internal note for this week"
}
```

Required function secrets:

- `SUPABASE_URL`
- `SUPABASE_SERVICE_ROLE_KEY`
- `BOOKSHELF_WEEKLY_ADMIN_SECRET`

Suggested Flow request header:

- `x-admin-secret: <your BOOKSHELF_WEEKLY_ADMIN_SECRET>`

### Output

After this run, you should be able to visit:

- `/pages/bookshelf-weekly-preview`

and review:

- free sample
- paid sample
- empty shelf sample

The preview page reads from the public RPC:

- `public.get_latest_bookshelf_weekly_preview()`

## Flow 2: Friday 4 PM send

### Suggested name

`Bookshelf Weekly Send`

### Schedule

- every Friday
- `4:00 PM`
- `America/Los_Angeles`

### Required gate

Before sending, confirm the current campaign:

- exists for the current week
- has `status = approved`

If not:

- do not send
- optionally mark `status = skipped`

### Send action

Once approved:

1. load recipients from `bookshelf_weekly_send_queue`
2. split by:
   - `free_personalized`
   - `paid_personalized`
   - `empty_shelf`
3. send the correct email template / payload for each
4. mark queue rows:
   - `sent`
   - `failed`
   - `skipped`
5. mark campaign:
   - `status = sent`
   - `sent_at = now()`

For now, keep the Friday 4 PM Flow as a scheduled shell until the send endpoint is wired. The preview generator comes first.

## Paid status rule

The prep job should treat a subscriber as paid when:

- `society_key_used_at` exists

Supabase is the source of truth here.

## Suggested approval workflow

Approve in Supabase by updating:

- `bookshelf_weekly_campaigns.status = approved`
- `approved_at`
- `approved_by`

That keeps approval state in one place and makes the 4 PM gate straightforward.

## Test send recommendation

Best practice:

- after Friday morning prep
- send test versions to your own address first

Suggested tests:

- free sample
- paid sample
- empty shelf sample

Then approve the live send.

## Minimum viable launch

1. prep flow runs Friday morning
2. preview page updates
3. you review and approve
4. send flow runs Friday at 4 PM if approved

That is the safest first live version.
