<?php

declare(strict_types=1);

require_once '../config.php';
require_once '../helpers.php';
require_once '../views/questionnaire_analytics.php';

check_role('siswa');

if (!PdoStagedScreeningStore::schemaIsReady($pdo)) {
    http_response_code(503);
    header('Retry-After: 300');
    exit('Layanan skrining sedang disiapkan. Silakan coba kembali beberapa saat lagi.');
}

$userId = (int) $_SESSION['user_id'];
$screeningService = new StagedScreeningService(new PdoStagedScreeningStore($pdo));
$questionnaireId = null;

if (array_key_exists('questionnaire_id', $_GET)) {
    try {
        $questionnaireId = (int) boundedInt($_GET['questionnaire_id'], 1, PHP_INT_MAX);
    } catch (InvalidArgumentException) {
        http_response_code(404);
        exit('Hasil skrining tidak ditemukan.');
    }
}

$screening = $questionnaireId === null
    ? $screeningService->latestResultForStudent($userId)
    : $screeningService->resultForStudent($userId, $questionnaireId);

$legacyQuestionnaire = null;
$legacyPresentation = null;
if ($screening === null && $questionnaireId === null) {
    $legacyRepository = new QuestionnaireAnalyticsRepository($pdo);
    $legacyQuestionnaire = $legacyRepository->latestPrimaryForStudent($userId);
    if ($legacyQuestionnaire && empty($legacyQuestionnaire['versi_screening'])) {
        $legacyDetection = $legacyRepository->latestDetectionForStudent(
            $userId,
            (int) $legacyQuestionnaire['id']
        );
        $legacyPresentation = (new QuestionnaireResultPresenter())->forResult(
            $legacyQuestionnaire,
            $legacyDetection
        );
    }
}

if ($screening === null && $legacyPresentation === null) {
    http_response_code(404);
    exit('Hasil skrining tidak ditemukan.');
}
if ($screening !== null && $screening['tahap_screening'] === 'faktor_risiko_tersedia') {
    header('Location: kuesioner.php?questionnaire_id=' . (int) $screening['id'], true, 303);
    exit;
}

$presentation = null;
if ($screening !== null) {
    try {
        $presentation = (new StagedScreeningResultPresenter())->present($screening);
    } catch (InvalidArgumentException) {
        http_response_code(422);
        exit('Hasil skrining belum dapat ditampilkan. Silakan hubungi petugas UKS.');
    }
}

$answerSections = [];
try {
    $snapshot = json_decode((string) ($screening['answers_snapshot'] ?? ''), true, 64, JSON_THROW_ON_ERROR);
    if (
        is_array($snapshot)
        && ($snapshot['version'] ?? null) === StagedScreeningSnapshot::VERSION
        && is_array($snapshot['sections'] ?? null)
    ) {
        $answerSections = $snapshot['sections'];
    }
} catch (JsonException) {
    $answerSections = [];
}

$statusIcon = match ($presentation['status_class'] ?? '') {
    'danger' => 'triangle-alert',
    'success' => 'circle-check',
    default => 'activity',
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Skrining - AKRAB</title>
    <link href="/assets/vendor/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css?v=20260830" rel="stylesheet">
    <script src="/assets/vendor/lucide.min.js"></script>
    <style>
        .result-shell { max-width: 920px; }
        .result-hero { border-radius: 1.5rem; overflow: hidden; }
        .metric-card { border: 1px solid rgba(15, 23, 42, .08); border-radius: 1rem; }
        .metric-value { font-size: clamp(2rem, 8vw, 3.25rem); line-height: 1; }
        .recommendation-number { width: 2rem; height: 2rem; flex: 0 0 2rem; }
    </style>
</head>
<body class="bg-light">
<?php renderImpersonationBanner($pdo, $_SESSION); ?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold text-white" href="dashboard.php">AKRAB Siswa</a>
        <div class="ms-auto d-flex gap-2">
            <a class="btn btn-sm btn-light" href="konsultasi.php">Tanya UKS</a>
            <a class="btn btn-sm btn-outline-light" href="../logout.php">Logout</a>
        </div>
    </div>
</nav>

<main class="container result-shell py-4 py-md-5">
    <?php if ($screening !== null && !empty($screening['history_only_at'])): ?>
        <div class="alert alert-secondary" role="note">
            <strong>Riwayat pribadi.</strong> Hasil ini tetap tersimpan untuk catatan Anda, tetapi tidak dihitung dalam pendataan utama sekolah.
        </div>
    <?php endif; ?>
    <?php if ($legacyPresentation !== null && $legacyQuestionnaire !== null): ?>
        <div class="alert alert-info" role="note">
            Ini adalah hasil historis dari format kuesioner sebelumnya.
        </div>
        <?php renderQuestionnaireResult($legacyPresentation, $legacyQuestionnaire); ?>
    <?php else: ?>
    <section class="card result-hero border-0 shadow-sm mb-4" aria-labelledby="resultTitle">
        <div class="card-body p-4 p-md-5 text-center bg-<?= escape_output($presentation['status_class']) ?>-subtle">
            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-white shadow-sm mb-3" style="width: 4rem; height: 4rem;">
                <i data-lucide="<?= escape_output($statusIcon) ?>" class="text-<?= escape_output($presentation['status_class']) ?>" style="width: 2rem; height: 2rem;" aria-hidden="true"></i>
            </div>
            <p class="small fw-bold text-uppercase text-secondary mb-2">Hasil skrining AKRAB</p>
            <h1 class="h2 fw-bold mb-3" id="resultTitle"><?= escape_output($presentation['title']) ?></h1>
            <p class="mb-0 mx-auto" style="max-width: 720px;"><?= escape_output($presentation['explanation']) ?></p>
        </div>
    </section>

    <section class="row g-3 mb-4" aria-label="Ringkasan skor">
        <div class="col-md-<?= $presentation['show_risk_score'] ? '6' : '12' ?>">
            <div class="metric-card bg-white shadow-sm p-4 h-100 text-center">
                <p class="text-muted fw-semibold mb-2">Rerata gejala</p>
                <div class="metric-value fw-bold text-primary"><?= escape_output($presentation['symptom_average']) ?></div>
                <p class="small text-muted mb-0 mt-2">dari skala 0–10 · ambang lanjut &gt; 4,6</p>
            </div>
        </div>
        <?php if ($presentation['show_risk_score']): ?>
            <div class="col-md-6">
                <div class="metric-card bg-white shadow-sm p-4 h-100 text-center">
                    <p class="text-muted fw-semibold mb-2">Skor faktor risiko</p>
                    <div class="metric-value fw-bold text-<?= escape_output($presentation['status_class']) ?>">
                        <?= escape_output($presentation['risk_percentage']) ?>
                    </div>
                    <p class="small text-muted mb-0 mt-2">terindikasi bila di bawah 75%</p>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <section class="card border-0 shadow-sm mb-4" aria-labelledby="recommendationTitle">
        <div class="card-body p-4">
            <h2 class="h5 fw-bold mb-3" id="recommendationTitle">Langkah yang disarankan</h2>
            <div class="vstack gap-3">
                <?php foreach ($presentation['recommendations'] as $index => $recommendation): ?>
                    <div class="d-flex align-items-start gap-3">
                        <span class="recommendation-number rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center fw-bold">
                            <?= $index + 1 ?>
                        </span>
                        <p class="mb-0 pt-1"><?= escape_output($recommendation) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <div class="alert alert-warning border-warning" role="note">
        <strong>Catatan penting:</strong> <?= escape_output($presentation['disclaimer']) ?>
    </div>

    <?php if ($answerSections !== []): ?>
        <section class="accordion mb-4" id="answerAccordion" aria-label="Rincian jawaban">
            <?php $sectionIndex = 0; ?>
            <?php foreach ($answerSections as $section): ?>
                <?php
                if (!is_array($section) || !is_array($section['items'] ?? null)) continue;
                $sectionIndex++;
                $headingId = 'answerHeading' . $sectionIndex;
                $collapseId = 'answerCollapse' . $sectionIndex;
                ?>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="<?= $headingId ?>">
                        <button class="accordion-button <?= $sectionIndex === 1 ? '' : 'collapsed' ?>" type="button"
                                data-bs-toggle="collapse" data-bs-target="#<?= $collapseId ?>"
                                aria-expanded="<?= $sectionIndex === 1 ? 'true' : 'false' ?>" aria-controls="<?= $collapseId ?>">
                            <?= escape_output($section['label'] ?? 'Rincian jawaban') ?>
                        </button>
                    </h2>
                    <div id="<?= $collapseId ?>" class="accordion-collapse collapse <?= $sectionIndex === 1 ? 'show' : '' ?>" aria-labelledby="<?= $headingId ?>">
                        <div class="accordion-body">
                            <dl class="row mb-0">
                                <?php foreach ($section['items'] as $item): ?>
                                    <?php if (!is_array($item)) continue; ?>
                                    <dt class="col-md-8 fw-normal mb-2"><?= escape_output($item['question'] ?? '-') ?></dt>
                                    <dd class="col-md-4 fw-semibold text-md-end mb-2"><?= escape_output($item['answer'] ?? '-') ?></dd>
                                <?php endforeach; ?>
                            </dl>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
    <?php endif; ?>

    <div class="d-flex flex-column flex-sm-row justify-content-center gap-2">
        <a href="konsultasi.php" class="btn btn-primary btn-lg">Konsultasi dengan UKS</a>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-lg">Kembali ke dashboard</a>
    </div>
</main>

<script src="/assets/vendor/bootstrap.bundle.min.js"></script>
<script src="/assets/vendor/chart.umd.min.js"></script>
<script src="../assets/js/app-init.js?v=20260831-safe-install"></script>
<script>if (window.lucide) window.lucide.createIcons();</script>
</body>
</html>
