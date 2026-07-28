<?php

return [
    'version' => '007_model_metadata',
    'description' => 'Record model version and checksum with each clinical result',
    'up' => function (PDO $pdo): void {
        $pdo->exec("ALTER TABLE hasil_deteksi ADD COLUMN model_version VARCHAR(80) NULL AFTER kategori_risiko");
        $pdo->exec("ALTER TABLE hasil_deteksi ADD COLUMN model_checksum CHAR(64) NULL AFTER model_version");
    },
];
