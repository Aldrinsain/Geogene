<?php
// GeoGene - one place that loads private settings (API keys, DB password ...).
// NOTHING private is stored in the Git repository. Values are read, in this order, from:
//   1. config/secrets.php   (Hostinger: the file you edit on the server - gitignored)
//   2. real environment variables (getenv)
//   3. a .env file in the project root   (local dev - gitignored, see .env.example)
// The first one that defines a key wins.

if (defined('GEOGENE_ENV_LOADED')) return;
define('GEOGENE_ENV_LOADED', true);

if (is_file(__DIR__ . '/secrets.php')) require_once __DIR__ . '/secrets.php';

$__envFile = __DIR__ . '/../.env';
$__env = [];
if (is_file($__envFile)) {
    foreach (file($__envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $__line) {
        $__line = trim($__line);
        if ($__line === '' || $__line[0] === '#' || strpos($__line, '=') === false) continue;
        [$__k, $__v] = explode('=', $__line, 2);
        $__k = trim($__k);
        $__v = trim($__v);
        if (strlen($__v) >= 2 && ($__v[0] === '"' || $__v[0] === "'") && substr($__v, -1) === $__v[0]) {
            $__v = substr($__v, 1, -1);
        }
        $__env[$__k] = $__v;
    }
}

foreach ([
    'GEOGENE_PRODUCTION',
    'DB_HOST', 'DB_HOST_FALLBACK', 'DB_NAME', 'DB_USER', 'DB_PASS',
    'SMTP_USER', 'SMTP_PASS', 'SMTP_FROM_EMAIL',
    'GOOGLE_CLIENT_ID', 'GOOGLE_CLIENT_SECRET', 'GOOGLE_ALLOWED_DOMAIN',
    'ID_VERIFY_PEPPER', 'YOLO_SERVICE_URL',
] as $__key) {
    if (defined($__key)) continue;
    $__val = getenv($__key);
    if ($__val === false && isset($__env[$__key])) $__val = $__env[$__key];
    if ($__val === false || $__val === null) continue;
    if ($__key === 'GEOGENE_PRODUCTION') {
        $__val = in_array(strtolower((string) $__val), ['1', 'true', 'yes', 'on'], true);
    }
    define($__key, $__val);
}
unset($__envFile, $__env, $__line, $__k, $__v, $__key, $__val);
