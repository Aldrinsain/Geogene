<?php
// GeoGene - Google redirects back here after "Continue with Google" (user app).
// Every failure goes back to the login page with a friendly message; the real reason is
// written to the server error log ([GeoGene google]) - this page never shows an HTTP 500.
session_start();

function google_login_fail($msg) {
    header('Location: login.php?error=' . urlencode($msg));
    exit;
}

try {
    require_once __DIR__ . '/../config/db.php';
    require_once __DIR__ . '/../config/google-helpers.php';
    require_once __DIR__ . '/../config/account-status.php';

    // ---------- 1. Basic validation ----------
    if (isset($_GET['error'])) {
        google_login_fail('Google login was cancelled.');
    }
    if (!isset($_GET['code']) || !isset($_GET['state'])) {
        google_login_fail('Invalid Google login response.');
    }
    if (!isset($_SESSION['google_oauth_state']) || !hash_equals($_SESSION['google_oauth_state'], (string) $_GET['state'])) {
        google_login_fail('Your sign-in session expired. Please try again.');
    }
    unset($_SESSION['google_oauth_state']);
    if (!gg_google_configured()) {
        gg_google_log('GOOGLE_CLIENT_ID / GOOGLE_CLIENT_SECRET are empty (check the .env file on the server).');
        google_login_fail('Google Sign-In is not set up yet. Please log in with your email and password.');
    }

    // ---------- 2. Exchange the authorization code for an access token ----------
    $r = gg_google_http(GOOGLE_TOKEN_URL, [
        'code'          => $_GET['code'],
        'client_id'     => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'grant_type'    => 'authorization_code',
    ]);
    if ($r['error'] !== '') {
        gg_google_log('token request could not reach Google: ' . $r['error']);
        google_login_fail('Unable to connect to Google right now. Please try again.');
    }
    $tokenData = json_decode($r['body'], true) ?: [];
    if (empty($tokenData['access_token'])) {
        $gErr = (string) ($tokenData['error'] ?? 'unknown');
        gg_google_log("token exchange failed: HTTP {$r['status']} error=$gErr "
            . (string) ($tokenData['error_description'] ?? '')
            . ' | redirect_uri sent = ' . GOOGLE_REDIRECT_URI
            . ' (this exact URL must be listed under "Authorized redirect URIs" in Google Cloud Console)');
        google_login_fail(gg_google_friendly_error($gErr));
    }

    // ---------- 3. Who is signing in? ----------
    $r = gg_google_http(GOOGLE_USERINFO_URL, null, ['Authorization: Bearer ' . $tokenData['access_token']]);
    $googleUser = json_decode($r['body'], true) ?: [];
    if ($r['error'] !== '' || empty($googleUser['email'])) {
        gg_google_log("userinfo failed: HTTP {$r['status']} {$r['error']} " . substr($r['body'], 0, 200));
        google_login_fail('Could not get your Google email.');
    }
    $emailVerified = $googleUser['email_verified'] ?? false;
    if (!($emailVerified === true || $emailVerified === 'true' || $emailVerified === 1 || $emailVerified === '1')) {
        google_login_fail('Your Google account email is not verified.');
    }

    $googleId   = (string) ($googleUser['sub'] ?? '');
    $email      = strtolower(trim((string) $googleUser['email']));
    $fullName   = trim((string) ($googleUser['name'] ?? '')) ?: $email;

    // ---------- 4. Existing account (by Google id or email) or a new one ----------
    $stmt = $pdo->prepare("SELECT * FROM users WHERE google_id = ? OR email = ? LIMIT 1");
    $stmt->execute([$googleId, $email]);
    $user = $stmt->fetch();

    if ($user) {
        if (($user['role'] ?? 'user') !== 'user') {
            // Staff / admin accounts have their own login pages and sessions.
            google_login_fail('This is a staff/admin account. Please use the Staff or Admin login page.');
        }
        if (geogene_account_is_disabled($user['status'])) {
            google_login_fail(GEOGENE_DISABLED_MESSAGE);
        }
        // Link the Google account, and mark the email verified (Google just proved it).
        $pdo->prepare("UPDATE users SET google_id = COALESCE(google_id, ?), email_verified = 1 WHERE user_id = ?")
            ->execute([$googleId, $user['user_id']]);
        $userId = $user['user_id'];
        $fullName = $user['full_name'] ?: $fullName;
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO users (full_name, email, password_hash, role, status, email_verified, google_id)
             VALUES (?, ?, NULL, 'user', 'pending', 1, ?)"
        );
        $stmt->execute([$fullName, $email, $googleId]);
        $userId = $pdo->lastInsertId();
        $user = ['identity_verified' => 0];
    }

    // ---------- 5. Log in ----------
    session_regenerate_id(true);
    $_SESSION['user_id']           = $userId;
    $_SESSION['full_name']         = $fullName;
    $_SESSION['identity_verified'] = $user['identity_verified'] ?? 0;

    // Next time this device opens the app it goes straight to Login (skips the intro)
    if (is_file(__DIR__ . '/../includes/returning-device.php')) {
        require_once __DIR__ . '/../includes/returning-device.php';
        gg_mark_returning_device();
    }

    $pdo->prepare("INSERT INTO login_logs (user_id, ip_address, user_agent) VALUES (?, ?, ?)")
        ->execute([$userId, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '']);

    if (empty($_SESSION['identity_verified'])) {
        header('Location: ../identity-verification/scan-id.php');
    } else {
        header('Location: ../dashboard/dashboard.php');
    }
    exit;

} catch (Throwable $e) {
    gg_google_log('callback crashed: ' . $e->getMessage());
    google_login_fail('Something went wrong with Google sign-in. Please try again.');
}
