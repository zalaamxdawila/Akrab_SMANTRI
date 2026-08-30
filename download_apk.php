<?php

declare(strict_types=1);

$apkPath = __DIR__ . '/downloads/AKRAB-Android-v1.0.0.bin';
$requestMethod = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

if (!in_array($requestMethod, ['GET', 'HEAD'], true)) {
    header('Allow: GET, HEAD');
    http_response_code(405);
    exit;
}

if (!is_file($apkPath) || !is_readable($apkPath)) {
    http_response_code(404);
    exit;
}

$apkSize = filesize($apkPath);
if ($apkSize === false) {
    http_response_code(500);
    exit;
}

while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: application/vnd.android.package-archive');
header('Content-Disposition: attachment; filename="AKRAB-Android-v1.0.0.apk"');
header('Content-Length: ' . $apkSize);
header('Cache-Control: public, max-age=31536000, immutable');
header('X-Content-Type-Options: nosniff');

if ($requestMethod === 'HEAD') {
    exit;
}

readfile($apkPath);
