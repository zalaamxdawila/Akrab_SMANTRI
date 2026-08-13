<?php

declare(strict_types=1);

return [
    'version' => '018_recalculate_centered_logistic_results',
    'description' => 'Append corrected research results using centered blood indices',
    'up' => function (PDO $pdo): void {
        $version = 'AKRAB-RESEARCH-CENTERED-v1.1';
        $checksum = '94e15b011a955aca8286041ab730ec47540c398987250e979700e3d9bc7198da';

        $statement = $pdo->prepare(
            "INSERT INTO hasil_deteksi
                (user_id, questionnaire_id, probabilitas_risiko,
                 kategori_risiko, model_version, model_checksum, tanggal)
             SELECT calculated.user_id, calculated.questionnaire_id,
                    LEAST(0.99, GREATEST(0, calculated.probability)),
                    CASE
                        WHEN calculated.probability < 0.33 THEN 'rendah'
                        WHEN calculated.probability < 0.66 THEN 'sedang'
                        ELSE 'tinggi'
                    END,
                    ?, ?, CURRENT_DATE
             FROM (
                 SELECT k.user_id, k.id AS questionnaire_id,
                        1 / (1 + EXP(-(
                            15.5
                            - 1.5 * k.kadar_hb
                            - 0.1 * (k.kadar_mch - 29.5)
                            - 0.1 * (k.kadar_mchc - 33.2)
                            - 0.05 * (k.kadar_mcv - 90.0)
                        ))) AS probability
                 FROM kuesioner k
                 WHERE k.archived_at IS NULL
                   AND k.kadar_hb IS NOT NULL
                   AND k.kadar_mch IS NOT NULL
                   AND k.kadar_mchc IS NOT NULL
                   AND k.kadar_mcv IS NOT NULL
                   AND NOT EXISTS (
                       SELECT 1 FROM kuesioner newer
                       WHERE newer.user_id = k.user_id
                         AND newer.archived_at IS NULL
                         AND (newer.created_at > k.created_at
                              OR (newer.created_at = k.created_at AND newer.id > k.id))
                   )
             ) calculated
             WHERE NOT EXISTS (
                 SELECT 1 FROM hasil_deteksi existing
                 WHERE existing.questionnaire_id = calculated.questionnaire_id
                   AND existing.model_version = ?
                   AND existing.archived_at IS NULL
             )"
        );
        $statement->execute([$version, $checksum, $version]);
    },
];
