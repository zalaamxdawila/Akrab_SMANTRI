<?php
require_once '../config.php';
require_once '../helpers.php';

check_role('uks');

// Statistics for Dashboard
// 1. Total Students
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'siswa'");
$total_siswa = $stmt->fetch()['total'];

// 2. High Risk Students
$stmt = $pdo->query("SELECT COUNT(DISTINCT user_id) as total FROM hasil_deteksi WHERE kategori_risiko = 'tinggi' AND tanggal = (SELECT MAX(tanggal) FROM hasil_deteksi h2 WHERE h2.user_id = hasil_deteksi.user_id)");
$risiko_tinggi = $stmt->fetch()['total'];

// 2b. Risk Distribution (Tinggi, Sedang, Rendah)
$stmt = $pdo->query("
    SELECT kategori_risiko, COUNT(DISTINCT user_id) as total 
    FROM hasil_deteksi 
    WHERE (user_id, tanggal) IN (SELECT user_id, MAX(tanggal) FROM hasil_deteksi GROUP BY user_id)
    GROUP BY kategori_risiko
");
$risk_distribution = ['tinggi' => 0, 'sedang' => 0, 'rendah' => 0];
while ($row = $stmt->fetch()) {
    $risk_distribution[$row['kategori_risiko']] = (int)$row['total'];
}

// 3. Unanswered Consultations
$stmt = $pdo->query("SELECT COUNT(*) as total FROM konsultasi WHERE status = 'menunggu'");
$konsultasi_menunggu = $stmt->fetch()['total'];

// 4. Data for Chart.js (TTD Compliance last 7 days)
$chart_labels = [];
$data_patuh = [];
$data_tidak_patuh = [];

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('d M', strtotime($date));
    
    // Count 'sudah'
    $stmt = $pdo->prepare("SELECT COUNT(*) as patuh FROM konsumsi_ttd WHERE tanggal = ? AND status_konsumsi = 'sudah'");
    $stmt->execute([$date]);
    $patuh = $stmt->fetch()['patuh'];
    $data_patuh[] = $patuh;
    
    // Count 'tidak patuh' (Total siswa - patuh)
    $tidak_patuh = max(0, $total_siswa - $patuh);
    $data_tidak_patuh[] = $tidak_patuh;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard UKS - AKRAB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css?v=<?= time() ?>" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
    <div class="container">
        <a class="navbar-brand text-white fw-bold" href="dashboard.php">AKRAB UKS Panel</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-toggle="target" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link text-white active fw-bold" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="data_siswa.php">Data Siswa</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="kelola_artikel.php">Berita</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="edukasi.php">SOP Penanganan</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="jawab_konsultasi.php">Konsultasi</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-4">
    <h3 class="mb-4">Dashboard Monitoring UKS</h3>
    
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card p-3 shadow-sm border-start border-primary border-5 h-100">
                <h5 class="text-muted">Total Siswa Terdaftar</h5>
                <h2 class="display-5 text-primary mb-0"><?= $total_siswa ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3 shadow-sm border-start border-danger border-5 h-100">
                <h5 class="text-muted">Risiko Anemia Tinggi</h5>
                <h2 class="display-5 text-danger mb-0"><?= $risiko_tinggi ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3 shadow-sm border-start border-warning border-5 h-100">
                <h5 class="text-muted">Konsultasi Menunggu</h5>
                <h2 class="display-5 text-warning mb-0"><?= $konsultasi_menunggu ?></h2>
            </div>
        </div>
    </div>
    
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <a href="data_siswa.php" class="text-decoration-none">
                <div class="card bg-success text-white text-center p-3 h-100 shadow-sm card-hover">
                    <i data-lucide="users" class="mx-auto mb-2" style="width: 32px; height: 32px;"></i>
                    <h5 class="mb-0">Data Siswa</h5>
                </div>
            </a>
        </div>
        <!-- Scan QR Card -->
        <div class="col-md-3">
            <a href="scan_qr.php" class="text-decoration-none">
                <div class="card text-white text-center p-3 h-100 shadow-sm card-hover" style="background: linear-gradient(45deg, #0d6efd, #6610f2);">
                    <i data-lucide="scan-line" class="mx-auto mb-2" style="width: 32px; height: 32px;"></i>
                    <h5 class="mb-0">Scan QR Siswa</h5>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="kelola_artikel.php" class="text-decoration-none">
                <div class="card bg-info text-white text-center p-3 h-100 shadow-sm card-hover">
                    <i data-lucide="newspaper" class="mx-auto mb-2" style="width: 32px; height: 32px;"></i>
                    <h5 class="mb-0">Kelola Berita</h5>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="edukasi.php" class="text-decoration-none">
                <div class="card bg-secondary text-white text-center p-3 h-100 shadow-sm card-hover">
                    <i data-lucide="book-open" class="mx-auto mb-2" style="width: 32px; height: 32px;"></i>
                    <h5 class="mb-0">SOP UKS</h5>
                </div>
            </a>
        </div>
    </div>
    
    <!-- Command Center Analytics -->
    <div class="row mb-3">
        <div class="col-12 text-end d-flex justify-content-end gap-2">
            <a href="export_csv.php" class="btn btn-success fw-bold shadow-sm d-inline-flex align-items-center gap-2">
                <i data-lucide="file-spreadsheet"></i> Export ke Excel (.csv)
            </a>
            <a href="cetak_laporan_eksekutif.php" target="_blank" class="btn btn-warning fw-bold shadow-sm d-inline-flex align-items-center gap-2">
                <i data-lucide="printer"></i> Cetak Laporan Kepala Sekolah (PDF)
            </a>
        </div>
    </div>
    
    <div class="row">
        <!-- TTD Chart -->
        <div class="col-md-8 mb-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title text-primary"><i data-lucide="bar-chart-2" style="width: 20px; height: 20px;"></i> Grafik Kepatuhan Minum TTD (7 Hari Terakhir)</h5>
                    <canvas id="ttdChart" height="100"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Risk Pie Chart -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center d-flex flex-column justify-content-center">
                    <h5 class="card-title text-danger mb-4"><i data-lucide="pie-chart" style="width: 20px; height: 20px;"></i> Peta Risiko Siswa</h5>
                    <div style="max-height: 250px; margin: 0 auto;">
                        <canvas id="riskPieChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/lucide@latest"></script>
<script src="../assets/js/app-init.js"></script>
<script src="../assets/js/main.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    lucide.createIcons();
    
    // TTD Bar Chart
    const ctx = document.getElementById('ttdChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [
                {
                    label: 'Sudah Minum (Patuh)',
                    data: <?= json_encode($data_patuh) ?>,
                    backgroundColor: 'rgba(25, 135, 84, 0.7)',
                    borderColor: 'rgba(25, 135, 84, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Belum Minum',
                    data: <?= json_encode($data_tidak_patuh) ?>,
                    backgroundColor: 'rgba(220, 53, 69, 0.7)',
                    borderColor: 'rgba(220, 53, 69, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true, stacked: true },
                x: { stacked: true }
            }
        }
    });

    // Risk Pie Chart
    const ctxPie = document.getElementById('riskPieChart').getContext('2d');
    new Chart(ctxPie, {
        type: 'doughnut',
        data: {
            labels: ['Risiko Tinggi', 'Risiko Sedang', 'Risiko Rendah'],
            datasets: [{
                data: [
                    <?= $risk_distribution['tinggi'] ?>, 
                    <?= $risk_distribution['sedang'] ?>, 
                    <?= $risk_distribution['rendah'] ?>
                ],
                backgroundColor: [
                    'rgba(220, 53, 69, 0.8)',  // Danger
                    'rgba(255, 193, 7, 0.8)',  // Warning
                    'rgba(25, 135, 84, 0.8)'   // Success
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
});
</script>
</body>
</html>
