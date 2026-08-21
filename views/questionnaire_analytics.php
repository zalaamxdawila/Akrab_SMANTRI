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

        <?php renderQuestionnaireAnswerCharts($presentation['answer_charts'] ?? []); ?>

        <?php renderQuestionnaireChoiceCharts($presentation['choice_charts'] ?? []); ?>

        <?php renderQuestionnaireAnswerOverview($presentation['answers']); ?>

        <?php renderLogisticRegressionExplanation($presentation['logistic'] ?? null); ?>

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

                <div class="alert alert-secondary small mt-4 mb-0" role="note">
                    <?= escape_output((string) $presentation['disclaimer']) ?>
                </div>
            </div>
        </details>
    </section>
    <?php
}

/** @param array<string, array<string, mixed>> $charts */
function renderQuestionnaireAnswerCharts(array $charts): void
{
    if (!isset($charts['gejala'], $charts['sikap'], $charts['pengetahuan'], $charts['makan'])) return;
    $chartDefinitions = [
        'gejala' => [
            'id' => 'answerSymptomChart',
            'title' => 'Diagram gejala anemia',
            'description' => 'Nilai 0–10 menunjukkan tingkat keparahan gejala yang dirasakan.',
            'dataset' => 'Tingkat gejala',
            'color' => '#dc3545',
        ],
        'sikap' => [
            'id' => 'answerAttitudeChart',
            'title' => 'Diagram jawaban sikap',
            'description' => 'Nilai 1 berarti sangat tidak setuju dan nilai 4 berarti sangat setuju.',
            'dataset' => 'Tingkat persetujuan',
            'color' => '#0d6efd',
        ],
        'pengetahuan' => [
            'id' => 'answerKnowledgeChart',
            'title' => 'Diagram jawaban pengetahuan',
            'description' => 'Menampilkan jumlah pilihan yang dipilih pada setiap pertanyaan.',
            'dataset' => 'Jumlah pilihan',
            'color' => '#198754',
        ],
        'makan' => [
            'id' => 'answerDietChart',
            'title' => 'Diagram pola makan',
            'description' => 'Nilai 1 tidak pernah, 2 kadang-kadang, dan 3 selalu.',
            'dataset' => 'Frekuensi',
            'color' => '#fd7e14',
        ],
    ];
    ?>
    <section class="card shadow-sm border-0 mb-4" aria-labelledby="answer-chart-title">
        <div class="card-header bg-white border-bottom p-3 p-md-4">
            <p class="text-uppercase small fw-semibold text-primary mb-1">Visualisasi respons</p>
            <h2 class="h4 mb-1" id="answer-chart-title">Diagram hasil kuesioner</h2>
            <p class="text-muted mb-0">Arahkan kursor atau sentuh batang untuk melihat pertanyaan lengkap.</p>
        </div>
        <div class="card-body p-3 p-md-4">
            <div class="row g-4">
                <?php foreach ($chartDefinitions as $key => $definition): ?>
                <div class="col-12 col-xl-6">
                    <h3 class="h6 mb-1"><?= escape_output($definition['title']) ?></h3>
                    <p class="small text-muted mb-3"><?= escape_output($definition['description']) ?></p>
                    <div style="height: 380px">
                        <canvas id="<?= $definition['id'] ?>" role="img"
                            aria-label="<?= escape_output($definition['title']) ?>"></canvas>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof Chart === 'undefined') return;
        const configs = <?= json_encode(array_map(
            static fn (string $key, array $definition): array => [
                'id' => $definition['id'],
                'dataset' => $definition['dataset'],
                'color' => $definition['color'],
                'labels' => $charts[$key]['labels'],
                'questions' => $charts[$key]['questions'],
                'values' => $charts[$key]['values'],
                'max' => $charts[$key]['max'],
            ],
            array_keys($chartDefinitions),
            array_values($chartDefinitions)
        ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        configs.forEach(function (config) {
            const canvas = document.getElementById(config.id);
            if (!canvas) return;
            new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: config.labels,
                    datasets: [{
                        label: config.dataset,
                        data: config.values,
                        backgroundColor: config.color,
                        borderColor: config.color,
                        borderWidth: 1
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                title: items => config.questions[items[0].dataIndex],
                                label: item => config.dataset + ': ' + item.raw
                            }
                        }
                    },
                    scales: {
                        x: { beginAtZero: true, max: config.max, ticks: { precision: 0 } },
                        y: { grid: { display: false } }
                    }
                }
            });
        });
    });
    </script>
    <?php
}

/** @param array<string, mixed>|null $model */
function renderLogisticRegressionExplanation(?array $model): void
{
    ?>
    <section class="card shadow-sm border-0 mb-4" aria-labelledby="logistic-model-title">
        <div class="card-header bg-white border-bottom p-3 p-md-4">
            <p class="text-uppercase small fw-semibold text-primary mb-1">Simulasi Model Penelitian</p>
            <h2 class="h4 mb-1" id="logistic-model-title">Cara regresi logistik menghitung risiko</h2>
            <p class="text-muted mb-0">Model mengubah empat nilai laboratorium menjadi probabilitas 0–100% melalui fungsi sigmoid.</p>
        </div>
        <div class="card-body p-3 p-md-4">
            <?php if ($model === null): ?>
                <div class="alert alert-warning mb-0" role="status">
                    Regresi logistik belum dapat dihitung. Lengkapi Hb, MCHC, MCV, dan MCH terlebih dahulu.
                </div>
            <?php else: ?>
                <div class="row g-3 mb-4 text-center" aria-label="Tahapan regresi logistik">
                    <?php foreach ([
                        ['1', 'Data laboratorium', 'Empat nilai diperiksa dan dimasukkan ke model.'],
                        ['2', 'Hitung nilai z', 'Indeks sel darah dipusatkan ke nilai acuan, lalu dikalikan koefisien.'],
                        ['3', 'Fungsi sigmoid', 'Nilai z diubah menjadi probabilitas 0–100%.'],
                        ['4', 'Kategori hasil', 'Probabilitas dipetakan menjadi rendah, sedang, atau tinggi.'],
                    ] as [$number, $title, $description]): ?>
                        <div class="col-6 col-lg-3"><div class="border rounded-3 p-3 h-100 bg-light">
                            <span class="badge rounded-pill text-bg-primary mb-2"><?= $number ?></span>
                            <h3 class="h6 mb-1"><?= escape_output($title) ?></h3>
                            <p class="small text-muted mb-0"><?= escape_output($description) ?></p>
                        </div></div>
                    <?php endforeach; ?>
                </div>
                <div class="row g-4">
                    <div class="col-lg-7">
                        <h3 class="h6">Kontribusi variabel pada persamaan</h3>
                        <div class="table-responsive"><table class="table table-sm align-middle">
                            <thead><tr><th>Variabel</th><th>Nilai</th><th>Nilai model</th><th>Koefisien</th><th>Kontribusi</th></tr></thead>
                            <tbody><?php foreach ($model['terms'] as $term): ?><tr>
                                <th><?= escape_output((string) $term['label']) ?></th>
                                <td><?= escape_output(number_format((float) $term['value'], 2, ',', '.')) ?> <?= escape_output((string) $term['unit']) ?></td>
                                <td><?php if ((float) $term['reference_value'] === 0.0): ?><?= escape_output(number_format((float) $term['centered_value'], 2, ',', '.')) ?><?php else: ?>(<?= escape_output(number_format((float) $term['value'], 2, ',', '.')) ?> − <?= escape_output(number_format((float) $term['reference_value'], 2, ',', '.')) ?>) = <?= escape_output(number_format((float) $term['centered_value'], 2, ',', '.')) ?><?php endif; ?></td>
                                <td><?= escape_output(number_format((float) $term['coefficient'], 2, ',', '.')) ?></td>
                                <td><?= escape_output(number_format((float) $term['contribution'], 3, ',', '.')) ?></td>
                            </tr><?php endforeach; ?></tbody>
                        </table></div>
                        <div class="bg-light border rounded-3 p-3 font-monospace small">
                            z = <?= escape_output(number_format((float) $model['intercept'], 2, ',', '.')) ?> − 1,5(Hb) − 0,1(MCH−29,5) − 0,1(MCHC−33,2) − 0,05(MCV−90)<br>
                            z = <?= escape_output(number_format((float) $model['z'], 4, ',', '.')) ?><br>
                            <?= escape_output((string) $model['equation']) ?>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="border rounded-3 p-4 h-100 d-flex flex-column justify-content-center text-center">
                            <p class="small text-uppercase fw-semibold text-muted mb-1">Probabilitas simulasi</p>
                            <p class="display-5 fw-bold text-primary mb-1"><?= escape_output(number_format((float) $model['probability'] * 100, 2, ',', '.')) ?>%</p>
                            <p class="mb-3">Kategori <strong><?= escape_output(ucfirst((string) $model['category'])) ?></strong></p>
                            <p class="small text-muted mb-0">Rendah &lt;33% · Sedang 33–&lt;66% · Tinggi ≥66%</p>
                        </div>
                    </div>
                </div>
                <div class="alert alert-secondary small mt-4 mb-0" role="note">
                    Simulasi Model Penelitian — bukan diagnosis medis dan belum menggantikan penilaian tenaga kesehatan.
                </div>
            <?php endif; ?>
        </div>
    </section>
    <?php
}

/** @param array<string, list<array<string, mixed>>> $sections */
function renderQuestionnaireChoiceCharts(array $sections): void
{
    if ($sections === []) return;
    $sectionLabels = [
        'gejala' => ['Keluhan dan gejala anemia', '#dc3545'],
        'sikap' => ['Sikap terhadap anemia', '#0d6efd'],
        'pengetahuan' => ['Pengetahuan tentang anemia', '#198754'],
        'makan' => ['Pola makan', '#fd7e14'],
    ];
    $jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
    ?>
    <section class="card shadow-sm border-0 mb-4" aria-labelledby="choice-chart-title">
        <div class="card-header bg-white border-bottom p-3 p-md-4">
            <p class="text-uppercase small fw-semibold text-primary mb-1">Perbandingan pilihan</p>
            <h2 class="h4 mb-1" id="choice-chart-title">Diagram pilihan setiap pertanyaan</h2>
            <p class="text-muted mb-0">Batang berwarna menunjukkan pilihan responden; batang abu-abu menunjukkan pilihan yang tidak dipilih.</p>
        </div>
        <div class="card-body p-3 p-md-4">
            <?php foreach ($sectionLabels as $sectionKey => [$sectionLabel, $color]):
                if (!isset($sections[$sectionKey])) continue;
                ?>
                <section class="<?= $sectionKey !== 'gejala' ? 'mt-5' : '' ?>" aria-labelledby="choice-section-<?= $sectionKey ?>">
                    <h3 class="h5 mb-3" id="choice-section-<?= $sectionKey ?>"><?= escape_output($sectionLabel) ?></h3>
                    <div class="row g-3">
                        <?php foreach ($sections[$sectionKey] as $index => $chart):
                            $canvasId = 'questionChoiceChart-' . $chart['key'];
                            $selectedText = $chart['selected'] === [] ? 'Tidak ada' : implode(', ', $chart['selected']);
                            $height = max(190, count($chart['labels']) * 42);
                            ?>
                            <div class="col-12 col-xl-6">
                                <article class="border rounded-3 p-3 h-100">
                                    <p class="small text-uppercase text-muted fw-semibold mb-1">Pertanyaan <?= $index + 1 ?></p>
                                    <h4 class="h6 mb-2"><?= escape_output((string) $chart['question']) ?></h4>
                                    <p class="small mb-3 fw-semibold">Pilihan yang dipilih: <?= escape_output($selectedText) ?></p>
                                    <div style="height: <?= $height ?>px">
                                        <canvas id="<?= escape_output($canvasId) ?>" role="img" aria-label="Diagram pilihan pertanyaan <?= $index + 1 ?>"></canvas>
                                    </div>
                                </article>
                            </div>
                            <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                const canvas = document.getElementById(<?= json_encode($canvasId, $jsonFlags) ?>);
                                if (!canvas || typeof Chart === 'undefined') return;
                                const values = <?= json_encode($chart['values'], $jsonFlags) ?>;
                                new Chart(canvas, {
                                    type: 'bar',
                                    data: {
                                        labels: <?= json_encode($chart['labels'], $jsonFlags) ?>,
                                        datasets: [{
                                            data: values,
                                            backgroundColor: values.map(value => value === 1 ? <?= json_encode($color, $jsonFlags) ?> : '#dee2e6'),
                                            borderColor: values.map(value => value === 1 ? <?= json_encode($color, $jsonFlags) ?> : '#adb5bd'),
                                            borderWidth: 1,
                                            borderRadius: 5
                                        }]
                                    },
                                    options: {
                                        indexAxis: 'y',
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        plugins: {
                                            legend: { display: false },
                                            tooltip: { callbacks: { label: context => context.raw === 1 ? 'Dipilih' : 'Tidak dipilih' } }
                                        },
                                        scales: {
                                            x: { beginAtZero: true, max: 1, ticks: { stepSize: 1, callback: value => value === 1 ? 'Dipilih' : 'Tidak dipilih' } },
                                            y: { grid: { display: false } }
                                        }
                                    }
                                });
                            });
                            </script>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
}

/** @param array<string, mixed> $answers */
function renderQuestionnaireAnswerOverview(array $answers): void
{
    $answerCount = 0;
    foreach (($answers['sections'] ?? []) as $section) {
        $answerCount += count($section['items'] ?? []);
    }
    ?>
    <section class="card shadow-sm border-0 mb-4 question-answer-overview"
             aria-labelledby="question-answer-title">
        <div class="card-header bg-white border-bottom p-3 p-md-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <p class="text-uppercase small fw-semibold text-primary mb-1">Rincian respons</p>
                    <h2 class="h4 mb-1" id="question-answer-title">Pertanyaan dan jawaban</h2>
                    <p class="text-muted mb-0">
                        Nilai yang diberikan pada setiap pertanyaan saat pengisian terakhir.
                    </p>
                </div>
                <?php if ($answers['available']): ?>
                    <span class="badge rounded-pill text-bg-primary px-3 py-2">
                        <?= $answerCount ?> jawaban tercatat
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body p-3 p-md-4">
            <?php if (!$answers['available']): ?>
                <div class="alert alert-info d-flex gap-2 mb-0" role="status">
                    <i data-lucide="info" aria-hidden="true"></i>
                    <span><?= escape_output((string) $answers['message']) ?></span>
                </div>
            <?php else: ?>
                <div class="row g-3 g-xl-4">
                    <?php foreach ($answers['sections'] as $section): ?>
                        <div class="col-12 col-xl-6">
                            <article class="border rounded-3 h-100 overflow-hidden">
                                <header class="bg-light border-bottom px-3 py-3 d-flex justify-content-between align-items-center gap-2">
                                    <h3 class="h6 fw-bold mb-0">
                                        <?= escape_output((string) $section['label']) ?>
                                    </h3>
                                    <span class="badge text-bg-secondary">
                                        <?= count($section['items']) ?> butir
                                    </span>
                                </header>
                                <ol class="list-group list-group-numbered list-group-flush mb-0">
                                    <?php foreach ($section['items'] as $item): ?>
                                        <li class="list-group-item d-flex gap-3 py-3"
                                            data-answer-key="<?= escape_output((string) $item['key']) ?>">
                                            <div class="ms-2 flex-grow-1">
                                                <p class="fw-semibold mb-2">
                                                    <?= escape_output((string) $item['question']) ?>
                                                </p>
                                                <div class="bg-light border rounded-2 px-3 py-2">
                                                    <span class="d-block small text-uppercase text-muted fw-semibold mb-1">
                                                        Jawaban
                                                    </span>
                                                    <span class="fw-bold text-body">
                                                        <?= escape_output((string) $item['answer']) ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ol>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="small text-muted mt-3 mb-0">
                    Rincian mengikuti versi pertanyaan saat data diisi
                    <?= $answers['version'] ? ' (' . escape_output((string) $answers['version']) . ')' : '' ?>.
                </p>
            <?php endif; ?>
        </div>
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
            <div class="col-sm-6 col-lg-4">
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
                    },
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
                    backgroundColor: ['#dc3545cc', '#198754cc', '#0dcaf0cc', '#6f42c1cc', '#fd7e14cc', '#20c997cc'],
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
