<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/helpers.php';
require_once dirname(__DIR__) . '/app/Security/SuperadminGuard.php';
require_once dirname(__DIR__)
    . '/app/Repositories/SuperadminAuditRepository.php';
require_once dirname(__DIR__) . '/views/superadmin/layout.php';

try {
    SuperadminGuard::authorize($pdo, $_SESSION);
} catch (Throwable) {
    http_response_code(403);
    exit('Akses ditolak.');
}

try {
    $filters = [
        'authenticated_actor_id' => $_GET['authenticated_actor_id'] ?? '',
        'effective_actor_id' => $_GET['effective_actor_id'] ?? '',
        'action' => normalizeText($_GET['action'] ?? '', 80),
        'outcome' => normalizeText($_GET['outcome'] ?? '', 20),
        'request_id' => normalizeText($_GET['request_id'] ?? '', 64),
        'date_from' => normalizeText($_GET['date_from'] ?? '', 10),
        'date_to' => normalizeText($_GET['date_to'] ?? '', 10),
    ];
    $page = max(
        1,
        filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1
    );
    $result = (new SuperadminAuditRepository($pdo))->paginate(
        $filters,
        $page,
        25
    );
} catch (InvalidArgumentException) {
    http_response_code(400);
    exit('Filter audit tidak valid.');
}
$baseQuery = array_filter(
    $filters,
    static fn (mixed $value): bool => $value !== '' && $value !== null
);

renderSuperadminHeader('Audit Trail', 'audit');
?>
<section class="master-card filter-panel mb-4" aria-labelledby="audit-filter-title">
    <h2 id="audit-filter-title" class="visually-hidden">Filter audit</h2>
    <form method="get" action="audit.php" class="row g-3 align-items-end">
        <div class="col-sm-6 col-xl-2">
            <label class="form-label" for="authenticated_actor_id">Actor asli</label>
            <input class="form-control" id="authenticated_actor_id"
                   name="authenticated_actor_id" inputmode="numeric"
                   value="<?= escape_output($filters['authenticated_actor_id']) ?>">
        </div>
        <div class="col-sm-6 col-xl-2">
            <label class="form-label" for="effective_actor_id">Actor efektif</label>
            <input class="form-control" id="effective_actor_id"
                   name="effective_actor_id" inputmode="numeric"
                   value="<?= escape_output($filters['effective_actor_id']) ?>">
        </div>
        <div class="col-sm-6 col-xl-2">
            <label class="form-label" for="action">Action</label>
            <input class="form-control" id="action" name="action" maxlength="80"
                   value="<?= escape_output($filters['action']) ?>">
        </div>
        <div class="col-sm-6 col-xl-2">
            <label class="form-label" for="outcome">Outcome</label>
            <select class="form-select" id="outcome" name="outcome">
                <option value="">Semua</option>
                <?php foreach (['started', 'success', 'failed', 'forbidden'] as $option): ?>
                    <option value="<?= $option ?>" <?= $filters['outcome'] === $option ? 'selected' : '' ?>>
                        <?= escape_output(ucfirst($option)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-6 col-xl-2">
            <label class="form-label" for="date_from">Dari tanggal</label>
            <input class="form-control" id="date_from" name="date_from"
                   type="date" value="<?= escape_output($filters['date_from']) ?>">
        </div>
        <div class="col-sm-6 col-xl-2">
            <label class="form-label" for="date_to">Sampai tanggal</label>
            <input class="form-control" id="date_to" name="date_to"
                   type="date" value="<?= escape_output($filters['date_to']) ?>">
        </div>
        <div class="col-xl-8">
            <label class="form-label" for="request_id">Request ID</label>
            <input class="form-control" id="request_id" name="request_id"
                   maxlength="64" value="<?= escape_output($filters['request_id']) ?>">
        </div>
        <div class="col-xl-4 d-flex gap-2">
            <button class="btn btn-primary" type="submit">Terapkan filter</button>
            <a class="btn btn-outline-secondary" href="audit.php">Reset</a>
        </div>
    </form>
</section>

<section class="master-card p-3 p-lg-4" aria-labelledby="audit-list-title">
    <div class="d-flex justify-content-between gap-2 align-items-center mb-3">
        <h2 id="audit-list-title" class="h5 mb-0">Peristiwa tercatat</h2>
        <span class="text-muted small"><?= $result['total'] ?> hasil</span>
    </div>
    <div class="table-responsive">
        <table class="table master-table">
            <thead>
            <tr>
                <th scope="col">Waktu</th>
                <th scope="col">Actor asli</th>
                <th scope="col">Actor efektif</th>
                <th scope="col">Action</th>
                <th scope="col">Outcome</th>
                <th scope="col">Request</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($result['items'] === []): ?>
                <tr><td colspan="6" class="empty-state">Belum ada audit yang cocok.</td></tr>
            <?php endif; ?>
            <?php foreach ($result['items'] as $event): ?>
                <tr>
                    <td class="text-nowrap"><?= escape_output(date('d M Y H:i', strtotime($event['created_at']))) ?></td>
                    <td><?= escape_output($event['authenticated_name'] ?: 'Sistem') ?></td>
                    <td><?= escape_output($event['effective_name'] ?: '—') ?></td>
                    <td>
                        <code><?= escape_output($event['action']) ?></code>
                        <small class="d-block text-muted"><?= escape_output($event['route'] ?: $event['target_type']) ?></small>
                    </td>
                    <td><span class="status-pill status-neutral"><?= escape_output($event['outcome'] ?: '—') ?></span></td>
                    <td><code><?= escape_output($event['request_id'] ?: '—') ?></code></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if ($result['pages'] > 1): ?>
        <nav class="mt-3" aria-label="Halaman audit">
            <ul class="pagination justify-content-center mb-0">
                <?php foreach ([
                    'Sebelumnya' => max(1, $result['page'] - 1),
                    'Berikutnya' => min($result['pages'], $result['page'] + 1),
                ] as $label => $targetPage): ?>
                    <?php $disabled = ($label === 'Sebelumnya' && $result['page'] === 1)
                        || ($label === 'Berikutnya' && $result['page'] === $result['pages']); ?>
                    <li class="page-item <?= $disabled ? 'disabled' : '' ?>">
                        <?php if ($disabled): ?>
                            <span class="page-link" aria-disabled="true"><?= $label ?></span>
                        <?php else: ?>
                            <a class="page-link"
                               href="?<?= escape_output(http_build_query($baseQuery + ['page' => $targetPage])) ?>">
                                <?= $label ?>
                            </a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
    <?php endif; ?>
</section>
<?php renderSuperadminFooter(); ?>
