<?php

declare(strict_types=1);

return [
    'version' => '017_lab_change_requests',
    'description' => 'Require approval before students replace questionnaire laboratory values',
    'up' => function (PDO $pdo): void {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS lab_change_requests (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                questionnaire_id BIGINT UNSIGNED NOT NULL,
                student_id BIGINT UNSIGNED NOT NULL,
                kadar_hb DECIMAL(5,2) NOT NULL,
                kadar_mchc DECIMAL(6,2) NOT NULL,
                kadar_mcv DECIMAL(6,2) NOT NULL,
                kadar_mch DECIMAL(6,2) NOT NULL,
                status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
                reviewed_by BIGINT UNSIGNED NULL,
                reviewer_role ENUM('uks', 'superadmin') NULL,
                reviewed_at DATETIME NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_lab_request_student_status (student_id, status, created_at),
                INDEX idx_lab_request_questionnaire (questionnaire_id, created_at),
                CONSTRAINT fk_lab_request_questionnaire FOREIGN KEY (questionnaire_id) REFERENCES kuesioner(id),
                CONSTRAINT fk_lab_request_student FOREIGN KEY (student_id) REFERENCES users(id),
                CONSTRAINT fk_lab_request_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id)
            ) ENGINE=InnoDB"
        );
    },
];
