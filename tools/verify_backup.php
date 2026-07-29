<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$manifestPath = $argv[1] ?? '';
if ($manifestPath === '' || !is_file($manifestPath)) {
    fwrite(STDERR, "Usage: php tools/verify_backup.php <backup-manifest.json>\n");
    exit(1);
}

$manifest = json_decode((string) file_get_contents($manifestPath), true);
if (!is_array($manifest) || !isset($manifest['files']) || !is_array($manifest['files'])) {
    fwrite(STDERR, "Invalid backup manifest.\n");
    exit(1);
}

$baseDirectory = dirname(realpath($manifestPath) ?: $manifestPath);
$failed = false;
foreach ($manifest['files'] as $item) {
    $relative = $item['path'] ?? '';
    $expected = strtolower($item['sha256'] ?? '');
    if (
        !is_string($relative)
        || $relative === ''
        || str_contains($relative, '..')
        || str_starts_with($relative, '/')
        || !preg_match('/^[a-f0-9]{64}$/', $expected)
    ) {
        fwrite(STDERR, "FAIL invalid manifest entry\n");
        $failed = true;
        continue;
    }

    $path = $baseDirectory . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    $actual = is_file($path) ? hash_file('sha256', $path) : false;
    $passed = is_string($actual) && hash_equals($expected, strtolower($actual));
    echo ($passed ? 'PASS ' : 'FAIL ') . $relative . PHP_EOL;
    $failed = $failed || !$passed;
}

exit($failed ? 1 : 0);
