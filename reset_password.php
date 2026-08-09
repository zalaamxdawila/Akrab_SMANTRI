<?php
require_once "config.php";
require_once "helpers.php";

if (isset($_SESSION["user_id"])) {
    header("Location: " . ($_SESSION["role"] == "superadmin" ? "superadmin/dashboard.php" : ($_SESSION["role"] == "uks" ? "uks/dashboard.php" : "siswa/dashboard.php")));
    exit;
}

$tokenInput = $_GET["token"] ?? $_POST["token"] ?? "";
$token = is_string($tokenInput) ? trim($tokenInput) : '';
try {
    $tokenDigest = PasswordResetToken::digest($token);
} catch (InvalidArgumentException) {
    exit("Token reset tidak valid atau tidak ditemukan.");
}

$error = "";
$success = "";
$req = null;

try {
    $stmt = $pdo->prepare("SELECT p.id, p.user_id, u.username, u.nama FROM password_reset_requests p JOIN users u ON p.user_id = u.id WHERE p.token_hash = ? AND p.status = \"pending\" AND p.expires_at > NOW()");
    $stmt->execute([$tokenDigest]);
    $req = $stmt->fetch();
} catch (Exception $e) {
    // If table not updated yet
    exit("Fitur dalam pemeliharaan.");
}

if (!$req) {
    exit("Link reset password tidak valid, sudah kadaluarsa, atau sudah digunakan.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $passwordInput = $_POST["password"] ?? "";
    $confirmInput = $_POST["confirm"] ?? "";
    $password = is_string($passwordInput) ? $passwordInput : '';
    $confirm = is_string($confirmInput) ? $confirmInput : '';

    if (strlen($password) < 12 || strlen($password) > 1024) {
        $error = "Password harus terdiri dari 12 sampai 1024 karakter.";
    } elseif ($password !== $confirm) {
        $error = "Konfirmasi password tidak cocok.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        if ($hash === false) {
            $error = "Gagal mengubah password.";
        } else {
            try {
                $pdo->beginTransaction();
                $stmtReq = $pdo->prepare("UPDATE password_reset_requests SET status = \"completed\", token_hash = NULL, expires_at = NULL WHERE id = ? AND token_hash = ? AND status = \"pending\" AND expires_at > NOW()");
                $stmtReq->execute([$req["id"], $tokenDigest]);
                if ($stmtReq->rowCount() !== 1) {
                    throw new RuntimeException('Reset token was already consumed.');
                }

                $stmtUpd = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $stmtUpd->execute([$hash, $req["user_id"]]);
                $pdo->commit();

                try {
                    recordAuditEvent(
                        $pdo,
                        null,
                        'password_reset.completed',
                        'user',
                        (int) $req['user_id'],
                        ['outcome' => 'success']
                    );
                } catch (Throwable $auditException) {
                    akrabLog('error', 'password_reset_audit_failed', [
                        'exception_class' => get_class($auditException),
                    ]);
                }

                $success = "Password Anda berhasil diubah! Silakan login dengan password baru.";
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                akrabLog('error', 'password_reset_failed', [
                    'exception_class' => get_class($exception),
                ]);
                $error = "Gagal mengubah password.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Aplikasi AKRAB</title>
    <link rel="icon" href="assets/icons/icon.svg" type="image/svg+xml">
    <link href="/assets/vendor/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <script src="/assets/vendor/lucide.min.js"></script>
    <style>
        .login-bg { background: url("assets/img/bg.png") no-repeat center center fixed; background-size: cover; }
        .login-card { background: var(--bg-white); border-radius: 1rem; box-shadow: 0 10px 25px rgba(0,0,0,0.05); border: 1px solid rgba(255,255,255,0.8); backdrop-filter: blur(10px); }
    </style>
</head>
<body class="d-flex align-items-center py-4 login-bg" style="min-height: 100vh;">
<main class="form-signin w-100 m-auto animate-fade-in-up" style="max-width: 420px; padding: 15px;">
    <div class="login-card p-4 p-md-5">
        <div class="text-center mb-4">
            <div class="mb-3"><img src="assets/img/logo.png" alt="AKRAB" style="width: 80px; height: 80px; border-radius: 16px;"></div>
            <h2 class="h3 fw-bold mb-2 text-primary">Buat Password Baru</h2>
            <p class="text-muted small">Halo, <?= htmlspecialchars($req["nama"]) ?> (<?= htmlspecialchars($req["username"]) ?>). Silakan buat password baru Anda.</p>
        </div>

        <?php if ($error): ?><div class="alert alert-danger text-center small"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success text-center small mb-4"><?= htmlspecialchars($success) ?></div>
            <a href="login.php" class="w-100 btn btn-primary">Ke Halaman Login</a>
        <?php else: ?>
        <form method="POST" action="reset_password.php">
            <?= csrfInput() ?>
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="password" name="password" placeholder="Password Baru" minlength="12" maxlength="1024" autocomplete="new-password" required>
                <label for="password">Password Baru</label>
            </div>
            <div class="form-floating mb-4">
                <input type="password" class="form-control" id="confirm" name="confirm" placeholder="Ulangi Password" minlength="12" maxlength="1024" autocomplete="new-password" required>
                <label for="confirm">Ulangi Password</label>
            </div>
            <button class="w-100 btn btn-lg btn-primary rounded-pill fw-bold shadow-sm" type="submit">Ubah Password</button>
        </form>
        <?php endif; ?>
    </div>
</main>
<script src="/assets/vendor/bootstrap.bundle.min.js"></script>
<script>lucide.createIcons();</script>
</body>
</html>
