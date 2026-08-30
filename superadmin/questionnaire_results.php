<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/helpers.php';
require_once dirname(__DIR__) . '/app/Security/SuperadminGuard.php';
require_once dirname(__DIR__) . '/views/superadmin/layout.php';
require_once dirname(__DIR__) . '/views/questionnaire_analytics.php';
require_once dirname(__DIR__) . '/views/staged_screening_staff.php';

try {
    SuperadminGuard::authorize($pdo, $_SESSION);
} catch (Throwable) {
    http_response_code(403);
    exit('Akses ditolak.');
}

$repository = new QuestionnaireAnalyticsRepository($pdo);
$insightService = new QuestionnaireInsights();
$aggregate = $repository->aggregate();
$stagedStudents = $repository->latestStagedByStudent();
$legacyStudents = $repository->latestLegacyByStudent();
$averageInsights = $insightService->forResponse([
    'skor_gejala' => $aggregate['averages']['gejala'],
    'skor_makan' => $aggregate['averages']['makan'],
    'skor_pengetahuan' => $aggregate['averages']['pengetahuan'],
    'skor_sikap' => $aggregate['averages']['sikap'],
]);
$aggregateAnswers = (new QuestionnaireAggregatePresenter())->forSnapshots(
    $repository->activeAnswerSnapshots()
);

$selectedStudentId = filter_input(INPUT_GET, 'student_id', FILTER_VALIDATE_INT);
$selectedQuestionnaireId = filter_input(
    INPUT_GET,
    'questionnaire_id',
    FILTER_VALIDATE_INT
);
if (isset($_GET['questionnaire_id']) && (!$selectedQuestionnaireId || $selectedQuestionnaireId < 1)) {
    http_response_code(400);
    exit('ID kuesioner tidak valid.');
}
$selectedStudent = null;
$selectedHistory = [];
$selectedPrimary = null;
$selectedLatest = null;
$selectedIsHistoryOnly = false;
$selectedPresentation = null;
$selectedStagedPresentation = null;
$selectedChart = ['labels' => [], 'series' => []];
if ($selectedStudentId) {
    $selectedStudent = $repository->student((int) $selectedStudentId);
    if (!$selectedStudent) {
        http_response_code(404);
        exit('Siswa tidak ditemukan.');
    }
    $selectedHistory = $repository->historyForStudent((int) $selectedStudentId);
    $selectedPrimary = $repository->latestPrimaryForStudent(
        (int) $selectedStudentId
    );
    $selectedLatest = $selectedQuestionnaireId
        ? $repository->primaryQuestionnaireForStudent(
            (int) $selectedStudentId,
            (int) $selectedQuestionnaireId
        )
        : ($selectedPrimary ?? ($selectedHistory
            ? $selectedHistory[array_key_last($selectedHistory)]
            : null));
    if ($selectedQuestionnaireId && !$selectedLatest) {
        http_response_code(404);
        exit('Hasil kuesioner tidak ditemukan.');
    }
    $selectedIsHistoryOnly = $selectedLatest
        && !empty($selectedLatest['history_only_at']);
    $selectedDetection = $repository->latestDetectionForStudent(
        (int) $selectedStudentId,
        (int) ($selectedLatest['id'] ?? 0)
    );
    $isSelectedStaged = $selectedLatest && !empty($selectedLatest['versi_screening']);
    if ($isSelectedStaged && ($selectedLatest['tahap_screening'] ?? null) !== 'faktor_risiko_tersedia') {
        try {
            $selectedStagedPresentation = (new StagedScreeningResultPresenter())->present($selectedLatest);
        } catch (InvalidArgumentException) {
            $selectedStagedPresentation = null;
        }
    }
    $selectedPresentation = $selectedLatest && !$isSelectedStaged
        ? (new QuestionnaireResultPresenter())->forResult(
            $selectedLatest,
            $selectedDetection
        )
        : null;
    $selectedChart = $insightService->historyChart($selectedHistory);
}

$retakeNotice = $_SESSION['_questionnaire_retake_notice'] ?? null;
unset($_SESSION['_questionnaire_retake_notice']);
if (
    !is_array($retakeNotice)
    || (int) ($retakeNotice['student_id'] ?? 0) !== (int) $selectedStudentId
) {
    $retakeNotice = null;
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

<div class="d-flex justify-content-end mb-3">
    <a class="btn btn-outline-warning me-2" href="lab_requests.php">Permintaan perubahan lab</a>
</div>

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

<?php if ($aggregate['staged']['responses'] > 0): ?>
    <section class="master-card p-3 p-lg-4 mb-4" aria-labelledby="master-staged-summary">
        <h2 class="h5" id="master-staged-summary">Ringkasan skrining gejala bertahap</h2>
        <div class="row g-3 mt-1">
            <?php foreach ([
                ['Pengisian bertahap', $aggregate['staged']['responses']],
                ['Faktor risiko selesai', $aggregate['staged']['completed']],
                ['Terindikasi risiko anemia', $aggregate['staged']['indicated']],
            ] as [$label, $value]): ?>
                <div class="col-md-4"><div class="border rounded-3 p-3 h-100">
                    <span class="metric-label"><?= escape_output($label) ?></span>
                    <div class="h3 mb-0"><?= (int) $value ?></div>
                </div></div>
            <?php endforeach; ?>
        </div>
        <p class="text-muted small mt-3 mb-0">
            Rerata gejala <?= escape_output($aggregate['staged']['avg_symptom']) ?>/10
            <?php if ($aggregate['staged']['completed'] > 0): ?>
                · rerata faktor risiko <?= escape_output($aggregate['staged']['avg_risk']) ?>%
            <?php endif; ?>
        </p>
    </section>
<?php endif; ?>

<?php if ($aggregate['total_students'] > 0): ?>
    <?php renderQuestionnaireCompletionChart(
        'masterCompletionChart',
        (int) $aggregate['responding_students'],
        (int) $aggregate['not_responded_students']
    ); ?>
<?php endif; ?>

<?php if ($aggregate['legacy_responses'] > 0): ?>
    <section class="master-card p-3 p-lg-4 mb-4">
        <?php renderQuestionnaireAggregateRecap(
            $averageInsights,
            (int) $aggregate['legacy_responses']
        ); ?>
    </section>

    <section class="master-card p-3 p-lg-4 mb-4" aria-labelledby="master-average-title">
        <h2 class="h5" id="master-average-title">Diagram format historis</h2>
        <p class="text-muted small">Hanya data format lama; tidak dicampur dengan skrining bertahap.</p>
        <div style="height: 320px"><canvas id="masterAverageChart"></canvas></div>
        <div class="mt-4">
            <?php renderQuestionnaireInsights(
                $averageInsights,
                $insightService->disclaimer()
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

<?php if ($selectedStudent): ?>
    <section class="master-card p-3 p-lg-4 mb-4" aria-labelledby="individual-title">
        <?php if (($retakeNotice['status'] ?? null) === 'success'): ?>
            <div class="alert alert-success" role="status">
                Pengisian ulang berhasil diaktifkan. Hasil sebelumnya menjadi riwayat pribadi dan tidak dihitung dalam pendataan utama.
            </div>
        <?php elseif (($retakeNotice['status'] ?? null) === 'error'): ?>
            <div class="alert alert-danger" role="alert">
                Reset kuesioner ditolak. Pastikan ada hasil utama dan alasan reset sudah diisi 5–500 karakter.
            </div>
        <?php endif; ?>
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
        <?php if ($selectedPrimary): ?>
            <div class="border border-warning-subtle bg-warning-subtle rounded-3 p-3 mb-4">
                <h3 class="h6">Aktifkan pengisian ulang</h3>
                <p class="small text-muted mb-3">
                    Hasil lama tetap tersimpan sebagai riwayat pribadi, tetapi tidak lagi dihitung dalam pendataan utama.
                </p>
                <form method="post" action="questionnaire_retake.php"
                      data-questionnaire-retake-form
                      data-student-name="<?= escape_output((string) $selectedStudent['nama']) ?>">
                    <?= csrfInput() ?>
                    <input type="hidden" name="student_id" value="<?= (int) $selectedStudent['id'] ?>">
                    <label class="form-label" for="retakeReasonSuperadmin">Alasan reset</label>
                    <textarea class="form-control mb-3" id="retakeReasonSuperadmin"
                              name="reason" rows="3" minlength="5" maxlength="500"
                              required placeholder="Contoh: periode skrining perlu diulang"></textarea>
                    <button type="submit" class="btn btn-outline-danger">
                        Reset dan izinkan isi ulang
                    </button>
                </form>
            </div>
        <?php endif; ?>
        <?php if (!$selectedLatest): ?>
            <p class="text-muted mb-0">Siswa ini belum memiliki pengisian aktif.</p>
        <?php else: ?>
            <?php if ($selectedIsHistoryOnly): ?>
                <div class="alert alert-secondary" role="note">
                    <strong>Riwayat pribadi.</strong> Hasil ini tetap tersimpan untuk rekam individual dan tidak dihitung dalam pendataan utama.
                    <?php if (!empty($selectedLatest['history_only_reason'])): ?>
                        <span class="d-block mt-1"><strong>Alasan reset:</strong> <?= escape_output($selectedLatest['history_only_reason']) ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <?php if (empty($selectedLatest['versi_screening'])): ?>
                <div style="height: 320px"><canvas id="masterStudentChart"></canvas></div>
            <?php endif; ?>
            <div class="mt-4">
                <?php if (!empty($selectedLatest['versi_screening'])): ?>
                    <?php renderStagedScreeningForStaff($selectedStagedPresentation, $selectedLatest); ?>
                <?php else: ?>
                    <?php renderQuestionnaireResult(
                        $selectedPresentation,
                        $selectedLatest
                    ); ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<section class="master-card p-3 p-lg-4 mb-4" aria-labelledby="master-staged-students-title">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <p class="eyebrow mb-1">Daftar skrining bertahap</p>
            <h2 class="h5 mb-1" id="master-staged-students-title">Hasil Skrining Baru</h2>
            <p class="text-muted small mb-0">Terbaru per siswa untuk skrining gejala tanpa pemeriksaan Hb.</p>
        </div>
        <form method="post" action="questionnaire_export.php">
            <?= csrfInput() ?>
            <input type="hidden" name="type" value="baru">
            <button class="btn btn-success btn-sm d-inline-flex align-items-center gap-2" type="submit">
                <i data-lucide="file-spreadsheet" aria-hidden="true"></i>
                Export hasil baru (.csv)
            </button>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table master-table align-middle">
            <thead><tr>
                <th scope="col">Siswa</th><th scope="col">Kelas saat pengisian</th><th scope="col">Tanggal</th>
                <th scope="col">Gejala</th><th scope="col">Tahap</th><th scope="col">Faktor risiko</th><th scope="col">Hasil</th>
                <th scope="col"><span class="visually-hidden">Aksi</span></th>
            </tr></thead>
            <tbody>
            <?php if (!$stagedStudents): ?>
                <tr><td colspan="8" class="empty-state">Belum ada hasil skrining baru.</td></tr>
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
                           href="?student_id=<?= (int) $student['student_id'] ?>&amp;questionnaire_id=<?= (int) $student['questionnaire_id'] ?>">Lihat hasil</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="master-card p-3 p-lg-4" aria-labelledby="master-legacy-students-title">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <p class="eyebrow mb-1">Daftar historis</p>
            <h2 class="h5 mb-1" id="master-legacy-students-title">Hasil Kuesioner Lama</h2>
            <p class="text-muted small mb-0">Terbaru per siswa khusus format lama; tidak digabung dengan skrining baru.</p>
        </div>
        <form method="post" action="questionnaire_export.php">
            <?= csrfInput() ?>
            <input type="hidden" name="type" value="lama">
            <button class="btn btn-outline-success btn-sm d-inline-flex align-items-center gap-2" type="submit">
                <i data-lucide="file-spreadsheet" aria-hidden="true"></i>
                Export hasil lama (.csv)
            </button>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table master-table align-middle">
            <thead><tr>
                <th scope="col">Siswa</th><th scope="col">Kelas</th><th scope="col">Tanggal</th>
                <th scope="col">Keluhan</th><th scope="col">Pola makan</th><th scope="col">Lab</th>
                <th scope="col"><span class="visually-hidden">Aksi</span></th>
            </tr></thead>
            <tbody>
            <?php if (!$legacyStudents): ?>
                <tr><td colspan="7" class="empty-state">Belum ada hasil kuesioner lama.</td></tr>
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
                           href="?student_id=<?= (int) $student['student_id'] ?>&amp;questionnaire_id=<?= (int) $student['questionnaire_id'] ?>">Lihat hasil</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php if ($aggregate['legacy_responses'] > 0): ?>
    <?php renderQuestionnaireAverageChartScript('masterAverageChart', $averageInsights); ?>
<?php endif; ?>
<?php if ($selectedHistory && empty($selectedLatest['versi_screening'])): ?>
    <?php renderQuestionnaireHistoryChartScript('masterStudentChart', $selectedChart); ?>
<?php endif; ?>
<script src="../assets/js/questionnaire-retake.js?v=20260830"></script>
<?php renderSuperadminFooter(); ?>
