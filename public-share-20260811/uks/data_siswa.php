<?php
require_once '../config.php';
require_once '../helpers.php';

check_role('uks');

$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$params = [];
$page = max(1, filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1);
$perPage = 25;
$where = " WHERE u.role = 'siswa'";

if (!empty($search)) {
    $where .= " AND (u.nama LIKE ? OR u.username LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$countStmt = $pdo->prepare('SELECT COUNT(*) FROM users u' . $where);
$countStmt->execute($params);
$totalSiswa = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalSiswa / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$query = "
    SELECT 
        u.id, 
        u.nama, 
        u.kelas, 
        u.username,
        (SELECT kategori_risiko FROM hasil_deteksi WHERE user_id = u.id ORDER BY tanggal DESC, id DESC LIMIT 1) as risiko_terakhir,
        (SELECT tanggal FROM hasil_deteksi WHERE user_id = u.id ORDER BY tanggal DESC, id DESC LIMIT 1) as tanggal_cek,
        (SELECT COUNT(*) FROM konsumsi_ttd WHERE user_id = u.id AND status_konsumsi = 'sudah') as total_ttd
    FROM users u
";
$query .= $where . " ORDER BY u.kelas ASC, u.nama ASC LIMIT ? OFFSET ?";

$stmt = $pdo->prepare($query);
$position = 1;
foreach ($params as $value) {
    $stmt->bindValue($position++, $value, PDO::PARAM_STR);
}
$stmt->bindValue($position++, $perPage, PDO::PARAM_INT);
$stmt->bindValue($position, $offset, PDO::PARAM_INT);
$stmt->execute();
$siswa = $stmt->fetchAll();
$pageQuery = $search !== '' ? '&amp;search=' . rawurlencode($search) : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Siswa - AKRAB UKS</title>
    <link href="/assets/vendor/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css?v=20260729" rel="stylesheet">
    <script src="/assets/vendor/lucide.min.js"></script>
</head>
<body>
<?php renderImpersonationBanner($pdo, $_SESSION); ?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
    <div class="container">
        <a class="navbar-brand text-white fw-bold" href="dashboard.php">AKRAB UKS Panel</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link text-white" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link text-white active" href="data_siswa.php">Data Siswa</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="jawab_konsultasi.php">Konsultasi</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">Data Rekam Medis Siswa</h3>
        <a href="scan_qr.php" class="btn btn-primary shadow-sm d-flex align-items-center gap-2">
            <i data-lucide="scan-line" style="width: 18px; height: 18px;"></i> Pindai QR
        </a>
    </div>
    
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form action="data_siswa.php" method="GET" class="d-flex gap-2">
                <input type="text" name="search" class="form-control" placeholder="Cari Nama / NISN Siswa..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-primary">Cari</button>
                <?php if (!empty($search)): ?>
                    <a href="data_siswa.php" class="btn btn-outline-secondary">Reset</a>
                <?php endif; ?>
            </form>
        </div>
    </div>
    
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="d-flex justify-content-end mb-3 gap-2 flex-wrap">
                <a href="import_siswa.php" class="btn btn-outline-primary fw-bold d-inline-flex align-items-center gap-2"><i data-lucide="upload" style="width:18px;"></i> Import Data</a>
                <a href="export_csv.php?type=siswa" class="btn btn-success fw-bold d-inline-flex align-items-center gap-2 shadow-sm"><i data-lucide="download" style="width:18px;"></i> Data Siswa (CSV)</a>
                <a href="export_csv.php?type=log" class="btn btn-info text-white fw-bold d-inline-flex align-items-center gap-2 shadow-sm"><i data-lucide="file-spreadsheet" style="width:18px;"></i> Log TTD (CSV)</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Nama Siswa</th>
                            <th>Kelas</th>
                            <th>Total Minum TTD</th>
                            <th>Status Risiko Terakhir</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($siswa)): ?>
                            <tr><td colspan="6" class="text-center py-4">Belum ada data siswa terdaftar.</td></tr>
                        <?php else: ?>
                            <?php $no = $offset + 1; foreach ($siswa as $s): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><strong><?= htmlspecialchars($s['nama']) ?></strong><br><small class="text-muted">@<?= htmlspecialchars($s['username']) ?></small></td>
                                    <td><?= htmlspecialchars($s['kelas']) ?></td>
                                    <td><span class="badge bg-success rounded-pill"><?= $s['total_ttd'] ?> Kali</span></td>
                                    <td>
                                        <?php if ($s['risiko_terakhir']): ?>
                                            <?php 
                                                $badge_class = 'bg-success';
                                                if ($s['risiko_terakhir'] == 'sedang') $badge_class = 'bg-warning text-dark';
                                                if ($s['risiko_terakhir'] == 'tinggi') $badge_class = 'bg-danger';
                                            ?>
                                            <span class="badge <?= $badge_class ?>"><?= strtoupper($s['risiko_terakhir']) ?></span>
                                            <br><small class="text-muted"><?= date('d M Y', strtotime($s['tanggal_cek'])) ?></small>
                                        <?php else: ?>
                                            <span class="text-muted small">Belum Cek</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="detail_siswa.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-info">Detail</a>
                                        <?php if ($s['risiko_terakhir'] === 'tinggi'): ?>
                                            <a href="cetak_rujukan.php?id=<?= $s['id'] ?>&print=1" target="_blank" class="btn btn-sm btn-danger ms-1 d-inline-flex align-items-center gap-1">
                                                <i data-lucide="printer" style="width: 14px; height: 14px;"></i> Rujukan
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Halaman data siswa" class="mt-3">
                    <ul class="pagination justify-content-center mb-0">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="?page=<?= max(1, $page - 1) ?><?= $pageQuery ?>">Sebelumnya</a></li>
                        <li class="page-item disabled"><span class="page-link">Halaman <?= $page ?> dari <?= $totalPages ?></span></li>
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>"><a class="page-link" href="?page=<?= min($totalPages, $page + 1) ?><?= $pageQuery ?>">Berikutnya</a></li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="/assets/vendor/bootstrap.bundle.min.js"></script>
<script>
  lucide.createIcons();
</script>
<script src="../assets/js/app-init.js?v=20260729"></script>
</body>
</html>
