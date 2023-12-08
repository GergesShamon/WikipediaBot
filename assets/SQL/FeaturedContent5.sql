SELECT
    COUNT(DISTINCT red_pagelinks.pl_title) AS red_links
FROM pagelinks AS red_pagelinks
INNER JOIN page ON (red_pagelinks.pl_from = page.page_id)
WHERE page_title = "{{Name}}"
AND page_namespace = 0
AND red_pagelinks.pl_namespace = 0
AND red_pagelinks.pl_title NOT IN (
    SELECT red_page.page_title
    FROM page AS red_page
WHERE red_page.page_namespace = 0
)