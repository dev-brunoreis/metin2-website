-- Support/Content were shipped as empty shells (no catalog ACL). Drop leftovers.

DELETE FROM acl_role_resources WHERE role IN ('support', 'content');

DELETE FROM acl_role_sections WHERE role IN ('support', 'content');

DELETE r FROM admin_roles r
LEFT JOIN admins a ON a.role = r.slug
WHERE r.slug IN ('support', 'content') AND a.id IS NULL;
