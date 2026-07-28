<?php
require_once 'config.php';
try {
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
    $res = $stmt->fetchAll();
    echo "SUCCESS: " . count($res) . " rows";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
