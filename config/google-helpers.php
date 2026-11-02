<?php
// GeoGene - shared Google Sign-In helpers (used by auth/google-callback.php,
// staff/google-login.php and admin/google-login.php).
//
// Goals:
//  * never crash with an HTTP 500 - every failure becomes a friendly message,
//    and the REAL reason (Google's error, cURL error, redirect URI used) is written
//    to the server error log with the prefix [GeoGene google]
//  * verify that an access token was really issued to OUR Google client (audience check)
//  * work on XAMPP (missing CA bundle) and on shared hosting without cURL

require_once __DIR__ . '/google-oauth.php';

// Google endpoints (only overridden by automated tests)
if (!defined('GOOGLE_AUTH_URL'))      define('GOOGLE_AUTH_URL',      'https://accounts.google.com/o/oauth2/v2/auth');
if (!defined('GOOGLE_TOKEN_URL'))     define('GOOGLE_TOKEN_URL',     'https://oauth2.googleapis.com/token');
if (!defined('GOOGLE_TOKENINFO_URL')) define('GOOGLE_TOKENINFO_URL', 'https://oauth2.googleapis.com/tokeninfo');
if (!defined('GOOGLE_USERINFO_URL'))  define('GOOGLE_USERINFO_URL',  'https://www.googleapis.com/oauth2/v3/userinfo');

function gg_google_configured(): bool
{
    return GOOGLE_CLIENT_ID !== '' && GOOGLE_CLIENT_SECRET !== ''
        && strpos(GOOGLE_CLIENT_ID, 'PASTE-YOUR') === false;
}

function gg_google_log(string $msg): void
{
    error_log('[GeoGene google] ' . $msg);
}

/**
 * Small HTTP client. Returns ['status' => int, 'body' => string, 'error' => string].
 * $post = array => POST form-encoded; null => GET.
 */
function gg_google_http(string $url, ?array $post = null, array $headers = []): array
{
    if (function_exists('curl_init')) {
        $run = function (bool $verify) use ($url, $post, $headers) {
            $ch = curl_init($url);
            $opts = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_SSL_VERIFYPEER => $verify,
                CURLOPT_SSL_VERIFYHOST => $verify ? 2 : 0,
                CURLOPT_HTTPHEADER     => $headers,
            ];
            if ($post !== null) {
                $opts[CURLOPT_POST] = true;
                $opts[CURLOPT_POSTFIELDS] = http_build_query($post);
            }
            curl_setopt_array($ch, $opts);
            $body = curl_exec($ch);
            $res = [
                'status' => (int) curl_getinfo($ch, CURLINFO_HTTP_CODE),
                'body'   => $body === false ? '' : (string) $body,
                'error'  => $body === false ? curl_error($ch) : '',
                'errno'  => curl_errno($ch),
            ];
            curl_close($ch);
            return $res;
        };
        $r = $run(true);
        // XAMPP on Windows often has no CA bundle ("SSL certificate problem"). Only on a local
        // (non-production) install do we retry without verification so development keeps working.
        if ($r['error'] !== '' && in_array($r['errno'], [60, 77], true) && !GEOGENE_PRODUCTION) {
            gg_google_log('SSL certificate problem on local install; retrying without verification. '
                . 'Set curl.cainfo in php.ini to fix this properly.');
            $r = $run(false);
        }
        return $r;
    }

    // No cURL on this host: fall back to PHP streams.
    $h = $headers;
    if ($post !== null) $h[] = 'Content-Type: application/x-www-form-urlencoded';
    $ctx = stream_context_create(['http' => [
        'method' => $post !== null ? 'POST' : 'GET',
        'header' => implode("\r\n", $h),
        'content' => $post !== null ? http_build_query($post) : '',
        'timeout' => 15, 'ignore_errors' => true,
    ]]);
    $body = @file_get_contents($url, false, $ctx);
    $status = 0;
    foreach (($http_response_header ?? []) as $line) {
        if (preg_match('#^HTTP/\S+\s+(\d{3})#', $line, $m)) $status = (int) $m[1];
    }
    return ['status' => $status, 'body' => $body === false ? '' : $body, 'error' => $body === false ? 'request failed' : '', 'errno' => 0];
}

/** Friendly text for the error codes Google's token endpoint can return. */
function gg_google_friendly_error(string $googleError): string
{
    switch ($googleError) {
        case 'redirect_uri_mismatch':
        case 'invalid_client':
        case 'unauthorized_client':
            return 'Google Sign-In is not set up correctly for this website yet. Please contact the administrator.';
        case 'invalid_grant':
            return 'That Google sign-in link already expired. Please tap "Continue with Google" again.';
        default:
            return 'Google sign-in failed. Please try again.';
    }
}

/**
 * Checks an access token (from Google Identity Services) and returns the verified profile.
 * Returns ['ok' => true, 'profile' => [email, email_verified, name, sub, picture]]
 *      or ['ok' => false, 'message' => friendly text].
 */
function gg_google_profile_from_access_token(string $accessToken): array
{
    if (!gg_google_configured()) {
        gg_google_log('GOOGLE_CLIENT_ID / GOOGLE_CLIENT_SECRET are empty (check the .env file on the server).');
        return ['ok' => false, 'message' => 'Google Sign-In is not set up yet. Please contact the administrator.'];
    }

    // 1) Was this token issued to OUR client? (otherwise any app's token for the same person would work)
    $r = gg_google_http(GOOGLE_TOKENINFO_URL . '?access_token=' . urlencode($accessToken));
    if ($r['error'] !== '' || $r['status'] !== 200) {
        gg_google_log("tokeninfo failed: HTTP {$r['status']} {$r['error']} " . substr($r['body'], 0, 200));
        return ['ok' => false, 'message' => 'Unable to verify your Google account. Please try again.'];
    }
    $info = json_decode($r['body'], true) ?: [];
    $aud = $info['aud'] ?? ($info['azp'] ?? '');
    if (!hash_equals(GOOGLE_CLIENT_ID, (string) $aud)) {
        gg_google_log('access token was issued to a different client: ' . $aud);
        return ['ok' => false, 'message' => 'This Google sign-in could not be verified. Please try again.'];
    }

    // 2) Who is it?
    $r = gg_google_http(GOOGLE_USERINFO_URL, null, ['Authorization: Bearer ' . $accessToken]);
    if ($r['error'] !== '' || $r['status'] !== 200) {
        gg_google_log("userinfo failed: HTTP {$r['status']} {$r['error']} " . substr($r['body'], 0, 200));
        return ['ok' => false, 'message' => 'Unable to read your Google account. Please try again.'];
    }
    $p = json_decode($r['body'], true) ?: [];
    $email = strtolower(trim((string) ($p['email'] ?? '')));
    $verified = ($p['email_verified'] ?? false);
    $verified = ($verified === true || $verified === 'true' || $verified === 1 || $verified === '1');
    if ($email === '' || !$verified) {
        return ['ok' => false, 'message' => 'Your Google account email could not be verified.'];
    }
    return ['ok' => true, 'profile' => [
        'email' => $email, 'email_verified' => true,
        'name' => (string) ($p['name'] ?? ''), 'sub' => (string) ($p['sub'] ?? ''),
        'picture' => $p['picture'] ?? null,
    ]];
}

/**
 * Shared "Sign in with Google" for the STAFF and ADMIN portals.
 * Never creates an account - it only signs in an existing account that has the given role.
 * Returns ['success' => bool, 'message' => string, 'redirect' => string?]
 * (the caller must already have started the right session: staff = default, admin = admin session name)
 */
function gg_google_portal_login(PDO $pdo, string $accessToken, string $role, string $redirect): array
{
    require_once __DIR__ . '/account-status.php';
    if ($accessToken === '') return ['success' => false, 'message' => 'No Google credential was received.'];

    $res = gg_google_profile_from_access_token($accessToken);
    if (!$res['ok']) return ['success' => false, 'message' => $res['message']];
    $email = $res['profile']['email'];

    if (GOOGLE_ALLOWED_DOMAIN !== '' && substr($email, -strlen('@' . GOOGLE_ALLOWED_DOMAIN)) !== strtolower('@' . GOOGLE_ALLOWED_DOMAIN)) {
        return ['success' => false, 'message' => 'Please sign in using your ' . GOOGLE_ALLOWED_DOMAIN . ' institutional email address.'];
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = ? LIMIT 1");
    $stmt->execute([$email, $role]);
    $acct = $stmt->fetch();

    $label = $role === 'admin' ? 'admin' : 'staff';
    if (!$acct) {
        return ['success' => false, 'message' => "No $label account found for $email. Ask an administrator to create your account first."];
    }
    if (geogene_account_is_disabled($acct['status'])) {
        return ['success' => false, 'message' => GEOGENE_DISABLED_MESSAGE];
    }
    if ($acct['status'] !== 'active') {
        return ['success' => false, 'message' => "Your $label account is not active yet. Please contact an administrator."];
    }

    // Remember the Google account on first use (same as the user app does)
    if (!empty($res['profile']['sub']) && empty($acct['google_id'])) {
        try {
            $pdo->prepare("UPDATE users SET google_id = ? WHERE user_id = ? AND google_id IS NULL")
                ->execute([$res['profile']['sub'], $acct['user_id']]);
        } catch (Throwable $e) { /* optional */ }
    }

    session_regenerate_id(true);
    $_SESSION['staff_id']   = $acct['user_id'];
    $_SESSION['staff_name'] = $acct['full_name'];
    $_SESSION['staff_role'] = $acct['role'];

    $pdo->prepare("INSERT INTO login_logs (user_id, ip_address, user_agent) VALUES (?, ?, ?)")
        ->execute([$acct['user_id'], $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '']);

    return ['success' => true, 'message' => 'Signed in successfully.', 'redirect' => $redirect];
}
