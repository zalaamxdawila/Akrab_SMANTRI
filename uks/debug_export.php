<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config.php';
require_once '../helpers.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS riwayat_haid (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        tanggal_mulai DATE NOT NULL,
        tanggal_selesai DATE NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    $stmt = $pdo->query("
        SELECT 
            u.id, u.nama, u.kelas, u.username as nisn,
            (SELECT COUNT(*) FROM riwayat_haid rh WHERE rh.user_id = u.id AND rh.tanggal_selesai IS NULL) as is_haid,
            (SELECT COUNT(*) FROM konsumsi_ttd k WHERE k.user_id = u.id AND k.status_konsumsi = 'sudah') as total_ttd,
            (SELECT kategori_risiko FROM hasil_deteksi hd WHERE hd.user_id = u.id ORDER BY tanggal DESC LIMIT 1) as risiko,
            (SELECT saran FROM hasil_deteksi hd WHERE hd.user_id = u.id ORDER BY tanggal DESC LIMIT 1) as saran
        FROM users u
        WHERE u.role = 'siswa'
        ORDER BY u.kelas ASC, u.nama ASC
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "SUCCESS: Fetched " . count($rows) . " rows.<br>";
    echo "<pre>";
    print_r($rows[0] ?? 'No data');
    echo "</pre>";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . " on line " . $e->getLine() . " in " . $e->getFile();
}
