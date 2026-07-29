<?php
require_once '../config.php';
require_once '../helpers.php';
check_role('uks');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pindai QR Siswa - AKRAB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js"></script>
</head>
<body class="bg-light">
<?php renderImpersonationBanner($pdo, $_SESSION); ?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand text-white fw-bold" href="dashboard.php">
            <i data-lucide="arrow-left" style="width: 20px; height: 20px;" class="me-2"></i> Kembali
        </a>
    </div>
</nav>

<div class="container py-4 text-center">
    <h3 class="fw-bold mb-3">Pemindai Kartu QR</h3>
    <p class="text-muted mb-4">Arahkan kamera ke Kartu QR Siswa untuk melihat profil rekam medisnya secara instan.</p>
    
    <div class="card shadow-sm border-0 mb-4 mx-auto" style="max-width: 500px; border-radius: 20px; overflow: hidden;">
        <div class="card-body p-0">
            <div id="reader" style="width: 100%;"></div>
        </div>
    </div>
    
    <div id="result-box" class="alert alert-success d-none mx-auto" style="max-width: 500px;">
        <i data-lucide="check-circle" class="mb-2" style="width: 32px; height: 32px;"></i>
        <h5 class="fw-bold">Berhasil Dipindai!</h5>
        <p class="mb-0">Mengalihkan ke profil siswa: <span id="scanned-nisn" class="fw-bold"></span></p>
    </div>
</div>

<script>
    lucide.createIcons();
    
    document.addEventListener("DOMContentLoaded", function() {
        const html5QrcodeScanner = new Html5QrcodeScanner(
            "reader",
            { fps: 10, qrbox: {width: 250, height: 250}, aspectRatio: 1.0 },
            /* verbose= */ false
        );
        
        function onScanSuccess(decodedText, decodedResult) {
            // Hentikan pemindaian agar tidak terulang
            html5QrcodeScanner.clear();
            
            // Tampilkan pesan sukses
            document.getElementById('scanned-nisn').innerText = decodedText;
            document.getElementById('result-box').classList.remove('d-none');
            
            // Redirect ke halaman detail atau pencarian
            setTimeout(() => {
                window.location.href = "data_siswa.php?search=" + encodeURIComponent(decodedText);
            }, 1000);
        }
        
        function onScanFailure(error) {
            // Abaikan error pemindaian latar belakang
        }
        
        html5QrcodeScanner.render(onScanSuccess, onScanFailure);
    });
</script>
<script src="../assets/js/app-init.js?v=20260729"></script>
</body>
</html>
