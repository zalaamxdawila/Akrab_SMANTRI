<?php
require_once '../config.php';
require_once '../helpers.php';

check_role('uks');

// 1. Total Students
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'siswa'");
$total_siswa = $stmt->fetch()['total'];

// 2. Risk Distribution
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

// 3. Class Leaderboard
$stmt = $pdo->query("
    SELECT u.kelas, COUNT(k.id) as total_minum 
    FROM users u 
    LEFT JOIN konsumsi_ttd k ON u.id = k.user_id AND k.status_konsumsi = 'sudah' 
    WHERE u.role = 'siswa' AND u.kelas IS NOT NULL AND u.kelas != ''
    GROUP BY u.kelas 
    ORDER BY total_minum DESC 
");
$leaderboard = $stmt->fetchAll();

$tanggal_cetak = date('d F Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Eksekutif - AKRAB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #fff; color: #000; font-family: 'Times New Roman', Times, serif; }
        .kop-surat { border-bottom: 3px solid #000; padding-bottom: 15px; margin-bottom: 25px; }
        .logo-placeholder { width: 80px; height: 80px; background: #eee; border: 1px solid #ccc; display: flex; align-items: center; justify-content: center; font-weight: bold; border-radius: 50%; }
        .box-stat { border: 1px solid #000; padding: 15px; text-align: center; margin-bottom: 20px; }
        .box-stat h1 { font-size: 2.5rem; margin: 0; }
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; padding: 20px; }
            .box-stat { border: 1px solid #000 !important; }
        }
        .signature-box { margin-top: 50px; }
    </style>
</head>
<body>

<div class="container py-4">
    <div class="text-end mb-4 no-print">
        <button onclick="window.print()" class="btn btn-primary px-4 py-2 fw-bold">🖨️ Cetak PDF</button>
        <a href="dashboard.php" class="btn btn-secondary px-4 py-2 ms-2">Kembali</a>
    </div>

    <div class="kop-surat d-flex align-items-center">
        <div class="me-4"><img src="../assets/img/logo.png" style="width: 80px; height: 80px; object-fit: contain;"></div>
        <div class="text-center flex-grow-1">
            <h4 class="mb-1 fw-bold">PEMERINTAH PROVINSI PENDIDIKAN</h4>
            <h3 class="mb-1 fw-bold">SMA NEGERI 1 NUSANTARA</h3>
            <p class="mb-0">Jl. Pendidikan No. 123, Kota Cerdas, Telp. (021) 1234567<br>Website: sman1nusantara.sch.id | Email: info@sman1nusantara.sch.id</p>
        </div>
    </div>

    <div class="text-center mb-5">
        <h5 class="fw-bold text-decoration-underline mb-0">LAPORAN EKSEKUTIF STATUS KESEHATAN SISWA (ANEMIA)</h5>
        <p>Periode: <?= date('F Y') ?></p>
    </div>

    <div class="row mb-4">
        <div class="col-4">
            <div class="box-stat">
                <p class="mb-1 fw-bold">Total Populasi Siswa Terdaftar</p>
                <h1><?= $total_siswa ?></h1>
            </div>
        </div>
        <div class="col-4">
            <div class="box-stat">
                <p class="mb-1 fw-bold">Siswa Berisiko TINGGI</p>
                <h1><?= $risk_distribution['tinggi'] ?></h1>
            </div>
        </div>
        <div class="col-4">
            <div class="box-stat">
                <p class="mb-1 fw-bold">Siswa Berisiko Sedang</p>
                <h1><?= $risk_distribution['sedang'] ?></h1>
            </div>
        </div>
    </div>

    <h6 class="fw-bold mt-4">A. Peringkat Kepatuhan Minum TTD (Tablet Tambah Darah) per Kelas</h6>
    <table class="table table-bordered border-dark table-sm mt-2">
        <thead>
            <tr>
                <th class="text-center" style="width: 50px;">Peringkat</th>
                <th>Nama Kelas</th>
                <th class="text-center">Total Distribusi/Minum TTD</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($leaderboard)): ?>
            <tr><td colspan="3" class="text-center">Belum ada data kepatuhan tercatat.</td></tr>
            <?php else: ?>
                <?php foreach ($leaderboard as $index => $lb): ?>
                <tr>
                    <td class="text-center"><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($lb['kelas']) ?></td>
                    <td class="text-center"><?= $lb['total_minum'] ?> Tablet</td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <h6 class="fw-bold mt-4">B. Kesimpulan & Rekomendasi</h6>
    <p class="text-justify">
        Berdasarkan hasil pemindaian (Screening) Sistem AKRAB, terdapat <strong><?= $risk_distribution['tinggi'] ?> siswa</strong> yang terindikasi memiliki risiko tinggi terhadap Anemia. Unit Kesehatan Sekolah (UKS) telah mencetak surat rujukan ke Puskesmas terkait untuk siswa-siswa tersebut. Kami merekomendasikan agar pihak sekolah terus mendukung kampanye minum Tablet Tambah Darah (TTD) massal setiap minggu, khususnya untuk kelas dengan tingkat kepatuhan terendah.
    </p>

    <div class="row signature-box">
        <div class="col-6 text-center">
            <br>
            <p class="mb-5">Mengetahui,<br>Kepala Sekolah</p>
            <p><strong>_______________________</strong><br>NIP. </p>
        </div>
        <div class="col-6 text-center">
            <p class="mb-5">Kota Cerdas, <?= $tanggal_cetak ?><br>Koordinator UKS</p>
            <p><strong><?= htmlspecialchars($_SESSION['nama']) ?></strong><br>NIP. ___________________</p>
        </div>
    </div>
</div>

</body>
</html>
