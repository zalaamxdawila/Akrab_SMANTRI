<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/helpers.php';

check_role('siswa');
$studentId = (int) $_SESSION['user_id'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = $_POST['email'] ?? '';
    $email = is_string($raw) ? strtolower(trim($raw)) : '';
    if (strlen($email) > 254 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $error = 'Masukkan alamat email yang valid.';
    } else {
        try {
            $statement = $pdo->prepare('UPDATE users SET email = ? WHERE id = ? AND role = \'siswa\'');
            $statement->execute([$email, $studentId]);
            recordAuditEvent($pdo, $studentId, 'student.email_completed', 'user', $studentId, [
                'actor_role' => 'siswa', 'outcome' => 'success',
            ]);
            $next = studentOnboardingDestination(studentOnboardingState($pdo, $studentId));
            header('Location: ' . BASE_URL . ($next ?? 'siswa/dashboard.php'), true, 303);
            exit;
        } catch (PDOException $exception) {
            $error = $exception->getCode() === '23000'
                ? 'Email sudah digunakan oleh akun lain.'
                : publicErrorMessage();
        }
    }
}
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Lengkapi Email - AKRAB</title><link href="/assets/vendor/bootstrap.min.css" rel="stylesheet"><link href="../assets/css/style.css?v=20260729" rel="stylesheet"></head>
<body class="bg-light"><main class="container py-5" style="max-width:620px"><section class="card border-0 shadow-sm"><div class="card-body p-4 p-md-5">
<p class="text-uppercase small fw-semibold text-primary mb-1">Langkah 1 dari 3</p><h1 class="h3">Lengkapi email akun</h1>
<p class="text-muted">Email diperlukan untuk pemulihan password dan keamanan akun. Setelah tersimpan, Anda akan diarahkan otomatis ke langkah berikutnya.</p>
<?php if ($error !== ''): ?><div class="alert alert-danger" role="alert"><?= escape_output($error) ?></div><?php endif; ?>
<form method="post"><?= csrfInput() ?><label for="email" class="form-label fw-semibold">Alamat email aktif</label>
<input id="email" name="email" type="email" maxlength="254" autocomplete="email" class="form-control form-control-lg mb-2" placeholder="nama@contoh.com" required autofocus>
<div class="form-text mb-4">Gunakan email pribadi yang dapat Anda akses.</div><button class="btn btn-primary btn-lg w-100" type="submit">Simpan dan lanjutkan</button></form>
<a class="btn btn-link text-muted w-100 mt-2" href="../logout.php">Keluar dari akun</a>
</div></section></main><script src="/assets/vendor/bootstrap.bundle.min.js"></script></body></html>
