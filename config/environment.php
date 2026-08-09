<?php

/**
 * Load simple KEY=VALUE entries without overriding variables supplied by hosting.
 */
function loadEnvironmentFile($path)
{
    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }

        $separator = strpos($line, '=');
        if ($separator === false) {
            continue;
        }

        $name = trim(substr($line, 0, $separator));
        if (!preg_match('/^[A-Z][A-Z0-9_]*$/', $name) || getenv($name) !== false) {
            continue;
        }

        $value = trim(substr($line, $separator + 1));
        if (
            strlen($value) >= 2
            && (($value[0] === '"' && substr($value, -1) === '"')
                || ($value[0] === "'" && substr($value, -1) === "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        putenv($name . '=' . $value);
    }
}

/**
 * Read an optional application setting from the process environment.
 */
function environmentValue($name, $default = null)
{
    $value = getenv($name);

    if ($value === false || trim($value) === '') {
        return $default;
    }

    return $value;
}

/**
 * Read a required setting without exposing its name or value to end users.
 */
function requireEnvironmentValue($name)
{
    $value = environmentValue($name);

    if ($value === null) {
        throw new RuntimeException('Required application configuration is missing.');
    }

    return $value;
}

/**
 * Limit production traffic to the single canonical AKRAB hostname.
 */
function applicationHostIsAllowed(?string $httpHost, string $environment): bool
{
    if ($httpHost === null || trim($httpHost) === '') {
        return false;
    }

    $host = strtolower(trim($httpHost));
    $host = preg_replace('/:\d+$/', '', $host);
    if (!is_string($host)) {
        return false;
    }

    $allowedHosts = ['akrab.portodq.com'];
    if ($environment !== 'production') {
        $allowedHosts[] = 'localhost';
        $allowedHosts[] = '127.0.0.1';
    }

    return in_array($host, $allowedHosts, true);
}
