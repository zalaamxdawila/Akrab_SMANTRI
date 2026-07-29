<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/config/environment.php';

$projectRoot = dirname(__DIR__);
loadEnvironmentFile($projectRoot . '/.env');

$environment = environmentValue('AKRAB_APP_ENV', 'production');
$database = requireEnvironmentValue('AKRAB_DB_NAME');
$productionDatabase = 'u602402025_akrab';

if ($environment !== 'staging' || $database === $productionDatabase) {
    fwrite(STDERR, "Seeder refused: requires AKRAB_APP_ENV=staging and a non-production database.\n");
    exit(1);
}
if (!in_array('--confirm-synthetic-data', $argv, true)) {
    fwrite(STDERR, "Add --confirm-synthetic-data after verifying the staging target.\n");
    exit(1);
}

$fixturePassword = environmentValue('AKRAB_STAGING_FIXTURE_PASSWORD');
if ($fixturePassword === null || strlen($fixturePassword) < 12) {
    fwrite(STDERR, "AKRAB_STAGING_FIXTURE_PASSWORD must contain at least 12 characters.\n");
    exit(1);
}

$pdo = new PDO(
    'mysql:host=' . requireEnvironmentValue('AKRAB_DB_HOST')
        . ';dbname=' . $database . ';charset=utf8mb4',
    requireEnvironmentValue('AKRAB_MIGRATION_DB_USER'),
    requireEnvironmentValue('AKRAB_MIGRATION_DB_PASS'),
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);

$users = [
    ['Siswa Sintetis', 'siswa', 'uat_siswa', 'X-SYNTHETIC', null],
    ['Petugas UKS Sintetis', 'uks', 'uat_uks', null, null],
    ['Orang Tua Sintetis', 'orangtua', 'uat_orangtua', null, 'uat_siswa'],
];
$hash = password_hash($fixturePassword, PASSWORD_DEFAULT);
$statement = $pdo->prepare(
    'INSERT INTO users (nama, role, username, password_hash, kelas, anak_username)
     VALUES (?, ?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE nama = VALUES(nama), password_hash = VALUES(password_hash),
       kelas = VALUES(kelas), anak_username = VALUES(anak_username)'
);

$pdo->beginTransaction();
try {
    foreach ($users as [$name, $role, $username, $class, $studentUsername]) {
        $statement->execute([$name, $role, $username, $hash, $class, $studentUsername]);
    }
    $pdo->commit();
} catch (Throwable $exception) {
    $pdo->rollBack();
    fwrite(STDERR, "Synthetic fixture transaction failed.\n");
    exit(1);
}

echo "Synthetic staging users are ready; password was read only from the environment.\n";
