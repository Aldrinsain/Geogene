<?php
/**
 * lib/YoloOcrClient.php
 * ----------------------
 * Talks to the local Python YOLO+OCR service (geogene-yolo/service/app.py)
 * and converts its response into the SAME $certFields shape that
 * cert_auto_extract() already produces from the regex/text-based OCR
 * pipeline - so civil-registry-scan-inline.php can hand either one to the
 * existing cert_map_to_legacy_columns() unchanged.
 *
 * Every function here fails SOFT: any problem (service not running, model
 * not trained yet, network hiccup, bad response) returns null and the
 * caller falls back to the already-working browser-OCR pipeline. This file
 * never throws, never blocks the request for long (short connect timeout),
 * and never invents a field value.
 */

if (!defined('YOLO_SERVICE_URL')) {
    // Change this if the Python service is started on a different port
    // (see geogene-yolo/service/app.py's PORT constant).
    define('YOLO_SERVICE_URL', 'http://127.0.0.1:5005');
}

/**
 * Sends one certificate photo to the YOLO+OCR service.
 * Returns the raw {field_name: {text, confidence}|null, ...} map from the
 * service on success, or null if the service is unavailable / the model
 * isn't trained yet / anything else went wrong.
 */
function yolo_scan_certificate(string $absoluteImagePath, string $registryType): ?array
{
    if (!is_file($absoluteImagePath)) return null;
    if (!function_exists('curl_init')) return null; // PHP built without curl - silently skip, fall back

    $ch = curl_init(rtrim(YOLO_SERVICE_URL, '/') . '/scan');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        // Fail FAST if the service simply isn't running (the common case
        // until a model is trained) - never make staff wait on this.
        CURLOPT_CONNECTTIMEOUT => 2,
        // Model inference + OCR on a full photo can genuinely take a few
        // seconds once the service IS up and warmed.
        CURLOPT_TIMEOUT        => 25,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => [
            'document_type' => $registryType,
            'image'         => new CURLFile($absoluteImagePath),
        ],
    ]);
    $raw = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($raw === false || $curlError !== '') {
        error_log('[YOLO OCR] service unreachable (' . $curlError . ') - falling back to browser OCR.');
        return null;
    }

    $data = json_decode($raw, true);
    if (!is_array($data) || empty($data['success']) || !isset($data['fields']) || !is_array($data['fields'])) {
        if (is_array($data) && !empty($data['error'])) {
            error_log('[YOLO OCR] service returned an error: ' . $data['error']);
        }
        return null;
    }

    return $data['fields'];
}

/**
 * Converts the YOLO service's {field_name: {text, confidence}|null, ...}
 * response into the flat $certFields array cert_map_to_legacy_columns()
 * expects (same key names cert_auto_extract() already produces for a
 * BIRTH certificate) - so the rest of civil-registry-scan-inline.php
 * doesn't need to know or care which OCR pipeline actually ran.
 *
 * Person names come back as ONE combined string per box (e.g. "Sain,
 * Aldrin Piloton"), but cert_map_to_legacy_columns() needs them already
 * split into first/middle/last - reuses the exact same comma-aware
 * splitting rule the Excel import uses (see CivilRegistryImport.php),
 * so a name box reads the same way here as it does on import: a comma
 * means "Surname, Given Names"; no comma means no surname on file.
 */
function yolo_fields_to_cert_fields(array $yoloFields): array
{
    require_once __DIR__ . '/CivilRegistryImport.php';

    $text = function (string $key) use ($yoloFields): string {
        return trim((string) ($yoloFields[$key]['text'] ?? ''));
    };

    $cert = [];

    if ($text('registry_no') !== '') $cert['registry_number'] = $text('registry_no');
    if ($text('barangay') !== '') $cert['barangay'] = $text('barangay');
    if ($text('date_of_birth') !== '') $cert['date_of_birth'] = $text('date_of_birth');
    // cert_map_to_legacy_columns() rebuilds place_of_event by joining the
    // hospital/city/province boxes together - the YOLO model only detects
    // ONE combined "place of birth" box, so put the whole reading in the
    // first of those three; the other two are simply left unset.
    if ($text('place_of_birth') !== '') $cert['place_of_birth_hospital'] = $text('place_of_birth');

    $splitInto = function (string $rawName, string $prefix) use (&$cert): void {
        if ($rawName === '') return;
        $parts = import_split_full_name($rawName);
        $cert[$prefix . '_first']  = $parts['first'];
        $cert[$prefix . '_middle'] = $parts['middle'];
        $cert[$prefix . '_last']   = $parts['last'];
    };

    $splitInto($text('child_name'), 'child');
    $splitInto($text('mother_name'), 'mother');
    $splitInto($text('father_name'), 'father');

    return $cert;
}

/**
 * Also returns per-field confidence, keyed the same way
 * civil-registry-scan-inline.php's existing "confidence" response field
 * uses in Old Certificate Mode - lets the Bulk Scan review UI flag
 * low-confidence reads later if that's ever wired in, without needing
 * another round-trip to the Python service.
 */
function yolo_fields_confidence(array $yoloFields): array
{
    $confidence = [];
    foreach ($yoloFields as $key => $val) {
        if (is_array($val) && isset($val['text'], $val['confidence'])) {
            $confidence[$key] = ['value' => $val['text'], 'confidence' => $val['confidence']];
        }
    }
    return $confidence;
}
