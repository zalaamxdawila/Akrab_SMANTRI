<?php
require_once 'config.php';
require_once 'helpers.php';

if (isset($_SESSION['user_id'])) {
    $destination = $_SESSION['role'] === 'siswa'
        ? studentOnboardingDestination(studentOnboardingState($pdo, (int) $_SESSION['user_id']))
        : null;
    header('Location: ' . BASE_URL . ($destination ?? dashboardForRole($_SESSION['role'])));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usernameInput = $_POST['username'] ?? '';
    $passwordInput = $_POST['password'] ?? '';
    $username = is_string($usernameInput) ? trim($usernameInput) : '';
    $password = is_string($passwordInput) ? $passwordInput : '';

    if (!AuthAttemptLimiter::allows($_SESSION, 'password-login')) {
        $error = "Username atau password salah!";
    } elseif ($username !== '' && $password !== '') {
        $stmt = $pdo->prepare(
            'SELECT id, nama, role, status, password_hash
             FROM users
             WHERE username = ?'
        );
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if (
            $user
            && password_verify($password, $user['password_hash'])
            && userCanAuthenticate($user)
        ) {
            AuthAttemptLimiter::clear($_SESSION, 'password-login');
            regenerateAuthenticatedSession();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['role'] = $user['role'];
            recordAuditEvent($pdo, (int) $user['id'], 'auth.login_succeeded', 'session', null, ['outcome' => 'success', 'actor_role' => $user['role']]);
            akrabLog('info', 'login_succeeded', ['outcome' => 'success', 'actor_role' => $user['role']]);

            $destination = $user['role'] === 'siswa'
                ? studentOnboardingDestination(studentOnboardingState($pdo, (int) $user['id']))
                : null;
            header('Location: ' . BASE_URL . ($destination ?? dashboardForRole($user['role'])));
            exit;
        } else {
            AuthAttemptLimiter::record($_SESSION, 'password-login');
            recordAuditEvent($pdo, null, 'auth.login_failed', 'session', null, ['outcome' => 'failed']);
            akrabLog('warn', 'login_failed', ['outcome' => 'failed']);
            $error = "Username atau password salah!";
        }
    } else {
        $error = "Semua kolom harus diisi!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Aplikasi AKRAB</title>
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
            <h2 class="h3 fw-bold mb-2" style="color: var(--primary-color);">AKRAB</h2>
            <p class="text-muted small mb-0">Aplikasi Kesehatan Remaja Bebas Anemia</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-auto-dismiss border-0 shadow-sm rounded-3 py-2 text-center small fw-medium d-flex align-items-center justify-content-center gap-2" role="alert">
                <i data-lucide="alert-circle" style="width: 18px;"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="login.php" class="mt-4">
            <?= csrfInput() ?>
            <div class="form-floating mb-3">
                <input type="text" class="form-control rounded-3 border-0 bg-light" id="floatingInput" name="username" placeholder="Username" autocomplete="username" required>
                <label for="floatingInput" class="text-muted"><i data-lucide="user" style="width: 16px;" class="me-1"></i> Username</label>
            </div>
            <div class="form-floating mb-4">
                <input type="password" class="form-control rounded-3 border-0 bg-light" id="floatingPassword" name="password" placeholder="Password" autocomplete="current-password" required>
                <label for="floatingPassword" class="text-muted"><i data-lucide="lock" style="width: 16px;" class="me-1"></i> Password</label>
            </div>
            <button class="w-100 btn btn-lg btn-primary rounded-pill fw-bold shadow-sm mb-3 d-flex justify-content-center align-items-center gap-2" type="submit">
                Masuk <i data-lucide="arrow-right" style="width: 20px;"></i>
            </button>

            <div class="text-center mb-3">
                <a href="lupa_password.php" class="text-decoration-none text-muted small fw-semibold">Lupa Password?</a>
            </div>
            <p class="text-center text-muted mb-0 small">Belum punya akun? <a href="register.php" class="text-decoration-none fw-semibold" style="color: var(--primary-color);">Daftar di sini</a></p>
        </form>
    </div>
</main>

<script src="/assets/vendor/bootstrap.bundle.min.js"></script>
<script>lucide.createIcons();</script>
<script src="assets/js/main.js?v=20260729"></script>
<script src="assets/js/app-init.js?v=20260729"></script>
</body>
</html>
