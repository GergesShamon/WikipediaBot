SELECT p.page_title, r.rev_timestamp
FROM page AS p
INNER JOIN revision_userindex AS r ON p.page_latest = r.rev_page
INNER JOIN templatelinks ON p.page_id = templatelinks.tl_from
INNER JOIN linktarget ON templatelinks.tl_target_id = linktarget.lt_id
WHERE p.page_is_redirect = 0
AND p.page_namespace = 0
AND r.rev_parent_id = 0
AND linktarget.lt_title NOT LIKE "لا_للأخطاء_الإملائية"
GROUP BY p.page_title
HAVING r.rev_timestamp < DATE_SUB(NOW(), INTERVAL 3 HOUR)
ORDER BY r.rev_timestamp DESC
LIMIT {{LIMIT}}
OFFSET {{OFFSET}};