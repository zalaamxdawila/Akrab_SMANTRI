<?php
require_once '../config.php';
require_once '../helpers.php';

check_role('siswa');
$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_password'])) {
        $old_pass = $_POST['old_password'];
        $new_pass = $_POST['new_password'];
        $confirm_pass = $_POST['confirm_password'];
        
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (password_verify($old_pass, $user['password_hash'])) {
            if ($new_pass === $confirm_pass) {
                if (strlen($new_pass) >= 6) {
                    $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
                    $update = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                    $update->execute([$new_hash, $user_id]);
                    $success = "Password berhasil diperbarui!";
                } else {
                    $error = "Password baru minimal 6 karakter.";
                }
            } else {
                $error = "Konfirmasi password baru tidak cocok.";
            }
        } else {
            $error = "Password lama salah.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - AKRAB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css?v=<?= time() ?>" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand text-primary fw-bold" href="dashboard.php">AKRAB Siswa</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-toggle="target" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="edukasi.php">Edukasi</a></li>
                <li class="nav-item"><a class="nav-link" href="kuesioner.php">Kuesioner</a></li>
                <li class="nav-item"><a class="nav-link" href="konsultasi.php">Konsultasi</a></li>
                <li class="nav-item"><a class="nav-link active fw-bold text-primary" href="profil.php">Profil</a></li>
                <li class="nav-item"><a class="nav-link text-danger" href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h3 class="mb-4 text-center">Pengaturan Profil</h3>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-auto-dismiss"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-auto-dismiss"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-4">
                    <h5 class="card-title text-primary border-bottom pb-2 mb-3">Ubah Password</h5>
                    <form method="POST">
                        <input type="hidden" name="update_password" value="1">
                        <div class="mb-3">
                            <label class="form-label">Password Lama</label>
                            <input type="password" name="old_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password Baru</label>
                            <input type="password" name="new_password" class="form-control" required minlength="6">
                            <div class="form-text">Minimal 6 karakter.</div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" name="confirm_password" class="form-control" required minlength="6">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Simpan Password Baru</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
<script src="../assets/js/app-init.js"></script>
</body>
</html>
