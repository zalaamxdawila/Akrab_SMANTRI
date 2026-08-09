<?php

declare(strict_types=1);

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
    <div class="alert alert-secondary small mt-3 mb-0" role="note">
        <?= escape_output($disclaimer) ?>
    </div>
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
                labels: <?= json_encode($chart['labels'], $jsonFlags) ?>,
                datasets: [
                    {
                        label: 'Keluhan & gejala',
                        data: <?= json_encode($chart['series']['gejala'], $jsonFlags) ?>,
                        borderColor: '#dc3545',
                        backgroundColor: 'rgba(220, 53, 69, .12)',
                        tension: .25
                    },
                    {
                        label: 'Pola makan',
                        data: <?= json_encode($chart['series']['makan'], $jsonFlags) ?>,
                        borderColor: '#198754',
                        backgroundColor: 'rgba(25, 135, 84, .12)',
                        tension: .25
                    },
                    {
                        label: 'Pengetahuan',
                        data: <?= json_encode($chart['series']['pengetahuan'], $jsonFlags) ?>,
                        borderColor: '#0dcaf0',
                        backgroundColor: 'rgba(13, 202, 240, .12)',
                        tension: .25
                    },
                    {
                        label: 'Sikap & kesadaran',
                        data: <?= json_encode($chart['series']['sikap'], $jsonFlags) ?>,
                        borderColor: '#6f42c1',
                        backgroundColor: 'rgba(111, 66, 193, .12)',
                        tension: .25
                    }
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
