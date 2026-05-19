create table if not exists public.newsletter_leads (
  id bigint generated always as identity primary key,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  email text not null,
  email_normalized text not null,
  source text not null default 'site',
  page_path text,
  page_title text,
  source_book_handle text,
  source_book_title text,
  session_id text,
  substack_sync_status text not null default 'pending'
    check (substack_sync_status in ('pending', 'synced', 'failed', 'manual')),
  substack_synced_at timestamptz,
  substack_error text,
  metadata jsonb not null default '{}'::jsonb,
  unique (email_normalized, source)
);

create index if not exists newsletter_leads_created_at_idx
  on public.newsletter_leads (created_at desc);

create index if not exists newsletter_leads_email_normalized_idx
  on public.newsletter_leads (email_normalized);

create index if not exists newsletter_leads_source_idx
  on public.newsletter_leads (source);

create index if not exists newsletter_leads_substack_sync_idx
  on public.newsletter_leads (substack_sync_status, created_at desc);

alter table public.newsletter_leads enable row level security;

drop policy if exists "allow anon insert newsletter leads" on public.newsletter_leads;
create policy "allow anon insert newsletter leads"
on public.newsletter_leads
for insert
to anon, authenticated
with check (true);

drop policy if exists "allow anon update newsletter leads" on public.newsletter_leads;
create policy "allow anon update newsletter leads"
on public.newsletter_leads
for update
to anon, authenticated
using (true)
with check (true);

drop policy if exists "deny public reads for newsletter leads" on public.newsletter_leads;
create policy "deny public reads for newsletter leads"
on public.newsletter_leads
for select
to anon, authenticated
using (false);

create or replace function public.bump_newsletter_leads_updated_at()
returns trigger
language plpgsql
as $$
begin
  new.updated_at = now();
  return new;
end;
$$;

drop trigger if exists newsletter_leads_set_updated_at on public.newsletter_leads;
create trigger newsletter_leads_set_updated_at
before update on public.newsletter_leads
for each row execute function public.bump_newsletter_leads_updated_at();
