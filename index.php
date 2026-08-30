<?php
// GeoGene - Entry point
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard/dashboard.php');
} else {
    // Welcome intro splash is the very first step for new/guest users
    header('Location: auth/intro.php');
}
exit;
