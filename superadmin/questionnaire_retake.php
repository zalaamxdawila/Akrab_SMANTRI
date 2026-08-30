<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/helpers.php';
require_once dirname(__DIR__) . '/app/Security/SuperadminGuard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Metode tidak diizinkan.');
}

try {
    $actor = SuperadminGuard::authorize($pdo, $_SESSION);
} catch (Throwable) {
    http_response_code(403);
    exit('Akses ditolak.');
}

$studentId = filter_var(
    $_POST['student_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);
if ($studentId === false) {
    http_response_code(422);
    exit('Target siswa tidak valid.');
}

try {
    $reason = is_string($_POST['reason'] ?? null)
        ? $_POST['reason']
        : '';
    $updated = (new QuestionnaireRetakeService(
        new PdoQuestionnaireRetakeStore($pdo)
    ))->enableRetake(
        $actor,
        (int) $studentId,
        $reason,
        requestCorrelationId()
    );
    $_SESSION['_questionnaire_retake_notice'] = [
        'student_id' => (int) $studentId,
        'status' => 'success',
        'count' => $updated,
    ];
} catch (Throwable $exception) {
    akrabLog('warn', 'questionnaire_retake_failed', [
        'actor_role' => 'superadmin',
        'exception_class' => get_class($exception),
        'outcome' => 'rejected',
    ]);
    $_SESSION['_questionnaire_retake_notice'] = [
        'student_id' => (int) $studentId,
        'status' => 'error',
    ];
}

header(
    'Location: questionnaire_results.php?student_id='
    . rawurlencode((string) $studentId),
    true,
    303
);
exit;
