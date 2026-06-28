create table if not exists public.blog_polls (
  id uuid primary key default gen_random_uuid(),
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  poll_key text not null unique,
  post_id integer not null,
  post_url text,
  question text not null,
  options jsonb not null default '[]'::jsonb,
  status text not null default 'active'
);

create index if not exists blog_polls_post_id_idx
  on public.blog_polls (post_id);

create index if not exists blog_polls_status_idx
  on public.blog_polls (status);

alter table public.blog_polls enable row level security;

drop policy if exists "deny public reads for blog polls" on public.blog_polls;
create policy "deny public reads for blog polls"
on public.blog_polls
for select
to anon, authenticated
using (false);

drop policy if exists "deny public writes for blog polls" on public.blog_polls;
create policy "deny public writes for blog polls"
on public.blog_polls
for all
to anon, authenticated
using (false)
with check (false);

create table if not exists public.blog_poll_votes (
  id uuid primary key default gen_random_uuid(),
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  poll_key text not null,
  post_id integer not null,
  post_url text,
  option_key text not null,
  voter_hash text not null,
  reader_email_hash text,
  user_agent text
);

create unique index if not exists blog_poll_votes_unique_voter_idx
  on public.blog_poll_votes (poll_key, voter_hash);

create index if not exists blog_poll_votes_poll_post_idx
  on public.blog_poll_votes (poll_key, post_id);

create index if not exists blog_poll_votes_created_at_idx
  on public.blog_poll_votes (created_at desc);

alter table public.blog_poll_votes enable row level security;

drop policy if exists "deny public reads for blog poll votes" on public.blog_poll_votes;
create policy "deny public reads for blog poll votes"
on public.blog_poll_votes
for select
to anon, authenticated
using (false);

drop policy if exists "deny public writes for blog poll votes" on public.blog_poll_votes;
create policy "deny public writes for blog poll votes"
on public.blog_poll_votes
for all
to anon, authenticated
using (false)
with check (false);
