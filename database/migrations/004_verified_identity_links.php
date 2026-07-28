<?php

return [
    'version' => '004_verified_identity_links',
    'description' => 'Add verified parent-student links, registration throttling, and audit events',
    'up' => function (PDO $pdo): void {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS parent_student_links (
                id INT AUTO_INCREMENT PRIMARY KEY,
                parent_id INT NOT NULL,
                student_id INT NULL,
                requested_student_username VARCHAR(50) NOT NULL,
                status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
                reviewed_by INT NULL,
                requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                reviewed_at TIMESTAMP NULL,
                UNIQUE KEY uq_parent_student_link (parent_id),
                KEY idx_parent_links_status (status),
                CONSTRAINT fk_parent_links_parent FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_parent_links_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE SET NULL,
                CONSTRAINT fk_parent_links_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS audit_log (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                actor_id INT NULL,
                action VARCHAR(80) NOT NULL,
                target_type VARCHAR(50) NOT NULL,
                target_id INT NULL,
                metadata_json JSON NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_audit_actor_created (actor_id, created_at),
                KEY idx_audit_action_created (action, created_at),
                CONSTRAINT fk_audit_actor FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS registration_attempts (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                client_hash CHAR(64) NOT NULL,
                attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_registration_attempts_client_time (client_hash, attempted_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $pdo->exec(
            "INSERT IGNORE INTO parent_student_links
                (parent_id, student_id, requested_student_username, status, reviewed_at)
             SELECT p.id, s.id, p.anak_username, 'approved', CURRENT_TIMESTAMP
             FROM users p
             JOIN users s ON s.username = p.anak_username AND s.role = 'siswa'
             WHERE p.role = 'orangtua' AND p.anak_username IS NOT NULL AND p.anak_username <> ''"
        );
    },
];
