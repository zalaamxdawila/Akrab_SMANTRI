<?php

declare(strict_types=1);

require_once __DIR__ . '/config/environment.php';
loadEnvironmentFile(__DIR__ . '/.env');
require_once __DIR__ . '/config/error_handling.php';
require_once __DIR__ . '/config/observability.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Request-ID: ' . requestCorrelationId());

$status = 'ok';
$statusCode = 200;
try {
    $pdo = new PDO(
        'mysql:host=' . requireEnvironmentValue('AKRAB_DB_HOST') . ';dbname=' . requireEnvironmentValue('AKRAB_DB_NAME') . ';charset=utf8mb4',
        requireEnvironmentValue('AKRAB_DB_USER'),
        requireEnvironmentValue('AKRAB_DB_PASS'),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 3]
    );
    $pdo->query('SELECT 1')->fetchColumn();
} catch (Throwable $exception) {
    $status = 'degraded';
    $statusCode = 503;
    akrabLog('error', 'health_database_failed', ['exception_class' => get_class($exception), 'status_code' => 503]);
}

http_response_code($statusCode);
echo json_encode(['status' => $status], JSON_THROW_ON_ERROR);
