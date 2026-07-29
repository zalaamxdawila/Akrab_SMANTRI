<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/app/Security/SuperadminGuard.php';
require_once dirname(__DIR__) . '/app/Services/SuperadminConsultationService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metode tidak diizinkan.');
}
try {
    $actor = SuperadminGuard::authorize($pdo, $_SESSION);
    $service = new SuperadminConsultationService($pdo);
    $id = filter_var($_POST['record_id'] ?? null, FILTER_VALIDATE_INT);
    $type = (string) ($_POST['record_type'] ?? '');
    $reason = (string) ($_POST['reason'] ?? '');
    if (!$id || !in_array($type, ['consultation', 'reply'], true)) {
        throw new InvalidArgumentException('Target tidak valid.');
    }
    if (($_POST['action'] ?? '') === 'archive') {
        if ($type === 'reply') {
            $service->archiveReply($actor, (int) $id, $reason, requestCorrelationId());
        } else {
            $service->archiveConsultation(
                $actor, (int) $id, $reason, requestCorrelationId()
            );
        }
    } elseif ($type === 'reply') {
        $service->correctReply(
            $actor, (int) $id, $_POST['content'] ?? '',
            $reason, requestCorrelationId()
        );
    } else {
        $service->correctConsultation(
            $actor, (int) $id, $_POST['content'] ?? '',
            $_POST['status'] ?? '', $reason, requestCorrelationId()
        );
    }
    header('Location: consultations.php?type=' . urlencode($type) . '&updated=1', true, 303);
    exit;
} catch (Throwable) {
    http_response_code(422);
    exit('Perubahan konsultasi ditolak.');
}
