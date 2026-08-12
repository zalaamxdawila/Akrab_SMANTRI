<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/helpers.php';
require_once dirname(__DIR__) . '/app/Security/SuperadminGuard.php';
require_once dirname(__DIR__) . '/views/superadmin/layout.php';

try {
    $actor = SuperadminGuard::authorize($pdo, $_SESSION);
} catch (Throwable) {
    http_response_code(403);
    exit('Akses ditolak.');
}
$error = '';
$service = new ImpersonationService($pdo, $_SESSION);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $targetId = filter_var($_POST['target_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$targetId) {
            throw new InvalidArgumentException('Target tidak valid.');
        }
        $service->start(
            $actor->authenticatedActorId,
            (string) ($_POST['password'] ?? ''),
            (int) $targetId,
            (string) ($_POST['reason_category'] ?? ''),
            (string) ($_POST['reason_note'] ?? '')
        );
        header('Location: ' . BASE_URL . dashboardForRole((string) $_SESSION['role']), true, 303);
        exit;
    } catch (Throwable) {
        $error = 'Login As ditolak. Periksa target, password, alasan, atau batas percobaan.';
    }
}
try {
    $search = normalizeText($_GET['search'] ?? '', 100);
    $page = max(1, filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1);
    $targets = $service->paginateTargets($search, $page, 25);
} catch (InvalidArgumentException) {
    http_response_code(400);
    exit('Filter tidak valid.');
}
renderSuperadminHeader('Login As Pengguna', 'login_as');
?>
<?php if ($error): ?><div class="alert alert-danger"><?= escape_output($error) ?></div><?php endif; ?>
<section class="master-card p-3 p-lg-4 mb-4">
<form method="get" class="row g-3 align-items-end">
<div class="col-md-8"><label for="search" class="form-label">Cari target aktif</label>
<input id="search" name="search" class="form-control" maxlength="100" value="<?= escape_output($search) ?>"></div>
<div class="col-md-4"><button class="btn btn-outline-primary" type="submit">Cari</button></div>
</form></section>
<section class="master-card p-3 p-lg-4">
<div class="alert alert-warning">Login As berlaku maksimal 15 menit. Seluruh aktivitas tercatat dengan actor asli dan actor efektif.</div>
<?php foreach ($targets['items'] as $target): ?>
<form method="post" action="login_as.php" class="row g-2 align-items-end border-bottom py-3">
<?= csrfInput() ?><input type="hidden" name="target_id" value="<?= (int) $target['id'] ?>">
<div class="col-lg-3"><strong><?= escape_output($target['nama']) ?></strong><small class="d-block text-muted">@<?= escape_output($target['username']) ?> · <?= escape_output($target['role']) ?></small></div>
<div class="col-lg-2"><label class="form-label" for="category-<?= (int) $target['id'] ?>">Alasan</label>
<select id="category-<?= (int) $target['id'] ?>" name="reason_category" class="form-select" required><option value="support">Dukungan</option><option value="verification">Verifikasi</option><option value="training">Pelatihan</option><option value="incident_review">Review insiden</option></select></div>
<div class="col-lg-3"><label class="form-label" for="note-<?= (int) $target['id'] ?>">Catatan</label><input id="note-<?= (int) $target['id'] ?>" name="reason_note" class="form-control" minlength="5" maxlength="500" required></div>
<div class="col-lg-2"><label class="form-label" for="password-<?= (int) $target['id'] ?>">Password Anda</label><input id="password-<?= (int) $target['id'] ?>" name="password" type="password" class="form-control" autocomplete="current-password" required></div>
<div class="col-lg-2"><button class="btn btn-danger" type="submit">Mulai Login As</button></div>
</form>
<?php endforeach; ?>
</section>
<?php renderSuperadminFooter(); ?>
