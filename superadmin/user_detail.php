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

$userId = filter_var(
    $_GET['id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);
$user = $userId === false
    ? null
    : (new SuperadminUserRepository($pdo))->findDetail((int) $userId);
if ($user === null) {
    http_response_code(404);
    exit('Pengguna tidak ditemukan.');
}

renderSuperadminHeader('Detail Pengguna', 'users');
?>
<div class="mb-3">
    <a href="users.php" class="btn btn-sm btn-outline-secondary">← Kembali ke daftar</a>
</div>
<div class="row g-4">
    <section class="col-lg-7" aria-labelledby="identity-title">
        <div class="master-card p-3 p-lg-4">
            <p class="eyebrow mb-1">Identitas akun</p>
            <h2 id="identity-title" class="h4 mb-4"><?= escape_output($user['nama']) ?></h2>
            <dl class="detail-list">
                <dt>Username</dt>
                <dd>@<?= escape_output($user['username']) ?></dd>
                <dt>Role</dt>
                <dd><?= escape_output(ucfirst($user['role'])) ?></dd>
                <dt>Status</dt>
                <dd><span class="status-pill status-neutral"><?= escape_output($user['status']) ?></span></dd>
                <dt>Kelas</dt>
                <dd><?= escape_output($user['kelas'] ?: 'Tidak berlaku') ?></dd>
                <dt>Terdaftar</dt>
                <dd><?= escape_output(date('d M Y, H:i', strtotime($user['created_at']))) ?></dd>
            </dl>
        </div>
    </section>
    <aside class="col-lg-5" aria-labelledby="records-title">
        <div class="master-card p-3 p-lg-4">
            <p class="eyebrow mb-1">Jejak data</p>
            <h2 id="records-title" class="h5 mb-3">Ringkasan catatan</h2>
            <dl class="detail-list">
                <dt>Kuesioner</dt>
                <dd><?= $user['record_counts']['questionnaires'] ?></dd>
                <dt>Hasil skrining</dt>
                <dd><?= $user['record_counts']['risk_results'] ?></dd>
                <dt>Konsumsi TTD</dt>
                <dd><?= $user['record_counts']['ttd_records'] ?></dd>
                <dt>Konsultasi</dt>
                <dd><?= $user['record_counts']['consultations'] ?></dd>
            </dl>
            <p class="small text-muted mt-4 mb-0">
                Halaman ini tidak menampilkan atau menyediakan perubahan credential.
            </p>
        </div>
    </aside>
</div>
<?php renderSuperadminFooter(); ?>
