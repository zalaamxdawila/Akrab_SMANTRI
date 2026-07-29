<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/helpers.php';
require_once dirname(__DIR__) . '/app/Security/SuperadminGuard.php';
require_once dirname(__DIR__)
    . '/app/Repositories/SuperadminOverviewRepository.php';
require_once dirname(__DIR__) . '/views/superadmin/layout.php';

try {
    SuperadminGuard::authorize($pdo, $_SESSION);
} catch (Throwable) {
    http_response_code(403);
    exit('Akses ditolak.');
}

$summary = (new SuperadminOverviewRepository($pdo))->summary(date('Y-m-d'));
$activeUsers = array_sum(array_column($summary['accounts'], 'active'));
$clinicalEnabled = clinicalApprovalGatePassed();

renderSuperadminHeader('Ringkasan Sistem', 'dashboard');
?>
<section aria-labelledby="quick-summary-title">
    <h2 id="quick-summary-title" class="visually-hidden">Ringkasan cepat</h2>
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <article class="master-card metric-card">
                <span class="metric-label">Pengguna aktif</span>
                <div class="metric-value"><?= $activeUsers ?></div>
                <span class="status-pill status-safe">Akun operasional</span>
            </article>
        </div>
        <div class="col-6 col-xl-3">
            <article class="master-card metric-card">
                <span class="metric-label">Tautan menunggu</span>
                <div class="metric-value"><?= $summary['parent_links']['pending'] ?></div>
                <span class="status-pill status-warn">Perlu ditinjau</span>
            </article>
        </div>
        <div class="col-6 col-xl-3">
            <article class="master-card metric-card">
                <span class="metric-label">Konsultasi menunggu</span>
                <div class="metric-value"><?= $summary['operations']['consultations_pending'] ?></div>
                <span class="status-pill status-neutral">Antrean layanan</span>
            </article>
        </div>
        <div class="col-6 col-xl-3">
            <article class="master-card metric-card">
                <span class="metric-label">TTD dikonfirmasi hari ini</span>
                <div class="metric-value"><?= $summary['operations']['ttd_confirmed_today'] ?></div>
                <span class="status-pill status-safe">Catatan harian</span>
            </article>
        </div>
    </div>
</section>

<div class="row g-4">
    <section class="col-xl-8" aria-labelledby="account-title">
        <div class="master-card p-3 p-lg-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <p class="eyebrow mb-1">Identitas</p>
                    <h2 id="account-title" class="h5 mb-0">Status akun per role</h2>
                </div>
                <a href="users.php" class="btn btn-sm btn-outline-primary">Lihat pengguna</a>
            </div>
            <div class="table-responsive">
                <table class="table master-table">
                    <thead>
                    <tr>
                        <th scope="col">Role</th>
                        <th scope="col">Aktif</th>
                        <th scope="col">Nonaktif</th>
                        <th scope="col">Diarsipkan</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($summary['accounts'] as $role => $counts): ?>
                        <tr>
                            <th scope="row"><?= escape_output(ucfirst($role)) ?></th>
                            <td><?= $counts['active'] ?></td>
                            <td><?= $counts['inactive'] ?></td>
                            <td><?= $counts['archived'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <aside class="col-xl-4" aria-labelledby="system-title">
        <div class="master-card p-3 p-lg-4">
            <p class="eyebrow mb-1">Governance</p>
            <h2 id="system-title" class="h5 mb-3">Status sistem</h2>
            <dl class="detail-list">
                <dt>Clinical gate</dt>
                <dd>
                    <span class="status-pill <?= $clinicalEnabled ? 'status-warn' : 'status-safe' ?>">
                        <?= $clinicalEnabled ? 'Aktif' : 'OFF · terkunci' ?>
                    </span>
                </dd>
                <dt>Migration</dt>
                <dd><code><?= escape_output($summary['migration_version'] ?? 'Belum tersedia') ?></code></dd>
                <dt>Kuesioner</dt>
                <dd><?= $summary['health']['questionnaires'] ?> catatan</dd>
                <dt>Pemeriksaan Hb</dt>
                <dd><?= $summary['health']['hb_records'] ?> catatan</dd>
                <dt>Artikel</dt>
                <dd><?= $summary['operations']['articles'] ?> artikel</dd>
            </dl>
            <a href="audit.php" class="btn btn-sm btn-outline-primary mt-4">Buka audit trail</a>
        </div>
    </aside>
</div>
<?php renderSuperadminFooter(); ?>
