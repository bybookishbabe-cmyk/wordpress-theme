create table if not exists public.newsletter_submissions (
  id uuid primary key default gen_random_uuid(),
  created_at timestamptz not null default now(),
  display_name text not null,
  submission_type text not null,
  submission_text text not null,
  book_title_author text,
  can_feature_with_name boolean not null default false,
  page_path text,
  session_id text,
  user_agent text,
  status text not null default 'new'
);

create index if not exists newsletter_submissions_created_at_idx
  on public.newsletter_submissions (created_at desc);

create index if not exists newsletter_submissions_status_idx
  on public.newsletter_submissions (status);

create index if not exists newsletter_submissions_type_idx
  on public.newsletter_submissions (submission_type);

alter table public.newsletter_submissions enable row level security;

drop policy if exists "allow anon inserts for newsletter submissions" on public.newsletter_submissions;
create policy "allow anon inserts for newsletter submissions"
on public.newsletter_submissions
for insert
to anon, authenticated
with check (true);

drop policy if exists "deny public reads for newsletter submissions" on public.newsletter_submissions;
create policy "deny public reads for newsletter submissions"
on public.newsletter_submissions
for select
to anon, authenticated
using (false);
