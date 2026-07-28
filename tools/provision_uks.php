<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/config/environment.php';

$projectRoot = dirname(__DIR__);
loadEnvironmentFile($projectRoot . '/.env');
$options = getopt('', ['username:', 'name:']);
$username = trim((string) ($options['username'] ?? ''));
$name = trim((string) ($options['name'] ?? ''));
$password = requireEnvironmentValue('AKRAB_PROVISION_UKS_PASSWORD');

if (
    $username === ''
    || $name === ''
    || strlen($username) > 50
    || strlen($name) > 100
    || strlen($password) < 12
) {
    fwrite(STDERR, "Usage: php tools/provision_uks.php --username=<id> --name=<name>\n");
    exit(1);
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

try {
    $pdo->beginTransaction();
    $insert = $pdo->prepare(
        "INSERT INTO users (nama, role, username, password_hash)
         VALUES (?, 'uks', ?, ?)"
    );
    $insert->execute([$name, $username, password_hash($password, PASSWORD_DEFAULT)]);
    $userId = (int) $pdo->lastInsertId();

    $audit = $pdo->prepare(
        "INSERT INTO audit_log (actor_id, action, target_type, target_id, metadata_json)
         VALUES (NULL, 'uks.provisioned', 'user', ?, ?)"
    );
    $audit->execute([
        $userId,
        json_encode(['operator' => 'cli', 'username' => $username], JSON_THROW_ON_ERROR),
    ]);
    $pdo->commit();
    echo "UKS account provisioned with user ID {$userId}.\n";
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "Provisioning failed.\n");
    exit(1);
} finally {
    $password = '';
    putenv('AKRAB_PROVISION_UKS_PASSWORD');
}
