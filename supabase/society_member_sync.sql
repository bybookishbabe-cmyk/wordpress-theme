create table if not exists public.society_member_sync_events (
  id bigint generated always as identity primary key,
  stripe_event_id text unique,
  stripe_event_type text,
  stripe_customer_id text,
  stripe_subscription_id text,
  stripe_status text,
  email text,
  desired_access boolean,
  shopify_customer_id text,
  shopify_action text,
  sync_status text not null default 'logged',
  error_message text,
  payload jsonb not null default '{}'::jsonb,
  processed_at timestamptz not null default now(),
  created_at timestamptz not null default now()
);

create index if not exists society_member_sync_events_email_idx
  on public.society_member_sync_events (email, processed_at desc);

create index if not exists society_member_sync_events_customer_idx
  on public.society_member_sync_events (stripe_customer_id, processed_at desc);

create index if not exists society_member_sync_events_status_idx
  on public.society_member_sync_events (sync_status, processed_at desc);
