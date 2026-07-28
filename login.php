<?php
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT id, nama, role, password_hash FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if (
            $user
            && isApplicationRole((string) $user['role'])
            && password_verify($password, $user['password_hash'])
        ) {
            regenerateAuthenticatedSession();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['role'] = $user['role'];
            
            header('Location: ' . BASE_URL . dashboardForRole($user['role']));
            exit;
        } else {
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
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css?v=<?= time() ?>" rel="stylesheet">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
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
                <input type="text" class="form-control rounded-3 border-0 bg-light" id="floatingInput" name="username" placeholder="Username" required>
                <label for="floatingInput" class="text-muted"><i data-lucide="user" style="width: 16px;" class="me-1"></i> Username</label>
            </div>
            <div class="form-floating mb-4">
                <input type="password" class="form-control rounded-3 border-0 bg-light" id="floatingPassword" name="password" placeholder="Password" required>
                <label for="floatingPassword" class="text-muted"><i data-lucide="lock" style="width: 16px;" class="me-1"></i> Password</label>
            </div>
            <button class="w-100 btn btn-lg btn-primary rounded-pill fw-bold shadow-sm mb-4 d-flex justify-content-center align-items-center gap-2" type="submit">
                Masuk <i data-lucide="arrow-right" style="width: 20px;"></i>
            </button>
            <p class="text-center text-muted mb-0 small">Belum punya akun? <a href="register.php" class="text-decoration-none fw-semibold" style="color: var(--primary-color);">Daftar di sini</a></p>
        </form>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>lucide.createIcons();</script>
<script src="assets/js/main.js"></script>
<script src="assets/js/app-init.js"></script>
</body>
</html>
