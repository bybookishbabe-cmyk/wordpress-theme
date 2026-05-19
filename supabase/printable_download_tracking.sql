-- Allows the storefront to insert printable download analytics events.
-- Run this if your site_events insert policy is restricted to known event types.

drop policy if exists "allow anon inserts for site events" on public.site_events;
create policy "allow anon inserts for site events"
on public.site_events
for insert
to anon, authenticated
with check (
  session_id is not null
  and length(btrim(session_id)) between 1 and 120
  and event_type in (
    'daily_visit',
    'book_saved',
    'book_unsaved',
    'book_shared',
    'library_shared',
    'book_modal_opened',
    'book_link_clicked',
    'printable_download_clicked'
  )
  and (page_path is null or length(page_path) <= 500)
  and (page_title is null or length(page_title) <= 300)
  and (book_handle is null or length(book_handle) <= 200)
  and (book_title is null or length(book_title) <= 200)
  and (series_handle is null or length(series_handle) <= 200)
  and (ui_location is null or length(ui_location) <= 100)
  and jsonb_typeof(metadata) = 'object'
  and created_at >= now() - interval '1 hour'
  and created_at <= now() + interval '5 minutes'
);

-- Recent printable download rows.
select
  created_at at time zone 'America/Los_Angeles' as clicked_at_pacific,
  metadata->>'printable' as printable_key,
  metadata->>'product' as product,
  metadata->>'label' as label,
  metadata->>'source' as source,
  metadata->>'url' as url,
  page_path,
  session_id
from public.site_events
where event_type = 'printable_download_clicked'
order by created_at desc
limit 100;

-- Daily printable download totals.
select
  (created_at at time zone 'America/Los_Angeles')::date as report_date,
  metadata->>'printable' as printable_key,
  metadata->>'product' as product,
  metadata->>'label' as label,
  count(*) as total_download_clicks,
  count(distinct session_id) as unique_people
from public.site_events
where event_type = 'printable_download_clicked'
group by 1, 2, 3, 4
order by report_date desc, total_download_clicks desc;

-- Trial Kindle insert daily totals.
select
  (created_at at time zone 'America/Los_Angeles')::date as report_date,
  count(*) as total_download_clicks,
  count(distinct session_id) as unique_people
from public.site_events
where event_type = 'printable_download_clicked'
  and (
    lower(coalesce(metadata->>'printable', '')) like '%trial%'
    or lower(coalesce(metadata->>'product', '')) like '%trial%'
    or lower(coalesce(metadata->>'label', '')) like '%trial%'
    or lower(coalesce(metadata->>'url', '')) like '%trial%'
  )
  and (
    lower(coalesce(metadata->>'printable', '')) like '%kindle%'
    or lower(coalesce(metadata->>'product', '')) like '%kindle%'
    or lower(coalesce(metadata->>'label', '')) like '%kindle%'
    or lower(coalesce(metadata->>'url', '')) like '%kindle%'
  )
group by 1
order by report_date desc;
