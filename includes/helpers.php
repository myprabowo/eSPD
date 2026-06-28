<?php
/**
 * helpers.php — eSPD Utility Functions
 */

/** Indonesian month names (1-indexed). */
const BULAN_ID = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret',    4 => 'April',
    5 => 'Mei',     6 => 'Juni',     7 => 'Juli',      8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
];

function format_rupiah(int|float $amount): string {
    if ($amount == 0) return '-';
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function format_tanggal_indo(string $date): string {
    if (empty($date)) return '-';
    $ts = strtotime($date);
    if (!$ts) return $date;
    $d = (int) date('j', $ts);
    $m = (int) date('n', $ts);
    $y = date('Y', $ts);
    return $d . ' ' . (BULAN_ID[$m] ?? '') . ' ' . $y;
}

function safe_filename(string $s): string {
    return preg_replace('/[^a-zA-Z0-9_\-]/', '_', $s);
}

function json_response(array $data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function get_pangkat_from_golongan(string $golongan): string {
    $gol = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $golongan));
    $map = [
        'IA' => 'Juru Muda', 'IB' => 'Juru Muda Tingkat 1', 'IC' => 'Juru', 'ID' => 'Juru Tingkat 1',
        'IIA' => 'Pengatur Muda', 'IIB' => 'Pengatur Muda Tingkat 1', 'IIC' => 'Pengatur', 'IID' => 'Pengatur Tingkat 1',
        'IIIA' => 'Penata Muda', 'IIIB' => 'Penata Muda Tingkat 1', 'IIIC' => 'Penata', 'IIID' => 'Penata Tingkat 1',
        'IVA' => 'Pembina', 'IVB' => 'Pembina Tingkat 1', 'IVC' => 'Pembina Utama Muda', 'IVD' => 'Pembina Utama Madya', 'IVE' => 'Pembina Utama',
    ];
    return $map[$gol] ?? '';
}

function json_body(): array {
    static $body = null;
    if ($body === null) {
        $raw  = file_get_contents('php://input');
        $body = json_decode($raw, true) ?? [];
    }
    return $body;
}

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/audit_logger.php';

function h(mixed $s): string {
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Compute all derived/total fields for an SPD row.
 */
function compute_spd_totals(array $row): array {
    // Uang Harian totals
    $row['uh_total']  = ($row['uh_jml_hari'] ?? 0) * ($row['uh_per_hari'] ?? 0);
    $row['uh2_total'] = ($row['uh2_jml_hari'] ?? 0) * ($row['uh2_per_hari'] ?? 0);
    $row['uh3_total'] = ($row['uh3_jml_hari'] ?? 0) * ($row['uh3_per_hari'] ?? 0);
    $row['uh_fullboard_total'] = ($row['uh_fullboard_jml_hari'] ?? 0) * ($row['uh_fullboard_per_hari'] ?? 0);
    
    $row['total_uang_harian'] = $row['uh_total'] + $row['uh2_total'] + $row['uh3_total'] + $row['uh_fullboard_total'];

    // Hotel totals
    $row['hotel1_total'] = ($row['hotel1_tarif'] ?? 0) * ($row['hotel1_hari'] ?? 0);
    $row['hotel2_total'] = ($row['hotel2_tarif'] ?? 0) * ($row['hotel2_hari'] ?? 0);
    $row['hotel3_total'] = ($row['hotel3_tarif'] ?? 0) * ($row['hotel3_hari'] ?? 0);
    $row['hotel4_total'] = ($row['hotel4_tarif'] ?? 0) * ($row['hotel4_hari'] ?? 0);
    $row['hotel5_total'] = ($row['hotel5_tarif'] ?? 0) * ($row['hotel5_hari'] ?? 0);
    $row['hotel6_total'] = ($row['hotel6_tarif'] ?? 0) * ($row['hotel6_hari'] ?? 0);
    
    $row['total_hotel'] = $row['hotel1_total'] + $row['hotel2_total'] + $row['hotel3_total'] 
                        + $row['hotel4_total'] + $row['hotel5_total'] + $row['hotel6_total'];

    // Tiket total
    $row['total_tiket'] = (float)($row['tiket_berangkat'] ?? 0) + (float)($row['tiket_pulang'] ?? 0) + (float)($row['tiket_pp'] ?? 0);

    // Uang representatif total
    $row['uang_representatif_total'] = ($row['uang_representatif'] ?? 0) * ($row['uang_representatif_hari'] ?? 0);

    // DPR Penginapan
    $row['dpr_biaya_penginapan'] = ($row['dpr_tarif_hotel'] ?? 0) * ($row['dpr_malam_hotel'] ?? 0) * ($row['dpr_koefisien'] ?? 0.3);

    // Transport total
    $row['total_transport'] = ($row['transport_pp'] ?? 0) + ($row['transport_ke_bandara'] ?? 0) 
                            + ($row['transport_bandara_tujuan'] ?? 0) + ($row['transport_tujuan_bandara'] ?? 0) 
                            + ($row['transport_bandara_kedudukan'] ?? 0) + ($row['transport_kedudukan_tujuan'] ?? 0);

    // Covid total
    $row['total_covid'] = ($row['covid_berangkat'] ?? 0) + ($row['covid_pulang'] ?? 0) + ($row['covid_tanpa_bukti'] ?? 0);

    // Biaya bukti total
    $row['total_biaya_bukti'] = ($row['biaya_tol'] ?? 0) + ($row['biaya_taksi'] ?? 0) 
                              + ($row['biaya_bensin'] ?? 0) + ($row['biaya_riil_lainnya'] ?? 0);

    // Grand Total
    $row['grand_total'] = $row['total_tiket'] + $row['total_uang_harian'] + $row['total_hotel'] 
                        + $row['dpr_biaya_penginapan'] + $row['total_transport'] 
                        + $row['total_covid'] + $row['total_biaya_bukti'] 
                        + $row['uang_representatif_total'];

    return $row;
}

/**
 * Get the list of all editable SPD fields with their types and labels.
 */
function spd_field_definitions(): array {
    return [
        // Identitas
        'no_sppd'        => ['type' => 'text',   'label' => 'No. SPPD',       'group' => 'identitas'],
        'tgl_sppd'       => ['type' => 'date',   'label' => 'Tgl. SPPD',      'group' => 'identitas'],
        'nama'           => ['type' => 'text',   'label' => 'Nama',           'group' => 'identitas'],
        'nip'            => ['type' => 'text',   'label' => 'NIP',            'group' => 'identitas'],
        'golongan'       => ['type' => 'text',   'label' => 'Golongan',       'group' => 'identitas'],
        'pangkat'        => ['type' => 'text',   'label' => 'Pangkat',        'group' => 'identitas'],
        'jabatan'        => ['type' => 'text',   'label' => 'Jabatan',        'group' => 'identitas'],
        'instansi'       => ['type' => 'text',   'label' => 'Instansi',       'group' => 'identitas'],
        
        // Perjalanan
        'kota_asal'      => ['type' => 'text',   'label' => 'Kota Asal',      'group' => 'perjalanan'],
        'kota_tujuan'    => ['type' => 'text',   'label' => 'Kota Tujuan',    'group' => 'perjalanan'],
        'tiba_di'        => ['type' => 'text',   'label' => 'Tiba Di',        'group' => 'perjalanan'],
        'tgl_mulai'      => ['type' => 'date',   'label' => 'Tgl. Mulai',     'group' => 'perjalanan'],
        'tgl_akhir'      => ['type' => 'date',   'label' => 'Tgl. Akhir',     'group' => 'perjalanan'],
        'alat_angkut'    => ['type' => 'text',   'label' => 'Alat Angkut',    'group' => 'perjalanan'],
        
        // Tiket
        'tiket_pp'          => ['type' => 'text',   'label' => 'Tiket PP',         'group' => 'tiket'],
        'tiket_berangkat'   => ['type' => 'number', 'label' => 'Tiket Berangkat',  'group' => 'tiket'],
        'tiket_pulang'      => ['type' => 'number', 'label' => 'Tiket Pulang',     'group' => 'tiket'],
        
        // Uang Harian
        'uh_jml_hari'    => ['type' => 'number', 'label' => 'UH Jml Hari',     'group' => 'uang_harian'],
        'uh_per_hari'    => ['type' => 'number', 'label' => 'UH Per Hari',     'group' => 'uang_harian'],
        'uh2_jml_hari'   => ['type' => 'number', 'label' => 'UH2 Jml Hari',    'group' => 'uang_harian'],
        'uh2_per_hari'   => ['type' => 'number', 'label' => 'UH2 Per Hari',    'group' => 'uang_harian'],
        'uh3_jml_hari'   => ['type' => 'number', 'label' => 'UH3 Jml Hari',    'group' => 'uang_harian'],
        'uh3_per_hari'   => ['type' => 'number', 'label' => 'UH3 Per Hari',    'group' => 'uang_harian'],
        'uh_fullboard_per_hari' => ['type' => 'number', 'label' => 'Fullboard/Hari', 'group' => 'uang_harian'],
        'uh_fullboard_jml_hari' => ['type' => 'number', 'label' => 'Fullboard Hari',  'group' => 'uang_harian'],
        
        // Hotel
        'tarif_maks_hotel' => ['type' => 'number', 'label' => 'Tarif Maks Hotel', 'group' => 'hotel'],
        'hotel1_tarif'     => ['type' => 'number', 'label' => 'Hotel 1 Tarif',    'group' => 'hotel'],
        'hotel1_hari'      => ['type' => 'number', 'label' => 'Hotel 1 Hari',     'group' => 'hotel'],
        'hotel2_tarif'     => ['type' => 'number', 'label' => 'Hotel 2 Tarif',    'group' => 'hotel'],
        'hotel2_hari'      => ['type' => 'number', 'label' => 'Hotel 2 Hari',     'group' => 'hotel'],
        'hotel3_tarif'     => ['type' => 'number', 'label' => 'Hotel 3 Tarif',    'group' => 'hotel'],
        'hotel3_hari'      => ['type' => 'number', 'label' => 'Hotel 3 Hari',     'group' => 'hotel'],
        'hotel4_tarif'     => ['type' => 'number', 'label' => 'Hotel 4 Tarif',    'group' => 'hotel'],
        'hotel4_hari'      => ['type' => 'number', 'label' => 'Hotel 4 Hari',     'group' => 'hotel'],
        'hotel5_tarif'     => ['type' => 'number', 'label' => 'Hotel 5 Tarif',    'group' => 'hotel'],
        'hotel5_hari'      => ['type' => 'number', 'label' => 'Hotel 5 Hari',     'group' => 'hotel'],
        'hotel6_tarif'     => ['type' => 'number', 'label' => 'Hotel 6 Tarif',    'group' => 'hotel'],
        'hotel6_hari'      => ['type' => 'number', 'label' => 'Hotel 6 Hari',     'group' => 'hotel'],
        
        // Penginapan DPR
        'dpr_tarif_hotel'     => ['type' => 'number', 'label' => 'DPR Tarif Hotel',    'group' => 'dpr'],
        'dpr_malam_hotel'     => ['type' => 'number', 'label' => 'DPR Malam Hotel',    'group' => 'dpr'],
        'dpr_koefisien'       => ['type' => 'number', 'label' => 'DPR Koefisien',      'group' => 'dpr'],
        
        // Transport DPR
        'transport_pp'               => ['type' => 'number', 'label' => 'Transport PP',              'group' => 'transport'],
        'transport_ke_bandara'       => ['type' => 'number', 'label' => 'Ke Bandara',                'group' => 'transport'],
        'transport_bandara_tujuan'   => ['type' => 'number', 'label' => 'Bandara → Tujuan',          'group' => 'transport'],
        'transport_tujuan_bandara'   => ['type' => 'number', 'label' => 'Tujuan → Bandara',          'group' => 'transport'],
        'transport_bandara_kedudukan' => ['type' => 'number', 'label' => 'Bandara → Kedudukan',      'group' => 'transport'],
        'transport_kedudukan_tujuan'  => ['type' => 'number', 'label' => 'Kedudukan → Tujuan',       'group' => 'transport'],
        
        // Covid
        'covid_berangkat'   => ['type' => 'number', 'label' => 'Covid Berangkat',    'group' => 'covid'],
        'covid_pulang'      => ['type' => 'number', 'label' => 'Covid Pulang',       'group' => 'covid'],
        'covid_tanpa_bukti' => ['type' => 'number', 'label' => 'Covid Tanpa Bukti',  'group' => 'covid'],
        
        // Biaya dengan Bukti
        'biaya_tol'          => ['type' => 'number', 'label' => 'Biaya Tol',          'group' => 'biaya_bukti'],
        'biaya_taksi'        => ['type' => 'number', 'label' => 'Biaya Taksi',        'group' => 'biaya_bukti'],
        'biaya_bensin'       => ['type' => 'number', 'label' => 'Biaya Bensin',       'group' => 'biaya_bukti'],
        'biaya_riil_lainnya' => ['type' => 'number', 'label' => 'Biaya Riil Lainnya', 'group' => 'biaya_bukti'],
        
        // Uang Representatif
        'uang_representatif'      => ['type' => 'number', 'label' => 'Uang Representatif',      'group' => 'representatif'],
        'uang_representatif_hari' => ['type' => 'number', 'label' => 'Representatif Hari',       'group' => 'representatif'],
        
        // Tingkat Perjadin
        'tingkat_perjadin' => ['type' => 'text', 'label' => 'Tingkat Perjadin', 'group' => 'administrasi'],
        
        // Rekening
        'no_rekening'    => ['type' => 'text',   'label' => 'No. Rekening',    'group' => 'rekening'],
        'bank'           => ['type' => 'text',   'label' => 'Bank',            'group' => 'rekening'],
        'nama_rekening'  => ['type' => 'text',   'label' => 'Nama Rekening',   'group' => 'rekening'],
        
        // Administrasi
        'tgl_dok_diterima'  => ['type' => 'date',   'label' => 'Tgl Dok Diterima', 'group' => 'administrasi'],
        'tgl_rincian_spd'   => ['type' => 'date',   'label' => 'Tgl Rincian SPD',  'group' => 'administrasi'],
        'pengajuan_up_ls'   => ['type' => 'text',   'label' => 'UP/LS',            'group' => 'administrasi'],
        'tgl_pengajuan'     => ['type' => 'date',   'label' => 'Tgl Pengajuan',    'group' => 'administrasi'],
        'tgl_pembayaran'    => ['type' => 'date',   'label' => 'Tgl Pembayaran',   'group' => 'administrasi'],
        'persekot'          => ['type' => 'number', 'label' => 'Persekot',          'group' => 'administrasi'],
        
        // Akuntansi
        'akun'        => ['type' => 'text', 'label' => 'Akun',       'group' => 'akuntansi'],
        'no_routing'  => ['type' => 'text', 'label' => 'No Routing', 'group' => 'akuntansi'],
        'pic'         => ['type' => 'text', 'label' => 'PIC',        'group' => 'akuntansi'],
        'bendahara'   => ['type' => 'text', 'label' => 'Bendahara',  'group' => 'akuntansi'],
        'ppk'         => ['type' => 'text', 'label' => 'PPK',        'group' => 'akuntansi'],
        'unit'        => ['type' => 'text', 'label' => 'Unit',       'group' => 'akuntansi'],
        
        // Metadata
        'catatan'     => ['type' => 'textarea', 'label' => 'Catatan', 'group' => 'catatan'],
    ];
}

/**
 * Validate that a field name is allowed for SPD inline editing.
 */
function is_valid_spd_field(string $field): bool {
    return array_key_exists($field, spd_field_definitions());
}

/**
 * Get the upload directory for a specific SPD.
 */
function spd_upload_dir(int $id_spd): string {
    $dir = __DIR__ . '/../uploads/spd_' . $id_spd;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}

/**
 * Allowed file MIME types for upload.
 */
function allowed_mime_types(): array {
    return [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];
}

/**
 * File category labels.
 */
function file_categories(): array {
    return [
        'tiket'       => 'Tiket Perjalanan',
        'hotel'       => 'Kuitansi Hotel',
        'transport'   => 'Bukti Transport',
        'covid'       => 'Test Covid',
        'bukti_lain'  => 'Bukti Lainnya',
    ];
}
