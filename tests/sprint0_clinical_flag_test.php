<?php

require_once dirname(__DIR__) . '/helpers.php';

function assertSameValue($expected, $actual, $message)
{
    if ($expected !== $actual) {
        fwrite(STDERR, "FAIL: {$message}\n");
        fwrite(STDERR, 'Expected: ' . var_export($expected, true) . "\n");
        fwrite(STDERR, 'Actual: ' . var_export($actual, true) . "\n");
        exit(1);
    }
}

putenv('CLINICAL_RISK_ENABLED');
assertSameValue(false, isClinicalRiskEnabled(), 'Clinical risk must default to disabled.');

putenv('CLINICAL_RISK_ENABLED=false');
assertSameValue(false, isClinicalRiskEnabled(), 'The value false must keep clinical risk disabled.');

putenv('CLINICAL_RISK_ENABLED=unexpected');
assertSameValue(false, isClinicalRiskEnabled(), 'Unknown values must fail closed.');

putenv('CLINICAL_RISK_ENABLED=true');
assertSameValue(true, isClinicalRiskEnabled(), 'The explicit value true must enable clinical risk.');

putenv('CLINICAL_RISK_ENABLED');
echo "PASS: clinical risk feature flag is fail-closed.\n";
