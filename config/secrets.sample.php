<?php
// GeoGene - SAMPLE secrets file (safe to commit - walay tinuod nga keys dinhi).
// 1. Kopyaha kini isip  config/secrets.php
// 2. Ibutang ang tinuod nga values. Ang secrets.php naa sa .gitignore, dili ma-push sa GitHub.

// Set to true on the live (Hostinger) site. Hides PHP errors and the on-screen "DEV MODE" OTP codes.
define('GEOGENE_PRODUCTION', false);

// Database (leave these out on local XAMPP to use root / empty password)
// define('DB_HOST', 'localhost');
// define('DB_HOST_FALLBACK', '');
// define('DB_NAME', 'geogene_db');
// define('DB_USER', 'root');
// define('DB_PASS', '');

define('SMTP_USER', 'your-brevo-smtp-login@smtp-brevo.com');   // Brevo > SMTP & API > SMTP
define('SMTP_PASS', 'your-brevo-smtp-key');                    // Brevo SMTP key (xsmtpsib-...)
define('GOOGLE_CLIENT_ID', 'your-id.apps.googleusercontent.com');  // Google Cloud Console > Credentials
define('GOOGLE_CLIENT_SECRET', 'your-google-client-secret');  // Google Cloud Console > Credentials
