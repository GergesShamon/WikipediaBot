SELECT p.page_title
FROM page AS p
INNER JOIN revision AS r ON p.page_id = r.rev_page
INNER JOIN templatelinks ON p.page_id = templatelinks.tl_from
INNER JOIN linktarget ON templatelinks.tl_target_id = linktarget.lt_id
WHERE p.page_is_redirect = 0
AND p.page_namespace = 0
AND r.rev_timestamp > DATE_SUB(NOW(), INTERVAL 1 HOUR)
AND linktarget.lt_title NOT LIKE "لا_للأخطاء_الإملائية"
GROUP BY p.page_title
ORDER BY r.rev_timestamp DESC
LIMIT {{LIMIT}}
OFFSET {{OFFSET}};