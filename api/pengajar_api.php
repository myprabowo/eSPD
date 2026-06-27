<?php
/**
 * pengajar_api.php — Read-only proxy to MySQL Sistem Pelatihan
 * Provides pengajar (instructor) data for SPD person selection.
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

session_start_safe();
require_login();

$b      = json_body();
$action = $b['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    case 'list':
        $rows = db_mysql_query(
            "SELECT id, nama, instansi, nip, golongan, nik, bank, no_rekening, no_hp
             FROM pengajar WHERE deleted_at IS NULL ORDER BY nama ASC"
        );
        json_response(['success' => true, 'rows' => $rows]);
        break;

    case 'search':
        $q = trim($b['q'] ?? $_GET['q'] ?? '');
        if (strlen($q) < 2) {
            json_response(['success' => true, 'rows' => []]);
        }
        $rows = db_mysql_query(
            "SELECT id, nama, instansi, nip, golongan, nik, bank, no_rekening, no_hp
             FROM pengajar 
             WHERE deleted_at IS NULL AND (nama LIKE ? OR nip LIKE ? OR instansi LIKE ?)
             ORDER BY nama ASC LIMIT 20",
            ["%$q%", "%$q%", "%$q%"]
        );
        json_response(['success' => true, 'rows' => $rows]);
        break;

    default:
        json_response(['success' => false, 'message' => 'Action tidak dikenal.']);
}
