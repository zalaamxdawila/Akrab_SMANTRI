<?php
require_once '../config.php';
require_once '../helpers.php';

check_role('uks');

if (!isset($_GET['id'])) {
    header("Location: data_siswa.php");
    exit;
}

$siswa_id = (int)$_GET['id'];

// 1. Fetch User Data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'siswa'");
$stmt->execute([$siswa_id]);
$siswa = $stmt->fetch();

if (!$siswa) {
    die("Data siswa tidak ditemukan.");
}
recordAuditEvent($pdo, (int) $_SESSION['user_id'], 'health_record.viewed', 'student', $siswa_id, ['outcome' => 'success', 'actor_role' => 'uks']);
akrabLog('info', 'health_record_viewed', ['outcome' => 'success', 'target_type' => 'student', 'actor_role' => 'uks']);

// 2. Fetch Latest Kuesioner
$stmt = $pdo->prepare("SELECT * FROM kuesioner WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$siswa_id]);
$kuesioner = $stmt->fetch();

// 3. Fetch Latest Deteksi
$stmt = $pdo->prepare("SELECT * FROM hasil_deteksi WHERE user_id = ? ORDER BY tanggal DESC, id DESC LIMIT 1");
$stmt->execute([$siswa_id]);
$hasil = $stmt->fetch();

// 4. Fetch TTD Consumption
$stmt = $pdo->prepare("SELECT * FROM konsumsi_ttd WHERE user_id = ? ORDER BY tanggal DESC LIMIT 10");
$stmt->execute([$siswa_id]);
$ttd_logs = $stmt->fetchAll();

// 5. Total TTD
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM konsumsi_ttd WHERE user_id = ? AND status_konsumsi = 'sudah'");
$stmt->execute([$siswa_id]);
$total_ttd = $stmt->fetch()['total'];

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Siswa - AKRAB UKS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css?v=20260729" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
    <div class="container">
        <a class="navbar-brand text-white fw-bold" href="dashboard.php">AKRAB UKS Panel</a>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link text-white" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link text-white active" href="data_siswa.php">Data Siswa</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="jawab_konsultasi.php">Konsultasi</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Detail Rekam Medis: <?= htmlspecialchars($siswa['nama']) ?></h3>
        <a href="data_siswa.php" class="btn btn-outline-secondary">Kembali</a>
    </div>

    <div class="row g-4">
        <!-- Kolom Kiri: Profil & Risiko -->
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white fw-bold">Profil Siswa</div>
                <div class="card-body">
                    <p class="mb-1"><strong>Nama:</strong> <?= htmlspecialchars($siswa['nama']) ?></p>
                    <p class="mb-1"><strong>Username:</strong> @<?= htmlspecialchars($siswa['username']) ?></p>
                    <p class="mb-1"><strong>Kelas:</strong> <?= htmlspecialchars($siswa['kelas']) ?></p>
                    <?php if ($kuesioner): ?>
                        <p class="mb-1"><strong>Tgl Lahir:</strong> <?= $kuesioner['tanggal_lahir'] ? date('d M Y', strtotime($kuesioner['tanggal_lahir'])) : '-' ?></p>
                        <p class="mb-1"><strong>Alamat:</strong> <?= htmlspecialchars($kuesioner['alamat'] ?? '-') ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white fw-bold">Status Risiko Terakhir</div>
                <div class="card-body text-center">
                    <?php if ($hasil): ?>
                        <?php 
                            $badge = 'bg-success';
                            if ($hasil['kategori_risiko'] == 'sedang') $badge = 'bg-warning text-dark';
                            if ($hasil['kategori_risiko'] == 'tinggi') $badge = 'bg-danger';
                        ?>
                        <span class="badge <?= $badge ?> fs-3 p-3 w-100 mb-3"><?= strtoupper($hasil['kategori_risiko']) ?></span>
                        <p class="mb-1 fs-5">Probabilitas: <strong><?= round($hasil['probabilitas_risiko'] * 100, 1) ?>%</strong></p>
                        <small class="text-muted">Diperbarui: <?= date('d M Y', strtotime($hasil['tanggal'])) ?></small>
                    <?php else: ?>
                        <p class="text-muted my-3">Siswa belum mengisi kuesioner</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white fw-bold">Kepatuhan Minum TTD</div>
                <div class="card-body">
                    <h1 class="display-6 text-center text-success mb-3"><?= $total_ttd ?> Kali</h1>
                    <h6 class="border-bottom pb-2">Riwayat 10 Konsumsi Terakhir:</h6>
                    <?php if (empty($ttd_logs)): ?>
                        <p class="text-muted small">Belum ada data minum TTD.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush small">
                            <?php foreach ($ttd_logs as $log): ?>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span><?= date('d M Y', strtotime($log['tanggal'])) ?></span>
                                    <span class="badge bg-success">Sudah</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Rincian Kuesioner -->
        <div class="col-md-8">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-bold d-flex justify-content-between">
                    <span>Data Kuesioner Skrining</span>
                    <?php if ($kuesioner): ?>
                        <small class="text-muted">Tgl Isi: <?= date('d M Y', strtotime($kuesioner['created_at'])) ?></small>
                    <?php endif; ?>
                </div>
                <div class="card-body p-4">
                    <?php if (!$kuesioner): ?>
                        <div class="text-center py-5">
                            <h4 class="text-muted">Data Kuesioner Kosong</h4>
                        </div>
                    <?php else: ?>
                        <h5 class="text-primary border-bottom pb-2 mb-3">1. Data Laboratorium (Kaggle Features)</h5>
                        <div class="row mb-4">
                            <div class="col-6 col-md-3"><strong>Hemoglobin (Hb):</strong><br><?= $kuesioner['kadar_hb'] ? $kuesioner['kadar_hb'].' gr%' : '<span class="text-muted">-</span>' ?></div>
                            <div class="col-6 col-md-3"><strong>MCHC:</strong><br><?= $kuesioner['kadar_mchc'] ? $kuesioner['kadar_mchc'] : '<span class="text-muted">-</span>' ?></div>
                            <div class="col-6 col-md-3"><strong>MCV:</strong><br><?= $kuesioner['kadar_mcv'] ? $kuesioner['kadar_mcv'] : '<span class="text-muted">-</span>' ?></div>
                            <div class="col-6 col-md-3"><strong>MCH:</strong><br><?= $kuesioner['kadar_mch'] ? $kuesioner['kadar_mch'] : '<span class="text-muted">-</span>' ?></div>
                        </div>

                        <h5 class="text-primary border-bottom pb-2 mb-3">2. Skor Survei Kesehatan</h5>
                        <div class="row mb-4">
                            <div class="col-md-6 mb-3">
                                <div class="p-3 bg-light rounded border">
                                    <h6 class="mb-2">Gejala Klinis</h6>
                                    <h3 class="mb-0 text-danger"><?= $kuesioner['skor_gejala'] ?> <small class="text-muted fs-6">/ 100</small></h3>
                                    <small class="text-muted">Makin tinggi makin berisiko.</small>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="p-3 bg-light rounded border">
                                    <h6 class="mb-2">Pola Makan</h6>
                                    <h3 class="mb-0 text-success"><?= $kuesioner['skor_makan'] ?> <small class="text-muted fs-6">/ 18</small></h3>
                                    <small class="text-muted">Makin tinggi makin baik.</small>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="p-3 bg-light rounded border">
                                    <h6 class="mb-2">Pengetahuan Anemia</h6>
                                    <h3 class="mb-0 text-info"><?= $kuesioner['skor_pengetahuan'] ?></h3>
                                    <small class="text-muted">Total poin pengetahuan dasar.</small>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="p-3 bg-light rounded border">
                                    <h6 class="mb-2">Sikap & Pandangan</h6>
                                    <h3 class="mb-0 text-secondary"><?= $kuesioner['skor_sikap'] ?> <small class="text-muted fs-6">/ 40</small></h3>
                                    <small class="text-muted">Tingkat kesadaran bahaya anemia.</small>
                                </div>
                            </div>
                        </div>

                        <h5 class="text-primary border-bottom pb-2 mb-3">3. Riwayat Menstruasi</h5>
                        <div class="row mb-4">
                            <div class="col-md-4"><strong>Sudah Menstruasi:</strong><br><?= ucfirst($kuesioner['mens_sudah'] ?? '-') ?></div>
                            <div class="col-md-4"><strong>Siklus Teratur:</strong><br><?= ucfirst($kuesioner['mens_teratur'] ?? '-') ?></div>
                            <div class="col-md-4"><strong>Lama Hari:</strong><br><?= $kuesioner['mens_lama_hari'] ? $kuesioner['mens_lama_hari'].' Hari' : '-' ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/app-init.js?v=20260729"></script>
</body>
</html>
