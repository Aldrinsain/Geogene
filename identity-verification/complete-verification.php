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
/**
 * Compares a person's on-file name (first/middle/last) against OCR'd ID
 * text. Requires the first name AND last name to both be found (middle
 * name is often abbreviated, missing, or cut off on real IDs, so it's
 * treated as a bonus rather than a requirement).
 */
function name_matches_ocr(string $firstName, string $lastName, string $ocrText): bool
{
    $ocrNorm = normalize_for_match($ocrText);
    $firstNorm = normalize_for_match($firstName);
    $lastNorm = normalize_for_match($lastName);

    if ($firstNorm === '' || $lastNorm === '') return false;

    return token_found_in_text($firstNorm, $ocrNorm) && token_found_in_text($lastNorm, $ocrNorm);
}

/**
 * Keywords that typically appear on Philippine SCHOOL/STUDENT IDs. Finding
 * any of these is a hard reject, regardless of whether the name matches -
 * school IDs are not acceptable for identity verification.
 */
const SCHOOL_ID_KEYWORDS = [
    'STUDENT ID', 'SCHOOL ID', 'STUDENT NO', 'STUDENT NUMBER',
    'UNIVERSITY', 'COLLEGE OF', 'STATE COLLEGE', 'INSTITUTE OF TECHNOLOGY',
    'ACADEMY', 'SCHOOL YEAR', 'ENROLLMENT', 'REGISTRAR',
    'ELEMENTARY SCHOOL', 'SENIOR HIGH SCHOOL', 'JUNIOR HIGH SCHOOL',
    'DEPARTMENT OF EDUCATION', 'DEPED', 'CHED', 'TESDA', 'COURSE AND YEAR',
    'GUARDIAN', 'PARENT NAME',
];
/**
 * Keywords commonly found on valid Philippine GOVERNMENT-issued IDs.
 * Finding at least one of these gives us reasonable confidence the
 * document is a real government ID and not something else entirely.
 */
const GOVERNMENT_ID_KEYWORDS = [
    'REPUBLIC OF THE PHILIPPINES', 'PHILIPPINE IDENTIFICATION', 'PHILSYS',
    'PHILIPPINE STATISTICS AUTHORITY', 'DRIVERS LICENSE', "DRIVER'S LICENSE",
    'LAND TRANSPORTATION OFFICE', 'NON PROFESSIONAL', 'PROFESSIONAL DRIVER',
    'PASSPORT', 'DEPARTMENT OF FOREIGN AFFAIRS',
    'SOCIAL SECURITY SYSTEM', ' SSS ', 'GSIS', 'GOVERNMENT SERVICE INSURANCE',
    'PHILHEALTH', 'PHILIPPINE HEALTH INSURANCE',
    'COMMISSION ON ELECTIONS', 'COMELEC', 'VOTERS ID', 'VOTER ID',
    'POSTAL ID', 'PHILIPPINE POSTAL', 'PHLPOST',
    'SENIOR CITIZEN', 'PERSON WITH DISABILITY', ' PWD ',
    'UMID', 'UNIFIED MULTI PURPOSE ID',
    'PROFESSIONAL REGULATION COMMISSION', ' PRC ',
    'BUREAU OF INTERNAL REVENUE', 'TAXPAYER IDENTIFICATION',
    'NATIONAL BUREAU OF INVESTIGATION', 'NBI CLEARANCE',
    'BARANGAY CERTIFICATION', 'BARANGAY ID', 'OFFICE OF THE PUNONG BARANGAY',
    'PAG IBIG', 'HOME DEVELOPMENT MUTUAL FUND',
    'AFP', 'ARMED FORCES OF THE PHILIPPINES', 'PHILIPPINE NATIONAL POLICE',
];

/**
 * Classifies the scanned document as a school ID (reject), an
 * unrecognized/unclear document (reject, ask for a clearer government ID),
 * or an acceptable government-issued ID (pass).
 */
function classify_id_document(string $ocrText): array
{
    $norm = normalize_for_match($ocrText);
    $padded = ' ' . $norm . ' '; // so keyword edges like ' SSS ' can match cleanly

    foreach (SCHOOL_ID_KEYWORDS as $kw) {
        if (str_contains($padded, normalize_for_match($kw))) {
            return [
                'ok' => false,
                'reason' => 'Dili dawaton ang School ID. Palihug gamit og valid GOVERNMENT-issued ID (pananglit: PhilID/National ID, Driver\'s License, Passport, UMID, PhilHealth ID, Postal ID, Voter\'s ID, o Barangay ID).',
            ];
        }
    }

    $hasGovKeyword = false;
    foreach (GOVERNMENT_ID_KEYWORDS as $kw) {
        if (str_contains($padded, normalize_for_match($kw))) {
            $hasGovKeyword = true;
            break;
        }
    }

    if (!$hasGovKeyword) {
        return [
            'ok' => false,
            'reason' => 'Wala mi-confirm nga valid government-issued ID ni. Siguraduhon nga klaro ang litrato ug gamit og opisyal nga gobyerno ID (PhilID, Driver\'s License, Passport, UMID, PhilHealth, Postal ID, Voter\'s ID, o Barangay ID).',
        ];
    }

    return ['ok' => true, 'reason' => null];
}

/**
 * Best-effort extraction of a birth date from raw (un-normalized) OCR text.
 * Tries several common ID date formats and validates the result is a
 * plausible birth year before accepting it. Returns null if nothing
 * trustworthy is found - this is an auto-fill convenience, not a
 * requirement, so the person can always fix it later in Edit Profile.
 */
function extract_date_of_birth(string $ocrText): ?string
{
    $currentYear = (int) date('Y');
    $months = 'JAN(?:UARY)?|FEB(?:RUARY)?|MAR(?:CH)?|APR(?:IL)?|MAY|JUN(?:E)?|JUL(?:Y)?|AUG(?:UST)?|SEP(?:T(?:EMBER)?)?|OCT(?:OBER)?|NOV(?:EMBER)?|DEC(?:EMBER)?';

    $patterns = [
        '/\b\d{1,2}[\/\-.]\d{1,2}[\/\-.]\d{4}\b/',                            // 01/15/1998, 15-01-1998
        '/\b\d{4}[\/\-.]\d{1,2}[\/\-.]\d{1,2}\b/',                            // 1998-01-15
        '/\b(?:' . $months . ')[A-Z]*\.?\s+\d{1,2},?\s+\d{4}\b/i',            // JAN 15, 1998
        '/\b\d{1,2}\s+(?:' . $months . ')[A-Z]*\.?,?\s+\d{4}\b/i',           // 15 JAN 1998
    ];

    foreach ($patterns as $pattern) {
        if (!preg_match_all($pattern, $ocrText, $matches)) continue;

        foreach ($matches[0] as $candidate) {
            $ts = strtotime($candidate);
            if ($ts === false) continue;

            $year = (int) date('Y', $ts);
            if ($year < 1900 || $year > $currentYear) continue;

            return date('Y-m-d', $ts);
        }
    }

    return null;
}
/**
 * Best-effort extraction of the person's barangay by checking which known
 * barangay name (from the `barangays` table) appears in the OCR'd text.
 * Returns the properly-cased barangay name from the database, or null.
 */
function extract_barangay(string $ocrText, PDO $pdo): ?string
{
    $norm = normalize_for_match($ocrText);
    if ($norm === '') return null;

    $barangays = $pdo->query("SELECT name FROM barangays")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($barangays as $bgy) {
        $bgyNorm = normalize_for_match($bgy);
        if ($bgyNorm !== '' && str_contains($norm, $bgyNorm)) {
            return $bgy;
        }
    }

    return null;
}

$ocrText = $_POST['ocr_text'] ?? '';
if (trim($ocrText) === '') {
    echo json_encode(['success' => false, 'error' => 'Wala mi-basa og text gikan sa ID. Sulayi og laing litrato nga mas klaro.']);
    exit;
}