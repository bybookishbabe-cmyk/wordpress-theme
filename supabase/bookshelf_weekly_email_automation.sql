alter table public.bookshelf_subscribers
  add column if not exists society_key_used_at timestamptz,
  add column if not exists society_key_source text;

create table if not exists public.bookshelf_weekly_campaigns (
  id bigint generated always as identity primary key,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  week_of date not null unique,
  status text not null default 'draft'
    check (status in ('draft', 'test_sent', 'approved', 'sending', 'sent', 'skipped')),
  preview_ready_at timestamptz,
  approved_at timestamptz,
  approved_by text,
  scheduled_send_at timestamptz,
  sent_at timestamptz,
  free_subject text,
  paid_subject text,
  empty_subject text,
  newsletter_tease_title text,
  newsletter_tease_body text,
  notes text,
  metadata jsonb not null default '{}'::jsonb
);

create index if not exists bookshelf_weekly_campaigns_status_idx
  on public.bookshelf_weekly_campaigns (status, week_of desc);

create table if not exists public.bookshelf_weekly_preview_samples (
  id bigint generated always as identity primary key,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  campaign_id bigint not null references public.bookshelf_weekly_campaigns(id) on delete cascade,
  sample_type text not null
    check (sample_type in ('free_personalized', 'paid_personalized', 'empty_shelf')),
  sample_email text,
  access_tier text not null default 'free'
    check (access_tier in ('free', 'society')),
  subject_line text,
  preview_text text,
  intro_copy text,
  latest_books jsonb not null default '[]'::jsonb,
  recommended_book jsonb not null default '{}'::jsonb,
  support_link jsonb not null default '{}'::jsonb,
  newsletter_sneak_peek jsonb not null default '{}'::jsonb,
  html_preview text,
  metadata jsonb not null default '{}'::jsonb,
  unique (campaign_id, sample_type)
);

create index if not exists bookshelf_weekly_preview_samples_campaign_idx
  on public.bookshelf_weekly_preview_samples (campaign_id, sample_type);

create table if not exists public.bookshelf_weekly_send_queue (
  id bigint generated always as identity primary key,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  campaign_id bigint not null references public.bookshelf_weekly_campaigns(id) on delete cascade,
  subscriber_id bigint references public.bookshelf_subscribers(id) on delete set null,
  email_normalized text not null,
  access_tier text not null default 'free'
    check (access_tier in ('free', 'society')),
  send_variant text not null
    check (send_variant in ('free_personalized', 'paid_personalized', 'empty_shelf')),
  queue_status text not null default 'queued'
    check (queue_status in ('queued', 'sending', 'sent', 'failed', 'skipped')),
  latest_books jsonb not null default '[]'::jsonb,
  recommended_book jsonb not null default '{}'::jsonb,
  support_link jsonb not null default '{}'::jsonb,
  newsletter_sneak_peek jsonb not null default '{}'::jsonb,
  payload jsonb not null default '{}'::jsonb,
  flow_run_id text,
  send_error text,
  queued_at timestamptz not null default now(),
  sent_at timestamptz,
  skipped_at timestamptz,
  metadata jsonb not null default '{}'::jsonb,
  unique (campaign_id, email_normalized)
);

create index if not exists bookshelf_weekly_send_queue_campaign_idx
  on public.bookshelf_weekly_send_queue (campaign_id, queue_status);

create index if not exists bookshelf_weekly_send_queue_email_idx
  on public.bookshelf_weekly_send_queue (email_normalized, queued_at desc);

create or replace view public.bookshelf_latest_three_books as
select
  ranked.email_normalized,
  ranked.shopify_customer_id,
  ranked.customer_email,
  jsonb_agg(
    jsonb_build_object(
      'book_key', ranked.book_key,
      'book_handle', ranked.book_handle,
      'book_title', ranked.book_title,
      'author', ranked.author,
      'cover', ranked.cover,
      'amazon', ranked.amazon,
      'bookshop', ranked.bookshop,
      'shelf_name', ranked.shelf_name,
      'spice_level', ranked.spice_level,
      'darkness_level', ranked.darkness_level,
      'tropes', ranked.tropes,
      'saved_at', ranked.saved_at
    )
    order by ranked.saved_at desc
  ) as latest_books
from (
  select
    sb.*,
    row_number() over (
      partition by sb.email_normalized
      order by sb.saved_at desc, sb.id desc
    ) as row_num
  from public.bookshelf_saved_books sb
  where sb.is_active = true
) ranked
where ranked.row_num <= 3
group by ranked.email_normalized, ranked.shopify_customer_id, ranked.customer_email;

alter table public.bookshelf_weekly_campaigns enable row level security;
alter table public.bookshelf_weekly_preview_samples enable row level security;
alter table public.bookshelf_weekly_send_queue enable row level security;

drop policy if exists "allow service role manage weekly campaigns" on public.bookshelf_weekly_campaigns;
create policy "allow service role manage weekly campaigns"
on public.bookshelf_weekly_campaigns
for all
to authenticated, anon
using (true)
with check (true);

drop policy if exists "deny public reads for weekly campaigns" on public.bookshelf_weekly_campaigns;
create policy "deny public reads for weekly campaigns"
on public.bookshelf_weekly_campaigns
for select
to anon, authenticated
using (false);

drop policy if exists "allow service role manage weekly preview samples" on public.bookshelf_weekly_preview_samples;
create policy "allow service role manage weekly preview samples"
on public.bookshelf_weekly_preview_samples
for all
to authenticated, anon
using (true)
with check (true);

drop policy if exists "deny public reads for weekly preview samples" on public.bookshelf_weekly_preview_samples;
create policy "deny public reads for weekly preview samples"
on public.bookshelf_weekly_preview_samples
for select
to anon, authenticated
using (false);

drop policy if exists "allow service role manage weekly send queue" on public.bookshelf_weekly_send_queue;
create policy "allow service role manage weekly send queue"
on public.bookshelf_weekly_send_queue
for all
to authenticated, anon
using (true)
with check (true);

drop policy if exists "deny public reads for weekly send queue" on public.bookshelf_weekly_send_queue;
create policy "deny public reads for weekly send queue"
on public.bookshelf_weekly_send_queue
for select
to anon, authenticated
using (false);

drop trigger if exists bookshelf_weekly_campaigns_set_updated_at on public.bookshelf_weekly_campaigns;
create trigger bookshelf_weekly_campaigns_set_updated_at
before update on public.bookshelf_weekly_campaigns
for each row execute function public.bump_bookshelf_updated_at();

drop trigger if exists bookshelf_weekly_preview_samples_set_updated_at on public.bookshelf_weekly_preview_samples;
create trigger bookshelf_weekly_preview_samples_set_updated_at
before update on public.bookshelf_weekly_preview_samples
for each row execute function public.bump_bookshelf_updated_at();

drop trigger if exists bookshelf_weekly_send_queue_set_updated_at on public.bookshelf_weekly_send_queue;
create trigger bookshelf_weekly_send_queue_set_updated_at
before update on public.bookshelf_weekly_send_queue
for each row execute function public.bump_bookshelf_updated_at();

create or replace function public.get_latest_bookshelf_weekly_preview()
returns jsonb
language sql
security definer
set search_path = public
as $$
  with latest_campaign as (
    select *
    from public.bookshelf_weekly_campaigns
    order by week_of desc, created_at desc
    limit 1
  ),
  preview_rows as (
    select *
    from public.bookshelf_weekly_preview_samples
    where campaign_id = (select id from latest_campaign)
  )
  select jsonb_build_object(
    'week_of', latest_campaign.week_of,
    'run_week', latest_campaign.week_of,
    'status', latest_campaign.status,
    'scheduled_send_at', latest_campaign.scheduled_send_at,
    'approved_by', coalesce(latest_campaign.approved_by, ''),
    'notes', coalesce(latest_campaign.notes, ''),
    'free_sample', coalesce(
      (
        select jsonb_build_object(
          'subject_line', sample.subject_line,
          'preview_text', sample.preview_text,
          'intro_copy', sample.intro_copy,
          'latest_books', sample.latest_books,
          'recommended_book', sample.recommended_book,
          'support_link', sample.support_link,
          'newsletter_sneak_peek', sample.newsletter_sneak_peek,
          'metadata', sample.metadata
        )
        from preview_rows sample
        where sample.sample_type = 'free_personalized'
        limit 1
      ),
      '{}'::jsonb
    ),
    'paid_sample', coalesce(
      (
        select jsonb_build_object(
          'subject_line', sample.subject_line,
          'preview_text', sample.preview_text,
          'intro_copy', sample.intro_copy,
          'latest_books', sample.latest_books,
          'recommended_book', sample.recommended_book,
          'support_link', sample.support_link,
          'newsletter_sneak_peek', sample.newsletter_sneak_peek,
          'metadata', sample.metadata
        )
        from preview_rows sample
        where sample.sample_type = 'paid_personalized'
        limit 1
      ),
      '{}'::jsonb
    ),
    'empty_shelf_sample', coalesce(
      (
        select jsonb_build_object(
          'subject_line', sample.subject_line,
          'preview_text', sample.preview_text,
          'intro_copy', sample.intro_copy,
          'latest_books', sample.latest_books,
          'recommended_book', sample.recommended_book,
          'support_link', sample.support_link,
          'newsletter_sneak_peek', sample.newsletter_sneak_peek,
          'metadata', sample.metadata
        )
        from preview_rows sample
        where sample.sample_type = 'empty_shelf'
        limit 1
      ),
      '{}'::jsonb
    )
  )
  from latest_campaign;
$$;

grant execute on function public.get_latest_bookshelf_weekly_preview() to anon, authenticated;
