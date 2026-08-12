<?php

return [
    'version' => '001_baseline_schema',
    'description' => 'Create the canonical AKRAB schema without production seed data',
    'up' => function (PDO $pdo): void {
        MigrationRunner::executeSqlFile($pdo, dirname(__DIR__) . '/schema.sql');
    },
];
