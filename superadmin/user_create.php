<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/helpers.php';
require_once dirname(__DIR__) . '/app/Security/SuperadminGuard.php';
require_once dirname(__DIR__) . '/app/Services/SuperadminUserService.php';
require_once dirname(__DIR__) . '/views/superadmin/layout.php';

try {
    $actor = SuperadminGuard::authorize($pdo, $_SESSION);
} catch (Throwable) {
    http_response_code(403);
    exit('Akses ditolak.');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = (new SuperadminUserService($pdo))->create(
            $actor,
            $_POST,
            requestCorrelationId()
        );
        header('Location: user_detail.php?id=' . $id . '&created=1', true, 303);
        exit;
    } catch (PDOException $exception) {
        $error = 'Username sudah digunakan atau data tidak dapat disimpan.';
    } catch (Throwable $exception) {
        $error = $exception instanceof InvalidArgumentException
            ? $exception->getMessage()
            : 'Pengguna tidak dapat dibuat.';
    }
}

renderSuperadminHeader('Tambah Pengguna', 'users');
?>
<div class="mb-3"><a href="users.php" class="btn btn-sm btn-outline-secondary">← Kembali</a></div>
<section class="master-card p-3 p-lg-4">
    <?php if ($error !== ''): ?><div class="alert alert-danger"><?= escape_output($error) ?></div><?php endif; ?>
    <form method="post" action="user_create.php" class="row g-3">
        <?= csrfInput() ?>
        <div class="col-md-6">
            <label for="nama" class="form-label">Nama</label>
            <input id="nama" name="nama" class="form-control" minlength="2" maxlength="100" required>
        </div>
        <div class="col-md-6">
            <label for="username" class="form-label">Username</label>
            <input id="username" name="username" class="form-control" minlength="3" maxlength="50"
                   pattern="[a-zA-Z0-9][a-zA-Z0-9._-]{2,49}" autocomplete="off" required>
        </div>
        <div class="col-md-4">
            <label for="role" class="form-label">Role</label>
            <select id="role" name="role" class="form-select" required>
                <option value="siswa">Siswa</option>
                <option value="uks">UKS</option>
                <option value="orangtua">Orang tua</option>
            </select>
        </div>
        <div class="col-md-4">
            <label for="kelas" class="form-label">Kelas (khusus siswa)</label>
            <input id="kelas" name="kelas" class="form-control" maxlength="20">
        </div>
        <div class="col-md-4">
            <label for="password" class="form-label">Password awal</label>
            <input id="password" name="password" type="password" class="form-control"
                   minlength="12" maxlength="1024" autocomplete="new-password" required>
        </div>
        <div class="col-12">
            <button class="btn btn-primary" type="submit">Buat pengguna</button>
        </div>
    </form>
</section>
<?php renderSuperadminFooter(); ?>
