-- eSPD Database Schema (SQLite)
-- Designed for easy migration to MySQL

-- Tabel log audit aktivitas pengguna
CREATE TABLE IF NOT EXISTS audit_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    username TEXT,
    action TEXT,
    description TEXT,
    ip_address TEXT,
    created_at TEXT DEFAULT (datetime('now'))
);

-- Tabel biaya operasional lain-lain per kegiatan
CREATE TABLE IF NOT EXISTS kegiatan_biaya_lain (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    id_kegiatan INTEGER NOT NULL,
    nama_biaya TEXT NOT NULL,
    jumlah REAL DEFAULT 0,
    keterangan TEXT DEFAULT '',
    tanggal TEXT DEFAULT '',
    file_bukti TEXT DEFAULT '',
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);

-- Tabel users untuk autentikasi
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    display_name TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'user',
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);

-- Tabel kegiatan/pelatihan sebagai induk SPD
CREATE TABLE IF NOT EXISTS kegiatan (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nama_kegiatan TEXT NOT NULL,
    nomor_st TEXT DEFAULT '',
    tanggal_st TEXT DEFAULT '',
    perihal_st TEXT DEFAULT '',
    kota_tujuan TEXT DEFAULT '',
    persekot REAL DEFAULT 0,
    created_by TEXT DEFAULT NULL,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);

-- Tabel SPD utama (1 baris per orang per kegiatan)
CREATE TABLE IF NOT EXISTS spd (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    id_kegiatan INTEGER NOT NULL,
    id_pengajar INTEGER DEFAULT NULL,

    -- Identitas
    no_sppd TEXT DEFAULT '',
    tgl_sppd TEXT DEFAULT '',
    nama TEXT NOT NULL DEFAULT '',
    nip TEXT DEFAULT '',
    golongan TEXT DEFAULT '',
    pangkat TEXT DEFAULT '',
    jabatan TEXT DEFAULT '',
    instansi TEXT DEFAULT '',

    -- Perjalanan
    kota_asal TEXT DEFAULT '',
    kota_tujuan TEXT DEFAULT '',
    tiba_di TEXT DEFAULT '',
    tgl_mulai TEXT DEFAULT '',
    tgl_akhir TEXT DEFAULT '',
    alat_angkut TEXT DEFAULT '',

    -- Tiket
    tiket_pp TEXT DEFAULT '',
    tiket_berangkat REAL DEFAULT 0,
    tiket_pulang REAL DEFAULT 0,

    -- Uang Harian Kota 1
    uh_jml_hari INTEGER DEFAULT 0,
    uh_per_hari REAL DEFAULT 0,

    -- Uang Harian Kota 2
    uh2_jml_hari INTEGER DEFAULT 0,
    uh2_per_hari REAL DEFAULT 0,

    -- Uang Harian Kota 3
    uh3_jml_hari INTEGER DEFAULT 0,
    uh3_per_hari REAL DEFAULT 0,

    -- Uang Harian Fullboard
    uh_fullboard_per_hari REAL DEFAULT 0,
    uh_fullboard_jml_hari INTEGER DEFAULT 0,

    -- Hotel (max 6 hotel berbeda)
    tarif_maks_hotel REAL DEFAULT 0,
    hotel1_tarif REAL DEFAULT 0,
    hotel1_hari INTEGER DEFAULT 0,
    hotel2_tarif REAL DEFAULT 0,
    hotel2_hari INTEGER DEFAULT 0,
    hotel3_tarif REAL DEFAULT 0,
    hotel3_hari INTEGER DEFAULT 0,
    hotel4_tarif REAL DEFAULT 0,
    hotel4_hari INTEGER DEFAULT 0,
    hotel5_tarif REAL DEFAULT 0,
    hotel5_hari INTEGER DEFAULT 0,
    hotel6_tarif REAL DEFAULT 0,
    hotel6_hari INTEGER DEFAULT 0,

    -- Penginapan DPR
    dpr_tarif_hotel REAL DEFAULT 0,
    dpr_malam_hotel INTEGER DEFAULT 0,
    dpr_koefisien REAL DEFAULT 0.3,
    dpr_biaya_penginapan REAL DEFAULT 0,

    -- Transport DPR
    transport_pp REAL DEFAULT 0,
    transport_ke_bandara REAL DEFAULT 0,
    transport_bandara_tujuan REAL DEFAULT 0,
    transport_tujuan_bandara REAL DEFAULT 0,
    transport_bandara_kedudukan REAL DEFAULT 0,
    transport_kedudukan_tujuan REAL DEFAULT 0,

    -- Test Covid
    covid_berangkat REAL DEFAULT 0,
    covid_pulang REAL DEFAULT 0,
    covid_tanpa_bukti REAL DEFAULT 0,

    -- Komponen Transportasi dengan Bukti
    biaya_tol REAL DEFAULT 0,
    biaya_taksi REAL DEFAULT 0,
    biaya_bensin REAL DEFAULT 0,
    biaya_riil_lainnya REAL DEFAULT 0,

    -- Uang Representatif
    uang_representatif REAL DEFAULT 0,
    uang_representatif_hari INTEGER DEFAULT 0,

    -- Tingkat Perjalanan Dinas
    tingkat_perjadin TEXT DEFAULT '',

    -- Data Rekening
    no_rekening TEXT DEFAULT '',
    bank TEXT DEFAULT '',
    nama_rekening TEXT DEFAULT '',

    -- Administrasi
    tgl_dok_diterima TEXT DEFAULT '',
    tgl_rincian_spd TEXT DEFAULT '',
    pengajuan_up_ls TEXT DEFAULT '',
    tgl_pengajuan TEXT DEFAULT '',
    tgl_pembayaran TEXT DEFAULT '',
    persekot REAL DEFAULT 0,

    -- Akuntansi / Routing
    akun TEXT DEFAULT '',
    no_routing TEXT DEFAULT '',
    pic TEXT DEFAULT '',
    bendahara TEXT DEFAULT '',
    ppk TEXT DEFAULT '',
    unit TEXT DEFAULT '',

    -- Metadata
    status TEXT DEFAULT 'draft',
    catatan TEXT DEFAULT '',
    
    created_by TEXT DEFAULT NULL,

    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now')),

    FOREIGN KEY (id_kegiatan) REFERENCES kegiatan(id)
);

-- File bukti perjalanan
CREATE TABLE IF NOT EXISTS spd_files (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    id_spd INTEGER NOT NULL,
    kategori TEXT NOT NULL DEFAULT 'bukti_lain',
    nama_file TEXT NOT NULL,
    nama_asli TEXT NOT NULL,
    ukuran INTEGER DEFAULT 0,
    mime_type TEXT DEFAULT '',
    created_at TEXT DEFAULT (datetime('now')),
    FOREIGN KEY (id_spd) REFERENCES spd(id) ON DELETE CASCADE
);

-- Index untuk performa
CREATE INDEX IF NOT EXISTS idx_spd_kegiatan ON spd(id_kegiatan);
CREATE INDEX IF NOT EXISTS idx_spd_pengajar ON spd(id_pengajar);
CREATE INDEX IF NOT EXISTS idx_spd_status ON spd(status);
CREATE INDEX IF NOT EXISTS idx_spd_files_spd ON spd_files(id_spd);

-- Default admin user (password: admin123)
INSERT OR IGNORE INTO users (username, password, display_name, role)
VALUES ('admin', 'admin123', 'Administrator', 'admin');
