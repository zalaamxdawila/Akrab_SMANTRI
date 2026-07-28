<?php

require_once dirname(__DIR__) . '/config/environment.php';

function assertConfigSame($expected, $actual, $message)
{
    if ($expected !== $actual) {
        fwrite(STDERR, "FAIL: {$message}\n");
        fwrite(STDERR, 'Expected: ' . var_export($expected, true) . "\n");
        fwrite(STDERR, 'Actual: ' . var_export($actual, true) . "\n");
        exit(1);
    }
}

putenv('AKRAB_TEST_REQUIRED');

try {
    requireEnvironmentValue('AKRAB_TEST_REQUIRED');
    fwrite(STDERR, "FAIL: Missing required configuration must throw.\n");
    exit(1);
} catch (RuntimeException $exception) {
    assertConfigSame(
        'Required application configuration is missing.',
        $exception->getMessage(),
        'Missing configuration must use a generic message.'
    );
}

putenv('AKRAB_TEST_REQUIRED=configured-value');
assertConfigSame(
    'configured-value',
    requireEnvironmentValue('AKRAB_TEST_REQUIRED'),
    'Required configuration must be read from the environment.'
);

putenv('AKRAB_TEST_OPTIONAL');
assertConfigSame(
    'fallback-value',
    environmentValue('AKRAB_TEST_OPTIONAL', 'fallback-value'),
    'Optional configuration must use its explicit fallback.'
);

putenv('AKRAB_TEST_REQUIRED');
echo "PASS: environment configuration fails closed without leaking values.\n";
