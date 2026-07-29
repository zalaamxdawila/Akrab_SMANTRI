<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/app/Security/SuperadminGuard.php';
require_once dirname(__DIR__) . '/app/Services/SuperadminNotificationService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metode tidak diizinkan.');
}
try {
    $actor = SuperadminGuard::authorize($pdo, $_SESSION);
    $service = new SuperadminNotificationService($pdo);
    $id = filter_var($_POST['record_id'] ?? null, FILTER_VALIDATE_INT);
    $type = (string) ($_POST['record_type'] ?? '');
    $reason = (string) ($_POST['reason'] ?? '');
    $table = $type === 'schedule' ? 'jadwal_notifikasi'
        : ($type === 'delivery' ? 'log_notifikasi' : '');
    if (!$id || $table === '') {
        throw new InvalidArgumentException('Target tidak valid.');
    }
    if (($_POST['action'] ?? '') === 'archive') {
        $service->archive($actor, $table, (int) $id, $reason, requestCorrelationId());
    } elseif ($type === 'schedule') {
        $service->correctSchedule(
            $actor, (int) $id, $_POST['jam_pengingat'] ?? '',
            $_POST['hari'] ?? '', $_POST['aktif'] ?? '',
            $reason, requestCorrelationId()
        );
    } else {
        $service->correctDeliveryConfirmation(
            $actor, (int) $id, $_POST['sudah_dikonfirmasi'] ?? '',
            $reason, requestCorrelationId()
        );
    }
    header('Location: notifications.php?type=' . urlencode($type) . '&updated=1', true, 303);
    exit;
} catch (Throwable) {
    http_response_code(422);
    exit('Perubahan notifikasi ditolak.');
}
