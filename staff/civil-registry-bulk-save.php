<?php
/**
 * civil-registry-bulk-save.php
 * -----------------------------
 * AJAX endpoint for the "💾 Save All" button on civil-registry-bulk-scan.php.
 *
 * Accepts ONE JSON POST body describing every row left in the review
 * table after a bulk scan, and inserts each one as its own
 * civil_registry_records row - reusing the exact same name-parsing/
 * full-name-building helpers (parseGivenMiddleSurname, formatFullName)
 * the single Add Record form uses, so a batch-saved record looks and
 * behaves identically to one added by hand.
 *
 * Request (application/json POST body):
 *   {
 *     registry_type: "birth" | "marriage" | "death",
 *     records: [
 *       {
 *         registry_number, page_no?, date_prepared?, barangay, date_of_event,
 *         person_name,                      // "Given Middle Surname"
 *         mother_name?, father_name?,        // birth
 *         spouse_name?,                      // marriage - "Given Middle Surname" too (Mixed Case on save)
 *         sex?,                              // death
 *         image_path?, certificate_data?,
 *       },
 *       ...
 *     ]
 *   }
 *
 * Response JSON:
 *   { ok:true, saved:N, saved_row_indexes:[...], failed:[{row, reason}] }
 *   or { ok:false, error }
 *
 * A single bad row (missing name, duplicate registry number, etc.) never
 * aborts the whole batch - every other valid row still gets saved, and
 * the specific failures are reported back so staff can fix just those.
 */
require_once __DIR__ . '/includes/staff-auth-guard.php';
require_once __DIR__ . '/includes/name-format.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

function respond(array $data): void
{
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['ok' => false, 'error' => 'Invalid request method.']);
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
if (!is_array($payload) || empty($payload['records']) || !is_array($payload['records'])) {
    respond(['ok' => false, 'error' => 'No records were sent.']);
}

$type = $payload['registry_type'] ?? 'birth';
if (!in_array($type, ['birth', 'marriage', 'death'], true)) $type = 'birth';

$staffId = $_SESSION['staff_id'] ?? null;

$stmtInsert = $pdo->prepare("
    INSERT INTO civil_registry_records (registry_type, registry_number, book_no, page_no,
    city_municipality, date_prepared, full_name, first_name, middle_name, last_name, suffix,
    sex, civil_status, date_of_event, date_of_birth, place_of_event,
    father_name, mother_name, spouse_name, wife_father_name, wife_mother_name,
    age_at_death, cause_of_death, barangay, zone, remarks,
    scan_image_path, scan_image_path_2, scan_image_path_back, scan_image_path_back_2,
    certificate_data, verified_by, verified_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
");

// Registry numbers within THIS batch must be unique too (not just against
// what's already in the database) - two rows both left as, say, blank or
// an identical misread number would otherwise both "succeed" and create a
// confusing duplicate pair with no way to tell them apart afterward.
$seenInBatch = [];

$saved = 0;
$savedRowIndexes = [];
$failed = [];

foreach ($payload['records'] as $i => $rec) {
    if (!is_array($rec)) {
        $failed[] = ['row' => $i, 'reason' => 'Invalid row data.'];
        continue;
    }

    $registryNumber = trim((string) ($rec['registry_number'] ?? ''));
    // Barangay in ANY case (DICKLUM / dicklum) -> exact spelling from the barangays table
    $barangay = gg_canonical_barangay($pdo, (string) ($rec['barangay'] ?? ''));
    $dateOfEvent = trim((string) ($rec['date_of_event'] ?? ''));
    $dateOfEvent = ($dateOfEvent !== '' && strtotime($dateOfEvent)) ? date('Y-m-d', strtotime($dateOfEvent)) : null;
    // Header fields of the certificate that the scan also reads (they used to be dropped on save)
    $pageNo     = trim((string) ($rec['page_no'] ?? '')) ?: null;
    $bookNo     = trim((string) ($rec['book_no'] ?? '')) ?: null;
    $cityMun    = trim((string) ($rec['city_municipality'] ?? '')) ?: null;
    $datePrep   = trim((string) ($rec['date_prepared'] ?? ''));
    $datePrep   = ($datePrep !== '' && strtotime($datePrep)) ? date('Y-m-d', strtotime($datePrep)) : null;

    [$firstName, $middleName, $lastName] = parseGivenMiddleSurname(trim((string) ($rec['person_name'] ?? '')));

    if ($firstName === '' || $lastName === '') {
        $failed[] = ['row' => $i, 'reason' => 'Name is missing or incomplete (needs at least a given name and a surname).'];
        continue;
    }
    if ($registryNumber === '') {
        $failed[] = ['row' => $i, 'reason' => 'Registry No. is missing.'];
        continue;
    }
    if (isset($seenInBatch[$registryNumber])) {
        $failed[] = ['row' => $i, 'reason' => "Registry No. \"$registryNumber\" is duplicated within this same batch (row " . ($seenInBatch[$registryNumber] + 1) . ')."'];
        continue;
    }

    // A registry number already saved in a PREVIOUS batch/record is left
    // to the database's own UNIQUE constraint (if one exists) to catch via
    // the try/catch below - deliberately not pre-checked here, since two
    // legitimately different people can share a registry number field
    // left blank/placeholder in poor-quality scans, and the single Add
    // Record form doesn't pre-block on this either.

    $sex = $civilStatus = $dateOfBirth = $fatherName = $motherName = $spouseName = null;
    $wifeFatherName = $wifeMotherName = $ageAtDeath = $placeOfEvent = $causeOfDeath = null;

    if ($type === 'birth') {
        // "Given Middle Surname", Mixed Case (old "SURNAME, GIVEN" text is flipped too)
        $motherName = gg_clean_person_name((string) ($rec['mother_name'] ?? ''));
        $fatherName = gg_clean_person_name((string) ($rec['father_name'] ?? ''));
    } elseif ($type === 'marriage') {
        $sex = 'male';
        $civilStatus = 'married';
        $wifeNameTyped = trim((string) ($rec['spouse_name'] ?? ''));
        if ($wifeNameTyped !== '') {
            [$wifeFirst, $wifeMiddle, $wifeLast] = parseGivenMiddleSurname($wifeNameTyped);
            $spouseName = formatFullName($wifeLast, $wifeFirst, $wifeMiddle);
        }
    } else { // death
        $sex = in_array($rec['sex'] ?? '', ['male', 'female'], true) ? $rec['sex'] : null;
        $dateOfBirth = null; // not collected in the bulk review table - editable later on the record's own Edit page
    }

    $fullName = formatFullName($lastName, $firstName, $middleName);
    $imagePath = trim((string) ($rec['image_path'] ?? '')) ?: null;
    $certificateData = trim((string) ($rec['certificate_data'] ?? ''));

    $data = [
        $type, $registryNumber, $bookNo, $pageNo,
        $cityMun, $datePrep,
        $fullName ?: null, $firstName, $middleName ?: null, $lastName, null,
        $sex, $civilStatus, $dateOfEvent, $dateOfBirth, $placeOfEvent,
        $fatherName, $motherName, $spouseName, $wifeFatherName, $wifeMotherName,
        $ageAtDeath, $causeOfDeath, $barangay, null, null,
        $imagePath, null, null, null,
        $certificateData ?: null, $staffId,
    ];

    try {
        $stmtInsert->execute($data);
        $seenInBatch[$registryNumber] = $i;
        $saved++;
        $savedRowIndexes[] = $i;
    } catch (Throwable $e) {
        // Report the REAL reason (it used to always say "it may already exist",
        // which hid problems that had nothing to do with duplicates).
        error_log('civil-registry-bulk-save row ' . ($i + 1) . ': ' . $e->getMessage());
        $code = $e instanceof PDOException ? (int) ($e->errorInfo[1] ?? 0) : 0;
        if ($code === 1062) {
            $dup = $pdo->prepare("SELECT record_id, registry_type, first_name, middle_name, last_name FROM civil_registry_records WHERE registry_number = ? LIMIT 1");
            $dup->execute([$registryNumber]);
            $d = $dup->fetch();
            $reason = 'Registry No. "' . $registryNumber . '" is already saved'
                    . ($d ? ' for ' . formatFullName($d['last_name'], $d['first_name'], $d['middle_name'])
                          . ' (' . $d['registry_type'] . ' record #' . $d['record_id'] . ')' : '')
                    . '. Change the Registry No. in this row, or remove the row if it is the same record.';
        } elseif ($code === 1054) {
            $reason = 'The database is missing a column (' . $e->getMessage() . '). Run the SQL files in the migrations folder, then try again.';
        } else {
            $reason = 'Could not save this record: ' . $e->getMessage();
        }
        $failed[] = ['row' => $i, 'reason' => $reason];
    }
}

respond(['ok' => true, 'saved' => $saved, 'saved_row_indexes' => $savedRowIndexes, 'failed' => $failed]);
