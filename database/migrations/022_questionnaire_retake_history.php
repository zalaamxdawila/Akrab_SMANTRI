<?php

declare(strict_types=1);

return [
    'version' => '022_questionnaire_retake_history',
    'description' => 'Keep reset questionnaires as personal history outside primary reporting',
    'up' => function (PDO $pdo): void {
        $columns = [
            'history_only_at' => 'history_only_at TIMESTAMP NULL AFTER archive_reason',
            'history_only_by' => 'history_only_by INT NULL AFTER history_only_at',
            'history_only_reason' => 'history_only_reason VARCHAR(500) NULL AFTER history_only_by',
        ];
        foreach ($columns as $name => $definition) {
            $column = $pdo->query(
                'SHOW COLUMNS FROM kuesioner LIKE ' . $pdo->quote($name)
            );
            if (!$column->fetch()) {
                $pdo->exec('ALTER TABLE kuesioner ADD COLUMN ' . $definition);
            }
        }

        $index = $pdo->query(
            'SHOW INDEX FROM kuesioner WHERE Key_name = '
            . $pdo->quote('idx_kuesioner_primary_history')
        );
        if (!$index->fetch()) {
            $pdo->exec(
                'ALTER TABLE kuesioner ADD INDEX idx_kuesioner_primary_history '
                . '(user_id, history_only_at, archived_at, created_at)'
            );
        }

        $constraint = $pdo->query(
            "SELECT CONSTRAINT_NAME
             FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'kuesioner'
               AND CONSTRAINT_NAME = 'fk_kuesioner_history_only_by'"
        );
        if (!$constraint->fetch()) {
            $pdo->exec(
                'ALTER TABLE kuesioner
                 ADD CONSTRAINT fk_kuesioner_history_only_by
                 FOREIGN KEY (history_only_by) REFERENCES users(id)
                 ON DELETE SET NULL'
            );
        }
    },
    'down' => function (PDO $pdo): void {
        $pdo->exec(
            'ALTER TABLE kuesioner
             DROP FOREIGN KEY fk_kuesioner_history_only_by,
             DROP INDEX idx_kuesioner_primary_history,
             DROP COLUMN history_only_reason,
             DROP COLUMN history_only_by,
             DROP COLUMN history_only_at'
        );
    },
];
