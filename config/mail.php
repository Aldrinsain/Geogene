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

/**
 * Sends the OTP email. Returns true if actually emailed,
 * false if PHPMailer isn't installed yet or sending failed
 * (caller can fall back to showing the OTP on-screen for dev/testing).
 */
function send_otp_email($toEmail, $toName, $otp) {
    if (!PHPMAILER_AVAILABLE) {
        error_log("[GeoGene DEV MODE] PHPMailer not installed yet. OTP for $toEmail is: $otp");
        return false;
    }

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = 'GeoGene - Email Verification Code';
        $mail->Body    = "
            <div style='font-family:sans-serif;'>
              <h2 style='color:#1b6e3c;'>GeoGene</h2>
              <p>Hi $toName,</p>
              <p>Your verification code is:</p>
              <h1 style='letter-spacing:6px;color:#1b6e3c;'>$otp</h1>
              <p>This code will expire in 10 minutes. If you did not request this, please ignore this email.</p>
            </div>
        ";

        $mail->send();
        return true;
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        error_log("OTP Email failed for $toEmail: {$mail->ErrorInfo}");
        error_log("[GeoGene DEV MODE] OTP for $toEmail is: $otp");
        return false;
    }
}

/**
 * Sends a password-reset OTP email (same SMTP setup as send_otp_email(),
 * different subject/body so it's clearly a password reset, not an email
 * verification code).
 */
function send_password_reset_email($toEmail, $toName, $otp) {
    if (!PHPMAILER_AVAILABLE) {
        error_log("[GeoGene DEV MODE] PHPMailer not installed yet. Password reset code for $toEmail is: $otp");
        return false;
    }

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = 'GeoGene - Password Reset Code';
        $mail->Body    = "
            <div style='font-family:sans-serif;'>
              <h2 style='color:#1b6e3c;'>GeoGene</h2>
              <p>Hi $toName,</p>
              <p>We received a request to reset your password. Your reset code is:</p>
              <h1 style='letter-spacing:6px;color:#1b6e3c;'>$otp</h1>
              <p>This code will expire in 10 minutes. If you did not request this, you can safely ignore this email - your password will not be changed.</p>
            </div>
        ";

        $mail->send();
        return true;
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        error_log("Password reset email failed for $toEmail: {$mail->ErrorInfo}");
        error_log("[GeoGene DEV MODE] Password reset code for $toEmail is: $otp");
        return false;
    }
}
