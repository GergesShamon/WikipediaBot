SELECT
    REPLACE(page.page_title, '_', ' ') AS page_title
FROM
    page
JOIN
categorylinks on page.page_id = categorylinks.cl_from
WHERE cl_to like "{{Name}}";