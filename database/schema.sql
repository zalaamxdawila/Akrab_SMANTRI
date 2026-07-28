CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    role ENUM('siswa', 'uks') NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    kelas VARCHAR(20) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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
    kapan_rujuk_ke_ahli TEXT NULL
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

-- Insert dummy data for saran_edukasi
INSERT INTO saran_edukasi (kategori_anemia, judul_saran, isi_saran, rekomendasi_makanan, kapan_rujuk_ke_ahli) VALUES
('tidak_anemia', 'Kondisi Sehat', 'Pertahankan pola hidup sehat Anda saat ini.', 'Makan sayur, daging tanpa lemak, dan buah-buahan.', 'Tidak perlu rujukan.'),
('ringan', 'Anemia Ringan', 'Anda memiliki tanda-tanda anemia ringan. Jangan khawatir, tingkatkan asupan zat besi.', 'Bayam, hati ayam, daging merah matang, dan makanan kaya vitamin C.', 'Jika gejala pusing dan lemas memburuk.'),
('sedang', 'Anemia Sedang', 'Anda terdeteksi anemia sedang. Disarankan untuk mulai mengonsumsi TTD secara rutin dan memperbaiki pola makan.', 'Daging merah, hati ayam/sapi, sayuran hijau gelap, dan kurangi kafein setelah makan.', 'Segera hubungi petugas UKS atau puskesmas untuk pemeriksaan darah (Hb).'),
('berat', 'Anemia Berat', 'PERHATIAN: Indikasi anemia berat. Tubuh Anda mungkin sangat kekurangan zat besi atau oksigen.', 'Segera perbanyak makanan tinggi zat besi dan WAJIB konsumsi TTD.', 'SEGERA konsultasikan dengan ahli atau rujuk ke Puskesmas/Rumah Sakit terdekat.');

-- Insert dummy users for testing (password: password123)
-- Siswa
INSERT INTO users (nama, role, username, password_hash, kelas) VALUES ('Budi Santoso', 'siswa', 'budi', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'X-MIPA-1');
-- UKS
INSERT INTO users (nama, role, username, password_hash, kelas) VALUES ('Bu Ani (UKS)', 'uks', 'uks_ani', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL);
