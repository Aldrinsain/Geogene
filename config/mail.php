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

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'your_email@gmail.com');       // <-- palitan
define('SMTP_PASS', 'your_16_char_app_password');  // <-- palitan (Gmail App Password)
define('SMTP_PORT', 587);
define('SMTP_FROM_NAME', 'GeoGene');

$PHPMAILER_PATH = __DIR__ . '/../lib/PHPMailer/PHPMailer.php';
define('PHPMAILER_AVAILABLE', file_exists($PHPMAILER_PATH));

if (PHPMAILER_AVAILABLE) {
    require_once __DIR__ . '/../lib/PHPMailer/Exception.php';
    require_once __DIR__ . '/../lib/PHPMailer/PHPMailer.php';
    require_once __DIR__ . '/../lib/PHPMailer/SMTP.php';
}