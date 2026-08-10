<?php

declare(strict_types=1);

return [
    'version' => '016_questionnaire_result_link',
    'description' => 'Link clinical results to their questionnaire submission',
    'up' => function (PDO $pdo): void {
        $column = $pdo->query(
            'SHOW COLUMNS FROM hasil_deteksi LIKE '
            . $pdo->quote('questionnaire_id')
        );
        if (!$column->fetch()) {
            $pdo->exec(
                'ALTER TABLE hasil_deteksi
                 ADD COLUMN questionnaire_id INT NULL AFTER user_id'
            );
        }

        $index = $pdo->query(
            'SHOW INDEX FROM hasil_deteksi WHERE Key_name = '
            . $pdo->quote('idx_detection_questionnaire')
        );
        if (!$index->fetch()) {
            $pdo->exec(
                'ALTER TABLE hasil_deteksi
                 ADD INDEX idx_detection_questionnaire
                    (questionnaire_id, archived_at)'
            );
        }

        $constraint = $pdo->query(
            "SELECT CONSTRAINT_NAME
             FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'hasil_deteksi'
               AND CONSTRAINT_NAME = 'fk_detection_questionnaire'"
        );
        if (!$constraint->fetch()) {
            $pdo->exec(
                'ALTER TABLE hasil_deteksi
                 ADD CONSTRAINT fk_detection_questionnaire
                 FOREIGN KEY (questionnaire_id) REFERENCES kuesioner(id)
                 ON DELETE SET NULL'
            );
        }
    },
    'down' => function (PDO $pdo): void {
        $pdo->exec(
            'ALTER TABLE hasil_deteksi
             DROP FOREIGN KEY fk_detection_questionnaire,
             DROP INDEX idx_detection_questionnaire,
             DROP COLUMN questionnaire_id'
        );
    },
];
