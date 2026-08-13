<?php
require_once '../config.php';
require_once '../helpers.php';

check_role('siswa');
$user_id = $_SESSION['user_id'];

// Handle Menstrual Toggle
if (isset($_POST['toggle_haid'])) {
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT id FROM riwayat_haid WHERE user_id = ? AND tanggal_selesai IS NULL FOR UPDATE");
        $stmt->execute([$user_id]);
        $active_haid = $stmt->fetch();

        if ($active_haid) {
            $stmt = $pdo->prepare("UPDATE riwayat_haid SET tanggal_selesai = CURDATE() WHERE id = ? AND tanggal_selesai IS NULL");
            $stmt->execute([$active_haid['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO riwayat_haid (user_id, tanggal_mulai) VALUES (?, CURDATE())");
            $stmt->execute([$user_id]);
        }
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(409);
        exit('Status siklus tidak dapat diperbarui.');
    }
    header("Location: dashboard.php?haid_updated=1");
    exit;
}

// Check current menstrual status
$stmt = $pdo->prepare("SELECT id FROM riwayat_haid WHERE user_id = ? AND tanggal_selesai IS NULL");
$stmt->execute([$user_id]);
$sedang_haid = $stmt->fetch() ? true : false;

// Check latest questionnaire
$stmt = $pdo->prepare("SELECT * FROM kuesioner WHERE user_id = ? AND archived_at IS NULL ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$user_id]);
$kuesioner = $stmt->fetch();

$questionnaireEligibility = (new QuestionnaireEligibility())->forLatestSubmission(
    $kuesioner['created_at'] ?? null
);
$can_fill_kuesioner = $questionnaireEligibility['allowed'];
$next_kuesioner_date = $questionnaireEligibility['next_eligible_at']
    ? $questionnaireEligibility['next_eligible_at']->format('d M Y')
    : null;

// A result is current only when it was created with the latest questionnaire.
$hasil_deteksi = $kuesioner
    ? (new QuestionnaireAnalyticsRepository($pdo))->latestDetectionForStudent(
        (int) $user_id,
        (int) ($kuesioner['id'] ?? 0)
    )
    : null;

// Handle TTD consumption confirmation
if (isset($_POST['confirm_ttd'])) {
    $stmt = $pdo->prepare("INSERT INTO konsumsi_ttd (user_id, tanggal, status_konsumsi) VALUES (?, CURDATE(), 'sudah') ON DUPLICATE KEY UPDATE status_konsumsi = VALUES(status_konsumsi)");
    $stmt->execute([$user_id]);
    
    // Update notification log if exists
    $stmt = $pdo->prepare("UPDATE log_notifikasi SET sudah_dikonfirmasi = 1 WHERE siswa_id = ? AND DATE(tanggal_kirim) = CURDATE()");
    $stmt->execute([$user_id]);
    
    header("Location: dashboard.php?success=1");
    exit;
}

// Check if already consumed today
$stmt = $pdo->prepare("SELECT id FROM konsumsi_ttd WHERE user_id = ? AND tanggal = CURDATE() AND status_konsumsi = 'sudah'");
$stmt->execute([$user_id]);
$sudah_minum = $stmt->fetch() ? true : false;

// Hitung total kepatuhan (Gamifikasi)
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM konsumsi_ttd WHERE user_id = ? AND status_konsumsi = 'sudah'");
$stmt->execute([$user_id]);
$total_minum = $stmt->fetch()['total'];

$badge_html = '';
if ($total_minum >= 4) {
    $badge_html = '<div class="badge bg-warning text-dark px-3 py-2 fs-6 rounded-pill border border-warning shadow-sm animate-pulse-soft"><i data-lucide="medal" class="text-warning-emphasis"></i> Pahlawan Emas (4+ TTD)</div>';
} elseif ($total_minum >= 3) {
    $badge_html = '<div class="badge bg-secondary px-3 py-2 fs-6 rounded-pill border border-secondary shadow-sm"><i data-lucide="medal" class="text-light"></i> Pahlawan Perak (3 TTD)</div>';
} else {
    $badge_html = '<div class="badge bg-danger px-3 py-2 fs-6 rounded-pill border border-danger shadow-sm"><i data-lucide="shield" class="text-light"></i> Pejuang Pemula (Butuh TTD)</div>';
}

// Top 3 Leaderboard (Kelas Tersehat)
$stmt = $pdo->query("
    SELECT u.kelas, COUNT(k.id) as total_minum 
    FROM users u 
    LEFT JOIN konsumsi_ttd k ON u.id = k.user_id AND k.status_konsumsi = 'sudah' 
    WHERE u.role = 'siswa' AND u.kelas IS NOT NULL AND u.kelas != ''
    GROUP BY u.kelas 
    ORDER BY total_minum DESC 
    LIMIT 3
");
$leaderboard = $stmt->fetchAll();

// Dynamic News Feed from UKS CMS
// If no articles exist or table not created yet, fallback to static facts so UI isn't empty.
$news = [];
try {
    $stmt = $pdo->query("SELECT * FROM artikel_edukasi ORDER BY tanggal_publikasi DESC LIMIT 3");
    if ($stmt) {
        $news = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    // Table doesn't exist yet, gracefully fallback
}

if (empty($news)) {
    $fakta_list = [
        "Tahukah kamu? Remaja putri butuh zat besi lebih banyak karena mengalami menstruasi setiap bulan.",
        "Kurang darah bisa bikin konsentrasi belajar menurun drastis. Yuk minum TTD!",
        "Vitamin C membantu tubuh menyerap zat besi. Makan jeruk setelah minum TTD sangat dianjurkan.",
        "Hindari minum teh atau kopi bersamaan dengan TTD karena bisa menghambat penyerapan zat besi."
    ];
    $fallback_news = $fakta_list[array_rand($fakta_list)];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Siswa - AKRAB</title>
    <link href="/assets/vendor/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css?v=20260729" rel="stylesheet">
    <script src="/assets/vendor/lucide.min.js"></script>
</head>
<body>
<?php renderImpersonationBanner($pdo, $_SESSION); ?>

<nav class="navbar navbar-expand-lg navbar-light sticky-top">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">AKRAB Siswa</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="kuesioner.php">Kuesioner</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="konsultasi.php">Konsultasi</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-danger" href="../logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom animate-fade-in-up">
        <div>
            <h3 class="fw-bold mb-1" style="color: var(--primary-color);">Halo, <?= htmlspecialchars($_SESSION['nama']) ?>! 👋</h3>
            <p class="text-muted mb-0">Semangat menjaga kesehatan hari ini!</p>
        </div>
        <div class="d-flex flex-column align-items-end gap-2 mt-3 mt-md-0">
            <div class="d-flex flex-wrap justify-content-end gap-2 align-items-center">
                <?= $badge_html ?>
                <a href="id_card.php" class="btn btn-outline-primary btn-sm rounded-pill d-inline-flex align-items-center gap-1">
                    <i data-lucide="qr-code" style="width: 16px; height: 16px;"></i> Kartu QR
                </a>
            </div>
            
            <?php if ($total_minum >= 12): ?>
                <a href="cetak_sertifikat.php" target="_blank" class="btn btn-warning btn-sm fw-bold shadow-sm rounded-pill d-inline-flex align-items-center gap-1 animate-pulse-soft">
                    <i data-lucide="award" style="width: 16px; height: 16px;"></i> Unduh Piagam Duta Anemia
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-auto-dismiss">Terima kasih telah mencatat konsumsi TTD hari ini!</div>
    <?php endif; ?>
    <?php if (isset($_GET['haid_updated'])): ?>
        <div class="alert alert-info alert-auto-dismiss">Status siklus menstruasi berhasil diperbarui.</div>
    <?php endif; ?>
    <?php if (isset($_GET['cooldown'])): ?>
        <div class="alert alert-warning alert-auto-dismiss border-warning shadow-sm"><i data-lucide="lock" style="width: 18px;" class="me-1"></i> Anda sudah mengisi kuesioner semester ini. Kuesioner akan terbuka otomatis pada jadwal pengecekan kesehatan berikutnya.</div>
    <?php endif; ?>
    <?php if (isset($_GET['questionnaire_saved'])): ?>
        <div class="alert alert-success alert-auto-dismiss" role="status">Jawaban kuesioner berhasil disimpan. Hasil risiko belum tersedia sampai model selesai divalidasi klinis.</div>
    <?php endif; ?>

    <div class="row g-4 animate-fade-in-up delay-100">
        <!-- TTD Status -->
        <div class="col-md-6">
            <div class="card h-100 p-4 border-0 shadow-sm rounded-4">
                <div class="d-flex align-items-center">
                    <?php if ($sudah_minum): ?>
                    <div class="me-3"><i data-lucide="check-circle" class="text-success" style="width: 48px; height: 48px;"></i></div>
                    <div>
                        <h5 class="mb-1 text-dark">Status TTD Hari Ini</h5>
                        <p class="mb-0 text-success fw-bold">Hebat! Kamu sudah minum TTD hari ini.</p>
                    </div>
                    <?php else: ?>
                    <div class="me-3"><i data-lucide="pill" class="text-danger" style="width: 48px; height: 48px;"></i></div>
                    <div>
                        <h5 class="mb-1 text-dark">Sudah Minum TTD?</h5>
                        <?php if ($sedang_haid): ?>
                            <p class="mb-2 text-danger fw-bold"><i data-lucide="alert-circle" style="width: 16px; height: 16px;"></i> Kamu sedang masa haid. WAJIB minum 1 TTD setiap hari!</p>
                        <?php else: ?>
                            <p class="mb-2 text-muted">Jangan lupa minum Tablet Tambah Darah (TTD) hari ini.</p>
                        <?php endif; ?>
                        <div class="d-flex gap-2 flex-wrap mt-2">
                            <form method="POST">
                                <?= csrfInput() ?>
                                <button type="submit" name="confirm_ttd" class="btn btn-primary shadow-sm">Saya Sudah Minum</button>
                            </form>
                            <a href="export_calendar.php" class="btn btn-outline-dark shadow-sm d-flex align-items-center gap-1">
                                <i data-lucide="calendar-clock" style="width: 18px; height: 18px;"></i> Set Alarm HP
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Menstrual Tracker Toggle -->
        <div class="col-md-6">
            <div class="card p-4 shadow-sm border-0 h-100 bg-white">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <i data-lucide="calendar-heart" class="<?= $sedang_haid ? 'text-danger' : 'text-secondary' ?>" style="width: 48px; height: 48px;"></i>
                    </div>
                    <div>
                        <h5 class="mb-1 text-dark">Pelacak Siklus Haid</h5>
                        <p class="mb-2 text-muted">Tandai jika kamu sedang haid agar pengingat TTD disesuaikan.</p>
                        <form method="POST">
                            <?= csrfInput() ?>
                            <button type="submit" name="toggle_haid" class="btn <?= $sedang_haid ? 'btn-danger' : 'btn-outline-danger' ?> mt-2 shadow-sm">
                                <?= $sedang_haid ? 'Haid Selesai' : 'Saya Sedang Haid Hari Ini' ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Risk Status -->
        <div class="col-md-6">
            <div class="card h-100 p-4">
                <h5 class="card-title text-muted mb-3">Status Risiko Anemia</h5>
                <?php if ($hasil_deteksi): ?>
                    <?php 
                        $risk_class = 'risk-low';
                        $risk_label = 'Rendah';
                        if ($hasil_deteksi['kategori_risiko'] == 'sedang') { $risk_class = 'risk-medium'; $risk_label = 'Sedang'; }
                        if ($hasil_deteksi['kategori_risiko'] == 'tinggi') { $risk_class = 'risk-high'; $risk_label = 'Tinggi'; }
                    ?>
                    <div class="text-center my-4">
                        <span class="risk-badge <?= $risk_class ?> fs-4">Risiko <?= $risk_label ?></span>
                    </div>
                    <p class="text-center mb-4">Terakhir dicek pada: <?= date('d M Y', strtotime($hasil_deteksi['tanggal'])) ?></p>
                    <div class="text-center d-flex flex-column gap-2 align-items-center">
                        <a href="hasil_deteksi.php" class="btn btn-outline-primary w-75">Lihat Detail Saran</a>
                        <?php if ($can_fill_kuesioner): ?>
                            <a href="kuesioner.php" class="btn btn-primary w-75">Perbarui Hasil Kesehatan</a>
                        <?php else: ?>
                            <button class="btn btn-secondary w-75 opacity-75" disabled><i data-lucide="lock" style="width: 16px;"></i> Kuesioner Terkunci</button>
                            <small class="text-muted mt-1"><i data-lucide="calendar" style="width: 12px;"></i> Dapat diisi kembali pada: <strong><?= $next_kuesioner_date ?></strong></small>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center my-4">
                        <?php if ($kuesioner): ?>
                            <p class="text-muted">Kuesioner sudah tersimpan. Lengkapi empat nilai laboratorium untuk menjalankan simulasi regresi logistik.</p>
                            <a href="data_laboratorium.php" class="btn btn-primary">Lengkapi Data Laboratorium</a>
                        <?php else: ?>
                            <p class="text-muted">Belum ada data. Silakan isi kuesioner terlebih dahulu.</p>
                            <a href="kuesioner.php" class="btn btn-primary">Isi Kuesioner Sekarang</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Leaderboard & News Feed Row -->
    <div class="row mt-4 g-4 animate-fade-in-up delay-200">
        
        <!-- Papan Peringkat Kelas -->
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100 bg-light">
                <div class="card-body p-4">
                    <h5 class="text-primary border-bottom pb-2 mb-3 d-flex align-items-center gap-2">
                        <i data-lucide="trophy" class="text-warning"></i> Papan Peringkat Kelas (TTD)
                    </h5>
                    <?php if (empty($leaderboard)): ?>
                        <p class="text-muted mb-0">Belum ada data kepatuhan yang cukup.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush bg-transparent">
                            <?php foreach ($leaderboard as $index => $lb): ?>
                                <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center px-0">
                                    <div>
                                        <span class="badge bg-<?= $index === 0 ? 'warning text-dark' : ($index === 1 ? 'secondary' : 'danger') ?> rounded-circle me-2 p-2">#<?= $index + 1 ?></span>
                                        <span class="fw-bold fs-5"><?= htmlspecialchars($lb['kelas']) ?></span>
                                    </div>
                                    <span class="badge bg-primary rounded-pill"><?= $lb['total_minum'] ?> TTD Diminum</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Portal Berita Kesehatan -->
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100 bg-light">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                        <h5 class="text-primary mb-0 d-flex align-items-center gap-2">
                            <i data-lucide="newspaper" class="text-primary"></i> Berita Kesehatan UKS
                        </h5>
                        <a href="edukasi.php" class="btn btn-outline-primary btn-sm rounded-pill">Pusat Edukasi</a>
                    </div>
                    
                    <?php if (!empty($news)): ?>
                        <?php foreach ($news as $n): ?>
                            <div class="mb-3 border-bottom pb-2">
                                <a href="baca_artikel.php?id=<?= $n['id'] ?>" class="text-decoration-none">
                                    <h6 class="fw-bold mb-1 text-primary hover-opacity"><?= htmlspecialchars($n['judul']) ?></h6>
                                </a>
                                <p class="text-muted small mb-1"><?= htmlspecialchars(strlen($n['konten']) > 80 ? substr($n['konten'],0,80)."..." : $n['konten']) ?></p>
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <small class="text-secondary opacity-75"><?= date('d M Y', strtotime($n['tanggal_publikasi'])) ?></small>
                                    <a href="baca_artikel.php?id=<?= $n['id'] ?>" class="small text-primary fw-bold text-decoration-none">Baca Selengkapnya &rarr;</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="d-flex align-items-start gap-3">
                            <div class="mt-1"><i data-lucide="lightbulb" class="text-warning" style="width: 32px; height: 32px;"></i></div>
                            <div>
                                <h6 class="text-dark fw-bold mb-1">Fakta Anemia Hari Ini</h6>
                                <p class="mb-0 text-muted"><?= htmlspecialchars($fallback_news) ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
    </div>
</div>

<script src="/assets/vendor/bootstrap.bundle.min.js"></script>
<script src="/assets/vendor/lucide.min.js"></script>
<script src="../assets/js/app-init.js?v=20260729"></script>
<script src="../assets/js/chatbot.js?v=20260729"></script>
<script>
  lucide.createIcons();
</script>
<script src="../assets/js/main.js?v=20260729"></script>
</body>
</html>
