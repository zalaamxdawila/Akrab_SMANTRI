<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/config/environment.php';
require_once dirname(__DIR__) . '/database/MigrationRunner.php';

$projectRoot = dirname(__DIR__);
loadEnvironmentFile($projectRoot . '/.env');

$isProduction = environmentValue('AKRAB_APP_ENV', 'production') === 'production';
$productionAllowed = in_array('--allow-production', $argv, true);

if ($isProduction && !$productionAllowed) {
    fwrite(STDERR, "Production migration requires --allow-production.\n");
    exit(1);
}

$dbHost = requireEnvironmentValue('AKRAB_DB_HOST');
$dbName = requireEnvironmentValue('AKRAB_DB_NAME');
$migrationUser = requireEnvironmentValue('AKRAB_MIGRATION_DB_USER');
$migrationPass = requireEnvironmentValue('AKRAB_MIGRATION_DB_PASS');
$migrationPdo = new PDO(
    "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
    $migrationUser,
    $migrationPass,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);

$runner = new MigrationRunner($migrationPdo, $projectRoot . '/database/migrations');
$completed = $runner->migrate();

if ($completed === []) {
    echo "Database schema is already current.\n";
    exit(0);
}

foreach ($completed as $version) {
    echo "Applied {$version}\n";
}
