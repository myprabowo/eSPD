<?php
// Dashboard — Overview statistics
$uKeg = current_role() === 'Admin Super' ? "" : " WHERE created_by = '" . current_username() . "'";
$uSpd = current_role() === 'Admin Super' ? "" : " WHERE created_by = '" . current_username() . "'";
$uSpdAnd = current_role() === 'Admin Super' ? "" : " AND created_by = '" . current_username() . "'";
$uSpdAndS = current_role() === 'Admin Super' ? "" : " WHERE s.created_by = '" . current_username() . "'";

$kegiatanCount = db_query("SELECT COUNT(*) as cnt FROM kegiatan $uKeg")[0]['cnt'] ?? 0;
$spdCount      = db_query("SELECT COUNT(*) as cnt FROM spd $uSpd")[0]['cnt'] ?? 0;
$draftCount    = db_query("SELECT COUNT(*) as cnt FROM spd WHERE status='draft' $uSpdAnd")[0]['cnt'] ?? 0;
$paidCount     = db_query("SELECT COUNT(*) as cnt FROM spd WHERE status='paid' $uSpdAnd")[0]['cnt'] ?? 0;
$recentSpd     = db_query("SELECT s.*, k.nama_kegiatan FROM spd s LEFT JOIN kegiatan k ON s.id_kegiatan = k.id $uSpdAndS ORDER BY s.updated_at DESC LIMIT 10");
$recentKeg     = db_query("SELECT k.*, (SELECT COUNT(*) FROM spd WHERE id_kegiatan = k.id) as jumlah_spd FROM kegiatan k $uKeg ORDER BY k.created_at DESC LIMIT 5");
?>

<div class="page-header">
    <div>
        <h1>Dashboard</h1>
        <p class="subtitle">Selamat datang, <?= h(current_user()) ?></p>
    </div>
</div>

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
    <div class="stat-card">
        <div class="stat-icon orange">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div class="stat-info">
            <h3><?= $draftCount ?></h3>
            <p>SPD Draft</p>
        </div>
    </div>
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
            <div class="empty-state">
                <p>Belum ada kegiatan</p>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>Kegiatan</th><th>Kota Tujuan</th><th>SPD</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentKeg as $k): ?>
                        <tr class="clickable" onclick="window.location='?page=spd_list&id_kegiatan=<?= $k['id'] ?>'">
                            <td><strong><?= h($k['nama_kegiatan']) ?></strong></td>
                            <td><?= h($k['kota_tujuan'] ?: '-') ?></td>
                            <td><span class="badge badge-submitted"><?= $k['jumlah_spd'] ?></span></td>
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
            <h2>SPD Terakhir Diperbarui</h2>
        </div>
        <?php if (empty($recentSpd)): ?>
            <div class="empty-state">
                <p>Belum ada SPD</p>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>Nama</th><th>Kegiatan</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentSpd as $s): ?>
                        <tr class="clickable" onclick="window.location='?page=spd_detail&id=<?= $s['id'] ?>'">
                            <td><strong><?= h($s['nama']) ?></strong></td>
                            <td style="font-size:0.78rem;color:var(--text-muted)"><?= h($s['nama_kegiatan'] ?? '-') ?></td>
                            <td><span class="badge badge-<?= h($s['status']) ?>"><?= h(ucfirst($s['status'])) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
