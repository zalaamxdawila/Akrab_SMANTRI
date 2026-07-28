<?php
require_once '../config.php';
require_once '../helpers.php';

check_role('uks');

$type = isset($_GET['type']) ? $_GET['type'] : 'siswa';

// Bersihkan output buffer apa pun yang mungkin bocor dari file konfigurasi
if (ob_get_length()) {
    ob_end_clean();
}

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');

if ($type === 'log') {
    header('Content-Disposition: attachment; filename="Log_Konsumsi_TTD_AKRAB.csv"');
} else {
    header('Content-Disposition: attachment; filename="Data_Siswa_Risiko_AKRAB.csv"');
}

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Output UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

if ($type === 'siswa') {
    fputcsv($output, array_map('csvSafeCell', ['ID', 'Nama', 'Kelas', 'NISN', 'Status Haid', 'Total Minum TTD', 'Kategori Risiko']));

    // Fetch data
    $stmt = $pdo->query("
        SELECT 
            u.id, u.nama, u.kelas, u.username as nisn,
            (SELECT COUNT(*) FROM riwayat_haid rh WHERE rh.user_id = u.id AND rh.tanggal_selesai IS NULL) as is_haid,
            (SELECT COUNT(*) FROM konsumsi_ttd k WHERE k.user_id = u.id AND k.status_konsumsi = 'sudah') as total_ttd,
            (SELECT kategori_risiko FROM hasil_deteksi hd WHERE hd.user_id = u.id ORDER BY tanggal DESC, id DESC LIMIT 1) as risiko
        FROM users u
        WHERE u.role = 'siswa'
        ORDER BY u.kelas ASC, u.nama ASC
    ");

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $status_haid = $row['is_haid'] > 0 ? 'Sedang Haid' : 'Tidak';
        $risiko = $row['risiko'] ? strtoupper($row['risiko']) : 'BELUM SCREENING';
        
        fputcsv($output, array_map('csvSafeCell', [
            $row['id'],
            $row['nama'],
            $row['kelas'],
            $row['nisn'],
            $status_haid,
            $row['total_ttd'],
            $risiko
        ]));
    }
} elseif ($type === 'log') {
    fputcsv($output, array_map('csvSafeCell', ['Tanggal', 'Nama Siswa', 'Kelas', 'Status Konsumsi']));

    $stmt = $pdo->query("
        SELECT k.tanggal, u.nama, u.kelas, k.status_konsumsi
        FROM konsumsi_ttd k
        JOIN users u ON k.user_id = u.id
        ORDER BY k.tanggal DESC
    ");

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, array_map('csvSafeCell', [
            $row['tanggal'],
            $row['nama'],
            $row['kelas'],
            strtoupper($row['status_konsumsi'])
        ]));
    }
}

fclose($output);
exit;
