<?php
require_once '../config.php';
require_once '../helpers.php';

check_role('siswa');
$user_id = $_SESSION['user_id'];

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// The QR code will contain only the Student's NISN (Username) so it can be easily searched
$qr_data = $user['username'];

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu ID Digital - AKRAB</title>
    <link href="/assets/vendor/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css?v=20260818" rel="stylesheet">
    <script src="/assets/vendor/lucide.min.js"></script>
    <style>
        .id-card {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            border-radius: 20px;
            color: white;
            box-shadow: 0 10px 30px rgba(13, 110, 253, 0.3);
            overflow: hidden;
            position: relative;
        }
        .id-card::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 150px;
            height: 150px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }
        .id-card::after {
            content: '';
            position: absolute;
            bottom: -30px;
            left: -30px;
            width: 100px;
            height: 100px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }
        .qr-box {
            background: white;
            padding: 15px;
            border-radius: 15px;
            display: inline-block;
        }
        .qr-box img {
            display: block;
        }
        .user-avatar {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            border: 2px solid rgba(255,255,255,0.5);
        }
        @media print {
            body { background-color: white !important; }
            nav, .btn, .d-flex.justify-content-between.mb-4 { display: none !important; }
            .container { padding: 0 !important; margin: 0 !important; }
            .col-md-5 { width: 100% !important; max-width: 400px !important; margin: 0 auto !important; float: none !important; }
            .id-card {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                background: #0d6efd !important; /* Fallback for browsers not supporting gradient in print */
                background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%) !important;
                color: white !important;
                box-shadow: none !important;
            }
            .id-card h4, .id-card p, .id-card i {
                color: white !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .id-card::before, .id-card::after, .user-avatar {
                background: rgba(255,255,255,0.2) !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body class="bg-light">
<?php renderImpersonationBanner($pdo, $_SESSION); ?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
    <div class="container">
        <a class="navbar-brand text-white fw-bold d-flex align-items-center gap-2" href="dashboard.php">
            <i data-lucide="activity"></i> AKRAB Siswa
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link text-white" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="profil.php">Profil</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="mb-0">Kartu ID Digital</h3>
                <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">Kembali</a>
            </div>

            <div class="id-card p-4 text-center mb-4">
                <div class="user-avatar">
                    <i data-lucide="user" style="width: 40px; height: 40px; color: white;"></i>
                </div>
                <h4 class="fw-bold mb-0"><?= htmlspecialchars($user['nama']) ?></h4>
                <p class="mb-3 text-white-50">Kelas: <?= htmlspecialchars($user['kelas']) ?: 'Tidak Ada' ?> | ID: <?= htmlspecialchars($user['username']) ?></p>
                
                <div class="qr-box mb-3 shadow-sm d-flex justify-content-center align-items-center" style="min-height: 200px; min-width: 200px;">
                    <div id="qrcode"></div>
                </div>
                
                <p class="small mb-0 opacity-75">Tunjukkan QR Code ini ke petugas UKS untuk pemindaian medis instan.</p>
            </div>

            <div class="d-grid gap-2">
                <button onclick="window.print()" class="btn btn-primary d-flex align-items-center justify-content-center gap-2 py-3 fw-bold">
                    <i data-lucide="printer"></i> Cetak / Simpan PDF
                </button>
            </div>
        </div>
    </div>
</div>

<script src="/assets/vendor/bootstrap.bundle.min.js"></script>
<script src="/assets/vendor/qrcode.min.js"></script>
<script src="../assets/js/app-init.js?v=20260818"></script>
<script>
    // Generate QR Code locally to avoid AdBlocker/Network issues
    new QRCode(document.getElementById("qrcode"), {
        text: "<?= addslashes($qr_data) ?>",
        width: 180,
        height: 180,
        colorDark : "#000000",
        colorLight : "#ffffff",
        correctLevel : QRCode.CorrectLevel.H
    });
</script>
</body>
</html>
