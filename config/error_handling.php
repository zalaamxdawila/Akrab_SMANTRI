<?php

function publicErrorMessage()
{
    return 'Terjadi gangguan pada aplikasi. Silakan coba kembali atau hubungi administrator.';
}
function productionDisplayErrorsValue()
{
    return 0;
}

function requestCorrelationId(): string
{
    static $id;
    if ($id !== null) {
        return $id;
    }

    try {
        $id = bin2hex(random_bytes(16));
    } catch (Throwable $exception) {
        $id = hash('sha256', uniqid('', true));
    }

    return $id;
}
function configureProductionErrorHandling()
{
    $startedAt = microtime(true);
    ini_set('display_errors', (string)productionDisplayErrorsValue());
    ini_set('log_errors', '1');
    requestCorrelationId();
    if (!headers_sent()) {
        header('X-Request-ID: ' . requestCorrelationId());
    }

    set_exception_handler(function (Throwable $exception) {
        akrabLog('error', 'unhandled_exception', ['exception_class' => get_class($exception)]);

        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/plain; charset=utf-8');
        }

        echo publicErrorMessage();
    });

    register_shutdown_function(function () use ($startedAt): void {
        $statusCode = http_response_code() ?: 200;
        akrabLog($statusCode >= 500 ? 'error' : 'info', 'http_request_completed', [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'route' => basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'unknown')),
            'status_code' => $statusCode,
            'duration_ms' => round((microtime(true) - $startedAt) * 1000, 2),
            'outcome' => $statusCode >= 400 ? 'failed' : 'success',
        ]);
    });
}
