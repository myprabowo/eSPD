<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

session_start_safe();
require_login();

$b = json_body();
$action = $b['action'] ?? $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        $id_kegiatan = (int)($_GET['id_kegiatan'] ?? $b['id_kegiatan'] ?? 0);
        if (!$id_kegiatan) json_response(['success' => false, 'message' => 'ID Kegiatan diperlukan.']);
        $rows = db_query("SELECT * FROM kegiatan_biaya_lain WHERE id_kegiatan = ? ORDER BY id ASC", [$id_kegiatan]);
        json_response(['success' => true, 'rows' => $rows]);
        break;

    case 'create':
        $id_kegiatan = (int)($_POST['id_kegiatan'] ?? $b['id_kegiatan'] ?? 0);
        $nama_biaya = trim($_POST['nama_biaya'] ?? $b['nama_biaya'] ?? '');
        $jumlah = (float)($_POST['jumlah'] ?? $b['jumlah'] ?? 0);
        $keterangan = trim($_POST['keterangan'] ?? $b['keterangan'] ?? '');
        $tanggal = trim($_POST['tanggal'] ?? $b['tanggal'] ?? '');
        
        if (!$id_kegiatan || !$nama_biaya) {
            json_response(['success' => false, 'message' => 'Nama biaya diperlukan.']);
        }

        $namaFile = '';
        if (!empty($_FILES['file']['name'])) {
            $file = $_FILES['file'];
            if ($file['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $namaFile = uniqid('bl_') . '.' . strtolower($ext);
                $uploadDir = __DIR__ . '/../uploads/kegiatan_' . $id_kegiatan;
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                $destPath = $uploadDir . '/' . $namaFile;
                if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                    json_response(['success' => false, 'message' => 'Gagal mengunggah bukti.']);
                }
            }
        }
        
        db_execute(
            "INSERT INTO kegiatan_biaya_lain (id_kegiatan, nama_biaya, jumlah, keterangan, tanggal, file_bukti) VALUES (?, ?, ?, ?, ?, ?)",
            [$id_kegiatan, $nama_biaya, $jumlah, $keterangan, $tanggal, $namaFile]
        );
        $new_id = db_last_id();
        log_activity('CREATE', "Menambahkan biaya lain (ID Kegiatan: $id_kegiatan, ID Biaya: $new_id)");
        json_response(['success' => true, 'message' => 'Biaya lain berhasil ditambahkan!']);
        break;

    case 'update':
        $id = (int)($_POST['id'] ?? $b['id'] ?? 0);
        $id_kegiatan = (int)($_POST['id_kegiatan'] ?? $b['id_kegiatan'] ?? 0);
        $nama_biaya = trim($_POST['nama_biaya'] ?? $b['nama_biaya'] ?? '');
        $jumlah = (float)($_POST['jumlah'] ?? $b['jumlah'] ?? 0);
        $keterangan = trim($_POST['keterangan'] ?? $b['keterangan'] ?? '');
        $tanggal = trim($_POST['tanggal'] ?? $b['tanggal'] ?? '');
        
        if (!$id || !$nama_biaya) {
            json_response(['success' => false, 'message' => 'ID dan Nama biaya diperlukan.']);
        }

        $row = db_query("SELECT id_kegiatan, file_bukti FROM kegiatan_biaya_lain WHERE id = ?", [$id]);
        if (empty($row)) {
            json_response(['success' => false, 'message' => 'Data tidak ditemukan.']);
        }
        
        $namaFile = $row[0]['file_bukti'];
        
        if (!empty($_FILES['file']['name'])) {
            $file = $_FILES['file'];
            if ($file['error'] === UPLOAD_ERR_OK) {
                // Delete old file if exists
                if ($namaFile) {
                    $oldPath = __DIR__ . '/../uploads/kegiatan_' . $row[0]['id_kegiatan'] . '/' . $namaFile;
                    if (file_exists($oldPath)) unlink($oldPath);
                }
                
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $namaFile = uniqid('bl_') . '.' . strtolower($ext);
                $uploadDir = __DIR__ . '/../uploads/kegiatan_' . $id_kegiatan;
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                $destPath = $uploadDir . '/' . $namaFile;
                if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                    json_response(['success' => false, 'message' => 'Gagal mengunggah bukti.']);
                }
            }
        }
        
        db_execute(
            "UPDATE kegiatan_biaya_lain SET nama_biaya = ?, jumlah = ?, keterangan = ?, tanggal = ?, file_bukti = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$nama_biaya, $jumlah, $keterangan, $tanggal, $namaFile, $id]
        );
        log_activity('UPDATE', "Mengubah data biaya lain (ID: $id)");
        json_response(['success' => true, 'message' => 'Biaya lain berhasil diperbarui!']);
        break;

    case 'delete':
        $id = (int)($b['id'] ?? $_POST['id'] ?? 0);
        if (!$id) json_response(['success' => false, 'message' => 'ID diperlukan.']);
        
        $row = db_query("SELECT id_kegiatan, file_bukti FROM kegiatan_biaya_lain WHERE id = ?", [$id]);
        if (!empty($row)) {
            $f = $row[0]['file_bukti'];
            $k = $row[0]['id_kegiatan'];
            if ($f) {
                $path = __DIR__ . '/../uploads/kegiatan_' . $k . '/' . $f;
                if (file_exists($path)) unlink($path);
            }
        }
        
        db_execute("DELETE FROM kegiatan_biaya_lain WHERE id = ?", [$id]);
        log_activity('DELETE', "Menghapus biaya lain (ID: $id)");
        json_response(['success' => true, 'message' => 'Biaya lain berhasil dihapus!']);
        break;
        
    case 'download':
        $id = (int)($_GET['id'] ?? 0);
        $row = db_query("SELECT id_kegiatan, file_bukti FROM kegiatan_biaya_lain WHERE id = ?", [$id]);
        if (empty($row) || empty($row[0]['file_bukti'])) die('File tidak ditemukan');
        $path = __DIR__ . '/../uploads/kegiatan_' . $row[0]['id_kegiatan'] . '/' . $row[0]['file_bukti'];
        if (!file_exists($path)) die('File tidak ada di server');
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mimes = [
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg'
        ];
        $mime = $mimes[$ext] ?? 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . $row[0]['file_bukti'] . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;

    default:
        json_response(['success' => false, 'message' => 'Action tidak dikenal.']);
}
