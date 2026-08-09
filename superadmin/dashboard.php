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
$generatedResetLink = $_SESSION['_generated_password_reset_link'] ?? null;
unset($_SESSION['_generated_password_reset_link']);
if (
    !is_array($generatedResetLink)
    || !isset($generatedResetLink['request_id'], $generatedResetLink['url'])
    || !is_int($generatedResetLink['request_id'])
    || !is_string($generatedResetLink['url'])
) {
    $generatedResetLink = null;
}

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

    <?php
    $stmtReset = $pdo->query("SELECT p.id, p.created_at, p.token_hash IS NOT NULL AS has_token, p.expires_at, u.nama, u.username, u.role FROM password_reset_requests p JOIN users u ON p.user_id = u.id WHERE p.status = 'pending' ORDER BY p.created_at ASC LIMIT 100");
    $resetRequests = $stmtReset->fetchAll();
    ?>

    <section class="col-xl-8" aria-labelledby="reset-title">
        <div class="master-card p-3 p-lg-4 border-danger border-top border-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <p class="eyebrow text-danger mb-1"><i data-lucide="bell" style="width:14px"></i> Perhatian Khusus</p>
                    <h2 id="reset-title" class="h5 mb-0">Permintaan Reset Password (Lupa Password)</h2>
                </div>
            </div>

            <?php if ($generatedResetLink !== null): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    Link reset password mandiri berhasil dibuat. Link hanya ditampilkan sekali; salin sekarang dan kirim melalui kanal tepercaya.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (count($resetRequests) > 0): ?>
            <div class="table-responsive">
                <table class="table master-table table-hover">
                    <thead>
                    <tr>
                        <th scope="col">Nama / Username</th>
                        <th scope="col">Role</th>
                        <th scope="col">Waktu Permintaan</th>
                        <th scope="col">Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($resetRequests as $req): ?>
                        <tr>
                            <td>
                                <strong><?= escape_output($req['nama']) ?></strong><br>
                                <span class="text-muted small"><?= escape_output($req['username']) ?></span>
                            </td>
                            <td><span class="badge bg-secondary"><?= escape_output(ucfirst($req['role'])) ?></span></td>
                            <td><?= date('d M Y H:i', strtotime($req['created_at'])) ?></td>
                            <td>
                                <?php if (
                                    $generatedResetLink !== null
                                    && $generatedResetLink['request_id'] === (int) $req['id']
                                ): ?>
                                    <div class="input-group input-group-sm mb-2">
                                        <input type="text" class="form-control" value="<?= escape_output($generatedResetLink['url']) ?>" id="link-<?= (int) $req['id'] ?>" readonly>
                                        <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('link-<?= $req['id'] ?>').value); alert('Link disalin!')" title="Salin Link"><i data-lucide="copy" style="width:14px"></i></button>
                                    </div>
                                <?php endif; ?>
                                <form method="POST" action="process_reset_request.php" class="d-inline">
                                    <?= csrfInput() ?>
                                    <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                    <button type="submit" name="action" value="generate_link" class="btn btn-sm btn-primary"><?= (bool) $req['has_token'] && strtotime((string) $req['expires_at']) > time() ? 'Buat Ulang Link' : 'Buat Link Reset' ?></button>
                                    <button type="submit" name="action" value="complete" class="btn btn-sm btn-outline-success" onclick="return confirm('Tandai sudah diselesaikan tanpa mengubah password?')">Selesai</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div class="alert alert-light border text-center text-muted mb-0 py-4">
                    <i data-lucide="check-circle" class="text-success mb-2" style="width: 32px; height: 32px;"></i><br>
                    Tidak ada permintaan reset password saat ini.
                </div>
            <?php endif; ?>
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
            <a href="login_as.php" class="btn btn-sm btn-danger mt-4">Login As pengguna</a>
        </div>
    </aside>
</div>
<?php renderSuperadminFooter(); ?>
