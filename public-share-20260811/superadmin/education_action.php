<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/app/Security/SuperadminGuard.php';
require_once dirname(__DIR__) . '/app/Services/SuperadminEducationService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metode tidak diizinkan.');
}
try {
    $actor = SuperadminGuard::authorize($pdo, $_SESSION);
    $service = new SuperadminEducationService($pdo);
    $id = filter_var($_POST['record_id'] ?? null, FILTER_VALIDATE_INT);
    $type = (string) ($_POST['record_type'] ?? '');
    $reason = (string) ($_POST['reason'] ?? '');
    $table = $type === 'article' ? 'artikel_edukasi'
        : ($type === 'advice' ? 'saran_edukasi' : '');
    if (!$id || $table === '') {
        throw new InvalidArgumentException('Target tidak valid.');
    }
    if (($_POST['action'] ?? '') === 'archive') {
        $service->archive($actor, $table, (int) $id, $reason, requestCorrelationId());
    } elseif ($type === 'article') {
        $service->correctArticle(
            $actor, (int) $id, $_POST['title'] ?? '',
            $_POST['content'] ?? '', $reason, requestCorrelationId()
        );
    } else {
        $service->correctAdvice(
            $actor, (int) $id, $_POST, $reason, requestCorrelationId()
        );
    }
    header('Location: education.php?type=' . urlencode($type) . '&updated=1', true, 303);
    exit;
} catch (Throwable) {
    http_response_code(422);
    exit('Perubahan edukasi ditolak.');
}
