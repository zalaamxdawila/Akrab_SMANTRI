<?php

declare(strict_types=1);

return [
    'version' => '011_health_record_governance',
    'description' => 'Add reversible correction and archive metadata to health records',
    'up' => function (PDO $pdo): void {
        $tables = [
            'kuesioner',
            'hasil_deteksi',
            'kadar_hb',
            'konsumsi_ttd',
            'riwayat_haid',
        ];
        $columns = [
            'corrected_at' => 'TIMESTAMP NULL',
            'corrected_by' => 'INT NULL',
            'correction_reason' => 'VARCHAR(500) NULL',
            'archived_at' => 'TIMESTAMP NULL',
            'archived_by' => 'INT NULL',
            'archive_reason' => 'VARCHAR(500) NULL',
        ];
        foreach ($tables as $table) {
            foreach ($columns as $name => $definition) {
                $column = $pdo->query(
                    "SHOW COLUMNS FROM {$table} LIKE " . $pdo->quote($name)
                )->fetch();
                if (!$column) {
                    $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$name} {$definition}");
                }
            }
            $indexName = "idx_{$table}_archive";
            $index = $pdo->query(
                "SHOW INDEX FROM {$table} WHERE Key_name = "
                . $pdo->quote($indexName)
            )->fetch();
            if (!$index) {
                $pdo->exec(
                    "ALTER TABLE {$table} ADD KEY {$indexName} (archived_at, user_id)"
                );
            }
            foreach (['corrected_by', 'archived_by'] as $actorColumn) {
                $constraint = "fk_{$table}_{$actorColumn}";
                $check = $pdo->prepare(
                    'SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
                     WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ?
                       AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?'
                );
                $check->execute([$table, $constraint, 'FOREIGN KEY']);
                if (!$check->fetchColumn()) {
                    $pdo->exec(
                        "ALTER TABLE {$table} ADD CONSTRAINT {$constraint}
                         FOREIGN KEY ({$actorColumn}) REFERENCES users(id)
                         ON DELETE SET NULL"
                    );
                }
            }
        }
    },
];
