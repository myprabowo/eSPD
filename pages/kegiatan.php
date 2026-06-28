<?php
// Kegiatan management page
?>

<div class="page-header">
    <div>
        <h1>Manajemen Kegiatan</h1>
        <p class="subtitle">Kelola kegiatan pelatihan dan perjalanan dinas</p>
    </div>
    <div class="header-actions">
        <button class="btn btn-primary" onclick="openModal('modal-kegiatan')">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Tambah Kegiatan
        </button>
    </div>
</div>

<div class="card">
    <div id="kegiatan-table-container">
        <div class="table-wrapper">
            <table id="kegiatan-table">
                <thead>
                    <tr>
                        <th style="width:40px">#</th>
                        <th>Nama Kegiatan</th>
                        <th>Nomor ST</th>
                        <th>Tanggal ST</th>
                        <th>Kota Tujuan</th>
                        <th>Jumlah SPD</th>
                        <th style="width:120px">Aksi</th>
                    </tr>
                </thead>
                <tbody id="kegiatan-tbody">
                    <tr><td colspan="7" class="empty-state"><p>Memuat data...</p></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Tambah/Edit Kegiatan -->
<div class="modal-overlay" id="modal-kegiatan">
    <div class="modal">
        <div class="modal-header">
            <h2 id="modal-kegiatan-title">Tambah Kegiatan</h2>
            <button class="modal-close" onclick="closeModal('modal-kegiatan')">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="keg-id">
            <div class="form-group">
                <label class="form-label" for="keg-nama">Nama Kegiatan *</label>
                <input class="form-input" type="text" id="keg-nama" placeholder="Contoh: Pelatihan BMD Batch I">
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="keg-nomor-st">Nomor Surat Tugas</label>
                    <input class="form-input" type="text" id="keg-nomor-st" placeholder="ST-460/PKN/2026">
                </div>
                <div class="form-group">
                    <label class="form-label" for="keg-tanggal-st">Tanggal Surat Tugas</label>
                    <input class="form-input" type="date" id="keg-tanggal-st">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="keg-perihal">Perihal Surat Tugas</label>
                <textarea class="form-textarea" id="keg-perihal" rows="3" placeholder="Perihal / Uraian kegiatan"></textarea>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="keg-kota">Kota Tujuan</label>
                    <input class="form-input" type="text" id="keg-kota" placeholder="Contoh: Sorong">
                </div>
                <div class="form-group">
                    <label class="form-label" for="keg-persekot">Persekot (Rp)</label>
                    <input class="form-input" type="number" id="keg-persekot" placeholder="0">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('modal-kegiatan')">Batal</button>
            <button class="btn btn-primary" onclick="saveKegiatan()">Simpan</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', loadKegiatan);

async function loadKegiatan() {
    const result = await postAPI('api/kegiatan_api.php', { action: 'list' });
    const tbody = document.getElementById('kegiatan-tbody');
    
    if (!result || !result.success || !result.rows.length) {
        tbody.innerHTML = '<tr><td colspan="7"><div class="empty-state"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg><h3>Belum Ada Kegiatan</h3><p>Klik "Tambah Kegiatan" untuk memulai</p></div></td></tr>';
        return;
    }
    
    tbody.innerHTML = result.rows.map((k, i) => `
        <tr class="clickable" onclick="window.location='?page=spd_list&id_kegiatan=${k.id}'">
            <td>${i + 1}</td>
            <td><strong>${escapeHtml(k.nama_kegiatan)}</strong></td>
            <td style="font-size:0.82rem">${escapeHtml(k.nomor_st || '-')}</td>
            <td style="font-size:0.82rem">${k.tanggal_st ? formatIndoDate(k.tanggal_st) : '-'}</td>
            <td>${escapeHtml(k.kota_tujuan || '-')}</td>
            <td><span class="badge badge-submitted">${k.jumlah_spd}</span></td>
            <td onclick="event.stopPropagation()">
                <button class="btn btn-sm btn-secondary" onclick="editKegiatan(${k.id})" title="Edit">✎</button>
                <button class="btn btn-sm btn-danger" onclick="deleteKegiatan(${k.id})" title="Hapus">✕</button>
            </td>
        </tr>
    `).join('');
}

async function saveKegiatan() {
    const id = document.getElementById('keg-id').value;
    const data = {
        action: id ? 'update' : 'create',
        nama_kegiatan: document.getElementById('keg-nama').value,
        nomor_st: document.getElementById('keg-nomor-st').value,
        tanggal_st: document.getElementById('keg-tanggal-st').value,
        perihal_st: document.getElementById('keg-perihal').value,
        kota_tujuan: document.getElementById('keg-kota').value,
        persekot: document.getElementById('keg-persekot').value,
    };
    if (id) data.id = parseInt(id);
    
    const result = await postAPI('api/kegiatan_api.php', data);
    if (result && result.success) {
        toast(result.message, 'success');
        closeModal('modal-kegiatan');
        resetKegiatanForm();
        loadKegiatan();
    } else {
        toast(result?.message || 'Gagal menyimpan.', 'error');
    }
}

async function editKegiatan(id) {
    const result = await postAPI('api/kegiatan_api.php', { action: 'get', id });
    if (!result || !result.success) return;
    
    const k = result.row;
    document.getElementById('keg-id').value = k.id;
    document.getElementById('keg-nama').value = k.nama_kegiatan;
    document.getElementById('keg-nomor-st').value = k.nomor_st || '';
    document.getElementById('keg-tanggal-st').value = k.tanggal_st || '';
    document.getElementById('keg-perihal').value = k.perihal_st || '';
    document.getElementById('keg-kota').value = k.kota_tujuan || '';
    document.getElementById('keg-persekot').value = k.persekot || '0';
    document.getElementById('modal-kegiatan-title').textContent = 'Edit Kegiatan';
    openModal('modal-kegiatan');
}

async function deleteKegiatan(id) {
    if (!confirm('Yakin ingin menghapus kegiatan ini?')) return;
    const result = await postAPI('api/kegiatan_api.php', { action: 'delete', id });
    if (result && result.success) {
        toast(result.message, 'success');
        loadKegiatan();
    } else {
        toast(result?.message || 'Gagal menghapus.', 'error');
    }
}

function resetKegiatanForm() {
    document.getElementById('keg-id').value = '';
    document.getElementById('keg-nama').value = '';
    document.getElementById('keg-nomor-st').value = '';
    document.getElementById('keg-tanggal-st').value = '';
    document.getElementById('keg-perihal').value = '';
    document.getElementById('keg-kota').value = '';
    document.getElementById('keg-persekot').value = '';
    document.getElementById('modal-kegiatan-title').textContent = 'Tambah Kegiatan';
}
</script>
