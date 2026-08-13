<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/helpers.php';
require_once dirname(__DIR__) . '/app/Security/SuperadminGuard.php';
require_once dirname(__DIR__) . '/views/superadmin/layout.php';

try { SuperadminGuard::authorize($pdo, $_SESSION); } catch (Throwable) { http_response_code(403); exit('Akses ditolak.'); }
$service = new QuestionnaireLabService($pdo);
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $requestId = filter_var($_POST['request_id'] ?? null, FILTER_VALIDATE_INT);
        $decision = (string) ($_POST['decision'] ?? '');
        if (!$requestId || !in_array($decision, ['approve', 'reject'], true)) throw new InvalidArgumentException('Keputusan tidak valid.');
        $service->review((int)$requestId, (int)$_SESSION['user_id'], 'superadmin', $decision === 'approve');
        recordAuditEvent($pdo, (int)$_SESSION['user_id'], 'questionnaire.lab_change_reviewed', 'lab_change_request', (int)$requestId, ['actor_role'=>'superadmin','decision'=>$decision,'outcome'=>'success']);
        header('Location: lab_requests.php?processed=1'); exit;
    } catch (InvalidArgumentException $exception) { $error = $exception->getMessage(); }
}
$requests = $service->pendingRequests();
renderSuperadminHeader('Permintaan Data Lab', 'questionnaires');
?>
<div class="d-flex justify-content-between align-items-start mb-4"><div><p class="eyebrow mb-1">Persetujuan data</p><h1 class="h4">Permintaan perubahan laboratorium</h1><p class="text-muted mb-0">Review nilai pengganti sebelum model dihitung ulang.</p></div><a class="btn btn-outline-secondary" href="questionnaire_results.php">Kembali</a></div>
<?php if ($error): ?><div class="alert alert-danger"><?= escape_output($error) ?></div><?php endif; ?>
<?php if (isset($_GET['processed'])): ?><div class="alert alert-success">Permintaan berhasil diproses.</div><?php endif; ?>
<section class="master-card p-3 p-lg-4"><div class="table-responsive"><table class="table master-table align-middle"><thead><tr><th>Siswa</th><th>Hb</th><th>MCHC</th><th>MCV</th><th>MCH</th><th>Diajukan</th><th></th></tr></thead><tbody>
<?php if (!$requests): ?><tr><td colspan="7" class="empty-state">Tidak ada permintaan pending.</td></tr><?php endif; ?>
<?php foreach ($requests as $request): ?><tr><td><strong><?= escape_output((string)$request['nama']) ?></strong><small class="d-block text-muted"><?= escape_output((string)$request['kelas']) ?></small></td><td><?= escape_output((string)$request['current_hb']) ?> → <strong><?= escape_output((string)$request['kadar_hb']) ?></strong></td><td><?= escape_output((string)$request['current_mchc']) ?> → <strong><?= escape_output((string)$request['kadar_mchc']) ?></strong></td><td><?= escape_output((string)$request['current_mcv']) ?> → <strong><?= escape_output((string)$request['kadar_mcv']) ?></strong></td><td><?= escape_output((string)$request['current_mch']) ?> → <strong><?= escape_output((string)$request['kadar_mch']) ?></strong></td><td><?= escape_output((string)$request['created_at']) ?></td><td><form method="post" class="d-flex gap-2"><?= csrfInput() ?><input type="hidden" name="request_id" value="<?= (int)$request['id'] ?>"><button class="btn btn-sm btn-success" name="decision" value="approve">Setujui</button><button class="btn btn-sm btn-outline-danger" name="decision" value="reject">Tolak</button></form></td></tr><?php endforeach; ?>
</tbody></table></div></section>
<?php renderSuperadminFooter(); ?>
