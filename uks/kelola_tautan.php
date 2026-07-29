<?php
require_once '../config.php';
require_once '../helpers.php';

check_role('uks');
$uksId = (int) $_SESSION['user_id'];
$error = '';
$success = isset($_GET['updated']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $linkId = (int) ($_POST['link_id'] ?? 0);
    $decision = (string) ($_POST['decision'] ?? '');

    if ($linkId <= 0 || !in_array($decision, ['approved', 'rejected'], true)) {
        $error = 'Permintaan tidak valid.';
    } else {
        $pdo->beginTransaction();
        try {
            $lock = $pdo->prepare(
                "SELECT id, requested_student_username
                 FROM parent_student_links
                 WHERE id = ? AND status = 'pending'
                 FOR UPDATE"
            );
            $lock->execute([$linkId]);
            $link = $lock->fetch();
            if (!$link) {
                throw new DomainException('Link request is unavailable.');
            }

            $studentId = null;
            if ($decision === 'approved') {
                $student = $pdo->prepare(
                    "SELECT id FROM users WHERE username = ? AND role = 'siswa' LIMIT 1"
                );
                $student->execute([$link['requested_student_username']]);
                $studentId = $student->fetchColumn();
                if (!$studentId) {
                    throw new DomainException('Requested student is unavailable.');
                }
            }

            $update = $pdo->prepare(
                'UPDATE parent_student_links
                 SET student_id = ?, status = ?, reviewed_by = ?, reviewed_at = CURRENT_TIMESTAMP
                 WHERE id = ?'
            );
            $update->execute([$studentId ?: null, $decision, $uksId, $linkId]);

            recordAuditEvent($pdo, $uksId, 'parent_link.' . $decision, 'parent_student_link', $linkId, ['outcome' => $decision]);

            $pdo->commit();
            header('Location: kelola_tautan.php?updated=1');
            exit;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Permintaan tidak dapat diproses. Pastikan NISN siswa valid dan masih aktif.';
        }
    }
}

$page = max(1, filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1);
$perPage = 25;
$totalPending = (int) $pdo->query("SELECT COUNT(*) FROM parent_student_links WHERE status = 'pending'")->fetchColumn();
$totalPages = max(1, (int) ceil($totalPending / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$pendingStmt = $pdo->prepare(
    "SELECT psl.id, psl.requested_student_username, psl.requested_at,
            p.nama AS parent_name, p.username AS parent_username
     FROM parent_student_links psl
     JOIN users p ON p.id = psl.parent_id AND p.role = 'orangtua'
     WHERE psl.status = 'pending'
     ORDER BY psl.requested_at ASC, psl.id ASC
     LIMIT ? OFFSET ?"
);
$pendingStmt->bindValue(1, $perPage, PDO::PARAM_INT);
$pendingStmt->bindValue(2, $offset, PDO::PARAM_INT);
$pendingStmt->execute();
$pending = $pendingStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Tautan Orang Tua - AKRAB</title>
    <link href="/assets/vendor/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php renderImpersonationBanner($pdo, $_SESSION); ?>
<main class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">Verifikasi Tautan Orang Tua–Siswa</h3>
        <a href="dashboard.php" class="btn btn-outline-primary">Kembali</a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success">Keputusan tersimpan dan tercatat pada audit log.</div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr><th>Orang Tua</th><th>NISN diminta</th><th>Waktu</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                <?php foreach ($pending as $link): ?>
                    <tr>
                        <td><?= htmlspecialchars($link['parent_name']) ?><br><small><?= htmlspecialchars($link['parent_username']) ?></small></td>
                        <td><?= htmlspecialchars($link['requested_student_username']) ?></td>
                        <td><?= htmlspecialchars($link['requested_at']) ?></td>
                        <td>
                            <form method="POST" class="d-flex gap-2">
                                <?= csrfInput() ?>
                                <input type="hidden" name="link_id" value="<?= (int) $link['id'] ?>">
                                <button name="decision" value="approved" class="btn btn-sm btn-success">Setujui</button>
                                <button name="decision" value="rejected" class="btn btn-sm btn-outline-danger">Tolak</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$pending): ?>
                    <tr><td colspan="4" class="text-center text-muted py-4">Tidak ada permintaan menunggu.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($totalPages > 1): ?>
        <nav aria-label="Halaman verifikasi wali" class="mt-3">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="?page=<?= max(1, $page - 1) ?>">Sebelumnya</a></li>
                <li class="page-item disabled"><span class="page-link">Halaman <?= $page ?> dari <?= $totalPages ?></span></li>
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>"><a class="page-link" href="?page=<?= min($totalPages, $page + 1) ?>">Berikutnya</a></li>
            </ul>
        </nav>
    <?php endif; ?>
</main>
</body>
</html>
