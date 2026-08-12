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
    $rows = (new QuestionnaireAnalyticsRepository($pdo))->latestByStudentForExport();
    $stream = fopen('php://temp', 'w+b');
    if ($stream === false) {
        throw new RuntimeException('Unable to prepare the export stream.');
    }
    (new QuestionnaireExport())->writeCsv($stream, $rows);
    rewind($stream);
    recordAuditEvent(
        $pdo,
        (int) $_SESSION['user_id'],
        'questionnaire.exported',
        'questionnaire_latest_results',
        null,
        [
            'actor_role' => 'superadmin',
            'outcome' => 'success',
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
header('Content-Disposition: attachment; filename="Hasil_Kuesioner_AKRAB_' . date('Ymd-His') . '.csv"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, private');
header('Pragma: no-cache');

fpassthru($stream);
fclose($stream);
exit;
