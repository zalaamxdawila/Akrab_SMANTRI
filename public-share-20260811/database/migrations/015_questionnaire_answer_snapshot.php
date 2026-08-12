<?php

declare(strict_types=1);

return [
    'version' => '015_questionnaire_answer_snapshot',
    'description' => 'Store versioned snapshots of visible questionnaire answers',
    'up' => function (PDO $pdo): void {
        $statement = $pdo->query(
            'SHOW COLUMNS FROM kuesioner LIKE '
            . $pdo->quote('answers_snapshot')
        );
        if (!$statement->fetch()) {
            $pdo->exec(
                'ALTER TABLE kuesioner
                 ADD COLUMN answers_snapshot JSON NULL AFTER makanan_dikonsumsi'
            );
        }
    },
];
