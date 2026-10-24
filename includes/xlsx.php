<?php
// Minimal Excel (.xlsx) writer: one sheet, bold header row, filters, frozen header.
// Builds the zip by hand, so it needs no Composer package and no PHP zip extension.

/**
 * Sends a spreadsheet download and exits.
 * $rows are lists of values: ints/floats become number cells, everything else text.
 */
function xlsx_download(string $filename, array $headers, array $rows, array $widths = []): void
{
    $x = fn($v) => htmlspecialchars((string) $v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    $col = function (int $i): string {
        $s = '';
        for ($i++; $i > 0; $i = intdiv($i - 1, 26)) {
            $s = chr(65 + ($i - 1) % 26) . $s;
        }
        return $s;
    };

    $sheetRows = '';
    foreach (array_merge([$headers], $rows) as $r => $values) {
        $sheetRows .= '<row r="' . ($r + 1) . '">';
        foreach (array_values($values) as $c => $v) {
            $ref = $col($c) . ($r + 1);
            $style = $r === 0 ? ' s="1"' : '';
            if ((is_int($v) || is_float($v)) && $r > 0) {
                $sheetRows .= '<c r="' . $ref . '"' . $style . '><v>' . $v . '</v></c>';
            } else {
                // Strip characters XML does not allow; keep tabs and new lines.
                $v = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', (string) $v);
                $sheetRows .= '<c r="' . $ref . '" t="inlineStr"' . $style . '><is><t xml:space="preserve">' . $x($v) . '</t></is></c>';
            }
        }
        $sheetRows .= '</row>';
    }
    $lastCol = $col(max(0, count($headers) - 1));
    $cols = '';
    foreach ($headers as $i => $h) {
        $cols .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . ($widths[$i] ?? 18) . '" customWidth="1"/>';
    }

    $ns = 'http://schemas.openxmlformats.org';
    $files = [
        '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="' . $ns . '/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>',
        '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="' . $ns . '/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="' . $ns . '/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>',
        'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="' . $ns . '/spreadsheetml/2006/main" xmlns:r="' . $ns . '/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Sheet1" sheetId="1" r:id="rId1"/></sheets>'
            . '<definedNames><definedName name="_xlnm._FilterDatabase" localSheetId="0" hidden="1">Sheet1!$A$1:$' . $lastCol . '$' . (count($rows) + 1) . '</definedName></definedNames>'
            . '</workbook>',
        'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="' . $ns . '/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="' . $ns . '/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="' . $ns . '/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>',
        'xl/styles.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="' . $ns . '/spreadsheetml/2006/main">'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF14432A"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/></cellXfs>'
            . '</styleSheet>',
        'xl/worksheets/sheet1.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="' . $ns . '/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . '<cols>' . $cols . '</cols><sheetData>' . $sheetRows . '</sheetData>'
            . '<autoFilter ref="A1:' . $lastCol . (count($rows) + 1) . '"/>'
            . '</worksheet>',
    ];

    $zip = xlsx_zip($files);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
    header('Content-Length: ' . strlen($zip));
    header('Cache-Control: no-store');
    echo $zip;
    exit;
}

// Zip archive (deflate when zlib is available, otherwise stored) from [name => contents].
function xlsx_zip(array $files): string
{
    $data = $central = '';
    $time = (int) date('G') << 11 | (int) date('i') << 5 | intdiv((int) date('s'), 2);
    $date = ((int) date('Y') - 1980) << 9 | (int) date('n') << 5 | (int) date('j');
    foreach ($files as $name => $content) {
        $deflate = function_exists('gzdeflate');
        $packed = $deflate ? gzdeflate($content, 6) : $content;
        // flags, method, time, date, crc, compressed size, size, name length (shared by both headers)
        $meta = pack('vvvvVVVv', 0, $deflate ? 8 : 0, $time, $date, crc32($content), strlen($packed), strlen($content), strlen($name));
        $offset = strlen($data);
        $data .= "PK\x03\x04" . pack('v', 20) . $meta . pack('v', 0) . $name . $packed;
        $central .= "PK\x01\x02" . pack('vv', 20, 20) . $meta . pack('vvvvV', 0, 0, 0, 0, 0) . pack('V', $offset) . $name;
    }
    return $data . $central . "PK\x05\x06" . pack('vvvvVVv', 0, 0, count($files), count($files), strlen($central), strlen($data), 0);
}
