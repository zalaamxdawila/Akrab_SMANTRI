<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/helpers.php';
require_once dirname(__DIR__) . '/app/Security/SuperadminGuard.php';
require_once dirname(__DIR__) . '/app/Repositories/SuperadminHealthRepository.php';
require_once dirname(__DIR__) . '/views/superadmin/layout.php';

try {
    SuperadminGuard::authorize($pdo, $_SESSION);
    $type = normalizeText($_GET['type'] ?? 'questionnaire', 20);
    $search = normalizeText($_GET['search'] ?? '', 100);
    $archived = ($_GET['archived'] ?? '') === '1';
    $page = max(1, filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1);
    $result = (new SuperadminHealthRepository($pdo))
        ->paginate($type, $search, $archived, $page, 25);
} catch (InvalidArgumentException) {
    http_response_code(400);
    exit('Filter tidak valid.');
} catch (Throwable) {
    http_response_code(403);
    exit('Akses ditolak.');
}
$types = [
    'questionnaire' => 'Kuesioner',
    'risk' => 'Hasil risiko',
    'hb' => 'Pemeriksaan Hb',
    'ttd' => 'Konsumsi TTD',
    'menstruation' => 'Menstruasi',
];
$actionRoute = match ($type) {
    'questionnaire', 'risk' => 'health_questionnaire_action.php',
    'hb', 'ttd' => 'health_hb_ttd_action.php',
    'menstruation' => 'health_menstruation_action.php',
};
renderSuperadminHeader('Master Data Kesehatan', 'health');
?>
<div class="alert alert-info">
    Tampilan minimum untuk tata kelola. Alamat, credential, dan nilai sebelum/sesudah
    koreksi tidak ditampilkan pada audit.
</div>
<section class="master-card p-3 p-lg-4 mb-4">
<form method="get" class="row g-3 align-items-end">
<div class="col-md-3"><label for="type" class="form-label">Jenis catatan</label>
<select id="type" name="type" class="form-select">
<?php foreach ($types as $key => $label): ?><option value="<?= $key ?>" <?= $type === $key ? 'selected' : '' ?>><?= escape_output($label) ?></option><?php endforeach; ?>
</select></div>
<div class="col-md-5"><label for="search" class="form-label">Cari siswa</label>
<input id="search" name="search" class="form-control" maxlength="100" value="<?= escape_output($search) ?>"></div>
<div class="col-md-2 form-check"><input id="archived" name="archived" value="1" type="checkbox" class="form-check-input" <?= $archived ? 'checked' : '' ?>>
<label for="archived" class="form-check-label">Arsip saja</label></div>
<div class="col-md-2"><button class="btn btn-primary" type="submit">Terapkan</button></div>
</form></section>
<section class="master-card p-3 p-lg-4">
<div class="table-responsive"><table class="table master-table"><thead><tr>
<th>Siswa</th><th>Tanggal</th><th>Ringkasan</th><th>Status</th><th>Aksi</th>
</tr></thead><tbody>
<?php foreach ($result['items'] as $record): ?><tr>
<td><?= escape_output($record['student_name']) ?><small class="d-block text-muted">@<?= escape_output($record['student_username']) ?></small></td>
<td><?= escape_output($record['record_date']) ?></td>
<td><?= escape_output($record['summary']) ?></td>
<td><?= $record['archived_at'] ? 'Arsip' : ($record['corrected_at'] ? 'Dikoreksi' : 'Aktif') ?></td>
<td>
<?php if (!$record['archived_at']): ?>
<form method="post" action="<?= escape_output($actionRoute) ?>" class="d-flex flex-wrap gap-1">
<?= csrfInput() ?><input type="hidden" name="record_id" value="<?= (int) $record['id'] ?>">
<input type="hidden" name="record_type" value="<?= escape_output($type) ?>">
<select name="reason" class="form-select form-select-sm" aria-label="Alasan" required>
<option value="correction">Koreksi</option><option value="verification">Verifikasi</option>
<option value="data_governance">Tata kelola</option></select>
<?php if ($type === 'hb'): ?>
<input name="nilai_hb" type="number" min="0" max="30" step="0.1" class="form-control form-control-sm" placeholder="Hb" aria-label="Nilai Hb">
<select name="kategori_anemia" class="form-select form-select-sm" aria-label="Kategori anemia"><option value="tidak_anemia">Tidak anemia</option><option value="ringan">Ringan</option><option value="sedang">Sedang</option><option value="berat">Berat</option></select>
<input name="tanggal_periksa" type="date" class="form-control form-control-sm" aria-label="Tanggal periksa">
<?php elseif ($type === 'ttd'): ?>
<input name="tanggal" type="date" class="form-control form-control-sm" aria-label="Tanggal konsumsi">
<select name="status_konsumsi" class="form-select form-select-sm" aria-label="Status konsumsi"><option value="sudah">Sudah</option><option value="belum">Belum</option></select>
<?php elseif ($type === 'risk'): ?>
<input name="probabilitas_risiko" type="number" min="0" max="1" step="0.0001" class="form-control form-control-sm" placeholder="Probabilitas" aria-label="Probabilitas risiko">
<select name="kategori_risiko" class="form-select form-select-sm" aria-label="Kategori risiko"><option value="rendah">Rendah</option><option value="sedang">Sedang</option><option value="tinggi">Tinggi</option></select>
<input name="tanggal" type="date" class="form-control form-control-sm" aria-label="Tanggal hasil">
<?php elseif ($type === 'menstruation'): ?>
<input name="tanggal_mulai" type="date" class="form-control form-control-sm" aria-label="Tanggal mulai">
<input name="tanggal_selesai" type="date" class="form-control form-control-sm" aria-label="Tanggal selesai">
<?php else: ?>
<input name="kadar_hb" type="number" min="0" max="30" step="0.1" class="form-control form-control-sm" placeholder="Hb baru" aria-label="Hb baru">
<?php endif; ?>
<button class="btn btn-sm btn-outline-primary" name="action" value="correct">Koreksi</button>
<button class="btn btn-sm btn-warning" name="action" value="archive">Arsipkan</button>
</form>
<?php else: ?><span class="text-muted">Historis</span><?php endif; ?>
</td></tr><?php endforeach; ?>
<?php if (!$result['items']): ?><tr><td colspan="5" class="empty-state">Tidak ada catatan.</td></tr><?php endif; ?>
</tbody></table></div>
<?php if ($result['pages'] > 1): ?>
<?php $query = ['type' => $type, 'search' => $search] + ($archived ? ['archived' => '1'] : []); ?>
<nav aria-label="Halaman data kesehatan"><ul class="pagination justify-content-center mb-0">
<li class="page-item <?= $result['page'] <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="?<?= escape_output(http_build_query($query + ['page' => max(1, $result['page'] - 1)])) ?>">Sebelumnya</a></li>
<li class="page-item disabled"><span class="page-link">Halaman <?= $result['page'] ?> dari <?= $result['pages'] ?></span></li>
<li class="page-item <?= $result['page'] >= $result['pages'] ? 'disabled' : '' ?>"><a class="page-link" href="?<?= escape_output(http_build_query($query + ['page' => min($result['pages'], $result['page'] + 1)])) ?>">Berikutnya</a></li>
</ul></nav>
<?php endif; ?>
</section>
<?php renderSuperadminFooter(); ?>
