<?php

require_once dirname(__DIR__) . '/config/error_handling.php';

function assertErrorSame($expected, $actual, $message)
{
    if ($expected !== $actual) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

assertErrorSame(
    'Terjadi gangguan pada aplikasi. Silakan coba kembali atau hubungi administrator.',
    publicErrorMessage(),
    'Public error output must remain generic.'
);

assertErrorSame(
    '0',
    (string)productionDisplayErrorsValue(),
    'Production must disable display_errors.'
);

echo "PASS: production error output is generic.\n";
