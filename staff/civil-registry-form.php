<?php
/**
 * civil-registry-form.php - Add / Edit Civil Registry Record
 * ------------------------------------------------------------
 * Plain, simple input fields ONLY - grouped per registry type
 * (Birth / Marriage / Death). This is intentionally NOT styled to look
 * like the official certificate document (that look-and-feel lives only
 * in civil-registry-print.php for the actual "Print Exact Certificate"
 * output) - this page is just a normal data-entry form.
 *
 * Fields per type:
 *   BIRTH:    Page No., Registry No., Barangay, Date Prepared,
 *             Date of Birth, Name of Child, Name of Mother, Name of Father
 *   MARRIAGE: Page No., Registry No., Barangay, Date Prepared,
 *             Date of Marriage, Name of Wife, Name of Husband,
 *             Name of Mother (W), Name of Mother (H),
 *             Name of Father (W), Name of Father (H)
 *   DEATH:    Page No., Registry No., Barangay, Date Prepared,
 *             Name of Deceased, Sex, Date of Death, Date of Birth,
 *             Age at Time of Death, Place of Death, Civil Status,
 *             Name of Father, Maiden Name of Mother, Cause of Death
 *
 * The "📷 SCAN FRONT / SCAN BACK" section stays on this page (OCR via
 * civil-registry-scan-inline.php) and auto-fills whichever of the fields
 * above are still blank - see lib/CertificateFormFields.php's
 * cert_map_to_legacy_columns() for how the detailed OCR reading gets
 * mapped down onto these simple columns.
 */
require_once __DIR__ . '/includes/staff-auth-guard.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/name-format.php';

/**
 * Date helpers for the Date Prepared / Date of Birth / Marriage / Death fields.
 * The form posts ISO (yyyy-mm-dd) via the calendar picker; these re-check it on
 * the server so a bad value can never reach MySQL (e.g. "2020-13-45", a
 * hand-edited POST, or a future date).
 */
const GG_MIN_DATE = '1850-01-01';

/** Returns the ISO date if $v is a real calendar date, else ''. Empty-safe. */
function ggIsoOrEmpty($v): string
{
    $v = trim((string) $v);
    if ($v === '') return '';
    $d = DateTime::createFromFormat('!Y-m-d', $v);
    $e = DateTime::getLastErrors();
    if (!$d || ($e && ($e['warning_count'] || $e['error_count'])) || $d->format('Y-m-d') !== $v) return '';
    return $v;
}

/** mm/dd/yyyy for display, or '' for empty / invalid / 0000-00-00. */
function ggDisplayDate($v): string
{
    $iso = ggIsoOrEmpty($v);
    return $iso === '' ? '' : date('m/d/Y', strtotime($iso));
}

/**
 * Validate a posted date. Returns the ISO string, or null when empty OR invalid;
 * on invalid, appends a message to $errors. Dates must be real, not before 1850,
 * and not in the future (server timezone is Asia/Manila, set in config/db.php).
 */
function ggCheckPostedDate($raw, string $label, array &$errors, ?string $notAfterIso = null): ?string
{
    $raw = trim((string) $raw);
    if ($raw === '') return null;
    $iso = ggIsoOrEmpty($raw);
    if ($iso === '') { $errors[] = "$label is not a valid date."; return null; }
    if ($iso < GG_MIN_DATE) { $errors[] = "$label cannot be earlier than 01/01/1850."; return null; }
    if ($iso > date('Y-m-d')) { $errors[] = "$label cannot be a future date."; return null; }
    if ($notAfterIso !== null && $iso > $notAfterIso) {
        $errors[] = "$label cannot be later than the Date of Death."; return null;
    }
    return $iso;
}

$recordId = (int) ($_GET['id'] ?? 0);
$validTypes = ['birth', 'marriage', 'death'];

$record = [
    'registry_type' => 'birth', 'registry_number' => '', 'page_no' => '', 'barangay' => '',
    'date_prepared' => '', 'first_name' => '', 'middle_name' => '', 'last_name' => '',
    'sex' => 'male', 'civil_status' => '', 'date_of_event' => '', 'date_of_birth' => '',
    'father_name' => '', 'mother_name' => '', 'spouse_name' => '',
    'wife_father_name' => '', 'wife_mother_name' => '',
    'age_at_death' => '', 'place_of_event' => '', 'cause_of_death' => '',
    'scan_image_path' => '', 'scan_image_path_2' => '', 'scan_image_path_back' => '', 'scan_image_path_back_2' => '',
    'certificate_data' => '',
];
$isEdit = false;

if ($recordId) {
    $stmt = $pdo->prepare("SELECT * FROM civil_registry_records WHERE record_id = ?");
    $stmt->execute([$recordId]);
    $found = $stmt->fetch();
    if ($found) { $record = array_merge($record, $found); $isEdit = true; }
}

$type = $isEdit ? $record['registry_type'] : ($_GET['type'] ?? 'birth');
if (!in_array($type, $validTypes, true)) $type = 'birth';

$error = '';
$postRepopulate = false; // true only when redisplaying the form after a failed validation, so the single Name box shows exactly what staff just typed rather than rebuilding it from (still-empty) stored columns

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['registry_type'] ?? 'birth';
    if (!in_array($type, $validTypes, true)) $type = 'birth';

    $registryNumber = trim($_POST['registry_number'] ?? '');
    $pageNo         = trim($_POST['page_no'] ?? '');
    // Barangay in ANY case (DICKLUM / dicklum) -> the exact spelling in the barangays table, so the Map always finds it
    $barangay       = (string) gg_canonical_barangay($pdo, $_POST['barangay'] ?? '');
    $dateErrors     = [];
    $datePrepared   = ggCheckPostedDate($_POST['date_prepared'] ?? '', 'Date Prepared', $dateErrors);

    // "Name of Child / Husband / Deceased" is now ONE box on the form -
    // "Given Name Middle Name Surname" - parsed back into the existing
    // first_name/middle_name/last_name storage columns.
    [$firstName, $middleName, $lastName] = parseGivenMiddleSurname(trim($_POST['person_name'] ?? ''));
    $eventLabel  = ['birth' => 'Date of Birth', 'marriage' => 'Date of Marriage', 'death' => 'Date of Death'][$type] ?? 'Date of Event';
    $dateOfEvent = ggCheckPostedDate($_POST['date_of_event'] ?? '', $eventLabel, $dateErrors);

    // Per-type fields - everything not relevant to the chosen type stays
    // NULL (e.g. a Birth record never gets a cause_of_death value).
    $sex = $civilStatus = $dateOfBirth = $fatherName = $motherName = $spouseName = null;
    $wifeFatherName = $wifeMotherName = $ageAtDeath = $placeOfEvent = $causeOfDeath = null;

    if ($type === 'birth') {
        $motherName = gg_clean_person_name($_POST['mother_name'] ?? '');
        $fatherName = gg_clean_person_name($_POST['father_name'] ?? '');
    } elseif ($type === 'marriage') {
        $sex = 'male'; // this record's primary person is always the Husband
        $civilStatus = 'married';
        // "Name of Wife" is typed as ONE "Given Middle Surname" box, same as the
        // Husband. It is saved the same way ("Given Middle Surname", Mixed Case);
        // the Family Tree and Map match it to the wife's own record in any order.
        $wifeNameTyped = trim($_POST['spouse_name'] ?? '');
        if ($wifeNameTyped !== '') {
            [$wifeFirst, $wifeMiddle, $wifeLast] = parseGivenMiddleSurname($wifeNameTyped);
            $spouseName = formatFullName($wifeLast, $wifeFirst, $wifeMiddle);
        }
        $fatherName     = gg_clean_person_name($_POST['father_name'] ?? '');       // Name of Father (H)
        $motherName     = gg_clean_person_name($_POST['mother_name'] ?? '');       // Name of Mother (H)
        $wifeFatherName = gg_clean_person_name($_POST['wife_father_name'] ?? '');  // Name of Father (W)
        $wifeMotherName = gg_clean_person_name($_POST['wife_mother_name'] ?? '');  // Name of Mother (W)
    } else { // death
        $sex = in_array($_POST['sex'] ?? '', ['male', 'female'], true) ? $_POST['sex'] : null;
        $civilStatus = trim($_POST['civil_status'] ?? '') ?: null;
        $dateOfBirth = ggCheckPostedDate($_POST['date_of_birth'] ?? '', 'Date of Birth', $dateErrors, $dateOfEvent);
        $ageAtDeath  = trim($_POST['age_at_death'] ?? '') ?: null;
        $placeOfEvent = gg_title_case(trim($_POST['place_of_event'] ?? '')) ?: null; // Place of Death
        $causeOfDeath = trim($_POST['cause_of_death'] ?? '') ?: null;
        $fatherName = gg_clean_person_name($_POST['father_name'] ?? '');
        $motherName = gg_clean_person_name($_POST['mother_name'] ?? ''); // Maiden Name of Mother
    }

    $fullName = formatFullName($lastName, $firstName, $middleName);

    // Certificate scan data (optional) - filled by the "📷 SCAN FRONT" /
    // "📷 SCAN BACK" buttons below via civil-registry-scan-inline.php. On
    // Edit, if staff doesn't touch the scan section again, these hidden
    // inputs still carry the record's EXISTING saved values (pre-filled
    // from $record further down), so nothing gets silently wiped.
    $scanImagePath      = trim($_POST['scan_image_path'] ?? '') ?: null;
    $scanImagePath2     = trim($_POST['scan_image_path_2'] ?? '') ?: null;
    $scanImagePathBack  = trim($_POST['scan_image_path_back'] ?? '') ?: null;
    $scanImagePathBack2 = trim($_POST['scan_image_path_back_2'] ?? '') ?: null;
    $certificateData    = trim($_POST['certificate_data'] ?? '');
    // Only stamp verified_by/verified_at when there's actual scanned
    // certificate data behind this save - a plain manually-typed record
    // (no certificate photo involved) should never claim to be "verified".
    $verifiedBy    = $certificateData !== '' ? $_SESSION['staff_id'] : null;
    $verifiedAtSql = $certificateData !== '' ? 'NOW()' : 'NULL';

    if (empty($firstName) || empty($lastName) || empty($type) || empty($registryNumber)) {
        $error = 'Registry Type, Registry No., and Name are required.';
        $record = array_merge($record, $_POST, ['registry_type' => $type]);
        $postRepopulate = true;
    } elseif ($dateErrors) {
        $error = implode(' ', $dateErrors);
        $record = array_merge($record, $_POST, ['registry_type' => $type]);
        $postRepopulate = true;
    } else {
        // Book No. / City-Municipality / Suffix / Remarks / Zone are no
        // longer collected from this simplified form. Keep whatever was
        // already stored (edit) or leave empty (new record) - never
        // invent or wipe historical data for existing rows.
        $bookNo = $isEdit ? ($record['book_no'] ?? null) : null;
        $cityMunicipality = $isEdit ? ($record['city_municipality'] ?? null) : null;
        $suffix = $isEdit ? ($record['suffix'] ?? null) : null;
        $remarks = $isEdit ? ($record['remarks'] ?? null) : null;
        $zone = $isEdit ? ($record['zone'] ?? null) : null;

        $data = [
            $type, $registryNumber ?: null, $bookNo, $pageNo ?: null, $cityMunicipality, $datePrepared,
            $fullName ?: null, $firstName, $middleName ?: null, $lastName, $suffix,
            $sex, $civilStatus, $dateOfEvent, $dateOfBirth, $placeOfEvent,
            $fatherName, $motherName, $spouseName, $wifeFatherName, $wifeMotherName,
            $ageAtDeath, $causeOfDeath, $barangay ?: null, $zone, $remarks,
            $scanImagePath, $scanImagePath2, $scanImagePathBack, $scanImagePathBack2,
            $certificateData ?: null, $verifiedBy,
        ];

        try {
            if ($isEdit) {
                $stmt = $pdo->prepare("
                    UPDATE civil_registry_records SET registry_type=?, registry_number=?, book_no=?, page_no=?,
                    city_municipality=?, date_prepared=?, full_name=?, first_name=?, middle_name=?, last_name=?,
                    suffix=?, sex=?, civil_status=?, date_of_event=?, date_of_birth=?, place_of_event=?,
                    father_name=?, mother_name=?, spouse_name=?, wife_father_name=?, wife_mother_name=?,
                    age_at_death=?, cause_of_death=?, barangay=?, zone=?, remarks=?,
                    scan_image_path=?, scan_image_path_2=?, scan_image_path_back=?, scan_image_path_back_2=?,
                    certificate_data=?, verified_by=?, verified_at=$verifiedAtSql WHERE record_id = ?
                ");
                $stmt->execute(array_merge($data, [$recordId]));
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO civil_registry_records (registry_type, registry_number, book_no, page_no,
                    city_municipality, date_prepared, full_name, first_name, middle_name, last_name, suffix,
                    sex, civil_status, date_of_event, date_of_birth, place_of_event,
                    father_name, mother_name, spouse_name, wife_father_name, wife_mother_name,
                    age_at_death, cause_of_death, barangay, zone, remarks,
                    scan_image_path, scan_image_path_2, scan_image_path_back, scan_image_path_back_2,
                    certificate_data, verified_by, verified_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, $verifiedAtSql)
                ");
                $stmt->execute($data);
            }
            header('Location: civil-registry.php');
            exit;
        } catch (PDOException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                // duplicate Registry No. (it must be unique) - say exactly which record already has it
                $dup = $pdo->prepare("SELECT record_id, first_name, middle_name, last_name FROM civil_registry_records WHERE registry_number = ? LIMIT 1");
                $dup->execute([$registryNumber]);
                $d = $dup->fetch();
                $error = 'Registry No. "' . $registryNumber . '" is already used'
                       . ($d ? ' by ' . formatFullName($d['last_name'], $d['first_name'], $d['middle_name']) . ' (Record #' . $d['record_id'] . ')' : '')
                       . '. Please use a different Registry No.';
            } else {
                error_log('civil-registry-form save failed: ' . $e->getMessage());
                $error = 'Could not save the record: ' . $e->getMessage();
            }
            $record = array_merge($record, $_POST, ['registry_type' => $type]);
            $postRepopulate = true;
        }
    }
}

$pageTitle = $isEdit ? 'Edit Civil Registry Record' : 'Add Civil Registry Record';
$pageSub = 'Fill in the civil registry details.';
$activeStaffNav = 'registry';

// "Name of Child / Husband / Deceased" single-box display value.
// After a failed submit, $record['person_name'] IS the raw text staff
// just typed (re-show it exactly). Otherwise (a fresh Edit load, or a
// blank new-record form) build it from the real first_name/middle_name/
// last_name storage columns.
$personNameDisplay = $postRepopulate
    ? trim((string) ($record['person_name'] ?? ''))
    : formatGivenMiddleSurname($record['first_name'] ?? '', $record['middle_name'] ?? '', $record['last_name'] ?? '');

// "Name of Wife" single-box display value. spouse_name is stored in the
// OLDER "Surname, Given Name Middle Name" convention (see the note where
// it's parsed on submit above), so an Edit load has to convert it back to
// "Given Middle Surname" for the box; a failed-submit repopulate is
// already in that box format as typed, no conversion needed.
if ($postRepopulate) {
    $spouseNameDisplay = trim((string) ($record['spouse_name'] ?? ''));
} else {
    [$sdFirst, $sdMiddle, $sdLast] = splitCanonicalFullName((string) ($record['spouse_name'] ?? ''));
    $spouseNameDisplay = formatGivenMiddleSurname($sdFirst, $sdMiddle, $sdLast);
}

require_once __DIR__ . '/includes/staff-header.php';
?>

<div class="panel">
  <?php if ($error): ?><div class="error-text"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <form method="POST" autocomplete="off">
    <?php
      $scanFrontExisting  = $record['scan_image_path'] ?? '';
      $scanFront2Existing = $record['scan_image_path_2'] ?? '';
      $scanBackExisting   = $record['scan_image_path_back'] ?? '';
      $scanBack2Existing  = $record['scan_image_path_back_2'] ?? '';
      $certDataExisting   = $record['certificate_data'] ?? '';
    ?>
    <div class="panel" style="background:var(--bg-page);border:1px solid var(--border);border-radius:9px;padding:14px;margin-bottom:16px;">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;">
        <div style="font-weight:700;margin-bottom:2px;">📄 Certificate Scan (optional)</div>
        <?php if (!$isEdit): ?>
        <a href="civil-registry-bulk-scan.php" class="btn btn-outline btn-sm" style="white-space:nowrap;">📷 Scan multiple certificates at once →</a>
        <?php endif; ?>
      </div>
      <div style="font-size:12px;color:var(--text-muted);margin-bottom:10px;">
        Upload the Front and/or Back photo of the certificate, then click SCAN - the fields below are filled in automatically from the text that was read (OCR). You can still edit them manually after scanning.
      </div>

      <label style="display:flex;align-items:flex-start;gap:8px;font-weight:600;cursor:pointer;background:#fff6da;border:1px solid #e8d385;border-radius:8px;padding:8px 10px;margin-bottom:12px;">
        <input type="checkbox" id="oldCertificateMode" style="margin-top:3px;">
        <span style="font-size:12.5px;">
          🕰️ Old Certificate Mode
          <div style="font-weight:400;color:var(--text-muted);">Check this if the certificate is old/faded/blurred - slower, but the reading is more ACCURATE.</div>
        </span>
      </label>

      <div class="form-grid-2">
        <div style="border:1px solid var(--border);border-radius:8px;padding:10px;">
          <div style="font-weight:700;font-size:12.5px;margin-bottom:6px;">FRONT</div>
          <input type="file" id="scanFile-front-1" accept="image/jpeg,image/png,image/webp" style="width:100%;font-size:12px;margin-bottom:4px;">
          <input type="file" id="scanFile-front-2" accept="image/jpeg,image/png,image/webp" style="width:100%;font-size:12px;margin-bottom:8px;">
          <div style="font-size:10.5px;color:var(--text-muted);margin:-4px 0 8px;">1st photo required, 2nd optional (clearer retake)</div>
          <button type="button" class="btn btn-outline btn-sm" style="width:100%;" onclick="scanSide('front', this)">📷 SCAN FRONT</button>
          <div id="scanPreview-front" style="margin-top:8px;"><?php if ($scanFrontExisting): ?><img src="../<?= htmlspecialchars($scanFrontExisting) ?>" style="max-width:100%;border:1px solid var(--border);border-radius:6px;"><?php endif; ?></div>
          <div id="scanStatus-front" style="font-size:11.5px;margin-top:6px;min-height:14px;"></div>
          <details id="scanRawWrap-front" style="display:none;margin-top:6px;">
            <summary style="font-size:11px;cursor:pointer;color:var(--text-muted);">🔍 View the raw OCR text</summary>
            <pre id="scanRawText-front" style="white-space:pre-wrap;font-size:10.5px;background:var(--bg-page);border:1px solid var(--border);border-radius:6px;padding:6px;max-height:180px;overflow:auto;margin-top:4px;"></pre>
          </details>
        </div>
        <div style="border:1px solid var(--border);border-radius:8px;padding:10px;">
          <div style="font-weight:700;font-size:12.5px;margin-bottom:6px;">BACK <span style="font-weight:400;color:var(--text-muted);">(optional)</span></div>
          <input type="file" id="scanFile-back-1" accept="image/jpeg,image/png,image/webp" style="width:100%;font-size:12px;margin-bottom:4px;">
          <input type="file" id="scanFile-back-2" accept="image/jpeg,image/png,image/webp" style="width:100%;font-size:12px;margin-bottom:8px;">
          <div style="font-size:10.5px;color:var(--text-muted);margin:-4px 0 8px;">1st photo required, 2nd optional (clearer retake)</div>
          <button type="button" class="btn btn-outline btn-sm" style="width:100%;" onclick="scanSide('back', this)">📷 SCAN BACK</button>
          <div id="scanPreview-back" style="margin-top:8px;"><?php if ($scanBackExisting): ?><img src="../<?= htmlspecialchars($scanBackExisting) ?>" style="max-width:100%;border:1px solid var(--border);border-radius:6px;"><?php endif; ?></div>
          <div id="scanStatus-back" style="font-size:11.5px;margin-top:6px;min-height:14px;"></div>
          <details id="scanRawWrap-back" style="display:none;margin-top:6px;">
            <summary style="font-size:11px;cursor:pointer;color:var(--text-muted);">🔍 View the raw OCR text</summary>
            <pre id="scanRawText-back" style="white-space:pre-wrap;font-size:10.5px;background:var(--bg-page);border:1px solid var(--border);border-radius:6px;padding:6px;max-height:180px;overflow:auto;margin-top:4px;"></pre>
          </details>
        </div>
      </div>

      <?php foreach ([
          'scan_image_path' => $scanFrontExisting, 'scan_image_path_2' => $scanFront2Existing,
          'scan_image_path_back' => $scanBackExisting, 'scan_image_path_back_2' => $scanBack2Existing,
      ] as $name => $val): ?>
        <input type="hidden" name="<?= $name ?>" id="hidden-<?= $name ?>" value="<?= htmlspecialchars($val) ?>">
      <?php endforeach; ?>
      <input type="hidden" name="certificate_data" id="hiddenCertificateData" value="<?= htmlspecialchars($certDataExisting) ?>">
    </div>

    <div class="form-grid-2" style="background:var(--bg-page);border:1px solid var(--border);border-radius:9px;padding:12px 14px;margin-bottom:16px;">
      <div class="form-row">
        <label>Registry Type *</label>
        <select name="registry_type" id="registryType" required>
          <option value="birth" <?= $type === 'birth' ? 'selected' : '' ?>>Birth</option>
          <option value="marriage" <?= $type === 'marriage' ? 'selected' : '' ?>>Marriage</option>
          <option value="death" <?= $type === 'death' ? 'selected' : '' ?>>Death</option>
        </select>
      </div>
      <div class="form-row">
        <label>Registry No. *</label>
        <input type="text" name="registry_number" autocomplete="off" required value="<?= htmlspecialchars($record['registry_number'] ?? '') ?>" placeholder="0000-00000">
      </div>
    </div>

    <div class="form-grid-2">
      <div class="form-row"><label>Page No.</label><input type="text" name="page_no" autocomplete="off" value="<?= htmlspecialchars($record['page_no'] ?? '') ?>"></div>
      <div class="form-row"><label>Barangay</label><input type="text" name="barangay" autocomplete="off" value="<?= htmlspecialchars($record['barangay'] ?? '') ?>"></div>
    </div>
    <div class="form-grid-2">
      <div class="form-row">
        <label>Date Prepared</label>
        <div class="date-mask-wrap">
          <input type="text" class="dateMaskDisplay" data-real="datePreparedReal" data-label="Date Prepared" placeholder="mm/dd/yyyy" inputmode="numeric" autocomplete="off" maxlength="10"
                 value="<?= ggDisplayDate($record['date_prepared'] ?? '') ?>">
          <input type="hidden" name="date_prepared" id="datePreparedReal" value="<?= htmlspecialchars(ggIsoOrEmpty($record['date_prepared'] ?? '')) ?>">
          <?= dateMaskIconSvg() ?>
        </div>
      </div>
    </div>

    <!-- ============== BIRTH fields ============== -->
    <div id="fields-birth">
      <div class="form-row"><label>Name of Child *</label><input type="text" name="person_name" autocomplete="off" value="<?= htmlspecialchars($type === 'birth' ? $personNameDisplay : '') ?>" placeholder="Given Middle Surname (e.g. Aldrin Piloton Sain)"></div>
      <div class="form-row">
        <label>Date of Birth</label>
        <div class="date-mask-wrap">
          <input type="text" class="dateMaskDisplay" data-real="dateOfEventReal" data-label="Date of Birth" placeholder="mm/dd/yyyy" inputmode="numeric" autocomplete="off" maxlength="10"
                 value="<?= ($type === 'birth' && !empty($record['date_of_event'])) ? ggDisplayDate($record['date_of_event']) : '' ?>">
          <?= dateMaskIconSvg() ?>
        </div>
      </div>
      <div class="form-grid-2">
        <div class="form-row"><label>Name of Mother</label><input type="text" name="mother_name" autocomplete="off" value="<?= htmlspecialchars($type === 'birth' ? gg_display_name($record['mother_name'] ?? '') : '') ?>" placeholder="Given Middle Surname (e.g. Aldrin Piloton Sain)"></div>
        <div class="form-row"><label>Name of Father</label><input type="text" name="father_name" autocomplete="off" value="<?= htmlspecialchars($type === 'birth' ? gg_display_name($record['father_name'] ?? '') : '') ?>" placeholder="Given Middle Surname (e.g. Aldrin Piloton Sain)"></div>
      </div>
    </div>

    <!-- ============== MARRIAGE fields ============== -->
    <div id="fields-marriage">
      <div class="form-grid-2">
        <div class="form-row"><label>Name of Husband *</label><input type="text" name="person_name" autocomplete="off" value="<?= htmlspecialchars($type === 'marriage' ? $personNameDisplay : '') ?>" placeholder="Given Middle Surname (e.g. Aldrin Piloton Sain)"></div>
        <div class="form-row"><label>Name of Wife</label><input type="text" name="spouse_name" autocomplete="off" value="<?= htmlspecialchars($spouseNameDisplay) ?>" placeholder="Given Middle Surname (e.g. Aldrin Piloton Sain)"></div>
      </div>
      <div class="form-row">
        <label>Date of Marriage</label>
        <div class="date-mask-wrap">
          <input type="text" class="dateMaskDisplay" data-real="dateOfEventReal" data-label="Date of Marriage" placeholder="mm/dd/yyyy" inputmode="numeric" autocomplete="off" maxlength="10"
                 value="<?= ($type === 'marriage' && !empty($record['date_of_event'])) ? ggDisplayDate($record['date_of_event']) : '' ?>">
          <?= dateMaskIconSvg() ?>
        </div>
      </div>
      <div class="form-grid-2">
        <div class="form-row"><label>Name of Mother (H)</label><input type="text" name="mother_name" autocomplete="off" value="<?= htmlspecialchars($type === 'marriage' ? gg_display_name($record['mother_name'] ?? '') : '') ?>" placeholder="Given Middle Surname (e.g. Aldrin Piloton Sain)"></div>
        <div class="form-row"><label>Name of Father (H)</label><input type="text" name="father_name" autocomplete="off" value="<?= htmlspecialchars($type === 'marriage' ? gg_display_name($record['father_name'] ?? '') : '') ?>" placeholder="Given Middle Surname (e.g. Aldrin Piloton Sain)"></div>
      </div>
      <div class="form-grid-2">
        <div class="form-row"><label>Name of Mother (W)</label><input type="text" name="wife_mother_name" autocomplete="off" value="<?= htmlspecialchars(gg_display_name($record['wife_mother_name'] ?? '')) ?>" placeholder="Given Middle Surname (e.g. Aldrin Piloton Sain)"></div>
        <div class="form-row"><label>Name of Father (W)</label><input type="text" name="wife_father_name" autocomplete="off" value="<?= htmlspecialchars(gg_display_name($record['wife_father_name'] ?? '')) ?>" placeholder="Given Middle Surname (e.g. Aldrin Piloton Sain)"></div>
      </div>
    </div>

    <!-- ============== DEATH fields ============== -->
    <div id="fields-death">
      <div class="form-grid-2">
        <div class="form-row"><label>Name of Deceased *</label><input type="text" name="person_name" autocomplete="off" value="<?= htmlspecialchars($type === 'death' ? $personNameDisplay : '') ?>" placeholder="Given Middle Surname (e.g. Aldrin Piloton Sain)"></div>
        <div class="form-row">
          <label>Sex</label>
          <select name="sex" id="deathSex">
            <option value="male" <?= ($record['sex'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
            <option value="female" <?= ($record['sex'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
          </select>
        </div>
      </div>
      <div class="form-grid-2">
        <div class="form-row">
          <label>Date of Death</label>
          <div class="date-mask-wrap">
            <input type="text" class="dateMaskDisplay" data-real="dateOfEventReal" data-label="Date of Death" placeholder="mm/dd/yyyy" inputmode="numeric" autocomplete="off" maxlength="10"
                   value="<?= ($type === 'death' && !empty($record['date_of_event'])) ? ggDisplayDate($record['date_of_event']) : '' ?>">
            <?= dateMaskIconSvg() ?>
          </div>
        </div>
        <div class="form-row">
          <label>Date of Birth</label>
          <div class="date-mask-wrap">
            <input type="text" class="dateMaskDisplay" data-real="dateOfBirthReal" data-label="Date of Birth" data-not-after="dateOfEventReal" placeholder="mm/dd/yyyy" inputmode="numeric" autocomplete="off" maxlength="10"
                   value="<?= !empty($record['date_of_birth']) ? ggDisplayDate($record['date_of_birth']) : '' ?>">
            <input type="hidden" name="date_of_birth" id="dateOfBirthReal" value="<?= htmlspecialchars(ggIsoOrEmpty($record['date_of_birth'] ?? '')) ?>">
            <?= dateMaskIconSvg() ?>
          </div>
        </div>
      </div>
      <div class="form-grid-2">
        <div class="form-row"><label>Age at Time of Death</label><input type="text" name="age_at_death" autocomplete="off" id="ageAtDeathInput" value="<?= htmlspecialchars($record['age_at_death'] ?? '') ?>" placeholder="0 years"></div>
        <div class="form-row">
          <label>Civil Status</label>
          <select name="civil_status" id="deathCivilStatus">
            <option value="">-- Select --</option>
            <?php foreach (['single' => 'Single', 'married' => 'Married', 'widowed' => 'Widowed', 'divorced' => 'Divorced'] as $val => $label): ?>
              <option value="<?= $val ?>" <?= ($record['civil_status'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row"><label>Place of Death</label><input type="text" name="place_of_event" autocomplete="off" id="placeOfDeathInput" value="<?= htmlspecialchars($record['place_of_event'] ?? '') ?>"></div>
      <div class="form-grid-2">
        <div class="form-row"><label>Name of Father</label><input type="text" name="father_name" autocomplete="off" value="<?= htmlspecialchars($type === 'death' ? gg_display_name($record['father_name'] ?? '') : '') ?>" placeholder="Given Middle Surname (e.g. Aldrin Piloton Sain)"></div>
        <div class="form-row"><label>Maiden Name of Mother</label><input type="text" name="mother_name" autocomplete="off" value="<?= htmlspecialchars($type === 'death' ? gg_display_name($record['mother_name'] ?? '') : '') ?>" placeholder="Given Middle Surname (e.g. Aldrin Piloton Sain)"></div>
      </div>
      <div class="form-row"><label>Cause of Death</label><input type="text" name="cause_of_death" autocomplete="off" id="causeOfDeathInput" value="<?= htmlspecialchars($record['cause_of_death'] ?? '') ?>"></div>
    </div>

    <!-- One shared real <input type="date"> for the "main event date" -
         Date of Birth (Birth) / Date of Marriage (Marriage) / Date of Death
         (Death) - only ONE of the 3 masked displays above is visible at a
         time, and they all point at this same hidden real field. -->
    <input type="hidden" name="date_of_event" id="dateOfEventReal" value="<?= htmlspecialchars(ggIsoOrEmpty($record['date_of_event'] ?? '')) ?>">

    <div style="display:flex;gap:10px;margin-top:8px;">
      <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save Changes' : 'Add Record' ?></button>
      <a href="civil-registry.php" class="btn btn-outline">Cancel</a>
    </div>
  </form>
</div>

<?php
/** Small calendar-icon SVG reused next to every masked date field. */
function dateMaskIconSvg(): string
{
    return '<svg class="date-mask-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" width="16" height="16">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008H14.25v-.008Zm0 2.25h.008v.008H14.25V15Zm0 2.25h.008v.008H14.25v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z" />
      </svg>';
}
?>

<link rel="stylesheet" href="assets/css/gg-datepicker.css">
<script src="assets/js/gg-datepicker.js"></script>

<script>
(function () {
  // --------------------------------------------------------------
  // Show only the field group for the currently-selected Registry Type.
  // --------------------------------------------------------------
  var registryType = document.getElementById('registryType');
  var groups = { birth: document.getElementById('fields-birth'), marriage: document.getElementById('fields-marriage'), death: document.getElementById('fields-death') };

  function syncTypeFields() {
    Object.keys(groups).forEach(function (t) {
      var on = (registryType.value === t);
      groups[t].style.display = on ? '' : 'none';
      // IMPORTANT: also DISABLE every field of the hidden groups. The three groups reuse the same
      // field names (person_name, mother_name, father_name ...); hidden-but-enabled fields were still
      // submitted and the EMPTY copy from the last group (Death) overwrote what was typed in the
      // visible one => "Registry Type, Registry No., and Name are required" even when all was filled in.
      groups[t].querySelectorAll('input, select, textarea').forEach(function (el) { el.disabled = !on; });
    });
    // A different Registry Type = a different certificate: the next scan may fill the now-visible fields again
    // (the scan code lives in another script block, so it exposes this small hook)
    if (window.ggResetScanKeys) window.ggResetScanKeys();
  }
  registryType.addEventListener('change', syncTypeFields);
  syncTypeFields();

  // Date fields (Date Prepared / Date of Birth|Marriage|Death): typed mm/dd/yyyy +
  // calendar popup, validation and sync to the hidden ISO inputs are all handled by
  // assets/js/gg-datepicker.js (auto-initialises every .dateMaskDisplay).
})();
</script>

<!-- Client-side (in-browser) OCR - reads the certificate photo directly on
     the staff's computer via WebAssembly, so scanning works even when the
     PHP server itself has no Tesseract binary installed (e.g. a plain
     XAMPP setup on Windows with nothing extra configured). Needs internet
     access once to download the engine/language data; after that first
     load the browser caches it. If this can't load at all (no internet),
     scanning silently falls back to whatever the server can do on its own. -->
<script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>

<!-- Client-side (in-browser) OCR - reads the certificate photo directly on
     the staff's computer via WebAssembly, so scanning works even when the
     PHP server itself has no Tesseract binary installed (e.g. a plain
     XAMPP setup on Windows with nothing extra configured). Needs internet
     access once to download the engine/language data; after that first
     load the browser caches it. If this can't load at all (no internet),
     scanning silently falls back to whatever the server can do on its own. -->
<script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
<script src="assets/js/cert-ocr-client.js"></script>

<script>
(function () {
  // "📷 SCAN FRONT" / "📷 SCAN BACK" - Certificate Scan section on THIS
  // Add/Edit Record form. Uploads straight to civil-registry-scan-inline.php
  // (one side at a time) and auto-fills the plain fields already on this
  // form. Never clobbers a value the staff already typed, or a value the
  // OTHER side's scan already filled in - first result for a given field
  // wins; everything stays editable afterward.
  var LOADING_MESSAGES = ['Scanning certificate...', 'Enhancing image...', 'Reading certificate...', 'Extracting fields...', 'Checking OCR...', 'Almost done...'];
  var scanFilledKeys = {};
  window.ggResetScanKeys = function () { scanFilledKeys = {}; };

  /** Runs in-browser OCR on up to 2 photo Files (shared with Bulk Scan -
   *  see assets/js/cert-ocr-client.js) and shows live % progress in
   *  statusEl while it works. */
  async function recognizeInBrowser(files, statusEl) {
    return window.CertOcrClient.recognize(files, function (pct) {
      if (statusEl) statusEl.textContent = 'Reading the photo in the browser... ' + pct + '%';
    });
  }
  var sexTouched = false, civilStatusTouched = false;

  var sexSelectEl = document.getElementById('deathSex');
  if (sexSelectEl) sexSelectEl.addEventListener('change', function () { sexTouched = true; });
  var civilStatusEl = document.getElementById('deathCivilStatus');
  if (civilStatusEl) civilStatusEl.addEventListener('change', function () { civilStatusTouched = true; });

  var certDataInput = document.getElementById('hiddenCertificateData');
  var mergedCertFields = {};
  try { if (certDataInput && certDataInput.value) mergedCertFields = JSON.parse(certDataInput.value); } catch (e) { mergedCertFields = {}; }

  function flash(el) {
    el.style.transition = 'background-color 0.3s';
    el.style.backgroundColor = '#d7f5dc';
    setTimeout(function () { el.style.backgroundColor = ''; }, 1800);
  }

  // Mixed Case for names: ALDRIN PILOTON SAIN / aldrin piloton sain -> Aldrin Piloton Sain
  function ggTitleCase(str) {
    str = String(str || '').replace(/\s+/g, ' ').trim().toLowerCase();
    return str.replace(/(^|[\s\-.'’(\/])(\p{L})/gu, function (m, a, b) { return a + b.toUpperCase(); })
              .replace(/\b(Ii|Iii|Iv)\b/g, function (m) { return m.toUpperCase(); });
  }
  // Old "SURNAME, GIVEN MIDDLE" text -> "Given Middle Surname"
  function ggGivenFirst(str) {
    str = String(str || '').trim();
    var i = str.indexOf(',');
    if (i !== -1) str = (str.slice(i + 1).trim() + ' ' + str.slice(0, i).trim()).trim();
    return ggTitleCase(str);
  }
  var GG_NAME_FIELDS = ['person_name', 'mother_name', 'father_name', 'spouse_name', 'wife_father_name', 'wife_mother_name'];
  document.addEventListener('focusout', function (e) {
    var t = e.target;
    if (!t || !t.name) return;
    if (GG_NAME_FIELDS.indexOf(t.name) !== -1 && t.value) t.value = ggGivenFirst(t.value);
    else if ((t.name === 'barangay' || t.name === 'place_of_event') && t.value) t.value = ggTitleCase(t.value);
  });

  function applyText(selector, key, val) {
    if (!val) return false;
    var m = selector.match(/name="([^"]+)"/);
    if (m && GG_NAME_FIELDS.indexOf(m[1]) !== -1) val = ggGivenFirst(val);
    else if (m && (m[1] === 'barangay' || m[1] === 'place_of_event')) val = ggTitleCase(val);
    // Several groups (Birth / Marriage / Death) reuse the same field names - fill the one in the ACTIVE
    // group (the enabled one), not simply the first one in the page.
    var all = document.querySelectorAll(selector), el = null;
    for (var i = 0; i < all.length; i++) { if (!all[i].disabled) { el = all[i]; break; } }
    if (!el) el = all[0] || null;
    if (!el || scanFilledKeys[key]) return false;
    if (el.value && el.value.trim() !== '') return false;
    el.value = val;
    scanFilledKeys[key] = true;
    flash(el);
    return true;
  }

  function setDateField(realId, iso) {
    var real = document.getElementById(realId);
    if (!real) return;
    real.value = iso;
    document.querySelectorAll('.dateMaskDisplay[data-real="' + realId + '"]').forEach(function (display) {
      var parts = iso.split('-');
      if (parts.length === 3) display.value = parts[1] + '/' + parts[2] + '/' + parts[0];
    });
    if (window.GGDatePicker) window.GGDatePicker.refresh(realId); // validate (future/invalid) + sync shared fields
    flash(real);
  }

  function startLoadingCycle(statusEl) {
    var i = 0;
    statusEl.style.color = 'var(--text-muted)';
    statusEl.textContent = LOADING_MESSAGES[0];
    return setInterval(function () {
      i = (i + 1) % LOADING_MESSAGES.length;
      statusEl.textContent = LOADING_MESSAGES[i];
    }, 900);
  }

  // Clear a stale "Please choose a photo first" message the
  // moment staff picks a new file, so it never sits there looking like the
  // scan still failed after they've already fixed it.
  ['front', 'back'].forEach(function (side) {
    ['1', '2'].forEach(function (n) {
      var fileInput = document.getElementById('scanFile-' + side + '-' + n);
      var statusEl = document.getElementById('scanStatus-' + side);
      if (fileInput && statusEl) {
        fileInput.addEventListener('change', function () { statusEl.textContent = ''; });
      }
    });
  });

  window.scanSide = async function (side, btnEl) {
    var file1 = document.getElementById('scanFile-' + side + '-1');
    var file2 = document.getElementById('scanFile-' + side + '-2');
    var statusEl = document.getElementById('scanStatus-' + side);
    var previewEl = document.getElementById('scanPreview-' + side);

    if (!file1.files[0]) {
      statusEl.style.color = '#a3271f';
      statusEl.textContent = 'Please choose a photo first (' + side + ').';
      return;
    }

    var registryTypeEl = document.getElementById('registryType');
    var registryType = registryTypeEl ? registryTypeEl.value : 'birth';
    var oldModeEl = document.getElementById('oldCertificateMode');

    btnEl.disabled = true;
    var originalLabel = btnEl.textContent;
    statusEl.style.color = 'var(--text-muted)';

    // Try reading the photo(s) IN THE BROWSER first (Tesseract.js) - this
    // is what actually makes SCAN work when the server has no Tesseract
    // installed. Skipped entirely when "Old Certificate Mode" is checked,
    // since that mode's multi-pass image enhancement only exists server-side.
    var clientText = '';
    if (!(oldModeEl && oldModeEl.checked)) {
      clientText = await recognizeInBrowser([file1.files[0], file2.files[0]], statusEl);
    }

    var fd = new FormData();
    fd.append('side', side);
    fd.append('registry_type', registryType);
    if (oldModeEl && oldModeEl.checked) fd.append('old_certificate_mode', '1');
    fd.append('certificate_image', file1.files[0]);
    if (file2.files[0]) fd.append('certificate_image2', file2.files[0]);
    if (clientText) fd.append('ocr_text_client', clientText);

    var cycle = startLoadingCycle(statusEl);

    try {
      var res = await fetch('civil-registry-scan-inline.php', { method: 'POST', body: fd });
      var data = await res.json();
      clearInterval(cycle);

      if (!data.ok) {
        statusEl.style.color = '#a3271f';
        statusEl.textContent = data.error || 'An error occurred while scanning.';
        return;
      }

      if (side === 'front') {
        document.getElementById('hidden-scan_image_path').value = data.image_path || '';
        document.getElementById('hidden-scan_image_path_2').value = data.image_path_2 || '';
      } else {
        document.getElementById('hidden-scan_image_path_back').value = data.image_path || '';
        document.getElementById('hidden-scan_image_path_back_2').value = data.image_path_2 || '';
      }
      if (data.image_path && previewEl) {
        previewEl.innerHTML = '<img src="../' + data.image_path + '" style="max-width:100%;border:1px solid var(--border);border-radius:6px;">';
      }

      var rawWrap = document.getElementById('scanRawWrap-' + side);
      var rawTextEl = document.getElementById('scanRawText-' + side);
      if (rawWrap && rawTextEl) {
        if (data.raw_ocr_text) {
          rawTextEl.textContent = data.raw_ocr_text;
          rawWrap.style.display = '';
        } else {
          rawWrap.style.display = 'none';
        }
      }

      if (data.warning) {
        statusEl.style.color = '#8a6d00';
        statusEl.textContent = '⚠ ' + data.warning;
        return;
      }

      var certFields = data.cert_fields || {};
      Object.keys(certFields).forEach(function (k) { if (!mergedCertFields[k]) mergedCertFields[k] = certFields[k]; });
      if (certDataInput) certDataInput.value = JSON.stringify(mergedCertFields);

      var legacy = data.legacy || {};
      var common = data.common || {};
      var filledCount = 0;

      // "Name of Child / Husband / Deceased" is one box now - build
      // "Given Middle Surname" from the 3 separate OCR-read pieces
      // (skipping any that came back blank) instead of filling 3 inputs.
      var personNameParts = [legacy.first_name, legacy.middle_name, legacy.last_name]
        .filter(function (v) { return v && String(v).trim() !== ''; });
      if (personNameParts.length) {
        if (applyText('[name="person_name"]', 'person_name', personNameParts.join(' '))) filledCount++;
      }
      if (applyText('[name="father_name"]', 'father_name', legacy.father_name)) filledCount++;
      if (applyText('[name="mother_name"]', 'mother_name', legacy.mother_name)) filledCount++;
      // legacy.spouse_name arrives from OCR as "Surname, Given Middle" (the
      // same older convention father_name/mother_name still use) - flip it
      // to "Given Middle Surname" for the Name of Wife box, same as the
      // Edit-load conversion server-side (splitCanonicalFullName()).
      var spouseNameForBox = legacy.spouse_name;
      if (spouseNameForBox && spouseNameForBox.indexOf(',') !== -1) {
        var spouseCommaParts = spouseNameForBox.split(',');
        var spouseLast = spouseCommaParts[0].trim();
        var spouseGiven = spouseCommaParts.slice(1).join(',').trim();
        spouseNameForBox = (spouseGiven + ' ' + spouseLast).trim();
      }
      if (applyText('[name="spouse_name"]', 'spouse_name', spouseNameForBox)) filledCount++;
      if (applyText('[name="wife_father_name"]', 'wife_father_name', legacy.wife_father_name)) filledCount++;
      if (applyText('[name="wife_mother_name"]', 'wife_mother_name', legacy.wife_mother_name)) filledCount++;
      if (applyText('[name="age_at_death"]', 'age_at_death', legacy.age_at_death)) filledCount++;
      if (applyText('[name="place_of_event"]', 'place_of_event', legacy.place_of_event)) filledCount++;
      if (applyText('[name="cause_of_death"]', 'cause_of_death', legacy.cause_of_death)) filledCount++;
      if (applyText('[name="registry_number"]', 'registry_number', common.registry_number)) filledCount++;
      if (applyText('[name="page_no"]', 'page_no', common.page_no)) filledCount++;
      if (applyText('[name="barangay"]', 'barangay', common.barangay)) filledCount++;

      var dateOfEventReal = document.getElementById('dateOfEventReal');
      if (legacy.date_of_event && dateOfEventReal && !scanFilledKeys.date_of_event && !dateOfEventReal.value) {
        setDateField('dateOfEventReal', legacy.date_of_event);
        scanFilledKeys.date_of_event = true;
        filledCount++;
      }
      var dobReal = document.getElementById('dateOfBirthReal');
      if (legacy.date_of_birth && dobReal && !scanFilledKeys.date_of_birth && !dobReal.value) {
        setDateField('dateOfBirthReal', legacy.date_of_birth);
        scanFilledKeys.date_of_birth = true;
        filledCount++;
      }
      var dpReal = document.getElementById('datePreparedReal');
      if (common.date_prepared && dpReal && !scanFilledKeys.date_prepared && !dpReal.value) {
        setDateField('datePreparedReal', common.date_prepared);
        scanFilledKeys.date_prepared = true;
        filledCount++;
      }

      if (legacy.sex && sexSelectEl && !scanFilledKeys.sex && !sexTouched) {
        sexSelectEl.value = legacy.sex;
        scanFilledKeys.sex = true;
        filledCount++;
      }
      if (legacy.civil_status && civilStatusEl && !scanFilledKeys.civil_status && !civilStatusTouched) {
        civilStatusEl.value = legacy.civil_status;
        scanFilledKeys.civil_status = true;
        filledCount++;
      }

      var lowConfCount = 0;
      var confidence = data.confidence || {};
      Object.keys(confidence).forEach(function (k) { if (confidence[k].status === 'LOW') lowConfCount++; });

      statusEl.style.color = lowConfCount > 0 ? '#8a6d00' : '#1a7a2e';
      statusEl.textContent = filledCount > 0
        ? ('✓ Scanned - ' + filledCount + ' field(s) auto-filled' + (lowConfCount > 0 ? ' (' + lowConfCount + ' with low OCR confidence, please review)' : '.'))
        : 'The photo was scanned but no new fields were filled in (the fields already have values, or nothing could be read).';
    } catch (e) {
      clearInterval(cycle);
      statusEl.style.color = '#a3271f';
      statusEl.textContent = 'A connection error occurred. Please try again.';
    } finally {
      btnEl.disabled = false;
      btnEl.textContent = originalLabel;
    }
  };
})();
</script>

<?php require_once __DIR__ . '/includes/staff-footer.php'; ?>