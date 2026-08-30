<?php
require_once 'config.php';

$is_logged_in = isset($_SESSION['user_id']);
$dashboard_url = $is_logged_in
    ? dashboardForRole((string) $_SESSION['role'])
    : 'login.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AKRAB — Aplikasi Kesehatan Remaja Bebas Anemia</title>
    <link rel="icon" href="assets/icons/icon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/assets/icons/apple-touch-icon.png">
    <link rel="manifest" href="/manifest.json?v=20260831-safe-install">
    <meta name="theme-color" content="#047857">
    <link href="/assets/vendor/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css?v=20260818" rel="stylesheet">
    <script src="/assets/vendor/lucide.min.js"></script>
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold fs-4 d-flex align-items-center gap-2" href="index.php">
            <i data-lucide="heart-pulse" style="width: 24px; height: 24px;"></i> AKRAB
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item"><a class="nav-link" href="#tentang">Tentang</a></li>
                <li class="nav-item"><a class="nav-link" href="#fitur">Fitur</a></li>
                <li class="nav-item"><a class="nav-link" href="#cara-kerja">Cara Kerja</a></li>
                <?php if ($is_logged_in): ?>
                    <li class="nav-item ms-lg-3"><a class="btn btn-primary px-4 rounded-pill" href="<?= $dashboard_url ?>">Dashboard</a></li>
                <?php else: ?>
                    <li class="nav-item ms-lg-3"><a class="btn btn-outline-primary px-4 rounded-pill" href="login.php">Masuk</a></li>
                    <li class="nav-item ms-2"><a class="btn btn-primary px-4 rounded-pill" href="register.php">Daftar</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<header class="hero-section text-center">
    <div class="container">
        <div class="mb-3">
            <span class="badge bg-white text-primary px-3 py-2 rounded-pill fw-bold shadow-sm" style="font-size: 0.85rem;">
                <i data-lucide="sparkles" style="width: 14px; height: 14px; display: inline;"></i> SMAN 3 Padang Health Platform
            </span>
        </div>
        <h1 class="mb-4">Wujudkan Generasi Remaja<br>Cerdas & Sehat Bebas Anemia</h1>
        <p class="lead mb-5">AKRAB mendampingi sekolah dalam memantau kesehatan siswa, mendeteksi risiko anemia secara dini, dan mengingatkan konsumsi Tablet Tambah Darah (TTD) secara berkala.</p>

        <?php if ($is_logged_in): ?>
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <a href="<?= $dashboard_url ?>" class="btn btn-light btn-lg px-5 rounded-pill shadow fw-bold text-primary">
                    <i data-lucide="layout-dashboard" style="width: 20px;"></i> Buka Dashboard
                </a>
                <button type="button" class="btn btn-outline-light btn-lg px-4 rounded-pill" data-install-app hidden>
                    <i data-lucide="download" style="width: 20px;"></i> Pasang Aplikasi
                </button>
            </div>
        <?php else: ?>
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <a href="register.php" class="btn btn-light btn-lg px-5 rounded-pill shadow fw-bold text-primary">
                    <i data-lucide="user-plus" style="width: 20px;"></i> Daftar Gratis
                </a>
                <a href="login.php" class="btn btn-outline-light btn-lg px-5 rounded-pill">
                    <i data-lucide="log-in" style="width: 20px;"></i> Masuk
                </a>
                <button type="button" class="btn btn-outline-light btn-lg px-4 rounded-pill" data-install-app hidden>
                    <i data-lucide="download" style="width: 20px;"></i> Pasang Aplikasi
                </button>
            </div>
        <?php endif; ?>
        <p class="small text-white mt-3 mb-0" data-install-status role="status" aria-live="polite"></p>
        <aside class="alert alert-light text-start mx-auto mt-3 mb-0" style="max-width: 32rem;" data-install-help hidden aria-labelledby="installHelpTitle">
            <div class="d-flex justify-content-between gap-3">
                <div>
                    <h2 class="h6 fw-bold text-dark" id="installHelpTitle" tabindex="-1">Pasang AKRAB di iPhone atau iPad</h2>
                    <ol class="small text-dark mb-0 ps-3">
                        <li>Ketuk tombol <strong>Bagikan</strong> di Safari.</li>
                        <li>Pilih <strong>Tambahkan ke Layar Utama</strong>.</li>
                        <li>Ketuk <strong>Tambah</strong> untuk menyelesaikan.</li>
                    </ol>
                </div>
                <button type="button" class="btn-close" data-install-help-close aria-label="Tutup petunjuk instalasi"></button>
            </div>
        </aside>
    </div>
</header>

<!-- Stats Bar -->
<section class="container" style="margin-top: -45px; position: relative; z-index: 2;">
    <div class="stats-bar shadow-lg">
        <div class="row g-0">
            <div class="col-6 col-md-3">
                <div class="stat-item">
                    <div class="stat-number">3<span class="text-primary">Fitur</span></div>
                    <div class="stat-label">Unggulan Utama</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-item border-start border-end border-bottom border-md-0" style="border-color: var(--border-color) !important;">
                    <div class="stat-number">10<span class="text-primary">+</span></div>
                    <div class="stat-label">Pertanyaan Skrining</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-item border-bottom border-md-0" style="border-color: var(--border-color) !important;">
                    <div class="stat-number">24/7</div>
                    <div class="stat-label">Monitoring Kesehatan</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-item border-start border-bottom border-md-0" style="border-color: var(--border-color) !important;">
                    <div class="stat-number">100<span class="text-primary">%</span></div>
                    <div class="stat-label">Gratis & Aman</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Tentang Section -->
<section id="tentang" class="py-5 mt-5">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-bold mb-3" style="font-size: 0.8rem;">TENTANG AKRAB</span>
                <h2 class="fw-bold mb-3" style="font-size: 2rem;">Mengapa AKRAB Penting?</h2>
                <p class="text-muted mb-4" style="font-size: 1.05rem; line-height: 1.8;">
                    Anemia pada remaja merupakan masalah serius di Indonesia yang dapat menurunkan konsentrasi belajar,
                    menghambat pertumbuhan, dan menyebabkan gejala <strong>5L</strong> — Lemah, Letih, Lesu, Lelah, dan Lalai.
                </p>
                <p class="text-muted mb-4" style="font-size: 1.05rem; line-height: 1.8;">
                    AKRAB adalah jembatan pintar antara siswa dan Unit Kesehatan Sekolah (UKS) untuk mendeteksi dini,
                    memantau, dan mencegah anemia bersama-sama.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle bg-primary bg-opacity-10 p-2"><i data-lucide="shield-check" style="width: 20px; height: 20px; color: var(--primary-color);"></i></div>
                        <span class="fw-semibold small">Data Tercatat Aman</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle bg-primary bg-opacity-10 p-2"><i data-lucide="smartphone" style="width: 20px; height: 20px; color: var(--primary-color);"></i></div>
                        <span class="fw-semibold small">Akses dari HP</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle bg-primary bg-opacity-10 p-2"><i data-lucide="bell-ring" style="width: 20px; height: 20px; color: var(--primary-color);"></i></div>
                        <span class="fw-semibold small">Pengingat TTD</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <div class="p-4 rounded-4" style="background: linear-gradient(135deg, rgba(16,185,129,0.08), rgba(59,130,246,0.05));">
                    <i data-lucide="heart-pulse" style="width: 120px; height: 120px; color: var(--primary-color); opacity: 0.8;" class="animate-float"></i>
                    <div class="mt-3">
                        <span class="badge bg-primary text-white px-3 py-2 rounded-pill fw-bold">SMAN 3 Padang</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Fitur Section -->
<section id="fitur" class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-bold mb-3" style="font-size: 0.8rem;">FITUR UNGGULAN</span>
            <h2 class="fw-bold" style="font-size: 2rem;">Kemampuan AKRAB</h2>
            <p class="text-muted mx-auto" style="max-width: 560px;">Semua yang dibutuhkan untuk skrining dan pencegahan anemia tersedia dalam satu aplikasi.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon-wrap">
                        <i data-lucide="brain-circuit" style="width: 36px; height: 36px;"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Skrining Dini Sederhana</h5>
                    <p class="text-muted mb-0 small" style="line-height: 1.7;">AKRAB melakukan skrining bertahap berdasarkan gejala dan faktor risiko tanpa mewajibkan pemeriksaan Hb. Hasil merupakan indikasi risiko, bukan diagnosis, dan disertai saran tindak lanjut.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon-wrap">
                        <i data-lucide="alarm-clock" style="width: 36px; height: 36px;"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Pengingat TTD Otomatis</h5>
                    <p class="text-muted mb-0 small" style="line-height: 1.7;">Sistem mengingatkan siswa secara berkala untuk minum Tablet Tambah Darah, dilengkapi grafik kepatuhan untuk pemantauan UKS.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon-wrap">
                        <i data-lucide="message-square-heart" style="width: 36px; height: 36px;"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Konsultasi Terpadu</h5>
                    <p class="text-muted mb-0 small" style="line-height: 1.7;">Siswa dapat berkonsultasi dua arah secara pribadi dengan petugas UKS tentang kondisi kesehatannya kapan saja.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Cara Kerja -->
<section id="cara-kerja" class="py-5 bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-bold mb-3" style="font-size: 0.8rem;">CARA KERJA</span>
            <h2 class="fw-bold" style="font-size: 2rem;">Langkah Mudah Memulai</h2>
        </div>
        <div class="row g-4 text-center">
            <div class="col-md-3">
                <div class="step-number">1</div>
                <h6 class="fw-bold">Daftar Akun</h6>
                <p class="text-muted small mb-0">Buat akun siswa dengan NISN dan nama lengkap Anda.</p>
            </div>
            <div class="col-md-3">
                <div class="step-number">2</div>
                <h6 class="fw-bold">Isi Kuesioner</h6>
                <p class="text-muted small mb-0">Jawab pertanyaan skrining tentang gejala, pola makan, dan faktor risiko.</p>
            </div>
            <div class="col-md-3">
                <div class="step-number">3</div>
                <h6 class="fw-bold">Lihat Hasil</h6>
                <p class="text-muted small mb-0">Dapatkan analisis risiko anemia dan saran tindak lanjut dari UKS.</p>
            </div>
            <div class="col-md-3">
                <div class="step-number">4</div>
                <h6 class="fw-bold">Pantau & Patuhi</h6>
                <p class="text-muted small mb-0">Catat konsumsi TTD harian dan pantau perkembangan kesehatan Anda.</p>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="py-5">
    <div class="container">
        <div class="cta-section text-center p-5 p-md-4">
            <h2 class="fw-bold mb-3 text-white" style="font-size: 1.75rem; position: relative;">Siap Mendukung Kesehatan Remaja?</h2>
            <p class="mb-4 opacity-90 text-white" style="max-width: 520px; margin: 0 auto; position: relative;">Bergabunglah dengan AKRAB dan jadilah bagian dari revolusi kesehatan sekolah digital.</p>
            <div class="d-flex flex-wrap justify-content-center gap-3" style="position: relative;">
                <?php if (!$is_logged_in): ?>
                    <a href="register.php" class="btn btn-light btn-lg px-5 rounded-pill fw-bold text-primary shadow">
                        <i data-lucide="user-plus" style="width: 20px;"></i> Daftar Gratis
                    </a>
                <?php endif; ?>
                <a href="Panduan_Instalasi_PWA_AKRAB.pdf" target="_blank" class="btn btn-outline-light btn-lg px-4 rounded-pill">
                    <i data-lucide="smartphone" style="width: 20px;"></i> Panduan PWA
                </a>
                <a href="/downloads/AKRAB-Android-v1.0.0.apk" download class="btn btn-light btn-lg px-4 rounded-pill fw-bold text-primary shadow" aria-describedby="apkSecurityNote">
                    <i data-lucide="download" style="width: 20px;"></i> Download APK Android
                </a>
                <a href="/downloads/AKRAB-Android-v1.0.0.apk.sha256" download class="btn btn-outline-light btn-lg px-4 rounded-pill">
                    <i data-lucide="shield-check" style="width: 20px;"></i> Checksum APK
                </a>
            </div>
            <p class="small text-white opacity-75 mt-3 mb-0" id="apkSecurityNote" style="position: relative;">
                APK resmi v1.0.0 ditandatangani dan hanya memuat situs HTTPS AKRAB. Pembaruan isi aplikasi mengikuti website.
            </p>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="site-footer py-4 mt-auto">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-2">
                <i data-lucide="heart-pulse" style="width: 20px; height: 20px; color: var(--primary-color);"></i>
                <span class="fw-bold text-white">AKRAB</span>
                <span class="small opacity-75">— SMAN 3 Padang</span>
            </div>
            <p class="mb-0 small opacity-60">&copy; <?= date('Y') ?> Aplikasi AKRAB. Hak Cipta Dilindungi.</p>
        </div>
    </div>
</footer>

<script src="/assets/vendor/bootstrap.bundle.min.js"></script>
<script>lucide.createIcons();</script>
<script src="assets/js/app-init.js?v=20260831-safe-install"></script>
</body>
</html>
