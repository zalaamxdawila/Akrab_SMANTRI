<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/config/csv.php';
require_once dirname(__DIR__) . '/app/Security/SuperadminGuard.php';
require_once dirname(__DIR__) . '/app/Repositories/SuperadminReportRepository.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Metode tidak diizinkan.');
}
try {
    $context = SuperadminGuard::authorize($pdo, $_SESSION);
    $confirmation = (string) ($_POST['confirmation'] ?? '');
    $reason = (string) ($_POST['reason'] ?? '');
    $role = normalizeText($_POST['role'] ?? '', 20);
    if ($confirmation !== 'EXPORT AKRAB') {
        throw new InvalidArgumentException('Konfirmasi ekspor tidak sesuai.');
    }
    if (!in_array($reason, ['operational_review', 'compliance_review', 'authorized_backup'], true)) {
        throw new InvalidArgumentException('Alasan ekspor tidak valid.');
    }
    $rows = (new SuperadminReportRepository($pdo))->exportRows($role);
    $requestId = substr((string) ($_SERVER['HTTP_X_REQUEST_ID'] ?? bin2hex(random_bytes(16))), 0, 64);
    $audit = $pdo->prepare(
        'INSERT INTO audit_log (actor_id, authenticated_actor_id, effective_actor_id,
         request_id, action, target_type, metadata_json) VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $audit->execute([
        $context->authenticatedActorId,
        $context->authenticatedActorId,
        $context->effectiveActorId,
        $requestId,
        'report.export',
        'users',
        json_encode(['outcome' => 'success', 'reason_category' => $reason, 'row_count' => count($rows)], JSON_THROW_ON_ERROR),
    ]);
} catch (InvalidArgumentException $exception) {
    http_response_code(400);
    exit(escape_output($exception->getMessage()));
} catch (Throwable) {
    http_response_code(403);
    exit('Akses ditolak.');
}

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="akrab-users-' . date('Ymd-His') . '.csv"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, private');
header('Pragma: no-cache');
$stream = fopen('php://output', 'wb');
fputcsv($stream, ['ID', 'Nama', 'Username', 'Role', 'Status', 'Kelas', 'Terdaftar']);
foreach ($rows as $row) {
    fputcsv($stream, array_map('csvSafeCell', [
        $row['id'], $row['nama'], $row['username'], $row['role'],
        $row['status'], $row['kelas'] ?? '', $row['created_at'],
    ]));
}
fclose($stream);
