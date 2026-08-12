<?php

declare(strict_types=1);

/**
 * @param array<string, mixed> $presentation
 * @param array<string, mixed> $response
 */
function renderQuestionnaireResult(array $presentation, array $response): void
{
    $risk = $presentation['risk'];
    ?>
    <section class="questionnaire-result" aria-labelledby="questionnaire-summary-title">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                    <div>
                        <p class="text-uppercase small fw-semibold text-muted mb-1">Hasil Ringkas</p>
                        <h2 class="h4 mb-1" id="questionnaire-summary-title">Ringkasan skrining terakhir</h2>
                        <p class="text-muted mb-0">Fokus utama dan langkah yang dapat dilakukan berikutnya.</p>
                    </div>
                    <div class="text-md-end" aria-label="Kategori risiko">
                        <span class="badge text-bg-<?= escape_output((string) $risk['tone']) ?> fs-6 mb-1">
                            Risiko <?= escape_output((string) $risk['label']) ?>
                        </span>
                        <div class="small text-muted">
                            Probabilitas <?= escape_output((string) $risk['probability_label']) ?>
                            · <?= escape_output((string) $risk['date_label']) ?>
                        </div>
                    </div>
                </div>

                <?php renderQuestionnaireInsights($presentation['scores'], '', true); ?>

                <div class="row g-4 mt-1">
                    <div class="col-lg-6">
                        <h3 class="h6">Faktor yang paling perlu diperhatikan</h3>
                        <ol class="list-group list-group-numbered">
                            <?php foreach ($presentation['priorities'] as $priority): ?>
                                <li class="list-group-item">
                                    <strong><?= escape_output((string) $priority['label']) ?></strong>
                                    <span class="d-block small text-muted">
                                        <?= escape_output((string) $priority['level']) ?> —
                                        <?= escape_output((string) $priority['explanation']) ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    </div>
                    <div class="col-lg-6">
                        <h3 class="h6">Langkah berikutnya</h3>
                        <ul class="list-group">
                            <?php foreach ($presentation['actions'] as $action): ?>
                                <li class="list-group-item d-flex gap-2">
                                    <span class="text-primary" aria-hidden="true">✓</span>
                                    <span><?= escape_output((string) $action) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <div class="alert alert-secondary small mt-4 mb-0" role="note">
                    <?= escape_output((string) $presentation['disclaimer']) ?>
                </div>
            </div>
        </div>

        <details class="card shadow-sm border-0 mb-4">
            <summary class="card-header bg-white p-3 p-md-4 d-flex flex-column gap-1">
                <span class="h5 mb-0">Hasil Lengkap</span>
                <span class="small text-muted">Buka rincian skor, data pendukung, serta pertanyaan dan jawaban.</span>
            </summary>
            <div class="card-body p-4">
                <section aria-labelledby="complete-score-title">
                    <h3 class="h5" id="complete-score-title">Rincian skor per aspek</h3>
                    <?php renderQuestionnaireInsights($presentation['scores'], '', true); ?>
                </section>

                <section class="mt-4 pt-3 border-top" aria-labelledby="complete-lab-title">
                    <h3 class="h5" id="complete-lab-title">Data laboratorium</h3>
                    <?php renderLabSummary($response); ?>
                </section>

                <section class="mt-4 pt-3 border-top" aria-labelledby="complete-menstruation-title">
                    <h3 class="h5" id="complete-menstruation-title">Data menstruasi</h3>
                    <?php renderMenstruationSummary($response); ?>
                </section>

                <section class="mt-4 pt-3 border-top" aria-labelledby="complete-diet-title">
                    <h3 class="h5" id="complete-diet-title">Catatan pola makan</h3>
                    <?php renderDietSummary($response); ?>
                </section>

                <section class="mt-4 pt-3 border-top" aria-labelledby="complete-answer-title">
                    <h3 class="h5" id="complete-answer-title">Pertanyaan dan jawaban</h3>
                    <?php if (!$presentation['answers']['available']): ?>
                        <div class="alert alert-info mb-0" role="status">
                            <?= escape_output((string) $presentation['answers']['message']) ?>
                        </div>
                    <?php else: ?>
                        <?php foreach ($presentation['answers']['sections'] as $section): ?>
                            <section class="mt-3" aria-label="<?= escape_output((string) $section['label']) ?>">
                                <h4 class="h6 text-primary"><?= escape_output((string) $section['label']) ?></h4>
                                <dl class="list-group mb-0">
                                    <?php foreach ($section['items'] as $item): ?>
                                        <div class="list-group-item">
                                            <dt class="fw-semibold"><?= escape_output((string) $item['question']) ?></dt>
                                            <dd class="mb-0 text-muted"><?= escape_output((string) $item['answer']) ?></dd>
                                        </div>
                                    <?php endforeach; ?>
                                </dl>
                            </section>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </section>

                <div class="alert alert-secondary small mt-4 mb-0" role="note">
                    <?= escape_output((string) $presentation['disclaimer']) ?>
                </div>
            </div>
        </details>
    </section>
    <?php
}

/**
 * @param array<string, array<string, mixed>> $insights
 */
function renderQuestionnaireInsights(
    array $insights,
    string $disclaimer,
    bool $showValues = true
): void {
    ?>
    <div class="row g-3 questionnaire-insights">
        <?php foreach ($insights as $insight): ?>
            <div class="col-sm-6 col-xl-3">
                <article class="p-3 bg-light border rounded-3 h-100">
                    <div class="d-flex justify-content-between gap-2">
                        <h3 class="h6 mb-1"><?= escape_output((string) $insight['label']) ?></h3>
                        <span class="badge text-bg-<?= escape_output((string) $insight['tone']) ?>">
                            <?= escape_output((string) $insight['level']) ?>
                        </span>
                    </div>
                    <?php if ($showValues): ?>
                        <p class="h4 mb-2">
                            <?= escape_output(number_format((float) $insight['value'], 1, ',', '.')) ?>
                            <small class="text-muted fs-6">
                                / <?= escape_output(number_format((float) $insight['max'], 0, ',', '.')) ?>
                            </small>
                        </p>
                    <?php endif; ?>
                    <div class="progress mb-2" role="progressbar"
                         aria-label="<?= escape_output((string) $insight['label']) ?>"
                         aria-valuenow="<?= escape_output((string) $insight['percentage']) ?>"
                         aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar bg-<?= escape_output((string) $insight['tone']) ?>"
                             style="width: <?= escape_output((string) $insight['percentage']) ?>%"></div>
                    </div>
                    <p class="small text-muted mb-0">
                        <?= escape_output((string) $insight['explanation']) ?>
                    </p>
                </article>
            </div>
        <?php endforeach; ?>
    </div>
    <?php if ($disclaimer !== ''): ?>
        <div class="alert alert-secondary small mt-3 mb-0" role="note">
            <?= escape_output($disclaimer) ?>
        </div>
    <?php endif; ?>
    <?php
}

/**
 * @param array<string, mixed> $response
 */
function renderLabSummary(array $response): void
{
    $fields = [
        'kadar_hb' => ['Hb', 'g/dL'],
        'kadar_mchc' => ['MCHC', 'g/dL'],
        'kadar_mcv' => ['MCV', 'fL'],
        'kadar_mch' => ['MCH', 'pg'],
    ];
    ?>
    <dl class="row g-2 mb-0">
        <?php foreach ($fields as $field => [$label, $unit]): ?>
            <div class="col-6 col-lg-3">
                <dt class="small text-muted"><?= escape_output($label) ?></dt>
                <dd class="fw-semibold mb-0">
                    <?php if ($response[$field] === null): ?>
                        Belum tersedia
                    <?php else: ?>
                        <?= escape_output(number_format((float) $response[$field], 1, ',', '.')) ?>
                        <?= escape_output($unit) ?>
                    <?php endif; ?>
                </dd>
            </div>
        <?php endforeach; ?>
    </dl>
    <?php
}

/**
 * @param array<string, mixed> $response
 */
function renderMenstruationSummary(array $response): void
{
    ?>
    <dl class="row g-2 mb-0 mt-3">
        <div class="col-6 col-lg-3">
            <dt class="small text-muted">Sudah Menstruasi</dt>
            <dd class="fw-semibold mb-0"><?= escape_output(ucfirst((string) ($response['mens_sudah'] ?? '-'))) ?></dd>
        </div>
        <div class="col-6 col-lg-3">
            <dt class="small text-muted">Siklus Teratur</dt>
            <dd class="fw-semibold mb-0"><?= escape_output(ucfirst((string) ($response['mens_teratur'] ?? '-'))) ?></dd>
        </div>
        <div class="col-6 col-lg-3">
            <dt class="small text-muted">Lama Hari</dt>
            <dd class="fw-semibold mb-0"><?= $response['mens_lama_hari'] ? (int) $response['mens_lama_hari'].' Hari' : '-' ?></dd>
        </div>
        <div class="col-6 col-lg-3">
            <dt class="small text-muted">Jarak Siklus</dt>
            <dd class="fw-semibold mb-0"><?= $response['mens_jarak_siklus'] ? (int) $response['mens_jarak_siklus'].' Hari' : '-' ?></dd>
        </div>
    </dl>
    <?php
}

/**
 * @param array<string, mixed> $response
 */
function renderDietSummary(array $response): void
{
    ?>
    <dl class="row g-2 mb-0 mt-3">
        <div class="col-12">
            <dt class="small text-muted">Makanan Sering Dikonsumsi</dt>
            <dd class="fw-semibold mb-0"><?= nl2br(escape_output((string) ($response['makanan_dikonsumsi'] ?? '-'))) ?></dd>
        </div>
    </dl>
    <?php
}

/**
 * @param array{labels:list<string>,series:array<string,list<float>>} $chart
 */
function renderQuestionnaireHistoryChartScript(string $canvasId, array $chart): void
{
    $jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
    $singleResponse = count($chart['labels']) === 1;
    $displayLabels = $singleResponse
        ? ['Keluhan & gejala', 'Pola makan', 'Pengetahuan', 'Sikap & kesadaran']
        : $chart['labels'];
    $singleResponseValues = $singleResponse
        ? [
            $chart['series']['gejala'][0] ?? 0,
            $chart['series']['makan'][0] ?? 0,
            $chart['series']['pengetahuan'][0] ?? 0,
            $chart['series']['sikap'][0] ?? 0,
        ]
        : [];
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const canvas = document.getElementById(<?= json_encode($canvasId, $jsonFlags) ?>);
        if (!canvas || typeof Chart === 'undefined') return;
        const chartTextColor = () =>
            document.documentElement.getAttribute('data-bs-theme') === 'dark'
                ? '#cbd5e1'
                : '#334155';
        const chart = new Chart(canvas, {
            type: 'line',
            data: {
                labels: <?= json_encode($displayLabels, $jsonFlags) ?>,
                datasets: [
                    <?php if ($singleResponse): ?>
                    {
                        label: <?= json_encode('Hasil '.$chart['labels'][0], $jsonFlags) ?>,
                        data: <?= json_encode($singleResponseValues, $jsonFlags) ?>,
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13, 110, 253, .12)',
                        showLine: true,
                        borderWidth: 3,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        pointBorderWidth: 2,
                        pointBackgroundColor: '#ffffff',
                        fill: false,
                        tension: .25
                    }
                    <?php else: ?>
                    {
                        label: 'Keluhan & gejala',
                        data: <?= json_encode($chart['series']['gejala'], $jsonFlags) ?>,
                        borderColor: '#dc3545',
                        backgroundColor: 'rgba(220, 53, 69, .12)',
                        showLine: true,
                        borderWidth: 3,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBorderWidth: 2,
                        pointBackgroundColor: '#ffffff',
                        fill: false,
                        tension: .25
                    },
                    {
                        label: 'Pola makan',
                        data: <?= json_encode($chart['series']['makan'], $jsonFlags) ?>,
                        borderColor: '#198754',
                        backgroundColor: 'rgba(25, 135, 84, .12)',
                        showLine: true,
                        borderWidth: 3,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBorderWidth: 2,
                        pointBackgroundColor: '#ffffff',
                        fill: false,
                        tension: .25
                    },
                    {
                        label: 'Pengetahuan',
                        data: <?= json_encode($chart['series']['pengetahuan'], $jsonFlags) ?>,
                        borderColor: '#0dcaf0',
                        backgroundColor: 'rgba(13, 202, 240, .12)',
                        showLine: true,
                        borderWidth: 3,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBorderWidth: 2,
                        pointBackgroundColor: '#ffffff',
                        fill: false,
                        tension: .25
                    },
                    {
                        label: 'Sikap & kesadaran',
                        data: <?= json_encode($chart['series']['sikap'], $jsonFlags) ?>,
                        borderColor: '#6f42c1',
                        backgroundColor: 'rgba(111, 66, 193, .12)',
                        showLine: true,
                        borderWidth: 3,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBorderWidth: 2,
                        pointBackgroundColor: '#ffffff',
                        fill: false,
                        tension: .25
                    }
                    <?php endif; ?>
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { labels: { color: chartTextColor() } },
                    tooltip: {
                        callbacks: {
                            label: context => `${context.dataset.label}: ${context.parsed.y}%`
                        }
                    }
                },
                scales: {
                    x: { ticks: { color: chartTextColor() }, grid: { color: 'rgba(148, 163, 184, .18)' } },
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: { color: chartTextColor() },
                        grid: { color: 'rgba(148, 163, 184, .18)' },
                        title: {
                            display: true,
                            text: 'Persentase dari skor maksimum',
                            color: chartTextColor()
                        }
                    }
                }
            }
        });
        document.addEventListener('akrab:themechange', () => {
            const color = chartTextColor();
            chart.options.plugins.legend.labels.color = color;
            chart.options.scales.x.ticks.color = color;
            chart.options.scales.y.ticks.color = color;
            chart.options.scales.y.title.color = color;
            chart.update();
        });
    });
    </script>
    <?php
}

/**
 * @param array<string, array<string, mixed>> $insights
 */
function renderQuestionnaireAverageChartScript(string $canvasId, array $insights): void
{
    $labels = array_column($insights, 'label');
    $values = array_map(
        static fn (array $insight): float => (float) $insight['percentage'],
        array_values($insights)
    );
    $jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const canvas = document.getElementById(<?= json_encode($canvasId, $jsonFlags) ?>);
        if (!canvas || typeof Chart === 'undefined') return;
        const chartTextColor = () =>
            document.documentElement.getAttribute('data-bs-theme') === 'dark'
                ? '#cbd5e1'
                : '#334155';
        const chart = new Chart(canvas, {
            type: 'bar',
            data: {
                labels: <?= json_encode($labels, $jsonFlags) ?>,
                datasets: [{
                    label: 'Rata-rata seluruh pengisian',
                    data: <?= json_encode($values, $jsonFlags) ?>,
                    backgroundColor: ['#dc3545cc', '#198754cc', '#0dcaf0cc', '#6f42c1cc'],
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        ticks: { color: chartTextColor() },
                        grid: { color: 'rgba(148, 163, 184, .18)' }
                    },
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: { color: chartTextColor() },
                        grid: { color: 'rgba(148, 163, 184, .18)' },
                        title: {
                            display: true,
                            text: 'Persentase dari skor maksimum',
                            color: chartTextColor()
                        }
                    }
                },
                plugins: { legend: { display: false } }
            }
        });
        document.addEventListener('akrab:themechange', () => {
            const color = chartTextColor();
            chart.options.scales.x.ticks.color = color;
            chart.options.scales.y.ticks.color = color;
            chart.options.scales.y.title.color = color;
            chart.update();
        });
    });
    </script>
    <?php
}
