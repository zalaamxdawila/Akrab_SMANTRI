<?php

declare(strict_types=1);

return [
    'version' => '020_add_risk_factor_columns',
    'description' => 'Add internal and external risk factor score columns to kuesioner',
    'up' => function (PDO $pdo): void {
        $column = $pdo->query("SHOW COLUMNS FROM kuesioner LIKE 'skor_faktor_internal'");
        if (!$column->fetch()) {
            $pdo->exec('ALTER TABLE kuesioner ADD COLUMN skor_faktor_internal INT NOT NULL DEFAULT 0 AFTER skor_makan');
        }
        $column = $pdo->query("SHOW COLUMNS FROM kuesioner LIKE 'skor_faktor_eksternal'");
        if (!$column->fetch()) {
            $pdo->exec('ALTER TABLE kuesioner ADD COLUMN skor_faktor_eksternal INT NOT NULL DEFAULT 0 AFTER skor_faktor_internal');
        }
    },
];
