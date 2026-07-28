<?php
// config.php

require_once __DIR__ . '/config/environment.php';
require_once __DIR__ . '/config/error_handling.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/config/authorization.php';

$appEnvironment = environmentValue('AKRAB_APP_ENV', 'production');
if ($appEnvironment === 'production') {
    configureProductionErrorHandling();
}

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
    exit(publicErrorMessage());
}

// Start Session if not already started and not running in CLI
if (session_status() === PHP_SESSION_NONE && php_sapi_name() !== 'cli') {
    $idleTimeout = max(300, (int)environmentValue('AKRAB_SESSION_IDLE_SECONDS', '1800'));
    $absoluteTimeout = max(
        $idleTimeout,
        (int)environmentValue('AKRAB_SESSION_ABSOLUTE_SECONDS', '28800')
    );
    startSecureSession($appEnvironment, $idleTimeout, $absoluteTimeout);
}

if (
    php_sapi_name() !== 'cli'
    && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'
) {
    verifyCsrfOrFail($_POST['_csrf'] ?? null);
}

define('BASE_URL', $baseUrl);
