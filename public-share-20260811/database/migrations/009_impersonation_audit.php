<?php

declare(strict_types=1);

return [
    'version' => '009_impersonation_audit',
    'description' => 'Add impersonation lifecycle and dual-actor audit context',
    'up' => function (PDO $pdo): void {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS impersonation_sessions (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                superadmin_id INT NOT NULL,
                target_user_id INT NOT NULL,
                reason_category VARCHAR(40) NOT NULL,
                reason_note VARCHAR(500) NOT NULL,
                started_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                expires_at TIMESTAMP NOT NULL,
                ended_at TIMESTAMP NULL,
                status ENUM('active', 'ended', 'expired', 'invalidated')
                    NOT NULL DEFAULT 'active',
                KEY idx_impersonation_superadmin_status
                    (superadmin_id, status),
                KEY idx_impersonation_target_status
                    (target_user_id, status),
                KEY idx_impersonation_expiry (status, expires_at),
                CONSTRAINT fk_impersonation_superadmin
                    FOREIGN KEY (superadmin_id) REFERENCES users(id),
                CONSTRAINT fk_impersonation_target
                    FOREIGN KEY (target_user_id) REFERENCES users(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
              COLLATE=utf8mb4_unicode_ci"
        );

        $columns = [
            'authenticated_actor_id' => 'INT NULL AFTER actor_id',
            'effective_actor_id' => 'INT NULL AFTER authenticated_actor_id',
            'impersonation_session_id' => 'BIGINT NULL AFTER effective_actor_id',
            'request_id' => 'VARCHAR(64) NULL AFTER impersonation_session_id',
        ];
        foreach ($columns as $name => $definition) {
            $column = $pdo->query(
                "SHOW COLUMNS FROM audit_log LIKE " . $pdo->quote($name)
            )->fetch();
            if (!$column) {
                $pdo->exec("ALTER TABLE audit_log ADD COLUMN {$name} {$definition}");
            }
        }

        $indexes = [
            'idx_audit_authenticated_created'
                => '(authenticated_actor_id, created_at)',
            'idx_audit_effective_created'
                => '(effective_actor_id, created_at)',
            'idx_audit_impersonation'
                => '(impersonation_session_id)',
            'idx_audit_request'
                => '(request_id)',
        ];
        foreach ($indexes as $name => $definition) {
            $index = $pdo->query(
                "SHOW INDEX FROM audit_log WHERE Key_name = " . $pdo->quote($name)
            )->fetch();
            if (!$index) {
                $pdo->exec("ALTER TABLE audit_log ADD KEY {$name} {$definition}");
            }
        }

        $constraints = [
            'fk_audit_authenticated_actor'
                => 'FOREIGN KEY (authenticated_actor_id) REFERENCES users(id) ON DELETE SET NULL',
            'fk_audit_effective_actor'
                => 'FOREIGN KEY (effective_actor_id) REFERENCES users(id) ON DELETE SET NULL',
            'fk_audit_impersonation_session'
                => 'FOREIGN KEY (impersonation_session_id) REFERENCES impersonation_sessions(id) ON DELETE SET NULL',
        ];
        foreach ($constraints as $name => $definition) {
            $statement = $pdo->prepare(
                'SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
                 WHERE CONSTRAINT_SCHEMA = DATABASE()
                   AND TABLE_NAME = ?
                   AND CONSTRAINT_NAME = ?
                   AND CONSTRAINT_TYPE = ?'
            );
            $statement->execute(['audit_log', $name, 'FOREIGN KEY']);
            if (!$statement->fetchColumn()) {
                $pdo->exec(
                    "ALTER TABLE audit_log ADD CONSTRAINT {$name} {$definition}"
                );
            }
        }
    },
];
