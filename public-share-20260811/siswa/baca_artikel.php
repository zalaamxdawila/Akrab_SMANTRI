<?php
require_once '../config.php';
require_once '../helpers.php';

check_role('siswa');

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit;
}

$id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM artikel_edukasi WHERE id = ?");
$stmt->execute([$id]);
$artikel = $stmt->fetch();

if (!$artikel) {
    die("Artikel tidak ditemukan. <a href='dashboard.php'>Kembali ke Dashboard</a>");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($artikel['judul']) ?> - AKRAB</title>
    <link href="/assets/vendor/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css?v=20260729" rel="stylesheet">
    <script src="/assets/vendor/lucide.min.js"></script>
</head>
<body class="bg-light">
<?php renderImpersonationBanner($pdo, $_SESSION); ?>

<nav class="navbar navbar-expand-lg navbar-light sticky-top bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand text-primary fw-bold" href="dashboard.php">AKRAB Siswa</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="edukasi.php">Edukasi</a></li>
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
            <div class="mb-4">
                <a href="dashboard.php" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                    <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i> Kembali ke Dashboard
                </a>
            </div>
            
            <div class="card shadow-sm border-0">
                <div class="card-body p-4 p-md-5">
                    <h2 class="fw-bold text-dark mb-3"><?= htmlspecialchars($artikel['judul']) ?></h2>
                    <div class="d-flex align-items-center text-muted mb-4 pb-3 border-bottom">
                        <i data-lucide="calendar" class="me-2" style="width: 18px; height: 18px;"></i>
                        <span>Dipublikasikan pada: <?= date('d M Y, H:i', strtotime($artikel['tanggal_publikasi'])) ?></span>
                    </div>
                    
                    <div class="article-content lh-lg" style="font-size: 1.1rem; color: #444;">
                        <?= nl2br(htmlspecialchars($artikel['konten'])) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/assets/vendor/bootstrap.bundle.min.js"></script>
<script>
  lucide.createIcons();
</script>
<script src="../assets/js/app-init.js?v=20260729"></script>
</body>
</html>
