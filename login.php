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
    <title>Login - AKRAB</title>
    <link rel="icon" href="assets/icons/icon.svg" type="image/svg+xml">
    <link href="/assets/vendor/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css?v=20260818" rel="stylesheet">
    <script src="/assets/vendor/lucide.min.js"></script>
</head>
<body class="login-bg d-flex align-items-center py-4" style="min-height: 100vh;">

<main class="form-signin w-100 m-auto animate-fade-in-up" style="max-width: 420px; padding: 15px;">
    <div class="login-card p-4 p-md-5">
        <div class="card-accent"></div>
        <div class="text-center mb-4 pt-3">
            <div class="mb-3">
                <img src="assets/img/logo.png" alt="AKRAB Logo" style="width: 72px; height: 72px; object-fit: contain; border-radius: 14px; box-shadow: 0 8px 24px rgba(16, 185, 129, 0.2);">
            </div>
            <h2 class="h4 fw-bold mb-1" style="color: var(--primary-dark);">Selamat Datang</h2>
            <p class="text-muted small mb-0">Masuk ke akun AKRAB Anda</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-auto-dismiss border-0 rounded-3 py-2 text-center small fw-medium d-flex align-items-center justify-content-center gap-2" role="alert" style="background: rgba(239, 68, 68, 0.1); color: #dc2626;">
                <i data-lucide="alert-circle" style="width: 16px;"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="mt-3">
            <?= csrfInput() ?>
            <div class="form-floating mb-3">
                <input type="text" class="form-control rounded-3" id="floatingInput" name="username" placeholder="Username" autocomplete="username" required style="background: var(--surface-muted);">
                <label for="floatingInput" class="text-muted"><i data-lucide="user" style="width: 14px;" class="me-1"></i> Username</label>
            </div>
            <div class="form-floating mb-4">
                <input type="password" class="form-control rounded-3" id="floatingPassword" name="password" placeholder="Password" autocomplete="current-password" required style="background: var(--surface-muted);">
                <label for="floatingPassword" class="text-muted"><i data-lucide="lock" style="width: 14px;" class="me-1"></i> Password</label>
            </div>
            <button class="w-100 btn btn-lg btn-primary rounded-pill fw-bold shadow-sm mb-3" type="submit">
                Masuk <i data-lucide="arrow-right" style="width: 18px;"></i>
            </button>

            <div class="text-center mb-3">
                <a href="lupa_password.php" class="text-decoration-none text-muted small fw-semibold">Lupa Password?</a>
            </div>
            <p class="text-center text-muted mb-0 small">Belum punya akun? <a href="register.php" class="text-decoration-none fw-bold" style="color: var(--primary-color);">Daftar di sini</a></p>
        </form>
    </div>
</main>

<script src="/assets/vendor/bootstrap.bundle.min.js"></script>
<script>lucide.createIcons();</script>
<script src="assets/js/main.js?v=20260818"></script>
<script src="assets/js/app-init.js?v=20260818"></script>
</body>
</html>
