SELECT
    page.page_title AS page_title,
    COUNT(DISTINCT pagelinks.pl_title) AS disambiguation_links
FROM
    page
INNER JOIN pagelinks ON (pagelinks.pl_from = page.page_id)
WHERE page_title = "{{Name}}"
AND page.page_namespace = 0
AND pagelinks.pl_namespace = 0
AND pagelinks.pl_title like "%(توضيح)%"