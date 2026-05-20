create table if not exists public.bookshelf_subscribers (
  id bigint generated always as identity primary key,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  email text not null,
  email_normalized text not null unique,
  session_id text,
  source text not null default 'bookshelf_popup',
  subscribed_at timestamptz not null default now(),
  last_synced_at timestamptz not null default now(),
  metadata jsonb not null default '{}'::jsonb
);

create index if not exists bookshelf_subscribers_email_normalized_idx
  on public.bookshelf_subscribers (email_normalized);

alter table public.bookshelf_subscribers
  add column if not exists wordpress_user_id text,
  add column if not exists shopify_customer_id text,
  add column if not exists customer_email text,
  add column if not exists account_status text not null default 'email_only'
    check (account_status in ('email_only', 'logged_in')),
  add column if not exists access_tier text not null default 'free'
    check (access_tier in ('free', 'society')),
  add column if not exists society_key_used_at timestamptz,
  add column if not exists society_key_source text,
  add column if not exists welcome_email_sent_at timestamptz,
  add column if not exists weekly_email_opt_in boolean not null default true,
  add column if not exists weekly_send_day text not null default 'friday',
  add column if not exists last_weekly_sent_at timestamptz,
  add column if not exists last_recommended_book_key text,
  add column if not exists last_shelf_snapshot jsonb not null default '[]'::jsonb;

create index if not exists bookshelf_subscribers_shopify_customer_idx
  on public.bookshelf_subscribers (shopify_customer_id);

create index if not exists bookshelf_subscribers_wordpress_user_idx
  on public.bookshelf_subscribers (wordpress_user_id);

alter table public.bookshelf_subscribers enable row level security;

drop policy if exists "allow anon upsert bookshelf subscribers" on public.bookshelf_subscribers;
create policy "allow anon upsert bookshelf subscribers"
on public.bookshelf_subscribers
for all
to anon, authenticated
using (true)
with check (true);

drop policy if exists "deny public reads for bookshelf subscribers" on public.bookshelf_subscribers;
create policy "deny public reads for bookshelf subscribers"
on public.bookshelf_subscribers
for select
to anon, authenticated
using (false);

create table if not exists public.bookshelf_saved_books (
  id bigint generated always as identity primary key,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  email_normalized text not null,
  book_key text not null,
  book_handle text,
  book_title text not null,
  author text,
  cover text,
  amazon text,
  bookshop text,
  source text not null default 'bookshelf_popup',
  metadata jsonb not null default '{}'::jsonb,
  unique (email_normalized, book_key)
);

alter table public.bookshelf_saved_books
  add column if not exists wordpress_user_id text,
  add column if not exists shopify_customer_id text,
  add column if not exists customer_email text,
  add column if not exists saved_at timestamptz not null default now(),
  add column if not exists removed_at timestamptz,
  add column if not exists is_active boolean not null default true,
  add column if not exists shelf_name text,
  add column if not exists spice_level integer,
  add column if not exists darkness_level integer,
  add column if not exists tropes jsonb not null default '[]'::jsonb;

create index if not exists bookshelf_saved_books_email_idx
  on public.bookshelf_saved_books (email_normalized);

create index if not exists bookshelf_saved_books_handle_idx
  on public.bookshelf_saved_books (book_handle);

create index if not exists bookshelf_saved_books_email_active_idx
  on public.bookshelf_saved_books (email_normalized, is_active, saved_at desc);

create index if not exists bookshelf_saved_books_customer_active_idx
  on public.bookshelf_saved_books (shopify_customer_id, is_active, saved_at desc);

create index if not exists bookshelf_saved_books_wordpress_active_idx
  on public.bookshelf_saved_books (wordpress_user_id, is_active, saved_at desc);

alter table public.bookshelf_saved_books enable row level security;

drop policy if exists "allow anon upsert bookshelf saved books" on public.bookshelf_saved_books;
create policy "allow anon upsert bookshelf saved books"
on public.bookshelf_saved_books
for all
to anon, authenticated
using (true)
with check (true);

drop policy if exists "deny public reads for bookshelf saved books" on public.bookshelf_saved_books;
drop policy if exists "allow anon select account bookshelf saved books" on public.bookshelf_saved_books;
create policy "allow anon select account bookshelf saved books"
on public.bookshelf_saved_books
for select
to anon, authenticated
using (is_active = true);

create table if not exists public.bookshelf_book_statuses (
  id bigint generated always as identity primary key,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  email_normalized text not null,
  book_key text not null,
  book_handle text,
  book_title text not null,
  status text not null check (status in ('tbr', 'reading', 'read', 'dnf')),
  source text not null default 'site',
  status_set_at timestamptz not null default now(),
  metadata jsonb not null default '{}'::jsonb,
  unique (email_normalized, book_key)
);

create index if not exists bookshelf_book_statuses_email_idx
  on public.bookshelf_book_statuses (email_normalized);

create index if not exists bookshelf_book_statuses_email_status_idx
  on public.bookshelf_book_statuses (email_normalized, status, status_set_at desc);

alter table public.bookshelf_book_statuses enable row level security;

alter table public.bookshelf_book_statuses
  add column if not exists wordpress_user_id text,
  add column if not exists shopify_customer_id text,
  add column if not exists customer_email text;

drop policy if exists "allow anon upsert bookshelf statuses" on public.bookshelf_book_statuses;
create policy "allow anon upsert bookshelf statuses"
on public.bookshelf_book_statuses
for all
to anon, authenticated
using (true)
with check (true);

drop policy if exists "deny public reads for bookshelf statuses" on public.bookshelf_book_statuses;
create policy "deny public reads for bookshelf statuses"
on public.bookshelf_book_statuses
for select
to anon, authenticated
using (false);

create table if not exists public.bookshelf_weekly_recs (
  id bigint generated always as identity primary key,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  email_normalized text not null,
  week_of date not null,
  recommended_book_key text,
  recommended_book_handle text,
  recommended_book_title text,
  shelf_snapshot jsonb not null default '[]'::jsonb,
  status_snapshot jsonb not null default '{}'::jsonb,
  metadata jsonb not null default '{}'::jsonb,
  sent_at timestamptz,
  unique (email_normalized, week_of)
);

create index if not exists bookshelf_weekly_recs_email_idx
  on public.bookshelf_weekly_recs (email_normalized, week_of desc);

alter table public.bookshelf_weekly_recs enable row level security;

drop policy if exists "allow anon upsert bookshelf weekly recs" on public.bookshelf_weekly_recs;
create policy "allow anon upsert bookshelf weekly recs"
on public.bookshelf_weekly_recs
for all
to anon, authenticated
using (true)
with check (true);

drop policy if exists "deny public reads for bookshelf weekly recs" on public.bookshelf_weekly_recs;
create policy "deny public reads for bookshelf weekly recs"
on public.bookshelf_weekly_recs
for select
to anon, authenticated
using (false);

create or replace function public.bump_bookshelf_updated_at()
returns trigger
language plpgsql
as $$
begin
  new.updated_at = now();
  return new;
end;
$$;

drop trigger if exists bookshelf_subscribers_set_updated_at on public.bookshelf_subscribers;
create trigger bookshelf_subscribers_set_updated_at
before update on public.bookshelf_subscribers
for each row execute function public.bump_bookshelf_updated_at();

drop trigger if exists bookshelf_saved_books_set_updated_at on public.bookshelf_saved_books;
create trigger bookshelf_saved_books_set_updated_at
before update on public.bookshelf_saved_books
for each row execute function public.bump_bookshelf_updated_at();

drop trigger if exists bookshelf_book_statuses_set_updated_at on public.bookshelf_book_statuses;
create trigger bookshelf_book_statuses_set_updated_at
before update on public.bookshelf_book_statuses
for each row execute function public.bump_bookshelf_updated_at();

drop trigger if exists bookshelf_weekly_recs_set_updated_at on public.bookshelf_weekly_recs;
create trigger bookshelf_weekly_recs_set_updated_at
before update on public.bookshelf_weekly_recs
for each row execute function public.bump_bookshelf_updated_at();
