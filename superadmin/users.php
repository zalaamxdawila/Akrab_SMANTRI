<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/helpers.php';
require_once dirname(__DIR__) . '/app/Security/SuperadminGuard.php';
require_once dirname(__DIR__)
    . '/app/Repositories/SuperadminUserRepository.php';
require_once dirname(__DIR__) . '/views/superadmin/layout.php';

try {
    SuperadminGuard::authorize($pdo, $_SESSION);
} catch (Throwable) {
    http_response_code(403);
    exit('Akses ditolak.');
}

try {
    $search = normalizeText($_GET['search'] ?? '', 100);
    $role = normalizeText($_GET['role'] ?? '', 20);
    $status = normalizeText($_GET['status'] ?? '', 20);
    $page = max(
        1,
        filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1
    );
    $result = (new SuperadminUserRepository($pdo))->paginate(
        $search,
        $role,
        $status,
        $page,
        25
    );
} catch (InvalidArgumentException) {
    http_response_code(400);
    exit('Filter tidak valid.');
}

$baseQuery = array_filter(
    ['search' => $search, 'role' => $role, 'status' => $status],
    static fn (string $value): bool => $value !== ''
);
renderSuperadminHeader('Direktori Pengguna', 'users');
?>
<section class="master-card filter-panel mb-4" aria-labelledby="filter-title">
    <h2 id="filter-title" class="visually-hidden">Filter pengguna</h2>
    <form method="get" action="users.php" class="row g-3 align-items-end">
        <div class="col-lg-5">
            <label for="search" class="form-label">Cari nama atau username</label>
            <input id="search" name="search" type="search" class="form-control"
                   maxlength="100" value="<?= escape_output($search) ?>"
                   placeholder="Contoh: Siti atau nisn123">
        </div>
        <div class="col-sm-6 col-lg-2">
            <label for="role" class="form-label">Role</label>
            <select id="role" name="role" class="form-select">
                <option value="">Semua role</option>
                <?php foreach (['siswa', 'uks', 'orangtua', 'superadmin'] as $option): ?>
                    <option value="<?= $option ?>" <?= $role === $option ? 'selected' : '' ?>>
                        <?= escape_output(ucfirst($option)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-6 col-lg-2">
            <label for="status" class="form-label">Status</label>
            <select id="status" name="status" class="form-select">
                <option value="">Semua status</option>
                <?php foreach (['active', 'inactive', 'archived'] as $option): ?>
                    <option value="<?= $option ?>" <?= $status === $option ? 'selected' : '' ?>>
                        <?= escape_output(ucfirst($option)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-lg-3 d-flex gap-2">
            <button class="btn btn-primary" type="submit">Terapkan</button>
            <a class="btn btn-outline-secondary" href="users.php">Reset</a>
        </div>
    </form>
</section>

<section class="master-card p-3 p-lg-4" aria-labelledby="user-list-title">
    <div class="d-flex flex-wrap justify-content-between gap-2 align-items-center mb-3">
        <h2 id="user-list-title" class="h5 mb-0">Pengguna terdaftar</h2>
        <div class="d-flex gap-2 align-items-center">
            <span class="text-muted small"><?= $result['total'] ?> hasil</span>
            <a class="btn btn-sm btn-primary" href="user_create.php">Tambah pengguna</a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table master-table">
            <thead>
            <tr>
                <th scope="col">Nama</th>
                <th scope="col">Role</th>
                <th scope="col">Status</th>
                <th scope="col">Kelas</th>
                <th scope="col">Terdaftar</th>
                <th scope="col"><span class="visually-hidden">Aksi</span></th>
            </tr>
            </thead>
            <tbody>
            <?php if ($result['items'] === []): ?>
                <tr><td colspan="6" class="empty-state">Tidak ada pengguna yang cocok.</td></tr>
            <?php endif; ?>
            <?php foreach ($result['items'] as $user): ?>
                <tr>
                    <th scope="row">
                        <?= escape_output($user['nama']) ?>
                        <small class="d-block text-muted">@<?= escape_output($user['username']) ?></small>
                    </th>
                    <td><?= escape_output(ucfirst($user['role'])) ?></td>
                    <td><span class="status-pill status-neutral"><?= escape_output($user['status']) ?></span></td>
                    <td><?= escape_output($user['kelas'] ?: '—') ?></td>
                    <td><?= escape_output(date('d M Y', strtotime($user['created_at']))) ?></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-primary"
                           href="user_detail.php?id=<?= (int) $user['id'] ?>">Detail</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if ($result['pages'] > 1): ?>
        <nav class="mt-3" aria-label="Halaman pengguna">
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
