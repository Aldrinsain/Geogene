<?php
// GeoGene - Google OAuth Configuration
// Kini nga file gigamit sa DUHA ka Google login: ang USER login
// (auth/google-login.php - full redirect flow) ug ang unified STAFF/ADMIN
// login (login.php, which calls staff/google-login.php - Google Identity
// Services token flow).

// ---------- Client ID/Secret ----------
// Gikan sa Google Cloud Console > APIs & Services > Credentials
// (Client: "User login")
// Ang GOOGLE_CLIENT_ID ug GOOGLE_CLIENT_SECRET naa na sa  .env  file (dili i-commit sa Git).
// (Gi-flag sa GitHub push protection ang duha, mao nga gi-tago ang parehas.)
require_once __DIR__ . '/env.php';
if (!defined('GOOGLE_CLIENT_ID'))     define('GOOGLE_CLIENT_ID', '');
if (!defined('GOOGLE_CLIENT_SECRET')) define('GOOGLE_CLIENT_SECRET', '');

// ---------- Optional domain restriction (para sa STAFF login lang) ----------
// Kung gusto limitahan ang staff Google Sign-In sa usa lang ka email domain
// (e.g. 'geogene.gov.ph'), ibutang dinhi. Blangko ('') = walay restriction,
// bisan unsa nga Google account nga naa na sa users table (office_head/admin)
// pwede mo-login.
define('GOOGLE_ALLOWED_DOMAIN', '');

// ---------- Redirect URI (para sa USER login flow lang) ----------
// Kinahanglan MOTUGMA gyud ni sa "Authorized redirect URI" nga gi-set sa
// Google Cloud Console para sa "User login" client.
// Auto-detect base sa current host aron mo-work bisan localhost, IP, o ngrok URL.
$protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')) ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
// Folder sa project relative sa web root: '' kung naa sa root sa domain (Hostinger public_html),
// '/geogene' kung naa sa subfolder (XAMPP htdocs/geogene). Auto-detect na - dili na hardcoded.
$ggDocRoot  = str_replace('\\', '/', (string) realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
$ggProjRoot = str_replace('\\', '/', (string) realpath(__DIR__ . '/..'));
$ggBase = ($ggDocRoot !== '' && strpos($ggProjRoot, $ggDocRoot) === 0)
        ? rtrim(substr($ggProjRoot, strlen($ggDocRoot)), '/')
        : '/geogene';
define('GOOGLE_REDIRECT_URI', $protocol . '://' . $host . $ggBase . '/auth/google-callback.php');
