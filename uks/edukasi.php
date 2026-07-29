<?php
require_once '../config.php';
require_once '../helpers.php';

check_role('uks');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOP Penanganan Anemia - AKRAB UKS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css?v=20260729" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
    <div class="container">
        <a class="navbar-brand text-white fw-bold" href="dashboard.php">AKRAB UKS Panel</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-toggle="target" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link text-white" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="data_siswa.php">Data Siswa</a></li>
                <li class="nav-item"><a class="nav-link text-white active fw-bold" href="edukasi.php">SOP Penanganan</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="jawab_konsultasi.php">Konsultasi</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Modul SOP Penanganan Anemia (Petugas UKS)</h3>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-start border-success border-5 h-100">
                <div class="card-body">
                    <h5 class="card-title fw-bold text-success">Kategori RENDAH</h5>
                    <p class="text-muted small">Tindakan preventif dan pemeliharaan.</p>
                    <hr>
                    <ul class="small">
                        <li>Pastikan siswa tetap minum TTD 1x Seminggu.</li>
                        <li>Berikan edukasi pola makan sehat bergizi seimbang.</li>
                        <li>Anjurkan olahraga rutin dan istirahat cukup.</li>
                        <li>Monitoring pengisian kuesioner bulan depan.</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-start border-warning border-5 h-100">
                <div class="card-body">
                    <h5 class="card-title fw-bold text-warning text-dark">Kategori SEDANG</h5>
                    <p class="text-muted small">Intervensi dini dan observasi.</p>
                    <hr>
                    <ul class="small">
                        <li>Panggil siswa ke UKS untuk wawancara singkat mengenai gejala yang dialami.</li>
                        <li>Periksa konjungtiva mata, telapak tangan, dan wajah (pucat).</li>
                        <li>Tekankan kepatuhan minum TTD. Jika sedang haid, pantau agar minum setiap hari.</li>
                        <li>Sarankan orang tua untuk meningkatkan asupan protein hewani.</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-start border-danger border-5 h-100">
                <div class="card-body">
                    <h5 class="card-title fw-bold text-danger">Kategori TINGGI</h5>
                    <p class="text-muted small">Tindakan kuratif dan rujukan segera.</p>
                    <hr>
                    <ul class="small">
                        <li><strong>RUJUK KE PUSKESMAS/DOKTER:</strong> Berikan surat pengantar pemeriksaan Lab darah (Hemoglobin).</li>
                        <li>Hubungi orang tua siswa terkait status risiko tinggi ini.</li>
                        <li>Berikan pendampingan psikologis (agar siswa tidak panik).</li>
                        <li>Pantau ketat kepatuhan minum TTD dan laporkan progres mingguannya.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-5 shadow-sm border-0">
        <div class="card-header bg-white fw-bold">Panduan Komunikasi Edukasi (KIE) untuk Siswa</div>
        <div class="card-body">
            <h6>1. Pendekatan Personal</h6>
            <p class="small text-muted mb-4">Jangan memarahi siswa yang lupa minum TTD. Gunakan pendekatan "Kenapa lupa?" dan berikan solusi (misal: set alarm di HP).</p>
            
            <h6>2. Meluruskan Mitos TTD</h6>
            <p class="small text-muted mb-4">Banyak siswa takut minum TTD karena mitos "bikin gemuk" atau "bikin tekanan darah tinggi". Edukasi bahwa TTD murni berisi zat besi untuk pembentukan sel darah merah, bukan obat penambah berat badan atau darah tinggi.</p>

            <h6>3. Manajemen Efek Samping</h6>
            <p class="small text-muted mb-0">Jika siswa mengeluh mual setelah minum TTD, anjurkan minum setelah makan malam menjelang tidur, atau diminum bersama jus jeruk (vitamin C) untuk menekan rasa mual dan memaksimalkan penyerapan.</p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/app-init.js?v=20260729"></script>
</body>
</html>
