<?php
require_once '../bootstrap.php';

check_role('uks');
$user_id = $_SESSION['user_id'];
$success = isset($_GET['replied']);
$successMessage = $success ? 'Balasan berhasil dikirim!' : '';

// Handle reply
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['konsultasi_id']) && !empty($_POST['isi_balasan'])) {
    $kons_id = (int)$_POST['konsultasi_id'];
    $balasan = sanitize_input($_POST['isi_balasan']);
    
    try {
        (new ConsultationService($pdo))->reply($user_id, $kons_id, $balasan);
        header('Location: jawab_konsultasi.php?replied=1');
        exit;
    } catch (Exception $e) {
        $error = 'Gagal mengirim balasan. Silakan coba lagi.';
    }
}

// Bounded list query: 20 records per page and no per-row answer query.
$page = max(1, filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1);
$perPage = 20;
$totalKonsultasi = (int) $pdo->query('SELECT COUNT(*) FROM konsultasi')->fetchColumn();
$totalPages = max(1, (int) ceil($totalKonsultasi / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$query = "
    SELECT k.*, u.nama as nama_siswa, u.kelas,
           b.isi_balasan, b.tanggal_balas
    FROM konsultasi k
    JOIN users u ON k.siswa_id = u.id
    LEFT JOIN balasan_konsultasi b ON b.konsultasi_id = k.id
    ORDER BY k.status ASC, k.tanggal_kirim DESC
    LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($query);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$konsultasi = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jawab Konsultasi - AKRAB UKS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css?v=20260729" rel="stylesheet">
</head>
<body>
<?php renderImpersonationBanner($pdo, $_SESSION); ?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
    <div class="container">
        <a class="navbar-brand text-white fw-bold" href="dashboard.php">AKRAB UKS Panel</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-toggle="target" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link text-white" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="data_siswa.php">Data Siswa</a></li>
                <li class="nav-item"><a class="nav-link text-white active" href="jawab_konsultasi.php">Konsultasi</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-4">
    <h3 class="mb-4">Panel Konsultasi Siswa</h3>
    
    <?php require __DIR__ . '/../views/partials/flash.php'; ?>
    
    <div class="row">
        <div class="col-12">
            <?php if (empty($konsultasi)): ?>
                <div class="card p-5 text-center shadow-sm">
                    <p class="text-muted mb-0">Belum ada pertanyaan dari siswa.</p>
                </div>
            <?php else: ?>
                <?php foreach ($konsultasi as $k): ?>
                    <div class="card shadow-sm mb-3 <?= $k['status'] == 'menunggu' ? 'border-start border-warning border-5' : 'border-start border-success border-5 opacity-75' ?>">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h5 class="mb-0"><?= htmlspecialchars($k['nama_siswa']) ?> <span class="badge bg-secondary ms-2"><?= htmlspecialchars($k['kelas']) ?></span></h5>
                                    <small class="text-muted"><?= date('d M Y, H:i', strtotime($k['tanggal_kirim'])) ?></small>
                                </div>
                                <span class="badge <?= $k['status'] == 'menunggu' ? 'bg-warning text-dark' : 'bg-success' ?> fs-6">
                                    <?= strtoupper($k['status']) ?>
                                </span>
                            </div>
                            
                            <p class="fs-5 mb-4">"<?= nl2br(htmlspecialchars($k['pertanyaan'])) ?>"</p>
                            
                            <?php if ($k['status'] == 'menunggu'): ?>
                                <form method="POST">
                                    <?= csrfInput() ?>
                                    <input type="hidden" name="konsultasi_id" value="<?= $k['id'] ?>">
                                    <div class="mb-3">
                                        <label class="form-label text-primary fw-bold">Tulis Balasan:</label>
                                        <textarea class="form-control" name="isi_balasan" rows="3" required placeholder="Ketik jawaban Anda di sini..."></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Kirim Balasan</button>
                                </form>
                            <?php else: ?>
                                <div class="bg-light p-3 rounded border">
                                    <small class="text-muted d-block mb-2">Dibalas pada: <?= date('d M Y, H:i', strtotime($k['tanggal_balas'])) ?></small>
                                    <p class="mb-0 text-success fw-bold">Jawaban: <span class="text-dark fw-normal"><?= nl2br(htmlspecialchars($k['isi_balasan'])) ?></span></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php if ($totalPages > 1): ?>
        <nav aria-label="Halaman konsultasi">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="?page=<?= max(1, $page - 1) ?>">Sebelumnya</a></li>
                <li class="page-item disabled"><span class="page-link">Halaman <?= $page ?> dari <?= $totalPages ?></span></li>
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>"><a class="page-link" href="?page=<?= min($totalPages, $page + 1) ?>">Berikutnya</a></li>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js?v=20260729"></script>
<script src="../assets/js/app-init.js?v=20260729"></script>
</body>
</html>
