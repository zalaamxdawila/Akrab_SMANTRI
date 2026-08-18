<?php
require_once '../config.php';
require_once '../helpers.php';

check_role('uks');

/* Dashboard read model is kept out of the page so query changes are testable. */
$dashboardRepository = new DashboardRepository($pdo);
$summary = $dashboardRepository->uksSummary();
$total_siswa = $summary['total_students'];

// 2. High Risk Students
$risiko_tinggi = $summary['high_risk'];

// 2b. Risk Distribution (Tinggi, Sedang, Rendah)
$risk_distribution = $summary['risk_distribution'];

// 3. Unanswered Consultations
$konsultasi_menunggu = $summary['pending_consultations'];

// 4. Data for Chart.js (TTD Compliance last 7 days)
$compliance = $dashboardRepository->ttdComplianceLastSevenDays($total_siswa);
$chart_labels = $compliance['labels'];
$data_patuh = $compliance['compliant'];
$data_tidak_patuh = $compliance['non_compliant'];

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard UKS - AKRAB</title>
    <link href="/assets/vendor/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css?v=20260818" rel="stylesheet">
    <!-- Chart.js -->
    <script src="/assets/vendor/chart.umd.min.js"></script>
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
                <li class="nav-item"><a class="nav-link text-white active fw-bold" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="hasil_kuesioner.php">Hasil Kuesioner</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="data_siswa.php">Data Siswa</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="kelola_tautan.php">Verifikasi Wali</a></li>
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
            <a href="hasil_kuesioner.php" class="text-decoration-none">
                <div class="card bg-success text-white text-center p-3 h-100 shadow-sm card-hover">
                    <i data-lucide="chart-no-axes-combined" class="mx-auto mb-2" style="width: 32px; height: 32px;"></i>
                    <h5 class="mb-0">Hasil Kuesioner</h5>
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

<script src="/assets/vendor/bootstrap.bundle.min.js"></script>
<script src="/assets/vendor/lucide.min.js"></script>
<script src="../assets/js/app-init.js?v=20260818"></script>
<script src="../assets/js/main.js?v=20260818"></script>
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
