<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/helpers.php';
require_once dirname(__DIR__) . '/app/Security/SuperadminGuard.php';
require_once dirname(__DIR__) . '/app/Services/QuestionnaireExport.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Metode tidak diizinkan.');
}

try {
    SuperadminGuard::authorize($pdo, $_SESSION);
    $type = $_POST['type'] ?? null;
    if (!is_string($type) || !in_array($type, ['baru', 'lama'], true)) {
        http_response_code(400);
        exit('Jenis export tidak valid.');
    }

    $repository = new QuestionnaireAnalyticsRepository($pdo);
    $exporter = new QuestionnaireExport();
    $isStaged = $type === 'baru';
    $rows = $isStaged
        ? $repository->latestStagedByStudentForExport()
        : $repository->latestLegacyByStudentForExport();
    $stream = fopen('php://temp', 'w+b');
    if ($stream === false) {
        throw new RuntimeException('Unable to prepare the export stream.');
    }
    if ($isStaged) {
        $exporter->writeStagedCsv($stream, $rows);
    } else {
        $exporter->writeLegacyCsv($stream, $rows);
    }
    rewind($stream);
    recordAuditEvent(
        $pdo,
        (int) $_SESSION['user_id'],
        'questionnaire.exported',
        $isStaged ? 'questionnaire_staged_results' : 'questionnaire_legacy_results',
        null,
        [
            'actor_role' => 'superadmin',
            'outcome' => 'success',
            'format' => $type,
            'row_count' => count($rows),
        ]
    );
} catch (Throwable) {
    http_response_code(403);
    exit('Akses ditolak.');
}

if (ob_get_length()) {
    ob_end_clean();
}
header('Content-Type: text/csv; charset=UTF-8');
$filename = $isStaged ? 'Hasil_Skrining_Baru_AKRAB_' : 'Hasil_Kuesioner_Lama_AKRAB_';
header('Content-Disposition: attachment; filename="' . $filename . date('Ymd-His') . '.csv"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, private');
header('Pragma: no-cache');

fpassthru($stream);
fclose($stream);
exit;
