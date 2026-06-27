<?php
// SPD Detail — Full form for one SPD
$spd_id = (int)($_GET['id'] ?? 0);
if (!$spd_id) {
    echo '<div class="container"><p>ID SPD tidak valid. <a href="?page=kegiatan">Kembali</a></p></div>';
    return;
}
$spd = db_query("SELECT * FROM spd WHERE id = ?", [$spd_id]);
if (empty($spd)) {
    echo '<div class="container"><p>SPD tidak ditemukan. <a href="?page=kegiatan">Kembali</a></p></div>';
    return;
}
$spd = compute_spd_totals($spd[0]);
$kegiatan = db_query("SELECT * FROM kegiatan WHERE id = ?", [$spd['id_kegiatan']]);
$kegiatan = $kegiatan[0] ?? [];
$files = db_query("SELECT * FROM spd_files WHERE id_spd = ? ORDER BY kategori, created_at", [$spd_id]);
$categories = file_categories();
?>

<div class="breadcrumb">
    <a href="?page=dashboard">Dashboard</a>
    <span class="sep">›</span>
    <a href="?page=kegiatan">Kegiatan</a>
    <span class="sep">›</span>
    <a href="?page=spd_list&id_kegiatan=<?= $spd['id_kegiatan'] ?>"><?= h($kegiatan['nama_kegiatan'] ?? 'SPD') ?></a>
    <span class="sep">›</span>
    <span><?= h($spd['nama']) ?></span>
</div>

<div class="page-header">
    <div>
        <h1><?= h($spd['nama']) ?></h1>
        <p class="subtitle">
            <span class="badge badge-<?= h($spd['status']) ?>"><?= h(ucfirst($spd['status'])) ?></span>
            &nbsp;<?= h($spd['instansi'] ?: '') ?>
            <?= $spd['golongan'] ? ' · Gol. ' . h($spd['golongan']) : '' ?>
        </p>
    </div>
    <div class="header-actions">
        <a href="?page=spd_list&id_kegiatan=<?= $spd['id_kegiatan'] ?>" class="btn btn-secondary">← Kembali</a>
    </div>
</div>

<div style="display:grid; grid-template-columns: 1fr 340px; gap:1rem; align-items:start;">

<!-- Left: Form Sections -->
<div>
    <!-- Section: Identitas -->
    <div class="section-group">
        <button class="section-toggle open" type="button">
            <span>👤 Identitas & Perjalanan</span>
            <span class="arrow">▼</span>
        </button>
        <div class="section-content open">
            <div class="section-content-inner">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">No. SPPD</label>
                        <input class="form-input spd-field" data-field="no_sppd" value="<?= h($spd['no_sppd']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tgl. SPPD</label>
                        <input class="form-input spd-field" type="date" data-field="tgl_sppd" value="<?= h($spd['tgl_sppd']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nama</label>
                        <input class="form-input spd-field" data-field="nama" value="<?= h($spd['nama']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">NIP</label>
                        <input class="form-input spd-field" data-field="nip" value="<?= h($spd['nip']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Golongan</label>
                        <input class="form-input spd-field" data-field="golongan" value="<?= h($spd['golongan']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Pangkat</label>
                        <input class="form-input spd-field" data-field="pangkat" value="<?= h($spd['pangkat']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jabatan</label>
                        <input class="form-input spd-field" data-field="jabatan" value="<?= h($spd['jabatan']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Instansi</label>
                        <input class="form-input spd-field" data-field="instansi" value="<?= h($spd['instansi']) ?>">
                    </div>
                </div>
                <hr style="border-color:var(--border);margin:1rem 0;">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Kota Asal</label>
                        <input class="form-input spd-field" data-field="kota_asal" value="<?= h($spd['kota_asal']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kota Tujuan</label>
                        <input class="form-input spd-field" data-field="kota_tujuan" value="<?= h($spd['kota_tujuan']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tiba Di</label>
                        <input class="form-input spd-field" data-field="tiba_di" value="<?= h($spd['tiba_di']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tgl. Mulai</label>
                        <input class="form-input spd-field" type="date" data-field="tgl_mulai" value="<?= h($spd['tgl_mulai']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tgl. Akhir</label>
                        <input class="form-input spd-field" type="date" data-field="tgl_akhir" value="<?= h($spd['tgl_akhir']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Alat Angkut</label>
                        <input class="form-input spd-field" data-field="alat_angkut" value="<?= h($spd['alat_angkut']) ?>" placeholder="Pesawat / Darat">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section: Tiket -->
    <div class="section-group">
        <button class="section-toggle" type="button">
            <span>✈️ Tiket</span>
            <span class="arrow">▼</span>
        </button>
        <div class="section-content">
            <div class="section-content-inner">
                <div class="form-grid-3">
                    <div class="form-group">
                        <label class="form-label">Tiket PP (Rp)</label>
                        <input class="form-input spd-field" type="number" step="any" data-field="tiket_pp" value="<?= $spd['tiket_pp'] ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tiket Berangkat (Rp)</label>
                        <input class="form-input spd-field" type="number" step="any" data-field="tiket_berangkat" value="<?= $spd['tiket_berangkat'] ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tiket Pulang (Rp)</label>
                        <input class="form-input spd-field" type="number" step="any" data-field="tiket_pulang" value="<?= $spd['tiket_pulang'] ?>">
                    </div>
                </div>
                <div class="card" style="margin-top:0.5rem;padding:0.75rem;background:var(--bg-elevated);">
                    <strong>Total Tiket:</strong> <span id="computed-total_tiket" class="total-row"><?= format_rupiah($spd['total_tiket']) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Section: Uang Harian -->
    <div class="section-group">
        <button class="section-toggle" type="button">
            <span>💰 Uang Harian</span>
            <span class="arrow">▼</span>
        </button>
        <div class="section-content">
            <div class="section-content-inner">
                <p style="font-size:0.8rem;color:var(--text-muted);margin-bottom:0.75rem;">Kota 1</p>
                <div class="form-grid-3">
                    <div class="form-group">
                        <label class="form-label">Jumlah Hari</label>
                        <input class="form-input spd-field" type="number" data-field="uh_jml_hari" value="<?= $spd['uh_jml_hari'] ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">UH Per Hari (Rp)</label>
                        <input class="form-input spd-field" type="number" step="any" data-field="uh_per_hari" value="<?= $spd['uh_per_hari'] ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Total</label>
                        <div class="form-input" style="background:var(--bg-elevated);cursor:default;" id="computed-uh_total"><?= format_rupiah($spd['uh_total']) ?></div>
                    </div>
                </div>
                <p style="font-size:0.8rem;color:var(--text-muted);margin-bottom:0.75rem;">Kota 2</p>
                <div class="form-grid-3">
                    <div class="form-group">
                        <label class="form-label">Jumlah Hari</label>
                        <input class="form-input spd-field" type="number" data-field="uh2_jml_hari" value="<?= $spd['uh2_jml_hari'] ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">UH Per Hari (Rp)</label>
                        <input class="form-input spd-field" type="number" step="any" data-field="uh2_per_hari" value="<?= $spd['uh2_per_hari'] ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Total</label>
                        <div class="form-input" style="background:var(--bg-elevated);cursor:default;" id="computed-uh2_total"><?= format_rupiah($spd['uh2_total']) ?></div>
                    </div>
                </div>
                <p style="font-size:0.8rem;color:var(--text-muted);margin-bottom:0.75rem;">Kota 3</p>
                <div class="form-grid-3">
                    <div class="form-group">
                        <label class="form-label">Jumlah Hari</label>
                        <input class="form-input spd-field" type="number" data-field="uh3_jml_hari" value="<?= $spd['uh3_jml_hari'] ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">UH Per Hari (Rp)</label>
                        <input class="form-input spd-field" type="number" step="any" data-field="uh3_per_hari" value="<?= $spd['uh3_per_hari'] ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Total</label>
                        <div class="form-input" style="background:var(--bg-elevated);cursor:default;" id="computed-uh3_total"><?= format_rupiah($spd['uh3_total']) ?></div>
                    </div>
                </div>
                <p style="font-size:0.8rem;color:var(--text-muted);margin-bottom:0.75rem;">Fullboard</p>
                <div class="form-grid-3">
                    <div class="form-group">
                        <label class="form-label">Jumlah Hari</label>
                        <input class="form-input spd-field" type="number" data-field="uh_fullboard_jml_hari" value="<?= $spd['uh_fullboard_jml_hari'] ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fullboard Per Hari (Rp)</label>
                        <input class="form-input spd-field" type="number" step="any" data-field="uh_fullboard_per_hari" value="<?= $spd['uh_fullboard_per_hari'] ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Total</label>
                        <div class="form-input" style="background:var(--bg-elevated);cursor:default;" id="computed-uh_fullboard_total"><?= format_rupiah($spd['uh_fullboard_total']) ?></div>
                    </div>
                </div>
                <div class="card" style="margin-top:0.5rem;padding:0.75rem;background:var(--bg-elevated);">
                    <strong>Total Uang Harian:</strong> <span id="computed-total_uang_harian" class="total-row"><?= format_rupiah($spd['total_uang_harian']) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Section: Hotel -->
    <div class="section-group">
        <button class="section-toggle" type="button">
            <span>🏨 Hotel</span>
            <span class="arrow">▼</span>
        </button>
        <div class="section-content">
            <div class="section-content-inner">
                <div class="form-group">
                    <label class="form-label">Tarif Maksimal Hotel SBU (Rp)</label>
                    <input class="form-input spd-field" type="number" step="any" data-field="tarif_maks_hotel" value="<?= $spd['tarif_maks_hotel'] ?>">
                </div>
                <?php for ($h = 1; $h <= 6; $h++): ?>
                <p style="font-size:0.8rem;color:var(--text-muted);margin:0.5rem 0 0.5rem;">Hotel <?= $h ?></p>
                <div class="form-grid-3">
                    <div class="form-group">
                        <label class="form-label">Tarif/Malam (Rp)</label>
                        <input class="form-input spd-field" type="number" step="any" data-field="hotel<?= $h ?>_tarif" value="<?= $spd["hotel{$h}_tarif"] ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jumlah Malam</label>
                        <input class="form-input spd-field" type="number" data-field="hotel<?= $h ?>_hari" value="<?= $spd["hotel{$h}_hari"] ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Total</label>
                        <div class="form-input" style="background:var(--bg-elevated);cursor:default;" id="computed-hotel<?= $h ?>_total"><?= format_rupiah($spd["hotel{$h}_total"]) ?></div>
                    </div>
                </div>
                <?php endfor; ?>
                <div class="card" style="margin-top:0.5rem;padding:0.75rem;background:var(--bg-elevated);">
                    <strong>Total Hotel:</strong> <span id="computed-total_hotel" class="total-row"><?= format_rupiah($spd['total_hotel']) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Section: DPR & Transport -->
    <div class="section-group">
        <button class="section-toggle" type="button">
            <span>🚗 DPR & Transport</span>
            <span class="arrow">▼</span>
        </button>
        <div class="section-content">
            <div class="section-content-inner">
                <p style="font-size:0.8rem;color:var(--text-muted);margin-bottom:0.75rem;">Penginapan DPR</p>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Tarif Hotel</label>
                        <input class="form-input spd-field" type="number" step="any" data-field="dpr_tarif_hotel" value="<?= $spd['dpr_tarif_hotel'] ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Malam</label>
                        <input class="form-input spd-field" type="number" data-field="dpr_malam_hotel" value="<?= $spd['dpr_malam_hotel'] ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Koefisien</label>
                        <input class="form-input spd-field" type="number" step="0.01" data-field="dpr_koefisien" value="<?= $spd['dpr_koefisien'] ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Biaya Penginapan</label>
                        <div class="form-input" style="background:var(--bg-elevated);cursor:default;" id="computed-dpr_biaya_penginapan"><?= format_rupiah($spd['dpr_biaya_penginapan']) ?></div>
                    </div>
                </div>
                <p style="font-size:0.8rem;color:var(--text-muted);margin:1rem 0 0.75rem;">Transport</p>
                <div class="form-grid-3">
                    <div class="form-group">
                        <label class="form-label">Transport PP</label>
                        <input class="form-input spd-field" type="number" step="any" data-field="transport_pp" value="<?= $spd['transport_pp'] ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ke Bandara</label>
                        <input class="form-input spd-field" type="number" step="any" data-field="transport_ke_bandara" value="<?= $spd['transport_ke_bandara'] ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Bandara → Tujuan</label>
                        <input class="form-input spd-field" type="number" step="any" data-field="transport_bandara_tujuan" value="<?= $spd['transport_bandara_tujuan'] ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tujuan → Bandara</label>
                        <input class="form-input spd-field" type="number" step="any" data-field="transport_tujuan_bandara" value="<?= $spd['transport_tujuan_bandara'] ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Bandara → Kedudukan</label>
                        <input class="form-input spd-field" type="number" step="any" data-field="transport_bandara_kedudukan" value="<?= $spd['transport_bandara_kedudukan'] ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kedudukan → Tujuan</label>
                        <input class="form-input spd-field" type="number" step="any" data-field="transport_kedudukan_tujuan" value="<?= $spd['transport_kedudukan_tujuan'] ?>">
                    </div>
                </div>
                <div class="card" style="margin-top:0.5rem;padding:0.75rem;background:var(--bg-elevated);">
                    <strong>Total Transport:</strong> <span id="computed-total_transport" class="total-row"><?= format_rupiah($spd['total_transport']) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Section: Biaya Lain -->
    <div class="section-group">
        <button class="section-toggle" type="button">
            <span>📝 Biaya Lain & Representatif</span>
            <span class="arrow">▼</span>
        </button>
        <div class="section-content">
            <div class="section-content-inner">
                <p style="font-size:0.8rem;color:var(--text-muted);margin-bottom:0.75rem;">Test Covid</p>
                <div class="form-grid-3">
                    <div class="form-group">
                        <label class="form-label">Berangkat (Bukti)</label>
                        <input class="form-input spd-field" type="number" step="any" data-field="covid_berangkat" value="<?= $spd['covid_berangkat'] ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Pulang (Bukti)</label>
                        <input class="form-input spd-field" type="number" step="any" data-field="covid_pulang" value="<?= $spd['covid_pulang'] ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanpa Bukti</label>
                        <input class="form-input spd-field" type="number" step="any" data-field="covid_tanpa_bukti" value="<?= $spd['covid_tanpa_bukti'] ?>">
                    </div>
                </div>
                <p style="font-size:0.8rem;color:var(--text-muted);margin:1rem 0 0.75rem;">Komponen Transportasi dengan Bukti</p>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Biaya Tol</label>
                        <input class="form-input spd-field" type="number" step="any" data-field="biaya_tol" value="<?= $spd['biaya_tol'] ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Biaya Taksi</label>
                        <input class="form-input spd-field" type="number" step="any" data-field="biaya_taksi" value="<?= $spd['biaya_taksi'] ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Biaya Bensin</label>
                        <input class="form-input spd-field" type="number" step="any" data-field="biaya_bensin" value="<?= $spd['biaya_bensin'] ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Biaya Riil Lainnya</label>
                        <input class="form-input spd-field" type="number" step="any" data-field="biaya_riil_lainnya" value="<?= $spd['biaya_riil_lainnya'] ?>">
                    </div>
                </div>
                <p style="font-size:0.8rem;color:var(--text-muted);margin:1rem 0 0.75rem;">Uang Representatif</p>
                <div class="form-grid-3">
                    <div class="form-group">
                        <label class="form-label">Per Hari (Rp)</label>
                        <input class="form-input spd-field" type="number" step="any" data-field="uang_representatif" value="<?= $spd['uang_representatif'] ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jumlah Hari</label>
                        <input class="form-input spd-field" type="number" data-field="uang_representatif_hari" value="<?= $spd['uang_representatif_hari'] ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Total</label>
                        <div class="form-input" style="background:var(--bg-elevated);cursor:default;" id="computed-uang_representatif_total"><?= format_rupiah($spd['uang_representatif_total']) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section: Rekening & Administrasi -->
    <div class="section-group">
        <button class="section-toggle" type="button">
            <span>🏦 Rekening</span>
            <span class="arrow">▼</span>
        </button>
        <div class="section-content">
            <div class="section-content-inner">
                <div class="form-grid-3">
                    <div class="form-group">
                        <label class="form-label">No. Rekening</label>
                        <input class="form-input spd-field" data-field="no_rekening" value="<?= h($spd['no_rekening']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Bank</label>
                        <input class="form-input spd-field" data-field="bank" value="<?= h($spd['bank']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nama Rekening</label>
                        <input class="form-input spd-field" data-field="nama_rekening" value="<?= h($spd['nama_rekening']) ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Right Sidebar: Summary + Files -->
<div>
    <!-- Grand Total Card -->
    <div class="card" style="margin-bottom:1rem;background:linear-gradient(135deg, rgba(99,102,241,0.15), rgba(139,92,246,0.1));">
        <h3 style="font-size:0.85rem;color:var(--text-secondary);margin-bottom:0.75rem;">Ringkasan Biaya</h3>
        <div style="display:flex;flex-direction:column;gap:0.4rem;font-size:0.85rem;">
            <div style="display:flex;justify-content:space-between;"><span>Total Tiket</span><span id="sidebar-total_tiket"><?= format_rupiah($spd['total_tiket']) ?></span></div>
            <div style="display:flex;justify-content:space-between;"><span>Total UH</span><span id="sidebar-total_uang_harian"><?= format_rupiah($spd['total_uang_harian']) ?></span></div>
            <div style="display:flex;justify-content:space-between;"><span>Total Hotel</span><span id="sidebar-total_hotel"><?= format_rupiah($spd['total_hotel']) ?></span></div>
            <div style="display:flex;justify-content:space-between;"><span>DPR Penginapan</span><span id="sidebar-dpr_biaya_penginapan"><?= format_rupiah($spd['dpr_biaya_penginapan']) ?></span></div>
            <div style="display:flex;justify-content:space-between;"><span>Total Transport</span><span id="sidebar-total_transport"><?= format_rupiah($spd['total_transport']) ?></span></div>
            <div style="display:flex;justify-content:space-between;"><span>Total Covid</span><span id="sidebar-total_covid"><?= format_rupiah($spd['total_covid']) ?></span></div>
            <div style="display:flex;justify-content:space-between;"><span>Biaya Bukti</span><span id="sidebar-total_biaya_bukti"><?= format_rupiah($spd['total_biaya_bukti']) ?></span></div>
            <div style="display:flex;justify-content:space-between;"><span>Representatif</span><span id="sidebar-uang_representatif_total"><?= format_rupiah($spd['uang_representatif_total']) ?></span></div>
            <hr style="border-color:var(--border);">
            <div style="display:flex;justify-content:space-between;font-weight:800;font-size:1rem;">
                <span>Grand Total</span>
                <span class="total-row" id="sidebar-grand_total"><?= format_rupiah($spd['grand_total']) ?></span>
            </div>
        </div>
    </div>

    <!-- File Upload -->
    <div class="card">
        <div class="card-header">
            <h3>📎 File Bukti</h3>
        </div>

        <!-- Upload Zone -->
        <div>
            <div class="form-group">
                <label class="form-label" for="file-kategori">Kategori</label>
                <select class="form-select" id="file-kategori">
                    <?php foreach ($categories as $key => $label): ?>
                    <option value="<?= $key ?>"><?= h($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="upload-zone" id="upload-zone">
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                <p>Klik atau seret file ke sini</p>
                <p class="upload-hint">PDF, JPG, PNG, Excel, Word (max 20MB)</p>
                <input type="file" style="display:none;" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.xls,.xlsx,.doc,.docx" multiple>
            </div>
        </div>

        <!-- File List -->
        <div class="file-list" id="file-list">
            <?php if (empty($files)): ?>
                <div class="empty-state" style="padding:1rem;"><p style="font-size:0.8rem;">Belum ada file</p></div>
            <?php else: ?>
                <?php foreach ($files as $f): ?>
                <div class="file-item" id="file-<?= $f['id'] ?>">
                    <span class="file-icon">📄</span>
                    <a href="api/file_api.php?action=download&id=<?= $f['id'] ?>" class="file-name" target="_blank" title="<?= h($f['nama_asli']) ?>">
                        <?= h($f['nama_asli']) ?>
                    </a>
                    <span class="file-size"><?= formatFileSize($f['ukuran']) ?></span>
                    <button class="file-delete" onclick="deleteFileItem(<?= $f['id'] ?>)" title="Hapus">✕</button>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

</div>

<?php
function formatFileSize(int $bytes): string {
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' KB';
    return round($bytes / (1024 * 1024), 1) . ' MB';
}
?>

<script>
const SPD_ID = <?= $spd_id ?>;
let saveTimer = null;

document.addEventListener('DOMContentLoaded', () => {
    // Auto-save on field change
    document.querySelectorAll('.spd-field').forEach(input => {
        input.addEventListener('change', () => saveField(input));
        // Debounce for text inputs
        if (input.type === 'text' || input.tagName === 'TEXTAREA') {
            input.addEventListener('input', () => {
                clearTimeout(saveTimer);
                saveTimer = setTimeout(() => saveField(input), 800);
            });
        }
    });
    
    // Init upload zone
    initUploadZone(document.getElementById('upload-zone'), {
        id_spd: SPD_ID,
        get kategori() { return document.getElementById('file-kategori').value; },
        onSuccess: (file) => addFileToList(file),
    });
});

async function saveField(input) {
    const field = input.dataset.field;
    let value = input.value;
    
    const result = await postAPI('api/spd_api.php', {
        action: 'inline_update',
        id: SPD_ID,
        field: field,
        value: value,
    });
    
    if (result && result.success) {
        input.style.borderColor = 'var(--success-500)';
        setTimeout(() => input.style.borderColor = '', 600);
        
        // Update computed fields
        if (result.row) {
            updateComputedFields(result.row);
        }
    } else {
        input.style.borderColor = 'var(--danger-500)';
        toast(result?.message || 'Gagal menyimpan.', 'error');
    }
}

function updateComputedFields(row) {
    const fields = [
        'total_tiket', 'uh_total', 'uh2_total', 'uh3_total', 'uh_fullboard_total',
        'total_uang_harian', 'hotel1_total', 'hotel2_total', 'hotel3_total',
        'hotel4_total', 'hotel5_total', 'hotel6_total', 'total_hotel',
        'dpr_biaya_penginapan', 'total_transport', 'total_covid', 'total_biaya_bukti',
        'uang_representatif_total', 'grand_total'
    ];
    
    fields.forEach(f => {
        // Update in-form computed displays
        const el = document.getElementById('computed-' + f);
        if (el) el.textContent = formatRupiah(row[f]);
        
        // Update sidebar
        const sideEl = document.getElementById('sidebar-' + f);
        if (sideEl) sideEl.textContent = formatRupiah(row[f]);
    });
    
    // Auto-update pangkat visually if backend changed it
    if (row.pangkat !== undefined) {
        const pangkatInput = document.querySelector('input[data-field="pangkat"]');
        if (pangkatInput && pangkatInput.value !== row.pangkat) {
            pangkatInput.value = row.pangkat;
            pangkatInput.style.borderColor = 'var(--success-500)';
            setTimeout(() => pangkatInput.style.borderColor = '', 600);
        }
    }
}

function addFileToList(file) {
    const container = document.getElementById('file-list');
    // Remove empty state
    const emptyState = container.querySelector('.empty-state');
    if (emptyState) emptyState.remove();
    
    const div = document.createElement('div');
    div.className = 'file-item';
    div.id = 'file-' + file.id;
    div.innerHTML = `
        <span class="file-icon">📄</span>
        <a href="api/file_api.php?action=download&id=${file.id}" class="file-name" target="_blank" title="${escapeHtml(file.nama_asli)}">
            ${escapeHtml(file.nama_asli)}
        </a>
        <span class="file-size">${formatFileSize(file.ukuran)}</span>
        <button class="file-delete" onclick="deleteFileItem(${file.id})" title="Hapus">✕</button>
    `;
    container.appendChild(div);
}

async function deleteFileItem(fileId) {
    await deleteFile(fileId, () => {
        const el = document.getElementById('file-' + fileId);
        if (el) el.remove();
    });
}
</script>
