<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/helpers.php';
require_once dirname(__DIR__) . '/views/questionnaire_analytics.php';

check_role('uks');

$repository = new QuestionnaireAnalyticsRepository($pdo);
$insightService = new QuestionnaireInsights();
$aggregate = $repository->aggregate();
$students = $repository->latestByStudent();
$averageResponse = [
    'skor_gejala' => $aggregate['averages']['gejala'],
    'skor_makan' => $aggregate['averages']['makan'],
    'skor_pengetahuan' => $aggregate['averages']['pengetahuan'],
    'skor_sikap' => $aggregate['averages']['sikap'],
];
$averageInsights = $insightService->forResponse($averageResponse);

recordAuditEvent(
    $pdo,
    (int) $_SESSION['user_id'],
    'questionnaire.analytics_viewed',
    'questionnaire_aggregate',
    null,
    ['actor_role' => 'uks', 'outcome' => 'success']
);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Kuesioner - AKRAB UKS</title>
    <link rel="icon" href="../assets/icons/icon.svg" type="image/svg+xml">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css?v=20260729" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js"></script>
</head>
<body>
<?php renderImpersonationBanner($pdo, $_SESSION); ?>
<nav class="navbar navbar-expand-lg sticky-top" aria-label="Navigasi UKS">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">AKRAB UKS Panel</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link active" aria-current="page" href="hasil_kuesioner.php">Hasil Kuesioner</a></li>
                <li class="nav-item"><a class="nav-link" href="data_siswa.php">Data Siswa</a></li>
                <li class="nav-item"><a class="nav-link" href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<main class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
        <div>
            <p class="text-uppercase text-success small fw-bold mb-1">Analitik skrining</p>
            <h1 class="h3 mb-1">Hasil Kuesioner Seluruh Siswa</h1>
            <p class="text-muted mb-0">Agregat memakai semua pengisian aktif; tabel menampilkan pengisian terbaru tiap siswa.</p>
        </div>
        <a class="btn btn-outline-primary" href="data_siswa.php">Buka data siswa</a>
    </div>

    <section class="row g-3 mb-4" aria-label="Ringkasan pengisian">
        <?php foreach ([
            ['Semua pengisian', $aggregate['total_responses']],
            ['Siswa yang mengisi', $aggregate['responding_students']],
            ['Hasil lab lengkap', $aggregate['lab_available']],
        ] as [$label, $value]): ?>
            <div class="col-sm-4">
                <div class="card h-100"><div class="card-body">
                    <p class="text-muted mb-1"><?= escape_output($label) ?></p>
                    <p class="display-6 fw-bold mb-0"><?= (int) $value ?></p>
                </div></div>
            </div>
        <?php endforeach; ?>
    </section>

    <?php if ($aggregate['total_responses'] === 0): ?>
        <div class="alert alert-info">Belum ada hasil kuesioner yang dapat dianalisis.</div>
    <?php else: ?>
        <section class="card mb-4" aria-labelledby="average-chart-title">
            <div class="card-body">
                <h2 class="h5" id="average-chart-title">Diagram rata-rata semua pengisian</h2>
                <p class="small text-muted">Semua skala dinormalisasi ke 0–100% agar dapat dibandingkan.</p>
                <div style="height: 320px"><canvas id="questionnaireAverageChart"></canvas></div>
            </div>
        </section>

        <section class="card mb-4" aria-labelledby="average-insight-title">
            <div class="card-body">
                <h2 class="h5 mb-3" id="average-insight-title">Penjelasan hasil keseluruhan</h2>
                <?php renderQuestionnaireInsights(
                    $averageInsights,
                    $insightService->disclaimer(),
                    true
                ); ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="card" aria-labelledby="student-result-title">
        <div class="card-body">
            <h2 class="h5 mb-3" id="student-result-title">Hasil terbaru per siswa</h2>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr>
                        <th>Siswa</th><th>Kelas</th><th>Tanggal</th>
                        <th>Keluhan</th><th>Pola makan</th><th>Lab</th><th></th>
                    </tr></thead>
                    <tbody>
                    <?php if (!$students): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">Belum ada hasil.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td>
                                <strong><?= escape_output((string) $student['nama']) ?></strong>
                                <small class="d-block text-muted">@<?= escape_output((string) $student['username']) ?></small>
                            </td>
                            <td><?= escape_output((string) ($student['kelas'] ?? '-')) ?></td>
                            <td><?= escape_output(date('d M Y', strtotime((string) $student['created_at']))) ?></td>
                            <td><?= (int) $student['skor_gejala'] ?>/100</td>
                            <td><?= (int) $student['skor_makan'] ?>/18</td>
                            <td><?= $student['kadar_hb'] === null ? 'Belum ada' : 'Lengkap' ?></td>
                            <td><a class="btn btn-sm btn-outline-primary"
                                   href="detail_siswa.php?id=<?= (int) $student['student_id'] ?>">Lihat per siswa</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/app-init.js?v=20260729"></script>
<script src="../assets/js/main.js?v=20260729"></script>
<?php if ($aggregate['total_responses'] > 0): ?>
    <?php renderQuestionnaireAverageChartScript('questionnaireAverageChart', $averageInsights); ?>
<?php endif; ?>
</body>
</html>
