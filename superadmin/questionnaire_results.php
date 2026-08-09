<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/helpers.php';
require_once dirname(__DIR__) . '/app/Security/SuperadminGuard.php';
require_once dirname(__DIR__) . '/views/superadmin/layout.php';
require_once dirname(__DIR__) . '/views/questionnaire_analytics.php';

try {
    SuperadminGuard::authorize($pdo, $_SESSION);
} catch (Throwable) {
    http_response_code(403);
    exit('Akses ditolak.');
}

$repository = new QuestionnaireAnalyticsRepository($pdo);
$insightService = new QuestionnaireInsights();
$aggregate = $repository->aggregate();
$students = $repository->latestByStudent();
$averageInsights = $insightService->forResponse([
    'skor_gejala' => $aggregate['averages']['gejala'],
    'skor_makan' => $aggregate['averages']['makan'],
    'skor_pengetahuan' => $aggregate['averages']['pengetahuan'],
    'skor_sikap' => $aggregate['averages']['sikap'],
]);

$selectedStudentId = filter_input(INPUT_GET, 'student_id', FILTER_VALIDATE_INT);
$selectedStudent = null;
$selectedHistory = [];
$selectedLatest = null;
$selectedPresentation = null;
$selectedChart = ['labels' => [], 'series' => []];
if ($selectedStudentId) {
    $selectedStudent = $repository->student((int) $selectedStudentId);
    if (!$selectedStudent) {
        http_response_code(404);
        exit('Siswa tidak ditemukan.');
    }
    $selectedHistory = $repository->historyForStudent((int) $selectedStudentId);
    $selectedLatest = $selectedHistory
        ? $selectedHistory[array_key_last($selectedHistory)]
        : null;
    $selectedDetection = $repository->latestDetectionForStudent(
        (int) $selectedStudentId
    );
    $selectedPresentation = $selectedLatest
        ? (new QuestionnaireResultPresenter())->forResult(
            $selectedLatest,
            $selectedDetection
        )
        : null;
    $selectedChart = $insightService->historyChart($selectedHistory);
}

recordAuditEvent(
    $pdo,
    (int) $_SESSION['user_id'],
    'questionnaire.analytics_viewed',
    $selectedStudent ? 'student' : 'questionnaire_aggregate',
    $selectedStudent ? (int) $selectedStudent['id'] : null,
    ['actor_role' => 'superadmin', 'outcome' => 'success']
);

renderSuperadminHeader('Hasil Kuesioner', 'questionnaires');
?>
<script src="/assets/vendor/chart.umd.min.js"></script>

<section class="row g-3 mb-4" aria-label="Ringkasan hasil kuesioner">
    <?php foreach ([
        ['Semua pengisian', $aggregate['total_responses']],
        ['Siswa yang mengisi', $aggregate['responding_students']],
        ['Hasil lab lengkap', $aggregate['lab_available']],
    ] as [$label, $value]): ?>
        <div class="col-sm-4">
            <article class="master-card metric-card">
                <span class="metric-label"><?= escape_output($label) ?></span>
                <div class="metric-value"><?= (int) $value ?></div>
            </article>
        </div>
    <?php endforeach; ?>
</section>

<?php if ($aggregate['total_responses'] > 0): ?>
    <section class="master-card p-3 p-lg-4 mb-4" aria-labelledby="master-average-title">
        <h2 class="h5" id="master-average-title">Diagram seluruh pengisian</h2>
        <p class="text-muted small">Rata-rata semua catatan aktif, dinormalisasi menjadi 0–100%.</p>
        <div style="height: 320px"><canvas id="masterAverageChart"></canvas></div>
        <div class="mt-4">
            <?php renderQuestionnaireInsights(
                $averageInsights,
                $insightService->disclaimer()
            ); ?>
        </div>
    </section>
<?php endif; ?>

<?php if ($selectedStudent): ?>
    <section class="master-card p-3 p-lg-4 mb-4" aria-labelledby="individual-title">
        <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
            <div>
                <p class="eyebrow mb-1">Hasil per siswa</p>
                <h2 class="h5 mb-0" id="individual-title">
                    <?= escape_output((string) $selectedStudent['nama']) ?>
                    <small class="text-muted">· <?= escape_output((string) ($selectedStudent['kelas'] ?? '-')) ?></small>
                </h2>
            </div>
            <a class="btn btn-sm btn-outline-secondary" href="questionnaire_results.php">Tutup detail</a>
        </div>
        <?php if (!$selectedLatest): ?>
            <p class="text-muted mb-0">Siswa ini belum memiliki pengisian aktif.</p>
        <?php else: ?>
            <div style="height: 320px"><canvas id="masterStudentChart"></canvas></div>
            <div class="mt-4">
                <?php renderQuestionnaireResult(
                    $selectedPresentation,
                    $selectedLatest
                ); ?>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<section class="master-card p-3 p-lg-4" aria-labelledby="master-students-title">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <p class="eyebrow mb-1">Daftar individual</p>
            <h2 class="h5 mb-0" id="master-students-title">Pengisian terbaru per siswa</h2>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table master-table align-middle">
            <thead><tr>
                <th>Siswa</th><th>Kelas</th><th>Tanggal</th>
                <th>Keluhan</th><th>Pola makan</th><th>Lab</th><th></th>
            </tr></thead>
            <tbody>
            <?php if (!$students): ?>
                <tr><td colspan="7" class="empty-state">Belum ada hasil kuesioner.</td></tr>
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
                           href="?student_id=<?= (int) $student['student_id'] ?>">Lihat hasil</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php if ($aggregate['total_responses'] > 0): ?>
    <?php renderQuestionnaireAverageChartScript('masterAverageChart', $averageInsights); ?>
<?php endif; ?>
<?php if ($selectedHistory): ?>
    <?php renderQuestionnaireHistoryChartScript('masterStudentChart', $selectedChart); ?>
<?php endif; ?>
<?php renderSuperadminFooter(); ?>
