<?php
require_once '../config.php';
require_once '../helpers.php';

check_role('uks');
$uks_id = $_SESSION['user_id'];
$successMessages = [
    'created' => 'Artikel berhasil diterbitkan.',
    'updated' => 'Artikel berhasil diperbarui.',
    'deleted' => 'Artikel berhasil dihapus.',
];
$success = $successMessages[$_GET['success'] ?? ''] ?? '';
$error = '';

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = (int) $_POST['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM artikel_edukasi WHERE id = ? AND uks_id = ?");
    if ($id > 0 && $stmt->execute([$id, $uks_id]) && $stmt->rowCount() === 1) {
        recordAuditEvent($pdo, (int) $uks_id, 'article.deleted', 'article', $id, ['outcome' => 'success']);
        header("Location: kelola_artikel.php?success=deleted");
        exit;
    }
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_id'])) {
    $judul = trim($_POST['judul']);
    $konten = trim($_POST['konten']);
    
    if (empty($judul) || empty($konten)) {
        $error = "Judul dan konten tidak boleh kosong.";
    } else {
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            // Edit
            $articleId = (int) $_POST['id'];
            $ownership = $pdo->prepare("SELECT id FROM artikel_edukasi WHERE id = ? AND uks_id = ?");
            $ownership->execute([$articleId, $uks_id]);
            if ($ownership->fetch()) {
                $stmt = $pdo->prepare("UPDATE artikel_edukasi SET judul = ?, konten = ? WHERE id = ? AND uks_id = ?");
                $stmt->execute([$judul, $konten, $articleId, $uks_id]);
                recordAuditEvent($pdo, (int) $uks_id, 'article.updated', 'article', $articleId, ['outcome' => 'success']);
                header('Location: kelola_artikel.php?success=updated');
                exit;
            }
            $error = 'Artikel tidak ditemukan atau tidak boleh diubah.';
        } else {
            // Add
            $stmt = $pdo->prepare("INSERT INTO artikel_edukasi (uks_id, judul, konten) VALUES (?, ?, ?)");
            $stmt->execute([$uks_id, $judul, $konten]);
            $articleId = (int) $pdo->lastInsertId();
            recordAuditEvent($pdo, (int) $uks_id, 'article.created', 'article', $articleId, ['outcome' => 'success']);
            header('Location: kelola_artikel.php?success=created');
            exit;
        }
    }
}

// Fetch a bounded page of articles owned by the current UKS account.
$page = max(1, filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1);
$perPage = 15;
$countStmt = $pdo->prepare('SELECT COUNT(*) FROM artikel_edukasi WHERE uks_id = ?');
$countStmt->execute([$uks_id]);
$totalArticles = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalArticles / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$stmt = $pdo->prepare("SELECT * FROM artikel_edukasi WHERE uks_id = ? ORDER BY tanggal_publikasi DESC, id DESC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $uks_id, PDO::PARAM_INT);
$stmt->bindValue(2, $perPage, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$articles = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Artikel - AKRAB UKS</title>
    <link href="/assets/vendor/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css?v=20260729" rel="stylesheet">
    <script src="/assets/vendor/lucide.min.js"></script>
</head>
<body class="bg-light">
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
                <li class="nav-item"><a class="nav-link text-white" href="data_siswa.php">Data Siswa</a></li>
                <li class="nav-item"><a class="nav-link text-white active fw-bold" href="kelola_artikel.php">Berita</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="edukasi.php">SOP Penanganan</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="jawab_konsultasi.php">Konsultasi</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">Kelola Artikel / Pengumuman</h3>
        <button class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#addModal">
            <i data-lucide="plus"></i> Tulis Artikel Baru
        </button>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-auto-dismiss"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger alert-auto-dismiss"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-4">Tanggal</th>
                        <th>Judul Artikel</th>
                        <th class="text-end px-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($articles)): ?>
                    <tr>
                        <td colspan="3" class="text-center py-4 text-muted">Belum ada artikel. Publikasikan berita pertama Anda!</td>
                    </tr>
                    <?php endif; ?>
                    <?php foreach ($articles as $a): ?>
                    <tr>
                        <td class="px-4 align-middle"><?= date('d M Y, H:i', strtotime($a['tanggal_publikasi'])) ?></td>
                        <td class="align-middle fw-bold"><?= htmlspecialchars($a['judul']) ?></td>
                        <td class="text-end px-4">
                            <form method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus artikel ini?')">
                                <?= csrfInput() ?>
                                <input type="hidden" name="delete_id" value="<?= (int) $a['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i data-lucide="trash-2" style="width: 16px; height: 16px;"></i> Hapus
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($totalPages > 1): ?>
        <nav aria-label="Halaman artikel" class="mt-3">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="?page=<?= max(1, $page - 1) ?>">Sebelumnya</a></li>
                <li class="page-item disabled"><span class="page-link">Halaman <?= $page ?> dari <?= $totalPages ?></span></li>
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>"><a class="page-link" href="?page=<?= min($totalPages, $page + 1) ?>">Berikutnya</a></li>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<!-- Modal Add -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST">
          <?= csrfInput() ?>
          <div class="modal-header">
            <h5 class="modal-title fw-bold">Tulis Artikel Baru</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
                <label class="form-label fw-bold">Judul Artikel / Pengumuman</label>
                <input type="text" name="judul" class="form-control" required placeholder="Contoh: Pentingnya Minum TTD Sebelum Tidur">
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">Konten</label>
                <textarea name="konten" class="form-control" rows="6" required placeholder="Tulis isi berita atau pengumuman di sini..."></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary d-flex align-items-center gap-2">
                <i data-lucide="send"></i> Publikasikan
            </button>
          </div>
      </form>
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
