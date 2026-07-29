<?php
require_once '../config.php';
require_once '../helpers.php';

check_role('siswa');
$user_id = $_SESSION['user_id'];
$nama = $_SESSION['nama'];

// Periksa kelayakan: 12 hari unik dalam 90 hari terakhir.
$stmt = $pdo->prepare(
    "SELECT COUNT(DISTINCT tanggal)
     FROM konsumsi_ttd
     WHERE user_id = ? AND status_konsumsi = 'sudah'
       AND tanggal >= DATE_SUB(CURDATE(), INTERVAL " . AKRAB_CERTIFICATE_WINDOW_DAYS . " DAY)"
);
$stmt->execute([$user_id]);
$total_minum = (int) $stmt->fetchColumn();

if (!isCertificateEligible($total_minum)) {
    echo '<!DOCTYPE html><html lang="id"><head><title>Akses Ditolak - AKRAB</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;"><main class="card shadow-sm border-0 p-5 text-center" style="max-width: 500px;"><h1 class="h3 fw-bold text-dark mb-3">Belum Memenuhi Syarat</h1><p class="text-muted mb-4">Diperlukan minimal 12 hari unik konsumsi TTD dalam 90 hari terakhir (Saat ini: '.$total_minum.' hari).</p><a href="dashboard.php" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">Kembali ke Dasbor</a></main></body></html>';
    exit;
}

$tanggal_cetak = date('d F Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Sertifikat Duta Anemia - <?= htmlspecialchars($nama) ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,700&family=Montserrat:wght@400;600&display=swap');
        
        body {
            margin: 0;
            padding: 0;
            background: #e9ecef;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .certificate {
            width: 800px;
            height: 565px;
            background: white;
            padding: 40px;
            box-sizing: border-box;
            position: relative;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            border: 15px solid #0d6efd;
            outline: 5px solid white;
            outline-offset: -10px;
        }

        .certificate::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 20px;
            right: 20px;
            bottom: 20px;
            border: 2px dashed #0d6efd;
            opacity: 0.5;
            pointer-events: none;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .title {
            font-family: 'Playfair Display', serif;
            font-size: 42px;
            color: #0d6efd;
            margin: 0;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        
        .subtitle {
            font-family: 'Montserrat', sans-serif;
            font-size: 16px;
            color: #6c757d;
            margin-top: 5px;
            letter-spacing: 5px;
            text-transform: uppercase;
        }
        
        .content {
            text-align: center;
            font-family: 'Montserrat', sans-serif;
        }
        
        .presented-to {
            font-size: 18px;
            color: #495057;
            margin: 30px 0 10px;
        }
        
        .name {
            font-family: 'Playfair Display', serif;
            font-size: 48px;
            font-weight: 700;
            color: #212529;
            margin: 0;
            border-bottom: 2px solid #0d6efd;
            display: inline-block;
            padding: 0 40px 10px;
        }
        
        .reason {
            font-size: 16px;
            color: #495057;
            margin-top: 20px;
            line-height: 1.6;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .footer {
            position: absolute;
            bottom: 50px;
            width: calc(100% - 80px);
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            font-family: 'Montserrat', sans-serif;
        }
        
        .signature {
            text-align: center;
            width: 200px;
        }
        
        .signature-line {
            border-bottom: 1px solid #212529;
            margin-bottom: 10px;
            height: 40px;
        }
        
        .badge-seal {
            width: 100px;
            height: 100px;
            background: #ffc107;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Playfair Display', serif;
            font-weight: bold;
            color: white;
            font-size: 14px;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            border: 4px dashed white;
            outline: 2px solid #ffc107;
        }
        
        @media print {
            body { background: white; }
            .certificate { box-shadow: none; }
        }
    </style>
</head>
<body onload="window.print()">
<?php renderImpersonationBanner($pdo, $_SESSION); ?>

<div class="certificate">
    <div class="header">
        <h1 class="title">Sertifikat Penghargaan</h1>
        <div class="subtitle">Duta Kesehatan Remaja Bebas Anemia</div>
    </div>
    
    <div class="content">
        <div class="presented-to">Dengan bangga diberikan kepada:</div>
        <h2 class="name"><?= htmlspecialchars($nama) ?></h2>
        
        <div class="reason">
            Atas dedikasi, kedisiplinan, dan kepedulian yang luar biasa dalam menjaga kesehatan diri melalui 
            <strong>Kepatuhan Konsumsi Tablet Tambah Darah (TTD) Paripurna</strong>. 
            Anda adalah inspirasi bagi remaja putri Indonesia!
        </div>
    </div>
    
    <div class="footer">
        <div class="signature">
            <div class="signature-line"></div>
            <strong>Kepala Sekolah</strong>
            <div style="font-size: 12px; color: #6c757d;">Mengetahui</div>
        </div>
        
        <div class="badge-seal">
            GOLD<br>AWARD
        </div>
        
        <div class="signature">
            <div class="signature-line"></div>
            <strong>Pembina UKS</strong>
            <div style="font-size: 12px; color: #6c757d;">Diterbitkan: <?= $tanggal_cetak ?></div>
        </div>
    </div>
</div>

</body>
</html>
