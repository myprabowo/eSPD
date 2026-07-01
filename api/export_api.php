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

        // Check ownership
        if (current_role() !== 'Admin Super' && ($kegiatan['created_by'] ?? '') !== current_username()) {
            http_response_code(403); exit('Akses ditolak');
        }

        $spds = db_query("SELECT * FROM spd WHERE id_kegiatan = ? ORDER BY nama ASC", [$id_kegiatan]);

        $xlsxPath = generate_spd_excel_file($kegiatan, $spds);
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

        // Check ownership
        if (current_role() !== 'Admin Super' && ($kegiatan['created_by'] ?? '') !== current_username()) {
            http_response_code(403); exit('Akses ditolak');
        }

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
