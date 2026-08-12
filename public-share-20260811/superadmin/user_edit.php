<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/helpers.php';
require_once dirname(__DIR__) . '/app/Security/SuperadminGuard.php';
require_once dirname(__DIR__) . '/app/Services/SuperadminUserService.php';
require_once dirname(__DIR__) . '/app/Repositories/SuperadminUserRepository.php';
require_once dirname(__DIR__) . '/views/superadmin/layout.php';

try {
    $actor = SuperadminGuard::authorize($pdo, $_SESSION);
} catch (Throwable) {
    http_response_code(403);
    exit('Akses ditolak.');
}
$id = filter_var($_GET['id'] ?? $_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
$user = $id ? (new SuperadminUserRepository($pdo))->findDetail((int) $id) : null;
if (!$user || $user['role'] === 'superadmin') {
    http_response_code(404);
    exit('Pengguna tidak ditemukan.');
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        (new SuperadminUserService($pdo))->correct(
            $actor,
            (int) $id,
            $_POST,
            (string) ($_POST['reason'] ?? ''),
            requestCorrelationId()
        );
        header('Location: user_detail.php?id=' . (int) $id . '&updated=1', true, 303);
        exit;
    } catch (Throwable $exception) {
        $error = $exception instanceof InvalidArgumentException
            ? $exception->getMessage() : 'Koreksi tidak dapat disimpan.';
    }
}
renderSuperadminHeader('Koreksi Pengguna', 'users');
?>
<div class="mb-3"><a href="user_detail.php?id=<?= (int) $id ?>" class="btn btn-sm btn-outline-secondary">← Kembali</a></div>
<section class="master-card p-3 p-lg-4">
    <?php if ($error): ?><div class="alert alert-danger"><?= escape_output($error) ?></div><?php endif; ?>
    <form method="post" action="user_edit.php" class="row g-3">
        <?= csrfInput() ?><input type="hidden" name="user_id" value="<?= (int) $id ?>">
        <div class="col-md-5"><label for="nama" class="form-label">Nama</label>
            <input id="nama" name="nama" class="form-control" value="<?= escape_output($user['nama']) ?>" maxlength="100" required></div>
        <div class="col-md-3"><label for="kelas" class="form-label">Kelas</label>
            <input id="kelas" name="kelas" class="form-control" value="<?= escape_output($user['kelas'] ?? '') ?>" maxlength="20"></div>
        <div class="col-md-4"><label for="reason" class="form-label">Alasan</label>
            <select id="reason" name="reason" class="form-select" required>
                <option value="correction">Koreksi data</option><option value="verification">Verifikasi</option>
            </select></div>
        <div class="col-12"><button class="btn btn-primary" type="submit">Simpan koreksi</button></div>
    </form>
</section>
<?php renderSuperadminFooter(); ?>
