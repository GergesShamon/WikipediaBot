select 
  REPLACE(page_title,"_"," ") as "page_title", (
  select user_name from flaggedrevs 
  inner join user on user.user_id = flaggedrevs.fr_user
  where fr_page_id = page_id
  order by fr_timestamp asc
  limit 1) as "user_reviewed"
from page
where page.page_is_redirect = 0
and page.page_namespace = 0
and page_id in (select fp_page_id from flaggedpages where fp_page_id = page_id)
and page_title in (
  select pl_title from pagelinks
  where pl_from = 3421494
  and pl_from_namespace = 4
  and pl_namespace = 0
);