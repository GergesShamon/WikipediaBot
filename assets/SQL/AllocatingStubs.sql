SELECT
    REPLACE(p.page_title, "_", " ") AS title,
    REPLACE(REPLACE(REPLACE(GROUP_CONCAT(cl2.cl_to), "_", " "),"بوابة ",""),"/مقالات متعلقة","") AS portals
FROM
    page AS p
JOIN categorylinks AS cl1 ON p.page_id = cl1.cl_from
JOIN categorylinks AS cl2 ON p.page_id = cl2.cl_from
WHERE p.page_is_redirect = 0
AND p.page_namespace = 0
AND cl1.cl_to = "بذرة"
AND cl2.cl_to LIKE "%/مقالات_متعلقة"
GROUP BY p.page_title