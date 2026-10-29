<?php
/**
 * GeoGene - download a list as CSV or as a real Excel (.xlsx) file.
 * Used by Verification Monitoring and User Management. No library is needed
 * (the .xlsx is built with PHP's built-in ZipArchive, the same extension the
 * Excel import already uses).
 *
 *   gg_export_send('xlsx'|'csv', 'file-name-without-extension', $headers, $rows, $options)
 *     $headers = ['Full Name', 'Email', ...]
 *     $rows    = [[...], [...]]   (same order as $headers)
 *     $options = ['sheet' => 'Users', 'date_cols' => [7]]   (date_cols = column numbers holding YYYY-MM-DD)
 * If the server has no zip extension, the CSV is sent instead (Excel opens CSV too).
 */

if (!function_exists('gg_export_send')) {

    /** Excel would run a cell that starts with = + - @ as a formula in a CSV - defuse it. */
    function gg_csv_safe($v)
    {
        return (is_string($v) && $v !== '' && strpos("=+-@\t\r", $v[0]) !== false) ? "'" . $v : $v;
    }

    function gg_send_csv(string $fileName, array $headers, array $rows): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $fileName . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");                         // UTF-8 BOM so Excel shows ñ / é correctly
        fputcsv($out, $headers);
        foreach ($rows as $r) fputcsv($out, array_map('gg_csv_safe', $r));
        fclose($out);
    }

    /** XML-safe text: escape & < > and drop characters XML does not allow. */
    function gg_xml_text($v): string
    {
        $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', (string) $v);
        return htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /** A1, B1 ... Z1, AA1 ... */
    function gg_xl_col(int $i): string
    {
        $s = '';
        for ($i++; $i > 0; $i = intdiv($i - 1, 26)) $s = chr(65 + ($i - 1) % 26) . $s;
        return $s;
    }

    function gg_send_xlsx(string $fileName, string $sheetName, array $headers, array $rows, array $dateCols = []): bool
    {
        if (!class_exists('ZipArchive')) return false;

        $nCols = count($headers);
        $widths = array_map(fn($h) => mb_strlen((string) $h), $headers);
        $sheetRows = '';

        // header row (style 1 = bold on a green fill)
        $cells = '';
        foreach ($headers as $c => $h) {
            $cells .= '<c r="' . gg_xl_col($c) . '1" s="1" t="inlineStr"><is><t>' . gg_xml_text($h) . '</t></is></c>';
        }
        $sheetRows .= '<row r="1">' . $cells . '</row>';

        $rowNo = 1;
        foreach ($rows as $r) {
            $rowNo++;
            $cells = '';
            for ($c = 0; $c < $nCols; $c++) {
                $v = $r[$c] ?? '';
                $ref = gg_xl_col($c) . $rowNo;
                $widths[$c] = max($widths[$c], min(60, mb_strlen((string) $v)));
                if (in_array($c, $dateCols, true) && preg_match('/^(\d{4})-(\d{2})-(\d{2})/', (string) $v, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
                    // real Excel date (days since 1899-12-30), shown as yyyy-mm-dd so it can be sorted / filtered
                    $serial = (int) floor((gmmktime(0, 0, 0, (int) $m[2], (int) $m[3], (int) $m[1]) - gmmktime(0, 0, 0, 12, 30, 1899)) / 86400);
                    $cells .= '<c r="' . $ref . '" s="2"><v>' . $serial . '</v></c>';
                } elseif ($v === '' || $v === null) {
                    continue;
                } else {
                    // inline text: Excel never treats it as a formula, so no extra escaping is needed
                    $cells .= '<c r="' . $ref . '" t="inlineStr"><is><t xml:space="preserve">' . gg_xml_text($v) . '</t></is></c>';
                }
            }
            $sheetRows .= '<row r="' . $rowNo . '">' . $cells . '</row>';
        }

        $cols = '';
        foreach ($widths as $c => $w) $cols .= '<col min="' . ($c + 1) . '" max="' . ($c + 1) . '" width="' . max(10, min(60, $w + 3)) . '" customWidth="1"/>';
        $lastCol = gg_xl_col(max(0, $nCols - 1));

        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<dimension ref="A1:' . $lastCol . max(1, $rowNo) . '"/>'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . '<cols>' . $cols . '</cols>'
            . '<sheetData>' . $sheetRows . '</sheetData>'
            . '<autoFilter ref="A1:' . $lastCol . max(1, $rowNo) . '"/>'
            . '</worksheet>';

        $safeSheet = gg_xml_text(mb_substr(preg_replace('/[\\\\\/?*\[\]:]/', ' ', $sheetName), 0, 31) ?: 'Sheet1');
        $files = [
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="' . $safeSheet . '" sheetId="1" r:id="rId1"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>',
            'xl/styles.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><numFmts count="1"><numFmt numFmtId="164" formatCode="yyyy\-mm\-dd"/></numFmts><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF1B6E3C"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="3"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/><xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>',
            'xl/worksheets/sheet1.xml' => $sheetXml,
        ];

        $tmp = tempnam(sys_get_temp_dir(), 'ggx');
        try {
            $zip = new ZipArchive();
            if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) { @unlink($tmp); return false; }
            foreach ($files as $name => $xml) $zip->addFromString($name, $xml);
            $zip->close();
        } catch (Throwable $e) {                 // no usable zip support: the caller falls back to CSV
            @unlink($tmp);
            error_log('gg_send_xlsx: ' . $e->getMessage());
            return false;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $fileName . '.xlsx"');
        header('Content-Length: ' . filesize($tmp));
        readfile($tmp);
        @unlink($tmp);
        return true;
    }

    /** Entry point: $format is 'xlsx' or 'csv'. Always ends the request. */
    function gg_export_send(string $format, string $fileName, array $headers, array $rows, array $options = []): void
    {
        if ($format === 'xlsx' && gg_send_xlsx($fileName, $options['sheet'] ?? 'Export', $headers, $rows, $options['date_cols'] ?? [])) exit;
        gg_send_csv($fileName, $headers, $rows);          // CSV requested, or no zip extension on this server
        exit;
    }

    /** The user columns shared by both pages (citizen accounts). */
    function gg_users_export_headers(): array
    {
        return ['Full Name', 'Email Address', 'Phone Number', 'Barangay', 'Date of Birth', 'Status', 'Identity Verified', 'Date Registered'];
    }
    function gg_users_export_row(array $u): array
    {
        require_once __DIR__ . '/../../config/account-status.php';
        $d = fn($v) => $v ? date('Y-m-d', strtotime($v)) : '';
        return [
            $u['full_name'] ?? '', $u['email'] ?? '', $u['phone_number'] ?? '', $u['barangay'] ?? '',
            $d($u['date_of_birth'] ?? null),
            geogene_account_status_label($u['status'] ?? ''),
            !empty($u['identity_verified']) ? 'Verified' : 'Pending',
            $d($u['created_at'] ?? null),
        ];
    }
}
