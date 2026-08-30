<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/helpers.php';

check_role('uks');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Metode tidak diizinkan.');
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
    $actor = (new ActorContextResolver($pdo))->resolve($_SESSION);
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
        'actor_role' => 'uks',
        'exception_class' => get_class($exception),
        'outcome' => 'rejected',
    ]);
    $_SESSION['_questionnaire_retake_notice'] = [
        'student_id' => (int) $studentId,
        'status' => 'error',
    ];
}

header(
    'Location: detail_siswa.php?id=' . rawurlencode((string) $studentId),
    true,
    303
);
exit;
