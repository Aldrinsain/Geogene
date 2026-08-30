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
/**
 * Checks whether a single name token (e.g. "DELA CRUZ" or "JUAN") appears
 * in the OCR'd ID text - either as an exact substring, or (to tolerate
 * common OCR misreads like O/0, I/1, S/5) as a close fuzzy match against
 * any word-window of the same length in the OCR text.
 */
function token_found_in_text(string $token, string $ocrText): bool
{
    $token = trim($token);
    if ($token === '') return true; // nothing required, trivially "found"

    if (str_contains($ocrText, $token)) return true;

// Fuzzy fallback: slide a window of OCR words the same length as the
    // token and see if any window is a close-enough match (handles
    // misreads without being so loose it matches unrelated names).
    $tokenWords = explode(' ', $token);
    $ocrWords = explode(' ', $ocrText);
    $winLen = count($tokenWords);

    for ($i = 0; $i <= count($ocrWords) - $winLen; $i++) {
        $window = implode(' ', array_slice($ocrWords, $i, $winLen));
        $maxLen = max(strlen($token), strlen($window));
        if ($maxLen === 0) continue;
        similar_text($token, $window, $pct);
        if ($pct >= 80.0) return true;
    }

    return false;
}