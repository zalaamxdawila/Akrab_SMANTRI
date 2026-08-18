<?php
require_once '../config.php';
require_once '../helpers.php';

check_role('siswa');
$user_id = $_SESSION['user_id'];
$success = isset($_GET['updated']) ? $_GET['updated'] : '';
$error = '';

// Fetch current user data
$stmt = $pdo->prepare("SELECT nama, username, kelas, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$userData = $stmt->fetch();

// Fetch current TTD notification schedule
$stmt = $pdo->prepare("SELECT * FROM jadwal_notifikasi WHERE siswa_id = ? AND archived_at IS NULL ORDER BY id DESC LIMIT 1");
$stmt->execute([$user_id]);
$jadwal = $stmt->fetch();

// Handle Profile Update (email)
if (isset($_POST['update_profile'])) {
    $newEmail = strtolower(trim($_POST['email'] ?? ''));

    if ($newEmail !== '' && (strlen($newEmail) > 254 || filter_var($newEmail, FILTER_VALIDATE_EMAIL) === false)) {
        $error = 'Format email tidak valid.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
            $stmt->execute([$newEmail !== '' ? $newEmail : null, $user_id]);
            $userData['email'] = $newEmail;
            header('Location: profil.php?updated=email');
            exit;
        } catch (PDOException $e) {
            $error = $e->getCode() === '23000'
                ? 'Email sudah digunakan oleh akun lain.'
                : 'Gagal memperbarui profil.';
        }
    }
}

// Handle TTD Reminder Settings
if (isset($_POST['update_jadwal'])) {
    $jam = $_POST['jam_pengingat'] ?? '08:00';
    $hari = $_POST['hari'] ?? 'mingguan';
    $aktif = isset($_POST['aktif']) ? 1 : 0;

    if ($jadwal) {
        $stmt = $pdo->prepare("UPDATE jadwal_notifikasi SET jam_pengingat = ?, hari = ?, aktif = ? WHERE id = ?");
        $stmt->execute([$jam, $hari, $aktif, $jadwal['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO jadwal_notifikasi (siswa_id, jam_pengingat, hari, aktif) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $jam, $hari, $aktif]);
    }
    header('Location: profil.php?updated=jadwal');
    exit;
}

// Handle Password Change
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
                regenerateAuthenticatedSession();
                header('Location: profil.php?updated=password');
                exit;
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

// Refresh user data after any update
$stmt = $pdo->prepare("SELECT nama, username, kelas, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$userData = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM jadwal_notifikasi WHERE siswa_id = ? AND archived_at IS NULL ORDER BY id DESC LIMIT 1");
$stmt->execute([$user_id]);
$jadwal = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - AKRAB</title>
    <link href="/assets/vendor/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css?v=20260729" rel="stylesheet">
    <script src="/assets/vendor/lucide.min.js"></script>
</head>
<body class="bg-light">
<?php renderImpersonationBanner($pdo, $_SESSION); ?>

<nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand text-primary fw-bold" href="dashboard.php">AKRAB Siswa</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="kuesioner.php">Kuesioner</a></li>
                <li class="nav-item"><a class="nav-link" href="konsultasi.php">Konsultasi</a></li>
                <li class="nav-item"><a class="nav-link active fw-bold text-primary" href="profil.php">Profil</a></li>
                <li class="nav-item"><a class="nav-link text-danger" href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-4" style="max-width: 700px;">
    <h3 class="mb-4 fw-bold text-primary"><i data-lucide="user-circle" style="width: 28px;" class="me-2"></i>Profil Saya</h3>

    <?php if ($success === 'email'): ?>
        <div class="alert alert-success alert-auto-dismiss">Email berhasil diperbarui!</div>
    <?php elseif ($success === 'jadwal'): ?>
        <div class="alert alert-success alert-auto-dismiss">Pengingat TTD berhasil diperbarui!</div>
    <?php elseif ($success === 'password'): ?>
        <div class="alert alert-success alert-auto-dismiss">Password berhasil diperbarui!</div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-auto-dismiss"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Section 1: Profile Info -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4">
            <h5 class="card-title text-primary border-bottom pb-2 mb-3 d-flex align-items-center gap-2">
                <i data-lucide="user" style="width: 20px;"></i> Informasi Profil
            </h5>

            <div class="row g-3 mb-4">
                <div class="col-sm-6">
                    <label class="form-label text-muted small fw-semibold">Nama Lengkap</label>
                    <div class="form-control bg-light fw-semibold"><?= htmlspecialchars($userData['nama']) ?></div>
                </div>
                <div class="col-sm-6">
                    <label class="form-label text-muted small fw-semibold">NISN / Username</label>
                    <div class="form-control bg-light fw-semibold"><?= htmlspecialchars($userData['username']) ?></div>
                </div>
                <div class="col-sm-6">
                    <label class="form-label text-muted small fw-semibold">Kelas</label>
                    <div class="form-control bg-light fw-semibold"><?= htmlspecialchars($userData['kelas'] ?: '-') ?></div>
                </div>
                <div class="col-sm-6">
                    <label class="form-label text-muted small fw-semibold">Email <span class="badge bg-info text-white ms-1">Opsional</span></label>
                    <form method="POST" class="d-flex gap-2">
                        <?= csrfInput() ?>
                        <input type="email" name="email" class="form-control" maxlength="254"
                               value="<?= htmlspecialchars($userData['email'] ?? '') ?>"
                               placeholder="Untuk pemulihan password">
                        <input type="hidden" name="update_profile" value="1">
                        <button type="submit" class="btn btn-primary btn-sm flex-shrink-0">
                            <i data-lucide="save" style="width: 16px;"></i> Simpan
                        </button>
                    </form>
                    <div class="form-text">Email digunakan untuk pemulihan password jika lupa.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 2: TTD Reminder Settings -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4">
            <h5 class="card-title text-primary border-bottom pb-2 mb-3 d-flex align-items-center gap-2">
                <i data-lucide="bell" style="width: 20px;"></i> Pengingat Minum TTD
            </h5>
            <p class="text-muted small mb-3">Atur pengingat untuk minum Tablet Tambah Darah (TTD) secara berkala.</p>

            <form method="POST">
                <?= csrfInput() ?>
                <input type="hidden" name="update_jadwal" value="1">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Jam Pengingat</label>
                        <input type="time" name="jam_pengingat" class="form-control"
                               value="<?= htmlspecialchars($jadwal['jam_pengingat'] ?? '08:00') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Frekuensi</label>
                        <select name="hari" class="form-select">
                            <option value="harian" <?= ($jadwal['hari'] ?? '') === 'harian' ? 'selected' : '' ?>>Harian</option>
                            <option value="mingguan" <?= ($jadwal['hari'] ?? 'mingguan') === 'mingguan' ? 'selected' : '' ?>>Mingguan</option>
                            <option value="saat_menstruasi" <?= ($jadwal['hari'] ?? '') === 'saat_menstruasi' ? 'selected' : '' ?>>Saat Menstruasi</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="aktif" id="jadwalAktif"
                                   <?= ($jadwal['aktif'] ?? 1) ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold small" for="jadwalAktif">Aktifkan Pengingat</label>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-3">
                    <i data-lucide="save" style="width: 16px;" class="me-1"></i> Simpan Pengingat
                </button>
            </form>

            <?php if ($jadwal): ?>
            <div class="mt-3 p-3 bg-light rounded-3">
                <small class="text-muted">
                    <i data-lucide="info" style="width: 14px;" class="me-1"></i>
                    Status:
                    <?php if ($jadwal['aktif']): ?>
                        <span class="text-success fw-bold">Aktif</span> &mdash;
                        <?php if ($jadwal['hari'] === 'harian'): ?>
                            Setiap hari jam <?= date('H:i', strtotime($jadwal['jam_pengingat'])) ?>
                        <?php elseif ($jadwal['hari'] === 'mingguan'): ?>
                            Setiap minggu jam <?= date('H:i', strtotime($jadwal['jam_pengingat'])) ?>
                        <?php else: ?>
                            Saat menstruasi jam <?= date('H:i', strtotime($jadwal['jam_pengingat'])) ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="text-muted fw-bold">Nonaktif</span>
                    <?php endif; ?>
                </small>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Section 3: Change Password -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4">
            <h5 class="card-title text-primary border-bottom pb-2 mb-3 d-flex align-items-center gap-2">
                <i data-lucide="lock" style="width: 20px;"></i> Ubah Password
            </h5>
            <form method="POST">
                <?= csrfInput() ?>
                <input type="hidden" name="update_password" value="1">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Password Lama</label>
                    <input type="password" name="old_password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Password Baru</label>
                    <input type="password" name="new_password" class="form-control" required minlength="6">
                    <div class="form-text">Minimal 6 karakter.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Konfirmasi Password Baru</label>
                    <input type="password" name="confirm_password" class="form-control" required minlength="6">
                </div>
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="save" style="width: 16px;" class="me-1"></i> Simpan Password Baru
                </button>
            </form>
        </div>
    </div>
</div>

<script src="/assets/vendor/bootstrap.bundle.min.js"></script>
<script src="../assets/js/app-init.js?v=20260729"></script>
<script>lucide.createIcons();</script>
</body>
</html>
