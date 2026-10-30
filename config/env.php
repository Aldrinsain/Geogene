<?php
/**
 * GeoGene - .env loader (walay Composer / dugang nga library).
 *
 * Gibasa ang  .env  file sa ROOT sa project (tupad sa index.php) ug gihimo nga
 * constants ang mga setting niini: DB_HOST, DB_NAME, DB_USER, DB_PASS,
 * SMTP_USER, SMTP_PASS, GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, ID_VERIFY_PEPPER.
 * Mahimo sab nga kuhaon sa ubang code gamit ang  geogene_env('SMTP_FROM_EMAIL', 'default').
 *
 * - Kung naay .env  -> .env ra ang gamiton.
 * - Kung WALAY .env -> mo-fallback sa daan nga  config/secrets.php  (kung naa).
 * - Kung walay duha -> gamiton ang XAMPP defaults (root, walay password).
 *
 * I-require_once kini sa matag config file - usa ra kini mo-load bisan ilang ihap.
 */
if (!function_exists('geogene_env')) {

    /** I-parse ang sulod sa .env -> array. Suporta ang "quotes", # comments, CRLF (Windows), BOM. */
    function geogene_env_parse(string $text): array
    {
        $text = preg_replace('/^\xEF\xBB\xBF/', '', $text);       // UTF-8 BOM (gidugang sa Notepad usahay)
        $out = [];
        foreach (preg_split('/\r\n|\r|\n/', $text) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') continue;
            if (stripos($line, 'export ') === 0) $line = ltrim(substr($line, 7));
            $eq = strpos($line, '=');
            if ($eq === false) continue;
            $key = trim(substr($line, 0, $eq));
            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key)) continue;
            $val = ltrim(substr($line, $eq + 1));

            if ($val !== '' && ($val[0] === '"' || $val[0] === "'")) {
                $q = $val[0];
                $buf = '';
                $n = strlen($val);
                for ($i = 1; $i < $n; $i++) {
                    $c = $val[$i];
                    if ($q === '"' && $c === '\\' && $i + 1 < $n && ($val[$i + 1] === '"' || $val[$i + 1] === '\\')) {
                        $buf .= $val[$i + 1];
                        $i++;
                        continue;
                    }
                    if ($c === $q) break;                        // closing quote - ignore the rest of the line
                    $buf .= $c;
                }
                $val = $buf;
            } else {
                $val = rtrim(preg_replace('/\s+#.*$/', '', $val));   // "value  # comment"
            }
            $out[$key] = $val;
        }
        return $out;
    }

    /** Kuhaa ang value gikan sa .env  (o ang $default kung wala). */
    function geogene_env(string $key, $default = null)
    {
        $env = $GLOBALS['GEOGENE_ENV'] ?? [];
        return array_key_exists($key, $env) ? $env[$key] : $default;
    }

    $GLOBALS['GEOGENE_ENV'] = [];
    // Use .env; if it is missing, accept .env.production (so forgetting to rename it on the server
    // no longer silently drops the DB + SMTP settings and breaks every email).
    $geoEnvFile = dirname(__DIR__) . '/.env';
    if (!is_file($geoEnvFile) && is_file(dirname(__DIR__) . '/.env.production')) {
        $geoEnvFile = dirname(__DIR__) . '/.env.production';
    }

    if (is_file($geoEnvFile) && is_readable($geoEnvFile)) {
        $GLOBALS['GEOGENE_ENV'] = geogene_env_parse((string) file_get_contents($geoEnvFile));
        $geoEnvKeys = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS',
                       'SMTP_USER', 'SMTP_PASS',
                       'GOOGLE_CLIENT_ID', 'GOOGLE_CLIENT_SECRET',
                       'ID_VERIFY_PEPPER'];   // GEOGENE_PRODUCTION is parsed to a real true/false below (the text "false" would be truthy)
        foreach ($geoEnvKeys as $geoKey) {
            if (array_key_exists($geoKey, $GLOBALS['GEOGENE_ENV']) && !defined($geoKey)) {
                define($geoKey, $GLOBALS['GEOGENE_ENV'][$geoKey]);
            }
        }
    } elseif (is_file(__DIR__ . '/secrets.php')) {
        require_once __DIR__ . '/secrets.php';        // daan nga paagi (fallback)
    }
}

// ---------- LIVE site vs LOCAL (XAMPP) ----------
// GEOGENE_PRODUCTION=true  in .env  -> live site.   =false -> local/testing.
// Kung wala gi-set, auto-detect: localhost / 127.0.0.1 / *.local / LAN IP (192.168.x, 10.x) = local;
// bisan unsa nga laing host (e.g. imong Hostinger domain) = LIVE.
// Sa LIVE: walay PHP error nga ipakita sa browser, ug ang OTP code DILI ipakita sa screen
// (kung pumalya ang email, dili makalusot ang tawo sa email verification).
if (!defined('GEOGENE_PRODUCTION')) {
    $geoFlag = geogene_env('GEOGENE_PRODUCTION', null);
    if ($geoFlag !== null && $geoFlag !== '') {
        define('GEOGENE_PRODUCTION', in_array(strtolower((string) $geoFlag), ['1', 'true', 'yes', 'on'], true));
    } else {
        $geoHost = strtolower(preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? 'localhost')));
        $geoLocal = $geoHost === 'localhost' || $geoHost === '[::1]' || $geoHost === '::1'
            || (bool) preg_match('/^(127\.|10\.|192\.168\.|172\.(1[6-9]|2\d|3[01])\.)/', $geoHost)
            || (bool) preg_match('/\.(local|test|localhost)$/', $geoHost)
            || PHP_SAPI === 'cli';
        define('GEOGENE_PRODUCTION', !$geoLocal);
    }
}
if (!defined('GEOGENE_SHOW_DEV_OTP')) define('GEOGENE_SHOW_DEV_OTP', !GEOGENE_PRODUCTION);
if (GEOGENE_PRODUCTION) {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}
