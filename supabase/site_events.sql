create table if not exists public.site_events (
  id bigint generated always as identity primary key,
  created_at timestamptz not null default now(),
  session_id text not null,
  event_type text not null,
  page_path text,
  page_title text,
  book_handle text,
  book_title text,
  series_handle text,
  ui_location text,
  metadata jsonb not null default '{}'::jsonb
);

create index if not exists site_events_created_at_idx
  on public.site_events (created_at desc);

create index if not exists site_events_event_type_idx
  on public.site_events (event_type);

create index if not exists site_events_session_id_idx
  on public.site_events (session_id);

create index if not exists site_events_book_handle_idx
  on public.site_events (book_handle);

alter table public.site_events enable row level security;

drop policy if exists "allow anon inserts for site events" on public.site_events;
create policy "allow anon inserts for site events"
on public.site_events
for insert
to anon, authenticated
with check (true);

drop policy if exists "deny public reads for site events" on public.site_events;
create policy "deny public reads for site events"
on public.site_events
for select
to anon, authenticated
using (false);

create or replace view public.site_events_with_pacific_time as
select
  id,
  created_at,
  created_at at time zone 'America/Los_Angeles' as created_at_pacific,
  (created_at at time zone 'America/Los_Angeles')::date as created_date_pacific,
  date_trunc('week', created_at at time zone 'America/Los_Angeles')::date as created_week_start_pacific,
  session_id,
  event_type,
  page_path,
  page_title,
  book_handle,
  book_title,
  series_handle,
  ui_location,
  metadata
from public.site_events;

drop view if exists public.site_events_summary_last_30_days;

create view public.site_events_summary_last_30_days as
select
  'America/Los_Angeles'::text as report_timezone,
  event_type,
  coalesce(book_title, '(none)') as book_title,
  coalesce(book_handle, '(none)') as book_handle,
  count(*) as total_events,
  count(distinct session_id) as unique_sessions,
  max(created_at) as last_seen_at,
  max(created_at at time zone 'America/Los_Angeles') as last_seen_at_pacific
from public.site_events
where created_at >= ((now() at time zone 'America/Los_Angeles') - interval '30 days') at time zone 'America/Los_Angeles'
group by 1, 2, 3, 4
order by total_events desc, unique_sessions desc;

drop view if exists public.site_events_daily_summary;

create view public.site_events_daily_summary as
select
  'America/Los_Angeles'::text as report_timezone,
  (created_at at time zone 'America/Los_Angeles')::date as report_date,
  count(*) as total_events,
  count(distinct session_id) as tracked_unique_visitors,
  count(distinct event_type) as unique_event_types,
  count(distinct page_path) filter (where page_path is not null and page_path <> '') as unique_pages,
  count(distinct coalesce(book_handle, book_title)) filter (
    where coalesce(book_handle, book_title) is not null
      and coalesce(book_handle, book_title) <> ''
  ) as unique_books,
  min(created_at at time zone 'America/Los_Angeles') as first_event_at_pacific,
  max(created_at at time zone 'America/Los_Angeles') as last_event_at_pacific
from public.site_events
group by 1, 2
order by report_date desc;

drop view if exists public.site_events_daily_top_conversions;

create view public.site_events_daily_top_conversions as
with conversion_source as (
  select
    (created_at at time zone 'America/Los_Angeles')::date as report_date,
    session_id,
    event_type,
    book_title,
    book_handle,
    page_path,
    metadata,
    case
      when event_type = 'book_link_clicked'
        then 'book_link_clicked:' || coalesce(metadata->>'destination', 'unknown')
      else event_type
    end as conversion_type
  from public.site_events
  where event_type in ('book_saved', 'book_link_clicked', 'book_shared', 'library_shared')
),
conversion_rollups as (
  select
    'America/Los_Angeles'::text as report_timezone,
    report_date,
    conversion_type,
    count(*) as total_conversions,
    count(distinct session_id) as tracked_unique_visitors,
    count(distinct coalesce(book_handle, book_title)) filter (
      where coalesce(book_handle, book_title) is not null
        and coalesce(book_handle, book_title) <> ''
    ) as unique_books
  from conversion_source
  group by 1, 2, 3
),
top_books as (
  select
    report_date,
    conversion_type,
    coalesce(book_title, book_handle) as top_book_title,
    count(*) as top_book_hits,
    row_number() over (
      partition by report_date, conversion_type
      order by count(*) desc, coalesce(book_title, book_handle) asc
    ) as rn
  from conversion_source
  where coalesce(book_title, book_handle) is not null
    and coalesce(book_title, book_handle) <> ''
  group by report_date, conversion_type, coalesce(book_title, book_handle)
),
top_pages as (
  select
    report_date,
    conversion_type,
    page_path as top_page_path,
    count(*) as top_page_hits,
    row_number() over (
      partition by report_date, conversion_type
      order by count(*) desc, page_path asc
    ) as rn
  from conversion_source
  where page_path is not null
    and page_path <> ''
  group by report_date, conversion_type, page_path
)
select
  cr.report_timezone,
  cr.report_date,
  cr.conversion_type,
  cr.total_conversions,
  cr.tracked_unique_visitors,
  cr.unique_books,
  tb.top_book_title,
  tb.top_book_hits,
  tp.top_page_path,
  tp.top_page_hits,
  rank() over (
    partition by cr.report_date
    order by cr.total_conversions desc, cr.tracked_unique_visitors desc, cr.conversion_type asc
  ) as conversion_rank
from conversion_rollups cr
left join top_books tb
  on tb.report_date = cr.report_date
 and tb.conversion_type = cr.conversion_type
 and tb.rn = 1
left join top_pages tp
  on tp.report_date = cr.report_date
 and tp.conversion_type = cr.conversion_type
 and tp.rn = 1
order by cr.report_date desc, conversion_rank asc, cr.conversion_type asc;

create table if not exists public.site_events_weekly_overview (
  week_start date primary key,
  week_end date not null,
  report_timezone text not null default 'America/Los_Angeles',
  total_events bigint not null,
  unique_sessions bigint not null,
  unique_event_types bigint not null,
  unique_pages bigint not null,
  unique_books bigint not null,
  top_event_type text,
  top_event_hits bigint,
  recorded_at timestamptz not null default now()
);

create table if not exists public.site_events_weekly_event_summary (
  week_start date not null,
  week_end date not null,
  report_timezone text not null default 'America/Los_Angeles',
  event_type text not null,
  total_events bigint not null,
  unique_sessions bigint not null,
  unique_pages bigint not null,
  unique_books bigint not null,
  top_page_path text,
  top_page_hits bigint,
  top_book_title text,
  top_book_hits bigint,
  last_seen_at timestamptz,
  recorded_at timestamptz not null default now(),
  primary key (week_start, report_timezone, event_type)
);

create index if not exists site_events_weekly_event_summary_week_start_idx
  on public.site_events_weekly_event_summary (week_start desc);

create index if not exists site_events_weekly_event_summary_total_events_idx
  on public.site_events_weekly_event_summary (total_events desc);

alter table public.site_events_weekly_overview enable row level security;
alter table public.site_events_weekly_event_summary enable row level security;

drop policy if exists "deny public reads for weekly site events overview" on public.site_events_weekly_overview;
create policy "deny public reads for weekly site events overview"
on public.site_events_weekly_overview
for select
to anon, authenticated
using (false);

drop policy if exists "deny public reads for weekly site events detail" on public.site_events_weekly_event_summary;
create policy "deny public reads for weekly site events detail"
on public.site_events_weekly_event_summary
for select
to anon, authenticated
using (false);

create or replace function public.capture_site_events_weekly_summary(
  run_at timestamptz default now(),
  report_timezone text default 'America/Los_Angeles'
)
returns table (
  out_week_start date,
  out_week_end date,
  out_event_types_logged integer,
  out_total_events_logged bigint
)
language plpgsql
security definer
set search_path = public
as $$
declare
  current_week_start date;
  target_week_start date;
  target_week_end date;
  target_week_start_utc timestamptz;
  target_week_end_utc timestamptz;
begin
  current_week_start := date_trunc('week', run_at at time zone report_timezone)::date;
  target_week_start := current_week_start - 7;
  target_week_end := current_week_start;

  target_week_start_utc := target_week_start::timestamp at time zone report_timezone;
  target_week_end_utc := target_week_end::timestamp at time zone report_timezone;

  with weekly_source as (
    select *
    from public.site_events
    where created_at >= target_week_start_utc
      and created_at < target_week_end_utc
  ),
  top_events as (
    select event_type, count(*) as total_events
    from weekly_source
    group by event_type
    order by total_events desc, event_type asc
  ),
  weekly_overview as (
    select
      target_week_start as week_start,
      target_week_end as week_end,
      report_timezone as report_timezone,
      count(*) as total_events,
      count(distinct session_id) as unique_sessions,
      count(distinct event_type) as unique_event_types,
      count(distinct coalesce(page_path, '')) filter (where page_path is not null and page_path <> '') as unique_pages,
      count(distinct coalesce(book_handle, book_title, '')) filter (
        where coalesce(book_handle, book_title, '') <> ''
      ) as unique_books,
      (select event_type from top_events limit 1) as top_event_type,
      (select total_events from top_events limit 1) as top_event_hits
    from weekly_source
  ),
  upsert_overview as (
    insert into public.site_events_weekly_overview (
      week_start,
      week_end,
      report_timezone,
      total_events,
      unique_sessions,
      unique_event_types,
      unique_pages,
      unique_books,
      top_event_type,
      top_event_hits,
      recorded_at
    )
    select
      week_start,
      week_end,
      report_timezone,
      total_events,
      unique_sessions,
      unique_event_types,
      unique_pages,
      unique_books,
      top_event_type,
      top_event_hits,
      now()
    from weekly_overview
    on conflict (week_start) do update
    set
      week_end = excluded.week_end,
      report_timezone = excluded.report_timezone,
      total_events = excluded.total_events,
      unique_sessions = excluded.unique_sessions,
      unique_event_types = excluded.unique_event_types,
      unique_pages = excluded.unique_pages,
      unique_books = excluded.unique_books,
      top_event_type = excluded.top_event_type,
      top_event_hits = excluded.top_event_hits,
      recorded_at = now()
    returning 1
  ),
  event_rollups as (
    select
      target_week_start as week_start,
      target_week_end as week_end,
      report_timezone as report_timezone,
      event_type,
      count(*) as total_events,
      count(distinct session_id) as unique_sessions,
      count(distinct coalesce(page_path, '')) filter (where page_path is not null and page_path <> '') as unique_pages,
      count(distinct coalesce(book_handle, book_title, '')) filter (
        where coalesce(book_handle, book_title, '') <> ''
      ) as unique_books,
      max(created_at) as last_seen_at
    from weekly_source
    group by event_type
  ),
  top_pages as (
    select
      event_type,
      page_path,
      count(*) as page_hits,
      row_number() over (
        partition by event_type
        order by count(*) desc, page_path asc
      ) as rn
    from weekly_source
    where page_path is not null
      and page_path <> ''
    group by event_type, page_path
  ),
  top_books as (
    select
      event_type,
      coalesce(book_title, book_handle) as top_book_title,
      count(*) as book_hits,
      row_number() over (
        partition by event_type
        order by count(*) desc, coalesce(book_title, book_handle) asc
      ) as rn
    from weekly_source
    where coalesce(book_title, book_handle, '') <> ''
    group by event_type, coalesce(book_title, book_handle)
  ),
  upsert_events as (
    insert into public.site_events_weekly_event_summary (
      week_start,
      week_end,
      report_timezone,
      event_type,
      total_events,
      unique_sessions,
      unique_pages,
      unique_books,
      top_page_path,
      top_page_hits,
      top_book_title,
      top_book_hits,
      last_seen_at,
      recorded_at
    )
    select
      er.week_start,
      er.week_end,
      er.report_timezone,
      er.event_type,
      er.total_events,
      er.unique_sessions,
      er.unique_pages,
      er.unique_books,
      tp.page_path as top_page_path,
      tp.page_hits as top_page_hits,
      tb.top_book_title,
      tb.book_hits as top_book_hits,
      er.last_seen_at,
      now()
    from event_rollups er
    left join top_pages tp
      on tp.event_type = er.event_type
     and tp.rn = 1
    left join top_books tb
      on tb.event_type = er.event_type
     and tb.rn = 1
    on conflict (week_start, report_timezone, event_type) do update
    set
      week_end = excluded.week_end,
      total_events = excluded.total_events,
      unique_sessions = excluded.unique_sessions,
      unique_pages = excluded.unique_pages,
      unique_books = excluded.unique_books,
      top_page_path = excluded.top_page_path,
      top_page_hits = excluded.top_page_hits,
      top_book_title = excluded.top_book_title,
      top_book_hits = excluded.top_book_hits,
      last_seen_at = excluded.last_seen_at,
      recorded_at = now()
    returning 1
  )
  select
    target_week_start,
    target_week_end,
    coalesce((select count(*) from event_rollups), 0)::integer,
    coalesce((select total_events from weekly_overview), 0)
  into out_week_start, out_week_end, out_event_types_logged, out_total_events_logged;

  return next;
end;
$$;

create or replace view public.site_events_weekly_top_event_types as
select
  week_start,
  week_end,
  report_timezone,
  event_type,
  total_events,
  unique_sessions,
  unique_pages,
  unique_books,
  top_page_path,
  top_page_hits,
  top_book_title,
  top_book_hits,
  last_seen_at,
  rank() over (
    partition by week_start, report_timezone
    order by total_events desc, unique_sessions desc, event_type asc
  ) as event_rank
from public.site_events_weekly_event_summary
order by week_start desc, event_rank asc, event_type asc;

comment on function public.capture_site_events_weekly_summary(timestamptz, text) is
'Captures the previous completed Monday-through-Sunday week into weekly site event summary tables. Example manual run: select * from public.capture_site_events_weekly_summary();';

comment on view public.site_events_with_pacific_time is
'Raw site events with Pacific-local timestamp, date, and Monday-based week start helpers for analytics in Seattle time.';

comment on view public.site_events_weekly_top_event_types is
'Week-over-week ranking of top event types, intended for Monday analytics comparisons.';

-- Optional Monday automation in Supabase pg_cron:
-- select cron.schedule(
--   'capture-site-events-weekly-summary',
--   '5 8 * * 1',
--   $$select public.capture_site_events_weekly_summary(now(), 'America/Los_Angeles');$$
-- );

select * from public.capture_site_events_weekly_summary(now(), 'America/Los_Angeles');
