<?php
session_start();
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

/**
 * Normalize a string for name-matching: uppercase, strip anything that
 * isn't a letter or space, collapse whitespace. OCR output from ID cards
 * is messy (mixed case, stray symbols, extra spaces), so both sides of
 * the comparison go through this before we check them against each other.
 */
function normalize_for_match(string $s): string
{
    $s = strtoupper($s);
    $s = preg_replace('/[^A-Z\s]/', ' ', $s);
    $s = preg_replace('/\s+/', ' ', $s);
    return trim($s);
}
