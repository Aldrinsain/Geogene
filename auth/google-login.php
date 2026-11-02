<?php
// GeoGene - "Continue with Google" (user app). Sends the person to Google's sign-in page;
// Google then comes back to auth/google-callback.php.
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/google-helpers.php';

if (!gg_google_configured()) {
    gg_google_log('GOOGLE_CLIENT_ID / GOOGLE_CLIENT_SECRET are empty (check the .env file on the server).');
    header('Location: login.php?error=' . urlencode('Google Sign-In is not set up yet. Please log in with your email and password.'));
    exit;
}

// Random state protects against CSRF in the OAuth flow
$state = bin2hex(random_bytes(16));
$_SESSION['google_oauth_state'] = $state;

$params = [
    'client_id'     => GOOGLE_CLIENT_ID,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope'         => 'openid email profile',
    'state'         => $state,
    'prompt'        => 'select_account',
];

header('Location: ' . GOOGLE_AUTH_URL . '?' . http_build_query($params));
exit;
