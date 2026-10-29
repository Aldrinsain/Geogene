<?php
// GeoGene - Google OAuth Configuration
// Kini nga file gigamit sa DUHA ka Google login: ang USER login
// (auth/google-login.php - full redirect flow) ug ang unified STAFF/ADMIN
// login (login.php, which calls staff/google-login.php - Google Identity
// Services token flow).

// ---------- Client ID/Secret ----------
// Gikan sa Google Cloud Console > APIs & Services > Credentials
// (Client: "User login")
// Ang GOOGLE_CLIENT_SECRET naa na sa config/secrets.php (dili i-commit sa Git).
require_once __DIR__ . '/env.php';
if (!defined('GOOGLE_CLIENT_ID'))     define('GOOGLE_CLIENT_ID', '');       // Google Cloud Console > Credentials (set in secrets.php / .env)
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
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
      || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
$protocol = $https ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Folder where GeoGene lives, relative to the web root. Auto-detected, so it is
// '/geogene' on local XAMPP and '' (empty) when the site sits at the root of a
// Hostinger domain/subdomain.
$__docRoot = rtrim(str_replace('\\', '/', (string)realpath($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
$__appRoot = str_replace('\\', '/', (string)realpath(__DIR__ . '/..'));
$__basePath = ($__docRoot !== '' && strpos($__appRoot, $__docRoot) === 0)
    ? substr($__appRoot, strlen($__docRoot))
    : '/geogene';
if (!defined('GOOGLE_REDIRECT_URI')) {
    define('GOOGLE_REDIRECT_URI', $protocol . '://' . $host . $__basePath . '/auth/google-callback.php');
}
