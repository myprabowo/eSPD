<?php
/**
 * file_api.php — File Upload/Download API for SPD evidence documents
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

session_start_safe();
require_login();

$b = json_body();
$action = $b['action'] ?? $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    case 'upload':
        $id_spd   = (int)($_POST['id_spd'] ?? 0);
        $kategori = trim($_POST['kategori'] ?? 'bukti_lain');
        
        if (!$id_spd) json_response(['success' => false, 'message' => 'ID SPD diperlukan.']);
        
        // Verify SPD exists
        $spd = db_query("SELECT id FROM spd WHERE id = ?", [$id_spd]);
        if (empty($spd)) json_response(['success' => false, 'message' => 'SPD tidak ditemukan.']);

        // Validate category
        $validCats = array_keys(file_categories());
        if (!in_array($kategori, $validCats)) $kategori = 'bukti_lain';

        if (empty($_FILES['file'])) {
            json_response(['success' => false, 'message' => 'File tidak ditemukan.']);
        }

        $file = $_FILES['file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            json_response(['success' => false, 'message' => 'Upload gagal: error code ' . $file['error']]);
        }

        // Validate size (max 20MB)
        if ($file['size'] > 20 * 1024 * 1024) {
            json_response(['success' => false, 'message' => 'File terlalu besar (max 20MB).']);
        }

        // Validate MIME type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        if (!in_array($mimeType, allowed_mime_types())) {
            json_response(['success' => false, 'message' => "Tipe file tidak diizinkan: {$mimeType}"]);
        }

        // Generate unique filename
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $namaFile = uniqid($kategori . '_') . '.' . strtolower($ext);
        
        $uploadDir = spd_upload_dir($id_spd);
        $destPath = $uploadDir . '/' . $namaFile;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            json_response(['success' => false, 'message' => 'Gagal menyimpan file.']);
        }

        db_execute(
            "INSERT INTO spd_files (id_spd, kategori, nama_file, nama_asli, ukuran, mime_type) VALUES (?,?,?,?,?,?)",
            [$id_spd, $kategori, $namaFile, $file['name'], $file['size'], $mimeType]
        );
        $new_id = db_last_id();
        log_activity('UPLOAD', "Mengunggah file bukti SPD (ID SPD: $id_spd, ID File: $new_id)");

        json_response([
            'success' => true, 
            'message' => 'File berhasil diunggah!',
            'file' => [
                'id'         => $new_id,
                'nama_file'  => $namaFile,
                'nama_asli'  => $file['name'],
                'kategori'   => $kategori,
                'ukuran'     => $file['size'],
                'mime_type'  => $mimeType,
            ]
        ]);
        break;

    case 'list':
        $id_spd = (int)($_GET['id_spd'] ?? 0);
        if (!$id_spd) json_response(['success' => false, 'message' => 'ID SPD diperlukan.']);
        $files = db_query("SELECT * FROM spd_files WHERE id_spd = ? ORDER BY kategori, created_at", [$id_spd]);
        json_response(['success' => true, 'files' => $files]);
        break;

    case 'delete':
        $id = (int)($b['id'] ?? 0);
        if (!$id) json_response(['success' => false, 'message' => 'ID file diperlukan.']);

        $file = db_query("SELECT * FROM spd_files WHERE id = ?", [$id]);
        if (empty($file)) json_response(['success' => false, 'message' => 'File tidak ditemukan.']);
        $f = $file[0];

        // Check ownership via SPD -> Kegiatan
        if (current_role() !== 'Admin Super') {
            $owner = db_query(
                "SELECT s.id FROM spd s JOIN kegiatan k ON s.id_kegiatan = k.id WHERE s.id = ? AND k.created_by = ?",
                [$f['id_spd'], current_username()]
            );
            if (empty($owner)) json_response(['success' => false, 'message' => 'Akses ditolak.']);
        }

        // Delete from disk
        $filePath = __DIR__ . '/../uploads/spd_' . $f['id_spd'] . '/' . $f['nama_file'];
        if (file_exists($filePath)) unlink($filePath);

        db_execute("DELETE FROM spd_files WHERE id = ?", [$id]);
        log_activity('DELETE', "Menghapus file bukti SPD (ID File: $id)");
        json_response(['success' => true, 'message' => 'File berhasil dihapus!']);
        break;

    case 'download':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { http_response_code(404); exit('Not found'); }

        $file = db_query("SELECT * FROM spd_files WHERE id = ?", [$id]);
        if (empty($file)) { http_response_code(404); exit('Not found'); }
        $f = $file[0];

        $filePath = __DIR__ . '/../uploads/spd_' . $f['id_spd'] . '/' . $f['nama_file'];
        if (!file_exists($filePath)) { http_response_code(404); exit('File not found on disk'); }

        header('Content-Type: ' . ($f['mime_type'] ?: 'application/octet-stream'));
        header('Content-Disposition: inline; filename="' . $f['nama_asli'] . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;

    default:
        json_response(['success' => false, 'message' => 'Action tidak dikenal.']);
}
