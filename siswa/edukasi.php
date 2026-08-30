<?php
require_once '../config.php';
require_once '../helpers.php';

check_role('siswa');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pusat Edukasi Anemia - AKRAB</title>
    <link href="/assets/vendor/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css?v=20260818" rel="stylesheet">
    <script src="/assets/vendor/lucide.min.js"></script>
    <style>
        .edu-card {
            transition: transform 0.3s;
        }
        .edu-card:hover {
            transform: translateY(-5px);
        }
    </style>
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
                <li class="nav-item"><a class="nav-link active fw-bold text-primary" href="edukasi.php">Edukasi</a></li>
                <li class="nav-item"><a class="nav-link" href="kuesioner.php">Kuesioner</a></li>
                <li class="nav-item"><a class="nav-link" href="konsultasi.php">Konsultasi</a></li>
                <li class="nav-item"><a class="nav-link text-danger" href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-5">
    <div class="text-center mb-5">
        <h2 class="fw-bold text-primary">Pusat Informasi Anemia</h2>
        <p class="text-muted">Kenali, Cegah, dan Tangani Anemia Sejak Dini</p>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-6">
            <div class="card shadow-sm h-100 border-0 edu-card">
                <div class="card-body p-4">
                    <h4 class="text-danger border-bottom pb-2 mb-3">Apa itu Anemia?</h4>
                    <p>Anemia adalah kondisi di mana jumlah sel darah merah atau konsentrasi hemoglobin di dalamnya lebih rendah dari biasanya. Hemoglobin sangat penting karena membawa oksigen ke seluruh tubuh.</p>
                    <p>Remaja putri sangat rentan mengalami anemia karena kehilangan darah setiap bulan akibat menstruasi, ditambah dengan kebutuhan gizi yang tinggi di masa pertumbuhan.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm h-100 border-0 edu-card">
                <div class="card-body p-4">
                    <h4 class="text-warning border-bottom pb-2 mb-3 d-flex align-items-center gap-2"><i data-lucide="alert-triangle"></i> Gejala (5L)</h4>
                    <ul class="list-unstyled fs-5">
                        <li class="mb-2 d-flex align-items-center gap-2"><i data-lucide="frown" class="text-secondary"></i> <strong>Lemah</strong> - Tubuh terasa tidak bertenaga</li>
                        <li class="mb-2 d-flex align-items-center gap-2"><i data-lucide="battery-low" class="text-secondary"></i> <strong>Letih</strong> - Mudah capek walau aktivitas ringan</li>
                        <li class="mb-2 d-flex align-items-center gap-2"><i data-lucide="thermometer" class="text-secondary"></i> <strong>Lesu</strong> - Tidak ada semangat</li>
                        <li class="mb-2 d-flex align-items-center gap-2"><i data-lucide="moon" class="text-secondary"></i> <strong>Lelah</strong> - Terasa sangat berat untuk bergerak</li>
                        <li class="mb-2 d-flex align-items-center gap-2"><i data-lucide="brain" class="text-secondary"></i> <strong>Lalai</strong> - Sering lupa dan sulit konsentrasi belajar</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-5 bg-white">
        <div class="card-body p-5">
            <h3 class="text-center mb-4 text-success">Cara Ampuh Mencegah Anemia</h3>
            <div class="row g-4">
                <div class="col-md-4 text-center">
                    <div class="mb-3 d-flex justify-content-center"><i data-lucide="pill" class="text-danger" style="width: 64px; height: 64px;"></i></div>
                    <h5>Minum TTD Teratur</h5>
                    <p class="text-muted small">Minum Tablet Tambah Darah (TTD) 1 tablet setiap minggu. Saat sedang menstruasi, minum 1 tablet setiap hari selama haid.</p>
                </div>
                <div class="col-md-4 text-center">
                    <div class="mb-3 d-flex justify-content-center"><i data-lucide="beef" class="text-danger" style="width: 64px; height: 64px;"></i></div>
                    <h5>Makan Tinggi Zat Besi</h5>
                    <p class="text-muted small">Perbanyak konsumsi hati ayam, daging merah, ikan, bayam, daun singkong, dan kacang-kacangan.</p>
                </div>
                <div class="col-md-4 text-center">
                    <div class="mb-3 d-flex justify-content-center"><i data-lucide="citrus" class="text-warning" style="width: 64px; height: 64px;"></i></div>
                    <h5>Vitamin C Sangat Penting</h5>
                    <p class="text-muted small">Bantu penyerapan zat besi dengan minum air jeruk atau makan buah-buahan tinggi Vitamin C saat makan besar.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-danger shadow-sm border-0 p-4">
        <h5 class="alert-heading fw-bold d-flex align-items-center gap-2"><i data-lucide="shield-alert"></i> Pantangan Minum TTD!</h5>
        <p class="mb-0"><strong>JANGAN</strong> meminum Tablet Tambah Darah (TTD) bersamaan dengan <strong>Teh, Kopi, atau Susu</strong>. Minuman tersebut mengandung senyawa (tanin/kalsium) yang akan menghambat penyerapan zat besi ke dalam tubuh. Gunakan air putih atau jus jeruk!</p>
    </div>
</div>

<script src="/assets/vendor/bootstrap.bundle.min.js"></script>
<script>
  lucide.createIcons();
</script>
<script src="../assets/js/app-init.js?v=20260831-safe-install"></script>
</body>
</html>
