<?php
// GeoGene - Forgot Password logic (citizen/user accounts).
//
// Uses the EXISTING  otp_codes  table (purpose = 'password_reset'). The table
// is upgraded in place the first time this file runs (and migrations/
// add_password_reset_security.sql does the same thing if you prefer to run it
// by hand in phpMyAdmin):
//   - otp_code  VARCHAR(6) -> VARCHAR(255)   (so a password_hash() of the code fits)
//   - attempts  TINYINT     (wrong-code counter, brute-force protection)
//   - request_ip VARCHAR(45) (per-IP request limit)
//
// Registration OTPs (purpose = 'email_verification') are NOT touched.
//
// Rules:
//   * 6-digit code from random_int() (cryptographically secure)
//   * stored only as password_hash(); compared with password_verify()
//   * expires after 10 minutes, single use
//   * a new request invalidates all older unused reset codes of that user
//   * resend cooldown 60s, max 5 codes / hour / account, max 10 / hour / IP
//   * max 5 wrong guesses per code, then the code is dead
//   * every DB / mail problem is logged server-side and turned into a friendly
//     message - never an HTTP 500, never a leaked server error

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mail.php';

const GG_PR_TTL_MINUTES   = 10;
const GG_PR_COOLDOWN_SEC  = 60;
const GG_PR_MAX_PER_USER  = 5;     // per hour
const GG_PR_MAX_PER_IP    = 10;    // per hour
const GG_PR_MAX_ATTEMPTS  = 5;

/**
 * Which login area is resetting a password. Each area keeps its own session keys
 * (so a citizen, staff and admin reset can't mix) and only accepts its own roles.
 *   user  = citizen app  (auth/*.php)      any role, like before
 *   staff = Civil Registry staff (root forgot/reset-password.php)  role office_head
 *   admin = admin/forgot-password.php, admin/reset-password.php    role admin
 */
function gg_pr_context(string $who): array {
    switch ($who) {
        case 'staff':
            return ['roles' => ['office_head'], 'uid' => 'staff_reset_user_id', 'email' => 'staff_reset_email',
                    'preview' => 'staff_reset_otp_preview', 'cool' => 'staff_pr_last_request'];
        case 'admin':
            return ['roles' => ['admin'], 'uid' => 'admin_reset_user_id', 'email' => 'admin_reset_email',
                    'preview' => 'admin_reset_otp_preview', 'cool' => 'admin_pr_last_request'];
        default:
            return ['roles' => [], 'uid' => 'reset_user_id', 'email' => 'reset_email',
                    'preview' => 'dev_otp_preview', 'cool' => 'pr_last_request'];
    }
}

/** Remove the reset session data of one area (after a successful reset). */
function gg_pr_clear_session(string $who): void {
    $c = gg_pr_context($who);
    unset($_SESSION[$c['uid']], $_SESSION[$c['email']], $_SESSION[$c['preview']], $_SESSION[$c['cool']]);
}

/** Make sure otp_codes has the columns we need. Returns true when ready. */
function gg_pr_ensure_schema(PDO $pdo): bool {
    static $ready = null;
    if ($ready !== null) return $ready;
    try {
        $cols = [];
        $q = $pdo->prepare("SELECT COLUMN_NAME, CHARACTER_MAXIMUM_LENGTH FROM information_schema.COLUMNS
                            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'otp_codes'");
        $q->execute();
        foreach ($q->fetchAll() as $r) $cols[$r['COLUMN_NAME']] = (int) $r['CHARACTER_MAXIMUM_LENGTH'];

        if (!isset($cols['otp_code'])) { error_log('[GeoGene reset] otp_codes table/otp_code column missing'); return $ready = false; }
        if ($cols['otp_code'] < 100) $pdo->exec("ALTER TABLE otp_codes MODIFY otp_code VARCHAR(255) NOT NULL");
        if (!isset($cols['attempts']))   $pdo->exec("ALTER TABLE otp_codes ADD COLUMN attempts TINYINT UNSIGNED NOT NULL DEFAULT 0");
        if (!isset($cols['request_ip'])) $pdo->exec("ALTER TABLE otp_codes ADD COLUMN request_ip VARCHAR(45) NULL");
        return $ready = true;
    } catch (Throwable $e) {
        error_log('[GeoGene reset] schema upgrade failed: ' . $e->getMessage()
            . '  -> run migrations/add_password_reset_security.sql in phpMyAdmin');
        return $ready = false;
    }
}

function gg_pr_client_ip(): string {
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}

/**
 * Create + email a reset code.
 * Returns ['status' => ..., 'message' => ..., 'user_id' => int|null, 'email' => string, 'dev_code' => string|null]
 * status: sent | unknown (no such account - shown to the user exactly like "sent")
 *         | invalid_email | cooldown | limit | mail_failed | error
 */
function gg_pr_request_code(PDO $pdo, string $emailRaw, string $who = 'user'): array {
    $c = gg_pr_context($who);
    $roles = $c['roles'];
    $email = strtolower(trim($emailRaw));
    $res = ['status' => 'error', 'message' => 'Something went wrong. Please try again in a moment.',
            'user_id' => null, 'email' => $email, 'dev_code' => null, 'wait' => 0];

    if ($email === '' || strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['status' => 'invalid_email', 'message' => 'Please enter a valid email address.'] + $res;
    }

    // Cooldown that applies to everybody (also unknown emails), kept in the session.
    $last = (int) ($_SESSION[$c['cool']] ?? 0);
    if ($last && time() - $last < GG_PR_COOLDOWN_SEC) {
        $wait = GG_PR_COOLDOWN_SEC - (time() - $last);
        return ['status' => 'cooldown', 'wait' => $wait,
                'message' => "Please wait $wait seconds before requesting another code."] + $res;
    }

    try {
        if (!gg_pr_ensure_schema($pdo)) return $res;

        $sql = "SELECT user_id, full_name FROM users WHERE email = ?";
        $params = [$email];
        if ($roles) {
            $sql .= " AND role IN (" . implode(',', array_fill(0, count($roles), '?')) . ")";
            $params = array_merge($params, $roles);
        }
        $st = $pdo->prepare($sql);
        $st->execute($params);
        $user = $st->fetch();

        $_SESSION[$c['cool']] = time();

        if (!$user) {
            // Same outward behaviour as a real send, so the form can't be used to discover accounts.
            unset($_SESSION[$c['uid']], $_SESSION[$c['email']], $_SESSION[$c['preview']]);
            return ['status' => 'unknown', 'message' => ''] + $res;
        }

        $uid = (int) $user['user_id'];
        $ip  = gg_pr_client_ip();
        $now = date('Y-m-d H:i:s');
        $hourAgo = date('Y-m-d H:i:s', time() - 3600);

        // Per-account cooldown + hourly limits
        $st = $pdo->prepare("SELECT created_at FROM otp_codes WHERE user_id = ? AND purpose = 'password_reset' ORDER BY otp_id DESC LIMIT 1");
        $st->execute([$uid]);
        $lastRow = $st->fetchColumn();
        if ($lastRow && time() - strtotime($lastRow) < GG_PR_COOLDOWN_SEC) {
            $wait = GG_PR_COOLDOWN_SEC - (time() - strtotime($lastRow));
            return ['status' => 'cooldown', 'wait' => $wait,
                    'message' => "Please wait $wait seconds before requesting another code."] + $res;
        }
        $st = $pdo->prepare("SELECT COUNT(*) FROM otp_codes WHERE user_id = ? AND purpose = 'password_reset' AND created_at >= ?");
        $st->execute([$uid, $hourAgo]);
        $cntUser = (int) $st->fetchColumn();
        $st = $pdo->prepare("SELECT COUNT(*) FROM otp_codes WHERE request_ip = ? AND purpose = 'password_reset' AND created_at >= ?");
        $st->execute([$ip, $hourAgo]);
        $cntIp = (int) $st->fetchColumn();
        if ($cntUser >= GG_PR_MAX_PER_USER || $cntIp >= GG_PR_MAX_PER_IP) {
            return ['status' => 'limit',
                    'message' => 'Too many reset requests. Please try again in an hour.'] + $res;
        }

        // New code - old unused ones are invalidated first
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $hash = password_hash($code, PASSWORD_DEFAULT);
        $exp  = date('Y-m-d H:i:s', time() + GG_PR_TTL_MINUTES * 60);

        $pdo->prepare("UPDATE otp_codes SET is_used = 1 WHERE user_id = ? AND purpose = 'password_reset' AND is_used = 0")->execute([$uid]);
        $pdo->prepare("INSERT INTO otp_codes (user_id, otp_code, purpose, expires_at, is_used, attempts, request_ip, created_at)
                       VALUES (?, ?, 'password_reset', ?, 0, 0, ?, ?)")
            ->execute([$uid, $hash, $exp, $ip, $now]);
        $otpId = (int) $pdo->lastInsertId();

        $sent = send_password_reset_email($email, $user['full_name'], $code);

        if (!$sent) {
            // Dev/localhost without SMTP: show the code on screen so testing can continue.
            if (GEOGENE_SHOW_DEV_OTP) {
                $_SESSION[$c['uid']]     = $uid;
                $_SESSION[$c['email']]   = $email;
                $_SESSION[$c['preview']] = $code;
                return ['status' => 'sent', 'message' => '', 'user_id' => $uid, 'dev_code' => $code] + $res;
            }
            $pdo->prepare("UPDATE otp_codes SET is_used = 1 WHERE otp_id = ?")->execute([$otpId]);
            $_SESSION[$c['cool']] = 0;   // let them retry right away
            return ['status' => 'mail_failed',
                    'message' => "We couldn't send the email right now. Please try again in a few minutes."] + $res;
        }

        $_SESSION[$c['uid']]   = $uid;
        $_SESSION[$c['email']] = $email;
        unset($_SESSION[$c['preview']]);
        return ['status' => 'sent', 'message' => '', 'user_id' => $uid] + $res;

    } catch (Throwable $e) {
        error_log('[GeoGene reset] request failed: ' . $e->getMessage());
        return $res;
    }
}

/**
 * Check the code and set the new password.
 * Returns ['ok' => bool, 'message' => string, 'role' => string|null]
 */
function gg_pr_verify_and_reset(PDO $pdo, int $userId, string $code, string $newPassword, string $who = 'user'): array {
    $roles = gg_pr_context($who)['roles'];
    $fail = function ($m) { return ['ok' => false, 'message' => $m, 'role' => null]; };
    try {
        if (!gg_pr_ensure_schema($pdo)) return $fail('Something went wrong. Please try again in a moment.');

        if ($roles) {   // staff/admin pages only ever reset accounts with their own role
            $rr = $pdo->prepare("SELECT role FROM users WHERE user_id = ?");
            $rr->execute([$userId]);
            if (!in_array($rr->fetchColumn(), $roles, true)) return $fail('This reset request is not valid. Please start again.');
        }

        $st = $pdo->prepare("SELECT otp_id, otp_code, attempts FROM otp_codes
                             WHERE user_id = ? AND purpose = 'password_reset' AND is_used = 0 AND expires_at >= ?
                             ORDER BY otp_id DESC LIMIT 1");
        $st->execute([$userId, date('Y-m-d H:i:s')]);
        $row = $st->fetch();

        if (!$row) return $fail('This code has expired or was already used. Please request a new one.');

        if ((int) $row['attempts'] >= GG_PR_MAX_ATTEMPTS) {
            $pdo->prepare("UPDATE otp_codes SET is_used = 1 WHERE otp_id = ?")->execute([$row['otp_id']]);
            return $fail('Too many wrong attempts. Please request a new code.');
        }

        if (!password_verify($code, $row['otp_code'])) {
            $pdo->prepare("UPDATE otp_codes SET attempts = attempts + 1 WHERE otp_id = ?")->execute([$row['otp_id']]);
            $left = GG_PR_MAX_ATTEMPTS - ((int) $row['attempts'] + 1);
            if ($left <= 0) {
                $pdo->prepare("UPDATE otp_codes SET is_used = 1 WHERE otp_id = ?")->execute([$row['otp_id']]);
                return $fail('Too many wrong attempts. Please request a new code.');
            }
            return $fail("That code is incorrect. You have $left " . ($left === 1 ? 'try' : 'tries') . ' left.');
        }

        $pdo->beginTransaction();
        // Claim the code atomically - only one request can ever win this UPDATE.
        $claim = $pdo->prepare("UPDATE otp_codes SET is_used = 1 WHERE otp_id = ? AND is_used = 0");
        $claim->execute([$row['otp_id']]);
        if ($claim->rowCount() !== 1) { $pdo->rollBack(); return $fail('This code was already used. Please request a new one.'); }

        $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?")
            ->execute([password_hash($newPassword, PASSWORD_DEFAULT), $userId]);
        $pdo->prepare("UPDATE otp_codes SET is_used = 1 WHERE user_id = ? AND purpose = 'password_reset'")->execute([$userId]);
        $pdo->commit();

        $r = $pdo->prepare("SELECT role FROM users WHERE user_id = ?");
        $r->execute([$userId]);
        return ['ok' => true, 'message' => '', 'role' => $r->fetchColumn() ?: 'user'];

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('[GeoGene reset] verify failed: ' . $e->getMessage());
        return $fail('Something went wrong. Please try again in a moment.');
    }
}
