<?php
/**
 * xlsx_export.php — eSPD XLSX & ZIP Export
 * Uses ZipArchive + XML to generate proper XLSX files without external libraries.
 */

/**
 * Generate XLSX file content from header + rows.
 * Returns the path to a temporary file.
 */
function generate_xlsx(array $header, array $rows, string $sheetName = 'SPD', array $colTypes = [], array $merges = []): string {
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

    // Header strings
    $headerRows = is_array($header[0] ?? null) ? $header : [$header];
    foreach ($headerRows as $hr) {
        foreach ($hr as $h) {
            $addStr((string) $h);
        }
    }

    // Build sheet rows
    $colLetter = function (int $n): string {
        $s = '';
        while ($n > 0) {
            $n--; $s = chr(65 + ($n % 26)) . $s; $n = intdiv($n, 26);
        }
        return $s;
    };

    $sheetRows = '';

    // Header rows
    $rowNum = 1;
    foreach ($headerRows as $hr) {
        $cells = '';
        foreach (array_values($hr) as $ci => $h) {
            $col = $colLetter($ci + 1);
            $ref = $col . $rowNum;
            if ($h === '') {
                $cells .= "<c r=\"{$ref}\" s=\"1\"/>";
            } else {
                $idx = $addStr((string) $h);
                $cells .= "<c r=\"{$ref}\" t=\"s\" s=\"1\"><v>{$idx}</v></c>";
            }
        }
        $sheetRows .= "<row r=\"{$rowNum}\" ht=\"25\" customHeight=\"1\">{$cells}</row>";
        $rowNum++;
    }

    // Data rows
    foreach ($rows as $ri => $row) {
        $cells = '';
        $rs = ($ri % 2 === 0) ? 2 : 3; // 2 = odd row style, 3 = even row style
        $cs = ($ri % 2 === 0) ? 4 : 5; // 4 = odd row currency, 5 = even row currency

        foreach (array_values($row) as $ci => $cell) {
            $col = $colLetter($ci + 1);
            $ref = $col . $rowNum;
            
            $isText = in_array($ci, $colTypes['text'] ?? []);
            $isCurrency = in_array($ci, $colTypes['currency'] ?? []);
            
            if ($isText) {
                $idx = $addStr((string) $cell);
                $cells .= "<c r=\"{$ref}\" t=\"s\" s=\"{$rs}\"><v>{$idx}</v></c>";
            } elseif ($isCurrency && is_numeric($cell) && $cell !== '') {
                $cells .= "<c r=\"{$ref}\" s=\"{$cs}\"><v>{$cell}</v></c>";
            } elseif (is_numeric($cell) && $cell !== '') {
                $cells .= "<c r=\"{$ref}\" s=\"{$rs}\"><v>{$cell}</v></c>";
            } else {
                $idx = $addStr((string) $cell);
                $cells .= "<c r=\"{$ref}\" t=\"s\" s=\"{$rs}\"><v>{$idx}</v></c>";
            }
        }
        $sheetRows .= "<row r=\"{$rowNum}\">{$cells}</row>";
        $rowNum++;
    }

    // Style for header (bold white on dark blue), zebra rows, borders, and currency
    $styleXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<fonts count="3">'
        .   '<font><sz val="11"/><name val="Calibri"/></font>'
        .   '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
        .   '<font><sz val="11"/><name val="Calibri"/></font>'
        . '</fonts>'
        . '<fills count="5">'
        .   '<fill><patternFill patternType="none"/></fill>'
        .   '<fill><patternFill patternType="gray125"/></fill>'
        .   '<fill><patternFill patternType="solid"><fgColor rgb="FF003366"/></patternFill></fill>'
        .   '<fill><patternFill patternType="solid"><fgColor rgb="FFF5F8FF"/></patternFill></fill>'
        .   '<fill><patternFill patternType="solid"><fgColor rgb="FFFFFFFF"/></patternFill></fill>'
        . '</fills>'
        . '<borders count="3">'
        .   '<border><left/><right/><top/><bottom/><diagonal/></border>'
        .   '<border><left style="thin"><color rgb="FFDDDDDD"/></left><right style="thin"><color rgb="FFDDDDDD"/></right><top style="thin"><color rgb="FFDDDDDD"/></top><bottom style="thin"><color rgb="FFDDDDDD"/></bottom><diagonal/></border>'
        .   '<border><left style="thin"><color rgb="FFAAAAAA"/></left><right style="thin"><color rgb="FFAAAAAA"/></right><top style="thin"><color rgb="FFAAAAAA"/></top><bottom style="thin"><color rgb="FFAAAAAA"/></bottom><diagonal/></border>'
        . '</borders>'
        . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
        . '<cellXfs count="6">'
        .   '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
        .   '<xf numFmtId="0" fontId="1" fillId="2" borderId="2" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
        .   '<xf numFmtId="0" fontId="2" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'
        .   '<xf numFmtId="0" fontId="2" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'
        .   '<xf numFmtId="3" fontId="2" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyNumberFormat="1" applyAlignment="1"><alignment vertical="top"/></xf>'
        .   '<xf numFmtId="3" fontId="2" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyNumberFormat="1" applyAlignment="1"><alignment vertical="top"/></xf>'
        . '</cellXfs>'
        . '</styleSheet>';

    $mergeXml = '';
    if (!empty($merges)) {
        $mcCount = count($merges);
        $mergeXml = "<mergeCells count=\"{$mcCount}\">";
        foreach ($merges as $m) {
            $mergeXml .= "<mergeCell ref=\"{$m}\"/>";
        }
        $mergeXml .= "</mergeCells>";
    }

    $headerRowCount = count($headerRows);
    $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
        . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        . '<sheetViews>'
        . '<sheetView tabSelected="1" workbookViewId="0">'
        . "<pane ySplit=\"{$headerRowCount}\" topLeftCell=\"A" . ($headerRowCount + 1) . "\" activePane=\"bottomLeft\" state=\"frozen\"/>"
        . '</sheetView>'
        . '</sheetViews>'
        . '<sheetData>' . $sheetRows . '</sheetData>'
        . $mergeXml
        . '</worksheet>';

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
 * Generate the SPD Excel file matching the template format.
 * Returns the path to the temporary XLSX file.
 */
function generate_spd_excel_file(array $kegiatan, array $spds): string {
    // Build Excel header/rows
    $exportHeader = [
        ["NO", "No_SPPD", "Tgl_sppd", "Nama", "NIP", "asal", "tujuan", "Tiba Di", "tgl_mulai", "tgl_akhir", "TIKET", "", "", "Uang Harian", "", "", "Uang Harian KOTA KE 2", "", "", "Uang Harian Kota ke 3", "", "", "Uang harian Fullboard", "", "", "TARIF MAKSIMAL HOTEL SBU", "HOTEL dengan KUITANSI (ISI TARIF PER HARI)", "JML HARI", "KUITANSI HOTEL KE 2-ISI TARIF PER HARI (APABILA GANTI-GANTI HOTEL)", "JML HARI (HOTEL KE-2-APABILA GANTI HOTEL)", "KUITANSI HOTEL KE 3-ISI TARIF PER HARI (APABILA GANTI-GANTI HOTEL)", "JML HARI (HOTEL KE-3-APABILA GANTI HOTEL)", "KUITANSI HOTEL KE 4-ISI TARIF PER HARI (APABILA GANTI-GANTI HOTEL)", "JML HARI (HOTEL KE-4-APABILA GANTI HOTEL)", "KUITANSI HOTEL KE 5-ISI TARIF PER HARI (APABILA GANTI-GANTI HOTEL)", "JML HARI (HOTEL KE-5-APABILA GANTI HOTEL)", "KUITANSI HOTEL KE 6-ISI TARIF PER HARI (APABILA GANTI-GANTI HOTEL)", "JML HARI (HOTEL KE-6-APABILA GANTI HOTEL)", "Penginapan DPR", "", "", "", "TRANSPORT DPR", "", "", "", "", "", "TEST COVID", "", "", "Komponen Transportasi Dengan Bukti", "", "", "", "", "", "", "Data SPD", "", "", "", "Tingkat Perjalanan Dinas", "Surat Tugas", "", "", "No. Rek.", "Bank", "Nama", "Alat Angkut yang Digunakan", "Tanggal Dokumen diterima PIC Keu", "Tanggal penyampaian Rincian SPD dan DPR", "Pengajuan Pembayaran/ Penerimaan Dok DS dari Pelaksana Perjadin", "", "Tanggal Pembayaran", "PERSEKOT", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""],
        ["", "", "", "", "", "", "", "", "", "", "Tiket PP", "Berangkat", "Pulang", "jml_hari", "uang harian (per satu hr)", "harian total", "jml_hari", "uang harian (per satu hr)", "harian total", "jml_hari", "uang harian (per satu hr)", "harian total", "Fullboard (per satu hari)", "Jumlah Hari", "Fullboard total", "", "", "", "", "", "", "", "", "", "", "", "", "", "TARIF HOTEL SESUAI GOLONGAN", "Malam hotel", "0.3", "Penggantian Biaya Penginapan", "Penggantian biaya transport dari tempat kedudukan ke tempat tujuan (PP)", "Penggantian biaya transport dari tempat kedudukan ke bandara", "Penggantian biaya transport dari bandara ke tempat tujuan", "Penggantian biaya transport dari tempat tujuan ke bandara", "Penggantian biaya transport dari bandara ke tempat kedudukan", "Penggantian biaya transport dari tempat kedudukan ke tempat tujuan/dari tempat tujuan ke tempat kedudukan", "Biaya Test Covid 19 berangkat\nDENGAN BUKTI", "pulang", "Biaya Test Covid 19\nISI DISINI JIKA TANPA BUKTI", "Biaya Tol", "Taksi", "Bensin", "Penggantian Biaya Riil Lainnya", "Uang Representatif", "Jumlah Hari", "Jumlah Uang Representatif", "Golongan", "Pangkat", "Jabatan", "Instansi", "", "Nomor", "Tanggal", "Perihal", "", "", "Rekening", "", "", "", "UP/LS", "Tanggal", "", "JUMLAH", "Persekot", "Kurang/Lebih", "", "", "", "", "", "", "AKUN", "No Routing", "PIC", "Bendahara", "PPK", "UNIT", "", "", "", "", "", "Total Tiket", "Total Hotel", "Total Taksi, Tol dan BBM", "Total Uang Representatif", "Total UH"],
        ["1", "2", "3", "4", "5", "6", "7", "8", "9", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "23", "24", "25", "26", "27", "28", "29", "30", "31", "32", "33", "34", "35", "36", "37", "38", "39", "40", "41.0", "42", "43", "44", "45", "46", "47", "48", "49", "50", "51", "52", "53", "54", "55", "56", "57", "58", "59", "60", "61", "62", "63", "64", "65", "66", "", "68", "69", "70", "71", "72", "73", "74", "75", "76", "77", "78", "79.0", "80.0", "81.0", "82.0", "83.0", "84.0", "85", "86", "87", "88", "89", "", "", "", "", "", "", "", "", "", "", ""]
    ];

    $exportRows = [];
    foreach ($spds as $i => $spd) {
        $spd = compute_spd_totals($spd);
        $exportRows[] = [
            $i + 1, // 0
            $spd['no_sppd'], // 1
            $spd['tgl_sppd'], // 2
            $spd['nama'], // 3
            $spd['nip'], // 4
            $spd['kota_asal'], // 5
            $spd['kota_tujuan'], // 6
            $spd['tiba_di'], // 7
            $spd['tgl_mulai'], // 8
            $spd['tgl_akhir'], // 9
            $spd['tiket_pp'], // 10
            $spd['tiket_berangkat'], // 11
            $spd['tiket_pulang'], // 12
            $spd['uh_jml_hari'], // 13
            $spd['uh_per_hari'], // 14
            $spd['uh_total'], // 15
            $spd['uh2_jml_hari'], // 16
            $spd['uh2_per_hari'], // 17
            $spd['uh2_total'], // 18
            $spd['uh3_jml_hari'], // 19
            $spd['uh3_per_hari'], // 20
            $spd['uh3_total'], // 21
            $spd['uh_fullboard_per_hari'], // 22
            $spd['uh_fullboard_jml_hari'], // 23
            $spd['uh_fullboard_total'], // 24
            $spd['tarif_maks_hotel'], // 25
            $spd['hotel1_tarif'], // 26
            $spd['hotel1_hari'], // 27
            $spd['hotel2_tarif'], // 28
            $spd['hotel2_hari'], // 29
            $spd['hotel3_tarif'], // 30
            $spd['hotel3_hari'], // 31
            $spd['hotel4_tarif'], // 32
            $spd['hotel4_hari'], // 33
            $spd['hotel5_tarif'], // 34
            $spd['hotel5_hari'], // 35
            $spd['hotel6_tarif'], // 36
            $spd['hotel6_hari'], // 37
            $spd['dpr_tarif_hotel'], // 38
            $spd['dpr_malam_hotel'], // 39
            $spd['dpr_koefisien'], // 40
            $spd['dpr_biaya_penginapan'], // 41
            $spd['transport_pp'], // 42
            $spd['transport_ke_bandara'], // 43
            $spd['transport_bandara_tujuan'], // 44
            $spd['transport_tujuan_bandara'], // 45
            $spd['transport_bandara_kedudukan'], // 46
            $spd['transport_kedudukan_tujuan'], // 47
            $spd['covid_berangkat'], // 48
            $spd['covid_pulang'], // 49
            $spd['covid_tanpa_bukti'], // 50
            $spd['biaya_tol'], // 51
            $spd['biaya_taksi'], // 52
            $spd['biaya_bensin'], // 53
            $spd['biaya_riil_lainnya'], // 54
            $spd['uang_representatif'], // 55
            $spd['uang_representatif_hari'], // 56
            $spd['uang_representatif_total'], // 57
            $spd['golongan'], // 58
            $spd['pangkat'], // 59
            $spd['jabatan'], // 60
            $spd['instansi'], // 61
            $spd['tingkat_perjadin'], // 62
            $kegiatan['nomor_st'], // 63
            $kegiatan['tanggal_st'], // 64
            $kegiatan['perihal_st'], // 65
            $spd['no_rekening'], // 66
            $spd['bank'], // 67
            $spd['nama_rekening'], // 68
            $spd['alat_angkut'], // 69
            $spd['tgl_dok_diterima'], // 70
            $spd['tgl_rincian_spd'], // 71
            $spd['pengajuan_up_ls'], // 72
            $spd['tgl_pengajuan'], // 73
            $spd['tgl_pembayaran'], // 74
            $spd['grand_total'], // 75
            $spd['persekot'], // 76
            $spd['kurang_lebih'], // 77
            '', '', '', '', '', '', // 78 - 83
            $spd['akun'], // 84
            $spd['no_routing'], // 85
            $spd['pic'], // 86
            $spd['bendahara'], // 87
            $spd['ppk'], // 88
            $spd['unit'], // 89
            '', '', '', '', '', // 90 - 94
            $spd['total_tiket'], // 95
            $spd['total_hotel'], // 96
            $spd['total_biaya_bukti'], // 97
            $spd['uang_representatif_total'], // 98
            $spd['total_uang_harian'] // 99
        ];
    }

    $colTypes = [
        'text' => [1, 4, 63, 66],
        'currency' => [10, 11, 12, 14, 15, 17, 18, 20, 21, 22, 24, 25, 26, 28, 30, 32, 34, 36, 38, 41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 54, 55, 57, 75, 76, 77, 95, 96, 97, 98, 99]
    ];
    $merges = ['K1:M1', 'N1:P1', 'Q1:S1', 'T1:V1', 'W1:Y1', 'AM1:AP1', 'AQ1:AV1', 'AW1:AY1', 'AZ1:BC1', 'BG1:BJ1', 'BL1:BN1', 'BU1:BV1', 'BX1:CQ1'];
    
    // Vertical merges for cells that span row 1 and 2
    $verticalMergeCols = array_merge(range(0, 9), range(25, 37), [62, 66, 67, 68, 69, 70, 71, 74]);
    foreach ($verticalMergeCols as $colIndex) {
        $col = '';
        $n = $colIndex + 1;
        while ($n > 0) {
            $n--; $col = chr(65 + ($n % 26)) . $col; $n = intdiv($n, 26);
        }
        $merges[] = "{$col}1:{$col}2";
    }

    return generate_xlsx($exportHeader, $exportRows, 'Rekap SPD', $colTypes, $merges);
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

    // Generate XLSX
    $xlsxPath = generate_spd_excel_file($kegiatan, $spds);

    // Build ZIP
    $zipPath = tempnam(sys_get_temp_dir(), 'spd_zip_');
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::OVERWRITE) !== true) return '';

    // Add Excel file
    if ($xlsxPath && file_exists($xlsxPath)) {
        $zip->addFile($xlsxPath, 'Rekap_SPD.xlsx');
    }

    // Add evidence files per person
    $catLabels = file_categories();
    foreach ($spds as $i => $spd) {
        $files = db_query("SELECT * FROM spd_files WHERE id_spd = ?", [$spd['id']]);
        if (empty($files)) continue;

        $folderName = sprintf('%02d_%s', $i + 1, safe_filename($spd['nama']));
        foreach ($files as $f) {
            $filePath = __DIR__ . '/../uploads/spd_' . $spd['id'] . '/' . $f['nama_file'];
            if (file_exists($filePath)) {
                $kategoriName = safe_filename($catLabels[$f['kategori']] ?? $f['kategori']);
                $newFileName = $kategoriName . '_' . $f['nama_asli'];
                $zip->addFile($filePath, "Bukti_Perjalanan/{$folderName}/{$newFileName}");
            }
        }
    }

    // Add evidence files for Biaya Lain (kegiatan_biaya_lain)
    $biayaLain = db_query("SELECT * FROM kegiatan_biaya_lain WHERE id_kegiatan = ? AND file_bukti != ''", [$id_kegiatan]);
    if (!empty($biayaLain)) {
        $folderBiayaLain = "Biaya_Lain";
        foreach ($biayaLain as $bl) {
            $filePath = __DIR__ . '/../uploads/kegiatan_' . $id_kegiatan . '/' . $bl['file_bukti'];
            if (file_exists($filePath)) {
                $ext = pathinfo($bl['file_bukti'], PATHINFO_EXTENSION);
                $safeName = safe_filename($bl['nama_biaya']) . '_' . $bl['id'] . ($ext ? '.' . $ext : '');
                $zip->addFile($filePath, "Bukti_Perjalanan/{$folderBiayaLain}/{$safeName}");
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
