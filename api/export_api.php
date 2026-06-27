<?php
/**
 * export_api.php — Export SPD data as XLSX or ZIP bundle
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/xlsx_export.php';

session_start_safe();
require_login();

$action = $_GET['action'] ?? '';

switch ($action) {

    case 'export_excel':
        $id_kegiatan = (int)($_GET['id_kegiatan'] ?? 0);
        if (!$id_kegiatan) { http_response_code(400); exit('ID kegiatan diperlukan'); }

        $kegiatan = db_query("SELECT * FROM kegiatan WHERE id = ?", [$id_kegiatan]);
        if (empty($kegiatan)) { http_response_code(404); exit('Kegiatan tidak ditemukan'); }
        $kegiatan = $kegiatan[0];

        $spds = db_query("SELECT * FROM spd WHERE id_kegiatan = ? ORDER BY nama ASC", [$id_kegiatan]);

        $header = [
            'No', 'No SPPD', 'Tgl SPPD', 'Nama', 'NIP', 'Golongan', 'Pangkat', 'Jabatan', 'Instansi',
            'Kota Asal', 'Kota Tujuan', 'Tgl Mulai', 'Tgl Akhir',
            'Tiket Berangkat', 'Tiket Pulang', 'Total Tiket',
            'UH Hari', 'UH/Hari', 'UH Total',
            'Hotel Tarif', 'Hotel Hari', 'Hotel Total',
            'Transport Total', 'Uang Representatif Total',
            'Grand Total', 'Persekot', 'Kurang/Lebih',
            'No Rekening', 'Bank', 'Status',
        ];

        $rows = [];
        foreach ($spds as $i => $spd) {
            $spd = compute_spd_totals($spd);
            $rows[] = [
                $i + 1,
                $spd['no_sppd'], $spd['tgl_sppd'], $spd['nama'], $spd['nip'],
                $spd['golongan'], $spd['pangkat'], $spd['jabatan'], $spd['instansi'],
                $spd['kota_asal'], $spd['kota_tujuan'], $spd['tgl_mulai'], $spd['tgl_akhir'],
                $spd['tiket_berangkat'], $spd['tiket_pulang'], $spd['total_tiket'],
                $spd['uh_jml_hari'], $spd['uh_per_hari'], $spd['total_uang_harian'],
                $spd['hotel1_tarif'], $spd['hotel1_hari'], $spd['total_hotel'],
                $spd['total_transport'], $spd['uang_representatif_total'],
                $spd['grand_total'], $spd['persekot'], $spd['kurang_lebih'],
                $spd['no_rekening'], $spd['bank'], $spd['status'],
            ];
        }

        $xlsxPath = generate_xlsx($header, $rows, 'Rekap SPD');
        if (!$xlsxPath || !file_exists($xlsxPath)) {
            http_response_code(500); exit('Gagal membuat file Excel');
        }

        $filename = 'Rekap_SPD_' . safe_filename($kegiatan['nama_kegiatan']) . '_' . date('Y-m-d') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($xlsxPath));
        readfile($xlsxPath);
        unlink($xlsxPath);
        exit;

    case 'export_zip':
        $id_kegiatan = (int)($_GET['id_kegiatan'] ?? 0);
        if (!$id_kegiatan) { http_response_code(400); exit('ID kegiatan diperlukan'); }

        $kegiatan = db_query("SELECT * FROM kegiatan WHERE id = ?", [$id_kegiatan]);
        if (empty($kegiatan)) { http_response_code(404); exit('Kegiatan tidak ditemukan'); }
        $kegiatan = $kegiatan[0];

        $zipPath = generate_export_zip($id_kegiatan);
        if (!$zipPath || !file_exists($zipPath)) {
            http_response_code(500); exit('Gagal membuat file ZIP');
        }

        $filename = 'SPD_' . safe_filename($kegiatan['nama_kegiatan']) . '_' . date('Y-m-d') . '.zip';
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($zipPath));
        readfile($zipPath);
        unlink($zipPath);
        exit;

    default:
        http_response_code(400);
        echo 'Action tidak dikenal. Gunakan: export_excel atau export_zip';
        exit;
}
