SELECT
    REPLACE(p.page_title, "_", " ") AS page_title
FROM
    page AS p
INNER JOIN categorylinks AS category ON p.page_id = category.cl_from
WHERE
    p.page_is_redirect = 0
    AND p.page_id in (SELECT fr_page_id FROM flaggedrevs WHERE fr_rev_id = page_latest)
    AND p.page_namespace = 0
    AND category.cl_to = "صفحات_تحوي_وصلات_ملفات_معطوبة"
GROUP BY p.page_title
LIMIT {{LIMIT}}
OFFSET {{OFFSET}};