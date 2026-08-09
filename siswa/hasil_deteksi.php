<?php
require_once '../config.php';
require_once '../helpers.php';
require_once '../views/questionnaire_analytics.php';

check_role('siswa');
$user_id = $_SESSION['user_id'];

$questionnaireRepository = new QuestionnaireAnalyticsRepository($pdo);
$questionnaireHistory = $questionnaireRepository->historyForStudent((int) $user_id);
$kuesioner = $questionnaireHistory
    ? $questionnaireHistory[array_key_last($questionnaireHistory)]
    : null;
$hasil = $questionnaireRepository->latestDetectionForStudent((int) $user_id);

if (!$hasil) {
    header("Location: kuesioner.php");
    exit;
}

// Get advice based on risk category
$kategori = canonicalRiskCategory((string) $hasil['kategori_risiko']);
$kat_anemia = adviceCategoryForRisk($kategori);

$stmt = $pdo->prepare("SELECT * FROM saran_edukasi WHERE kategori_anemia = ? AND archived_at IS NULL");
$stmt->execute([$kat_anemia]);
$saran = $stmt->fetch();
$resultPresentation = $kuesioner
    ? (new QuestionnaireResultPresenter())->forResult($kuesioner, $hasil)
    : null;

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Deteksi - AKRAB</title>
    <link href="/assets/vendor/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css?v=20260729" rel="stylesheet">
</head>
<body>
<?php renderImpersonationBanner($pdo, $_SESSION); ?>

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
        <div class="col-xl-10">
            <?php if ($resultPresentation): ?>
                <?php renderQuestionnaireResult($resultPresentation, $kuesioner); ?>
            <?php else: ?>
                <div class="alert alert-warning" role="alert">
                    Hasil risiko tersedia, tetapi rincian kuesioner aktif tidak ditemukan.
                    Silakan hubungi petugas UKS untuk pemeriksaan data.
                </div>
            <?php endif; ?>
            
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

<script src="/assets/vendor/bootstrap.bundle.min.js"></script>
<script src="../assets/js/app-init.js?v=20260729"></script>
</body>
</html>
