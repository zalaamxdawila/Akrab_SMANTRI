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
    if (!$id || !in_array($type, ['questionnaire', 'risk'], true)) {
        throw new InvalidArgumentException('Target tidak valid.');
    }
    if (($_POST['action'] ?? '') === 'archive') {
        $service->archive(
            $actor,
            $type === 'risk' ? 'hasil_deteksi' : 'kuesioner',
            (int) $id,
            $reason,
            requestCorrelationId()
        );
    } elseif ($type === 'risk') {
        $service->correctRiskResult($actor, (int) $id, [
            'probabilitas_risiko' => $_POST['probabilitas_risiko'] ?? '',
            'kategori_risiko' => $_POST['kategori_risiko'] ?? '',
            'tanggal' => $_POST['tanggal'] ?? '',
        ], $reason, requestCorrelationId());
    } else {
        $fields = array_intersect_key($_POST, array_flip([
            'kadar_hb', 'kadar_mchc', 'kadar_mcv', 'kadar_mch',
            'skor_gejala', 'skor_sikap', 'skor_pengetahuan', 'skor_makan',
        ]));
        $service->correctQuestionnaire(
            $actor, (int) $id, $fields, $reason, requestCorrelationId()
        );
    }
    header('Location: health_records.php?type=' . urlencode($type) . '&updated=1', true, 303);
    exit;
} catch (Throwable) {
    http_response_code(422);
    exit('Perubahan data kesehatan ditolak.');
}
