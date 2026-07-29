<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metode tidak diizinkan.');
}
try {
    (new ImpersonationService($pdo, $_SESSION))->end();
    header('Location: ' . BASE_URL . 'superadmin/dashboard.php', true, 303);
    exit;
} catch (Throwable) {
    http_response_code(403);
    exit('Tidak dapat kembali ke Superadmin.');
}
