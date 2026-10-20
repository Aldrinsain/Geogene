<?php
/**
 * SimpleXlsxReader
 * ------------------------------------------------------------
 * A tiny, dependency-free reader for .xlsx files, built for the
 * Civil Registry Excel Import feature. An .xlsx file is just a
 * zip archive of XML files, so we read it with PHP's built-in
 * ZipArchive + SimpleXML - no Composer / PhpSpreadsheet required.
 *
 * Limitations (acceptable for simple data-entry spreadsheets):
 *  - Only reads the FIRST worksheet.
 *  - Formulas are not evaluated (only their cached value, if any).
 *  - Returns every cell as a plain string; numeric Excel date
 *    serials are left as numbers - the caller converts them
 *    (see excel_serial_to_date() below).
 */

function read_xlsx_rows(string $path): array
{
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException(
            'The PHP "zip" extension is required to read .xlsx files. ' .
            'Please enable it (extension=zip in php.ini) and restart Apache, ' .
            'or upload a .csv file instead.'
        );
    }

    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        throw new RuntimeException('Unable to open the uploaded file. Make sure it is a valid, unmodified .xlsx file.');
    }

    // ---- Resolve the first worksheet's real path via workbook.xml + rels ----
    $sheetPath = 'xl/worksheets/sheet1.xml'; // sensible fallback
    $workbookXml = $zip->getFromName('xl/workbook.xml');
    $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
    if ($workbookXml !== false && $relsXml !== false) {
        $wb = @simplexml_load_string($workbookXml);
        $rels = @simplexml_load_string($relsXml);
        if ($wb && $rels && isset($wb->sheets->sheet[0])) {
            $firstSheet = $wb->sheets->sheet[0];
            $rAttrs = $firstSheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships', true);
            $rId = (string) ($rAttrs->id ?? '');
            foreach ($rels->Relationship as $rel) {
                if ((string) $rel['Id'] === $rId) {
                    $target = ltrim((string) $rel['Target'], '/');
                    $sheetPath = str_starts_with($target, 'xl/') ? $target : 'xl/' . $target;
                    break;
                }
            }
        }
    }

    // ---- Shared strings table ----
    $sharedStrings = [];
    $sstXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($sstXml !== false) {
        $sst = @simplexml_load_string($sstXml);
        if ($sst) {
            foreach ($sst->si as $si) {
                if (isset($si->t)) {
                    $sharedStrings[] = (string) $si->t;
                } else {
                    $text = '';
                    foreach ($si->r as $r) {
                        $text .= (string) $r->t;
                    }
                    $sharedStrings[] = $text;
                }
            }
        }
    }

    $sheetXml = $zip->getFromName($sheetPath);
    $zip->close();
    if ($sheetXml === false) {
        throw new RuntimeException('Could not find worksheet data inside the .xlsx file.');
    }

    $xml = @simplexml_load_string($sheetXml);
    if (!$xml || !isset($xml->sheetData)) {
        throw new RuntimeException('The worksheet inside the .xlsx file could not be parsed.');
    }

    $rows = [];
    foreach ($xml->sheetData->row as $row) {
        $cellsByCol = [];
        $maxCol = -1;
        $nextCol = 0; // fallback position tracker - see note below
        foreach ($row->c as $c) {
            $ref = (string) $c['r'];
            // The "r" (cell reference, e.g. "C4") attribute is technically
            // OPTIONAL per the OOXML spec - some tools (WPS Office, certain
            // save/export paths, files that went through a conversion) omit
            // it on some or all cells, relying on cell ORDER within the row
            // instead. xlsx_col_letter_to_index('') used to silently default
            // to column A (index 0) whenever "r" was missing, which made
            // every such cell collide and overwrite each other in column A -
            // a real, silent data-loss bug. Now: use the parsed "r" when
            // it's present and valid, otherwise fall back to "next
            // sequential column after the last one seen" - which is exactly
            // how the OOXML spec says a reader should behave when "r" is absent.
            if ($ref !== '' && preg_match('/^[A-Z]+/i', $ref)) {
                $colIndex = xlsx_col_letter_to_index($ref);
            } else {
                $colIndex = $nextCol;
            }
            $nextCol = $colIndex + 1;

            $type = (string) $c['t'];

            if ($type === 's') {
                $idx = (int) $c->v;
                $value = $sharedStrings[$idx] ?? '';
            } elseif ($type === 'inlineStr') {
                $value = (string) ($c->is->t ?? '');
            } else {
                $value = (string) $c->v; // numeric, boolean, or formula-cached value
            }

            $cellsByCol[$colIndex] = $value;
            if ($colIndex > $maxCol) {
                $maxCol = $colIndex;
            }
        }

        $rowData = [];
        for ($i = 0; $i <= $maxCol; $i++) {
            $rowData[] = $cellsByCol[$i] ?? '';
        }
        $rows[] = $rowData;
    }

    return $rows;
}

function xlsx_col_letter_to_index(string $ref): int
{
    preg_match('/^([A-Z]+)/i', $ref, $m);
    $letters = strtoupper($m[1] ?? 'A');
    $index = 0;
    for ($i = 0, $len = strlen($letters); $i < $len; $i++) {
        $index = $index * 26 + (ord($letters[$i]) - ord('A') + 1);
    }
    return $index - 1;
}

/**
 * Converts an Excel date serial number (e.g. 44927) to 'Y-m-d'.
 * Excel's epoch is 1899-12-30 (it has a well-known off-by-one/leap-year
 * quirk baked in, which this offset already accounts for).
 */
function excel_serial_to_date(float $serial): string
{
    $unixTimestamp = ($serial - 25569) * 86400;
    return gmdate('Y-m-d', (int) round($unixTimestamp));
}

/**
 * Reads a .csv file into the same row-array-of-strings shape as read_xlsx_rows().
 * Handles a UTF-8 BOM if present (common when exporting from Excel).
 */
function read_csv_rows(string $path): array
{
    $rows = [];
    $handle = fopen($path, 'r');
    if ($handle === false) {
        throw new RuntimeException('Unable to open the uploaded .csv file.');
    }

    $first = true;
    while (($data = fgetcsv($handle)) !== false) {
        if ($first) {
            // Strip a UTF-8 BOM from the very first cell, if present.
            if (isset($data[0])) {
                $data[0] = preg_replace('/^\xEF\xBB\xBF/', '', $data[0]);
            }
            $first = false;
        }
        $rows[] = $data;
    }
    fclose($handle);

    return $rows;
}
