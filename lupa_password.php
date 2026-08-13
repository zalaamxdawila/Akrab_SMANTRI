<?php
require_once 'config.php';
require_once 'helpers.php';

if (isset($_SESSION['user_id'])) {
    header("Location: " . ($_SESSION['role'] == 'superadmin' ? 'superadmin/dashboard.php' : ($_SESSION['role'] == 'uks' ? 'uks/dashboard.php' : 'siswa/dashboard.php')));
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifierInput = $_POST['identifier'] ?? '';
    $identifier = is_string($identifierInput) ? strtolower(trim($identifierInput)) : '';

    if (!AuthAttemptLimiter::allows($_SESSION, 'password-reset-request')) {
        $success = 'Jika akun terdaftar, permintaan reset akan diteruskan ke Superadmin.';
    } elseif ($identifier === '' || strlen($identifier) > 254) {
        $error = "Email atau Username/NISN wajib diisi.";
    } else {
        AuthAttemptLimiter::record($_SESSION, 'password-reset-request');
        $stmt = $pdo->prepare("SELECT id, nama, role FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        if ($user) {
            try {
                $requestCreated = false;
                $stmtCheck = $pdo->prepare("SELECT id FROM password_reset_requests WHERE user_id = ? AND status = 'pending'");
                $stmtCheck->execute([$user['id']]);

                if (!$stmtCheck->fetch()) {
                    $stmtInsert = $pdo->prepare("INSERT INTO password_reset_requests (user_id) VALUES (?)");
                    $stmtInsert->execute([$user['id']]);
                    $requestCreated = true;
                }

                $superadminEmail = environmentValue('AKRAB_SUPERADMIN_EMAIL');
                if (
                    $requestCreated
                    && is_string($superadminEmail)
                    && filter_var($superadminEmail, FILTER_VALIDATE_EMAIL)
                ) {
                    $message = "Halo Superadmin,\n\n";
                    $message .= "Ada permintaan reset password baru di aplikasi AKRAB.\n";
                    $message .= "Silakan buka dashboard superadmin untuk meninjaunya.\n";
                    @mail(
                        $superadminEmail,
                        'Permintaan Reset Password - AKRAB',
                        $message,
                        "From: noreply@akrab.portodq.com\r\nReply-To: noreply@akrab.portodq.com"
                    );
                }
            } catch (Throwable $e) {
                akrabLog('error', 'reset_request_failed', ['exception_class' => get_class($e)]);
                $error = 'Permintaan belum dapat diproses. Silakan coba kembali.';
            }
        }

        if ($error === '') {
            $success = 'Jika akun terdaftar, permintaan reset akan diteruskan ke Superadmin.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Aplikasi AKRAB</title>
    <link rel="icon" href="assets/icons/icon.svg" type="image/svg+xml">
    <!-- Bootstrap CSS -->
    <link href="/assets/vendor/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css?v=20260729" rel="stylesheet">
    <!-- Lucide Icons -->
    <script src="/assets/vendor/lucide.min.js"></script>
    <style>
        .login-bg {
            background: url('assets/img/bg.png') no-repeat center center fixed;
            background-size: cover;
        }
        .login-card {
            background: var(--bg-white);
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            border: 1px solid rgba(255,255,255,0.8);
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body class="d-flex align-items-center py-4 login-bg" style="min-height: 100vh;">

<main class="form-signin w-100 m-auto animate-fade-in-up" style="max-width: 420px; padding: 15px;">
    <div class="login-card p-4 p-md-5">
        <div class="text-center mb-4">
            <div class="mb-3">
                <img src="assets/img/logo.png" alt="AKRAB Logo" style="width: 80px; height: 80px; object-fit: contain; border-radius: 16px; box-shadow: 0 8px 20px rgba(16, 185, 129, 0.25);">
            </div>
            <h2 class="h3 fw-bold mb-2" style="color: var(--primary-color);">Lupa Password?</h2>
            <p class="text-muted small mb-0">Masukkan email atau NISN/Username untuk meminta reset password ke Superadmin.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-auto-dismiss border-0 shadow-sm rounded-3 py-2 text-center small fw-medium d-flex align-items-center justify-content-center gap-2" role="alert">
                <i data-lucide="alert-circle" style="width: 18px;"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success border-0 shadow-sm rounded-3 py-2 text-center small fw-medium d-flex align-items-center justify-content-center gap-2" role="alert">
                <i data-lucide="check-circle-2" style="width: 18px;"></i> <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="lupa_password.php" class="mt-4">
            <?= csrfInput() ?>
            <div class="form-floating mb-4">
                <input type="text" class="form-control rounded-3 border-0 bg-light" id="floatingInput" name="identifier" maxlength="254" placeholder="Email atau Username" autocomplete="username" required autofocus>
                <label for="floatingInput" class="text-muted"><i data-lucide="user" style="width: 16px;" class="me-1"></i> Email / Username / NISN</label>
            </div>
            <button class="w-100 btn btn-lg btn-primary rounded-pill fw-bold shadow-sm mb-3 d-flex justify-content-center align-items-center gap-2" type="submit">
                <i data-lucide="send" style="width: 20px;"></i> Minta Reset
            </button>

            <div class="text-center mt-3">
                <a href="login.php" class="text-decoration-none text-muted small fw-semibold">
                    <i data-lucide="arrow-left" style="width: 14px;" class="me-1"></i> Kembali ke Login
                </a>
            </div>
        </form>
    </div>
</main>

<script src="/assets/vendor/bootstrap.bundle.min.js"></script>
<script>lucide.createIcons();</script>
<script src="assets/js/main.js?v=20260729"></script>
</body>
</html>
