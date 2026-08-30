<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/helpers.php';
require_once dirname(__DIR__) . '/views/questionnaire_analytics.php';

check_role('uks');

$repository = new QuestionnaireAnalyticsRepository($pdo);
$insightService = new QuestionnaireInsights();
$aggregate = $repository->aggregate();
$stagedStudents = $repository->latestStagedByStudent();
$legacyStudents = $repository->latestLegacyByStudent();
$averageResponse = [
    'skor_gejala' => $aggregate['averages']['gejala'],
    'skor_makan' => $aggregate['averages']['makan'],
    'skor_pengetahuan' => $aggregate['averages']['pengetahuan'],
    'skor_sikap' => $aggregate['averages']['sikap'],
];
$averageInsights = $insightService->forResponse($averageResponse);
$aggregateAnswers = (new QuestionnaireAggregatePresenter())->forSnapshots(
    $repository->activeAnswerSnapshots()
);

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
    <link href="/assets/vendor/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css?v=20260818" rel="stylesheet">
    <script src="/assets/vendor/chart.umd.min.js"></script>
    <script src="/assets/vendor/lucide.min.js"></script>
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
            <p class="text-muted mb-0">Format skrining baru dan data historis dipisahkan agar skornya tidak tercampur.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-warning" href="permintaan_lab.php">Permintaan perubahan lab</a>
            <a class="btn btn-outline-primary" href="data_siswa.php">Buka data siswa</a>
        </div>
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

    <?php if ($aggregate['staged']['responses'] > 0): ?>
        <section class="card mb-4" aria-labelledby="staged-summary-title">
            <div class="card-body">
                <h2 class="h5" id="staged-summary-title">Ringkasan skrining gejala bertahap</h2>
                <div class="row g-3 mt-1">
                    <?php foreach ([
                        ['Pengisian bertahap', $aggregate['staged']['responses']],
                        ['Faktor risiko selesai', $aggregate['staged']['completed']],
                        ['Terindikasi risiko anemia', $aggregate['staged']['indicated']],
                    ] as [$label, $value]): ?>
                        <div class="col-md-4"><div class="border rounded-3 p-3 h-100">
                            <p class="text-muted small mb-1"><?= escape_output($label) ?></p>
                            <p class="h3 mb-0"><?= (int) $value ?></p>
                        </div></div>
                    <?php endforeach; ?>
                </div>
                <p class="small text-muted mt-3 mb-0">
                    Rerata gejala: <?= escape_output($aggregate['staged']['avg_symptom']) ?>/10
                    <?php if ($aggregate['staged']['completed'] > 0): ?>
                        · rerata faktor risiko: <?= escape_output($aggregate['staged']['avg_risk']) ?>%
                    <?php endif; ?>
                </p>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($aggregate['total_students'] > 0): ?>
        <?php renderQuestionnaireCompletionChart(
            'questionnaireCompletionChart',
            (int) $aggregate['responding_students'],
            (int) $aggregate['not_responded_students']
        ); ?>
    <?php endif; ?>

    <?php if ($aggregate['total_responses'] === 0): ?>
        <div class="alert alert-info">Belum ada hasil kuesioner yang dapat dianalisis.</div>
    <?php elseif ($aggregate['legacy_responses'] > 0): ?>
        <section class="card mb-4">
            <div class="card-body">
                <?php renderQuestionnaireAggregateRecap(
                    $averageInsights,
                    (int) $aggregate['legacy_responses']
                ); ?>
            </div>
        </section>

        <section class="card mb-4" aria-labelledby="average-chart-title">
            <div class="card-body">
                <h2 class="h5" id="average-chart-title">Diagram rata-rata format historis</h2>
                <p class="small text-muted">Hanya data format lama; tidak dicampur dengan skrining bertahap.</p>
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

        <?php if ($aggregateAnswers['responses_with_answers'] > 0): ?>
            <?php renderQuestionnaireChoiceCharts(
                $aggregateAnswers['charts'],
                true,
                $aggregateAnswers['responses_with_answers'],
                'aggregateQuestionChoiceChart'
            ); ?>
        <?php endif; ?>
    <?php endif; ?>

    <section class="card mb-4" aria-labelledby="staged-result-title">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <h2 class="h5 mb-1" id="staged-result-title">Hasil Skrining Baru</h2>
                    <p class="small text-muted mb-0">Terbaru per siswa untuk skrining gejala bertahap tanpa pemeriksaan Hb.</p>
                </div>
                <a class="btn btn-success btn-sm d-inline-flex align-items-center gap-2"
                   href="export_questionnaire.php?type=baru">
                    <i data-lucide="file-spreadsheet" aria-hidden="true"></i>
                    Export hasil baru (.csv)
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr>
                        <th scope="col">Siswa</th><th scope="col">Kelas saat pengisian</th><th scope="col">Tanggal</th>
                        <th scope="col">Gejala</th><th scope="col">Tahap</th><th scope="col">Faktor risiko</th><th scope="col">Hasil</th>
                        <th scope="col"><span class="visually-hidden">Aksi</span></th>
                    </tr></thead>
                    <tbody>
                    <?php if (!$stagedStudents): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">Belum ada hasil skrining baru.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($stagedStudents as $student): ?>
                        <tr>
                            <td>
                                <strong><?= escape_output((string) $student['nama']) ?></strong>
                                <small class="d-block text-muted">@<?= escape_output((string) $student['username']) ?></small>
                            </td>
                            <td><?= escape_output((string) ($student['pendidikan'] ?? $student['kelas'] ?? '-')) ?></td>
                            <td><?= escape_output(date('d M Y', strtotime((string) $student['created_at']))) ?></td>
                            <td><?= escape_output($student['rerata_gejala']) ?>/10</td>
                            <td>
                                <?php if (($student['tahap_screening'] ?? null) === 'selesai'): ?>Selesai
                                <?php elseif (($student['tahap_screening'] ?? null) === 'faktor_risiko_tersedia'): ?>Menunggu faktor risiko
                                <?php else: ?>Gejala selesai
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (($student['tahap_screening'] ?? null) === 'selesai'): ?>
                                    <?= escape_output($student['persentase_faktor_risiko']) ?>%
                                <?php elseif (($student['tahap_screening'] ?? null) === 'faktor_risiko_tersedia'): ?>
                                    Belum dijawab
                                <?php else: ?>
                                    Tidak dibuka
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (($student['hasil_screening'] ?? null) === 'terindikasi_anemia'): ?>Terindikasi risiko anemia
                                <?php elseif (($student['hasil_screening'] ?? null) === 'tidak_terindikasi_anemia'): ?>Belum terindikasi risiko anemia
                                <?php elseif (($student['hasil_screening'] ?? null) === 'gejala_di_bawah_ambang'): ?>Gejala di bawah ambang
                                <?php else: ?>Belum selesai
                                <?php endif; ?>
                            </td>
                            <td><a class="btn btn-sm btn-outline-primary"
                                   href="detail_siswa.php?id=<?= (int) $student['student_id'] ?>&amp;questionnaire_id=<?= (int) $student['questionnaire_id'] ?>">Lihat hasil</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="card" aria-labelledby="legacy-result-title">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <h2 class="h5 mb-1" id="legacy-result-title">Hasil Kuesioner Lama</h2>
                    <p class="small text-muted mb-0">Terbaru per siswa khusus format historis; tidak digabung dengan skrining baru.</p>
                </div>
                <a class="btn btn-outline-success btn-sm d-inline-flex align-items-center gap-2"
                   href="export_questionnaire.php?type=lama">
                    <i data-lucide="file-spreadsheet" aria-hidden="true"></i>
                    Export hasil lama (.csv)
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr>
                        <th scope="col">Siswa</th><th scope="col">Kelas</th><th scope="col">Tanggal</th>
                        <th scope="col">Keluhan</th><th scope="col">Pola makan</th><th scope="col">Lab</th>
                        <th scope="col"><span class="visually-hidden">Aksi</span></th>
                    </tr></thead>
                    <tbody>
                    <?php if (!$legacyStudents): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">Belum ada hasil kuesioner lama.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($legacyStudents as $student): ?>
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
                                   href="detail_siswa.php?id=<?= (int) $student['student_id'] ?>&amp;questionnaire_id=<?= (int) $student['questionnaire_id'] ?>">Lihat hasil</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</main>
<script src="/assets/vendor/bootstrap.bundle.min.js"></script>
<script src="../assets/js/app-init.js?v=20260831-safe-install"></script>
<script src="../assets/js/main.js?v=20260818"></script>
<?php if ($aggregate['legacy_responses'] > 0): ?>
    <?php renderQuestionnaireAverageChartScript('questionnaireAverageChart', $averageInsights); ?>
<?php endif; ?>
</body>
</html>
