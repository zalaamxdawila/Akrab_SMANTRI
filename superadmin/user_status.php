<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/app/Security/SuperadminGuard.php';
require_once dirname(__DIR__) . '/app/Services/SuperadminUserService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metode tidak diizinkan.');
}
try {
    $actor = SuperadminGuard::authorize($pdo, $_SESSION);
    $id = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id) {
        throw new InvalidArgumentException('Target tidak valid.');
    }
    (new SuperadminUserService($pdo))->changeStatus(
        $actor,
        (int) $id,
        (string) ($_POST['status'] ?? ''),
        (string) ($_POST['reason'] ?? ''),
        requestCorrelationId()
    );
    header('Location: user_detail.php?id=' . (int) $id . '&status_updated=1', true, 303);
    exit;
} catch (Throwable) {
    http_response_code(422);
    exit('Perubahan status ditolak.');
}
