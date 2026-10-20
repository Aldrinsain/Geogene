<?php
/**
 * GeoGene - ID verification library
 * ---------------------------------
 * Tanang logic sa pag-verify sa ID naa diri (walay output, walay session),
 * aron masulayan ug maayo. Gigamit sa complete-verification.php.
 *
 * Unsa ang gi-check:
 *   1. Dili School ID / SPECIMEN / SAMPLE / photocopy
 *   2. Anong klase nga government ID (PhilID/ePhilID, Driver's License, PhilHealth,
 *      UMID, Passport, ug uban pa) - pinaagi sa mga printed nga keywords
 *   3. Ang ID NUMBER kay sakto ang format sa maong klase sa ID (ang peke
 *      kasagaran sayop ang number)
 *   4. Ang birthdate mabasa, ug dili expired ang ID
 *   5. Ang NGALAN sa ID naa sa MCRO records (first + last name, duol sa usag usa)
 *   6. Ang birthdate sa ID motugma sa birth record sa MCRO (kung naay birth record)
 *   7. Dili gigamit ang parehas nga ID number sa laing tawo
 */

require_once __DIR__ . '/../config/id-verification.php';

// =====================================================================
// TEXT NORMALIZATION
// =====================================================================

/** Uppercase, tangtanga ang accents (Ñ -> N), letters ra ug spaces. */
function normalize_for_match(string $s): string
{
    $s = strtr($s, ['Ñ' => 'N', 'ñ' => 'n']);
    $s = strtoupper($s);
    $s = preg_replace('/[^A-Z\s]/', ' ', $s);
    $s = preg_replace('/\s+/', ' ', $s);
    return trim($s);
}

/**
 * Gihimong tokens (words) ang OCR text para sa name matching.
 * Ang words nga naay letters UG numbers (pananglit "J0SE") gi-ayo ang numbers
 * ngadto sa letters (0->O, 1->I, 5->S, 8->B). Ang puro numbers gitangtang.
 */
function ocr_tokens(string $ocrText): array
{
    $ocrText = strtr($ocrText, ['Ñ' => 'N', 'ñ' => 'n']);
    $ocrText = strtoupper($ocrText);
    $raw = preg_split('/[^A-Z0-9]+/', $ocrText, -1, PREG_SPLIT_NO_EMPTY);
    $out = [];
    foreach ($raw as $w) {
        $hasLetter = preg_match('/[A-Z]/', $w);
        $hasDigit  = preg_match('/[0-9]/', $w);
        if (!$hasLetter) continue;                 // puro numbers: dili name
        if ($hasDigit) $w = strtr($w, ['0' => 'O', '1' => 'I', '5' => 'S', '8' => 'B', '2' => 'Z', '6' => 'G']);
        $out[] = $w;
    }
    return $out;
}

/** Duol ba kaayo ang duha ka words? (tolerant sa gamay nga OCR misread) */
function word_close(string $a, string $b): bool
{
    if ($a === $b) return true;
    $len = max(strlen($a), strlen($b));
    if (abs(strlen($a) - strlen($b)) > 2) return false;
    $allowed = $len <= 4 ? 0 : ($len <= 7 ? 1 : 2);
    if ($allowed === 0) return false;
    return levenshtein($a, $b) <= $allowed;
}

/**
 * Pangitaon ang tanang positions sa tokens nga motugma sa usa ka word.
 * @return int[]
 */
function word_positions(string $word, array $tokens): array
{
    $pos = [];
    foreach ($tokens as $i => $t) {
        if ($t === $word || word_close($word, $t)) $pos[] = $i;
    }
    return $pos;
}

/**
 * Naa ba ang tibuok name field (usa o daghang words) sa tokens?
 * @return int[]|null  positions sa tanang words, o null kung naay wala makita
 */
function name_field_positions(string $field, array $tokens): ?array
{
    $words = array_values(array_filter(explode(' ', normalize_for_match($field)), fn($w) => strlen($w) > 1));
    if (!$words) return null;
    $all = [];
    foreach ($words as $w) {
        $p = word_positions($w, $tokens);
        if (!$p) return null;
        $all = array_merge($all, $p);
    }
    return $all;
}

/**
 * Motugma ba ang ngalan sa record sa OCR text sa ID?
 *  - first name (tanang words) ug last name (tanang words) kinahanglan makita
 *  - ang first ug last name kinahanglan duol sa usag usa (dili nagkalayo sa text)
 *  - middle name: bonus ra (gi-abbreviate o wala sa daghang ID)
 * @return array{ok:bool, middle_ok:bool}
 */
function name_matches_ocr(string $first, ?string $middle, string $last, string $ocrText): array
{
    $tokens = ocr_tokens($ocrText);
    $res = ['ok' => false, 'middle_ok' => false];
    if (!$tokens) return $res;

    $fp = name_field_positions($first, $tokens);
    $lp = name_field_positions($last, $tokens);
    if ($fp === null || $lp === null) return $res;

    $near = false;
    foreach ($fp as $a) {
        foreach ($lp as $b) {
            if (abs($a - $b) <= ID_VERIFY_NAME_WINDOW) { $near = true; break 2; }
        }
    }
    if (!$near) return $res;
    $res['ok'] = true;

    $middle = trim((string) $middle);
    if ($middle !== '') {
        $mp = name_field_positions($middle, $tokens);
        if ($mp !== null) {
            $res['middle_ok'] = true;
        } else {
            // initial lang (pananglit "S." o "S")
            $initial = substr(normalize_for_match($middle), 0, 1);
            foreach ($tokens as $t) {
                if (strlen($t) === 1 && $t === $initial) { $res['middle_ok'] = true; break; }
            }
        }
    }
    return $res;
}

// =====================================================================
// DOCUMENT CLASSIFICATION
// =====================================================================

const SCHOOL_ID_KEYWORDS = [
    'STUDENT ID', 'SCHOOL ID', 'STUDENT NO', 'STUDENT NUMBER',
    'UNIVERSITY', 'COLLEGE OF', 'STATE COLLEGE', 'INSTITUTE OF TECHNOLOGY',
    'ACADEMY', 'SCHOOL YEAR', 'ENROLLMENT', 'REGISTRAR',
    'ELEMENTARY SCHOOL', 'SENIOR HIGH SCHOOL', 'JUNIOR HIGH SCHOOL',
    'DEPARTMENT OF EDUCATION', 'DEPED', 'CHED', 'TESDA', 'COURSE AND YEAR',
    'GUARDIAN', 'PARENT NAME',
];

/** Mga word nga nagpasabot nga sample/copy/peke ang dokumento. Exact word lang. */
const FAKE_MARKER_WORDS = ['SPECIMEN', 'SAMPLE', 'FAKE', 'PHOTOCOPY', 'XEROX', 'TEMPLATE', 'MOCKUP'];

/**
 * Definition sa matag ID type.
 *  specific = keywords nga mao ra ang naa niana nga ID
 *  common   = keywords nga makita sa daghang ID (di ra mag-isa makapasar)
 *  min      = minimum nga total keyword hits (specific + common)
 *  number   = unsaon pagkuha sa ID number (['digits'=>N] = N ka digit; ['regex'=>[...]] = letter formats)
 *  strong   = kung true, ang ID number o ang birthdate kinahanglan mabasa
 */
function id_type_definitions(): array
{
    $common = ['REPUBLIC OF THE PHILIPPINES', 'REPUBLIKA NG PILIPINAS'];
    return [
        'philsys' => [
            'label'    => 'PhilID / ePhilID (National ID)',
            'specific' => ['PHILIPPINE IDENTIFICATION', 'PAMBANSANG PAGKAKAKILANLAN', 'PAGKAKAKILANLAN', 'PHILSYS', 'EPHILID', 'PHILID',
                           'PHILIPPINE STATISTICS AUTHORITY', 'PHILIPPINE IDENTIFICATION SYSTEM',
                           'APELYIDO', 'MGA PANGALAN', 'GITNANG APELYIDO', 'PETSA NG KAPANGANAKAN', 'GIVEN NAMES', 'PCN'],
            'common'   => array_merge($common, ['LAST NAME', 'MIDDLE NAME', 'DATE OF BIRTH', 'SUFFIX', 'TIRAHAN', 'ADDRESS']),
            'min'      => 2, 'strong' => true,
            'number'   => ['digits' => 16],                               // PCN: 5185-3198-6136-7052
        ],
        'drivers_license' => [
            'label'    => "Driver's License / Student Permit",
            'specific' => ['DRIVERS LICENSE', 'DRIVER LICENSE', 'LAND TRANSPORTATION OFFICE', 'DEPARTMENT OF TRANSPORTATION',
                           'DEPARTMENT OF TRANSPORTATION AND COMMUNICATIONS', 'LICENSE NO', 'NON PROFESSIONAL', 'STUDENT PERMIT',
                           'PERMIT NO', 'SIGNATURE OF LICENSEE', 'AGENCY CODE', 'AGY CODE', 'RESTRICTIONS', 'CONDITIONS'],
            'common'   => array_merge($common, ['PROFESSIONAL', 'EXPIRATION DATE', 'EXPIRES', 'BLOOD TYPE', 'EYES COLOR', 'NATIONALITY',
                                                'DATE OF BIRTH', 'LAST NAME FIRST NAME MIDDLE NAME', 'ADDRESS', 'VALID UNTIL', 'DATE ISSUED']),
            'min'      => 2, 'strong' => true,
            'number'   => ['regex' => [
                '/(?<![A-Z0-9])([A-Z0][0-9OISBL]{2})[\s\-]([0-9OISBL]{2})[\s\-]([0-9OISBL]{6})(?![A-Z0-9])/',   // N01-23-456789  (license)
                '/(?<![A-Z0-9])([0-9OISBL]{3})[\s\-]([0-9OISBL]{2})[\s\-]([0-9OISBL]{6})(?![A-Z0-9])/',         // 123-12-123456  (student permit)
                '/(?<![A-Z0-9])([A-Z][0-9OISBL]{2})([0-9OISBL]{2})([0-9OISBL]{6})(?![A-Z0-9])/',                // N0123456789   (dashes lost)
            ]],
        ],
        'philhealth' => [
            'label'    => 'PhilHealth ID',
            'specific' => ['PHILHEALTH', 'PHIL HEALTH', 'PHILIPPINE HEALTH INSURANCE', 'PHILIPPINE HEALTH INSURANCE CORPORATION', 'HEALTH INSURANCE',
                           'PHILHEALTH IDENTIFICATION', 'PHILHEALTH ID', 'PARTNER IN HEALTH', 'YOUR PARTNER IN HEALTH',
                           'INFORMAL ECONOMY', 'FORMAL ECONOMY', 'INDIRECT CONTRIBUTOR', 'DIRECT CONTRIBUTOR', 'POINT OF SERVICE', 'LIFETIME MEMBER'],
            'common'   => array_merge($common, ['PIN', 'MEMBER', 'DATE OF BIRTH', 'MALE', 'FEMALE', 'SPONSORED']),
            'min'      => 2, 'strong' => true,
            'number'   => ['digits' => 12],                               // PIN: 12-345678901-2  (or 1 2 3 4 5 6 ...)
        ],
        'umid' => [
            'label'    => 'UMID',
            'specific' => ['UMID', 'UNIFIED MULTI PURPOSE', 'UNIFIED MULTI PURPOSE ID', 'SOCIAL SECURITY SYSTEM', 'GSIS', 'GOVERNMENT SERVICE INSURANCE'],
            'common'   => array_merge($common, ['CRN']),
            'min'      => 2, 'strong' => true,
            'number'   => ['digits' => 12],                               // CRN
        ],
        'passport' => [
            'label'    => 'Passport',
            'specific' => ['PASSPORT', 'PASAPORTE', 'DEPARTMENT OF FOREIGN AFFAIRS'],
            'common'   => $common,
            'min'      => 2, 'strong' => true,
            'number'   => ['regex' => ['/(?<![A-Z0-9])([A-Z])([0-9OISBL]{7})([A-Z])(?![A-Z0-9])/']],       // P1234567A
        ],
        // ---- Ubang government IDs: walay fixed nga number format, mao nga daghan nga keyword ang gi-require ----
        'postal' => [
            'label' => 'Postal ID', 'specific' => ['POSTAL IDENTITY CARD', 'POSTAL ID', 'PHLPOST', 'PHILIPPINE POSTAL CORPORATION'],
            'common' => $common, 'min' => 2, 'strong' => false, 'number' => null,
        ],
        'voters' => [
            'label' => "Voter's ID", 'specific' => ['COMMISSION ON ELECTIONS', 'COMELEC', 'VOTERS ID', 'VOTER ID', 'VOTERS IDENTIFICATION'],
            'common' => $common, 'min' => 2, 'strong' => false, 'number' => null,
        ],
        'prc' => [
            'label' => 'PRC ID', 'specific' => ['PROFESSIONAL REGULATION COMMISSION', 'PRC'],
            'common' => $common, 'min' => 2, 'strong' => false, 'number' => null,
        ],
        'tin' => [
            'label' => 'TIN ID', 'specific' => ['BUREAU OF INTERNAL REVENUE', 'TAXPAYER IDENTIFICATION', 'TIN'],
            'common' => $common, 'min' => 2, 'strong' => false, 'number' => null,
        ],
        'pagibig' => [
            'label' => 'Pag-IBIG ID', 'specific' => ['PAG IBIG', 'HOME DEVELOPMENT MUTUAL FUND'],
            'common' => $common, 'min' => 2, 'strong' => false, 'number' => null,
        ],
        'barangay' => [
            'label' => 'Barangay ID', 'specific' => ['BARANGAY ID', 'BARANGAY CERTIFICATION', 'OFFICE OF THE PUNONG BARANGAY', 'PUNONG BARANGAY'],
            'common' => array_merge($common, ['MUNICIPALITY', 'PROVINCE']), 'min' => 2, 'strong' => false, 'number' => null,
        ],
        'senior_pwd' => [
            'label' => 'Senior Citizen / PWD ID', 'specific' => ['SENIOR CITIZEN', 'PERSON WITH DISABILITY', 'OSCA', 'PDAO', 'NCDA'],
            'common' => $common, 'min' => 2, 'strong' => false, 'number' => null,
        ],
        'nbi_pnp_afp' => [
            'label' => 'NBI / PNP / AFP ID', 'specific' => ['NATIONAL BUREAU OF INVESTIGATION', 'NBI CLEARANCE', 'PHILIPPINE NATIONAL POLICE', 'ARMED FORCES OF THE PHILIPPINES'],
            'common' => $common, 'min' => 2, 'strong' => false, 'number' => null,
        ],
    ];
}

function keyword_found(string $keyword, string $ocrNorm, array $tokens): bool
{
    $kwNorm = normalize_for_match($keyword);
    if ($kwNorm === '') return false;

    // Short acronyms / single words: exact word match ra (walay fuzzy, para dili false positive)
    if (!str_contains($kwNorm, ' ')) {
        if (strlen($kwNorm) <= 5) return in_array($kwNorm, $tokens, true);
        if (word_positions($kwNorm, $tokens) !== []) return true;
        // OCR may split the word in two ("PHIL HEALTH", "PAGKAKAKILAN LANAN")
        return window_close($kwNorm, $tokens);
    }
    // Phrase: exact substring
    if (str_contains(' ' . $ocrNorm . ' ', ' ' . $kwNorm . ' ')) return true;

    // Phrase: every word present and close together
    $words = explode(' ', $kwNorm);
    $positions = [];
    $allWords = true;
    foreach ($words as $w) {
        $p = word_positions($w, $tokens);
        if (!$p) { $allWords = false; break; }
        $positions[] = $p;
    }
    if ($allWords) {
        $window = count($words) + 4;
        foreach ($positions[0] as $start) {
            $ok = true;
            foreach ($positions as $plist) {
                $near = false;
                foreach ($plist as $p) { if (abs($p - $start) <= $window) { $near = true; break; } }
                if (!$near) { $ok = false; break; }
            }
            if ($ok) return true;
        }
    }
    // Last resort: compare the phrase with every nearby run of OCR words, ignoring the spaces
    // (handles words that were split/merged/misread in several places, e.g. glossy cards)
    return window_close($kwNorm, $tokens);
}

/**
 * True kung naay hugpong sa OCR words nga duol kaayo (<= ~22% nga lahi) sa keyword,
 * dili tagda ang spaces. Gigamit para sa phrases nga gi-misread sa OCR.
 */
function window_close(string $kwNorm, array $tokens): bool
{
    $kw = str_replace(' ', '', $kwNorm);
    $len = strlen($kw);
    if ($len < 8 || $len > 240) return false;
    $allowed = (int) floor($len * 0.22);
    $words = substr_count($kwNorm, ' ') + 1;
    $n = count($tokens);
    for ($i = 0; $i < $n; $i++) {
        $acc = '';
        for ($j = $i; $j < $n && $j < $i + $words + 3; $j++) {
            $acc .= $tokens[$j];
            $l = strlen($acc);
            if ($l > $len + $allowed) break;
            if ($l < $len - $allowed) continue;
            if (levenshtein($acc, $kw) <= $allowed) return true;
        }
    }
    return false;
}

function classify_id_document(string $ocrText): array
{
    $norm   = normalize_for_match($ocrText);
    $tokens = ocr_tokens($ocrText);
    $fail = fn(string $reason, string $code) => ['ok' => false, 'reason' => $reason, 'code' => $code, 'type' => null, 'label' => null, 'strong' => false, 'hits' => 0];

    // 1) Sample / specimen / photocopy nga marka
    foreach (FAKE_MARKER_WORDS as $w) {
        if (in_array($w, $tokens, true)) {
            return $fail('The scanned item looks like a SAMPLE/SPECIMEN or a photocopy. Only the original, actual ID is accepted.', 'sample');
        }
    }

    // 2) School ID
    foreach (SCHOOL_ID_KEYWORDS as $kw) {
        if (keyword_found($kw, $norm, $tokens)) {
            return $fail("School IDs are not accepted. Please use a valid GOVERNMENT-issued ID (for example: PhilID/ePhilID, Driver's License, PhilHealth, UMID, Passport, Postal ID, Voter's ID, or Barangay ID).", 'school');
        }
    }

    // 3) Pangitaa ang pinaka-motugma nga ID type
    $best = null;
    foreach (id_type_definitions() as $key => $def) {
        $spec = 0; $com = 0;
        foreach ($def['specific'] as $kw) if (keyword_found($kw, $norm, $tokens)) $spec++;
        foreach ($def['common'] as $kw)   if (keyword_found($kw, $norm, $tokens)) $com++;

        // Dugang ebidensya: ang ID number nga naay ESPESIPIKONG porma (naay dash/gaps)
        // pananglit 5185-3198-6136-7052 (PhilID), N01-12-123456 (License), 12-345678901-2 (PhilHealth)
        if (!empty($def['number'])) {
            foreach (id_number_candidates($key, $ocrText) as $cand) {
                if ($cand['formatted']) { $spec++; break; }
            }
        }

        if ($spec < 1 || ($spec + $com) < $def['min']) continue;
        $score = $spec * 10 + $com;
        if ($best === null || $score > $best['score']) {
            $best = ['score' => $score, 'type' => $key, 'label' => $def['label'], 'strong' => $def['strong'], 'hits' => $spec + $com];
        }
    }

    if ($best === null) {
        return $fail("We could not confirm that this is a valid government-issued ID. Make sure the whole ID is clear in the photo (PhilID/ePhilID, Driver's License, PhilHealth, UMID, Passport, Postal ID, Voter's ID, or Barangay ID).", 'unknown');
    }
    return ['ok' => true, 'reason' => null, 'code' => 'ok'] + $best;
}

// =====================================================================
// ID NUMBER
// =====================================================================

/**
 * Tanang posible nga ID number sa text para sa maong type, gi-ranggo (pinakamaayo una).
 * Gi-tolerate ang OCR digit misreads (O->0, I/L->1, S->5, B->8) ug ang numbers nga
 * gi-space-an (pananglit "1 5 0 2 5 6 9 7 6 9 5 3" sa PhilHealth).
 * @return array<int,array{value:string,formatted:bool,score:float}>
 */
function id_number_candidates(string $type, string $ocrText): array
{
    $defs = id_type_definitions();
    $cfg = $defs[$type]['number'] ?? null;
    if (!$cfg) return [];

    $text = strtoupper($ocrText);
    $digitFix = ['O' => '0', 'I' => '1', 'L' => '1', 'S' => '5', 'B' => '8'];
    $out = [];

    if (!empty($cfg['digits'])) {
        $n = (int) $cfg['digits'];
        $re = '/(?<![0-9OISBL])((?:[0-9OISBL][ \-.]?){' . ($n - 1) . '}[0-9OISBL])(?![0-9OISBL])/';
        if (preg_match_all($re, $text, $m)) {
            foreach ($m[1] as $raw) {
                $chars = preg_replace('/[ \-.]/', '', $raw);
                if (strlen($chars) !== $n) continue;
                $real = preg_match_all('/[0-9]/', $chars);
                if ($real < (int) ceil($n * 0.75)) continue;                  // mga letra ra nga murag digits
                $value = strtr($chars, $digitFix);
                if (count(array_unique(str_split($value))) < 3) continue;     // 0000000... / 1111...
                $formatted = (bool) preg_match('/[0-9OISBL]{2,}[ \-][0-9OISBL]{2,}/', $raw);   // groups, dili single spaced digits
                $out[] = ['value' => $value, 'formatted' => $formatted, 'score' => $real / $n + ($formatted ? 0.5 : 0)];
            }
        }
    }

    foreach (($cfg['regex'] ?? []) as $ri => $re) {
        if (!preg_match_all($re, $text, $m, PREG_SET_ORDER)) continue;
        foreach ($m as $hit) {
            $parts = array_slice($hit, 1);
            $canon = '';
            foreach ($parts as $i => $p) {
                if ($type === 'passport' && ($i === 0 || $i === 2)) { $canon .= $p; continue; }
                if ($type === 'drivers_license' && $ri !== 1 && $i === 0) {          // letter + 2 digits
                    $first = $p[0] === '0' ? 'O' : $p[0];
                    $canon .= $first . strtr(substr($p, 1), $digitFix);
                    continue;
                }
                $canon .= strtr($p, $digitFix);
            }
            $digitsOnly = preg_replace('/[^0-9]/', '', $canon);
            if ($digitsOnly === '' || count(array_unique(str_split($digitsOnly))) < 3) continue;
            $dashed = (bool) preg_match('/[\s\-]/', $hit[0]);
            $out[] = ['value' => $canon, 'formatted' => $dashed && $ri < 2, 'score' => ($ri === 0 ? 2.0 : ($ri === 1 ? 1.5 : 1.0)) + ($dashed ? 0.5 : 0)];
        }
    }

    usort($out, fn($a, $b) => $b['score'] <=> $a['score']);
    return $out;
}

/**
 * Ang pinakamaayong ID number base sa format sa type, o null.
 * @return string|null  canonical (letters+digits, walay spaces/dash)
 */
function extract_id_number(string $type, string $ocrText): ?string
{
    $c = id_number_candidates($type, $ocrText);
    return $c ? $c[0]['value'] : null;
}

// =====================================================================
// DATES
// =====================================================================

/**
 * Kuhaon ang tanang petsa sa text.
 * @return array<int, array{pos:int, dates:string[]}>  dates = posibleng Y-m-d (2 kung dili klaro ang d/m order)
 */
function extract_all_dates(string $text): array
{
    $now = (int) date('Y');
    $months = ['JAN' => 1, 'FEB' => 2, 'MAR' => 3, 'APR' => 4, 'MAY' => 5, 'JUN' => 6,
               'JUL' => 7, 'AUG' => 8, 'SEP' => 9, 'OCT' => 10, 'NOV' => 11, 'DEC' => 12];
    $monRe = 'JAN(?:UARY)?|FEB(?:RUARY)?|MAR(?:CH)?|APR(?:IL)?|MAY|JUN(?:E)?|JUL(?:Y)?|AUG(?:UST)?|SEP(?:T(?:EMBER)?)?|OCT(?:OBER)?|NOV(?:EMBER)?|DEC(?:EMBER)?';
    $T = strtoupper($text);
    $found = [];
    $valid = function (int $y, int $m, int $d) use ($now): ?string {
        if ($m < 1 || $m > 12 || $d < 1 || $d > 31 || $y < 1900 || $y > $now + 25) return null;
        return checkdate($m, $d, $y) ? sprintf('%04d-%02d-%02d', $y, $m, $d) : null;
    };

    // YYYY/MM/DD
    if (preg_match_all('/\b(\d{4})[\/\-.](\d{1,2})[\/\-.](\d{1,2})\b/', $T, $mm, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
        foreach ($mm as $x) {
            $d = $valid((int)$x[1][0], (int)$x[2][0], (int)$x[3][0]);
            if ($d) $found[] = ['pos' => $x[0][1], 'dates' => [$d]];
        }
    }
    // DD/MM/YYYY o MM/DD/YYYY
    if (preg_match_all('/\b(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})\b/', $T, $mm, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
        foreach ($mm as $x) {
            $a = (int)$x[1][0]; $b = (int)$x[2][0]; $y = (int)$x[3][0];
            $c = [];
            if ($a > 12)      $c[] = $valid($y, $b, $a);
            elseif ($b > 12)  $c[] = $valid($y, $a, $b);
            else { $c[] = $valid($y, $a, $b); if ($a !== $b) $c[] = $valid($y, $b, $a); }
            $c = array_values(array_filter($c));
            if ($c) $found[] = ['pos' => $x[0][1], 'dates' => $c];
        }
    }
    // JANUARY 15, 1998
    if (preg_match_all('/\b(' . $monRe . ')[A-Z]*\.?\s+(\d{1,2})\s*,?\s+(\d{4})\b/', $T, $mm, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
        foreach ($mm as $x) {
            $d = $valid((int)$x[3][0], $months[substr($x[1][0], 0, 3)], (int)$x[2][0]);
            if ($d) $found[] = ['pos' => $x[0][1], 'dates' => [$d]];
        }
    }
    // 15 JANUARY 1998
    if (preg_match_all('/\b(\d{1,2})\s+(' . $monRe . ')[A-Z]*\.?,?\s+(\d{4})\b/', $T, $mm, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
        foreach ($mm as $x) {
            $d = $valid((int)$x[3][0], $months[substr($x[2][0], 0, 3)], (int)$x[1][0]);
            if ($d) $found[] = ['pos' => $x[0][1], 'dates' => [$d]];
        }
    }
    usort($found, fn($a, $b) => $a['pos'] <=> $b['pos']);
    return $found;
}

/**
 * Kuhaon ang BIRTHDATE sa ID. Dili na lang "unang petsa" - kay ang ID naay
 * issue date ug expiry date pud. Prayoridad:
 *   1) petsa nga naa duol sa "BIRTH" / "KAPANGANAKAN" / "DOB" label
 *   2) kung walay label: ang PINAKA-ANTIGO nga petsa (birthdate kanunay ang pinaka-una)
 * @return string[]  posibleng birthdates (Y-m-d), blanko kung wala
 */
function extract_birth_dates(string $ocrText): array
{
    $dates = extract_all_dates($ocrText);
    if (!$dates) return [];
    $T = strtoupper($ocrText);
    $today = date('Y-m-d');

    // Ayaw apila ang expiry/issue dates
    $birthCandidates = [];
    foreach ($dates as $d) {
        $before = substr($T, max(0, $d['pos'] - 60), min(60, $d['pos']));
        if (preg_match('/EXPIR|VALID\s+UNTIL|ISSUE|ISSUED|DATE\s+OF\s+ISSUE|PETSA\s+NG\s+PAGKAKA/', $before)
            && !preg_match('/BIRTH|KAPANGANAKAN|DOB/', substr($before, -35))) {
            continue;
        }
        $birthCandidates[] = $d;
    }
    if (!$birthCandidates) return [];

    foreach ($birthCandidates as $d) {
        $before = substr($T, max(0, $d['pos'] - 45), min(45, $d['pos']));
        if (preg_match('/BIRTH|BIRTHDAY|KAPANGANAKAN|DOB|D\.O\.B/', $before)) {
            return array_values(array_filter($d['dates'], fn($x) => $x < $today));
        }
    }
    usort($birthCandidates, fn($a, $b) => min($a['dates']) <=> min($b['dates']));
    return array_values(array_filter($birthCandidates[0]['dates'], fn($x) => $x < $today));
}

function extract_expiry_date(string $ocrText): ?string
{
    $T = strtoupper($ocrText);
    if (!preg_match_all('/EXPIR(?:ATION|ES|Y)?\s*(?:DATE)?|VALID\s+UNTIL|VALIDITY/', $T, $labels, PREG_OFFSET_CAPTURE)) return null;
    $dates = extract_all_dates($ocrText);
    foreach ($labels[0] as $lab) {
        $after = $lab[1] + strlen($lab[0]);
        foreach ($dates as $d) {
            $gap = $d['pos'] - $after;
            if ($gap >= -2 && $gap <= 45) return max($d['dates']);   // ambiguous d/m: hunahunaon nga mas layo (mas luag sa user)
        }
        // 2-digit year, e.g. "Valid until 11-05-20"
        if (preg_match('/^[\s:.\-]{0,12}(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{2})(?!\d)/', substr($T, $after, 40), $m)) {
            $yy = 2000 + (int) $m[3];
            $cands = [];
            foreach ([[(int) $m[1], (int) $m[2]], [(int) $m[2], (int) $m[1]]] as [$mo, $da]) {
                if ($mo >= 1 && $mo <= 12 && checkdate($mo, $da, $yy)) $cands[] = sprintf('%04d-%02d-%02d', $yy, $mo, $da);
            }
            if ($cands) return max($cands);
        }
    }
    return null;
}

// =====================================================================
// BARANGAY (auto-fill)
// =====================================================================

function extract_barangay(string $ocrText, PDO $pdo): ?string
{
    $norm = normalize_for_match($ocrText);
    if ($norm === '') return null;
    $barangays = $pdo->query("SELECT name FROM barangays")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($barangays as $bgy) {
        $bgyNorm = normalize_for_match($bgy);
        if ($bgyNorm !== '' && str_contains(' ' . $norm . ' ', ' ' . $bgyNorm . ' ')) return $bgy;
    }
    return null;
}

// =====================================================================
// MCRO RECORD MATCHING
// =====================================================================

function person_key(array $r): string
{
    return normalize_for_match(trim(($r['first_name'] ?? '') . ' ' . ($r['middle_name'] ?? '') . ' ' . ($r['last_name'] ?? '')));
}

/**
 * Pangitaon sa MCRO records ang tanang tawo nga ang ngalan makita sa ID.
 * Unya pilion ang usa:
 *   - Birthdate sa ID == birth record date -> prayoridad
 *   - Ang record nga gi-search sa user ($preferredId) kung kauban sa matches
 *   - Middle name nga motugma
 * Kung daghan gihapon nga lahi nga tawo ang parehas og ngalan -> 'ambiguous'.
 *
 * @return array{status:string, record:?array, dob_checked:bool, dob_ok:bool}
 *   status: 'matched' | 'not_found' | 'ambiguous' | 'dob_mismatch'
 */
function find_mcro_match(PDO $pdo, string $ocrText, ?int $preferredId, array $idDobs): array
{
    $none = ['status' => 'not_found', 'record' => null, 'dob_checked' => false, 'dob_ok' => false];
    $tokens = ocr_tokens($ocrText);
    if (!$tokens) return $none;

    $stmt = $pdo->query("SELECT record_id, registry_type, first_name, middle_name, last_name, date_of_event, barangay
                         FROM civil_registry_records
                         WHERE first_name <> '' AND last_name <> ''");

    $lastCache = [];   // normalized last name -> bool (para dili balik-balik ang levenshtein)
    $cands = [];
    while ($r = $stmt->fetch()) {
        $ln = normalize_for_match($r['last_name']);
        if ($ln === '') continue;
        if (!isset($lastCache[$ln])) $lastCache[$ln] = name_field_positions($r['last_name'], $tokens) !== null;
        if (!$lastCache[$ln]) continue;

        $m = name_matches_ocr($r['first_name'], $r['middle_name'], $r['last_name'], $ocrText);
        if ($m['ok']) { $r['middle_ok'] = $m['middle_ok']; $cands[] = $r; }
    }
    if (!$cands) return $none;

    // ---- 1) DOB sa ID vs birth records ----
    $dobChecked = false;
    if ($idDobs) {
        $birthRecs = array_filter($cands, fn($r) => $r['registry_type'] === 'birth' && !empty($r['date_of_event']));
        if ($birthRecs) {
            $dobChecked = true;
            $dobHits = array_values(array_filter($birthRecs, fn($r) => in_array($r['date_of_event'], $idDobs, true)));
            if ($dobHits) {
                // Ang birth records nga motugma sa DOB ra ang gamiton. Ang non-birth record
                // (death/marriage) sa parehas nga ngalan gi-apil lang kung klaro nga usa ra
                // ang tawo (walay lain nga birth record nga lahi og DOB ug parehas og ngalan).
                $hitKeys = array_unique(array_map('person_key', $dobHits));
                $ambiguousKeys = [];
                foreach ($hitKeys as $k) {
                    $dobsForKey = array_unique(array_map(
                        fn($r) => $r['date_of_event'],
                        array_filter($birthRecs, fn($r) => person_key($r) === $k)
                    ));
                    if (count($dobsForKey) > 1) $ambiguousKeys[] = $k;
                }
                $others = array_filter($cands, fn($r) => $r['registry_type'] !== 'birth'
                    && in_array(person_key($r), $hitKeys, true)
                    && !in_array(person_key($r), $ambiguousKeys, true));
                $cands = array_values(array_merge($dobHits, $others));
            } else {
                // naay birth record nga parehas og ngalan, pero lahi ang birthdate -> dili ni siya
                return ['status' => 'dob_mismatch', 'record' => null, 'dob_checked' => true, 'dob_ok' => false];
            }
        }
    }

    // ---- 2) pilia ang usa ----
    if ($preferredId) {
        foreach ($cands as $r) {
            if ((int) $r['record_id'] === $preferredId) return ['status' => 'matched', 'record' => $r, 'dob_checked' => $dobChecked, 'dob_ok' => $dobChecked];
        }
    }
    $withMiddle = array_values(array_filter($cands, fn($r) => $r['middle_ok']));
    if ($withMiddle) $cands = $withMiddle;

    $keys = array_unique(array_map('person_key', $cands));
    if (count($keys) > 1 && !$dobChecked) {
        return ['status' => 'ambiguous', 'record' => null, 'dob_checked' => false, 'dob_ok' => false];
    }
    // prefer ang birth record
    usort($cands, fn($a, $b) => ($a['registry_type'] === 'birth' ? 0 : 1) <=> ($b['registry_type'] === 'birth' ? 0 : 1));
    return ['status' => 'matched', 'record' => $cands[0], 'dob_checked' => $dobChecked, 'dob_ok' => $dobChecked];
}

// =====================================================================
// IMAGE CHECKS (GD) + SERVER-SIDE OCR
// =====================================================================

/** Decode ang data-URL/base64 JPEG/PNG. Return binary o null. */
function decode_image_payload(string $payload): ?string
{
    if ($payload === '') return null;
    if (preg_match('#^data:image/(jpeg|jpg|png);base64,#i', $payload, $m)) {
        $payload = substr($payload, strlen($m[0]));
    }
    $bin = base64_decode($payload, true);
    if ($bin === false || strlen($bin) < 2000 || strlen($bin) > 8 * 1024 * 1024) return null;
    $isJpeg = substr($bin, 0, 3) === "\xFF\xD8\xFF";
    $isPng  = substr($bin, 0, 8) === "\x89PNG\r\n\x1a\n";
    return ($isJpeg || $isPng) ? $bin : null;
}

/**
 * Basic image quality: gidak-on ug sharpness (Laplacian variance).
 * @return array{ok:bool, width:int, height:int, sharpness:?float, reason:?string}
 */
function check_image_quality(string $bin): array
{
    $info = @getimagesizefromstring($bin);
    if (!$info) return ['ok' => false, 'width' => 0, 'height' => 0, 'sharpness' => null, 'reason' => 'The photo that was sent is not valid.'];
    [$w, $h] = $info;
    if ($w < ID_VERIFY_MIN_WIDTH || $h < 300) {
        return ['ok' => false, 'width' => $w, 'height' => $h, 'sharpness' => null,
                'reason' => 'The photo resolution is too low. Move the ID a little closer to the camera.'];
    }
    if (!function_exists('imagecreatefromstring')) {
        return ['ok' => true, 'width' => $w, 'height' => $h, 'sharpness' => null, 'reason' => null]; // walay GD, skip sharpness
    }
    $img = @imagecreatefromstring($bin);
    if (!$img) return ['ok' => true, 'width' => $w, 'height' => $h, 'sharpness' => null, 'reason' => null];

    $tw = 480; $th = max(1, (int) round($h * $tw / $w));
    $small = imagecreatetruecolor($tw, $th);
    imagecopyresampled($small, $img, 0, 0, 0, 0, $tw, $th, $w, $h);
    imagefilter($small, IMG_FILTER_GRAYSCALE);

    $g = [];
    for ($y = 0; $y < $th; $y++) for ($x = 0; $x < $tw; $x++) $g[$y][$x] = imagecolorat($small, $x, $y) & 0xFF;
    $vals = []; $sum = 0;
    for ($y = 1; $y < $th - 1; $y++) {
        for ($x = 1; $x < $tw - 1; $x++) {
            $lap = -4 * $g[$y][$x] + $g[$y - 1][$x] + $g[$y + 1][$x] + $g[$y][$x - 1] + $g[$y][$x + 1];
            $vals[] = $lap; $sum += $lap;
        }
    }
    $n = max(1, count($vals)); $mean = $sum / $n; $var = 0;
    foreach ($vals as $v) $var += ($v - $mean) ** 2;
    $var /= $n;
    imagedestroy($img); imagedestroy($small);

    if ($var < ID_VERIFY_MIN_SHARPNESS) {
        return ['ok' => false, 'width' => $w, 'height' => $h, 'sharpness' => $var,
                'reason' => 'The ID photo is blurry. Please focus and retake it in better lighting.'];
    }
    return ['ok' => true, 'width' => $w, 'height' => $h, 'sharpness' => $var, 'reason' => null];
}

/** Hanapon ang Tesseract binary sa server. Return path o null. */
function find_tesseract(): ?string
{
    if (!function_exists('exec')) return null;
    $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
    if (in_array('exec', $disabled, true)) return null;

    $candidates = [];
    if (ID_VERIFY_TESSERACT !== '') $candidates[] = ID_VERIFY_TESSERACT;
    $candidates = array_merge($candidates, [
        'C:\\Program Files\\Tesseract-OCR\\tesseract.exe',
        'C:\\Program Files (x86)\\Tesseract-OCR\\tesseract.exe',
        '/usr/bin/tesseract', '/usr/local/bin/tesseract', '/opt/homebrew/bin/tesseract',
    ]);
    foreach ($candidates as $c) if (@is_file($c)) return $c;

    // PATH
    $out = []; $code = 1;
    @exec(stripos(PHP_OS, 'WIN') === 0 ? 'where tesseract 2>NUL' : 'command -v tesseract 2>/dev/null', $out, $code);
    if ($code === 0 && !empty($out[0]) && @is_file(trim($out[0]))) return trim($out[0]);
    return null;
}

/**
 * Enhanced copies sa litrato para sa OCR. Ang lain-laing card mas maayo basahon sa lain-laing paagi
 * (puti nga ePhilID = grayscale, glossy nga berde nga PhilHealth = red channel / sharpen,
 * gamay nga license text = dako ug sharp).
 * @param string $mode 'gray' | 'sharp' | 'red'
 */
function enhance_for_ocr(string $bin, string $mode = 'gray'): ?string
{
    if (!function_exists('imagecreatefromstring')) return null;
    $img = @imagecreatefromstring($bin);
    if (!$img) return null;
    $w = imagesx($img); $h = imagesy($img);
    $tw = 2400; $th = (int) round($h * $tw / $w);
    $big = imagecreatetruecolor($tw, $th);
    imagecopyresampled($big, $img, 0, 0, 0, 0, $tw, $th, $w, $h);
    if ($mode === 'red') {
        imagefilter($big, IMG_FILTER_COLORIZE, 0, -255, -255);   // keep only the red channel
        imagefilter($big, IMG_FILTER_GRAYSCALE);
        imagefilter($big, IMG_FILTER_BRIGHTNESS, 70);
    } else {
        imagefilter($big, IMG_FILTER_GRAYSCALE);
    }
    imagefilter($big, IMG_FILTER_CONTRAST, -40);                  // negative = mas taas ang contrast sa GD
    if ($mode === 'sharp') {
        imageconvolution($big, [[-1, -1, -1], [-1, 16, -1], [-1, -1, -1]], 8, 0);
    }
    ob_start(); imagepng($big); $png = ob_get_clean();
    imagedestroy($img); imagedestroy($big);
    return $png ?: null;
}

/**
 * Basaha sa SERVER ang ID (para dili ma-fake sa browser). Daghang pass, ang tanang text gi-hiusa:
 * orihinal, enhanced gray, enhanced sharp, ug red channel.
 * @return string|null  text, o null kung walay Tesseract sa server
 */
function server_ocr(string $imageBin): ?string
{
    $tess = find_tesseract();
    if (!$tess) return null;

    $jobs = [[$imageBin, '3']];
    foreach ([['gray', '3'], ['sharp', '11'], ['red', '6']] as [$mode, $psm]) {
        $enh = enhance_for_ocr($imageBin, $mode);
        $jobs[] = [$enh ?: $imageBin, $psm];
    }

    $text = '';
    foreach ($jobs as [$bin, $psm]) {
        $tmp = tempnam(sys_get_temp_dir(), 'idscan_');
        if ($tmp === false) continue;
        $img = $tmp . '.img';
        @rename($tmp, $img);
        file_put_contents($img, $bin);
        $out = []; $code = 1;
        @exec(escapeshellarg($tess) . ' ' . escapeshellarg($img) . ' stdout -l eng --psm ' . $psm . ' 2>&1', $out, $code);
        @unlink($img);
        if ($code === 0) $text .= "\n" . implode("\n", $out);
    }
    return trim($text) !== '' ? $text : null;
}

// =====================================================================
// ID NUMBER HASH (anti-reuse)
// =====================================================================

function id_number_hash(string $type, string $number): string
{
    return hash_hmac('sha256', $type . '|' . $number, ID_VERIFY_PEPPER);
}
