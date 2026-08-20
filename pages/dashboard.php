<?php
// Dashboard — Overview statistics
$isPic = is_pic_keuangan();
$uKeg = $isPic ? "" : " WHERE created_by = '" . current_username() . "'";
$uSpd = $isPic ? "" : " WHERE created_by = '" . current_username() . "'";
$uSpdAnd = $isPic ? "" : " AND created_by = '" . current_username() . "'";
$uSpdAndS = $isPic ? "" : " WHERE s.created_by = '" . current_username() . "'";

$kegiatanCount  = db_query("SELECT COUNT(*) as cnt FROM kegiatan $uKeg")[0]['cnt'] ?? 0;
$spdCount       = db_query("SELECT COUNT(*) as cnt FROM spd $uSpd")[0]['cnt'] ?? 0;
$draftCount     = db_query("SELECT COUNT(*) as cnt FROM spd WHERE status='draft' $uSpdAnd")[0]['cnt'] ?? 0;
$submittedCount = db_query("SELECT COUNT(*) as cnt FROM spd WHERE status='submitted' $uSpdAnd")[0]['cnt'] ?? 0;
$paidCount      = db_query("SELECT COUNT(*) as cnt FROM spd WHERE status='paid' $uSpdAnd")[0]['cnt'] ?? 0;

if ($isPic) {
    // Prioritize submitted items at the top of the list for PIC Keuangan
    $recentSpd = db_query("SELECT s.*, k.nama_kegiatan FROM spd s LEFT JOIN kegiatan k ON s.id_kegiatan = k.id $uSpdAndS ORDER BY (s.status = 'submitted') DESC, s.updated_at DESC LIMIT 10");
    $recentKeg = db_query("SELECT k.*, 
                                  (SELECT COUNT(*) FROM spd WHERE id_kegiatan = k.id) as jumlah_spd,
                                  (SELECT COUNT(*) FROM spd WHERE id_kegiatan = k.id AND status = 'submitted') as submitted_spd
                           FROM kegiatan k $uKeg 
                           ORDER BY (SELECT COUNT(*) FROM spd WHERE id_kegiatan = k.id AND status = 'submitted') DESC, k.created_at DESC LIMIT 5");
} else {
    $recentSpd = db_query("SELECT s.*, k.nama_kegiatan FROM spd s LEFT JOIN kegiatan k ON s.id_kegiatan = k.id $uSpdAndS ORDER BY s.updated_at DESC LIMIT 10");
    $recentKeg = db_query("SELECT k.*, (SELECT COUNT(*) FROM spd WHERE id_kegiatan = k.id) as jumlah_spd FROM kegiatan k $uKeg ORDER BY k.created_at DESC LIMIT 5");
}
?>

<div class="page-header">
    <div>
        <div style="display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;">
            <h1 style="margin: 0;">Dashboard</h1>
            <?php if ($isPic): ?>
                <span class="badge badge-submitted" style="font-weight: 700; padding: 0.25rem 0.65rem; border: 1px solid rgba(37, 99, 235, 0.3);">
                    PIC KEUANGAN
                </span>
            <?php endif; ?>
        </div>
        <p class="subtitle">Selamat datang, <?= h(current_user()) ?></p>
    </div>
</div>

<?php if ($isPic && $submittedCount > 0): ?>
<div class="alert-banner-keuangan">
    <div class="alert-banner-icon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
        </svg>
    </div>
    <div class="alert-banner-content">
        <h4>Terdapat <?= $submittedCount ?> Berkas SPD Menunggu Verifikasi</h4>
        <p>Data SPD telah diajukan (submitted) oleh pembuat kegiatan dan siap untuk diperiksa rincian biaya, dokumen bukti fisik, serta diproses verifikasinya.</p>
    </div>
</div>
<?php endif; ?>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon purple">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
        </div>
        <div class="stat-info">
            <h3><?= $kegiatanCount ?></h3>
            <p>Total Kegiatan</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <div class="stat-info">
            <h3><?= $spdCount ?></h3>
            <p>Total SPD</p>
        </div>
    </div>
    <?php if ($isPic): ?>
    <div class="stat-card <?= $submittedCount > 0 ? 'stat-card-attention' : '' ?>">
        <div class="stat-icon blue" style="background: rgba(37, 99, 235, 0.15); color: var(--primary-600);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <line x1="16" y1="13" x2="8" y2="13"/>
                <line x1="16" y1="17" x2="8" y2="17"/>
                <polyline points="10 9 9 9 8 9"/>
            </svg>
        </div>
        <div class="stat-info">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <h3 style="margin: 0;"><?= $submittedCount ?></h3>
                <?php if ($submittedCount > 0): ?>
                    <span class="badge badge-submitted-pulse" style="font-size: 0.65rem; padding: 0.1rem 0.45rem;">
                        <span class="pulse-dot"></span> Perlu Review
                    </span>
                <?php endif; ?>
            </div>
            <p>SPD Submitted</p>
        </div>
    </div>
    <?php else: ?>
    <div class="stat-card">
        <div class="stat-icon orange">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div class="stat-info">
            <h3><?= $draftCount ?></h3>
            <p>SPD Draft</p>
        </div>
    </div>
    <?php endif; ?>
    <div class="stat-card">
        <div class="stat-icon green">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <div class="stat-info">
            <h3><?= $paidCount ?></h3>
            <p>SPD Terbayar</p>
        </div>
    </div>
</div>

<div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
    <!-- Recent Kegiatan -->
    <div class="card">
        <div class="card-header">
            <h2>Kegiatan Terbaru</h2>
            <a href="?page=kegiatan" class="btn btn-sm btn-secondary">Lihat Semua</a>
        </div>
        <?php if (empty($recentKeg)): ?>
            <div class="empty-state" style="padding: 4rem 1rem; text-align: center;">
                <div style="font-size: 3.5rem; color: var(--border); margin-bottom: 1rem;">📁</div>
                <h3 style="color: var(--text-primary); margin-bottom: 0.5rem; font-size: 1.1rem;">Belum Ada Kegiatan</h3>
                <p style="color: var(--text-muted); margin-bottom: 1.5rem; font-size: 0.95rem;">Anda belum menambahkan kegiatan atau pelatihan apa pun.</p>
                <a href="?page=kegiatan" class="btn btn-primary">+ Buat Kegiatan Pertama</a>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Kegiatan</th>
                            <th>Kota Tujuan</th>
                            <th style="text-align: right;">Status SPD</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recentKeg as $k): ?>
                        <tr class="clickable" onclick="window.location='?page=spd_list&id_kegiatan=<?= $k['id'] ?>'">
                            <td><strong><?= h($k['nama_kegiatan']) ?></strong></td>
                            <td><?= h($k['kota_tujuan'] ?: '-') ?></td>
                            <td style="text-align: right; white-space: nowrap;">
                                <?php if ($isPic && !empty($k['submitted_spd'])): ?>
                                    <span class="badge badge-submitted badge-submitted-pulse" title="Perlu verifikasi">
                                        <span class="pulse-dot"></span> <?= $k['submitted_spd'] ?> Submitted
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-draft"><?= $k['jumlah_spd'] ?> SPD</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Recent SPD -->
    <div class="card">
        <div class="card-header">
            <div style="display:flex; align-items:center; gap:0.5rem;">
                <h2><?= $isPic ? 'Antrean & Status SPD' : 'SPD Terakhir Diperbarui' ?></h2>
                <?php if ($isPic && $submittedCount > 0): ?>
                    <span class="badge badge-submitted badge-submitted-pulse" style="font-size:0.68rem;">
                        <span class="pulse-dot"></span> <?= $submittedCount ?> Submitted
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <?php if (empty($recentSpd)): ?>
            <div class="empty-state" style="padding: 4rem 1rem; text-align: center;">
                <div style="font-size: 3.5rem; color: var(--border); margin-bottom: 1rem;">📄</div>
                <h3 style="color: var(--text-primary); margin-bottom: 0.5rem; font-size: 1.1rem;">Belum Ada SPD</h3>
                <p style="color: var(--text-muted); font-size: 0.95rem;">Surat Perjalanan Dinas yang Anda buat akan muncul di sini.</p>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Kegiatan</th>
                            <th style="text-align: center;">Status</th>
                            <?php if ($isPic): ?><th style="text-align: center;">Aksi</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recentSpd as $s): 
                        $isSubmitted = ($s['status'] === 'submitted');
                    ?>
                        <tr class="clickable <?= $isSubmitted ? 'tr-submitted-highlight' : '' ?>" onclick="window.location='?page=spd_detail&id=<?= $s['id'] ?>'">
                            <td>
                                <div style="display:flex; align-items:center; gap:0.4rem;">
                                    <?php if ($isPic && $isSubmitted): ?>
                                        <span style="display:inline-block; width:6px; height:6px; border-radius:50%; background:var(--primary-600);" title="Perlu Verifikasi"></span>
                                    <?php endif; ?>
                                    <strong><?= h($s['nama']) ?></strong>
                                </div>
                            </td>
                            <td style="font-size:0.78rem;color:var(--text-muted)"><?= h($s['nama_kegiatan'] ?? '-') ?></td>
                            <td style="text-align: center;">
                                <?php if ($isSubmitted): ?>
                                    <span class="badge badge-submitted badge-submitted-pulse">
                                        <span class="pulse-dot"></span> SUBMITTED
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-<?= h($s['status']) ?>"><?= h(ucfirst($s['status'])) ?></span>
                                <?php endif; ?>
                            </td>
                            <?php if ($isPic): ?>
                            <td style="text-align: center; white-space: nowrap;">
                                <?php if ($isSubmitted): ?>
                                    <a href="?page=spd_detail&id=<?= $s['id'] ?>" class="btn btn-sm btn-primary" style="font-size:0.75rem; padding:0.25rem 0.6rem; border-radius: 999px; text-decoration: none;">
                                        Review ➔
                                    </a>
                                <?php else: ?>
                                    <a href="?page=spd_detail&id=<?= $s['id'] ?>" class="btn btn-sm btn-secondary" style="font-size:0.75rem; padding:0.25rem 0.6rem; border-radius: 999px; text-decoration: none;">
                                        Detail
                                    </a>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

