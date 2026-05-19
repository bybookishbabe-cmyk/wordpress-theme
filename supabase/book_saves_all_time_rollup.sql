-- Public all-time aggregate for the storefront trending shelf.
-- Raw public.book_saves rows stay private; this exposes counts only.

begin;

create or replace view public.book_saves_all_time_rollup as
with normalized as (
  select
    coalesce(
      nullif(lower(trim(book_handle)), ''),
      nullif(lower(trim(book_title)), '')
    ) as book_key,
    nullif(trim(book_handle), '') as book_handle,
    nullif(trim(book_title), '') as book_title,
    created_at
  from public.book_saves
)
select
  book_key,
  coalesce(
    max(book_title) filter (where book_title is not null),
    max(book_handle) filter (where book_handle is not null),
    book_key
  ) as book_title,
  count(*) as total_saves,
  max(created_at) as last_saved_at
from normalized
where book_key is not null
group by book_key;

comment on view public.book_saves_all_time_rollup is
'Public aggregate view for all-time book save counts. Raw public.book_saves rows stay private.';

grant select on public.book_saves_all_time_rollup to anon, authenticated;

commit;
