<?php
/**
 * civil-registry-scan-inline.php
 * --------------------------------
 * AJAX endpoint for the "📷 SCAN FRONT" / "📷 SCAN BACK" buttons that live
 * DIRECTLY inside civil-registry-form.php (Add/Edit Record) - NOT the
 * separate "Scan & Upload Certificate" page (civil-registry-scan.php).
 *
 * Unlike civil-registry-scan.php (which builds up a full official
 * certificate_data document across a session draft + a dedicated verify
 * screen), this endpoint handles ONE side (front or back) at a time,
 * uploaded straight from whatever record the staff currently has open,
 * and returns values already mapped onto the SIMPLE columns that
 * civil-registry-form.php's fields use (first_name, last_name, sex,
 * father_name, ...) via the same cert_map_to_legacy_columns() helper the
 * other flow uses - so both flows stay consistent with each other.
 *
 * It also returns the raw (unmapped) certificate fields so the calling
 * page can keep a running certificate_data JSON blob across both the
 * Front and Back scans, to store alongside the record (same column the
 * other flow uses) for future reference/printing.
 *
 * Request (multipart/form-data POST):
 *   side                  = front | back
 *   registry_type         = birth | marriage | death
 *   old_certificate_mode  = "1" (optional - see lib/CertificateOcrAdvanced.php)
 *   certificate_image     = file (required)
 *   certificate_image2    = file (optional - 2nd/clearer photo of the same side)
 *   ocr_text_client       = string (optional) - text already recognized IN
 *                           THE BROWSER (Tesseract.js) for this side, sent
 *                           by civil-registry-form.php's scanSide(). When
 *                           present, this is used INSTEAD of running OCR on
 *                           the server - this is what makes scanning work
 *                           even when the PHP server itself has no
 *                           Tesseract binary installed (e.g. plain XAMPP on
 *                           Windows with nothing extra configured). The
 *                           image is still uploaded/saved normally either way.
 *
 * Response JSON:
 *   { ok:true, side, image_path, image_path_2,
 *     legacy: {...simple column values...},
 *     common: {registry_number?, city_municipality?, date_prepared?, barangay?},
 *     cert_fields: {...all raw non-empty certificate fields...},
 *     confidence: {...key => {value,confidence,status}, only in old-certificate mode...} }
 *   or { ok:false, error }
 */

require_once __DIR__ . '/includes/staff-auth-guard.php';
require_once __DIR__ . '/includes/name-format.php';
require_once __DIR__ . '/lib/CertificateFormFields.php';
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

$side = $_POST['side'] ?? '';
if (!in_array($side, ['front', 'back'], true)) {
    respond(['ok' => false, 'error' => 'Invalid side (front/back).']);
}

$type = $_POST['registry_type'] ?? 'birth';
if (!in_array($type, ['birth', 'marriage', 'death'], true)) $type = 'birth';

$oldMode = !empty($_POST['old_certificate_mode']);

if (empty($_FILES['certificate_image']) || $_FILES['certificate_image']['error'] !== UPLOAD_ERR_OK) {
    respond(['ok' => false, 'error' => 'Please choose a photo to upload (JPG/PNG/WEBP).']);
}

$allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
$uploadDir = __DIR__ . '/../uploads/civil-registry-scans/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

/** Validates + saves ONE uploaded photo; returns ['path'=>relative,'full'=>absolute] or null. */
$saveOne = function (array $file, string $label) use ($allowed, $uploadDir): ?array {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!isset($allowed[$mime])) return null;
    // No app-level size cap here anymore - staff asked for "walay limit"
    // (no limit) on certificate photo uploads, especially for Bulk Scan
    // where dozens of full-resolution phone photos get sent in one go.
    // The real, unavoidable ceiling is PHP's own php.ini settings
    // (upload_max_filesize / post_max_size / max_file_uploads) - a PHP
    // script can NEVER raise those for itself mid-request, so if uploads
    // still fail on very large or very many files, raise those three in
    // php.ini (or in an .htaccess with `php_value upload_max_filesize
    // 100M` etc. on Apache) and restart PHP/the web server.

    $filename = 'scan_' . ($_SESSION['staff_id'] ?? 0) . '_' . $label . '_' . time() . '_' . mt_rand(100, 999) . '.' . $allowed[$mime];
    $destPath = $uploadDir . $filename;
    move_uploaded_file($file['tmp_name'], $destPath);

    return ['path' => 'uploads/civil-registry-scans/' . $filename, 'full' => $destPath];
};

$photo1 = $saveOne($_FILES['certificate_image'], $side . '1');
if (!$photo1) {
    respond(['ok' => false, 'error' => 'Invalid image file (JPG/PNG/WEBP only).']);
}

$photo2 = null;
if (!empty($_FILES['certificate_image2']) && $_FILES['certificate_image2']['error'] === UPLOAD_ERR_OK) {
    $photo2 = $saveOne($_FILES['certificate_image2'], $side . '2');
}

$photoPaths = array_values(array_filter([$photo1['full'], $photo2['full'] ?? null]));
$clientOcrText = trim($_POST['ocr_text_client'] ?? '');
$serverOcrAvailable = cert_ocr_available();

// ---------------------------------------------------------------
// Pick which OCR result to use for this side, in order of preference:
//   0. YOLO field-detection + Tesseract (local Python service) - only
//      kicks in once a trained model exists at geogene-yolo/models/best.pt
//      AND the service (geogene-yolo/service/app.py) is running. Detects
//      each field's own region first, so it isn't thrown off by a
//      certificate's layout/wording the way whole-text pattern matching
//      can be. Until the model is trained, this always comes back null
//      almost instantly and every path below runs exactly as before.
//   1. Old Certificate Mode + server Tesseract available -> the slower
//      multi-pass image-enhancement pipeline (best quality for old/
//      faded certificates).
//   2. Browser-side OCR text (Tesseract.js, sent by the page's JS) -
//      works even with ZERO server-side setup, so this is what makes
//      scanning reliably work on a plain XAMPP install with no
//      Tesseract binary.
//   3. Server Tesseract, normal single-pass (kept as a fallback for
//      when the browser OCR itself failed to run, e.g. no internet
//      access to load the Tesseract.js script/language data).
//   4. Nothing available at all -> don't block the form, just tell
//      staff to type the fields manually.
// ---------------------------------------------------------------
require_once __DIR__ . '/lib/YoloOcrClient.php';
$yoloFieldsRaw = yolo_scan_certificate($photo1['full'], $type);

if ($yoloFieldsRaw !== null) {
    $certFields = yolo_fields_to_cert_fields($yoloFieldsRaw);
    $confidence = yolo_fields_confidence($yoloFieldsRaw);
    $rawOcrText = '';
} elseif ($oldMode && $serverOcrAvailable) {
    $perPhotoFieldSets = [];
    $rawOcrTextParts = [];
    foreach ($photoPaths as $p) {
        $adv = cert_adv_scan_photo($p, $type, true);
        if (!empty($adv['fields'])) $perPhotoFieldSets[] = $adv['fields'];
        if (!empty($adv['ocr_text'])) $rawOcrTextParts[] = $adv['ocr_text'];
    }
    $confidence = cert_adv_merge_field_sets($perPhotoFieldSets);
    $certFields = array_map(fn($f) => $f['value'], $confidence);
    $rawOcrText = trim(implode("\n", $rawOcrTextParts));
} elseif ($clientOcrText !== '') {
    $certFields = cert_auto_extract($type, $clientOcrText);
    $confidence = [];
    $rawOcrText = $clientOcrText;
} elseif ($serverOcrAvailable) {
    $ocrText = cert_run_ocr_multi($photoPaths);
    $certFields = trim($ocrText) !== '' ? cert_auto_extract($type, $ocrText) : [];
    $confidence = [];
    $rawOcrText = $ocrText;
} else {
    // Never block the form - image is saved, staff just types the fields manually.
    respond([
        'ok'           => true,
        'side'         => $side,
        'image_path'   => $photo1['path'],
        'image_path_2' => $photo2['path'] ?? null,
        'legacy'       => [],
        'common'       => [],
        'cert_fields'  => [],
        'confidence'   => [],
        'raw_ocr_text' => '',
        'warning'      => 'OCR did not run (browser or server) - please type the fields manually.',
    ]);
}

if (empty($certFields)) {
    respond([
        'ok'           => true,
        'side'         => $side,
        'image_path'   => $photo1['path'],
        'image_path_2' => $photo2['path'] ?? null,
        'legacy'       => [],
        'common'       => [],
        'cert_fields'  => [],
        'confidence'   => [],
        'raw_ocr_text' => $rawOcrText,
        'warning'      => 'The photo was saved but no clear text could be read - it may be blurry, rotated, or have glare. Try Old Certificate Mode, or type the fields manually.',
    ]);
}

$legacy = cert_map_to_legacy_columns($type, $certFields);

// Never let a low-confidence/absent OCR guess silently set Sex or Civil
// Status via the mapping helper's ternary fallback ("not female" => male
// by default) - only carry those two over when the source field genuinely
// had a reading. Everything else in $legacy is dropped a few lines down if
// blank anyway.
if ($type === 'birth' && empty(trim((string) ($certFields['child_sex'] ?? '')))) {
    unset($legacy['sex']);
}
if ($type === 'death') {
    if (empty(trim((string) ($certFields['sex'] ?? '')))) unset($legacy['sex']);
    if (empty(trim((string) ($certFields['civil_status'] ?? '')))) unset($legacy['civil_status']);
}
$legacy = array_filter($legacy, fn($v) => $v !== null && trim((string) $v) !== '');

// Names come off the certificate in ALL CAPS - show them in Mixed Case, always
// "Given Middle Surname" (first/middle/last are separate pieces, parents/spouse one text).
foreach (['first_name', 'middle_name', 'last_name'] as $nf) {
    if (isset($legacy[$nf])) $legacy[$nf] = gg_title_case($legacy[$nf]);
}
foreach (['father_name', 'mother_name', 'spouse_name', 'wife_father_name', 'wife_mother_name'] as $nf) {
    if (isset($legacy[$nf])) $legacy[$nf] = gg_display_name($legacy[$nf]);
}
if (isset($legacy['place_of_event'])) $legacy['place_of_event'] = gg_title_case($legacy['place_of_event']);

$common = [];
foreach (['registry_number', 'page_no', 'city_municipality', 'date_prepared', 'barangay'] as $k) {
    if (!empty(trim((string) ($certFields[$k] ?? '')))) $common[$k] = trim($certFields[$k]);
}
if (!empty($common['barangay'])) $common['barangay'] = (string) gg_canonical_barangay($pdo, $common['barangay']);

// Best-effort date normalization to yyyy-mm-dd (what <input type="date">
// needs) - only applied when PHP can confidently parse it; otherwise the
// raw OCR text is left for staff to correct by hand rather than guessing.
$normalizeDate = function (?string $v): ?string {
    $v = trim((string) $v);
    if ($v === '') return null;
    $ts = strtotime($v);
    return $ts ? date('Y-m-d', $ts) : null;
};
if (!empty($legacy['date_of_event'])) {
    $n = $normalizeDate($legacy['date_of_event']);
    if ($n) $legacy['date_of_event'] = $n;
}
if (!empty($common['date_prepared'])) {
    $n = $normalizeDate($common['date_prepared']);
    if ($n) $common['date_prepared'] = $n;
}

respond([
    'ok'           => true,
    'side'         => $side,
    'image_path'   => $photo1['path'],
    'image_path_2' => $photo2['path'] ?? null,
    'legacy'       => $legacy,
    'common'       => $common,
    'cert_fields'  => array_filter($certFields, fn($v) => trim((string) $v) !== ''),
    'confidence'   => $confidence,
    'raw_ocr_text' => $rawOcrText,
]);
