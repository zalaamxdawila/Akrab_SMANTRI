<?php
require_once 'config.php';

$is_logged_in = isset($_SESSION['user_id']);
$dashboard_url = $is_logged_in ? ($_SESSION['role'] === 'siswa' ? 'siswa/dashboard.php' : 'uks/dashboard.php') : 'login.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AKRAB - Aplikasi Kesehatan Remaja Bebas Anemia</title>
    <!-- Bootstrap CSS -->
    <link href="/assets/vendor/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css?v=20260729" rel="stylesheet">
    <script src="/assets/vendor/lucide.min.js"></script>
    <style>
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, #3a7bd5 100%);
            color: white;
            padding: 100px 0;
            border-bottom-left-radius: 50px;
            border-bottom-right-radius: 50px;
        }
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }
    </style>
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm py-3">
    <div class="container">
        <a class="navbar-brand text-primary fw-bold fs-4 d-flex align-items-center gap-2" href="index.php">
            <i data-lucide="heart-pulse" class="text-danger"></i> AKRAB
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item"><a class="nav-link" href="#tentang">Tentang</a></li>
                <li class="nav-item"><a class="nav-link" href="#fitur">Fitur</a></li>
                <?php if ($is_logged_in): ?>
                    <li class="nav-item ms-lg-3"><a class="btn btn-primary px-4 rounded-pill" href="<?= $dashboard_url ?>">Ke Dashboard</a></li>
                <?php else: ?>
                    <li class="nav-item ms-lg-3"><a class="btn btn-outline-primary px-4 rounded-pill me-2" href="login.php">Masuk</a></li>
                    <li class="nav-item mt-2 mt-lg-0"><a class="btn btn-primary px-4 rounded-pill" href="register.php">Daftar</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<header class="hero-section text-center">
    <div class="container">
        <h1 class="display-4 fw-bold mb-4">Wujudkan Generasi Remaja Cerdas, Sehat, Bebas Anemia!</h1>
        <p class="lead mb-4 px-md-5">AKRAB (Aplikasi Kesehatan Remaja Bebas Anemia) hadir untuk mendampingi sekolah dalam memantau kesehatan siswa dan mengingatkan konsumsi Tablet Tambah Darah (TTD).</p>
        <div class="alert alert-warning mx-auto mb-5 text-start" style="max-width: 760px;" role="alert">
            <strong>Status prototipe:</strong> fitur skrining risiko anemia sedang dinonaktifkan sampai model selesai divalidasi tenaga kesehatan. AKRAB bukan alat diagnosis dan tidak menggantikan pemeriksaan tenaga medis.
        </div>
        
        <?php if ($is_logged_in): ?>
            <a href="<?= $dashboard_url ?>" class="btn btn-light text-primary btn-lg px-5 rounded-pill shadow-sm fw-bold">Kembali ke Dashboard</a>
        <?php else: ?>
            <a href="register.php" class="btn btn-light text-primary btn-lg px-5 rounded-pill shadow-sm fw-bold me-3">Mulai Sekarang</a>
            <a href="login.php" class="btn btn-outline-light btn-lg px-5 rounded-pill">Masuk</a>
        <?php endif; ?>
    </div>
</header>

<!-- Tentang Section -->
<section id="tentang" class="py-5 mt-4">
    <div class="container text-center">
        <h2 class="fw-bold mb-4">Mengapa AKRAB Penting?</h2>
        <div class="row justify-content-center">
            <div class="col-md-8">
                <p class="fs-5 text-muted">Anemia pada remaja, khususnya remaja putri, merupakan masalah serius di Indonesia yang dapat menurunkan konsentrasi belajar, menghambat pertumbuhan, dan menyebabkan gejala 5L (Lemah, Letih, Lesu, Lelah, Lalai). AKRAB adalah jembatan pintar antara siswa dan Unit Kesehatan Sekolah (UKS) untuk menuntaskan masalah ini bersama-sama.</p>
            </div>
        </div>
    </div>
</section>

<!-- Fitur Section -->
<section id="fitur" class="py-5 bg-white">
    <div class="container">
        <h2 class="fw-bold text-center mb-5">Fitur Unggulan</h2>
        <div class="row g-4 text-center">
            <div class="col-md-4">
                <div class="p-4">
                    <div class="feature-icon d-flex justify-content-center"><i data-lucide="brain-circuit" style="width: 48px; height: 48px;"></i></div>
                    <h4 class="fw-bold">Deteksi Dini Cerdas</h4>
                    <p class="text-muted">Menggunakan perhitungan algoritma untuk memprediksi tingkat risiko anemia berdasarkan pola hidup, gejala klinis, dan hasil laboratorium.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-4">
                    <div class="feature-icon d-flex justify-content-center"><i data-lucide="alarm-clock" style="width: 48px; height: 48px;"></i></div>
                    <h4 class="fw-bold">Pengingat Otomatis TTD</h4>
                    <p class="text-muted">Sistem akan mengingatkan siswa secara berkala untuk meminum Tablet Tambah Darah, dilengkapi grafik kepatuhan untuk UKS.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-4">
                    <div class="feature-icon d-flex justify-content-center"><i data-lucide="message-square-heart" style="width: 48px; height: 48px;"></i></div>
                    <h4 class="fw-bold">Konsultasi Terpadu</h4>
                    <p class="text-muted">Siswa yang ragu dengan kondisi kesehatannya dapat langsung berkonsultasi dua arah secara pribadi dengan petugas UKS.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="py-5 text-center">
    <div class="container">
        <h2 class="fw-bold mb-3">Siap Mendukung Kesehatan Remaja?</h2>
        <p class="text-muted mb-4">Bergabunglah dengan AKRAB dan jadilah bagian dari revolusi kesehatan sekolah digital.</p>
        <?php if (!$is_logged_in): ?>
            <a href="register.php" class="btn btn-primary btn-lg px-5 rounded-pill mb-2">Daftar Gratis</a>
        <?php endif; ?>
        <a href="Panduan_Instalasi_PWA_AKRAB.pdf" target="_blank" class="btn btn-outline-dark btn-lg px-4 rounded-pill mb-2 d-inline-flex align-items-center gap-2">
            <i data-lucide="smartphone"></i> Panduan Instalasi Aplikasi (PWA)
        </a>
    </div>
</section>

<!-- Footer -->
<footer class="bg-dark text-white text-center py-4 mt-auto">
    <div class="container">
        <p class="mb-0">&copy; <?= date('Y') ?> Aplikasi AKRAB. Hak Cipta Dilindungi.</p>
    </div>
</footer>

<script src="/assets/vendor/bootstrap.bundle.min.js"></script>
<script>
  lucide.createIcons();
</script>
<script src="assets/js/app-init.js?v=20260729"></script>
</body>
</html>
