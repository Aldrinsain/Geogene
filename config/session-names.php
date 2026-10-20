<?php
// GeoGene - Admin now has its OWN login/session, completely separate from
// the Staff/Civil-Registry session (which still uses PHP's default
// session cookie, unchanged). This uses a different cookie name so an
// Admin and a Staff member can be logged in at the same time, in the same
// browser, without either one logging the other out or blocking their
// login.
//
// Referenced by: admin/login.php, admin/logout.php,
// admin/includes/admin-auth-guard.php, staff/includes/staff-auth-guard.php.
//
// Kept in its own tiny file (instead of inside config/db.php) so it can be
// checked before deciding whether a database connection is even needed.
if (!defined('GEOGENE_ADMIN_SESSION_NAME')) {
    define('GEOGENE_ADMIN_SESSION_NAME', 'geogene_admin_sess');
}
