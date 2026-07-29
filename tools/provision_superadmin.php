<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/config/environment.php';
require_once dirname(__DIR__) . '/app/Services/SuperadminProvisioningService.php';

$projectRoot = dirname(__DIR__);
loadEnvironmentFile($projectRoot . '/.env');
$options = getopt('', ['username:', 'name:']);
$username = trim((string) ($options['username'] ?? ''));
$name = trim((string) ($options['name'] ?? ''));
$password = '';
$exitCode = 0;

try {
    $password = requireEnvironmentValue('AKRAB_PROVISION_SUPERADMIN_PASSWORD');
    if (hash_equals('replace_before_running_cli_tool', $password)) {
        throw new RuntimeException('The provisioning password placeholder must be replaced.');
    }
    $pdo = new PDO(
        sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            requireEnvironmentValue('AKRAB_DB_HOST'),
            requireEnvironmentValue('AKRAB_DB_NAME')
        ),
        requireEnvironmentValue('AKRAB_MIGRATION_DB_USER'),
        requireEnvironmentValue('AKRAB_MIGRATION_DB_PASS'),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    $userId = (new SuperadminProvisioningService($pdo))->provision(
        $name,
        $username,
        $password
    );
    echo "Superadmin account provisioned or recovered with user ID {$userId}.\n";
} catch (Throwable $exception) {
    fwrite(
        STDERR,
        "Provisioning failed. Verify the CLI arguments and protected environment.\n"
    );
    $exitCode = 1;
} finally {
    $password = '';
    putenv('AKRAB_PROVISION_SUPERADMIN_PASSWORD');
}

exit($exitCode);
