<?php
require_once '../config.php';
require_once '../helpers.php';

check_role('uks');
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_csv'])) {
    $file = $_FILES['file_csv'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        
        if (strtolower($ext) === 'csv') {
            $handle = fopen($file['tmp_name'], 'r');
            $imported = 0;
            $skipped = 0;
            
            // Skip header if first row looks like header
            $first_row = fgetcsv($handle, 1000, ",");
            if (strtolower(trim($first_row[0])) !== 'nama') {
                // If it's not header, rewind
                rewind($handle);
            }
            
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // Format: Nama, Kelas, Username, Password
                if (count($data) >= 4) {
                    $nama = trim($data[0]);
                    $kelas = trim($data[1]);
                    $username = trim($data[2]);
                    $password = trim($data[3]);
                    
                    // Check if username exists
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                    $stmt->execute([$username]);
                    if (!$stmt->fetch()) {
                        // Insert
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $insert = $pdo->prepare("INSERT INTO users (username, password_hash, nama, kelas, role) VALUES (?, ?, ?, ?, 'siswa')");
                        if ($insert->execute([$username, $hash, $nama, $kelas])) {
                            $imported++;
                        } else {
                            $skipped++;
                        }
                    } else {
                        $skipped++;
                    }
                }
            }
            fclose($handle);
            $success = "Berhasil import $imported siswa. (Dilewati/Duplikat: $skipped)";
        } else {
            $error = "Harap unggah file dengan format .csv";
        }
    } else {
        $error = "Terjadi kesalahan saat mengunggah file.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Siswa Massal - AKRAB UKS</title>
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
                <li class="nav-item"><a class="nav-link text-white active fw-bold" href="data_siswa.php">Data Siswa</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="edukasi.php">SOP Penanganan</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="jawab_konsultasi.php">Konsultasi</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="profil.php">Profil</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="mb-0">Import Siswa Massal</h3>
                <a href="data_siswa.php" class="btn btn-outline-secondary">Kembali</a>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-auto-dismiss"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-auto-dismiss"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-4">
                    <h5 class="text-primary border-bottom pb-2 mb-3">Upload File CSV</h5>
                    
                    <div class="alert alert-info">
                        <strong>Format CSV:</strong> Pastikan file memiliki 4 kolom (dipisahkan oleh koma):<br>
                        <code>Nama, Kelas, Username, Password</code><br>
                        <small>Contoh Baris 1: Budi Santoso, X IPA 1, budi_xipa1, password123</small>
                    </div>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-4">
                            <label class="form-label">Pilih File CSV</label>
                            <input class="form-control" type="file" name="file_csv" accept=".csv" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2">
                            <i data-lucide="upload"></i> Mulai Import
                        </button>
                    </form>
                </div>
            </div>
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
