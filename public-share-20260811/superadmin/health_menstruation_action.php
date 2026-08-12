<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/app/Security/SuperadminGuard.php';
require_once dirname(__DIR__) . '/app/Services/SuperadminHealthService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metode tidak diizinkan.');
}
try {
    $actor = SuperadminGuard::authorize($pdo, $_SESSION);
    $service = new SuperadminHealthService($pdo);
    $id = filter_var($_POST['record_id'] ?? null, FILTER_VALIDATE_INT);
    $reason = (string) ($_POST['reason'] ?? '');
    if (!$id || ($_POST['record_type'] ?? '') !== 'menstruation') {
        throw new InvalidArgumentException('Target tidak valid.');
    }
    if (($_POST['action'] ?? '') === 'archive') {
        $service->archive(
            $actor, 'riwayat_haid', (int) $id, $reason, requestCorrelationId()
        );
    } else {
        $service->correctMenstruation(
            $actor, (int) $id, $_POST['tanggal_mulai'] ?? '',
            $_POST['tanggal_selesai'] ?? null, $reason, requestCorrelationId()
        );
    }
    header('Location: health_records.php?type=menstruation&updated=1', true, 303);
    exit;
} catch (Throwable) {
    http_response_code(422);
    exit('Perubahan data kesehatan ditolak.');
}
