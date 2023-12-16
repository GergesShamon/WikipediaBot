SELECT
    REPLACE(GROUP_CONCAT(REPLACE(il.il_to, "_", " ")), ",", "#,#") AS linked_images,
    REPLACE(p.page_title, "_", " ") AS page_title
FROM
    page AS p
INNER JOIN categorylinks AS category ON p.page_id = category.cl_from
LEFT JOIN imagelinks AS il ON p.page_id = il.il_from
WHERE
    p.page_is_redirect = 0
    AND p.page_namespace = 0
    AND il.il_from_namespace = 0
    AND il.il_to NOT LIKE "//%"
    AND il.il_to NOT LIKE "%.ogg"
    AND il.il_to LIKE "%.%"
    AND category.cl_to = "صفحات_تحوي_وصلات_ملفات_معطوبة"
GROUP BY p.page_title
LIMIT {{LIMIT}}
OFFSET {{OFFSET}};