<?php

function csrfToken(): string
{
    if (
        !isset($_SESSION['_csrf_token'])
        || !is_string($_SESSION['_csrf_token'])
        || strlen($_SESSION['_csrf_token']) !== 64
    ) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

function csrfTokenIsValid($submittedToken): bool
{
    return is_string($submittedToken)
        && isset($_SESSION['_csrf_token'])
        && is_string($_SESSION['_csrf_token'])
        && hash_equals($_SESSION['_csrf_token'], $submittedToken);
}

function csrfInput(): string
{
    return '<input type="hidden" name="_csrf" value="'
        . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8')
        . '">';
}

function verifyCsrfOrFail($submittedToken): void
{
    if (csrfTokenIsValid($submittedToken)) {
        return;
    }

    http_response_code(419);
    exit('Permintaan tidak dapat diverifikasi. Muat ulang halaman lalu coba kembali.');
}
