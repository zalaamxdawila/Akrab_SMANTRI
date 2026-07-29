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
    $type = (string) ($_POST['record_type'] ?? '');
    $reason = (string) ($_POST['reason'] ?? '');
    if (!$id || !in_array($type, ['hb', 'ttd'], true)) {
        throw new InvalidArgumentException('Target tidak valid.');
    }
    if (($_POST['action'] ?? '') === 'archive') {
        $service->archive(
            $actor,
            $type === 'hb' ? 'kadar_hb' : 'konsumsi_ttd',
            (int) $id,
            $reason,
            requestCorrelationId()
        );
    } elseif ($type === 'hb') {
        $service->correctHb(
            $actor, (int) $id, $_POST['nilai_hb'] ?? '',
            $_POST['kategori_anemia'] ?? '', $_POST['tanggal_periksa'] ?? '',
            $reason, requestCorrelationId()
        );
    } else {
        $service->correctTtd(
            $actor, (int) $id, $_POST['tanggal'] ?? '',
            $_POST['status_konsumsi'] ?? '', $reason, requestCorrelationId()
        );
    }
    header('Location: health_records.php?type=' . urlencode($type) . '&updated=1', true, 303);
    exit;
} catch (Throwable) {
    http_response_code(422);
    exit('Perubahan data kesehatan ditolak.');
}
