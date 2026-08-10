<?php
// config.php

require_once __DIR__ . '/config/environment.php';
loadEnvironmentFile(__DIR__ . '/.env');
require_once __DIR__ . '/config/error_handling.php';
require_once __DIR__ . '/config/observability.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/app/Security/ActorContext.php';
require_once __DIR__ . '/app/Security/ActorContextResolver.php';
require_once __DIR__ . '/app/Security/ImpersonationPolicy.php';
require_once __DIR__ . '/app/Security/ImpersonationMutationAudit.php';
require_once __DIR__ . '/app/Security/ImpersonationService.php';
require_once __DIR__ . '/app/Security/AuthAttemptLimiter.php';
require_once __DIR__ . '/app/Security/PasswordResetToken.php';
require_once __DIR__ . '/config/authorization.php';
require_once __DIR__ . '/config/validation.php';
require_once __DIR__ . '/config/csv.php';
require_once __DIR__ . '/config/integrity.php';
require_once __DIR__ . '/config/risk.php';
require_once __DIR__ . '/config/clinical.php';

$appEnvironment = environmentValue('AKRAB_APP_ENV', 'production');
date_default_timezone_set(environmentValue('AKRAB_TIMEZONE', 'Asia/Jakarta'));
if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('Cache-Control: private, no-store, max-age=0');
    header('Pragma: no-cache');
}
if ($appEnvironment === 'production') {
    configureProductionErrorHandling();
}

if (
    PHP_SAPI !== 'cli'
    && !applicationHostIsAllowed($_SERVER['HTTP_HOST'] ?? null, $appEnvironment)
) {
    http_response_code(400);
    exit('Host tidak diizinkan.');
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
    akrabLog('error', 'bootstrap_failed', ['exception_class' => get_class($exception)]);
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

if (php_sapi_name() !== 'cli' && isset($_SESSION['user_id'])) {
    try {
        $impersonationService = new ImpersonationService($pdo, $_SESSION);
        $impersonationService->expireIfNeeded();
        $actorContext = (new ActorContextResolver($pdo))->resolve($_SESSION);

        if (
            ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'
            && $actorContext->isImpersonating()
        ) {
            ImpersonationPolicy::assertAllowed(
                impersonationActionForRequest(
                    (string) ($_SERVER['SCRIPT_NAME'] ?? ''),
                    $_POST
                )
            );
            (new ImpersonationMutationAudit($pdo))->registerCurrentMutation(
                $actorContext,
                (string) ($_SERVER['SCRIPT_NAME'] ?? ''),
                requestCorrelationId()
            );
        }
    } catch (Throwable $exception) {
        akrabLog(
            'warn',
            'actor_context_rejected',
            ['exception_class' => get_class($exception)]
        );
        destroySessionCompletely($appEnvironment);
        http_response_code(403);
        exit('Akses ditolak.');
    }
}

if (
    php_sapi_name() !== 'cli'
    && isset($actorContext)
    && $actorContext->isImpersonating()
    && in_array(
        basename((string) ($_SERVER['SCRIPT_NAME'] ?? '')),
        [
            'export_calendar.php',
            'export_csv.php',
            'export_questionnaire.php',
            'questionnaire_export.php',
        ],
        true
    )
) {
    http_response_code(403);
    exit('Export tidak diizinkan selama Login As.');
}

if (
    php_sapi_name() !== 'cli'
    && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'
) {
    verifyCsrfOrFail(csrfTokenFromRequest($_POST, $_SERVER));
}

define('BASE_URL', $baseUrl);
