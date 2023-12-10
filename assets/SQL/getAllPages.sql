SELECT p.page_title
FROM page AS p
INNER JOIN revision AS r ON p.page_id = r.rev_page
WHERE p.page_is_redirect = 0
AND p.page_namespace = 0
ORDER BY r.rev_timestamp DESC
LIMIT {{LIMIT}}
OFFSET {{OFFSET}};