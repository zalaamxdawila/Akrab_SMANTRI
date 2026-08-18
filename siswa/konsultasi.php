<?php
require_once '../bootstrap.php';

check_role('siswa');
$user_id = $_SESSION['user_id'];
$success = isset($_GET['sent']);
$successMessage = $success ? 'Pertanyaan berhasil dikirim! Petugas akan segera membalas.' : '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['pertanyaan'])) {
    $pertanyaan = sanitize_input($_POST['pertanyaan']);
    
    try {
        (new ConsultationService($pdo))->ask($user_id, $pertanyaan);
        header('Location: konsultasi.php?sent=1');
        exit;
    } catch (Throwable $exception) {
        $error = "Gagal mengirim pertanyaan.";
    }
}

// Get a bounded, ownership-scoped history page.
$page = max(1, filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1);
$perPage = 10;
$countStmt = $pdo->prepare('SELECT COUNT(*) FROM konsultasi WHERE siswa_id = ?');
$countStmt->execute([$user_id]);
$totalHistory = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalHistory / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$stmt = $pdo->prepare("SELECT k.*, b.isi_balasan, b.tanggal_balas 
                      FROM konsultasi k 
                      LEFT JOIN balasan_konsultasi b ON k.id = b.konsultasi_id 
                      WHERE k.siswa_id = ? 
                      ORDER BY k.tanggal_kirim DESC, k.id DESC
                      LIMIT ? OFFSET ?");
$stmt->bindValue(1, $user_id, PDO::PARAM_INT);
$stmt->bindValue(2, $perPage, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$riwayat = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konsultasi - AKRAB</title>
    <link href="/assets/vendor/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css?v=20260818" rel="stylesheet">
</head>
<body>
<?php renderImpersonationBanner($pdo, $_SESSION); ?>

<nav class="navbar navbar-expand-lg navbar-light sticky-top">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">AKRAB Siswa</a>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="kuesioner.php">Kuesioner</a></li>
                <li class="nav-item"><a class="nav-link active" href="konsultasi.php">Konsultasi</a></li>
                <li class="nav-item"><a class="nav-link text-danger" href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="row">
        <!-- Form -->
        <div class="col-md-5 mb-4">
            <div class="card p-4 shadow-sm h-100">
                <h4 class="mb-3">Tanya Petugas UKS</h4>
                
                <?php require __DIR__ . '/../views/partials/flash.php'; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <?= csrfInput() ?>
                    <div class="mb-3">
                        <label class="form-label">Tulis pertanyaan atau keluhan Anda:</label>
                        <textarea class="form-control" name="pertanyaan" rows="5" required placeholder="Misal: Saya sering pusing setelah olahraga, apakah itu bahaya?"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Kirim Pertanyaan</button>
                </form>
            </div>
        </div>
        
        <!-- Riwayat -->
        <div class="col-md-7">
            <div class="card p-4 shadow-sm h-100">
                <h4 class="mb-4">Riwayat Konsultasi</h4>
                
                <?php if (empty($riwayat)): ?>
                    <p class="text-muted text-center my-5">Belum ada riwayat konsultasi.</p>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3 overflow-auto" style="max-height: 500px; padding-right: 10px;">
                        <?php foreach ($riwayat as $k): ?>
                            <div class="border rounded p-3 bg-light">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="badge <?= $k['status'] == 'menunggu' ? 'bg-warning text-dark' : 'bg-success' ?>">
                                        <?= strtoupper($k['status']) ?>
                                    </span>
                                    <small class="text-muted"><?= date('d M Y, H:i', strtotime($k['tanggal_kirim'])) ?></small>
                                </div>
                                
                                <p class="mb-2 fw-bold">P: <?= htmlspecialchars($k['pertanyaan']) ?></p>
                                
                                <?php if ($k['status'] == 'dijawab'): ?>
                                    <div class="border-start border-3 border-success ps-3 mt-3">
                                        <small class="text-muted d-block mb-1">Dibalas pada: <?= date('d M Y, H:i', strtotime($k['tanggal_balas'])) ?></small>
                                        <p class="mb-0 text-secondary"><strong>Jawab:</strong> <?= nl2br(htmlspecialchars($k['isi_balasan'])) ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Halaman riwayat konsultasi" class="mt-3">
                            <ul class="pagination pagination-sm justify-content-center mb-0">
                                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="?page=<?= max(1, $page - 1) ?>">Sebelumnya</a></li>
                                <li class="page-item disabled"><span class="page-link"><?= $page ?> / <?= $totalPages ?></span></li>
                                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>"><a class="page-link" href="?page=<?= min($totalPages, $page + 1) ?>">Berikutnya</a></li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
                
            </div>
        </div>
    </div>
</div>

<script src="/assets/vendor/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js?v=20260818"></script>
<script src="../assets/js/app-init.js?v=20260818"></script>
</body>
</html>
