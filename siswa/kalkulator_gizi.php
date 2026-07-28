<?php
require_once '../config.php';
require_once '../helpers.php';

check_role('siswa');

$bmi = null;
$status = '';
$color = '';
$saran = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['berat']) && isset($_POST['tinggi'])) {
    $berat = floatval($_POST['berat']);
    $tinggi = floatval($_POST['tinggi']) / 100; // convert cm to m
    
    if ($tinggi > 0 && $berat > 0) {
        $bmi = $berat / ($tinggi * $tinggi);
        
        if ($bmi < 18.5) {
            $status = 'Kurus (Kekurangan Berat Badan)';
            $color = 'danger';
            $saran = 'Kamu memiliki risiko tinggi terkena anemia karena kurangnya asupan nutrisi. Perbanyak konsumsi makanan tinggi zat besi dan protein seperti telur, daging merah, dan kacang-kacangan.';
        } elseif ($bmi >= 18.5 && $bmi <= 24.9) {
            $status = 'Ideal (Normal)';
            $color = 'success';
            $saran = 'Bagus sekali! Pertahankan pola makan sehatmu. Jangan lupa tetap konsumsi TTD secara teratur agar bebas anemia.';
        } elseif ($bmi >= 25 && $bmi <= 29.9) {
            $status = 'Gemuk (Kelebihan Berat Badan)';
            $color = 'warning';
            $saran = 'Perhatikan pola makanmu. Kurangi makanan manis dan berlemak, namun tetap pastikan asupan zat besimu cukup.';
        } else {
            $status = 'Obesitas';
            $color = 'danger';
            $saran = 'Segera konsultasikan dengan dokter atau ahli gizi untuk program penurunan berat badan yang aman tanpa mengurangi asupan zat gizi esensial.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kalkulator Gizi & BMI - AKRAB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css?v=<?= time() ?>" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
    <div class="container">
        <a class="navbar-brand text-white fw-bold d-flex align-items-center gap-2" href="dashboard.php">
            <i data-lucide="activity"></i> AKRAB Siswa
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-toggle="target" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link text-white" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="kuesioner.php">Skrining</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="edukasi.php">Pusat Edukasi</a></li>
                <li class="nav-item"><a class="nav-link text-white active fw-bold" href="kalkulator_gizi.php">Kalkulator Gizi</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="konsultasi.php">Konsultasi</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="profil.php">Profil</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <i data-lucide="scale" class="text-primary mb-3" style="width: 64px; height: 64px;"></i>
                        <h3 class="fw-bold text-primary">Kalkulator Status Gizi (BMI)</h3>
                        <p class="text-muted">Anemia dan kekurangan gizi sangat berkaitan erat. Cek status gizimu sekarang!</p>
                    </div>

                    <form method="POST">
                        <?= csrfInput() ?>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Berat Badan (kg)</label>
                            <input type="number" step="0.1" name="berat" class="form-control form-control-lg" required placeholder="Contoh: 50" value="<?= isset($_POST['berat']) ? htmlspecialchars($_POST['berat']) : '' ?>">
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold">Tinggi Badan (cm)</label>
                            <input type="number" step="0.1" name="tinggi" class="form-control form-control-lg" required placeholder="Contoh: 160" value="<?= isset($_POST['tinggi']) ? htmlspecialchars($_POST['tinggi']) : '' ?>">
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100 d-flex align-items-center justify-content-center gap-2">
                            <i data-lucide="calculator"></i> Hitung BMI
                        </button>
                    </form>
                </div>
            </div>

            <?php if ($bmi !== null): ?>
            <div class="card shadow border-0 border-top border-4 border-<?= $color ?>">
                <div class="card-body p-4 text-center">
                    <h5 class="text-muted mb-2">Skor BMI Kamu</h5>
                    <div class="display-3 fw-bold text-<?= $color ?> mb-2"><?= number_format($bmi, 1) ?></div>
                    <h4 class="mb-3">Status: <span class="badge bg-<?= $color ?>"><?= $status ?></span></h4>
                    <div class="alert alert-<?= $color ?> bg-opacity-10 text-start">
                        <i data-lucide="info" class="mb-2"></i><br>
                        <?= $saran ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  lucide.createIcons();
</script>
<script src="../assets/js/app-init.js"></script>
</body>
</html>
