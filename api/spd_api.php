<?php
/**
 * spd_api.php — CRUD API for SPD (Surat Perintah Perjalanan Dinas)
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

session_start_safe();
require_login();

$b      = json_body();
$action = $b['action'] ?? $_POST['action'] ?? $_GET['action'] ?? '';

function check_spd_access(int $id): bool {
    if (current_role() === 'Admin Super') return true;
    $row = db_query("SELECT id FROM spd WHERE id = ? AND created_by = ?", [$id, current_username()]);
    return !empty($row);
}

switch ($action) {

    case 'list':
        $id_kegiatan = (int)($b['id_kegiatan'] ?? $_GET['id_kegiatan'] ?? 0);
        if (!$id_kegiatan) json_response(['success' => false, 'message' => 'ID kegiatan diperlukan.']);
        $params = [$id_kegiatan];
        $userFilter = '';
        if (current_role() !== 'Admin Super') {
            $userFilter = ' AND s.created_by = ?';
            $params[] = current_username();
        }
        
        $rows = db_query(
            "SELECT s.*, (SELECT COUNT(*) FROM spd_files WHERE id_spd = s.id) as jumlah_file
             FROM spd s WHERE s.id_kegiatan = ?$userFilter ORDER BY s.nama ASC",
            $params
        );
        // Compute totals for each row
        $rows = array_map('compute_spd_totals', $rows);
        
        json_response(['success' => true, 'rows' => $rows]);
        break;

    case 'get':
        $id = (int)($b['id'] ?? $_GET['id'] ?? 0);
        if (!$id) json_response(['success' => false, 'message' => 'ID diperlukan.']);
        if (!check_spd_access($id)) json_response(['success' => false, 'message' => 'Akses ditolak.']);
        $row = db_query("SELECT * FROM spd WHERE id = ?", [$id]);
        if (empty($row)) json_response(['success' => false, 'message' => 'SPD tidak ditemukan.']);
        $row = compute_spd_totals($row[0]);
        
        // Get attached files
        $files = db_query("SELECT * FROM spd_files WHERE id_spd = ? ORDER BY kategori, created_at", [$id]);
        $row['files'] = $files;
        
        json_response(['success' => true, 'row' => $row]);
        break;

    case 'create':
        $id_kegiatan = (int)($b['id_kegiatan'] ?? 0);
        $nama = trim($b['nama'] ?? '');
        if (!$id_kegiatan) json_response(['success' => false, 'message' => 'ID kegiatan diperlukan.']);
        if ($nama === '') json_response(['success' => false, 'message' => 'Nama wajib diisi.']);

        // Get kegiatan default values
        $kegiatan = db_query("SELECT * FROM kegiatan WHERE id = ?", [$id_kegiatan]);
        $kota_tujuan = $kegiatan[0]['kota_tujuan'] ?? '';
        
        $golongan = trim($b['golongan'] ?? '');
        $pangkat = trim($b['pangkat'] ?? '') ?: get_pangkat_from_golongan($golongan);

        db_execute(
            "INSERT INTO spd (id_kegiatan, id_pengajar, nama, nip, golongan, pangkat, jabatan, instansi, 
                             kota_tujuan, tiba_di, no_rekening, bank, nama_rekening, created_by) 
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [
                $id_kegiatan,
                (int)($b['id_pengajar'] ?? 0) ?: null,
                $nama,
                trim($b['nip'] ?? ''),
                $golongan,
                $pangkat,
                trim($b['jabatan'] ?? ''),
                trim($b['instansi'] ?? ''),
                $kota_tujuan,
                $kota_tujuan,
                trim($b['no_rekening'] ?? ''),
                trim($b['bank'] ?? ''),
                trim($b['nama_rekening'] ?? $b['nama'] ?? ''),
                current_username(),
            ]
        );
        $new_id = db_last_id();
        log_activity('CREATE', "Membuat SPD baru: $nama (ID: $new_id)");
        json_response(['success' => true, 'message' => 'SPD berhasil dibuat!', 'id' => $new_id]);
        break;

    case 'update':
        $id = (int)($b['id'] ?? 0);
        if (!$id) json_response(['success' => false, 'message' => 'ID diperlukan.']);
        if (!check_spd_access($id)) json_response(['success' => false, 'message' => 'Akses ditolak.']);

        // Build dynamic update from allowed fields
        $fields = spd_field_definitions();
        $setClauses = [];
        $params = [];

        foreach ($b as $key => $value) {
            if ($key === 'id' || $key === 'action') continue;
            if (!is_valid_spd_field($key)) continue;

            $fieldDef = $fields[$key];
            if ($fieldDef['type'] === 'number') {
                $params[] = (float) $value;
            } else {
                $params[] = trim((string) $value);
            }
            $setClauses[] = "$key = ?";
        }

        if (empty($setClauses)) {
            json_response(['success' => false, 'message' => 'Tidak ada field yang diubah.']);
        }

        $setClauses[] = "updated_at = CURRENT_TIMESTAMP";
        $params[] = $id;

        $sql = "UPDATE spd SET " . implode(', ', $setClauses) . " WHERE id = ?";
        db_execute($sql, $params);

        // Return updated row with computed totals
        $row = db_query("SELECT * FROM spd WHERE id = ?", [$id]);
        $row = compute_spd_totals($row[0]);
        
        log_activity('UPDATE', "Mengubah data SPD (ID: $id)");

        json_response(['success' => true, 'message' => 'SPD berhasil diperbarui!', 'row' => $row]);
        break;

    case 'inline_update':
        // Single field inline update
        $id    = (int)($b['id'] ?? 0);
        $field = trim($b['field'] ?? '');
        $value = $b['value'] ?? '';
        
        if (!$id || !$field) json_response(['success' => false, 'message' => 'Parameter kurang.']);
        if (!is_valid_spd_field($field)) json_response(['success' => false, 'message' => 'Field tidak valid.']);
        if (!check_spd_access($id)) json_response(['success' => false, 'message' => 'Akses ditolak.']);

        $fieldDef = spd_field_definitions()[$field];
        if ($fieldDef['type'] === 'number') {
            $value = (float) $value;
        } else {
            $value = trim((string) $value);
        }

        $params = [$value, $id];
        $userFilter = '';
        if (current_role() !== 'Admin Super') {
            $userFilter = ' AND created_by = ?';
            $params[] = current_username();
        }
        db_execute(
            "UPDATE spd SET $field = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?$userFilter",
            $params
        );
        
        if ($field === 'golongan') {
            $pangkat = get_pangkat_from_golongan((string)$value);
            if ($pangkat !== '') {
                $pParams = [$pangkat, $id];
                if (current_role() !== 'Admin Super') $pParams[] = current_username();
                db_execute("UPDATE spd SET pangkat = ? WHERE id = ?$userFilter", $pParams);
            }
        }

        // Return computed totals
        $row = db_query("SELECT * FROM spd WHERE id = ?", [$id]);
        $row = compute_spd_totals($row[0]);

        json_response(['success' => true, 'row' => $row]);
        break;

    case 'update_status':
        $id     = (int)($b['id'] ?? 0);
        $status = trim($b['status'] ?? '');
        $allowed = ['draft', 'submitted', 'verified', 'paid'];
        if (!$id || !in_array($status, $allowed)) {
            json_response(['success' => false, 'message' => 'Parameter tidak valid.']);
        }
        if (!check_spd_access($id)) json_response(['success' => false, 'message' => 'Akses ditolak.']);
        db_execute("UPDATE spd SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?", [$status, $id]);
        log_activity('UPDATE', "Mengubah status SPD (ID: $id) menjadi $status");
        json_response(['success' => true, 'message' => "Status diubah ke: $status"]);
        break;

    case 'delete':
        $id = (int)($b['id'] ?? 0);
        if (!$id) json_response(['success' => false, 'message' => 'ID diperlukan.']);
        if (!check_spd_access($id)) json_response(['success' => false, 'message' => 'Akses ditolak.']);
        
        // Delete files from disk
        $files = db_query("SELECT * FROM spd_files WHERE id_spd = ?", [$id]);
        foreach ($files as $f) {
            $path = __DIR__ . '/../uploads/spd_' . $id . '/' . $f['nama_file'];
            if (file_exists($path)) unlink($path);
        }
        $uploadDir = __DIR__ . '/../uploads/spd_' . $id;
        if (is_dir($uploadDir)) rmdir($uploadDir);
        
        db_execute("DELETE FROM spd_files WHERE id_spd = ?", [$id]);
        db_execute("DELETE FROM spd WHERE id = ?", [$id]);
        
        log_activity('DELETE', "Menghapus data SPD (ID: $id)");
        
        json_response(['success' => true, 'message' => 'SPD berhasil dihapus!']);
        break;

    case 'bulk_create':
        // Create multiple SPDs from selected pengajar IDs
        $id_kegiatan = (int)($b['id_kegiatan'] ?? 0);
        $pengajar_ids = $b['pengajar_ids'] ?? [];
        
        if (!$id_kegiatan || empty($pengajar_ids)) {
            json_response(['success' => false, 'message' => 'Parameter kurang.']);
        }

        $kegiatan = db_query("SELECT * FROM kegiatan WHERE id = ?", [$id_kegiatan]);
        $kota_tujuan = $kegiatan[0]['kota_tujuan'] ?? '';
        
        $count = 0;
        $skipped = 0;
        foreach ($pengajar_ids as $pid) {
            $pid = (int) $pid;
            // Check duplicate
            $exists = db_query(
                "SELECT id FROM spd WHERE id_kegiatan = ? AND id_pengajar = ?",
                [$id_kegiatan, $pid]
            );
            if (!empty($exists)) { $skipped++; continue; }

            // Fetch pengajar data from MySQL
            $pengajar = db_mysql_query(
                "SELECT * FROM pengajar WHERE id = ? AND deleted_at IS NULL",
                [$pid]
            );
            if (empty($pengajar)) { $skipped++; continue; }
            $p = $pengajar[0];

            $golongan = $p['golongan'] ?? '';
            $pangkat = get_pangkat_from_golongan($golongan);
            
            db_execute(
                "INSERT INTO spd (id_kegiatan, id_pengajar, nama, nip, golongan, pangkat, jabatan, instansi, 
                                 kota_tujuan, tiba_di, no_rekening, bank, nama_rekening, created_by) 
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                [
                    $id_kegiatan, $pid,
                    $p['nama'], $p['nip'] ?? '', $golongan, $pangkat,
                    '', $p['instansi'] ?? '',
                    $kota_tujuan, $kota_tujuan,
                    $p['no_rekening'] ?? '', $p['bank'] ?? '', $p['nama'] ?? '',
                    current_username(),
                ]
            );
            $count++;
        }
        
        $msg = "Berhasil menambahkan {$count} SPD.";
        if ($skipped > 0) $msg .= " ({$skipped} dilewati karena duplikat/tidak ditemukan)";
        json_response(['success' => true, 'message' => $msg]);
        break;

    default:
        json_response(['success' => false, 'message' => 'Action tidak dikenal.']);
}
