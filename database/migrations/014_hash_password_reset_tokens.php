<?php

declare(strict_types=1);

return [
    'version' => '014_hash_password_reset_tokens',
    'description' => 'Store password reset tokens only as one-way SHA-256 digests',
    'up' => function (PDO $pdo): void {
        $columnExists = static function (string $column) use ($pdo): bool {
            $statement = $pdo->prepare(
                'SHOW COLUMNS FROM password_reset_requests LIKE ?'
            );
            $statement->execute([$column]);

            return (bool) $statement->fetch();
        };
        $indexExists = static function (string $index) use ($pdo): bool {
            $statement = $pdo->prepare(
                'SHOW INDEX FROM password_reset_requests WHERE Key_name = ?'
            );
            $statement->execute([$index]);

            return (bool) $statement->fetch();
        };

        if (!$columnExists('token_hash')) {
            $pdo->exec(
                'ALTER TABLE password_reset_requests
                 ADD COLUMN token_hash CHAR(64) NULL AFTER status'
            );
        }

        if ($columnExists('token')) {
            $pdo->exec(
                'UPDATE password_reset_requests
                 SET token_hash = SHA2(token, 256)
                 WHERE token IS NOT NULL AND token_hash IS NULL'
            );
            if ($indexExists('uq_password_reset_token')) {
                $pdo->exec(
                    'ALTER TABLE password_reset_requests
                     DROP INDEX uq_password_reset_token'
                );
            }
            $pdo->exec(
                'ALTER TABLE password_reset_requests DROP COLUMN token'
            );
        }

        if (!$indexExists('uq_password_reset_token_hash')) {
            $pdo->exec(
                'ALTER TABLE password_reset_requests
                 ADD UNIQUE KEY uq_password_reset_token_hash (token_hash)'
            );
        }
    },
];
