<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/google-oauth.php';
require_once __DIR__ . '/../config/account-status.php';

function google_login_fail($msg) {
    header('Location: login.php?error=' . urlencode($msg));
    exit;
}

// ---------- 1. Basic validation ----------
if (isset($_GET['error'])) {
    google_login_fail('Google login was cancelled.');
}
if (!isset($_GET['code']) || !isset($_GET['state'])) {
    google_login_fail('Invalid Google login response.');
}
if (!isset($_SESSION['google_oauth_state']) || $_GET['state'] !== $_SESSION['google_oauth_state']) {
    google_login_fail('Invalid session state. Please try again.');
}
unset($_SESSION['google_oauth_state']);

// ---------- 2. I-exchange ang authorization code para sa access token ----------
$tokenParams = [
    'code'          => $_GET['code'],
    'client_id'     => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'grant_type'    => 'authorization_code',
];

$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query($tokenParams),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => true,
]);
$tokenResponse = curl_exec($ch);
$tokenCurlErr  = curl_error($ch);
curl_close($ch);

if ($tokenResponse === false) {
    google_login_fail('Unable to connect to Google (' . $tokenCurlErr . ').');
}

$tokenData = json_decode($tokenResponse, true);
if (empty($tokenData['access_token'])) {
    google_login_fail('Could not get the Google access token.');
}

// ---------- 3. Kuhaon ang profile info gamit ang access token ----------
$ch = curl_init('https://www.googleapis.com/oauth2/v3/userinfo');
curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $tokenData['access_token']],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => true,
]);
$userInfoResponse = curl_exec($ch);
curl_close($ch);

$googleUser = json_decode($userInfoResponse, true);
if (empty($googleUser['email'])) {
    google_login_fail('Could not get your Google email.');
}

$googleId    = $googleUser['sub'];
$email       = $googleUser['email'];
$fullName    = $googleUser['name'] ?? $email;
$profilePic  = $googleUser['picture'] ?? null;

// ---------- 4. Pangitaon kung naa na ba nga account (gamit google_id o email) ----------
$stmt = $pdo->prepare("SELECT * FROM users WHERE google_id = ? OR email = ? LIMIT 1");
$stmt->execute([$googleId, $email]);
$user = $stmt->fetch();

if ($user) {
    if (geogene_account_is_disabled($user['status'])) {
        google_login_fail(GEOGENE_DISABLED_MESSAGE);   // this account is disabled
    }
    // Naa nay account (na-rehistro na kaniadto via email, o via Google na kaniadto) - i-link/update ang google_id
    if (empty($user['google_id'])) {
        $pdo->prepare("UPDATE users SET google_id = ? WHERE user_id = ?")
            ->execute([$googleId, $user['user_id']]);
    }
    $userId = $user['user_id'];
} else {
    // Bag-ong account - i-create nga naka-verify na dayon ang email (kay Google mismo ang nag-verify)
    $stmt = $pdo->prepare(
        "INSERT INTO users (full_name, email, password_hash, role, status, email_verified, google_id)
         VALUES (?, ?, NULL, 'user', 'pending', 1, ?)"
    );
    $stmt->execute([$fullName, $email, $googleId]);
    $userId = $pdo->lastInsertId();
    $user = ['identity_verified' => 0];
}

// ---------- 5. I-login ----------
$_SESSION['user_id']             = $userId;
$_SESSION['full_name']           = $fullName;
$_SESSION['identity_verified']   = $user['identity_verified'] ?? 0;

$pdo->prepare("INSERT INTO login_logs (user_id, ip_address, user_agent) VALUES (?, ?, ?)")
    ->execute([$userId, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '']);

if (empty($_SESSION['identity_verified'])) {
    header('Location: ../identity-verification/scan-id.php');
} else {
    header('Location: ../dashboard/dashboard.php');
}
exit;
