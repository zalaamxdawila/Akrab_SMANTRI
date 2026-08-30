<?php
require_once 'config.php';
require_once 'helpers.php';

$error = '';
$success = isset($_GET['registered'])
    ? "Pendaftaran berhasil! Silakan login ke akun Anda."
    : '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verifyCsrfOrFail(csrfTokenFromRequest($_POST, $_SERVER));

    $clientHash = hash_hmac(
        'sha256',
        (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
        requireEnvironmentValue('AKRAB_RATE_LIMIT_KEY')
    );
    $attemptCount = $pdo->prepare(
        'SELECT COUNT(*) FROM registration_attempts
         WHERE client_hash = ? AND attempted_at >= (CURRENT_TIMESTAMP - INTERVAL 15 MINUTE)'
    );
    $attemptCount->execute([$clientHash]);
    if ((int) $attemptCount->fetchColumn() >= 5) {
        http_response_code(429);
        $error = 'Terlalu banyak percobaan. Silakan coba lagi nanti.';
    } else {
        $attempt = $pdo->prepare('INSERT INTO registration_attempts (client_hash) VALUES (?)');
        $attempt->execute([$clientHash]);
    }

    $nama = sanitize_input($_POST['nama'] ?? '');
    $username = sanitize_input($_POST['username'] ?? '');
    $emailRaw = $_POST['email'] ?? '';
    $email = is_string($emailRaw) ? strtolower(trim($emailRaw)) : '';
    $password = trim($_POST['password'] ?? '');
    $role = sanitize_input($_POST['role'] ?? '');
    $kelas_tingkat = isset($_POST['kelas']) ? sanitize_input($_POST['kelas']) : '';
    $jurusan = isset($_POST['jurusan']) ? sanitize_input($_POST['jurusan']) : '';
    $kelas = $kelas_tingkat;
    if (!empty($jurusan)) {
        $kelas .= ' ' . $jurusan;
    }
    $anak_username = isset($_POST['anak_username']) ? sanitize_input($_POST['anak_username']) : null;
    
    if (empty($error) && !in_array($role, ['siswa', 'orangtua'], true)) {
        $error = 'Jenis akun tidak diizinkan untuk pendaftaran publik.';
    }

    if (empty($error)) {
        if (empty($email)) {
            $error = 'Email wajib diisi untuk akun baru.';
        } elseif (strlen($email) > 254 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $error = 'Alamat email tidak valid.';
        }
    }

    if (empty($error) && !empty($nama) && !empty($username) && !empty($password) && !empty($role)) {
        // Validate if Orang Tua
        if ($role === 'orangtua') {
            if (empty($anak_username)) {
                $error = "NISN Anak wajib diisi untuk pendaftaran Orang Tua.";
            }
        } 
        // Validate if Siswa
        elseif ($role === 'siswa' && empty($kelas)) {
            $error = "Kelas wajib dipilih untuk pendaftaran Siswa.";
        }
        // If no validation errors, proceed to insert
        if (empty($error)) {
            // Check identifiers before relying on the database unique constraints.
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $error = "Username/NISN atau email sudah digunakan!";
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                $pdo->beginTransaction();
                try {
                    $stmt = $pdo->prepare(
                        "INSERT INTO users (nama, role, username, email, password_hash, kelas)
                         VALUES (?, ?, ?, ?, ?, ?)"
                    );
                    $stmt->execute([$nama, $role, $username, $email, $password_hash, $kelas]);
                    $newUserId = (int) $pdo->lastInsertId();

                    if ($role === 'orangtua') {
                        $link = $pdo->prepare(
                            "INSERT INTO parent_student_links
                                (parent_id, requested_student_username, status)
                             VALUES (?, ?, 'pending')"
                        );
                        $link->execute([$newUserId, $anak_username]);

                        $audit = $pdo->prepare(
                            'INSERT INTO audit_log
                                (actor_id, action, target_type, target_id, metadata_json)
                             VALUES (?, ?, ?, ?, ?)'
                        );
                        $audit->execute([
                            $newUserId,
                            'parent_link.requested',
                            'parent_student_link',
                            (int) $pdo->lastInsertId(),
                            json_encode(['status' => 'pending'], JSON_THROW_ON_ERROR),
                        ]);
                    }

                    $pdo->commit();
                    header('Location: register.php?registered=1');
                    exit;
                } catch (Throwable $exception) {
                    $pdo->rollBack();
                    $error = "Terjadi kesalahan sistem saat mendaftar.";
                }
            }
        }
    } elseif (empty($error)) {
        $error = "Semua kolom wajib diisi!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - AKRAB</title>
    <link rel="icon" href="assets/icons/icon.svg" type="image/svg+xml">
    <link href="/assets/vendor/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css?v=20260818" rel="stylesheet">
    <script src="/assets/vendor/lucide.min.js"></script>
</head>
<body class="login-bg d-flex align-items-center py-4" style="min-height: 100vh;">

<main class="form-signin w-100 m-auto animate-fade-in-up" style="max-width: 460px; padding: 15px;">
    <div class="login-card p-4 p-md-5">
        <div class="card-accent"></div>
        <div class="text-center mb-4 pt-3">
            <div class="mb-3">
                <img src="assets/img/logo.png" alt="AKRAB Logo" style="width: 72px; height: 72px; object-fit: contain; border-radius: 14px; box-shadow: 0 8px 24px rgba(16, 185, 129, 0.2);">
            </div>
            <h2 class="h4 mb-1 fw-bold" style="color: var(--primary-dark);">Daftar Akun Baru</h2>
            <p class="text-muted small mb-0">Aplikasi Kesehatan Remaja Bebas Anemia</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-auto-dismiss border-0 rounded-3 py-2 text-center small fw-medium d-flex align-items-center justify-content-center gap-2" role="alert" style="background: rgba(239, 68, 68, 0.1); color: #dc2626;">
                <i data-lucide="alert-circle" style="width: 16px;"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="text-center py-4" role="alert">
                <div class="mb-3"><i data-lucide="check-circle" style="width: 48px; height: 48px; color: var(--primary-color);"></i></div>
                <p class="fw-bold mb-3" style="color: var(--primary-dark);">Pendaftaran Berhasil!</p>
                <p class="text-muted small mb-3"><?= htmlspecialchars($success) ?></p>
                <a href="login.php" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">Menuju Login</a>
            </div>
        <?php else: ?>

        <form action="register.php" method="POST" class="mt-2">
            <?= csrfInput() ?>
            <div class="mb-3">
                <label for="role" class="form-label small text-muted fw-semibold">Daftar Sebagai</label>
                <select class="form-select rounded-3" id="role" name="role" required onchange="toggleFields()" style="background: var(--surface-muted);">
                    <option value="siswa">Siswa</option>
                    <option value="orangtua">Orang Tua / Wali Murid</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="nama" class="form-label small text-muted fw-semibold">Nama Lengkap</label>
                <input type="text" class="form-control rounded-3" id="nama" name="nama" required style="background: var(--surface-muted);">
            </div>
            <div class="mb-3">
                <label for="username" class="form-label small text-muted fw-semibold">Username / NISN</label>
                <input type="text" class="form-control rounded-3" id="username" name="username" required style="background: var(--surface-muted);">
            </div>
            <div class="mb-3">
                <label for="email" class="form-label small text-muted fw-semibold">Email <span class="text-danger">*</span></label>
                <input type="email" maxlength="254" autocomplete="email" class="form-control rounded-3" id="email" name="email" required placeholder="Wajib untuk pemulihan password" style="background: var(--surface-muted);">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label small text-muted fw-semibold">Password</label>
                <input type="password" class="form-control rounded-3" id="password" name="password" required style="background: var(--surface-muted);">
            </div>
            <div class="mb-4" id="field-kelas">
                <div class="row g-2">
                    <div class="col-md-6">
                        <label for="kelas" class="form-label small text-muted fw-semibold">Tingkat Kelas</label>
                        <select class="form-select rounded-3" id="kelas" name="kelas" style="background: var(--surface-muted);">
                            <option value="">Pilih Tingkat</option>
                            <option value="Kelas VII">Kelas VII (SMP)</option>
                            <option value="Kelas VIII">Kelas VIII (SMP)</option>
                            <option value="Kelas IX">Kelas IX (SMP)</option>
                            <option value="Kelas X">Kelas X (SMA)</option>
                            <option value="Kelas XI">Kelas XI (SMA)</option>
                            <option value="Kelas XII">Kelas XII (SMA)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="jurusan" class="form-label small text-muted fw-semibold">Jurusan</label>
                        <input type="text" class="form-control rounded-3" id="jurusan" name="jurusan" placeholder="Cth: MIPA 1" style="background: var(--surface-muted);">
                    </div>
                </div>
            </div>
            <div class="mb-4" id="field-anak" style="display: none;">
                <label for="anak_username" class="form-label small text-muted fw-semibold">NISN Anak</label>
                <input type="text" class="form-control rounded-3" id="anak_username" name="anak_username" placeholder="Masukkan NISN Anak Anda" style="background: var(--surface-muted);">
                <small class="text-muted mt-1 d-block"><i data-lucide="info" style="width: 14px;" class="me-1"></i>Pastikan anak Anda sudah terdaftar terlebih dahulu.</small>
            </div>
            <button class="w-100 btn btn-lg btn-primary rounded-pill fw-bold shadow-sm mb-3" type="submit">Daftar Sekarang</button>
        </form>

        <div class="text-center pt-3 border-top" style="border-color: var(--border-color) !important;">
            <p class="text-muted small mb-0">Sudah punya akun? <a href="login.php" class="text-decoration-none fw-bold" style="color: var(--primary-color);">Masuk di sini</a></p>
        </div>
        <?php endif; ?>
    </div>
</main>

<script src="/assets/vendor/bootstrap.bundle.min.js"></script>
<script>
function toggleFields() {
    const role = document.getElementById('role').value;
    const fieldKelas = document.getElementById('field-kelas');
    const fieldAnak = document.getElementById('field-anak');
    fieldKelas.style.display = 'none';
    fieldAnak.style.display = 'none';
    document.getElementById('kelas').required = false;
    document.getElementById('anak_username').required = false;
    if (role === 'siswa') {
        fieldKelas.style.display = 'block';
        document.getElementById('kelas').required = true;
    } else if (role === 'orangtua') {
        fieldAnak.style.display = 'block';
        document.getElementById('anak_username').required = true;
    }
}
lucide.createIcons();
window.addEventListener('load', toggleFields);
</script>
<script src="assets/js/main.js?v=20260818"></script>
<script src="assets/js/app-init.js?v=20260831-safe-install"></script>
</body>
</html>
