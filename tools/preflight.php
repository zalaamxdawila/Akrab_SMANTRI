<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/config/environment.php';

loadEnvironmentFile(dirname(__DIR__) . '/.env');

$checks = [];
$checks['php_version'] = version_compare(PHP_VERSION, '8.1.0', '>=');
foreach (['pdo', 'pdo_mysql', 'json', 'mbstring', 'openssl'] as $extension) {
    $checks['extension_' . $extension] = extension_loaded($extension);
}
$checks['timezone'] = date_default_timezone_get() === 'Asia/Jakarta';
$checks['environment'] = environmentValue('AKRAB_APP_ENV') === 'production';
$checks['base_url'] = environmentValue('AKRAB_BASE_URL') === 'https://akrab.portodq.com/';
$checks['database_name'] = environmentValue('AKRAB_DB_NAME') === 'u602402025_akrab';
$checks['clinical_fail_closed'] = environmentValue('CLINICAL_RISK_ENABLED', 'false') !== 'true';

$required = [
    'AKRAB_DB_HOST',
    'AKRAB_DB_NAME',
    'AKRAB_DB_USER',
    'AKRAB_DB_PASS',
    'AKRAB_RATE_LIMIT_KEY',
];
foreach ($required as $name) {
    $checks['configured_' . strtolower($name)] = environmentValue($name) !== null;
}

if (in_array('--check-database', $argv, true)) {
    try {
        $pdo = new PDO(
            'mysql:host=' . requireEnvironmentValue('AKRAB_DB_HOST')
                . ';dbname=' . requireEnvironmentValue('AKRAB_DB_NAME')
                . ';charset=utf8mb4',
            requireEnvironmentValue('AKRAB_DB_USER'),
            requireEnvironmentValue('AKRAB_DB_PASS'),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 3,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
        $checks['database_connection'] = $pdo->query('SELECT 1')->fetchColumn() === 1;
        $schema = $pdo->prepare(
            'SELECT COUNT(*) FROM schema_migrations WHERE version = ?'
        );
        $schema->execute(['021_staged_screening']);
        $checks['database_schema_021'] = (int) $schema->fetchColumn() === 1;
    } catch (Throwable $exception) {
        $checks['database_connection'] = false;
        $checks['database_schema_021'] = false;
    }
}

$failed = false;
foreach ($checks as $name => $passed) {
    echo ($passed ? 'PASS' : 'FAIL') . ' ' . $name . PHP_EOL;
    $failed = $failed || !$passed;
}

echo 'Secrets are checked for presence only and are never printed.' . PHP_EOL;
exit($failed ? 1 : 0);
