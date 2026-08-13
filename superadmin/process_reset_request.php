<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/helpers.php';
require_once dirname(__DIR__) . '/app/Security/SuperadminGuard.php';

try {
    SuperadminGuard::authorize($pdo, $_SESSION);
} catch (Throwable) {
    http_response_code(403);
    exit('Akses ditolak.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Metode tidak diizinkan.');
}

$requestId = filter_var(
    $_POST['request_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);
$action = $_POST['action'] ?? '';
if ($requestId === false || !in_array($action, ['generate_link', 'complete'], true)) {
    http_response_code(400);
    exit('Permintaan tidak valid.');
}

$statement = $pdo->prepare(
    "SELECT p.id, p.user_id, u.email FROM password_reset_requests p
     JOIN users u ON u.id = p.user_id
     WHERE p.id = ? AND p.status = 'pending'"
);
$statement->execute([$requestId]);
$request = $statement->fetch();
if (!$request) {
    http_response_code(404);
    exit('Permintaan tidak ditemukan.');
}

if ($action === 'generate_link') {
    $issuedToken = PasswordResetToken::issue();
    $statement = $pdo->prepare(
        "UPDATE password_reset_requests
         SET token_hash = ?, expires_at = DATE_ADD(NOW(), INTERVAL 1 HOUR)
         WHERE id = ? AND status = 'pending'"
    );
    $statement->execute([$issuedToken['digest'], $requestId]);
    $_SESSION['_generated_password_reset_link'] = [
        'request_id' => (int) $requestId,
        'url' => BASE_URL . 'reset_password.php?token=' . $issuedToken['token'],
    ];
    if (filter_var($request['email'] ?? null, FILTER_VALIDATE_EMAIL)) {
        $resetUrl = BASE_URL . 'reset_password.php?token=' . $issuedToken['token'];
        $message = "Halo,\n\nLink reset password AKRAB Anda:\n{$resetUrl}\n\nLink berlaku selama 1 jam. Abaikan email ini jika Anda tidak meminta reset.";
        @mail(
            (string) $request['email'],
            'Reset Password AKRAB',
            $message,
            "From: noreply@akrab.portodq.com\r\nReply-To: noreply@akrab.portodq.com"
        );
    }
    recordAuditEvent(
        $pdo,
        (int) $_SESSION['user_id'],
        'password_reset.link_generated',
        'password_reset_request',
        (int) $requestId,
        ['target_user_id' => (int) $request['user_id']]
    );
    header('Location: dashboard.php', true, 303);
    exit;
}

$statement = $pdo->prepare(
    "UPDATE password_reset_requests
     SET status = 'completed', token_hash = NULL, expires_at = NULL
     WHERE id = ? AND status = 'pending'"
);
$statement->execute([$requestId]);
recordAuditEvent(
    $pdo,
    (int) $_SESSION['user_id'],
    'password_reset.request_completed',
    'password_reset_request',
    (int) $requestId,
    ['target_user_id' => (int) $request['user_id']]
);

header('Location: dashboard.php', true, 303);
exit;
