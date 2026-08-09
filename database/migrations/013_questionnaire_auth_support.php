<?php

declare(strict_types=1);

return [
    'version' => '013_questionnaire_auth_support',
    'description' => 'Add questionnaire details and managed password reset and passkey storage',
    'up' => function (PDO $pdo): void {
        $columnExists = static function (PDO $pdo, string $table, string $column): bool {
            $statement = $pdo->prepare("SHOW COLUMNS FROM {$table} LIKE ?");
            $statement->execute([$column]);
            return (bool) $statement->fetch();
        };
        $indexExists = static function (PDO $pdo, string $table, string $index): bool {
            $statement = $pdo->prepare("SHOW INDEX FROM {$table} WHERE Key_name = ?");
            $statement->execute([$index]);
            return (bool) $statement->fetch();
        };

        if (!$columnExists($pdo, 'kuesioner', 'mens_jarak_siklus')) {
            $pdo->exec('ALTER TABLE kuesioner ADD COLUMN mens_jarak_siklus INT NULL AFTER mens_lama_hari');
        }
        if (!$columnExists($pdo, 'kuesioner', 'makanan_dikonsumsi')) {
            $pdo->exec('ALTER TABLE kuesioner ADD COLUMN makanan_dikonsumsi TEXT NULL AFTER skor_makan');
        }
        $pdo->exec('ALTER TABLE kuesioner MODIFY COLUMN pendidikan VARCHAR(150) NULL');

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS password_reset_requests (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                status ENUM('pending', 'completed') NOT NULL DEFAULT 'pending',
                token CHAR(64) NULL,
                expires_at TIMESTAMP NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_password_reset_user_status (user_id, status),
                UNIQUE KEY uq_password_reset_token (token),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        if (!$columnExists($pdo, 'password_reset_requests', 'token')) {
            $pdo->exec('ALTER TABLE password_reset_requests ADD COLUMN token CHAR(64) NULL AFTER status');
        }
        if (!$columnExists($pdo, 'password_reset_requests', 'expires_at')) {
            $pdo->exec('ALTER TABLE password_reset_requests ADD COLUMN expires_at TIMESTAMP NULL AFTER token');
        }
        if (!$indexExists($pdo, 'password_reset_requests', 'idx_password_reset_user_status')) {
            $pdo->exec('ALTER TABLE password_reset_requests ADD KEY idx_password_reset_user_status (user_id, status)');
        }
        if (!$indexExists($pdo, 'password_reset_requests', 'uq_password_reset_token')) {
            $pdo->exec('ALTER TABLE password_reset_requests ADD UNIQUE KEY uq_password_reset_token (token)');
        }

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS webauthn_credentials (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                credential_id TEXT NOT NULL,
                public_key TEXT NOT NULL,
                sign_count INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_webauthn_user (user_id),
                UNIQUE KEY uq_webauthn_credential (credential_id(255)),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        if (!$indexExists($pdo, 'webauthn_credentials', 'idx_webauthn_user')) {
            $pdo->exec('ALTER TABLE webauthn_credentials ADD KEY idx_webauthn_user (user_id)');
        }
        if (!$indexExists($pdo, 'webauthn_credentials', 'uq_webauthn_credential')) {
            $pdo->exec('ALTER TABLE webauthn_credentials ADD UNIQUE KEY uq_webauthn_credential (credential_id(255))');
        }
    },
];
