<?php
// GeoGene - Mail Helper (send OTP codes via SMTP using PHPMailer)
//
// SETUP REQUIRED (optional, para tinuod nga mo-send og email):
// 1. Download PHPMailer: https://github.com/PHPMailer/PHPMailer
// 2. Extract the "src" folder into: geogene/lib/PHPMailer/
//    (dapat naa: geogene/lib/PHPMailer/Exception.php, PHPMailer.php, SMTP.php)
// 3. Palitan ang SMTP credentials sa ubos (Gmail App Password, dili regular password)
//
// KUNG WALA PA NI NA-SETUP: mo-work gihapon ang Register/OTP flow.
// Ang OTP dili lang ma-email - i-store ra sa session ug ipakita diretso sa
// verify-email.php screen (DEV MODE banner) para makapadayon ka og testing.

// Ang email settings naa na sa  .env  file (SMTP_USER, SMTP_PASS, ug kung gusto: SMTP_HOST,
// SMTP_PORT, SMTP_FROM_NAME, SMTP_FROM_EMAIL). Dili i-commit ang .env sa Git.
require_once __DIR__ . '/env.php';
if (!defined('SMTP_USER')) define('SMTP_USER', '');
if (!defined('SMTP_PASS')) define('SMTP_PASS', '');   // walay SMTP_PASS -> DEV MODE (OTP ipakita sa screen)
define('SMTP_HOST', geogene_env('SMTP_HOST', 'smtp-relay.brevo.com'));
define('SMTP_PORT', (int) geogene_env('SMTP_PORT', 587));
define('SMTP_FROM_NAME', geogene_env('SMTP_FROM_NAME', 'GeoGene'));
define('SMTP_FROM_EMAIL', geogene_env('SMTP_FROM_EMAIL', 'aldrinpilotonsain2003@gmail.com')); // verified sender sa Brevo

$PHPMAILER_PATH = __DIR__ . '/../lib/PHPMailer/PHPMailer.php';
define('PHPMAILER_AVAILABLE', file_exists($PHPMAILER_PATH));

if (PHPMAILER_AVAILABLE) {
    require_once __DIR__ . '/../lib/PHPMailer/Exception.php';
    require_once __DIR__ . '/../lib/PHPMailer/PHPMailer.php';
    require_once __DIR__ . '/../lib/PHPMailer/SMTP.php';
}

// ---------------------------------------------------------------------------
// Shared SMTP sender.
//
// - NEVER throws: every failure (PHPMailer exception, PHP Error, missing
//   extension, timeout) is caught, logged to the server error log, and
//   returned as false. This is what prevents the "HTTP ERROR 500" on
//   "Send Reset Code" - a mail problem can no longer crash the page.
// - Short timeouts so a blocked/slow SMTP port cannot hang the request until
//   PHP's max_execution_time (300s) kills it (that shows up as a 500 too).
// - If the configured port is 587 (STARTTLS) and the connection fails, it
//   automatically retries once on 465 (SMTPS), because some shared hosts
//   block one port but not the other.
// - The last error text is kept in $GLOBALS['GEOGENE_MAIL_LAST_ERROR'] for
//   logging only. It is never shown to the user.
// ---------------------------------------------------------------------------
function geogene_smtp_configured() {
    return SMTP_USER !== '' && SMTP_PASS !== '';
}

function geogene_send_mail($toEmail, $toName, $subject, $htmlBody, $altBody = '') {
    $GLOBALS['GEOGENE_MAIL_LAST_ERROR'] = '';

    if (!PHPMAILER_AVAILABLE) {
        $GLOBALS['GEOGENE_MAIL_LAST_ERROR'] = 'PHPMailer files not found in lib/PHPMailer/';
        error_log('[GeoGene mail] ' . $GLOBALS['GEOGENE_MAIL_LAST_ERROR']);
        return false;
    }
    if (!geogene_smtp_configured()) {
        $GLOBALS['GEOGENE_MAIL_LAST_ERROR'] = 'SMTP_USER / SMTP_PASS are empty (check the .env file on the server)';
        error_log('[GeoGene mail] ' . $GLOBALS['GEOGENE_MAIL_LAST_ERROR']);
        return false;
    }
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        $GLOBALS['GEOGENE_MAIL_LAST_ERROR'] = 'Invalid recipient address';
        error_log('[GeoGene mail] Invalid recipient address: ' . $toEmail);
        return false;
    }

    // [host, port, encryption]  - primary first, then a fallback.
    // Optional .env setting SMTP_SECURE=tls | ssl | none  (default: ssl for port 465, tls otherwise)
    $secure = strtolower((string) geogene_env('SMTP_SECURE', SMTP_PORT === 465 ? 'ssl' : 'tls'));
    $attempts = [[SMTP_HOST, SMTP_PORT, $secure]];
    if (SMTP_PORT === 587 && $secure === 'tls') {
        $attempts[] = [SMTP_HOST, 465, 'ssl'];
    }

    @ini_set('default_socket_timeout', '15');

    foreach ($attempts as $i => $a) {
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $a[0];
            $mail->Port       = $a[1];
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            if ($a[2] === 'ssl') {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($a[2] === 'none') {
                $mail->SMTPSecure  = '';
                $mail->SMTPAutoTLS = false;
            } else {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            }
            $mail->Timeout    = 12;      // seconds allowed to CONNECT to the SMTP server
            // Seconds to wait for each SMTP *reply*. PHPMailer's default is 300s, which
            // is what turns a slow/blocked SMTP server into an HTTP 500 (page timeout).
            $mail->getSMTPInstance()->Timelimit = 15;
            $mail->CharSet    = 'UTF-8';
            $mail->SMTPDebug  = 0;

            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($toEmail, (string) $toName);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = $altBody !== '' ? $altBody : trim(html_entity_decode(strip_tags($htmlBody)));

            $mail->send();
            return true;
        } catch (\Throwable $e) {
            $info = isset($mail) && $mail instanceof \PHPMailer\PHPMailer\PHPMailer && $mail->ErrorInfo !== ''
                ? $mail->ErrorInfo : $e->getMessage();
            $GLOBALS['GEOGENE_MAIL_LAST_ERROR'] = $info;
            error_log("[GeoGene mail] Send to $toEmail failed (port {$a[1]}): $info");
            // Only try the fallback port when the server could not be reached at all
            // (a wrong username/password would fail the same way on any port).
            if (stripos($info, 'connect') === false) break;
        }
    }
    return false;
}

/** GeoGene-branded HTML email (dark navy + green, table layout = works in Gmail/Outlook/mobile). */
function geogene_code_email_html($name, $headline, $intro, $code, $minutes = 10, $footerNote = '') {
    $n  = htmlspecialchars((string) $name, ENT_QUOTES, 'UTF-8');
    $h  = htmlspecialchars($headline, ENT_QUOTES, 'UTF-8');
    $in = htmlspecialchars($intro, ENT_QUOTES, 'UTF-8');
    $c  = htmlspecialchars((string) $code, ENT_QUOTES, 'UTF-8');
    $m  = (int) $minutes;
    $fn = htmlspecialchars($footerNote, ENT_QUOTES, 'UTF-8');
    return "<!DOCTYPE html><html><body style='margin:0;padding:0;background:#0b1224;'>
<table role='presentation' width='100%' cellpadding='0' cellspacing='0' style='background:#0b1224;padding:24px 12px;'>
 <tr><td align='center'>
  <table role='presentation' width='100%' cellpadding='0' cellspacing='0' style='max-width:480px;background:#131c36;border-radius:18px;overflow:hidden;font-family:Segoe UI,Arial,sans-serif;'>
   <tr><td style='padding:26px 28px 6px;text-align:center;'>
     <div style='font-size:26px;font-weight:800;color:#39ff88;letter-spacing:.5px;'>GeoGene</div>
     <div style='font-size:12px;color:#8a93b8;margin-top:2px;'>Civil Registry &amp; Genealogy</div>
   </td></tr>
   <tr><td style='padding:18px 28px 0;color:#f2f4fa;'>
     <h2 style='margin:0 0 10px;font-size:20px;color:#f2f4fa;'>$h</h2>
     <p style='margin:0 0 6px;font-size:14px;line-height:1.6;color:#c4cae3;'>Hi $n,</p>
     <p style='margin:0 0 18px;font-size:14px;line-height:1.6;color:#c4cae3;'>$in</p>
   </td></tr>
   <tr><td align='center' style='padding:0 28px;'>
     <div style='display:inline-block;background:#0b1224;border:1px solid #39ff88;border-radius:14px;padding:14px 26px;'>
       <span style='font-size:34px;font-weight:800;letter-spacing:10px;color:#39ff88;font-family:Consolas,Menlo,monospace;'>$c</span>
     </div>
   </td></tr>
   <tr><td style='padding:18px 28px 6px;color:#c4cae3;font-size:13px;line-height:1.6;text-align:center;'>
     This code expires in <strong style='color:#f2f4fa;'>$m minutes</strong> and can only be used once.
   </td></tr>
   <tr><td style='padding:8px 28px 26px;color:#8a93b8;font-size:12px;line-height:1.6;text-align:center;'>
     $fn<br>Never share this code with anyone. GeoGene staff will never ask for it.
   </td></tr>
  </table>
  <div style='font-family:Segoe UI,Arial,sans-serif;font-size:11px;color:#5d6788;margin-top:14px;'>This is an automated message from GeoGene. Please do not reply.</div>
 </td></tr>
</table></body></html>";
}

/**
 * Sends the registration / email-verification OTP.
 * Returns true if actually emailed, false otherwise (never throws).
 */
function send_otp_email($toEmail, $toName, $otp) {
    $html = geogene_code_email_html(
        $toName, 'Verify your email', 'Use the code below to verify your email address and finish creating your GeoGene account.',
        $otp, 10, 'If you did not create a GeoGene account, you can safely ignore this email.'
    );
    $ok = geogene_send_mail($toEmail, $toName, 'GeoGene - Email Verification Code', $html,
        "Your GeoGene verification code is $otp. It expires in 10 minutes.");
    if (!$ok && !GEOGENE_PRODUCTION) error_log("[GeoGene DEV MODE] Verification code for $toEmail is: $otp");
    return $ok;
}

/**
 * Sends the password-reset code (same SMTP setup, reset-specific wording).
 * Returns true if actually emailed, false otherwise (never throws).
 */
function send_password_reset_email($toEmail, $toName, $otp) {
    $html = geogene_code_email_html(
        $toName, 'Reset your password', 'We received a request to reset your GeoGene password. Enter this code on the reset page to choose a new password.',
        $otp, 10, 'If you did not request this, you can safely ignore this email - your password will not be changed.'
    );
    $ok = geogene_send_mail($toEmail, $toName, 'GeoGene - Password Reset Code', $html,
        "Your GeoGene password reset code is $otp. It expires in 10 minutes. If you did not request this, ignore this email.");
    if (!$ok && !GEOGENE_PRODUCTION) error_log("[GeoGene DEV MODE] Password reset code for $toEmail is: $otp");
    return $ok;
}
