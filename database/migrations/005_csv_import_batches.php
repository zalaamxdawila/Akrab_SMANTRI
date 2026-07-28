<?php

return [
    'version' => '005_csv_import_batches',
    'description' => 'Track CSV import batches for idempotency and auditability',
    'up' => function (PDO $pdo): void {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS csv_import_batches (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                batch_hash CHAR(64) NOT NULL UNIQUE,
                created_by INT NOT NULL,
                imported_count INT NOT NULL DEFAULT 0,
                skipped_count INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    },
];
