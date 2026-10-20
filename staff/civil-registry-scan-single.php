<?php
/**
 * civil-registry-scan-single.php
 * -------------------------------
 * AJAX endpoint for the "Verify Scanned Information" screen: lets staff
 * click a small "Scan" button UNDER ONE SPECIFIC PHOTO (Front 1, Front 2,
 * Back 1, or Back 2) instead of only relying on the combined OCR pass that
 * ran automatically at upload time.
 *
 * Why this is useful: sometimes one shot is blurry/glared and another shot
 * of the same side is clear - or staff only notices AFTER seeing the
 * "Raw OCR text" / blank fields that one particular photo needs a re-scan.
 * This re-runs OCR on JUST that one image (already saved on disk from the
 * upload step - nothing is re-uploaded) and fills in any fields that are
 * still blank, without touching values staff may have already corrected.
 *
 * Request:  POST side = front1 | front2 | back1 | back2
 * Response: JSON { ok: true, updated: {field_key: value, ...}, ocr_len: N }
 *           or    { ok: false, error: '...' }
 */

require_once __DIR__ . '/includes/staff-auth-guard.php';
require_once __DIR__ . '/lib/CertificateOcr.php';
require_once __DIR__ . '/lib/CertificateOcrAdvanced.php';

if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

function respond(array $data): void
{
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['ok' => false, 'error' => 'Invalid request method.']);
}

if (empty($_SESSION['scan_draft'])) {
    respond(['ok' => false, 'error' => 'There is no active scan draft. Please upload a photo first.']);
}

$side = $_POST['side'] ?? '';
$sideToDraftKey = [
    'front1' => 'image_path',
    'front2' => 'image_path_2',
    'back1'  => 'image_path_back',
    'back2'  => 'image_path_back_2',
];

if (!isset($sideToDraftKey[$side])) {
    respond(['ok' => false, 'error' => 'Invalid photo.']);
}

$draft = $_SESSION['scan_draft'];
$relPath = $draft[$sideToDraftKey[$side]] ?? null;

if (empty($relPath)) {
    respond(['ok' => false, 'error' => 'No photo was uploaded for this slot.']);
}

if (!cert_ocr_available()) {
    respond(['ok' => false, 'error' => 'OCR (Tesseract) is not installed on the server, so scanning is not possible.']);
}

$absPath = __DIR__ . '/../' . $relPath;
if (!is_file($absPath)) {
    respond(['ok' => false, 'error' => 'The saved photo could not be found on the server.']);
}

// OCR just this one photo. In Old Certificate Mode (set at upload time for
// this whole draft), run the slower multi-version/consensus pipeline for a
// better shot at faded/damaged text; otherwise keep the fast single-pass
// OCR used everywhere else.
$type = $draft['type'] ?? 'birth';
$oldMode = !empty($draft['old_certificate_mode']);

if ($oldMode) {
    $adv = cert_adv_scan_photo($absPath, $type, true);
    $ocrText = $adv['ocr_text'];
    if (trim($ocrText) === '') {
        respond(['ok' => false, 'error' => 'OCR could not read any text from this photo - it may be blurry, rotated, or have glare.']);
    }
    $extractedWithConfidence = $adv['fields']; // key => ['value','confidence','status','alternatives']
} else {
    $ocrText = cert_run_ocr($absPath);
    if (trim($ocrText) === '') {
        respond(['ok' => false, 'error' => 'OCR could not read any text from this photo - it may be blurry, rotated, or have glare.']);
    }
    $extracted = cert_auto_extract($type, $ocrText);
    $extractedWithConfidence = array_map(fn($v) => ['value' => $v, 'confidence' => 70, 'status' => 'MEDIUM', 'alternatives' => []], $extracted);
}

// Only report back fields that are genuinely new/useful - don't overwrite
// a value staff already has typed/verified in the form with a blank OCR
// result, and don't bother sending values the form already shows.
$existing = $draft['fields'] ?? [];
$existingConfidence = $draft['field_confidence'] ?? [];
$updated = [];
foreach ($extractedWithConfidence as $key => $info) {
    $value = trim((string) $info['value']);
    if ($value === '') continue;
    if (isset($existing[$key]) && trim((string) $existing[$key]) !== '') continue;
    $updated[$key] = $value;
    $existingConfidence[$key] = $info;
}

// Persist into the session draft so a page refresh keeps these too, and
// append this photo's OCR text to the stored raw text for the "Raw OCR
// text" details box.
$_SESSION['scan_draft']['fields'] = array_merge($existing, $updated);
$_SESSION['scan_draft']['field_confidence'] = $existingConfidence;
$_SESSION['scan_draft']['ocr_text'] = trim(($draft['ocr_text'] ?? '') . "\n" . $ocrText);
$_SESSION['scan_draft']['ocr_ran'] = true;

respond([
    'ok'      => true,
    'updated' => $updated,
    'confidence' => array_intersect_key($existingConfidence, $updated),
    'ocr_len' => strlen(trim($ocrText)),
]);
