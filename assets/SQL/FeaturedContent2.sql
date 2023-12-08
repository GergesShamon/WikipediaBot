SELECT COUNT(*) AS to_links
FROM pagelinks
WHERE pl_from_namespace = 0
AND pl_namespace = 0
AND pl_title = "{{Name}}";
