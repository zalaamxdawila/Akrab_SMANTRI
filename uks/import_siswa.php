<?php
require_once '../config.php';
require_once '../helpers.php';

check_role('uks');
$success = isset($_GET['imported'], $_GET['skipped'])
    ? sprintf(
        'Berhasil import %d siswa. (Dilewati/Duplikat: %d)',
        max(0, (int) $_GET['imported']),
        max(0, (int) $_GET['skipped'])
    )
    : '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_csv'])) {
    $file = $_FILES['file_csv'];

    $mime = is_file($file['tmp_name']) ? (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) : '';
    if ($file['error'] !== UPLOAD_ERR_OK || (int) $file['size'] > AKRAB_CSV_MAX_BYTES) {
        $error = 'File tidak valid atau melebihi batas ukuran 2 MB.';
    } elseif (
        strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'csv'
        || !in_array($mime, ['text/plain', 'text/csv', 'application/vnd.ms-excel'], true)
    ) {
        $error = 'Harap unggah file dengan format .csv.';
    } else {
        $batchHash = hash_file('sha256', $file['tmp_name']);
        $duplicate = $pdo->prepare('SELECT id FROM csv_import_batches WHERE batch_hash = ?');
        $duplicate->execute([$batchHash]);
        if ($duplicate->fetch()) {
            $error = 'File ini sudah pernah diproses.';
        } else {
            $handle = fopen($file['tmp_name'], 'rb');
            $imported = 0;
            $skipped = 0;
            $rowNumber = 0;
            $startedAt = microtime(true);
            try {
                $header = fgetcsv($handle, AKRAB_CSV_MAX_LINE_LENGTH, ',');
                if (!is_array($header) || !csvHeaderIsValid($header)) {
                    throw new InvalidArgumentException('Header CSV harus: Nama,Kelas,Username,Password.');
                }

                $pdo->beginTransaction();
                $exists = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
                $insert = $pdo->prepare(
                    "INSERT INTO users (username, password_hash, nama, kelas, role)
                     VALUES (?, ?, ?, ?, 'siswa')"
                );
                while (($data = fgetcsv($handle, AKRAB_CSV_MAX_LINE_LENGTH, ',')) !== false) {
                    $rowNumber++;
                    if ($rowNumber > AKRAB_CSV_MAX_ROWS || microtime(true) - $startedAt > 10) {
                        throw new RuntimeException('Import melebihi batas waktu atau jumlah baris.');
                    }
                    try {
                        [$nama, $kelas, $username, $password] = csvStudentRow($data);
                    } catch (InvalidArgumentException) {
                        $skipped++;
                        continue;
                    }
                    $exists->execute([$username]);
                    if ($exists->fetch()) {
                        $skipped++;
                        continue;
                    }
                    $insert->execute([$username, password_hash($password, PASSWORD_DEFAULT), $nama, $kelas]);
                    $imported++;
                }
                $batch = $pdo->prepare(
                    'INSERT INTO csv_import_batches (batch_hash, created_by, imported_count, skipped_count)
                     VALUES (?, ?, ?, ?)'
                );
                $batch->execute([$batchHash, (int) $_SESSION['user_id'], $imported, $skipped]);
                $pdo->commit();
                fclose($handle);
                $query = http_build_query(['imported' => $imported, 'skipped' => $skipped]);
                header("Location: import_siswa.php?{$query}");
                exit;
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                if (is_resource($handle)) {
                    fclose($handle);
                }
                $error = $exception instanceof InvalidArgumentException
                    ? $exception->getMessage()
                    : 'Import gagal dan tidak ada perubahan yang disimpan.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Siswa Massal - AKRAB UKS</title>
    <link href="/assets/vendor/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css?v=20260818" rel="stylesheet">
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
                        <?= csrfInput() ?>
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

<script src="/assets/vendor/bootstrap.bundle.min.js"></script>
<script>
  lucide.createIcons();
</script>
<script src="../assets/js/app-init.js?v=20260831-safe-install"></script>
</body>
</html>
