<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/helpers.php';

check_role('siswa');
$studentId = (int) $_SESSION['user_id'];
$repository = new QuestionnaireAnalyticsRepository($pdo);
$history = $repository->historyForStudent($studentId);
$questionnaire = $history ? $history[array_key_last($history)] : null;
if (!$questionnaire) {
    header('Location: kuesioner.php');
    exit;
}
$fields = ['kadar_hb', 'kadar_mchc', 'kadar_mcv', 'kadar_mch'];
$hasLab = true;
foreach ($fields as $field) {
    if ($questionnaire[$field] === null || $questionnaire[$field] === '') $hasLab = false;
}
$service = new QuestionnaireLabService($pdo);
$pending = $service->pendingForStudent($studentId);
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($hasLab) {
            $requestId = $service->requestChange($studentId, $_POST);
            recordAuditEvent($pdo, $studentId, 'questionnaire.lab_change_requested', 'lab_change_request', $requestId, ['actor_role' => 'siswa', 'outcome' => 'success']);
            header('Location: data_laboratorium.php?requested=1');
        } else {
            $service->completeInitial($studentId, $_POST);
            recordAuditEvent($pdo, $studentId, 'questionnaire.lab_completed', 'questionnaire', (int) $questionnaire['id'], ['actor_role' => 'siswa', 'outcome' => 'success']);
            header('Location: hasil_deteksi.php?lab_saved=1');
        }
        exit;
    } catch (InvalidArgumentException $exception) {
        $error = $exception->getMessage();
    } catch (Throwable $exception) {
        akrabLog('error', 'questionnaire_lab_failed', ['exception_class' => get_class($exception), 'outcome' => 'error']);
        $error = publicErrorMessage();
    }
}
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Data Laboratorium - AKRAB</title><link href="/assets/vendor/bootstrap.min.css" rel="stylesheet"><link href="../assets/css/style.css?v=20260729" rel="stylesheet"></head>
<body class="bg-light"><main class="container py-5" style="max-width:760px">
<a href="dashboard.php" class="btn btn-sm btn-outline-secondary mb-3">Kembali</a>
<section class="card shadow-sm border-0"><div class="card-body p-4 p-md-5">
<p class="text-uppercase small fw-semibold text-primary mb-1">Simulasi Model Penelitian</p>
<h1 class="h3">Data laboratorium regresi logistik</h1>
<p class="text-muted">Keempat nilai wajib diisi bersamaan. Sistem menggunakan data ini untuk simulasi penelitian, bukan diagnosis medis.</p>
<?php if ($error): ?><div class="alert alert-danger"><?= escape_output($error) ?></div><?php endif; ?>
<?php if (isset($_GET['requested'])): ?><div class="alert alert-success">Permintaan perubahan sudah dikirim ke UKS/superadmin.</div><?php endif; ?>
<?php if ($pending): ?><div class="alert alert-warning">Permintaan perubahan sedang menunggu persetujuan. Form dinonaktifkan sampai diproses.</div><?php endif; ?>
<?php if ($hasLab): ?><div class="alert alert-info">Data sudah tersimpan. Nilai baru di bawah akan menjadi permintaan perubahan dan belum berlaku sebelum disetujui.</div><?php endif; ?>
<form method="post"><?= csrfInput() ?><div class="row g-3">
<?php foreach ([['kadar_hb','Hb','g/dL',30],['kadar_mchc','MCHC','g/dL',100],['kadar_mcv','MCV','fL',200],['kadar_mch','MCH','pg',100]] as [$name,$label,$unit,$max]): ?>
<div class="col-md-6"><label class="form-label fw-semibold" for="<?= $name ?>"><?= $label ?> (<?= $unit ?>)</label><input class="form-control form-control-lg" type="number" step="0.01" min="0" max="<?= $max ?>" name="<?= $name ?>" id="<?= $name ?>" required <?= $pending ? 'disabled' : '' ?>></div>
<?php endforeach; ?></div><button class="btn btn-primary w-100 mt-4" type="submit" <?= $pending ? 'disabled' : '' ?>><?= $hasLab ? 'Ajukan perubahan data' : 'Simpan dan hitung regresi logistik' ?></button></form>
</div></section></main></body></html>
