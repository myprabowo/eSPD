-- eSPD Database Schema (MySQL)

CREATE TABLE IF NOT EXISTS audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    username VARCHAR(100),
    action VARCHAR(50),
    description TEXT,
    ip_address VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS kegiatan_biaya_lain (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_kegiatan INT NOT NULL,
    nama_biaya VARCHAR(255) NOT NULL,
    jumlah DOUBLE DEFAULT 0,
    keterangan TEXT,
    tanggal VARCHAR(50) DEFAULT '',
    file_bukti VARCHAR(255) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS kegiatan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kegiatan VARCHAR(255) NOT NULL,
    nomor_st VARCHAR(100) DEFAULT '',
    tanggal_st VARCHAR(50) DEFAULT '',
    perihal_st TEXT,
    kota_tujuan VARCHAR(100) DEFAULT '',
    persekot DOUBLE DEFAULT 0,
    created_by VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS spd (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_kegiatan INT NOT NULL,
    id_pengajar INT DEFAULT NULL,

    no_sppd VARCHAR(100) DEFAULT '',
    tgl_sppd VARCHAR(50) DEFAULT '',
    nama VARCHAR(100) NOT NULL DEFAULT '',
    nip VARCHAR(50) DEFAULT '',
    golongan VARCHAR(20) DEFAULT '',
    pangkat VARCHAR(50) DEFAULT '',
    jabatan VARCHAR(100) DEFAULT '',
    instansi VARCHAR(255) DEFAULT '',

    kota_asal VARCHAR(100) DEFAULT '',
    kota_tujuan VARCHAR(100) DEFAULT '',
    tiba_di VARCHAR(100) DEFAULT '',
    tgl_mulai VARCHAR(50) DEFAULT '',
    tgl_akhir VARCHAR(50) DEFAULT '',
    alat_angkut VARCHAR(100) DEFAULT '',

    tiket_pp VARCHAR(255) DEFAULT '',
    tiket_berangkat DOUBLE DEFAULT 0,
    tiket_pulang DOUBLE DEFAULT 0,

    uh_jml_hari INT DEFAULT 0,
    uh_per_hari DOUBLE DEFAULT 0,
    uh2_jml_hari INT DEFAULT 0,
    uh2_per_hari DOUBLE DEFAULT 0,
    uh3_jml_hari INT DEFAULT 0,
    uh3_per_hari DOUBLE DEFAULT 0,

    uh_fullboard_per_hari DOUBLE DEFAULT 0,
    uh_fullboard_jml_hari INT DEFAULT 0,

    tarif_maks_hotel DOUBLE DEFAULT 0,
    hotel1_tarif DOUBLE DEFAULT 0,
    hotel1_hari INT DEFAULT 0,
    hotel2_tarif DOUBLE DEFAULT 0,
    hotel2_hari INT DEFAULT 0,
    hotel3_tarif DOUBLE DEFAULT 0,
    hotel3_hari INT DEFAULT 0,
    hotel4_tarif DOUBLE DEFAULT 0,
    hotel4_hari INT DEFAULT 0,
    hotel5_tarif DOUBLE DEFAULT 0,
    hotel5_hari INT DEFAULT 0,
    hotel6_tarif DOUBLE DEFAULT 0,
    hotel6_hari INT DEFAULT 0,

    dpr_tarif_hotel DOUBLE DEFAULT 0,
    dpr_malam_hotel INT DEFAULT 0,
    dpr_koefisien DOUBLE DEFAULT 0.3,
    dpr_biaya_penginapan DOUBLE DEFAULT 0,

    transport_pp DOUBLE DEFAULT 0,
    transport_ke_bandara DOUBLE DEFAULT 0,
    transport_bandara_tujuan DOUBLE DEFAULT 0,
    transport_tujuan_bandara DOUBLE DEFAULT 0,
    transport_bandara_kedudukan DOUBLE DEFAULT 0,
    transport_kedudukan_tujuan DOUBLE DEFAULT 0,

    covid_berangkat DOUBLE DEFAULT 0,
    covid_pulang DOUBLE DEFAULT 0,
    covid_tanpa_bukti DOUBLE DEFAULT 0,

    biaya_tol DOUBLE DEFAULT 0,
    biaya_taksi DOUBLE DEFAULT 0,
    biaya_bensin DOUBLE DEFAULT 0,
    biaya_riil_lainnya DOUBLE DEFAULT 0,

    uang_representatif DOUBLE DEFAULT 0,
    uang_representatif_hari INT DEFAULT 0,

    tingkat_perjadin VARCHAR(50) DEFAULT '',

    no_rekening VARCHAR(50) DEFAULT '',
    bank VARCHAR(50) DEFAULT '',
    nama_rekening VARCHAR(100) DEFAULT '',

    tgl_dok_diterima VARCHAR(50) DEFAULT '',
    tgl_rincian_spd VARCHAR(50) DEFAULT '',
    pengajuan_up_ls VARCHAR(50) DEFAULT '',
    tgl_pengajuan VARCHAR(50) DEFAULT '',
    tgl_pembayaran VARCHAR(50) DEFAULT '',
    persekot DOUBLE DEFAULT 0,

    akun VARCHAR(50) DEFAULT '',
    no_routing VARCHAR(50) DEFAULT '',
    pic VARCHAR(100) DEFAULT '',
    bendahara VARCHAR(100) DEFAULT '',
    ppk VARCHAR(100) DEFAULT '',
    unit VARCHAR(100) DEFAULT '',

    status VARCHAR(20) DEFAULT 'draft',
    catatan TEXT,
    
    created_by VARCHAR(50) DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (id_kegiatan) REFERENCES kegiatan(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS spd_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_spd INT NOT NULL,
    kategori VARCHAR(50) NOT NULL DEFAULT 'bukti_lain',
    nama_file VARCHAR(255) NOT NULL,
    nama_asli VARCHAR(255) NOT NULL,
    ukuran INT DEFAULT 0,
    mime_type VARCHAR(100) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_spd) REFERENCES spd(id) ON DELETE CASCADE
);

-- Index untuk performa
CREATE INDEX idx_spd_kegiatan ON spd(id_kegiatan);
CREATE INDEX idx_spd_pengajar ON spd(id_pengajar);
CREATE INDEX idx_spd_status ON spd(status);
CREATE INDEX idx_spd_files_spd ON spd_files(id_spd);

-- Default admin user (password: admin123)
INSERT IGNORE INTO users (username, password, display_name, role)
VALUES ('admin', 'admin123', 'Administrator', 'admin');
