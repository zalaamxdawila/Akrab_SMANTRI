<?php
// config.php

require_once __DIR__ . '/config/environment.php';

try {
    $dbHost = requireEnvironmentValue('AKRAB_DB_HOST');
    $dbName = requireEnvironmentValue('AKRAB_DB_NAME');
    $dbUser = requireEnvironmentValue('AKRAB_DB_USER');
    $dbPass = requireEnvironmentValue('AKRAB_DB_PASS');
    $baseUrl = rtrim(requireEnvironmentValue('AKRAB_BASE_URL'), '/') . '/';

    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (Throwable $exception) {
    error_log('AKRAB bootstrap failed: ' . get_class($exception));
    http_response_code(500);
    exit('Aplikasi belum dapat terhubung. Silakan hubungi administrator.');
}

// Start Session if not already started and not running in CLI
if (session_status() === PHP_SESSION_NONE && php_sapi_name() !== 'cli') {
    session_start();
}

define('BASE_URL', $baseUrl);
