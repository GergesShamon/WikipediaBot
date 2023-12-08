SELECT COUNT(DISTINCT rd_from) AS redirects,
COUNT(DISTINCT revision_userindex.rev_id) AS edits
FROM redirect 
INNER JOIN page ON (redirect.rd_title = page.page_title)
INNER JOIN revision_userindex ON (revision_userindex.rev_page = page.page_id)
WHERE page_title = "{{Name}}"
AND page_namespace = 0;