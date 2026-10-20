<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/id-lib.php';
require_once __DIR__ . '/../staff/includes/name-format.php';
header('Content-Type: application/json');

/**
 * GeoGene - ID verification endpoint
 *
 * Input (POST): id_image (data URL sa captured ID), ocr_text (gikan sa browser OCR)
 *
 * Verified LANG kung ang tanan niini pasar:
 *   1. Tinood nga government ID (dili school ID / sample / photocopy)
 *   2. Valid ang ID number format sa maong klase sa ID
 *   3. Mabasa ang birthdate ug dili expired
 *   4. Ang ngalan sa ID naa sa MCRO records
 *   5. Ang birthdate sa ID motugma sa birth record sa MCRO
 *   6. Ang ID number wala gigamit sa laing tawo
 */

function respond(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['success' => false, 'error' => 'Invalid request.'], 405);
}

// ---------- Walay session context ----------
$loggedInUserId = $_SESSION['user_id'] ?? null;
if (!$loggedInUserId && empty($_SESSION['matched_record_id'])) {
    respond(['success' => false, 'error' => 'No active verification session'], 401);
}

// ---------- Rate limit (batok sa pag-brute-force) ----------
// A "deep" request is the automatic closer look at the SAME photo (the browser reads it a few more
// ways after the first read could not confirm the ID). It is only allowed right after a normal
// request asked for it, and it is not counted as another attempt.
$now = time();
$deep = ($_POST['deep'] ?? '') === '1';
if ($deep) {
    $tokenAt = (int) ($_SESSION['id_deep_token'] ?? 0);
    unset($_SESSION['id_deep_token']);
    if ($tokenAt < $now - 180) $deep = false;        // not requested (or too old) -> treat as a normal attempt
}
$attempts = array_filter($_SESSION['id_scan_attempts'] ?? [], fn($t) => $t > $now - 900);
if (!$deep) {
    if (count($attempts) >= ID_VERIFY_MAX_ATTEMPTS) {
        respond(['success' => true, 'verified' => false,
                 'error' => 'Too many scan attempts. Please wait 15 minutes, then try again.']);
    }
    $attempts[] = $now;
    $_SESSION['id_scan_attempts'] = array_values($attempts);
}

$checks = [];   // [['label'=>..., 'ok'=>bool], ...] - ipakita sa user
$debug  = [];

/**
 * Fail response: i-mark nga wala na-verify (pre-registration) ug ibalik ang checks.
 * $readProblem = true when the reason is that the PHOTO could not be read well enough (not that the
 * ID is wrong): the browser is then asked to take a closer look once, and tips are shown.
 */
$fail = function (string $message, bool $readProblem = false) use (&$checks, &$debug, $loggedInUserId, $deep): void {
    if (!$loggedInUserId) $_SESSION['identity_scan_verified'] = false;
    $out = ['success' => true, 'verified' => false, 'error' => $message, 'checks' => $checks];
    if ($readProblem) {
        $out['tips'] = [
            'Put the ID flat on a plain, dark surface and fill the frame with it.',
            'Avoid glare and shadows - tilt the ID slightly or move away from the light.',
            'Hold your phone steady and wait for the camera to focus before tapping the shutter.',
            'Make sure the whole ID is visible, including the name and the date of birth.',
        ];
        if (!$deep) {
            $_SESSION['id_deep_token'] = time();
            $out['retry_ocr'] = true;
        }
    }
    if (ID_VERIFY_DEBUG) $out['debug'] = $debug;
    respond($out);
};

// ---------- 1) Litrato ----------
$imageBin = decode_image_payload($_POST['id_image'] ?? '');
if ($imageBin === null) {
    respond(['success' => false, 'error' => 'No clear photo was received. Please capture the ID again.']);
}
$quality = check_image_quality($imageBin);
$checks[] = ['label' => 'Photo is clear and large enough', 'ok' => $quality['ok']];
if (!$quality['ok']) $fail($quality['reason']);

// ---------- 2) Basahon ang ID: SERVER OCR kung naay Tesseract, kung wala browser OCR ----------
$serverText = server_ocr($imageBin);
$serverRead = $serverText !== null;
if ($serverRead) {
    $ocrText = $serverText;                               // dili ma-fake gikan sa browser
} else {
    if (ID_VERIFY_REQUIRE_SERVER_OCR) {
        respond(['success' => false, 'error' => 'Server OCR has not been set up yet. Please contact the administrator.']);
    }
    $ocrText = (string) ($_POST['ocr_text'] ?? '');
}
if (mb_strlen(trim(preg_replace('/[^A-Za-z0-9]/', '', $ocrText))) < 25) {
    $checks[] = ['label' => 'ID text could be read', 'ok' => false];
    $fail('Too little could be read from the ID. Please retake it in better lighting, with the whole ID in the frame and no glare.', true);
}
$checks[] = ['label' => 'ID text could be read', 'ok' => true];
$debug = ['source' => $serverRead ? 'server' : 'browser', 'ocr_text_preview' => mb_substr($ocrText, 0, 600)];

// ---------- 3) Klase sa ID ----------
$cls = classify_id_document($ocrText);
$checks[] = ['label' => $cls['ok'] ? ('Valid government ID: ' . $cls['label']) : 'Valid government ID', 'ok' => $cls['ok']];
if (!$cls['ok']) $fail($cls['reason'], $cls['code'] === 'unknown');   // school / sample are final; "unknown" may just be a hard-to-read photo
$idType = $cls['type'];
$debug['id_type'] = $idType;

// ---------- 4) ID number + 5) Birthdate ----------
// For the main government IDs, at least ONE of the two must be readable: a correct-format ID number
// OR the birthdate (which is then compared with the birth record in the MCRO). A phone camera often
// cannot read every small detail of a glossy card, so both are not forced.
$idNumber = extract_id_number($idType, $ocrText);
$idDobs = extract_birth_dates($ocrText);
$debug['dob_candidates'] = $idDobs;
if ($idNumber !== null) $checks[] = ['label' => 'ID number format is correct', 'ok' => true];
if (!empty($idDobs))    $checks[] = ['label' => 'Birthdate on the ID is readable', 'ok' => true];
if ($cls['strong'] && $idNumber === null && empty($idDobs)) {
    $checks[] = ['label' => 'ID number or birthdate is readable', 'ok' => false];
    $fail('We could not read the ID number or the birthdate on this ' . $cls['label'] . '. Please retake the photo so the text on the ID is sharp and readable.', true);
}
if ($cls['strong'] && ID_VERIFY_REQUIRE_NUMBER && $idNumber === null) {
    $checks[] = ['label' => 'ID number is readable and in the correct format', 'ok' => false];
    $fail('Could not read a valid ID number on this ' . $cls['label'] . '. Please retake the photo so the number is sharp and readable.', true);
}
if (ID_VERIFY_REQUIRE_DOB && empty($idDobs)) {
    $checks[] = ['label' => 'Birthdate on the ID is readable', 'ok' => false];
    $fail('The birthdate on the ID could not be read. Make sure the whole ID is visible and clear.', true);
}
$expiry = extract_expiry_date($ocrText);
if ($expiry !== null) {
    $notExpired = $expiry >= date('Y-m-d');
    $checks[] = ['label' => 'ID is not expired', 'ok' => $notExpired];
    if (!$notExpired) $fail('The ID has expired (' . $expiry . '). Please use an ID that is still valid.');
}

// ---------- 6) Context: logged-in user (re-scan) o pre-registration ----------
$accountUser = null;
$preferredRecordId = null;
if ($loggedInUserId) {
    $stmt = $pdo->prepare("SELECT full_name, date_of_birth, barangay, matched_record_id FROM users WHERE user_id = ?");
    $stmt->execute([$loggedInUserId]);
    $accountUser = $stmt->fetch();
    if (!$accountUser) respond(['success' => false, 'error' => 'Your account could not be found.'], 404);
    $preferredRecordId = $accountUser['matched_record_id'] ? (int) $accountUser['matched_record_id'] : null;

    // Ang ngalan sa account kinahanglan motugma sa ID (dili pwede ang ID sa laing tawo)
    $parts = preg_split('/\s+/', trim($accountUser['full_name']));
    $accFirst = $parts[0] ?? '';
    $accLast  = count($parts) > 1 ? end($parts) : '';
    $acc = name_matches_ocr($accFirst, null, $accLast, $ocrText);
    $checks[] = ['label' => 'Name on the ID matches your account', 'ok' => $acc['ok']];
    if (!$acc['ok']) $fail('The name on the ID does not match the name on your account (' . $accountUser['full_name'] . ').');
} else {
    $preferredRecordId = (int) $_SESSION['matched_record_id'];
}

// ---------- 7) Naa ba ang ngalan sa MCRO records? ----------
$match = find_mcro_match($pdo, $ocrText, $preferredRecordId, $idDobs);
$checks[] = ['label' => 'Name is found in the MCRO records', 'ok' => !in_array($match['status'], ['not_found'], true)];
if ($match['status'] === 'not_found') {
    // may also be a misread name -> let the browser take a closer look once before giving up
    $fail('The name on your ID was not found in the MCRO records. Please make sure the name is correct, or visit the Municipal Civil Registry Office (MCRO).', true);
}
if ($match['status'] === 'dob_mismatch') {
    $checks[] = ['label' => 'Birthdate matches the MCRO record', 'ok' => false];
    $fail('The birthdate on the ID does not match the birth record in the MCRO. Please check your ID, or contact the MCRO.');
}
if ($match['status'] === 'ambiguous') {
    $fail('Several people in the MCRO records have the same name, so we cannot tell which one you are. Please visit the MCRO to be verified in person.');
}
if ($match['dob_checked']) $checks[] = ['label' => 'Birthdate matches the MCRO record', 'ok' => true];
$record = $match['record'];
$debug['matched_record_id'] = (int) $record['record_id'];

// Logged-in user nga naay naka-link nga record: dapat parehas nga tawo
if ($loggedInUserId && $preferredRecordId && (int) $record['record_id'] !== $preferredRecordId) {
    $stmt = $pdo->prepare("SELECT first_name, middle_name, last_name FROM civil_registry_records WHERE record_id = ?");
    $stmt->execute([$preferredRecordId]);
    $linked = $stmt->fetch();
    if ($linked && person_key($linked) !== person_key($record)) {
        $checks[] = ['label' => 'Same as the MCRO record linked to your account', 'ok' => false];
        $fail('The ID does not match the MCRO record linked to your account.');
    }
    $record = ['record_id' => $preferredRecordId] + $record;   // gamiton ang orihinal nga link
}

// ---------- 8) Dili pwede gamiton ang parehas nga ID sa laing tawo ----------
$idHash = $idNumber !== null ? id_number_hash($idType, $idNumber) : null;
if ($idHash !== null) {
    try {
        $stmt = $pdo->prepare("SELECT matched_record_id FROM verified_id_numbers WHERE id_hash = ?");
        $stmt->execute([$idHash]);
        $used = $stmt->fetch();
        if ($used && (int) $used['matched_record_id'] !== (int) $record['record_id']) {
            $checks[] = ['label' => 'ID has not been used by someone else', 'ok' => false];
            $fail('This ID number has already been used by someone else. If this is a mistake, please contact the MCRO.');
        }
    } catch (Throwable $e) {
        // wala pa na-run ang migration (verified_id_numbers) - i-skip, ayaw i-crash
        error_log('verified_id_numbers check skipped: ' . $e->getMessage());
    }
}

// =====================================================================
// PASAR TANAN -> VERIFIED
// =====================================================================
$extracted = ['date_of_birth' => null, 'barangay' => null];
$dobForFill = $idDobs[0] ?? null;
// kung naay birth record nga naka-match, gamiton ang official nga birthdate gikan sa MCRO
if (!empty($record['registry_type']) && $record['registry_type'] === 'birth' && !empty($record['date_of_event'])) {
    $dobForFill = $record['date_of_event'];
}
$bgyForFill = extract_barangay($ocrText, $pdo) ?: ($record['barangay'] ?? null);

if ($loggedInUserId) {
    $pdo->prepare("UPDATE users SET identity_verified = 1, matched_record_id = COALESCE(matched_record_id, ?) WHERE user_id = ?")
        ->execute([(int) $record['record_id'], $loggedInUserId]);

    $setParts = []; $params = [];
    if (empty($accountUser['date_of_birth']) && $dobForFill) { $setParts[] = 'date_of_birth = ?'; $params[] = $dobForFill; $extracted['date_of_birth'] = $dobForFill; }
    if (empty($accountUser['barangay']) && $bgyForFill)      { $setParts[] = 'barangay = ?';      $params[] = $bgyForFill;  $extracted['barangay'] = $bgyForFill; }
    if ($setParts) {
        $params[] = $loggedInUserId;
        $pdo->prepare("UPDATE users SET " . implode(', ', $setParts) . " WHERE user_id = ?")->execute($params);
    }

    $pdo->prepare("
        INSERT INTO identity_verifications (user_id, matched_record_id, verification_method, status, verified_at)
        VALUES (?, ?, 'id_scan', 'verified', NOW())
    ")->execute([$loggedInUserId, (int) $record['record_id']]);
    $_SESSION['identity_verified'] = 1;
} else {
    // Pre-registration: i-mark sa session. Kung lahi ang record nga naka-match sa ID kaysa
    // sa gi-search sa user, ang record nga motugma sa ID ang gamiton.
    $_SESSION['identity_scan_verified'] = true;
    $_SESSION['matched_record_id']      = (int) $record['record_id'];
    $fullName = formatFullName($record['last_name'] ?? '', $record['first_name'] ?? '', $record['middle_name'] ?? '');   // Given Middle Surname, Mixed Case
    if ($fullName !== '') $_SESSION['search_full_name'] = $fullName;
    $_SESSION['ocr_date_of_birth'] = $dobForFill;
    $_SESSION['ocr_barangay']      = $bgyForFill;
    $extracted = ['date_of_birth' => $dobForFill, 'barangay' => $bgyForFill];
}

// I-save ang hash sa ID number (dili ang tinood nga number)
if ($idHash !== null) {
    try {
        $pdo->prepare("INSERT IGNORE INTO verified_id_numbers (id_hash, id_type, matched_record_id) VALUES (?, ?, ?)")
            ->execute([$idHash, $idType, (int) $record['record_id']]);
    } catch (Throwable $e) {
        error_log('verified_id_numbers insert skipped: ' . $e->getMessage());
    }
}

$_SESSION['id_scan_attempts'] = [];   // reset human sa success

$out = ['success' => true, 'verified' => true, 'id_type' => $cls['label'], 'extracted' => $extracted, 'checks' => $checks];
if (ID_VERIFY_DEBUG) $out['debug'] = $debug;
respond($out);
