SELECT
page.page_id,
MAX(revision_userindex.rev_id) AS rev_undo,
MIN(revision_userindex.rev_parent_id) AS rev_undoafter
FROM revision_userindex
INNER JOIN actor ON actor.actor_id = revision_userindex.rev_actor
INNER JOIN page ON page.page_id = revision_userindex.rev_page
LEFT JOIN change_tag ON change_tag.ct_rev_id = revision_userindex.rev_id
LEFT JOIN change_tag_def ON change_tag_def.ctd_id = change_tag.ct_tag_id
WHERE actor.actor_name = '{{Name}}'
AND (change_tag_def.ctd_name IS NULL OR change_tag_def.ctd_name != 'mw-reverted')
AND revision_userindex.rev_parent_id != 0
AND revision_userindex.rev_id <= {{To}}
AND revision_userindex.rev_id >= {{From}}
AND page.page_namespace != 3
GROUP BY page.page_id;