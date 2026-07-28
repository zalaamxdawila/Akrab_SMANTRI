<?php
require_once '../config.php';
require_once '../helpers.php';

check_role('uks');

if (!isset($_GET['id'])) {
    die("ID Siswa tidak ditemukan.");
}

$siswa_id = (int) $_GET['id'];

// Get Data Siswa
$stmt = $pdo->prepare("SELECT u.*, h.kategori_risiko, h.probabilitas_risiko, h.tanggal as tgl_deteksi, k.skor_gejala, k.skor_makan 
                       FROM users u 
                       LEFT JOIN hasil_deteksi h ON u.id = h.user_id 
                       LEFT JOIN kuesioner k ON u.id = k.user_id
                       WHERE u.id = ? AND u.role = 'siswa' ORDER BY h.tanggal DESC, h.id DESC, k.created_at DESC, k.id DESC LIMIT 1");
$stmt->execute([$siswa_id]);
$data = $stmt->fetch();

if (!$data) {
    die("Data tidak ditemukan.");
}

$tanggal_sekarang = date('d F Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Rujukan - <?= htmlspecialchars($data['nama']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #fff; color: #000; font-family: 'Times New Roman', Times, serif; }
        .kop-surat { border-bottom: 3px solid #000; padding-bottom: 15px; margin-bottom: 25px; }
        .logo-placeholder { width: 80px; height: 80px; background: #eee; border: 1px solid #ccc; display: flex; align-items: center; justify-content: center; font-weight: bold; border-radius: 50%; }
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; padding: 20px; }
        }
        .signature-box { margin-top: 50px; }
    </style>
</head>
<body>

<div class="container py-4">
    <div class="text-end mb-4 no-print">
        <button onclick="window.print()" class="btn btn-primary px-4 py-2 fw-bold">🖨️ Cetak Surat Rujukan</button>
        <a href="data_siswa.php" class="btn btn-secondary px-4 py-2 ms-2">Kembali</a>
    </div>

    <div class="kop-surat d-flex align-items-center">
        <div class="me-4"><img src="../assets/img/logo.png" style="width: 80px; height: 80px; object-fit: contain;"></div>
        <div class="text-center flex-grow-1">
            <h4 class="mb-1 fw-bold">PEMERINTAH PROVINSI PENDIDIKAN</h4>
            <h3 class="mb-1 fw-bold">SMA NEGERI 1 NUSANTARA</h3>
            <p class="mb-0">Jl. Pendidikan No. 123, Kota Cerdas, Telp. (021) 1234567<br>Website: sman1nusantara.sch.id | Email: info@sman1nusantara.sch.id</p>
        </div>
    </div>

    <div class="text-center mb-4">
        <h5 class="fw-bold text-decoration-underline mb-0">SURAT RUJUKAN PEMERIKSAAN KESEHATAN</h5>
        <p>Nomor: 440 / UKS / <?= date('Y') ?> / <?= date('m') ?></p>
    </div>

    <p>Yth. Kepala Puskesmas / Dokter Pemeriksa<br>di Tempat</p>
    <p>Dengan hormat,<br>Bersama surat ini, kami dari Unit Kesehatan Sekolah (UKS) SMA Negeri 1 Nusantara merujuk siswa/i berikut:</p>

    <table class="table table-borderless table-sm mb-4 ms-3" style="width: auto;">
        <tr><td style="width: 150px;">Nama Lengkap</td><td>: <strong><?= htmlspecialchars($data['nama']) ?></strong></td></tr>
        <tr><td>Kelas</td><td>: <?= htmlspecialchars($data['kelas']) ?></td></tr>
        <tr><td>Nomor Induk / ID</td><td>: <?= htmlspecialchars($data['username']) ?></td></tr>
    </table>

    <p>Berdasarkan hasil *Screening* Mandiri Sistem Deteksi Dini Anemia (AKRAB) yang dilakukan pada tanggal <strong><?= $data['tgl_deteksi'] ?></strong>, siswa tersebut terindikasi memiliki risiko <strong>TINGGI</strong> terkena Anemia, dengan rincian temuan sebagai berikut:</p>

    <div class="card border-dark mb-4">
        <div class="card-body">
            <ul>
                <li><strong>Kategori Risiko Klinis:</strong> <?= strtoupper($data['kategori_risiko']) ?> (Probabilitas: <?= number_format($data['probabilitas_risiko'] * 100, 1) ?>%)</li>
                <li><strong>Gejala 5L (Lemah, Letih, Lesu, Lelah, Lalai):</strong> Dikeluhkan (Skor <?= $data['skor_gejala'] ?>/10)</li>
                <li><strong>Pola Makan:</strong> <?= $data['skor_makan'] < 5 ? 'Kurang asupan gizi zat besi' : 'Cukup' ?></li>
            </ul>
        </div>
    </div>

    <p>Mengingat hal tersebut berdampak pada konsentrasi dan prestasi belajar siswa, kami memohon bantuan Bapak/Ibu Dokter untuk dapat melakukan pemeriksaan lanjutan (Cek Laboratorium Hemoglobin) serta penanganan medis yang diperlukan.</p>

    <p>Demikian surat rujukan ini dibuat untuk dipergunakan sebagaimana mestinya. Atas perhatian dan kerjasamanya kami ucapkan terima kasih.</p>

    <div class="row signature-box">
        <div class="col-6 text-center">
            <br>
            <p class="mb-5">Mengetahui,<br>Orang Tua / Wali Murid</p>
            <p>_______________________</p>
        </div>
        <div class="col-6 text-center">
            <p class="mb-5">Kota Cerdas, <?= $tanggal_sekarang ?><br>Petugas UKS / Guru Penjaskes</p>
            <p><strong><?= htmlspecialchars($_SESSION['nama']) ?></strong><br>NIP. ___________________</p>
        </div>
    </div>
</div>

<script>
    // Auto-trigger print dialog when accessed with ?print=1
    if (window.location.search.includes('print=1')) {
        setTimeout(() => { window.print(); }, 500);
    }
</script>
</body>
</html>
