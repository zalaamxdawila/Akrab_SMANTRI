<?php
require_once '../config.php';
require_once '../helpers.php';
require_once '../views/questionnaire_analytics.php';

check_role('orangtua');
$parent_id = $_SESSION['user_id'];

$anak = null;
$kuesioner = null;
$hasil = null;
$kepatuhan = [];
$questionnaireHistory = [];
$resultPresentation = null;
$historyChart = ['labels' => [], 'series' => []];

$questionnaireRepository = new QuestionnaireAnalyticsRepository($pdo);
$questionnaireInsights = new QuestionnaireInsights();
$anak = $questionnaireRepository->approvedStudentForParent((int) $parent_id);
    
if ($anak) {
    $anak_id = (int) $anak['id'];
    $questionnaireHistory = $questionnaireRepository->historyForStudent($anak_id);
    $kuesioner = $questionnaireHistory
        ? $questionnaireHistory[array_key_last($questionnaireHistory)]
        : null;
    $historyChart = $questionnaireInsights->historyChart($questionnaireHistory);
        
    // Latest Risk Detection
    $hasil = $questionnaireRepository->latestDetectionForStudent(
        $anak_id,
        (int) ($kuesioner['id'] ?? 0)
    );
    $resultPresentation = $kuesioner
        ? (new QuestionnaireResultPresenter())->forResult($kuesioner, $hasil)
        : null;
        
    // TTD History (Last 5)
    $stmt = $pdo->prepare("SELECT * FROM konsumsi_ttd WHERE user_id = ? ORDER BY tanggal DESC LIMIT 5");
    $stmt->execute([$anak_id]);
    $kepatuhan = $stmt->fetchAll();
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Orang Tua - AKRAB</title>
    <link href="/assets/vendor/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css?v=20260729" rel="stylesheet">
    <script src="/assets/vendor/chart.umd.min.js"></script>
    <script src="/assets/vendor/lucide.min.js"></script>
</head>
<body class="bg-light">
<?php renderImpersonationBanner($pdo, $_SESSION); ?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand text-white fw-bold d-flex align-items-center gap-2" href="dashboard.php">
            <i data-lucide="users"></i> AKRAB Parent Portal
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link text-white fw-bold" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
        <div>
            <h3 class="fw-bold mb-1">Halo, Bapak/Ibu <?= htmlspecialchars($_SESSION['nama']) ?></h3>
            <p class="text-muted mb-0">Selamat datang di Pusat Pemantauan Kesehatan Anak.</p>
        </div>
    </div>

    <?php if (!$anak): ?>
        <div class="alert alert-warning d-flex align-items-center gap-3 shadow-sm border-0">
            <i data-lucide="alert-triangle" style="width: 32px; height: 32px;"></i>
            <div>
                <h5 class="fw-bold mb-1">Tautan Belum Disetujui</h5>
                <p class="mb-0">Data kesehatan anak baru tersedia setelah petugas UKS memverifikasi hubungan orang tua atau wali dengan siswa.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <!-- Profil Anak -->
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body text-center p-4">
                        <div class="bg-primary rounded-circle d-inline-flex justify-content-center align-items-center mb-3 text-white shadow-sm" style="width: 80px; height: 80px;">
                            <i data-lucide="user" style="width: 40px; height: 40px;"></i>
                        </div>
                        <h4 class="fw-bold mb-1"><?= htmlspecialchars($anak['nama']) ?></h4>
                        <p class="text-muted mb-3">Kelas: <?= htmlspecialchars($anak['kelas']) ?> | NISN: <?= htmlspecialchars($anak['username']) ?></p>
                        
                        <?php
                            $risk_badge = 'bg-secondary';
                            $risk_text = 'Belum Ada Data';
                            if ($hasil) {
                                if ($hasil['kategori_risiko'] == 'tinggi') { $risk_badge = 'bg-danger'; $risk_text = 'Risiko Tinggi Anemia'; }
                                elseif ($hasil['kategori_risiko'] == 'sedang') { $risk_badge = 'bg-warning text-dark'; $risk_text = 'Risiko Sedang'; }
                                else { $risk_badge = 'bg-success'; $risk_text = 'Risiko Rendah'; }
                            }
                        ?>
                        <div class="badge <?= $risk_badge ?> fs-6 p-2 rounded-pill shadow-sm w-100">Status: <?= $risk_text ?></div>
                    </div>
                </div>
            </div>

            <!-- Rekam Medis Singkat -->
            <div class="col-md-8">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body p-4">
                        <h5 class="fw-bold border-bottom pb-2 mb-3 text-primary d-flex align-items-center gap-2">
                            <i data-lucide="activity"></i> Rangkuman Kesehatan Terakhir
                        </h5>
                        
                        <?php if ($kuesioner): ?>
                            <?php renderQuestionnaireResult($resultPresentation, $kuesioner); ?>
                            <h6 class="fw-bold">Perkembangan semua pengisian</h6>
                            <p class="small text-muted">Grafik hanya memuat hasil dari akun siswa yang telah tertaut dan disetujui.</p>
                            <div class="mb-4" style="height: 300px">
                                <canvas id="parentQuestionnaireChart"></canvas>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">Anak Anda belum pernah mengisi kuesioner *screening* Anemia.</p>
                        <?php endif; ?>

                        <h5 class="fw-bold border-bottom pb-2 mb-3 text-success d-flex align-items-center gap-2">
                            <i data-lucide="pill"></i> Riwayat Minum Obat (TTD)
                        </h5>
                        <?php if (empty($kepatuhan)): ?>
                            <p class="text-muted mb-0">Belum ada riwayat konsumsi Tablet Tambah Darah (TTD).</p>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($kepatuhan as $k): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <div>
                                            <i data-lucide="<?= $k['status_konsumsi'] == 'sudah' ? 'check-circle' : 'x-circle' ?>" 
                                               class="<?= $k['status_konsumsi'] == 'sudah' ? 'text-success' : 'text-danger' ?> me-2" style="width: 18px; height: 18px;"></i>
                                            <?= date('d M Y', strtotime($k['tanggal'])) ?>
                                        </div>
                                        <span class="badge <?= $k['status_konsumsi'] == 'sudah' ? 'bg-success' : 'bg-danger' ?> rounded-pill">
                                            <?= $k['status_konsumsi'] == 'sudah' ? 'Diminum' : 'Tidak Diminum' ?>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <small class="text-muted d-block mt-3">* Ingatkan anak Anda untuk minum TTD seminggu sekali demi mencegah letih dan kurang konsentrasi belajar.</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="/assets/vendor/bootstrap.bundle.min.js"></script>
<script>
  lucide.createIcons();
</script>
<script src="../assets/js/app-init.js?v=20260729"></script>
<?php if ($questionnaireHistory): ?>
    <?php renderQuestionnaireHistoryChartScript('parentQuestionnaireChart', $historyChart); ?>
<?php endif; ?>
</body>
</html>
