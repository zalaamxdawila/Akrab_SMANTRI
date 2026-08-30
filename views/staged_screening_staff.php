<?php

declare(strict_types=1);

/**
 * @param array<string, mixed>|null $presentation Null means risk questions are pending.
 * @param array<string, mixed> $screening
 */
function renderStagedScreeningForStaff(?array $presentation, array $screening): void
{
    if ($presentation === null) {
        ?>
        <div class="alert alert-warning mb-0" role="status">
            Tahap gejala selesai dengan rerata
            <strong><?= escape_output(number_format((float) ($screening['rerata_gejala'] ?? 0), 1, ',', '.')) ?></strong>.
            Siswa belum menyelesaikan pertanyaan faktor risiko.
        </div>
        <?php
        return;
    }
    ?>
    <section class="card border-0 shadow-sm mb-4" aria-labelledby="staff-screening-result">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                <div>
                    <p class="small text-uppercase text-muted fw-semibold mb-1">Skrining gejala bertahap</p>
                    <h3 class="h5 mb-1" id="staff-screening-result"><?= escape_output($presentation['title']) ?></h3>
                    <p class="text-muted mb-0"><?= escape_output($presentation['explanation']) ?></p>
                </div>
                <span class="badge text-bg-<?= escape_output($presentation['status_class']) ?> fs-6">
                    <?= escape_output($presentation['title']) ?>
                </span>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-<?= $presentation['show_risk_score'] ? '6' : '12' ?>">
                    <div class="border rounded-3 p-3 h-100">
                        <span class="small text-muted">Rerata gejala</span>
                        <div class="h3 mb-0"><?= escape_output($presentation['symptom_average']) ?>/10</div>
                    </div>
                </div>
                <?php if ($presentation['show_risk_score']): ?>
                    <div class="col-md-6">
                        <div class="border rounded-3 p-3 h-100">
                            <span class="small text-muted">Skor faktor risiko</span>
                            <div class="h3 mb-0"><?= escape_output($presentation['risk_percentage']) ?></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <h4 class="h6">Saran tindak lanjut</h4>
            <ul class="mb-3">
                <?php foreach ($presentation['recommendations'] as $recommendation): ?>
                    <li><?= escape_output($recommendation) ?></li>
                <?php endforeach; ?>
            </ul>
            <div class="alert alert-secondary small mb-0" role="note">
                <?= escape_output($presentation['disclaimer']) ?>
            </div>
        </div>
    </section>
    <?php
}
