<?php
// GeoGene - Civil Registry Staff logout, pairs with /login.php. Only
// clears the Staff (default cookie) session - it does not touch the
// Admin session (different cookie, see admin/logout.php).
session_start();
unset($_SESSION['staff_id'], $_SESSION['staff_name'], $_SESSION['staff_role']);
header('Location: login.php');
exit;
