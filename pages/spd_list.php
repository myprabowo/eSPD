<?php
// SPD List — Table of all SPDs per kegiatan, with inline editing
$id_kegiatan = (int)($_GET['id_kegiatan'] ?? 0);
if (!$id_kegiatan) {
    echo '<div class="container"><p>ID Kegiatan tidak valid. <a href="?page=kegiatan">Kembali</a></p></div>';
    return;
}
$kegiatan = db_query("SELECT * FROM kegiatan WHERE id = ?", [$id_kegiatan]);
if (empty($kegiatan)) {
    echo '<div class="container"><p>Kegiatan tidak ditemukan. <a href="?page=kegiatan">Kembali</a></p></div>';
    return;
}
$kegiatan = $kegiatan[0];
?>

<div class="breadcrumb">
    <a href="?page=dashboard">Dashboard</a>
    <span class="sep">›</span>
    <a href="?page=kegiatan">Kegiatan</a>
    <span class="sep">›</span>
    <span><?= h($kegiatan['nama_kegiatan']) ?></span>
</div>

<div class="page-header">
    <div>
        <h1><?= h($kegiatan['nama_kegiatan']) ?></h1>
        <p class="subtitle">
            <?= h($kegiatan['nomor_st'] ?: '') ?>
            <?= $kegiatan['kota_tujuan'] ? ' · ' . h($kegiatan['kota_tujuan']) : '' ?>
        </p>
    </div>
    <div class="header-actions">
        <button class="btn btn-primary" onclick="openModal('modal-add-pengajar')">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
            Tambah dari DB Pengajar
        </button>
        <button class="btn btn-primary" onclick="openModal('modal-add-spd')">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Tambah SPD Manual
        </button>
        <button class="btn btn-primary" onclick="kirimKegiatan()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            Posting
        </button>
        <a href="api/export_api.php?action=export_excel&id_kegiatan=<?= $id_kegiatan ?>" class="btn btn-success">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Excel
        </a>
        <a href="api/export_api.php?action=export_zip&id_kegiatan=<?= $id_kegiatan ?>" class="btn btn-success">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            ZIP
        </a>
    </div>
</div>

<div class="card" style="padding:0.5rem;">
    <div class="table-wrapper">
        <table id="spd-table">
            <thead>
                <tr>
                    <th style="width:35px">#</th>
                    <th>Nama / NIP</th>
                    <th>Golongan</th>
                    <th>Kota Asal</th>
                    <th>Tanggal</th>
                    <th>UH/Hari</th>
                    <th>UH Hari</th>
                    <th class="currency">Total UH</th>
                    <th>Hotel/Mlm</th>
                    <th>Hotel Mlm</th>
                    <th class="currency">Total Hotel</th>
                    <th class="currency">Total Tiket</th>
                    <th class="currency">Grand Total</th>
                    <th>Status</th>
                    <th style="width:100px; text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody id="spd-tbody">
                <tr><td colspan="17"><div class="empty-state"><p>Memuat data...</p></div></td></tr>
            </tbody>
        </table>
    </div>
</div>

<div class="card" style="margin-top:1rem; padding:1.5rem; background:var(--bg-card); border-radius:12px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
        <h3 style="margin:0; color:var(--text-primary); font-size:1.1rem;">Biaya Lain-Lain</h3>
        <button class="btn btn-sm btn-primary" onclick="openAddBiayaLain()">+ Tambah</button>
    </div>
    <div class="table-wrapper">
        <table id="biayalain-table">
            <thead>
                <tr>
                    <th style="width:35px">#</th>
                    <th>Tanggal</th>
                    <th>Nama Biaya</th>
                    <th>Keterangan</th>
                    <th class="currency">Jumlah (Rp)</th>
                    <th style="text-align:center;">Bukti</th>
                    <th style="width:100px; text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody id="biayalain-tbody">
                <tr><td colspan="7"><div class="empty-state"><p>Memuat data...</p></div></td></tr>
            </tbody>
        </table>
    </div>
</div>

<div class="card" style="margin-top:1rem; padding:1.5rem; background:linear-gradient(135deg, rgba(99,102,241,0.05), rgba(139,92,246,0.05)); border-radius:12px;">
    <h3 style="margin-top:0; margin-bottom:1rem; color:var(--text-primary); font-size:1.1rem;">Ringkasan Biaya Kegiatan</h3>
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap:1rem; margin-bottom: 1rem;">
        <div><span style="color:var(--text-muted);font-size:0.85rem;">Total Tiket</span><div id="keg-total-tiket" style="font-weight:600;font-size:1.1rem;">Rp 0</div></div>
        <div><span style="color:var(--text-muted);font-size:0.85rem;">Total Uang Harian</span><div id="keg-total-uh" style="font-weight:600;font-size:1.1rem;">Rp 0</div></div>
        <div><span style="color:var(--text-muted);font-size:0.85rem;">Total Hotel</span><div id="keg-total-hotel" style="font-weight:600;font-size:1.1rem;">Rp 0</div></div>
        <div><span style="color:var(--text-muted);font-size:0.85rem;">Total Transport</span><div id="keg-total-transport" style="font-weight:600;font-size:1.1rem;">Rp 0</div></div>
        <div><span style="color:var(--text-muted);font-size:0.85rem;">Total Representatif</span><div id="keg-total-rep" style="font-weight:600;font-size:1.1rem;">Rp 0</div></div>
        <div><span style="color:var(--text-muted);font-size:0.85rem;">Biaya Lain-Lain</span><div id="keg-total-lain" style="font-weight:600;font-size:1.1rem;">Rp 0</div></div>
    </div>
    <div style="display:flex; flex-wrap:wrap; gap:2rem; padding-top:1rem; border-top:1px solid var(--border);">
        <div style="flex:1;">
            <div style="font-size:0.85rem;color:var(--text-muted);">Persekot Kegiatan</div>
            <div style="font-weight:600;font-size:1.3rem;color:var(--warning-600);"><?= format_rupiah($kegiatan['persekot']) ?></div>
        </div>
        <div style="flex:1; text-align:right;">
            <div style="font-size:0.85rem;color:var(--text-muted);">Grand Total Kegiatan</div>
            <div id="keg-grand-total" style="font-weight:800;font-size:1.5rem;color:var(--primary-600);">Rp 0</div>
        </div>
        <div style="flex:1; text-align:right;">
            <div id="keg-kurang-lebih-label" style="font-size:0.85rem;color:var(--text-muted);">Sisa / Kekurangan</div>
            <div id="keg-kurang-lebih" style="font-weight:800;font-size:1.5rem;">Rp 0</div>
        </div>
    </div>
</div>

<!-- Modal: Add Biaya Lain -->
<div class="modal-overlay" id="modal-add-biayalain">
    <div class="modal">
        <div class="modal-header">
            <h2 id="modal-title-biayalain">Tambah Biaya Lain-Lain</h2>
            <button class="modal-close" onclick="closeModal('modal-add-biayalain')">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="new-biaya-id" value="">
            <div class="form-group">
                <label class="form-label" for="new-biaya-tanggal">Tanggal</label>
                <input class="form-input" type="date" id="new-biaya-tanggal">
            </div>
            <div class="form-group">
                <label class="form-label" for="new-biaya-nama">Nama Biaya *</label>
                <input class="form-input" type="text" id="new-biaya-nama" placeholder="Contoh: Cetak Spanduk, Konsumsi">
            </div>
            <div class="form-group">
                <label class="form-label" for="new-biaya-jumlah">Jumlah (Rp) *</label>
                <input class="form-input" type="number" id="new-biaya-jumlah" placeholder="0">
            </div>
            <div class="form-group">
                <label class="form-label" for="new-biaya-ket">Keterangan</label>
                <input class="form-input" type="text" id="new-biaya-ket" placeholder="Opsional">
            </div>
            <div class="form-group">
                <label class="form-label" for="new-biaya-file">Unggah Bukti</label>
                <input class="form-input" type="file" id="new-biaya-file" accept=".pdf,.jpg,.jpeg,.png">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('modal-add-biayalain')">Batal</button>
            <button class="btn btn-primary" onclick="saveBiayaLain()">Simpan</button>
        </div>
    </div>
</div>

<!-- Modal: Add SPD Manual -->
<div class="modal-overlay" id="modal-add-spd">
    <div class="modal">
        <div class="modal-header">
            <h2>Tambah SPD Manual</h2>
            <button class="modal-close" onclick="closeModal('modal-add-spd')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Cari Pengajar (opsional)</label>
                <div class="autocomplete-wrapper">
                    <input class="form-input" type="text" id="ac-pengajar-input" placeholder="Ketik nama pengajar...">
                    <div class="autocomplete-dropdown" id="ac-pengajar-dropdown"></div>
                </div>
            </div>
            <hr style="border-color:var(--border);margin:0.75rem 0;">
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="new-nama">Nama *</label>
                    <input class="form-input" type="text" id="new-nama" placeholder="Nama lengkap">
                </div>
                <div class="form-group">
                    <label class="form-label" for="new-nip">NIP</label>
                    <input class="form-input" type="text" id="new-nip" placeholder="NIP">
                </div>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="new-golongan">Golongan</label>
                    <input class="form-input" type="text" id="new-golongan" placeholder="IV/a">
                </div>
                <div class="form-group">
                    <label class="form-label" for="new-jabatan">Jabatan</label>
                    <input class="form-input" type="text" id="new-jabatan" placeholder="Jabatan">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="new-instansi">Instansi</label>
                <input class="form-input" type="text" id="new-instansi" placeholder="Instansi">
            </div>
            <input type="hidden" id="new-id-pengajar" value="0">
            <input type="hidden" id="new-no-rekening" value="">
            <input type="hidden" id="new-bank" value="">
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('modal-add-spd')">Batal</button>
            <button class="btn btn-primary" onclick="saveNewSpd()">Simpan</button>
        </div>
    </div>
</div>

<!-- Modal: Bulk Add from Pengajar -->
<div class="modal-overlay" id="modal-add-pengajar">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h2>Tambah SPD dari Data Pengajar</h2>
            <button class="modal-close" onclick="closeModal('modal-add-pengajar')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="search-input-wrapper">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input class="form-input" type="text" id="pengajar-search" placeholder="Cari nama atau NIP..." style="padding-left:2.2rem;">
            </div>
            <div class="pengajar-list" id="pengajar-list">
                <div class="empty-state"><p>Memuat data pengajar...</p></div>
            </div>
        </div>
        <div class="modal-footer">
            <span id="pengajar-selected-count" style="color:var(--text-muted);font-size:0.85rem;margin-right:auto;">0 dipilih</span>
            <button class="btn btn-secondary" onclick="closeModal('modal-add-pengajar')">Batal</button>
            <button class="btn btn-primary" onclick="bulkAddPengajar()">Tambahkan</button>
        </div>
    </div>
</div>

<script>
const CURRENT_ROLE = '<?= current_role() ?>';
const ID_KEGIATAN = <?= $id_kegiatan ?>;
const PERSEKOT_KEGIATAN = <?= (float)($kegiatan['persekot'] ?? 0) ?>;
let allPengajar = [];
let sumTiket = 0, sumUH = 0, sumHotel = 0, sumTransport = 0, sumRep = 0, sumLain = 0, grandTotalSpd = 0;
let sumBiayaLainOps = 0;

async function rejectSpd(id) {
    const alasan = prompt("Masukkan alasan penolakan (keterangan):");
    if (!alasan) return;
    const result = await postAPI('api/spd_api.php', { action: 'reject', id, alasan });
    if (result && result.success) {
        toast(result.message, 'success');
        loadSpdList();
    } else {
        toast(result?.message || 'Gagal menolak SPD.', 'error');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadSpdList();
    loadBiayaLain();
    loadPengajarList();
    
    // Init autocomplete for manual add
    initPengajarAutocomplete(
        document.getElementById('ac-pengajar-input'),
        document.getElementById('ac-pengajar-dropdown'),
        (p) => {
            document.getElementById('new-id-pengajar').value = p.id;
            document.getElementById('new-nama').value = p.nama;
            document.getElementById('new-nip').value = p.nip || '';
            document.getElementById('new-golongan').value = p.golongan || '';
            document.getElementById('new-instansi').value = p.instansi || '';
            document.getElementById('new-no-rekening').value = p.no_rekening || '';
            document.getElementById('new-bank').value = p.bank || '';
        }
    );
    
    // Search filter for pengajar list
    document.getElementById('pengajar-search').addEventListener('input', filterPengajar);
});

async function loadSpdList() {
    const result = await postAPI('api/spd_api.php', { action: 'list', id_kegiatan: ID_KEGIATAN });
    const tbody = document.getElementById('spd-tbody');
    
    if (!result || !result.success || !result.rows.length) {
        tbody.innerHTML = '<tr><td colspan="17"><div class="empty-state"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg><h3>Belum Ada SPD</h3><p>Tambahkan SPD dari data pengajar atau input manual</p></div></td></tr>';
        sumTiket = sumUH = sumHotel = sumTransport = sumRep = sumLain = grandTotalSpd = 0;
        updateSummaryCard();
        return;
    }
    
    sumTiket = 0; sumUH = 0; sumHotel = 0; sumTransport = 0; sumRep = 0; sumLain = 0; grandTotalSpd = 0;

    tbody.innerHTML = result.rows.map((s, i) => {
        sumTiket += s.total_tiket || 0;
        sumUH += s.total_uang_harian || 0;
        sumHotel += s.total_hotel || 0;
        sumTransport += s.total_transport || 0;
        sumRep += s.uang_representatif_total || 0;
        sumLain += (s.total_covid || 0) + (s.total_biaya_bukti || 0);
        grandTotalSpd += s.grand_total || 0;
        
        const dateDisplay = formatIndoDateRange(s.tgl_mulai, s.tgl_akhir);
        
        return `
        <tr>
            <td>${i + 1}</td>
            <td>
                <a href="?page=spd_detail&id=${s.id}" style="color:var(--primary-700);font-weight:600;text-decoration:none;display:block;">${escapeHtml(s.nama)}</a>
                <span class="editable" data-id="${s.id}" data-field="nip" data-type="text" data-value="${escapeHtml(s.nip || '')}" style="font-size:0.85em; color:var(--text-muted);">${escapeHtml(s.nip || '-')}</span>
            </td>
            <td class="editable" data-id="${s.id}" data-field="golongan" data-type="text" data-value="${escapeHtml(s.golongan || '')}">${escapeHtml(s.golongan || '-')}</td>
            <td class="editable" data-id="${s.id}" data-field="kota_asal" data-type="text" data-value="${escapeHtml(s.kota_asal || '')}">${escapeHtml(s.kota_asal || '-')}</td>
            <td>
                <span class="editable" data-id="${s.id}" data-field="tgl_mulai" data-type="date" data-value="${s.tgl_mulai || ''}">${escapeHtml(dateDisplay.start)}</span>
                <span style="color:var(--border)">-</span>
                <span class="editable" data-id="${s.id}" data-field="tgl_akhir" data-type="date" data-value="${s.tgl_akhir || ''}">${escapeHtml(dateDisplay.end)}</span>
            </td>
            <td class="editable currency" data-id="${s.id}" data-field="uh_per_hari" data-type="number" data-value="${s.uh_per_hari || 0}">${formatNumber(s.uh_per_hari)}</td>
            <td class="editable" data-id="${s.id}" data-field="uh_jml_hari" data-type="number" data-value="${s.uh_jml_hari || 0}">${s.uh_jml_hari || '-'}</td>
            <td class="currency" data-computed="total_uang_harian">${formatRupiah(s.total_uang_harian)}</td>
            <td class="editable currency" data-id="${s.id}" data-field="hotel1_tarif" data-type="number" data-value="${s.hotel1_tarif || 0}">${formatNumber(s.hotel1_tarif)}</td>
            <td class="editable" data-id="${s.id}" data-field="hotel1_hari" data-type="number" data-value="${s.hotel1_hari || 0}">${s.hotel1_hari || '-'}</td>
            <td class="currency" data-computed="total_hotel">${formatRupiah(s.total_hotel)}</td>
            <td class="currency" data-computed="total_tiket">${formatRupiah(s.total_tiket)}</td>
            <td class="currency total-row" data-computed="grand_total">${formatRupiah(s.grand_total)}</td>
            <td>
                <span class="badge badge-${s.status}">${s.status.toUpperCase()}</span>
            </td>
            <td>
                <div style="display:flex; gap:0.25rem; justify-content:center;">
                    <a href="?page=spd_detail&id=${s.id}" class="btn btn-sm btn-secondary" title="Detail">📋</a>
                    <button class="btn btn-sm btn-danger" onclick="deleteSpd(${s.id})" title="Hapus">✕</button>
                    ${(CURRENT_ROLE === 'Admin Super' && s.status !== 'draft') ? `<button class="btn btn-sm btn-warning" onclick="rejectSpd(${s.id})" title="Reject">↩️</button>` : ''}
                </div>
            </td>
        </tr>
        `;
    }).join('');
    
    updateSummaryCard();
    initInlineEdit(document.getElementById('spd-table'));
}

async function loadBiayaLain() {
    const result = await postAPI('api/biaya_lain_api.php', { action: 'list', id_kegiatan: ID_KEGIATAN });
    const tbody = document.getElementById('biayalain-tbody');
    
    if (!result || !result.success || !result.rows.length) {
        tbody.innerHTML = '<tr><td colspan="7"><div class="empty-state"><p>Belum ada biaya lain-lain.</p></div></td></tr>';
        sumBiayaLainOps = 0;
        updateSummaryCard();
        return;
    }
    
    sumBiayaLainOps = 0;
    tbody.innerHTML = result.rows.map((b, i) => {
        sumBiayaLainOps += parseFloat(b.jumlah) || 0;
        const fileLink = b.file_bukti 
            ? `<a href="api/biaya_lain_api.php?action=download&id=${b.id}" target="_blank" title="Download Bukti">📄</a>` 
            : '-';
        return `
        <tr>
            <td>${i + 1}</td>
            <td>${escapeHtml(b.tanggal || '-')}</td>
            <td>${escapeHtml(b.nama_biaya)}</td>
            <td>${escapeHtml(b.keterangan || '-')}</td>
            <td class="currency">${formatRupiah(b.jumlah)}</td>
            <td style="text-align:center;">${fileLink}</td>
            <td>
                <div style="display:flex; gap:0.25rem; justify-content:center;">
                    <button class="btn btn-sm btn-secondary" onclick='editBiayaLain(${JSON.stringify(b).replace(/'/g, "&apos;")})' title="Edit">✏️</button>
                    <button class="btn btn-sm btn-danger" onclick="deleteBiayaLain(${b.id})" title="Hapus">✕</button>
                </div>
            </td>
        </tr>
        `;
    }).join('');
    
    updateSummaryCard();
}

function updateSummaryCard() {
    document.getElementById('keg-total-tiket').textContent = formatRupiah(sumTiket);
    document.getElementById('keg-total-uh').textContent = formatRupiah(sumUH);
    document.getElementById('keg-total-hotel').textContent = formatRupiah(sumHotel);
    document.getElementById('keg-total-transport').textContent = formatRupiah(sumTransport);
    document.getElementById('keg-total-rep').textContent = formatRupiah(sumRep);
    
    const combinedLain = sumLain + sumBiayaLainOps;
    document.getElementById('keg-total-lain').textContent = formatRupiah(combinedLain);
    
    const overallGrandTotal = grandTotalSpd + sumBiayaLainOps;
    document.getElementById('keg-grand-total').textContent = formatRupiah(overallGrandTotal);
    
    const kurangLebih = PERSEKOT_KEGIATAN - overallGrandTotal;
    const elKurangLebih = document.getElementById('keg-kurang-lebih');
    const elLabel = document.getElementById('keg-kurang-lebih-label');
    
    if (kurangLebih >= 0) {
        elLabel.textContent = 'Sisa (Lebih)';
        elKurangLebih.textContent = formatRupiah(kurangLebih);
        elKurangLebih.style.color = 'var(--success-600)';
    } else {
        elLabel.textContent = 'Kekurangan (Kurang)';
        elKurangLebih.textContent = formatRupiah(Math.abs(kurangLebih));
        elKurangLebih.style.color = 'var(--danger-600)';
    }
}

async function saveNewSpd() {
    const data = {
        action: 'create',
        id_kegiatan: ID_KEGIATAN,
        id_pengajar: parseInt(document.getElementById('new-id-pengajar').value) || 0,
        nama: document.getElementById('new-nama').value,
        nip: document.getElementById('new-nip').value,
        golongan: document.getElementById('new-golongan').value,
        jabatan: document.getElementById('new-jabatan').value,
        instansi: document.getElementById('new-instansi').value,
        no_rekening: document.getElementById('new-no-rekening').value,
        bank: document.getElementById('new-bank').value,
    };
    
    const result = await postAPI('api/spd_api.php', data);
    if (result && result.success) {
        toast(result.message, 'success');
        closeModal('modal-add-spd');
        // Reset form
        document.getElementById('new-id-pengajar').value = '0';
        document.getElementById('new-nama').value = '';
        document.getElementById('new-nip').value = '';
        document.getElementById('new-golongan').value = '';
        document.getElementById('new-jabatan').value = '';
        document.getElementById('new-instansi').value = '';
        document.getElementById('ac-pengajar-input').value = '';
        loadSpdList();
    } else {
        toast(result?.message || 'Gagal menyimpan.', 'error');
    }
}

async function saveBiayaLain() {
    const id = document.getElementById('new-biaya-id').value;
    const fd = new FormData();
    fd.append('action', id ? 'update' : 'create');
    if (id) fd.append('id', id);
    fd.append('id_kegiatan', ID_KEGIATAN);
    fd.append('tanggal', document.getElementById('new-biaya-tanggal').value);
    fd.append('nama_biaya', document.getElementById('new-biaya-nama').value);
    fd.append('jumlah', document.getElementById('new-biaya-jumlah').value);
    fd.append('keterangan', document.getElementById('new-biaya-ket').value);
    
    const fileInput = document.getElementById('new-biaya-file');
    if (fileInput.files.length > 0) {
        fd.append('file', fileInput.files[0]);
    }

    try {
        const response = await fetch('api/biaya_lain_api.php', {
            method: 'POST',
            body: fd
        });
        const result = await response.json();
        
        if (result && result.success) {
            toast(result.message, 'success');
            closeModal('modal-add-biayalain');
            document.getElementById('new-biaya-tanggal').value = '';
            document.getElementById('new-biaya-nama').value = '';
            document.getElementById('new-biaya-jumlah').value = '';
            document.getElementById('new-biaya-ket').value = '';
            document.getElementById('new-biaya-file').value = '';
            loadBiayaLain();
        } else {
            toast(result?.message || 'Gagal menyimpan.', 'error');
        }
    } catch (e) {
        console.error(e);
        toast('Terjadi kesalahan jaringan.', 'error');
    }
}

function openAddBiayaLain() {
    document.getElementById('modal-title-biayalain').textContent = 'Tambah Biaya Lain-Lain';
    document.getElementById('new-biaya-id').value = '';
    document.getElementById('new-biaya-tanggal').value = '';
    document.getElementById('new-biaya-nama').value = '';
    document.getElementById('new-biaya-jumlah').value = '';
    document.getElementById('new-biaya-ket').value = '';
    document.getElementById('new-biaya-file').value = '';
    openModal('modal-add-biayalain');
}

function editBiayaLain(b) {
    document.getElementById('modal-title-biayalain').textContent = 'Edit Biaya Lain-Lain';
    document.getElementById('new-biaya-id').value = b.id;
    document.getElementById('new-biaya-tanggal').value = b.tanggal || '';
    document.getElementById('new-biaya-nama').value = b.nama_biaya || '';
    document.getElementById('new-biaya-jumlah').value = b.jumlah || 0;
    document.getElementById('new-biaya-ket').value = b.keterangan || '';
    document.getElementById('new-biaya-file').value = '';
    openModal('modal-add-biayalain');
}

async function deleteBiayaLain(id) {
    if (!confirm('Yakin ingin menghapus biaya ini?')) return;
    const result = await postAPI('api/biaya_lain_api.php', { action: 'delete', id });
    if (result && result.success) {
        toast(result.message, 'success');
        loadBiayaLain();
    } else {
        toast(result?.message || 'Gagal menghapus.', 'error');
    }
}

async function loadPengajarList() {
    const result = await postAPI('api/pengajar_api.php', { action: 'list' });
    if (!result || !result.success) {
        document.getElementById('pengajar-list').innerHTML = '<div class="empty-state"><p>Tidak dapat memuat data pengajar. Pastikan koneksi MySQL tersedia.</p></div>';
        return;
    }
    allPengajar = result.rows;
    renderPengajarList(allPengajar);
}

function renderPengajarList(list) {
    const container = document.getElementById('pengajar-list');
    if (!list.length) {
        container.innerHTML = '<div class="empty-state"><p>Tidak ada data ditemukan</p></div>';
        return;
    }
    container.innerHTML = list.map(p => `
        <label class="pengajar-item">
            <input type="checkbox" value="${p.id}" class="pengajar-cb">
            <div>
                <div class="p-name">${escapeHtml(p.nama)}</div>
                <div class="p-detail">${escapeHtml(p.nip || '-')} · ${escapeHtml(p.instansi || '-')} · ${escapeHtml(p.golongan || '-')}</div>
            </div>
        </label>
    `).join('');
    
    // Update count
    container.querySelectorAll('.pengajar-cb').forEach(cb => {
        cb.addEventListener('change', updateSelectedCount);
    });
}

function filterPengajar() {
    const q = document.getElementById('pengajar-search').value.toLowerCase();
    const filtered = allPengajar.filter(p => 
        (p.nama || '').toLowerCase().includes(q) || 
        (p.nip || '').toLowerCase().includes(q) ||
        (p.instansi || '').toLowerCase().includes(q)
    );
    renderPengajarList(filtered);
}

function updateSelectedCount() {
    const count = document.querySelectorAll('.pengajar-cb:checked').length;
    document.getElementById('pengajar-selected-count').textContent = count + ' dipilih';
}

async function bulkAddPengajar() {
    const ids = Array.from(document.querySelectorAll('.pengajar-cb:checked')).map(cb => parseInt(cb.value));
    if (!ids.length) {
        toast('Pilih minimal 1 pengajar.', 'error');
        return;
    }
    
    const result = await postAPI('api/spd_api.php', {
        action: 'bulk_create',
        id_kegiatan: ID_KEGIATAN,
        pengajar_ids: ids,
    });
    
    if (result && result.success) {
        toast(result.message, 'success');
        closeModal('modal-add-pengajar');
        loadSpdList();
    } else {
        toast(result?.message || 'Gagal menambahkan.', 'error');
    }
}

async function kirimKegiatan() {
    if (!confirm('Yakin ingin mengirim semua data SPD dalam kegiatan ini ke Keuangan? Data yang sudah dikirim (Submitted) mungkin tidak bisa diedit lagi.')) return;
    const result = await postAPI('api/kegiatan_api.php', { action: 'submit_all', id: ID_KEGIATAN });
    if (result && result.success) {
        toast(result.message, 'success');
        loadSpdList();
    } else {
        toast(result?.message || 'Gagal mengirim.', 'error');
    }
}

async function deleteSpd(id) {
    if (!confirm('Yakin ingin menghapus SPD ini? File bukti juga akan dihapus.')) return;
    const result = await postAPI('api/spd_api.php', { action: 'delete', id });
    if (result && result.success) {
        toast(result.message, 'success');
        loadSpdList();
    } else {
        toast(result?.message || 'Gagal menghapus.', 'error');
    }
}
</script>
