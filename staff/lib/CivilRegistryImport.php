<?php
require_once __DIR__ . '/SimpleXlsxReader.php';
require_once __DIR__ . '/../includes/name-format.php';

/**
 * Column headers we recognize in the uploaded Excel/CSV file, keyed by the
 * civil_registry_records column they map to. Matching is case-insensitive
 * and ignores spaces/underscores/punctuation, so "First Name", "first_name"
 * and "FIRSTNAME" all match the same thing.
 *
 * This supports BOTH the real MCRO register formats (one full-name column
 * per person, e.g. "Name of Child") AND the older split First/Last Name
 * style, so whichever format a staff member's spreadsheet is already in,
 * it should just work.
 */
const IMPORT_FIELD_CANDIDATES = [
    'registry_type'     => ['registrytype', 'type', 'recordtype'],
    'registry_number'   => ['registrynumber', 'registryno', 'regno', 'regnumber', 'controlnumber', 'certificateno', 'certno'],
    'book_no'           => ['bookno', 'book'],
    'page_no'           => ['pageno', 'page'],
    'city_municipality' => ['citymunicipality', 'city', 'municipality'],
    'date_prepared'     => ['dateprepared', 'prepared'],
    'barangay'          => ['barangay', 'brgy', 'barangaydate', 'brgyname', 'barangayname'],
    'zone'              => ['zone', 'purok', 'sitio', 'zonepurok', 'purokzone', 'purokzonesitio'],

    // Full-name style columns (real MCRO register format) - each implies
    // a specific registry_type, handled in import_infer_registry_type().
    'name_of_child'     => ['nameofchild', 'childname'],
    'name_of_deceased'  => ['nameofdeceased', 'deceasedname', 'nameofthedeceased'],
    'name_of_wife'      => ['nameofwife', 'wifename', 'wife'],
    'name_of_husband'   => ['nameofhusband', 'husbandname', 'husband'],
    'full_name'         => ['fullname', 'name', 'nameofperson'],

    // Older split-name style columns (still supported for backward compatibility).
    'first_name'        => ['firstname', 'fname', 'givenname'],
    'middle_name'       => ['middlename', 'mname'],
    'last_name'         => ['lastname', 'lname', 'surname'],
    'suffix'            => ['suffix', 'extname', 'extensionname'],

    'sex'               => ['sex', 'gender'],
    'date_of_event'     => [
        'dateofevent', 'date', 'eventdate', 'dateregistered',
        'dateofbirth', 'birthdate', 'dateofmarriage', 'dateofdeath',
    ],
    'place_of_event'    => ['placeofevent', 'place', 'placeofbirth', 'placeofmarriage', 'placeofdeath', 'street'],
    'father_name'       => ['fathername', 'father', 'fathersname', 'nameoffather'],
    'mother_name'       => ['mothername', 'mother', 'mothersname', 'nameofmother'],
    'spouse_name'       => ['spousename', 'spouse', 'spousesname'],
    'remarks'           => ['remarks', 'notes', 'remark'],
];

function import_normalize_key(string $s): string
{
    return strtolower(preg_replace('/[^a-z0-9]/i', '', $s));
}

function import_build_column_map(array $headerRow): array
{
    $map = [];
    foreach ($headerRow as $colIndex => $header) {
        $norm = import_normalize_key((string) $header);
        if ($norm === '') continue;
        foreach (IMPORT_FIELD_CANDIDATES as $field => $candidates) {
            if (isset($map[$field])) continue; // first match wins
            if (in_array($norm, $candidates, true)) {
                $map[$field] = $colIndex;
                break;
            }
        }
    }
    return $map;
}

/**
 * Figures out which registry type this file/sheet is for. If an explicit
 * "Registry Type" column exists, that always wins. Otherwise it's inferred
 * from which full-name-style column is present - this is what lets a
 * staff member upload their birth.xlsx / marriage.xlsx / death.xlsx
 * straight from the register book, with no extra "pick a type" step.
 */
function import_infer_registry_type(array $colMap): ?string
{
    if (isset($colMap['registry_type'])) return null; // read per-row instead
    if (isset($colMap['name_of_child'])) return 'birth';
    if (isset($colMap['name_of_deceased'])) return 'death';
    if (isset($colMap['name_of_wife']) || isset($colMap['name_of_husband'])) return 'marriage';
    return null; // fall back to per-row / default
}

function import_normalize_registry_type(string $v): string
{
    $v = strtolower(trim($v));
    if (str_starts_with($v, 'birth') || $v === 'b') return 'birth';
    if (str_starts_with($v, 'marr') || $v === 'm') return 'marriage';
    if (str_starts_with($v, 'death') || $v === 'd') return 'death';
    return 'birth'; // sensible default
}

function import_normalize_sex(string $v): ?string
{
    $v = strtolower(trim($v));
    if (in_array($v, ['male', 'm'], true)) return 'male';
    if (in_array($v, ['female', 'f'], true)) return 'female';
    return null;
}

function import_normalize_date(string $v): ?string
{
    $v = trim($v);
    if ($v === '') return null;

    // Pure-number cells from Excel are date "serials" (days since 1899-12-30).
    if (preg_match('/^\d+(\.\d+)?$/', $v)) {
        $serial = (float) $v;
        if ($serial > 1 && $serial < 60000) {
            return excel_serial_to_date($serial);
        }
    }

    $ts = strtotime($v);
    if ($ts === false) return null;
    return date('Y-m-d', $ts);
}

/**
 * Splits a single full-name string into first/middle/last for storage.
 *
 * The app's standard name format everywhere else (Civil Registry Records
 * table, Add Record form, Family Tree, Map search) is
 * "Last Name, First Name Middle Name" - so a name column value like
 * "Alfeche, Estrellita Caballero" is handled as: last = Alfeche,
 * first = Estrellita, middle = Caballero.
 *
 * If there's no comma at all (some staff spreadsheets are typed as plain
 * "First Middle Last" with no comma), it falls back to a best-effort
 * word-position split: first word = first name, last word = last name,
 * everything between = middle name.
 */
function import_split_full_name(string $fullName): array
{
    $fullName = trim($fullName);

    if (str_contains($fullName, ',')) {
        [$last, $given] = array_pad(explode(',', $fullName, 2), 2, '');
        $last = trim($last);
        $givenParts = array_values(array_filter(preg_split('/\s+/', trim($given)), fn($p) => $p !== ''));

        if (count($givenParts) === 0) return ['first' => '', 'middle' => null, 'last' => $last];

        $first = $givenParts[0];
        $middleParts = array_slice($givenParts, 1);

        return [
            'first' => $first,
            'middle' => $middleParts ? implode(' ', $middleParts) : null,
            'last' => $last,
        ];
    }

    // No comma at all = no surname on file for this person (per the actual
    // convention in the source records: a name is only ever written
    // "Surname, Given Names" when a surname is known). Previously this
    // branch guessed the LAST WORD was the surname whenever there was no
    // comma - which was wrong: "Jhon" (one word) got duplicated as both
    // first AND last name, and "Mark Amoyo" (two words, First + Middle,
    // no surname) had "Amoyo" wrongly carved off as a surname. Now: with
    // no comma, the WHOLE string is First (+ Middle) and Last stays empty.
    $parts = preg_split('/\s+/', $fullName);
    $parts = array_values(array_filter($parts, fn($p) => $p !== ''));

    if (count($parts) === 0) return ['first' => '', 'middle' => null, 'last' => ''];

    $first = $parts[0];
    $middleParts = array_slice($parts, 1);

    return [
        'first' => $first,
        'middle' => $middleParts ? implode(' ', $middleParts) : null,
        'last' => '',
    ];
}

/**
 * Treats common "not applicable" placeholder text (typed in when a
 * father/mother/spouse is unknown) as if the field were left blank,
 * instead of storing the literal placeholder as if it were a real name.
 * Without this, everyone whose Father's/Mother's Name was typed as
 * "NOT APPLICABLE" ends up matching each other as false "siblings" (they
 * all share the exact same father_name + mother_name TEXT), and the UI
 * would otherwise show a nonsense "Father: Not Applicable" card.
 */
function import_clean_placeholder_name(?string $text): ?string
{
    $text = trim((string) $text);
    if ($text === '') return null;

    $normalized = strtoupper(preg_replace('/[^A-Z]/i', '', $text));
    $placeholders = ['NOTAPPLICABLE', 'NA', 'NONE', 'UNKNOWN', 'UNKNOWNPARENT', 'NILA', 'WALA'];

    return in_array($normalized, $placeholders, true) ? null : $text;
}

function import_parse_uploaded_file(string $tmpPath, string $originalName): array
{
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if ($ext === 'xlsx') {
        $rows = read_xlsx_rows($tmpPath);
    } elseif ($ext === 'csv') {
        $rows = read_csv_rows($tmpPath);
    } elseif ($ext === 'xls') {
        throw new RuntimeException('The old .xls format is not supported. Please open the file in Excel and "Save As" .xlsx or .csv, then upload again.');
    } else {
        throw new RuntimeException('Unsupported file type. Please upload a .xlsx or .csv file.');
    }

    // Drop fully blank rows.
    $rows = array_values(array_filter($rows, function ($r) {
        foreach ($r as $cell) {
            if (trim((string) $cell) !== '') return true;
        }
        return false;
    }));

    if (count($rows) < 1) {
        throw new RuntimeException('The file appears to be empty.');
    }

    $header = array_shift($rows);
    $colMap = import_build_column_map($header);
    $inferredType = import_infer_registry_type($colMap);

    // A file needs SOME way to know each person's name - either a
    // full-name-style column (Name of Child / Deceased / Wife / Husband /
    // Full Name), or the older split First Name + Last Name pair.
    $hasFullNameColumn = isset($colMap['name_of_child']) || isset($colMap['name_of_deceased'])
        || isset($colMap['name_of_wife']) || isset($colMap['name_of_husband']) || isset($colMap['full_name']);
    $hasSplitNameColumns = isset($colMap['first_name']) && isset($colMap['last_name']);

    if (!$hasFullNameColumn && !$hasSplitNameColumns) {
        throw new RuntimeException(
            'Could not find a name column in this file. Expected one of: "Name of Child", "Name of Deceased", ' .
            '"Name of Wife" / "Name of Husband", "Full Name", or separate "First Name" + "Last Name" columns.'
        );
    }

    $valid = [];
    $invalid = [];

    foreach ($rows as $rowNum => $row) {
        $get = fn($field) => isset($colMap[$field]) ? trim((string) ($row[$colMap[$field]] ?? '')) : '';
        $excelRowNumber = $rowNum + 2; // +1 for header row, +1 for 1-based numbering

        // ---- Determine the primary full name + registry type for this row ----
        $primaryFullName = '';
        $spouseFromColumn = null;
        $registryType = $inferredType;

        if ($get('name_of_child') !== '') {
            $primaryFullName = $get('name_of_child');
            $registryType = $registryType ?: 'birth';
        } elseif ($get('name_of_deceased') !== '') {
            $primaryFullName = $get('name_of_deceased');
            $registryType = $registryType ?: 'death';
        } elseif ($get('name_of_wife') !== '' || $get('name_of_husband') !== '') {
            // Marriage rows list both spouses - the wife becomes the
            // record's own name, the husband is stored as spouse_name.
            $primaryFullName = $get('name_of_wife') ?: $get('name_of_husband');
            $spouseFromColumn = $get('name_of_wife') !== '' ? ($get('name_of_husband') ?: null) : null;
            $registryType = $registryType ?: 'marriage';
        } elseif ($get('full_name') !== '') {
            $primaryFullName = $get('full_name');
        } elseif ($get('first_name') !== '' || $get('last_name') !== '') {
            // Suffix (Jr., Sr., III, ...) is intentionally left out of the
            // combined name here - it's stored in its own column but no
            // longer folded into full_name/display, matching the Add
            // Record form's behavior.
            $primaryFullName = trim($get('first_name') . ' ' . $get('middle_name') . ' ' . $get('last_name'));
        }

        if (!$registryType) {
            $registryType = isset($colMap['registry_type']) ? import_normalize_registry_type($get('registry_type')) : 'birth';
        }

        if ($primaryFullName === '') {
            $invalid[] = ['row' => $excelRowNumber, 'reason' => 'Missing name', 'record' => ['first_name' => '', 'last_name' => '']];
            continue;
        }

        // Prefer explicit split columns if the file also provided them
        // (more accurate than our best-effort auto-split); otherwise
        // derive first/middle/last from the full name for display/search.
        if ($get('first_name') !== '' || $get('last_name') !== '') {
            $firstName = $get('first_name');
            $middleName = $get('middle_name') ?: null;
            $lastName = $get('last_name');
            $suffix = $get('suffix') ?: null;
        } else {
            $split = import_split_full_name($primaryFullName);
            $firstName = $split['first'];
            $middleName = $split['middle'];
            $lastName = $split['last'];
            $suffix = null;
        }

        $record = [
            'registry_type'     => $registryType,
            'registry_number'   => $get('registry_number') ?: null,
            'book_no'           => $get('book_no') ?: null,
            'page_no'           => $get('page_no') ?: null,
            'city_municipality' => $get('city_municipality') ?: null,
            'date_prepared'     => import_normalize_date($get('date_prepared')),
            // Every name is saved Mixed Case and as "Given Middle Surname" (the sheet itself
            // may be ALL CAPS and/or "SURNAME, GIVEN MIDDLE" - both are still understood).
            'full_name'         => formatFullName($lastName, $firstName, $middleName) ?: gg_display_name($primaryFullName),
            'first_name'        => gg_title_case($firstName),
            'middle_name'       => $middleName !== null && $middleName !== '' ? gg_title_case($middleName) : null,
            'last_name'         => gg_title_case($lastName),
            'suffix'            => $suffix,
            'sex'               => import_normalize_sex($get('sex')),
            'date_of_event'     => import_normalize_date($get('date_of_event')),
            'place_of_event'    => $get('place_of_event') ? gg_title_case($get('place_of_event')) : null,
            'father_name'       => gg_clean_person_name(import_clean_placeholder_name($get('father_name'))),
            'mother_name'       => gg_clean_person_name(import_clean_placeholder_name($get('mother_name'))),
            'spouse_name'       => gg_clean_person_name(import_clean_placeholder_name($get('spouse_name') ?: $spouseFromColumn)),
            'barangay'          => $get('barangay') ?: null,
            'zone'              => $get('zone') ?: null,
            'remarks'           => $get('remarks') ?: null,
        ];

        if ($record['first_name'] === '' && $record['last_name'] === '') {
            $invalid[] = ['row' => $excelRowNumber, 'reason' => 'Missing name', 'record' => $record];
            continue;
        }

        $valid[] = ['row' => $excelRowNumber, 'record' => $record];
    }

    return ['valid' => $valid, 'invalid' => $invalid, 'colMap' => $colMap, 'header' => $header];
}

/**
 * Converts a 0-based column index back to its Excel letter (0 -> A, 1 -> B,
 * 26 -> AA, ...) - used to show the staff exactly which column in their
 * file each recognized field was read from.
 */
function import_col_index_to_letter(int $index): string
{
    $index++;
    $letter = '';
    while ($index > 0) {
        $rem = ($index - 1) % 26;
        $letter = chr(65 + $rem) . $letter;
        $index = intdiv($index - 1, 26);
    }
    return $letter;
}
