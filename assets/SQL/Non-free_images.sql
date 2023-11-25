SELECT
    img_name,
    img_size,
    img_height, 
    img_width
FROM
    image
JOIN page AS p ON p.page_title = img_name
JOIN categorylinks AS cl ON p.page_id = cl.cl_from
WHERE img_width > 400
AND img_height > 400
AND img_name NOT LIKE "%.svg"
AND p.page_is_redirect = 0
AND p.page_namespace = 6
AND cl_to = "جميع_الملفات_غير_الحرة";