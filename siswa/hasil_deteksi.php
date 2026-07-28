<?php
require_once '../config.php';
require_once '../helpers.php';

check_role('siswa');
$user_id = $_SESSION['user_id'];

// Get latest detection result
$stmt = $pdo->prepare("SELECT * FROM hasil_deteksi WHERE user_id = ? ORDER BY tanggal DESC, id DESC LIMIT 1");
$stmt->execute([$user_id]);
$hasil = $stmt->fetch();

if (!$hasil) {
    header("Location: kuesioner.php");
    exit;
}

// Get advice based on risk category
$kategori = canonicalRiskCategory((string) $hasil['kategori_risiko']);
$kat_anemia = adviceCategoryForRisk($kategori);

$stmt = $pdo->prepare("SELECT * FROM saran_edukasi WHERE kategori_anemia = ?");
$stmt->execute([$kat_anemia]);
$saran = $stmt->fetch();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Deteksi - AKRAB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css?v=<?= time() ?>" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light sticky-top">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">AKRAB Siswa</a>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="kuesioner.php">Kuesioner</a></li>
                <li class="nav-item"><a class="nav-link" href="konsultasi.php">Konsultasi</a></li>
                <li class="nav-item"><a class="nav-link text-danger" href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            
            <div class="card shadow-sm mb-4">
                <div class="card-body text-center p-5">
                    <h4 class="text-muted mb-4">Hasil Deteksi Risiko Anemia</h4>
                    
                    <?php 
                        $risk_class = 'risk-low';
                        $risk_label = 'RENDAH';
                        if ($kategori == 'sedang') { $risk_class = 'risk-medium'; $risk_label = 'SEDANG'; }
                        if ($kategori == 'tinggi') { $risk_class = 'risk-high'; $risk_label = 'TINGGI'; }
                        
                        // Convert probability to percentage
                        $percentage = round($hasil['probabilitas_risiko'] * 100, 1);
                    ?>
                    
                    <div class="mb-4">
                        <span class="risk-badge <?= $risk_class ?> display-6 p-3 px-5"><?= $risk_label ?></span>
                    </div>
                    
                    <p class="fs-5 mb-1">Probabilitas Risiko: <strong><?= $percentage ?>%</strong></p>
                    <p class="text-muted small">Dihitung pada: <?= date('d M Y', strtotime($hasil['tanggal'])) ?></p>
                </div>
            </div>
            
            <?php if ($saran): ?>
            <div class="card shadow-sm border-0 border-start border-5 <?= $kategori == 'tinggi' ? 'border-danger' : 'border-success' ?>">
                <div class="card-body p-4">
                    <h5 class="card-title text-primary mb-3">Saran Edukasi: <?= htmlspecialchars($saran['judul_saran']) ?></h5>
                    
                    <div class="mb-4">
                        <h6 class="fw-bold">Penjelasan:</h6>
                        <p><?= nl2br(htmlspecialchars($saran['isi_saran'])) ?></p>
                    </div>
                    
                    <div class="mb-4">
                        <h6 class="fw-bold">Rekomendasi Makanan:</h6>
                        <p><?= nl2br(htmlspecialchars($saran['rekomendasi_makanan'])) ?></p>
                    </div>
                    
                    <?php if ($saran['kapan_rujuk_ke_ahli']): ?>
                    <div class="alert alert-warning mb-0">
                        <strong>Kapan harus ke ahli?</strong><br>
                        <?= nl2br(htmlspecialchars($saran['kapan_rujuk_ke_ahli'])) ?>
                    </div>
                    <?php endif; ?>
                    
                </div>
            </div>
            <?php endif; ?>
            
            <div class="text-center mt-4">
                <a href="konsultasi.php" class="btn btn-primary btn-lg">Tanya Ahli/UKS</a>
                <a href="dashboard.php" class="btn btn-outline-secondary btn-lg ms-2">Kembali ke Dashboard</a>
            </div>
            
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/app-init.js"></script>
</body>
</html>
