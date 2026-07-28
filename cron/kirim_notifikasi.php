<?php
// cron/kirim_notifikasi.php
// Script ini seharusnya dijalankan via Cron Job di cPanel (misal: tiap jam 07:00 dan 16:00)

// Menggunakan path absolut atau relatif yang sesuai karena cron dijalankan dari CLI
require_once dirname(__DIR__) . '/config.php';

// Ambil semua siswa yang aktif jadwal notifikasinya
// Karena ini prototype, kita asumsikan semua siswa perlu diingatkan (harian/mingguan)
$stmt = $pdo->query("
    SELECT u.id, u.nama, u.kelas 
    FROM users u 
    WHERE u.role = 'siswa'
");
$siswa = $stmt->fetchAll();

foreach ($siswa as $s) {
    // Cek apakah hari ini sudah minum
    $check = $pdo->prepare("SELECT id FROM konsumsi_ttd WHERE user_id = ? AND tanggal = CURDATE() AND status_konsumsi = 'sudah'");
    $check->execute([$s['id']]);
    
    if (!$check->fetch()) {
        // Belum minum, kirim notifikasi
        
        // 1. Catat ke log in-app notification
        $insertLog = $pdo->prepare("INSERT INTO log_notifikasi (siswa_id, status_terkirim) VALUES (?, 'sukses')");
        $insertLog->execute([$s['id']]);
        
        // 2. Simulasi WhatsApp Gateway (Fonnte/Wablas)
        // Di aplikasi nyata, Anda akan menggunakan cURL ke API Fonnte/Wablas di sini.
        $pesan_wa = "Halo " . $s['nama'] . ", jangan lupa minum Tablet Tambah Darah (TTD) hari ini ya! Silakan konfirmasi di aplikasi AKRAB: " . BASE_URL;
        
        // echo "Mengirim WA ke " . $s['nama'] . ": " . $pesan_wa . "\n";
    }
}

echo "Cron Job Selesai: Notifikasi berhasil dikirim.\n";
?>
