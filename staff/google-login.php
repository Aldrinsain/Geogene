<?php
// GeoGene - Staff "Sign in with Google" handler
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/google-oauth.php';
require_once __DIR__ . '/../config/account-status.php';

header('Content-Type: application/json');

function respond(bool $ok, string $message = '', array $extra = [])
{
    echo json_encode(array_merge(['success' => $ok, 'message' => $message], $extra));
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$accessToken = $input['access_token'] ?? '';

if ($accessToken === '') {
    respond(false, 'No Google credential was received.');
}

// ---------- Ask Google who this access token belongs to ----------
$ch = curl_init('https://www.googleapis.com/oauth2/v3/userinfo?access_token=' . urlencode($accessToken));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false || $httpCode !== 200) {
    respond(false, 'Unable to verify your Google account. Please try again.');
}

$profile = json_decode($response, true);
$email = $profile['email'] ?? '';
$emailVerified = $profile['email_verified'] ?? false;
$name = $profile['name'] ?? '';

if ($email === '' || !$emailVerified) {
    respond(false, 'Your Google account email could not be verified.');
}

if (GOOGLE_ALLOWED_DOMAIN !== '' && !str_ends_with(strtolower($email), '@' . strtolower(GOOGLE_ALLOWED_DOMAIN))) {
    respond(false, 'Please sign in using your ' . GOOGLE_ALLOWED_DOMAIN . ' institutional email address.');
}

// ---------- Match against an EXISTING Civil Registry Staff account only ----------
// Signing in with Google never creates a new staff account by itself -
// it only logs in someone who is already an authorized office_head.
// Admin accounts sign in on their own separate page (admin/login.php),
// which has its own session cookie - they are intentionally excluded here
// so an Admin can never end up authenticated inside the Staff session.
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'office_head'");
$stmt->execute([$email]);
$staff = $stmt->fetch();

if (!$staff) {
    respond(false, 'No staff account found for ' . htmlspecialchars($email) . '. Ask an administrator to create your account first.');
}

if (geogene_account_is_disabled($staff['status'])) {
    respond(false, GEOGENE_DISABLED_MESSAGE);
}
if ($staff['status'] !== 'active') {
    respond(false, 'Your staff account is not active yet. Please contact an administrator.');
}

session_regenerate_id(true);
$_SESSION['staff_id'] = $staff['user_id'];
$_SESSION['staff_name'] = $staff['full_name'];
$_SESSION['staff_role'] = $staff['role'];

$pdo->prepare("INSERT INTO login_logs (user_id, ip_address, user_agent) VALUES (?, ?, ?)")
    ->execute([$staff['user_id'], $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '']);

respond(true, 'Signed in successfully.', ['redirect' => $staff['role'] === 'admin' ? '../admin/dashboard.php' : 'dashboard.php']);
