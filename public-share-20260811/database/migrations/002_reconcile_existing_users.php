<?php

return [
    'version' => '002_reconcile_existing_users',
    'description' => 'Reconcile parent role and link column on legacy databases',
    'up' => function (PDO $pdo): void {
        $pdo->exec(
            "ALTER TABLE users
             MODIFY COLUMN role ENUM('siswa', 'uks', 'orangtua') NOT NULL DEFAULT 'siswa'"
        );

        $column = $pdo->query("SHOW COLUMNS FROM users LIKE 'anak_username'")->fetch();
        if (!$column) {
            $pdo->exec('ALTER TABLE users ADD COLUMN anak_username VARCHAR(50) NULL AFTER kelas');
        }
    },
];
