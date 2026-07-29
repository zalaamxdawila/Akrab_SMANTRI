<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/helpers.php';
require_once dirname(__DIR__) . '/app/Security/SuperadminGuard.php';
require_once dirname(__DIR__) . '/app/Repositories/SuperadminReportRepository.php';
require_once dirname(__DIR__) . '/views/superadmin/layout.php';

try {
    SuperadminGuard::authorize($pdo, $_SESSION);
    $role = normalizeText($_GET['role'] ?? '', 20);
    $page = max(1, filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1);
    $result = (new SuperadminReportRepository($pdo))->paginate($role, $page, 25);
} catch (InvalidArgumentException) {
    http_response_code(400);
    exit('Filter tidak valid.');
} catch (Throwable) {
    http_response_code(403);
    exit('Akses ditolak.');
}

renderSuperadminHeader('Laporan Sistem', 'reports');
?>
<section class="master-card filter-panel mb-4">
    <form method="get" class="row g-3 align-items-end">
        <div class="col-md-5">
            <label class="form-label" for="role">Role</label>
            <select class="form-select" id="role" name="role">
                <option value="">Semua role</option>
                <?php foreach (['siswa', 'uks', 'orangtua', 'superadmin'] as $option): ?>
                    <option value="<?= $option ?>" <?= $role === $option ? 'selected' : '' ?>>
                        <?= escape_output(ucfirst($option)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-7 d-flex gap-2">
            <button class="btn btn-primary" type="submit">Terapkan</button>
            <a class="btn btn-outline-secondary" href="reports.php">Reset</a>
        </div>
    </form>
</section>
<section class="master-card p-3 p-lg-4 mb-4">
    <h2 class="h5">Ringkasan akun nonarsip</h2>
    <div class="table-responsive">
        <table class="table master-table">
            <thead><tr><th>Role</th><th>Status</th><th>Jumlah</th></tr></thead>
            <tbody>
            <?php foreach ($result['items'] as $item): ?>
                <tr><td><?= escape_output($item['role']) ?></td>
                    <td><?= escape_output($item['status']) ?></td>
                    <td><?= (int) $item['total'] ?></td></tr>
            <?php endforeach; ?>
            <?php if ($result['items'] === []): ?>
                <tr><td colspan="3" class="empty-state">Belum ada data.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<section class="master-card p-3 p-lg-4" aria-labelledby="export-title">
    <h2 id="export-title" class="h5">Ekspor akun nonarsip</h2>
    <p class="text-muted">Ekspor tidak memuat password atau data kesehatan.</p>
    <form method="post" action="export.php" class="row g-3">
        <?= csrfInput() ?>
        <input type="hidden" name="role" value="<?= escape_output($role) ?>">
        <div class="col-md-5">
            <label class="form-label" for="reason">Alasan</label>
            <select class="form-select" id="reason" name="reason" required>
                <option value="operational_review">Tinjauan operasional</option>
                <option value="compliance_review">Tinjauan kepatuhan</option>
                <option value="authorized_backup">Cadangan berizin</option>
            </select>
        </div>
        <div class="col-md-5">
            <label class="form-label" for="confirmation">Ketik EXPORT AKRAB</label>
            <input class="form-control" id="confirmation" name="confirmation"
                   required autocomplete="off">
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button class="btn btn-danger w-100" type="submit">Ekspor CSV</button>
        </div>
    </form>
</section>
<?php renderSuperadminFooter(); ?>
