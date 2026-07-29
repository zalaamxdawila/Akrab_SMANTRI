<?php

declare(strict_types=1);

return [
    'version' => '008_superadmin_identity',
    'description' => 'Add a singleton superadmin role and account lifecycle status',
    'up' => function (PDO $pdo): void {
        $roleColumn = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'")->fetch();
        if (!$roleColumn) {
            throw new RuntimeException('Users role column is not available.');
        }
        if (stripos((string) $roleColumn['Type'], "'superadmin'") === false) {
            $pdo->exec(
                "ALTER TABLE users
                 MODIFY COLUMN role ENUM('siswa', 'uks', 'orangtua', 'superadmin')
                 NOT NULL DEFAULT 'siswa'"
            );
        }

        $statusColumn = $pdo->query(
            "SHOW COLUMNS FROM users LIKE 'status'"
        )->fetch();
        if (!$statusColumn) {
            $pdo->exec(
                "ALTER TABLE users
                 ADD COLUMN status ENUM('active', 'inactive', 'archived')
                 NOT NULL DEFAULT 'active' AFTER password_hash"
            );
        }

        $singletonColumn = $pdo->query(
            "SHOW COLUMNS FROM users LIKE 'superadmin_key'"
        )->fetch();
        if (!$singletonColumn) {
            $pdo->exec(
                "ALTER TABLE users
                 ADD COLUMN superadmin_key TINYINT
                 GENERATED ALWAYS AS
                    (IF(role = 'superadmin', 1, NULL)) STORED
                 AFTER status"
            );
        }

        $singletonIndex = $pdo->query(
            "SHOW INDEX FROM users WHERE Key_name = 'uq_users_single_superadmin'"
        )->fetch();
        if (!$singletonIndex) {
            $pdo->exec(
                'ALTER TABLE users
                 ADD UNIQUE KEY uq_users_single_superadmin (superadmin_key)'
            );
        }
    },
];
