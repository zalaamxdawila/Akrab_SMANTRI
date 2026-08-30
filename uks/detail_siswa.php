<?php
require_once '../config.php';
require_once '../helpers.php';
require_once '../views/questionnaire_analytics.php';
require_once '../views/staged_screening_staff.php';

check_role('uks');

if (!isset($_GET['id'])) {
    header("Location: data_siswa.php");
    exit;
}

$siswa_id = (int)$_GET['id'];
$requestedQuestionnaireId = isset($_GET['questionnaire_id'])
    ? filter_var($_GET['questionnaire_id'], FILTER_VALIDATE_INT)
    : null;
if (isset($_GET['questionnaire_id']) && (!$requestedQuestionnaireId || $requestedQuestionnaireId < 1)) {
    http_response_code(400);
    exit('ID kuesioner tidak valid.');
}

// 1. Fetch User Data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'siswa'");
$stmt->execute([$siswa_id]);
$siswa = $stmt->fetch();

if (!$siswa) {
    die("Data siswa tidak ditemukan.");
}
recordAuditEvent($pdo, (int) $_SESSION['user_id'], 'health_record.viewed', 'student', $siswa_id, ['outcome' => 'success', 'actor_role' => 'uks']);
akrabLog('info', 'health_record_viewed', ['outcome' => 'success', 'target_type' => 'student', 'actor_role' => 'uks']);

$questionnaireRepository = new QuestionnaireAnalyticsRepository($pdo);
$questionnaireInsights = new QuestionnaireInsights();
$questionnaireHistory = $questionnaireRepository->historyForStudent($siswa_id);
$primaryQuestionnaire = $questionnaireRepository->latestPrimaryForStudent(
    $siswa_id
);
$kuesioner = $requestedQuestionnaireId
    ? $questionnaireRepository->primaryQuestionnaireForStudent(
        $siswa_id,
        (int) $requestedQuestionnaireId
    )
    : ($primaryQuestionnaire ?? ($questionnaireHistory
        ? $questionnaireHistory[array_key_last($questionnaireHistory)]
        : null));
if ($requestedQuestionnaireId && !$kuesioner) {
    http_response_code(404);
    exit('Hasil kuesioner tidak ditemukan.');
}
$isHistoryOnly = $kuesioner && !empty($kuesioner['history_only_at']);
$retakeNotice = $_SESSION['_questionnaire_retake_notice'] ?? null;
unset($_SESSION['_questionnaire_retake_notice']);
if (
    !is_array($retakeNotice)
    || (int) ($retakeNotice['student_id'] ?? 0) !== $siswa_id
) {
    $retakeNotice = null;
}
$historyChart = ['labels' => [], 'series' => []];

// 3. Fetch Latest Active Detection
$hasil = $questionnaireRepository->latestDetectionForStudent(
    $siswa_id,
    (int) ($kuesioner['id'] ?? 0)
);
$isStagedScreening = $kuesioner && !empty($kuesioner['versi_screening']);
if (!$isStagedScreening) {
    $legacyHistory = array_values(array_filter(
        $questionnaireHistory,
        static fn (array $row): bool => empty($row['versi_screening'])
    ));
    $historyChart = $questionnaireInsights->historyChart($legacyHistory);
}
$stagedPresentation = null;
if ($isStagedScreening && ($kuesioner['tahap_screening'] ?? null) !== 'faktor_risiko_tersedia') {
    try {
        $stagedPresentation = (new StagedScreeningResultPresenter())->present($kuesioner);
    } catch (InvalidArgumentException) {
        $stagedPresentation = null;
    }
}
$resultPresentation = $kuesioner && !$isStagedScreening
    ? (new QuestionnaireResultPresenter())->forResult($kuesioner, $hasil)
    : null;

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
    <link href="/assets/vendor/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css?v=20260818" rel="stylesheet">
    <script src="/assets/vendor/chart.umd.min.js"></script>
</head>
<body>
<?php renderImpersonationBanner($pdo, $_SESSION); ?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
    <div class="container">
        <a class="navbar-brand text-white fw-bold" href="dashboard.php">AKRAB UKS Panel</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link text-white" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="hasil_kuesioner.php">Hasil Kuesioner</a></li>
                <li class="nav-item"><a class="nav-link text-white active" href="data_siswa.php">Data Siswa</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="jawab_konsultasi.php">Konsultasi</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-4">
    <?php if (($retakeNotice['status'] ?? null) === 'success'): ?>
        <div class="alert alert-success" role="status">
            Pengisian ulang berhasil diaktifkan. Hasil sebelumnya dipindahkan ke riwayat pribadi dan tidak dihitung dalam pendataan utama.
        </div>
    <?php elseif (($retakeNotice['status'] ?? null) === 'error'): ?>
        <div class="alert alert-danger" role="alert">
            Reset kuesioner ditolak. Pastikan ada hasil utama dan alasan reset sudah diisi 5–500 karakter.
        </div>
    <?php endif; ?>
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

            <?php if ($primaryQuestionnaire): ?>
                <div class="card shadow-sm mb-4 border-warning-subtle">
                    <div class="card-header bg-warning-subtle fw-bold">Aktifkan Pengisian Ulang</div>
                    <div class="card-body">
                        <p class="small text-muted">
                            Hasil lama tetap tersimpan sebagai riwayat pribadi, tetapi tidak lagi dihitung dalam pendataan utama.
                        </p>
                        <form method="post" action="questionnaire_retake.php"
                              data-questionnaire-retake-form
                              data-student-name="<?= escape_output((string) $siswa['nama']) ?>">
                            <?= csrfInput() ?>
                            <input type="hidden" name="student_id" value="<?= $siswa_id ?>">
                            <label class="form-label" for="retakeReasonUks">Alasan reset</label>
                            <textarea class="form-control mb-3" id="retakeReasonUks"
                                      name="reason" rows="3" minlength="5" maxlength="500"
                                      required placeholder="Contoh: perlu pengisian ulang setelah evaluasi UKS"></textarea>
                            <button type="submit" class="btn btn-outline-danger w-100">
                                Reset dan izinkan isi ulang
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white fw-bold">Status Risiko Terakhir</div>
                <div class="card-body text-center">
                    <?php if ($isStagedScreening): ?>
                        <?php if ($stagedPresentation): ?>
                            <span class="badge text-bg-<?= escape_output($stagedPresentation['status_class']) ?> fs-6 p-3 w-100 mb-3">
                                <?= escape_output($stagedPresentation['title']) ?>
                            </span>
                            <p class="mb-1">Rerata gejala: <strong><?= escape_output($stagedPresentation['symptom_average']) ?>/10</strong></p>
                            <?php if ($stagedPresentation['show_risk_score']): ?>
                                <p class="mb-1">Faktor risiko: <strong><?= escape_output($stagedPresentation['risk_percentage']) ?></strong></p>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge text-bg-warning fs-6 p-3 w-100">Faktor risiko belum selesai</span>
                        <?php endif; ?>
                    <?php elseif ($hasil): ?>
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
                        <?php if ($isHistoryOnly): ?>
                            <div class="alert alert-secondary" role="note">
                                <strong>Riwayat pribadi.</strong> Hasil ini tetap tersimpan untuk rekam individual dan tidak dihitung dalam pendataan utama.
                                <?php if (!empty($kuesioner['history_only_reason'])): ?>
                                    <span class="d-block mt-1"><strong>Alasan reset:</strong> <?= escape_output($kuesioner['history_only_reason']) ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($isStagedScreening): ?>
                            <?php renderStagedScreeningForStaff($stagedPresentation, $kuesioner); ?>
                        <?php else: ?>
                            <?php renderQuestionnaireResult($resultPresentation, $kuesioner); ?>
                        <?php endif; ?>

                        <?php if (!$isStagedScreening): ?>
                            <h5 class="text-primary border-bottom pb-2 mb-3">Perkembangan Kuesioner Historis</h5>
                            <p class="small text-muted">Grafik hanya memuat data format lama dan menormalkan setiap skor ke skala 0–100%.</p>
                            <div class="mb-4" style="height: 320px">
                                <canvas id="studentQuestionnaireChart"></canvas>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/assets/vendor/bootstrap.bundle.min.js"></script>
<script src="../assets/js/app-init.js?v=20260831-safe-install"></script>
<script src="../assets/js/questionnaire-retake.js?v=20260830"></script>
<?php if ($questionnaireHistory && !$isStagedScreening): ?>
    <?php renderQuestionnaireHistoryChartScript('studentQuestionnaireChart', $historyChart); ?>
<?php endif; ?>
</body>
</html>
