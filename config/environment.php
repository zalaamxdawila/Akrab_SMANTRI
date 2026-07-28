<?php

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

