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
if (is_file(__DIR__ . '/secrets.php')) require_once __DIR__ . '/secrets.php';
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
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('GOOGLE_REDIRECT_URI', $protocol . '://' . $host . '/geogene/auth/google-callback.php');
