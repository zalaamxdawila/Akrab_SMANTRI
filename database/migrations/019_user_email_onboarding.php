<?php

declare(strict_types=1);

return [
    'version' => '019_user_email_onboarding',
    'description' => 'Add unique user email for onboarding and password recovery',
    'up' => function (PDO $pdo): void {
        $column = $pdo->query("SHOW COLUMNS FROM users LIKE 'email'");
        if (!$column->fetch()) {
            $pdo->exec('ALTER TABLE users ADD COLUMN email VARCHAR(254) NULL AFTER username');
        }
        $index = $pdo->query("SHOW INDEX FROM users WHERE Key_name = 'uq_users_email'");
        if (!$index->fetch()) {
            $pdo->exec('ALTER TABLE users ADD UNIQUE KEY uq_users_email (email)');
        }
    },
];
