<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/database/MigrationRunner.php';

$isProduction = environmentValue('AKRAB_APP_ENV', 'production') === 'production';
$productionAllowed = in_array('--allow-production', $argv, true);

if ($isProduction && !$productionAllowed) {
    fwrite(STDERR, "Production migration requires --allow-production.\n");
    exit(1);
}

$runner = new MigrationRunner($pdo, dirname(__DIR__) . '/database/migrations');
$completed = $runner->migrate();

if ($completed === []) {
    echo "Database schema is already current.\n";
    exit(0);
}

foreach ($completed as $version) {
    echo "Applied {$version}\n";
}
