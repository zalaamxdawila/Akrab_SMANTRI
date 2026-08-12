<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/helpers.php';
require_once dirname(__DIR__) . '/app/Security/SuperadminGuard.php';
require_once dirname(__DIR__) . '/app/Repositories/SuperadminParentLinkRepository.php';
require_once dirname(__DIR__) . '/views/superadmin/layout.php';

try {
    SuperadminGuard::authorize($pdo, $_SESSION);
    $search = normalizeText($_GET['search'] ?? '', 100);
    $status = normalizeText($_GET['status'] ?? '', 20);
    $archived = ($_GET['archived'] ?? '') === '1';
    $page = max(1, filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1);
    $result = (new SuperadminParentLinkRepository($pdo))
        ->paginate($search, $status, $archived, $page, 25);
} catch (InvalidArgumentException) {
    http_response_code(400);
    exit('Filter tidak valid.');
} catch (Throwable) {
    http_response_code(403);
    exit('Akses ditolak.');
}
renderSuperadminHeader('Relasi Orang Tua–Siswa', 'links');
?>
<section class="master-card p-3 p-lg-4 mb-4">
<form method="get" class="row g-3 align-items-end">
    <div class="col-md-5"><label for="search" class="form-label">Cari orang tua atau siswa</label>
        <input id="search" name="search" class="form-control" maxlength="100" value="<?= escape_output($search) ?>"></div>
    <div class="col-md-3"><label for="status" class="form-label">Status</label>
        <select id="status" name="status" class="form-select"><option value="">Semua</option>
        <?php foreach (['pending', 'approved', 'rejected'] as $option): ?>
            <option value="<?= $option ?>" <?= $status === $option ? 'selected' : '' ?>><?= ucfirst($option) ?></option>
        <?php endforeach; ?></select></div>
    <div class="col-md-2 form-check"><input id="archived" name="archived" value="1" type="checkbox" class="form-check-input" <?= $archived ? 'checked' : '' ?>>
        <label for="archived" class="form-check-label">Tampilkan arsip</label></div>
    <div class="col-md-2"><button class="btn btn-primary" type="submit">Terapkan</button></div>
</form></section>
<section class="master-card p-3 p-lg-4">
<div class="table-responsive"><table class="table master-table"><thead><tr>
<th>Orang tua</th><th>Siswa diminta</th><th>Siswa terhubung</th><th>Status</th><th>Aksi</th>
</tr></thead><tbody>
<?php foreach ($result['items'] as $link): ?><tr>
<td><?= escape_output($link['parent_name']) ?><small class="d-block text-muted">@<?= escape_output($link['parent_username']) ?></small></td>
<td><?= escape_output($link['requested_student_username']) ?></td>
<td><?= escape_output($link['student_name'] ?: '—') ?></td>
<td><?= escape_output($link['archived_at'] ? 'archived' : $link['status']) ?></td>
<td><form method="post" action="parent_link_action.php" class="d-flex flex-wrap gap-1">
<?= csrfInput() ?><input type="hidden" name="link_id" value="<?= (int) $link['id'] ?>">
<input class="form-control form-control-sm" name="student_username" maxlength="50" value="<?= escape_output($link['student_username'] ?: $link['requested_student_username']) ?>" aria-label="Username siswa">
<select class="form-select form-select-sm" name="reason" aria-label="Alasan perubahan" required>
<option value="verification">Verifikasi</option><option value="correction">Koreksi</option>
<option value="data_governance">Tata kelola</option><option value="support">Dukungan</option>
</select>
<?php if ($link['archived_at']): ?><button class="btn btn-sm btn-outline-primary" name="action" value="restore">Restore</button>
<?php else: ?><button class="btn btn-sm btn-success" name="action" value="approve">Setujui</button>
<button class="btn btn-sm btn-outline-danger" name="action" value="reject">Tolak</button>
<button class="btn btn-sm btn-outline-secondary" name="action" value="correct">Koreksi</button>
<button class="btn btn-sm btn-warning" name="action" value="archive">Arsip</button><?php endif; ?>
</form></td></tr><?php endforeach; ?>
<?php if (!$result['items']): ?><tr><td colspan="5" class="empty-state">Tidak ada relasi.</td></tr><?php endif; ?>
</tbody></table></div></section>
<?php renderSuperadminFooter(); ?>
