<?php
/**
 * Certificate scan photos are NOT served straight from /uploads any more (that folder is blocked by its own
 * .htaccess, because the photos hold personal data). They are served by civil-registry-image.php, which
 * checks that a staff member / admin is logged in first. gg_scan_url() turns a stored path
 * ("uploads/civil-registry-scans/scan_3_front1_1700000000_123.jpg") into that URL ("" if it isn't one).
 */
if (!defined('GG_SCAN_FILE_PATTERN')) {
    define('GG_SCAN_FILE_PATTERN', '/^scan_\d+_[A-Za-z0-9]+_\d+_\d+\.(jpg|png|webp)$/');
}
if (!function_exists('gg_scan_url')) {
    function gg_scan_url(?string $storedPath): string
    {
        $f = basename(str_replace('\\', '/', (string) $storedPath));
        return preg_match(GG_SCAN_FILE_PATTERN, $f) ? 'civil-registry-image.php?f=' . rawurlencode($f) : '';
    }
}
