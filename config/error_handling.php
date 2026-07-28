<?php

function publicErrorMessage()
{
    return 'Terjadi gangguan pada aplikasi. Silakan coba kembali atau hubungi administrator.';
}
function productionDisplayErrorsValue()
{
    return 0;
}

function configureProductionErrorHandling()
{
    ini_set('display_errors', (string)productionDisplayErrorsValue());
    ini_set('log_errors', '1');

    set_exception_handler(function (Throwable $exception) {
        error_log('AKRAB unhandled exception: ' . get_class($exception));

        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/plain; charset=utf-8');
        }

        echo publicErrorMessage();
    });
}
