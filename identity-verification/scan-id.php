<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// ---------- Pre-registration mode: gamiton ang matched_record_id gikan sa
// Search Full Name step (dili na kinahanglan naka-login para maka-scan) ----------
if (isset($_GET['record_id'])) {
    $_SESSION['matched_record_id'] = (int) $_GET['record_id'];
}
if (isset($_GET['name'])) {
    $_SESSION['search_full_name'] = $_GET['name'];
}

// Kung naka-login na gyud (returning user nga gi-redirect diri para sa re-scan,
// bisan tuod dili na kini kasagaran mahitabo kay usa ra ka higayon ang scan),
// tugotan pud siya. Apan kung wala gyud naka-login UG walay matched_record_id,
// balik siya sa Search Full Name.
if (!isset($_SESSION['user_id']) && empty($_SESSION['matched_record_id'])) {
    header('Location: ../identity-verification/search-name.php');
    exit;
}
// GeoGene - ID Verification Flow (Scan -> Verify -> Result)
// Ibutang ni nga file ug ang id_verify.css / id_verify.js sa parehas nga folder sa XAMPP htdocs
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GeoGene - ID Verification</title>
<link rel="stylesheet" href="../assets/css/id-verify.css">