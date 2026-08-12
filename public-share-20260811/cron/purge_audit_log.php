<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/helpers.php';

$retentionDays = max(30, (int) environmentValue('AKRAB_AUDIT_RETENTION_DAYS', '365'));
$statement = $pdo->prepare(
    'DELETE FROM audit_log
     WHERE created_at < DATE_SUB(CURRENT_TIMESTAMP, INTERVAL ? DAY)
     LIMIT 5000'
);
$statement->bindValue(1, $retentionDays, PDO::PARAM_INT);
$statement->execute();
akrabLog('info', 'audit_retention_completed', ['outcome' => 'success']);
