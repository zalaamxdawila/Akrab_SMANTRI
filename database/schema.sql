CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    role ENUM('siswa', 'uks', 'orangtua') NOT NULL DEFAULT 'siswa',
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    kelas VARCHAR(20) NULL,
    anak_username VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS parent_student_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parent_id INT NOT NULL,
    student_id INT NULL,
    requested_student_username VARCHAR(50) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    reviewed_by INT NULL,
    requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    UNIQUE KEY uq_parent_student_link (parent_id),
    KEY idx_parent_links_status (status),
    FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS audit_log (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    actor_id INT NULL,
    action VARCHAR(80) NOT NULL,
    target_type VARCHAR(50) NOT NULL,
    target_id INT NULL,
    metadata_json JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_audit_actor_created (actor_id, created_at),
    KEY idx_audit_action_created (action, created_at),
    FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS registration_attempts (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    client_hash CHAR(64) NOT NULL,
    attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_registration_attempts_client_time (client_hash, attempted_at)
);

CREATE TABLE IF NOT EXISTS csv_import_batches (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    batch_hash CHAR(64) NOT NULL UNIQUE,
    created_by INT NOT NULL,
    imported_count INT NOT NULL DEFAULT 0,
    skipped_count INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS kuesioner (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    -- I. Karakteristik
    tanggal_wawancara DATE NULL,
    nomor_responden VARCHAR(50) NULL,
    inisial_responden VARCHAR(50) NULL,
    tanggal_lahir DATE NULL,
    tempat_lahir VARCHAR(100) NULL,
    alamat TEXT NULL,
    pendidikan VARCHAR(20) NULL,
    
    -- II. Lab Darah (Kaggle Dataset Features)
    kadar_hb DECIMAL(5,2) NULL,
    kadar_mchc DECIMAL(5,2) NULL,
    kadar_mcv DECIMAL(5,2) NULL,
    kadar_mch DECIMAL(5,2) NULL,
    
    -- III. Gejala (1-10)
    skor_gejala INT NOT NULL DEFAULT 0,
    
    -- IV. Sikap (1-4)
    skor_sikap INT NOT NULL DEFAULT 0,
    
    -- V. Pengetahuan
    skor_pengetahuan INT NOT NULL DEFAULT 0,
    
    -- VI. Menstruasi
    mens_sudah ENUM('ya', 'belum') NULL,
    mens_usia_th INT NULL,
    mens_teratur ENUM('ya', 'tidak') NULL,
    mens_lama_hari INT NULL,
    
    -- VII. Pola Makan
    skor_makan INT NOT NULL DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS hasil_deteksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    probabilitas_risiko DECIMAL(5,4) NOT NULL,
    kategori_risiko ENUM('rendah', 'sedang', 'tinggi') NOT NULL,
    tanggal DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS konsumsi_ttd (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tanggal DATE NOT NULL,
    status_konsumsi ENUM('sudah', 'belum') NOT NULL,
    waktu_input TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS kadar_hb (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    nilai_hb DECIMAL(4,1) NOT NULL,
    kategori_anemia ENUM('tidak_anemia', 'ringan', 'sedang', 'berat') NOT NULL,
    tanggal_periksa DATE NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS saran_edukasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kategori_anemia ENUM('tidak_anemia', 'ringan', 'sedang', 'berat') NOT NULL,
    judul_saran VARCHAR(100) NOT NULL,
    isi_saran TEXT NOT NULL,
    rekomendasi_makanan TEXT NOT NULL,
    kapan_rujuk_ke_ahli TEXT NULL,
    UNIQUE KEY uq_saran_edukasi_kategori (kategori_anemia)
);

CREATE TABLE IF NOT EXISTS konsultasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    siswa_id INT NOT NULL,
    ahli_id INT NULL,
    pertanyaan TEXT NOT NULL,
    status ENUM('menunggu', 'dijawab') DEFAULT 'menunggu',
    tanggal_kirim TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (siswa_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (ahli_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS balasan_konsultasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    konsultasi_id INT NOT NULL,
    isi_balasan TEXT NOT NULL,
    tanggal_balas TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (konsultasi_id) REFERENCES konsultasi(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS jadwal_notifikasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    siswa_id INT NOT NULL,
    jam_pengingat TIME NOT NULL,
    hari ENUM('harian', 'mingguan', 'saat_menstruasi') NOT NULL,
    aktif BOOLEAN DEFAULT 1,
    FOREIGN KEY (siswa_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS log_notifikasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    siswa_id INT NOT NULL,
    tanggal_kirim TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status_terkirim ENUM('sukses', 'gagal') NOT NULL,
    sudah_dikonfirmasi BOOLEAN DEFAULT 0,
    FOREIGN KEY (siswa_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS riwayat_haid (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tanggal_mulai DATE NOT NULL,
    tanggal_selesai DATE NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS artikel_edukasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uks_id INT NOT NULL,
    judul VARCHAR(255) NOT NULL,
    konten TEXT NOT NULL,
    tanggal_publikasi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uks_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS schema_migrations (
    version VARCHAR(100) PRIMARY KEY,
    description VARCHAR(255) NOT NULL,
    applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
