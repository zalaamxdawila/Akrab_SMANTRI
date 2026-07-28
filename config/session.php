<?php

function sessionCookieOptions(string $environment): array
{
    return [
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $environment === 'production',
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

function sessionHasExpired(
    array $session,
    int $now,
    int $idleTimeout,
    int $absoluteTimeout
): bool {
    if (!isset($session['_created_at'], $session['_last_activity'])) {
        return false;
    }

    return ($now - (int)$session['_last_activity']) > $idleTimeout
        || ($now - (int)$session['_created_at']) > $absoluteTimeout;
}

function startSecureSession(
    string $environment,
    int $idleTimeout = 1800,
    int $absoluteTimeout = 28800
): void {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    session_set_cookie_params(sessionCookieOptions($environment));
    session_start();

    $now = time();
    if (sessionHasExpired($_SESSION, $now, $idleTimeout, $absoluteTimeout)) {
        destroySessionCompletely($environment);
        session_id('');
        session_set_cookie_params(sessionCookieOptions($environment));
        session_start();
    }

    $_SESSION['_created_at'] ??= $now;
    $_SESSION['_last_activity'] = $now;
}

function regenerateAuthenticatedSession(): void
{
    session_regenerate_id(true);
    unset($_SESSION['_csrf_token']);
    $_SESSION['_created_at'] = time();
    $_SESSION['_last_activity'] = time();
}

function destroySessionCompletely(string $environment): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $options = sessionCookieOptions($environment);
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $options['path'],
            'domain' => $options['domain'],
            'secure' => $options['secure'],
            'httponly' => $options['httponly'],
            'samesite' => $options['samesite'],
        ]);
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}
