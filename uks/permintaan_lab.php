<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/helpers.php';

check_role('uks');
$service = new QuestionnaireLabService($pdo);
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $requestId = filter_var($_POST['request_id'] ?? null, FILTER_VALIDATE_INT);
        $decision = (string) ($_POST['decision'] ?? '');
        if (!$requestId || !in_array($decision, ['approve', 'reject'], true)) throw new InvalidArgumentException('Keputusan tidak valid.');
        $service->review((int) $requestId, (int) $_SESSION['user_id'], 'uks', $decision === 'approve');
        recordAuditEvent($pdo, (int) $_SESSION['user_id'], 'questionnaire.lab_change_reviewed', 'lab_change_request', (int) $requestId, ['actor_role' => 'uks', 'decision' => $decision, 'outcome' => 'success']);
        header('Location: permintaan_lab.php?processed=1'); exit;
    } catch (InvalidArgumentException $exception) { $error = $exception->getMessage(); }
}
$requests = $service->pendingRequests();
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Permintaan Perubahan Lab - AKRAB</title><link href="/assets/vendor/bootstrap.min.css" rel="stylesheet"><link href="../assets/css/style.css?v=20260818" rel="stylesheet"></head><body class="bg-light"><main class="container py-5">
<div class="d-flex justify-content-between align-items-start mb-4"><div><p class="text-uppercase small fw-semibold text-success mb-1">Verifikasi UKS</p><h1 class="h3">Permintaan perubahan data laboratorium</h1><p class="text-muted">Nilai baru baru berlaku setelah disetujui.</p></div><a class="btn btn-outline-secondary" href="hasil_kuesioner.php">Kembali</a></div>
<?php if ($error): ?><div class="alert alert-danger"><?= escape_output($error) ?></div><?php endif; ?>
<?php if (isset($_GET['processed'])): ?><div class="alert alert-success">Permintaan berhasil diproses.</div><?php endif; ?>
<div class="card shadow-sm border-0"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Siswa</th><th>Hb</th><th>MCHC</th><th>MCV</th><th>MCH</th><th>Tanggal</th><th>Keputusan</th></tr></thead><tbody>
<?php if (!$requests): ?><tr><td colspan="7" class="text-center text-muted py-5">Tidak ada permintaan pending.</td></tr><?php endif; ?>
<?php foreach ($requests as $request): ?><tr><td><strong><?= escape_output((string)$request['nama']) ?></strong><small class="d-block text-muted"><?= escape_output((string)$request['kelas']) ?></small></td><td><?= escape_output((string)$request['current_hb']) ?> → <strong><?= escape_output((string)$request['kadar_hb']) ?></strong></td><td><?= escape_output((string)$request['current_mchc']) ?> → <strong><?= escape_output((string)$request['kadar_mchc']) ?></strong></td><td><?= escape_output((string)$request['current_mcv']) ?> → <strong><?= escape_output((string)$request['kadar_mcv']) ?></strong></td><td><?= escape_output((string)$request['current_mch']) ?> → <strong><?= escape_output((string)$request['kadar_mch']) ?></strong></td><td><?= escape_output((string)$request['created_at']) ?></td><td><form method="post" class="d-flex gap-2"><?= csrfInput() ?><input type="hidden" name="request_id" value="<?= (int)$request['id'] ?>"><button class="btn btn-sm btn-success" name="decision" value="approve">Setujui</button><button class="btn btn-sm btn-outline-danger" name="decision" value="reject">Tolak</button></form></td></tr><?php endforeach; ?>
</tbody></table></div></div></main></body></html>
