<?php
declare(strict_types=1);

/**
 * XlsxLite — pembaca & penulis .xlsx minimal, murni PHP (butuh ext-zip & ext-xml,
 * keduanya SELALU aktif di hosting cPanel standar). Tidak butuh Composer/PhpSpreadsheet.
 *
 * Catatan: cukup untuk kebutuhan tabel sederhana (satu sheet, teks & angka).
 * Jika di kemudian hari server memiliki PhpSpreadsheet (composer vendor/ diupload manual),
 * modul excel/import.php & export.php bisa diarahkan memakainya untuk fitur lebih lengkap.
 */
final class XlsxLite
{
    /**
     * Tulis array 2D ($rows[0] = header) menjadi file .xlsx di $outPath.
     */
    public static function write(array $rows, string $outPath, string $sheetName = 'Sheet1'): void
    {
        if (file_exists($outPath)) {
            @unlink($outPath);
        }
        $zip = new ZipArchive();
        if ($zip->open($outPath, ZipArchive::CREATE) !== true) {
            throw new RuntimeException('Tidak bisa membuat file xlsx.');
        }

        $zip->addFromString('[Content_Types].xml', self::contentTypesXml());
        $zip->addFromString('_rels/.rels', self::relsXml());
        $zip->addFromString('xl/workbook.xml', self::workbookXml($sheetName));
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRelsXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', self::sheetXml($rows));

        $zip->close();
    }

    private static function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '</Types>';
    }

    private static function relsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private static function workbookXml(string $sheetName): string
    {
        $name = htmlspecialchars($sheetName, ENT_QUOTES, 'UTF-8');
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . $name . '" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private static function workbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '</Relationships>';
    }

    private static function colLetter(int $index): string
    {
        // 0-based index -> A, B, ..., Z, AA, AB, ...
        $letter = '';
        $index++;
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $index = intdiv($index - $mod, 26);
        }
        return $letter;
    }

    private static function sheetXml(array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';

        foreach ($rows as $rIdx => $row) {
            $rNum = $rIdx + 1;
            $xml .= '<row r="' . $rNum . '">';
            foreach (array_values($row) as $cIdx => $val) {
                $ref = self::colLetter($cIdx) . $rNum;
                if (is_numeric($val) && $val !== '' && !preg_match('/^0[0-9]/', (string)$val)) {
                    $xml .= '<c r="' . $ref . '"><v>' . htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8') . '</v></c>';
                } else {
                    $safe = htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
                    $xml .= '<c r="' . $ref . '" t="inlineStr"><is><t xml:space="preserve">' . $safe . '</t></is></c>';
                }
            }
            $xml .= '</row>';
        }

        $xml .= '</sheetData></worksheet>';
        return $xml;
    }

    /**
     * Baca sheet pertama dari file .xlsx, kembalikan array 2D string.
     */
    public static function read(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('File .xlsx tidak valid atau rusak.');
        }

        // Baca shared strings jika ada
        $sharedStrings = [];
        $ssContent = $zip->getFromName('xl/sharedStrings.xml');
        if ($ssContent !== false) {
            $sharedStrings = self::parseSharedStrings($ssContent);
        }

        // Cari sheet pertama lewat workbook.xml + rels (fallback ke sheet1.xml)
        $sheetPath = 'xl/worksheets/sheet1.xml';
        $sheetContent = $zip->getFromName($sheetPath);
        if ($sheetContent === false) {
            // Cari file sheet apapun di dalam xl/worksheets/
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (preg_match('#^xl/worksheets/sheet\d+\.xml$#', $name)) {
                    $sheetContent = $zip->getFromName($name);
                    break;
                }
            }
        }
        $zip->close();

        if ($sheetContent === false) {
            throw new RuntimeException('Tidak ditemukan worksheet di dalam file .xlsx.');
        }

        return self::parseSheet($sheetContent, $sharedStrings);
    }

    private static function parseSharedStrings(string $xml): array
    {
        $result = [];
        $doc = new DOMDocument();
        $doc->loadXML($xml, LIBXML_NOENT | LIBXML_NOBLANKS);
        $siList = $doc->getElementsByTagName('si');
        foreach ($siList as $si) {
            $text = '';
            foreach ($si->getElementsByTagName('t') as $t) {
                $text .= $t->textContent;
            }
            $result[] = $text;
        }
        return $result;
    }

    private static function colToIndex(string $colRef): int
    {
        // e.g. "AC12" -> letters "AC"
        preg_match('/^([A-Z]+)/', $colRef, $m);
        $letters = $m[1] ?? 'A';
        $idx = 0;
        foreach (str_split($letters) as $ch) {
            $idx = $idx * 26 + (ord($ch) - 64);
        }
        return $idx - 1; // 0-based
    }

    private static function parseSheet(string $xml, array $sharedStrings): array
    {
        $doc = new DOMDocument();
        $doc->loadXML($xml, LIBXML_NOENT | LIBXML_NOBLANKS);
        $rows = [];
        foreach ($doc->getElementsByTagName('row') as $rowEl) {
            $rowData = [];
            foreach ($rowEl->getElementsByTagName('c') as $c) {
                $ref  = $c->getAttribute('r');
                $type = $c->getAttribute('t');
                $colIdx = self::colToIndex($ref);

                $value = '';
                $vNodes = $c->getElementsByTagName('v');
                if ($type === 's' && $vNodes->length > 0) {
                    $idx = (int)$vNodes->item(0)->textContent;
                    $value = $sharedStrings[$idx] ?? '';
                } elseif ($type === 'inlineStr') {
                    $isNodes = $c->getElementsByTagName('is');
                    if ($isNodes->length > 0) {
                        foreach ($isNodes->item(0)->getElementsByTagName('t') as $t) {
                            $value .= $t->textContent;
                        }
                    }
                } elseif ($vNodes->length > 0) {
                    $value = $vNodes->item(0)->textContent;
                }
                $rowData[$colIdx] = $value;
            }
            if (!empty($rowData)) {
                $maxIdx = max(array_keys($rowData));
                $ordered = [];
                for ($i = 0; $i <= $maxIdx; $i++) {
                    $ordered[] = $rowData[$i] ?? '';
                }
                $rows[] = $ordered;
            } else {
                $rows[] = [];
            }
        }
        return $rows;
    }
}
