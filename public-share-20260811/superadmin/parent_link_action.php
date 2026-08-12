<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/app/Security/SuperadminGuard.php';
require_once dirname(__DIR__) . '/app/Services/SuperadminParentLinkService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metode tidak diizinkan.');
}
try {
    $actor = SuperadminGuard::authorize($pdo, $_SESSION);
    (new SuperadminParentLinkService($pdo))->apply(
        $actor,
        (int) ($_POST['link_id'] ?? 0),
        (string) ($_POST['action'] ?? ''),
        (string) ($_POST['student_username'] ?? ''),
        (string) ($_POST['reason'] ?? ''),
        requestCorrelationId()
    );
    header('Location: parent_links.php?updated=1', true, 303);
    exit;
} catch (Throwable) {
    http_response_code(422);
    exit('Perubahan relasi ditolak.');
}
