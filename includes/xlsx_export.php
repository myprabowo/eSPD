<?php
/**
 * xlsx_export.php — eSPD XLSX & ZIP Export
 * Uses ZipArchive + XML to generate proper XLSX files without external libraries.
 */

/**
 * Generate XLSX file content from header + rows.
 * Returns the path to a temporary file.
 */
function generate_xlsx(array $header, array $rows, string $sheetName = 'SPD'): string {
    $xe = fn(string $s): string => htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');

    // Build shared strings
    $strings     = [];
    $stringIndex = [];
    $addStr = function (string $s) use (&$strings, &$stringIndex): int {
        if (!isset($stringIndex[$s])) {
            $stringIndex[$s] = count($strings);
            $strings[]       = $s;
        }
        return $stringIndex[$s];
    };

    foreach ($header as $h) { $addStr((string) $h); }

    // Build sheet rows
    $colLetter = function (int $n): string {
        $s = '';
        while ($n > 0) {
            $n--; $s = chr(65 + ($n % 26)) . $s; $n = intdiv($n, 26);
        }
        return $s;
    };

    $sheetRows = '';

    // Header row
    $rowNum = 1;
    $cells = '';
    foreach ($header as $ci => $h) {
        $col = $colLetter($ci + 1);
        $ref = $col . $rowNum;
        $idx = $addStr((string) $h);
        $cells .= "<c r=\"{$ref}\" t=\"s\" s=\"1\"><v>{$idx}</v></c>";
    }
    $sheetRows .= "<row r=\"{$rowNum}\">{$cells}</row>";

    // Data rows
    foreach ($rows as $ri => $row) {
        $rowNum = $ri + 2;
        $cells = '';
        foreach (array_values($row) as $ci => $cell) {
            $col = $colLetter($ci + 1);
            $ref = $col . $rowNum;
            
            // Check if numeric
            if (is_numeric($cell) && $cell !== '') {
                $cells .= "<c r=\"{$ref}\"><v>{$cell}</v></c>";
            } else {
                $idx = $addStr((string) $cell);
                $cells .= "<c r=\"{$ref}\" t=\"s\"><v>{$idx}</v></c>";
            }
        }
        $sheetRows .= "<row r=\"{$rowNum}\">{$cells}</row>";
    }

    // Style for header (bold)
    $styleXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font>'
        . '<font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
        . '<fills count="3"><fill><patternFill patternType="none"/></fill>'
        . '<fill><patternFill patternType="gray125"/></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FF4472C4"/></patternFill></fill></fills>'
        . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
        . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
        . '<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
        . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/></cellXfs>'
        . '</styleSheet>';

    $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
        . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        . '<sheetData>' . $sheetRows . '</sheetData></worksheet>';

    $ssItems = '';
    foreach ($strings as $s) {
        $ssItems .= '<si><t xml:space="preserve">' . $xe($s) . '</t></si>';
    }
    $cnt = count($strings);
    $sharedStrXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . "<sst xmlns=\"http://schemas.openxmlformats.org/spreadsheetml/2006/main\""
        . " count=\"{$cnt}\" uniqueCount=\"{$cnt}\">{$ssItems}</sst>";

    $safeSheetName = $xe(mb_substr($sheetName, 0, 31));
    $workbookXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
        . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        . "<sheets><sheet name=\"{$safeSheetName}\" sheetId=\"1\" r:id=\"rId1\"/></sheets></workbook>";

    $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
        . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>'
        . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
        . '</Relationships>';

    $pkgRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
        . '</Relationships>';

    $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        . '<Default Extension="xml" ContentType="application/xml"/>'
        . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
        . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
        . '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
        . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
        . '</Types>';

    $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx_');
    $zip     = new ZipArchive();
    if ($zip->open($tmpFile, ZipArchive::OVERWRITE) !== true) {
        return '';
    }
    $zip->addFromString('[Content_Types].xml',          $contentTypes);
    $zip->addFromString('_rels/.rels',                  $pkgRels);
    $zip->addFromString('xl/workbook.xml',              $workbookXml);
    $zip->addFromString('xl/_rels/workbook.xml.rels',   $wbRels);
    $zip->addFromString('xl/worksheets/sheet1.xml',     $sheetXml);
    $zip->addFromString('xl/sharedStrings.xml',         $sharedStrXml);
    $zip->addFromString('xl/styles.xml',                $styleXml);
    $zip->close();

    return $tmpFile;
}

/**
 * Build a complete export ZIP containing:
 * 1. Rekap_SPD.xlsx — all SPD data in one Excel file
 * 2. Bukti_Perjalanan/{nama}/ — uploaded evidence files per person
 */
function generate_export_zip(int $id_kegiatan): string {
    $kegiatan = db_query("SELECT * FROM kegiatan WHERE id = ?", [$id_kegiatan]);
    if (empty($kegiatan)) return '';
    $kegiatan = $kegiatan[0];

    // Get all SPDs
    $spds = db_query("SELECT * FROM spd WHERE id_kegiatan = ? ORDER BY nama ASC", [$id_kegiatan]);

    // Build Excel header/rows
    $exportHeader = [
        'No', 'No SPPD', 'Tgl SPPD', 'Nama', 'NIP', 'Golongan', 'Pangkat', 'Jabatan', 'Instansi',
        'Kota Asal', 'Kota Tujuan', 'Tiba Di', 'Tgl Mulai', 'Tgl Akhir', 'Alat Angkut',
        'Tiket PP', 'Tiket Berangkat', 'Tiket Pulang', 'Total Tiket',
        'UH Jml Hari', 'UH Per Hari', 'UH Total',
        'UH2 Jml Hari', 'UH2 Per Hari', 'UH2 Total',
        'UH3 Jml Hari', 'UH3 Per Hari', 'UH3 Total',
        'Fullboard/Hari', 'Fullboard Hari', 'Fullboard Total',
        'Tarif Maks Hotel',
        'Hotel 1 Tarif', 'Hotel 1 Hari', 'Hotel 1 Total',
        'Hotel 2 Tarif', 'Hotel 2 Hari', 'Hotel 2 Total',
        'Hotel 3 Tarif', 'Hotel 3 Hari', 'Hotel 3 Total',
        'Hotel 4 Tarif', 'Hotel 4 Hari', 'Hotel 4 Total',
        'Hotel 5 Tarif', 'Hotel 5 Hari', 'Hotel 5 Total',
        'Hotel 6 Tarif', 'Hotel 6 Hari', 'Hotel 6 Total',
        'Total Hotel',
        'DPR Tarif Hotel', 'DPR Malam', 'DPR Koefisien', 'DPR Biaya Penginapan',
        'Transport PP', 'Ke Bandara', 'Bandara-Tujuan', 'Tujuan-Bandara', 'Bandara-Kedudukan', 'Kedudukan-Tujuan',
        'Total Transport',
        'Covid Berangkat', 'Covid Pulang', 'Covid Tanpa Bukti', 'Total Covid',
        'Biaya Tol', 'Biaya Taksi', 'Biaya Bensin', 'Biaya Riil Lainnya', 'Total Biaya Bukti',
        'Uang Representatif', 'Representatif Hari', 'Representatif Total',
        'Tingkat Perjadin',
        'No Rekening', 'Bank', 'Nama Rekening',
        'No ST', 'Tgl ST', 'Perihal ST',
        'Tgl Dok Diterima', 'Tgl Rincian SPD', 'UP/LS', 'Tgl Pengajuan', 'Tgl Pembayaran',
        'Persekot', 'Grand Total', 'Kurang/Lebih',
        'Akun', 'No Routing', 'PIC', 'Bendahara', 'PPK', 'Unit',
        'Status', 'Catatan',
    ];

    $exportRows = [];
    foreach ($spds as $i => $spd) {
        $spd = compute_spd_totals($spd);
        $exportRows[] = [
            $i + 1,
            $spd['no_sppd'], $spd['tgl_sppd'], $spd['nama'], $spd['nip'],
            $spd['golongan'], $spd['pangkat'], $spd['jabatan'], $spd['instansi'],
            $spd['kota_asal'], $spd['kota_tujuan'], $spd['tiba_di'],
            $spd['tgl_mulai'], $spd['tgl_akhir'], $spd['alat_angkut'],
            $spd['tiket_pp'], $spd['tiket_berangkat'], $spd['tiket_pulang'], $spd['total_tiket'],
            $spd['uh_jml_hari'], $spd['uh_per_hari'], $spd['uh_total'],
            $spd['uh2_jml_hari'], $spd['uh2_per_hari'], $spd['uh2_total'],
            $spd['uh3_jml_hari'], $spd['uh3_per_hari'], $spd['uh3_total'],
            $spd['uh_fullboard_per_hari'], $spd['uh_fullboard_jml_hari'], $spd['uh_fullboard_total'],
            $spd['tarif_maks_hotel'],
            $spd['hotel1_tarif'], $spd['hotel1_hari'], $spd['hotel1_total'],
            $spd['hotel2_tarif'], $spd['hotel2_hari'], $spd['hotel2_total'],
            $spd['hotel3_tarif'], $spd['hotel3_hari'], $spd['hotel3_total'],
            $spd['hotel4_tarif'], $spd['hotel4_hari'], $spd['hotel4_total'],
            $spd['hotel5_tarif'], $spd['hotel5_hari'], $spd['hotel5_total'],
            $spd['hotel6_tarif'], $spd['hotel6_hari'], $spd['hotel6_total'],
            $spd['total_hotel'],
            $spd['dpr_tarif_hotel'], $spd['dpr_malam_hotel'], $spd['dpr_koefisien'], $spd['dpr_biaya_penginapan'],
            $spd['transport_pp'], $spd['transport_ke_bandara'], $spd['transport_bandara_tujuan'],
            $spd['transport_tujuan_bandara'], $spd['transport_bandara_kedudukan'], $spd['transport_kedudukan_tujuan'],
            $spd['total_transport'],
            $spd['covid_berangkat'], $spd['covid_pulang'], $spd['covid_tanpa_bukti'], $spd['total_covid'],
            $spd['biaya_tol'], $spd['biaya_taksi'], $spd['biaya_bensin'], $spd['biaya_riil_lainnya'], $spd['total_biaya_bukti'],
            $spd['uang_representatif'], $spd['uang_representatif_hari'], $spd['uang_representatif_total'],
            $spd['tingkat_perjadin'],
            $spd['no_rekening'], $spd['bank'], $spd['nama_rekening'],
            $kegiatan['nomor_st'], $kegiatan['tanggal_st'], $kegiatan['perihal_st'],
            $spd['tgl_dok_diterima'], $spd['tgl_rincian_spd'], $spd['pengajuan_up_ls'],
            $spd['tgl_pengajuan'], $spd['tgl_pembayaran'],
            $spd['persekot'], $spd['grand_total'], $spd['kurang_lebih'],
            $spd['akun'], $spd['no_routing'], $spd['pic'], $spd['bendahara'], $spd['ppk'], $spd['unit'],
            $spd['status'], $spd['catatan'],
        ];
    }

    // Generate XLSX
    $xlsxPath = generate_xlsx($exportHeader, $exportRows, 'Rekap SPD');

    // Build ZIP
    $zipPath = tempnam(sys_get_temp_dir(), 'spd_zip_');
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::OVERWRITE) !== true) return '';

    // Add Excel file
    if ($xlsxPath && file_exists($xlsxPath)) {
        $zip->addFile($xlsxPath, 'Rekap_SPD.xlsx');
    }

    // Add evidence files per person
    foreach ($spds as $i => $spd) {
        $files = db_query("SELECT * FROM spd_files WHERE id_spd = ?", [$spd['id']]);
        if (empty($files)) continue;

        $folderName = sprintf('%02d_%s', $i + 1, safe_filename($spd['nama']));
        foreach ($files as $f) {
            $filePath = __DIR__ . '/../uploads/spd_' . $spd['id'] . '/' . $f['nama_file'];
            if (file_exists($filePath)) {
                $zip->addFile($filePath, "Bukti_Perjalanan/{$folderName}/{$f['nama_asli']}");
            }
        }
    }

    $zip->close();

    // Clean up XLSX temp file
    if ($xlsxPath && file_exists($xlsxPath)) {
        unlink($xlsxPath);
    }

    return $zipPath;
}
