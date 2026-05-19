-- Supabase security cleanup for public storefront analytics helpers.
--
-- Apply this in the Supabase SQL editor for project efmrfxsmgbeikfgtrxjv.
-- It locks raw write tables behind RLS and exposes only aggregate views
-- needed by the storefront.

begin;

alter table if exists public.book_saves enable row level security;
alter table if exists public.boyfriend_votes enable row level security;

drop policy if exists "allow anon inserts for book saves" on public.book_saves;
create policy "allow anon inserts for book saves"
on public.book_saves
for insert
to anon, authenticated
with check (true);

drop policy if exists "deny public reads for book saves" on public.book_saves;
create policy "deny public reads for book saves"
on public.book_saves
for select
to anon, authenticated
using (false);

drop policy if exists "allow anon inserts for boyfriend votes" on public.boyfriend_votes;
create policy "allow anon inserts for boyfriend votes"
on public.boyfriend_votes
for insert
to anon, authenticated
with check (true);

drop policy if exists "deny public reads for boyfriend votes" on public.boyfriend_votes;
create policy "deny public reads for boyfriend votes"
on public.boyfriend_votes
for select
to anon, authenticated
using (false);

create or replace view public.book_saves_recent_rollup as
with normalized as (
  select
    coalesce(
      nullif(lower(trim(book_title)), '')
    ) as book_key,
    nullif(trim(book_title), '') as book_title,
    created_at
  from public.book_saves
)
select
  book_key,
  coalesce(
    max(book_title) filter (where book_title is not null),
    book_key
  ) as book_title,
  count(*) filter (where created_at >= now() - interval '7 days') as saves_last_7_days,
  count(*) filter (where created_at >= now() - interval '30 days') as saves_last_30_days,
  max(created_at) as last_saved_at
from normalized
where book_key is not null
group by book_key
having count(*) filter (where created_at >= now() - interval '30 days') > 0;

comment on view public.book_saves_recent_rollup is
'Public aggregate view for recent book save counts. Raw public.book_saves rows stay private.';

grant select on public.book_saves_recent_rollup to anon, authenticated;

create or replace view public.boyfriend_votes_current_month as
with monthly_votes as (
  select
    trim(name) as name,
    created_at
  from public.boyfriend_votes
  where created_at >= (
    date_trunc('month', now() at time zone 'America/Los_Angeles')
    at time zone 'America/Los_Angeles'
  )
)
select
  name,
  count(*) as vote_count,
  min(created_at) as first_vote_at,
  max(created_at) as last_vote_at,
  row_number() over (
    order by count(*) desc, name asc
  ) as vote_rank
from monthly_votes
where name is not null
  and name <> ''
group by name;

comment on view public.boyfriend_votes_current_month is
'Public aggregate view for current-month boyfriend vote totals. Raw public.boyfriend_votes rows stay private.';

grant select on public.boyfriend_votes_current_month to anon, authenticated;

commit;
