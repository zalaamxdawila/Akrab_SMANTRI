<?php

declare(strict_types=1);

return [
    'version' => '010_parent_link_archive',
    'description' => 'Add reversible governance metadata for accounts and parent links',
    'up' => function (PDO $pdo): void {
        $columns = [
            'users' => [
                'status_changed_at' => 'TIMESTAMP NULL AFTER status',
                'status_changed_by' => 'INT NULL AFTER status_changed_at',
                'status_reason' => 'VARCHAR(500) NULL AFTER status_changed_by',
            ],
            'parent_student_links' => [
                'archived_at' => 'TIMESTAMP NULL AFTER reviewed_at',
                'archived_by' => 'INT NULL AFTER archived_at',
                'archive_reason' => 'VARCHAR(500) NULL AFTER archived_by',
            ],
        ];
        foreach ($columns as $table => $definitions) {
            foreach ($definitions as $name => $definition) {
                $column = $pdo->query(
                    "SHOW COLUMNS FROM {$table} LIKE " . $pdo->quote($name)
                )->fetch();
                if (!$column) {
                    $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$name} {$definition}");
                }
            }
        }

        $indexes = [
            'users' => ['idx_users_status_changed' => '(status, status_changed_at)'],
            'parent_student_links' => [
                'idx_parent_links_archive_status' => '(archived_at, status)',
            ],
        ];
        foreach ($indexes as $table => $definitions) {
            foreach ($definitions as $name => $definition) {
                $index = $pdo->query(
                    "SHOW INDEX FROM {$table} WHERE Key_name = " . $pdo->quote($name)
                )->fetch();
                if (!$index) {
                    $pdo->exec("ALTER TABLE {$table} ADD KEY {$name} {$definition}");
                }
            }
        }

        $constraints = [
            ['users', 'fk_users_status_changed_by',
                'FOREIGN KEY (status_changed_by) REFERENCES users(id) ON DELETE SET NULL'],
            ['parent_student_links', 'fk_parent_links_archived_by',
                'FOREIGN KEY (archived_by) REFERENCES users(id) ON DELETE SET NULL'],
        ];
        foreach ($constraints as [$table, $name, $definition]) {
            $statement = $pdo->prepare(
                'SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
                 WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ?
                   AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?'
            );
            $statement->execute([$table, $name, 'FOREIGN KEY']);
            if (!$statement->fetchColumn()) {
                $pdo->exec(
                    "ALTER TABLE {$table} ADD CONSTRAINT {$name} {$definition}"
                );
            }
        }
    },
];
