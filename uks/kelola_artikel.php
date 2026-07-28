<?php
require_once '../config.php';
require_once '../helpers.php';

check_role('uks');
$uks_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Auto-create table
$pdo->exec("CREATE TABLE IF NOT EXISTS artikel_edukasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uks_id INT NOT NULL,
    judul VARCHAR(255) NOT NULL,
    konten TEXT NOT NULL,
    tanggal_publikasi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uks_id) REFERENCES users(id) ON DELETE CASCADE
)");

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM artikel_edukasi WHERE id = ?");
    if ($stmt->execute([$id])) {
        header("Location: kelola_artikel.php?success=deleted");
        exit;
    }
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = trim($_POST['judul']);
    $konten = trim($_POST['konten']);
    
    if (empty($judul) || empty($konten)) {
        $error = "Judul dan konten tidak boleh kosong.";
    } else {
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            // Edit
            $stmt = $pdo->prepare("UPDATE artikel_edukasi SET judul = ?, konten = ? WHERE id = ?");
            $stmt->execute([$judul, $konten, $_POST['id']]);
            $success = "Artikel berhasil diperbarui.";
        } else {
            // Add
            $stmt = $pdo->prepare("INSERT INTO artikel_edukasi (uks_id, judul, konten) VALUES (?, ?, ?)");
            $stmt->execute([$uks_id, $judul, $konten]);
            $success = "Artikel berhasil diterbitkan.";
        }
    }
}

// Fetch all articles
$stmt = $pdo->query("SELECT * FROM artikel_edukasi ORDER BY tanggal_publikasi DESC");
$articles = $stmt->fetchAll();

if (isset($_GET['success']) && $_GET['success'] === 'deleted') {
    $success = "Artikel berhasil dihapus.";
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Artikel - AKRAB UKS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css?v=<?= time() ?>" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-light">

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
                            <a href="?delete=<?= $a['id'] ?>" onclick="return confirm('Yakin ingin menghapus artikel ini?')" class="btn btn-sm btn-outline-danger">
                                <i data-lucide="trash-2" style="width: 16px; height: 16px;"></i> Hapus
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Add -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST">
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  lucide.createIcons();
</script>
<script src="../assets/js/app-init.js"></script>
</body>
</html>
