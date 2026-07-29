<?php

return [
    'version' => '006_ttd_haid_integrity',
    'description' => 'Enforce one TTD record per student/day and one active menstruation period',
    'up' => function (PDO $pdo): void {
        $pdo->exec(
            "DELETE k1 FROM konsumsi_ttd k1
             INNER JOIN konsumsi_ttd k2
               ON k1.user_id = k2.user_id AND k1.tanggal = k2.tanggal
              AND k1.id > k2.id"
        );
        $ttdIndex = $pdo->query(
            "SHOW INDEX FROM konsumsi_ttd WHERE Key_name = 'uq_konsumsi_ttd_user_date'"
        )->fetch();
        if (!$ttdIndex) {
            $pdo->exec(
                'ALTER TABLE konsumsi_ttd ADD UNIQUE KEY uq_konsumsi_ttd_user_date (user_id, tanggal)'
            );
        }

        $pdo->exec(
            "UPDATE riwayat_haid h1
             INNER JOIN riwayat_haid h2
               ON h1.user_id = h2.user_id
              AND h1.tanggal_selesai IS NULL
              AND h2.tanggal_selesai IS NULL
              AND h1.id < h2.id
             SET h1.tanggal_selesai = h1.tanggal_mulai"
        );
        $activeKey = $pdo->query(
            "SHOW COLUMNS FROM riwayat_haid LIKE 'active_key'"
        )->fetch();
        if (!$activeKey) {
            $pdo->exec(
                "ALTER TABLE riwayat_haid
                 ADD COLUMN active_key INT GENERATED ALWAYS AS
                    (IF(tanggal_selesai IS NULL, user_id, NULL)) STORED"
            );
        }

        $haidIndex = $pdo->query(
            "SHOW INDEX FROM riwayat_haid WHERE Key_name = 'uq_haid_one_active'"
        )->fetch();
        if (!$haidIndex) {
            $pdo->exec(
                'ALTER TABLE riwayat_haid ADD UNIQUE KEY uq_haid_one_active (active_key)'
            );
        }
    },
];
