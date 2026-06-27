/**
 * app.js — eSPD Core JavaScript Module
 */

// ==================== API Helpers ====================

async function fetchAPI(url, options = {}) {
    const defaults = {
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    };
    if (options.body && typeof options.body === 'object' && !(options.body instanceof FormData)) {
        options.body = JSON.stringify(options.body);
    }
    if (options.body instanceof FormData) {
        delete defaults.headers['Content-Type'];
    }
    const config = { ...defaults, ...options, headers: { ...defaults.headers, ...(options.headers || {}) } };
    
    try {
        const res = await fetch(url, config);
        if (res.status === 401) {
            toast('Sesi habis. Silakan login kembali.', 'error');
            setTimeout(() => window.location.href = 'index.php', 1500);
            return null;
        }
        const contentType = res.headers.get('content-type');
        if (contentType && contentType.includes('json')) {
            return await res.json();
        }
        return null;
    } catch (err) {
        console.error('API Error:', err);
        toast('Terjadi kesalahan koneksi.', 'error');
        return null;
    }
}

async function postAPI(url, data) {
    return fetchAPI(url, { method: 'POST', body: data });
}

// ==================== Toast Notifications ====================

function toast(message, type = 'info') {
    const container = document.getElementById('toast-container');
    if (!container) return;
    
    const el = document.createElement('div');
    el.className = `toast toast-${type}`;
    
    const icons = {
        success: '✓',
        error: '✗',
        info: 'ℹ',
    };
    
    el.innerHTML = `<span style="font-weight:700;font-size:1rem;">${icons[type] || ''}</span> ${escapeHtml(message)}`;
    container.appendChild(el);
    
    setTimeout(() => {
        el.classList.add('removing');
        setTimeout(() => el.remove(), 300);
    }, 3500);
}

// ==================== Formatting ====================

function formatRupiah(num) {
    if (!num || num === 0) return '-';
    return 'Rp ' + Number(num).toLocaleString('id-ID');
}

function formatNumber(num) {
    if (!num || num === 0) return '-';
    return Number(num).toLocaleString('id-ID');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

// ==================== Inline Edit ====================

function initInlineEdit(tableEl) {
    if (!tableEl) return;
    
    tableEl.querySelectorAll('td.editable').forEach(td => {
        td.addEventListener('click', function(e) {
            if (this.classList.contains('editing')) return;
            startInlineEdit(this);
        });
    });
}

function startInlineEdit(td) {
    const field = td.dataset.field;
    const id = td.dataset.id;
    const type = td.dataset.type || 'text';
    const currentValue = td.dataset.value || td.textContent.trim();
    
    td.classList.add('editing');
    
    let input;
    if (type === 'number') {
        input = document.createElement('input');
        input.type = 'number';
        input.step = 'any';
        input.value = currentValue.replace(/[^\d.-]/g, '') || '0';
    } else if (type === 'date') {
        input = document.createElement('input');
        input.type = 'date';
        input.value = currentValue;
    } else {
        input = document.createElement('input');
        input.type = 'text';
        input.value = currentValue;
    }
    
    td.textContent = '';
    td.appendChild(input);
    input.focus();
    input.select();
    
    const saveEdit = async () => {
        const newValue = input.value;
        td.classList.remove('editing');
        td.classList.add('cell-saving');
        td.textContent = type === 'number' ? formatNumber(newValue) : newValue;
        td.dataset.value = newValue;
        
        const result = await postAPI('api/spd_api.php', {
            action: 'inline_update',
            id: parseInt(id),
            field: field,
            value: newValue,
        });
        
        td.classList.remove('cell-saving');
        
        if (result && result.success) {
            td.classList.add('cell-saved');
            setTimeout(() => td.classList.remove('cell-saved'), 600);
            
            // Update computed totals in the row
            if (result.row) {
                updateRowTotals(td.closest('tr'), result.row);
            }
        } else {
            toast(result?.message || 'Gagal menyimpan.', 'error');
            td.textContent = currentValue;
        }
    };
    
    input.addEventListener('blur', saveEdit);
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            input.blur();
        }
        if (e.key === 'Escape') {
            td.classList.remove('editing');
            td.textContent = type === 'number' ? formatNumber(currentValue) : currentValue;
            td.dataset.value = currentValue;
        }
    });
}

function updateRowTotals(tr, row) {
    if (!tr) return;
    tr.querySelectorAll('td[data-computed]').forEach(td => {
        const key = td.dataset.computed;
        if (row[key] !== undefined) {
            td.textContent = formatRupiah(row[key]);
        }
    });
}

// ==================== Modal ====================

function openModal(id) {
    const overlay = document.getElementById(id);
    if (!overlay) return;
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeModal(id) {
    const overlay = document.getElementById(id);
    if (!overlay) return;
    overlay.classList.remove('active');
    document.body.style.overflow = '';
}

// Close modal on overlay click
document.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('active');
        document.body.style.overflow = '';
    }
});

// Close modal on Escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active').forEach(m => {
            m.classList.remove('active');
        });
        document.body.style.overflow = '';
    }
});

// ==================== Section Toggle (Accordion) ====================

function initSections() {
    document.querySelectorAll('.section-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
            btn.classList.toggle('open');
            const content = btn.nextElementSibling;
            if (content) content.classList.toggle('open');
        });
    });
}

// ==================== Tab Navigation ====================

function initTabs() {
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const tabGroup = btn.closest('.tab-nav').dataset.group || 'default';
            
            // Deactivate all tabs in group
            document.querySelectorAll(`.tab-btn[data-group="${tabGroup}"]`).forEach(b => b.classList.remove('active'));
            document.querySelectorAll(`.tab-panel[data-group="${tabGroup}"]`).forEach(p => p.classList.remove('active'));
            
            // Activate clicked tab
            btn.classList.add('active');
            const panel = document.getElementById(btn.dataset.tab);
            if (panel) panel.classList.add('active');
        });
    });
}

// ==================== File Upload ====================

function initUploadZone(zoneEl, options = {}) {
    if (!zoneEl) return;
    
    const fileInput = zoneEl.querySelector('input[type="file"]');
    
    zoneEl.addEventListener('click', () => fileInput?.click());
    
    zoneEl.addEventListener('dragover', (e) => {
        e.preventDefault();
        zoneEl.classList.add('dragover');
    });
    
    zoneEl.addEventListener('dragleave', () => {
        zoneEl.classList.remove('dragover');
    });
    
    zoneEl.addEventListener('drop', (e) => {
        e.preventDefault();
        zoneEl.classList.remove('dragover');
        if (e.dataTransfer.files.length) {
            const files = Array.from(e.dataTransfer.files);
            const selectEl = document.getElementById('file-kategori');
            const catName = selectEl ? selectEl.options[selectEl.selectedIndex].text : 'kategori ini';
            if (confirm(`Yakin ingin menyimpan ${files.length} file bukti ke bagian "${catName}"?`)) {
                files.forEach(f => handleFileUpload(f, options));
            }
        }
    });
    
    fileInput?.addEventListener('change', () => {
        if (fileInput.files.length) {
            const files = Array.from(fileInput.files);
            const selectEl = document.getElementById('file-kategori');
            const catName = selectEl ? selectEl.options[selectEl.selectedIndex].text : 'kategori ini';
            if (confirm(`Yakin ingin menyimpan ${files.length} file bukti ke bagian "${catName}"?`)) {
                files.forEach(f => handleFileUpload(f, options));
            }
            fileInput.value = '';
        }
    });
}

async function handleFileUpload(file, options = {}) {
    const { id_spd, kategori = 'bukti_lain', onSuccess } = options;
    
    if (file.size > 20 * 1024 * 1024) {
        toast('File terlalu besar (max 20MB).', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'upload');
    formData.append('file', file);
    formData.append('id_spd', id_spd);
    formData.append('kategori', kategori);
    
    const result = await fetch('api/file_api.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData,
    }).then(r => r.json()).catch(() => null);
    
    if (result && result.success) {
        toast('File berhasil diunggah!', 'success');
        if (onSuccess) onSuccess(result.file);
    } else {
        toast(result?.message || 'Gagal mengunggah file.', 'error');
    }
}

async function deleteFile(fileId, onSuccess) {
    if (!confirm('Hapus file ini?')) return;
    
    const result = await postAPI('api/file_api.php', { action: 'delete', id: fileId });
    if (result && result.success) {
        toast('File dihapus!', 'success');
        if (onSuccess) onSuccess();
    } else {
        toast(result?.message || 'Gagal menghapus file.', 'error');
    }
}

// ==================== Pengajar Search Autocomplete ====================

function initPengajarAutocomplete(inputEl, dropdownEl, onSelect) {
    let debounceTimer = null;
    
    inputEl.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        const q = inputEl.value.trim();
        
        if (q.length < 2) {
            dropdownEl.classList.remove('show');
            return;
        }
        
        debounceTimer = setTimeout(async () => {
            const result = await postAPI('api/pengajar_api.php', { action: 'search', q });
            if (!result || !result.success) return;
            
            dropdownEl.innerHTML = '';
            if (result.rows.length === 0) {
                dropdownEl.innerHTML = '<div class="autocomplete-item"><em>Tidak ditemukan</em></div>';
            } else {
                result.rows.forEach(p => {
                    const item = document.createElement('div');
                    item.className = 'autocomplete-item';
                    item.innerHTML = `<div class="ac-name">${escapeHtml(p.nama)}</div>
                        <div class="ac-detail">${escapeHtml(p.nip || '-')} · ${escapeHtml(p.instansi || '-')}</div>`;
                    item.addEventListener('click', () => {
                        onSelect(p);
                        dropdownEl.classList.remove('show');
                        inputEl.value = p.nama;
                    });
                    dropdownEl.appendChild(item);
                });
            }
            dropdownEl.classList.add('show');
        }, 300);
    });
    
    // Close dropdown on click outside
    document.addEventListener('click', (e) => {
        if (!inputEl.contains(e.target) && !dropdownEl.contains(e.target)) {
            dropdownEl.classList.remove('show');
        }
    });
}

// ==================== Utility ====================

function getUrlParam(name) {
    return new URLSearchParams(window.location.search).get(name);
}

function setLoading(show) {
    let overlay = document.getElementById('loading-overlay');
    if (show) {
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'loading-overlay';
            overlay.className = 'loading-overlay';
            overlay.innerHTML = '<div class="spinner"></div><p>Memuat...</p>';
            document.body.appendChild(overlay);
        }
        overlay.style.display = 'flex';
    } else if (overlay) {
        overlay.style.display = 'none';
    }
}

// ==================== Init ====================

document.addEventListener('DOMContentLoaded', () => {
    initSections();
    initTabs();
});
