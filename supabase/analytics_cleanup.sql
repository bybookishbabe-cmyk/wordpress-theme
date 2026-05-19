-- Simplified analytics layer for Bybookishbabe
--
-- Goal:
-- - Keep one raw event source: public.site_events
-- - Keep feature data separate: public.boyfriend_votes
-- - Expose only three analytics rollups:
--   public.analytics_daily
--   public.analytics_weekly
--   public.analytics_monthly
--
-- This migration is intentionally safe:
-- - It does not drop old views or tables yet.
-- - It lets you compare the new rollups against the old reports first.

drop view if exists public.analytics_daily;
drop view if exists public.analytics_weekly;
drop view if exists public.analytics_monthly;
drop view if exists public.analytics_top_books;
drop view if exists public.analytics_top_pages;

create view public.analytics_daily as
with base as (
  select
    'America/Los_Angeles'::text as report_timezone,
    (created_at at time zone 'America/Los_Angeles')::date as period_start,
    ((created_at at time zone 'America/Los_Angeles')::date + 1) as period_end,
    session_id,
    event_type,
    page_path,
    coalesce(book_handle, book_title) as book_key,
    metadata
  from public.site_events
)
select
  report_timezone,
  'day'::text as period_granularity,
  period_start,
  period_end,
  count(*) as total_events,
  count(distinct session_id) as unique_sessions,
  count(*) filter (where event_type = 'daily_visit') as daily_visits,
  count(*) filter (where event_type = 'book_saved') as book_saves,
  count(*) filter (where event_type = 'book_unsaved') as book_unsaves,
  count(*) filter (where event_type = 'book_shared') as book_shares,
  count(*) filter (where event_type = 'library_shared') as library_shares,
  count(*) filter (where event_type = 'book_modal_opened') as book_modal_opens,
  count(*) filter (where event_type = 'book_link_clicked') as outbound_clicks,
  count(*) filter (
    where event_type = 'book_link_clicked'
      and coalesce(metadata->>'destination', '') = 'amazon'
  ) as amazon_clicks,
  count(*) filter (
    where event_type = 'book_link_clicked'
      and coalesce(metadata->>'destination', '') = 'newsletter'
  ) as newsletter_clicks,
  count(distinct page_path) filter (
    where page_path is not null
      and page_path <> ''
  ) as unique_pages,
  count(distinct book_key) filter (
    where book_key is not null
      and book_key <> ''
  ) as unique_books
from base
group by report_timezone, period_start, period_end
order by period_start desc;

create view public.analytics_weekly as
with base as (
  select
    'America/Los_Angeles'::text as report_timezone,
    date_trunc('week', created_at at time zone 'America/Los_Angeles')::date as period_start,
    (date_trunc('week', created_at at time zone 'America/Los_Angeles')::date + 7) as period_end,
    session_id,
    event_type,
    page_path,
    coalesce(book_handle, book_title) as book_key,
    metadata
  from public.site_events
)
select
  report_timezone,
  'week'::text as period_granularity,
  period_start,
  period_end,
  count(*) as total_events,
  count(distinct session_id) as unique_sessions,
  count(*) filter (where event_type = 'daily_visit') as daily_visits,
  count(*) filter (where event_type = 'book_saved') as book_saves,
  count(*) filter (where event_type = 'book_unsaved') as book_unsaves,
  count(*) filter (where event_type = 'book_shared') as book_shares,
  count(*) filter (where event_type = 'library_shared') as library_shares,
  count(*) filter (where event_type = 'book_modal_opened') as book_modal_opens,
  count(*) filter (where event_type = 'book_link_clicked') as outbound_clicks,
  count(*) filter (
    where event_type = 'book_link_clicked'
      and coalesce(metadata->>'destination', '') = 'amazon'
  ) as amazon_clicks,
  count(*) filter (
    where event_type = 'book_link_clicked'
      and coalesce(metadata->>'destination', '') = 'newsletter'
  ) as newsletter_clicks,
  count(distinct page_path) filter (
    where page_path is not null
      and page_path <> ''
  ) as unique_pages,
  count(distinct book_key) filter (
    where book_key is not null
      and book_key <> ''
  ) as unique_books
from base
group by report_timezone, period_start, period_end
order by period_start desc;

create view public.analytics_monthly as
with base as (
  select
    'America/Los_Angeles'::text as report_timezone,
    date_trunc('month', created_at at time zone 'America/Los_Angeles')::date as period_start,
    (
      date_trunc('month', created_at at time zone 'America/Los_Angeles')::date
      + interval '1 month'
    )::date as period_end,
    session_id,
    event_type,
    page_path,
    coalesce(book_handle, book_title) as book_key,
    metadata
  from public.site_events
)
select
  report_timezone,
  'month'::text as period_granularity,
  period_start,
  period_end,
  count(*) as total_events,
  count(distinct session_id) as unique_sessions,
  count(*) filter (where event_type = 'daily_visit') as daily_visits,
  count(*) filter (where event_type = 'book_saved') as book_saves,
  count(*) filter (where event_type = 'book_unsaved') as book_unsaves,
  count(*) filter (where event_type = 'book_shared') as book_shares,
  count(*) filter (where event_type = 'library_shared') as library_shares,
  count(*) filter (where event_type = 'book_modal_opened') as book_modal_opens,
  count(*) filter (where event_type = 'book_link_clicked') as outbound_clicks,
  count(*) filter (
    where event_type = 'book_link_clicked'
      and coalesce(metadata->>'destination', '') = 'amazon'
  ) as amazon_clicks,
  count(*) filter (
    where event_type = 'book_link_clicked'
      and coalesce(metadata->>'destination', '') = 'newsletter'
  ) as newsletter_clicks,
  count(distinct page_path) filter (
    where page_path is not null
      and page_path <> ''
  ) as unique_pages,
  count(distinct book_key) filter (
    where book_key is not null
      and book_key <> ''
  ) as unique_books
from base
group by report_timezone, period_start, period_end
order by period_start desc;

comment on view public.analytics_daily is
'Daily analytics rollup derived from public.site_events in America/Los_Angeles time.';

comment on view public.analytics_weekly is
'Weekly analytics rollup derived from public.site_events in America/Los_Angeles time.';

comment on view public.analytics_monthly is
'Monthly analytics rollup derived from public.site_events in America/Los_Angeles time.';

create view public.analytics_top_books as
with book_events as (
  select
    'day'::text as period_granularity,
    (created_at at time zone 'America/Los_Angeles')::date as period_start,
    ((created_at at time zone 'America/Los_Angeles')::date + 1) as period_end,
    coalesce(book_handle, book_title) as book_key,
    coalesce(book_title, book_handle) as book_label,
    event_type
  from public.site_events
  where coalesce(book_handle, book_title) is not null
    and coalesce(book_handle, book_title) <> ''

  union all

  select
    'week'::text as period_granularity,
    date_trunc('week', created_at at time zone 'America/Los_Angeles')::date as period_start,
    (date_trunc('week', created_at at time zone 'America/Los_Angeles')::date + 7) as period_end,
    coalesce(book_handle, book_title) as book_key,
    coalesce(book_title, book_handle) as book_label,
    event_type
  from public.site_events
  where coalesce(book_handle, book_title) is not null
    and coalesce(book_handle, book_title) <> ''

  union all

  select
    'month'::text as period_granularity,
    date_trunc('month', created_at at time zone 'America/Los_Angeles')::date as period_start,
    (
      date_trunc('month', created_at at time zone 'America/Los_Angeles')::date
      + interval '1 month'
    )::date as period_end,
    coalesce(book_handle, book_title) as book_key,
    coalesce(book_title, book_handle) as book_label,
    event_type
  from public.site_events
  where coalesce(book_handle, book_title) is not null
    and coalesce(book_handle, book_title) <> ''
),
rollups as (
  select
    'America/Los_Angeles'::text as report_timezone,
    period_granularity,
    period_start,
    period_end,
    book_key,
    book_label,
    count(*) as total_book_events,
    count(*) filter (where event_type = 'book_saved') as book_saves,
    count(*) filter (where event_type = 'book_link_clicked') as outbound_clicks,
    count(*) filter (where event_type = 'book_shared') as book_shares,
    count(*) filter (where event_type = 'book_modal_opened') as modal_opens
  from book_events
  group by period_granularity, period_start, period_end, book_key, book_label
)
select
  report_timezone,
  period_granularity,
  period_start,
  period_end,
  book_key,
  book_label,
  total_book_events,
  book_saves,
  outbound_clicks,
  book_shares,
  modal_opens,
  rank() over (
    partition by period_granularity, period_start
    order by book_saves desc, outbound_clicks desc, total_book_events desc, book_label asc
  ) as period_rank
from rollups
order by
  case period_granularity
    when 'day' then 1
    when 'week' then 2
    when 'month' then 3
    else 4
  end,
  period_start desc,
  period_rank asc;

create view public.analytics_top_pages as
with page_events as (
  select
    'day'::text as period_granularity,
    (created_at at time zone 'America/Los_Angeles')::date as period_start,
    ((created_at at time zone 'America/Los_Angeles')::date + 1) as period_end,
    page_path,
    event_type
  from public.site_events
  where page_path is not null
    and page_path <> ''

  union all

  select
    'week'::text as period_granularity,
    date_trunc('week', created_at at time zone 'America/Los_Angeles')::date as period_start,
    (date_trunc('week', created_at at time zone 'America/Los_Angeles')::date + 7) as period_end,
    page_path,
    event_type
  from public.site_events
  where page_path is not null
    and page_path <> ''

  union all

  select
    'month'::text as period_granularity,
    date_trunc('month', created_at at time zone 'America/Los_Angeles')::date as period_start,
    (
      date_trunc('month', created_at at time zone 'America/Los_Angeles')::date
      + interval '1 month'
    )::date as period_end,
    page_path,
    event_type
  from public.site_events
  where page_path is not null
    and page_path <> ''
),
rollups as (
  select
    'America/Los_Angeles'::text as report_timezone,
    period_granularity,
    period_start,
    period_end,
    page_path,
    count(*) as total_page_events,
    count(*) filter (where event_type = 'daily_visit') as daily_visits,
    count(*) filter (where event_type = 'book_saved') as book_saves,
    count(*) filter (where event_type = 'book_link_clicked') as outbound_clicks,
    count(*) filter (where event_type = 'library_shared') as library_shares
  from page_events
  group by period_granularity, period_start, period_end, page_path
)
select
  report_timezone,
  period_granularity,
  period_start,
  period_end,
  page_path,
  total_page_events,
  daily_visits,
  book_saves,
  outbound_clicks,
  library_shares,
  rank() over (
    partition by period_granularity, period_start
    order by outbound_clicks desc, book_saves desc, total_page_events desc, page_path asc
  ) as period_rank
from rollups
order by
  case period_granularity
    when 'day' then 1
    when 'week' then 2
    when 'month' then 3
    else 4
  end,
  period_start desc,
  period_rank asc;

comment on view public.analytics_top_books is
'Top-performing books by day, week, and month derived from public.site_events.';

comment on view public.analytics_top_pages is
'Top-performing pages by day, week, and month derived from public.site_events.';

-- Verification queries:
-- select * from public.analytics_daily order by period_start desc limit 14;
-- select * from public.analytics_weekly order by period_start desc limit 8;
-- select * from public.analytics_monthly order by period_start desc limit 6;
-- select * from public.analytics_top_books where period_rank <= 10 order by period_start desc, period_rank asc;
-- select * from public.analytics_top_pages where period_rank <= 10 order by period_start desc, period_rank asc;
