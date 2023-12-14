SELECT 
  REPLACE(pagelinks.pl_title,"_"," ") as "page_title"
FROM pagelinks
JOIN page ON page.page_id = pagelinks.pl_from
WHERE page.page_title = "{{Name}}"
AND pagelinks.pl_from_namespace = {{FromNamespace}}
AND pagelinks.pl_namespace = {{Namespace}}
AND pagelinks.pl_title NOT IN (
SELECT red_page.page_title    
FROM page AS red_page
WHERE red_page.page_namespace = {{Namespace}});