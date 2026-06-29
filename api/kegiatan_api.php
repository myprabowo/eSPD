<?php
/**
 * kegiatan_api.php — CRUD API for Kegiatan (Training/Activity)
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

session_start_safe();
require_login();

$b      = json_body();
$action = $b['action'] ?? $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    case 'list':
        $params = [];
        $userFilter = '';
        if (current_role() !== 'Admin Super') {
            $userFilter = ' WHERE k.created_by = ?';
            $params[] = current_username();
        }
        $rows = db_query(
            "SELECT k.*, 
                    (SELECT COUNT(*) FROM spd WHERE id_kegiatan = k.id) as jumlah_spd
             FROM kegiatan k 
             $userFilter
             ORDER BY k.tanggal_st ASC, k.nama_kegiatan ASC",
            $params
        );
        json_response(['success' => true, 'rows' => $rows]);
        break;

    case 'get':
        $id = (int)($b['id'] ?? 0);
        if (!$id) json_response(['success' => false, 'message' => 'ID diperlukan.']);
        $params = [$id];
        $userFilter = '';
        if (current_role() !== 'Admin Super') {
            $userFilter = ' AND created_by = ?';
            $params[] = current_username();
        }
        $row = db_query("SELECT * FROM kegiatan WHERE id = ?$userFilter", $params);
        if (empty($row)) json_response(['success' => false, 'message' => 'Kegiatan tidak ditemukan atau akses ditolak.']);
        json_response(['success' => true, 'row' => $row[0]]);
        break;

    case 'create':
        $nama = trim($b['nama_kegiatan'] ?? '');
        if ($nama === '') json_response(['success' => false, 'message' => 'Nama kegiatan wajib diisi.']);
        db_execute(
            "INSERT INTO kegiatan (nama_kegiatan, nomor_st, tanggal_st, perihal_st, kota_tujuan, persekot, created_by) VALUES (?,?,?,?,?,?,?)",
            [
                $nama,
                trim($b['nomor_st']   ?? ''),
                trim($b['tanggal_st'] ?? ''),
                trim($b['perihal_st'] ?? ''),
                trim($b['kota_tujuan'] ?? ''),
                (float)($b['persekot'] ?? 0),
                current_username(),
            ]
        );
        $new_id = db_last_id();
        log_activity('CREATE', "Membuat kegiatan baru: $nama (ID: $new_id)");
        json_response(['success' => true, 'message' => 'Kegiatan berhasil disimpan!', 'id' => $new_id]);
        break;

    case 'update':
        $id   = (int)($b['id'] ?? 0);
        $nama = trim($b['nama_kegiatan'] ?? '');
        if (!$id || $nama === '') json_response(['success' => false, 'message' => 'Parameter kurang.']);
        $params = [
                $nama,
                trim($b['nomor_st']   ?? ''),
                trim($b['tanggal_st'] ?? ''),
                trim($b['perihal_st'] ?? ''),
                trim($b['kota_tujuan'] ?? ''),
                (float)($b['persekot'] ?? 0),
                $id,
            ];
        $userFilter = '';
        if (current_role() !== 'Admin Super') {
            $userFilter = ' AND created_by = ?';
            $params[] = current_username();
        }
        db_execute(
            "UPDATE kegiatan SET nama_kegiatan=?, nomor_st=?, tanggal_st=?, perihal_st=?, kota_tujuan=?, persekot=?, updated_at=CURRENT_TIMESTAMP WHERE id=? $userFilter",
            $params
        );
        log_activity('UPDATE', "Mengubah data kegiatan (ID: $id)");
        json_response(['success' => true, 'message' => 'Kegiatan berhasil diperbarui!']);
        break;

    case 'delete':
        $id = (int)($b['id'] ?? 0);
        if (!$id) json_response(['success' => false, 'message' => 'ID diperlukan.']);
        $params = [$id];
        $userFilter = '';
        if (current_role() !== 'Admin Super') {
            $userFilter = ' AND created_by = ?';
            $params[] = current_username();
        }
        // Cek hak akses dulu
        $check = db_query("SELECT id FROM kegiatan WHERE id = ?$userFilter", $params);
        if (empty($check)) json_response(['success' => false, 'message' => 'Kegiatan tidak ditemukan atau akses ditolak.']);
        // Check if SPD exists
        $used = db_query("SELECT COUNT(*) as cnt FROM spd WHERE id_kegiatan = ?", [$id]);
        if (($used[0]['cnt'] ?? 0) > 0) {
            json_response(['success' => false, 'message' => 'GAGAL: Masih ada SPD terdaftar di kegiatan ini.']);
        }
        $count = db_execute("DELETE FROM kegiatan WHERE id=?$userFilter", $params);
        if ($count > 0) {
            log_activity('DELETE', "Menghapus kegiatan (ID: $id)");
            json_response(['success' => true, 'message' => 'Kegiatan berhasil dihapus!']);
        }
        json_response(['success' => false, 'message' => 'Gagal menghapus kegiatan.']);
        break;
    case 'submit_all':
        $id = (int)($b['id'] ?? 0);
        if (!$id) json_response(['success' => false, 'message' => 'ID diperlukan.']);
        $params = [$id];
        $userFilter = '';
        if (current_role() !== 'Admin Super') {
            $userFilter = ' AND created_by = ?';
            $params[] = current_username();
        }
        $check = db_query("SELECT id FROM kegiatan WHERE id = ?$userFilter", $params);
        if (empty($check)) json_response(['success' => false, 'message' => 'Kegiatan tidak ditemukan atau akses ditolak.']);
        
        $count = db_execute("UPDATE spd SET status = 'submitted', updated_at = CURRENT_TIMESTAMP WHERE id_kegiatan = ? AND status = 'draft'", [$id]);
        log_activity('UPDATE', "Submit seluruh SPD draft (ID Kegiatan: $id) sejumlah $count data");
        json_response(['success' => true, 'message' => "Berhasil mengajukan $count SPD."]);
        break;

    default:
        json_response(['success' => false, 'message' => 'Action tidak dikenal.']);
}
