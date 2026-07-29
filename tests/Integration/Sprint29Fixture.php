<?php

declare(strict_types=1);

final class Sprint29Fixture
{
    public static function actor(): ActorContext
    {
        return new ActorContext(1, 1, 'superadmin', 'superadmin');
    }

    public static function database(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('CREATE TABLE users (
            id INTEGER PRIMARY KEY, nama TEXT, username TEXT, role TEXT, status TEXT
        )');
        $pdo->exec('CREATE TABLE audit_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT, actor_id INTEGER,
            authenticated_actor_id INTEGER, effective_actor_id INTEGER,
            impersonation_session_id INTEGER, request_id TEXT, action TEXT,
            target_type TEXT, target_id INTEGER, metadata_json TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )');
        $metadata = 'corrected_at TEXT, corrected_by INTEGER,
            correction_reason TEXT, archived_at TEXT, archived_by INTEGER,
            archive_reason TEXT';
        $pdo->exec("CREATE TABLE kuesioner (
            id INTEGER PRIMARY KEY, user_id INTEGER, kadar_hb REAL,
            kadar_mchc REAL, kadar_mcv REAL, kadar_mch REAL,
            skor_gejala INTEGER, skor_sikap INTEGER,
            skor_pengetahuan INTEGER, skor_makan INTEGER,
            created_at TEXT, {$metadata}
        )");
        $pdo->exec("CREATE TABLE hasil_deteksi (
            id INTEGER PRIMARY KEY, user_id INTEGER, probabilitas_risiko REAL,
            kategori_risiko TEXT, tanggal TEXT, created_at TEXT, {$metadata}
        )");
        $pdo->exec("CREATE TABLE kadar_hb (
            id INTEGER PRIMARY KEY, user_id INTEGER, nilai_hb REAL,
            kategori_anemia TEXT, tanggal_periksa TEXT, {$metadata}
        )");
        $pdo->exec("CREATE TABLE konsumsi_ttd (
            id INTEGER PRIMARY KEY, user_id INTEGER, tanggal TEXT,
            status_konsumsi TEXT, waktu_input TEXT, {$metadata},
            UNIQUE(user_id, tanggal)
        )");
        $pdo->exec("CREATE TABLE riwayat_haid (
            id INTEGER PRIMARY KEY, user_id INTEGER, tanggal_mulai TEXT,
            tanggal_selesai TEXT, {$metadata}
        )");
        $pdo->exec("INSERT INTO users VALUES
            (1,'Master','master','superadmin','active'),
            (2,'Siswa Satu','siswa1','siswa','active'),
            (3,'Siswa Dua','siswa2','siswa','active')");
        $pdo->exec("INSERT INTO kuesioner
            (id,user_id,kadar_hb,skor_gejala,skor_sikap,skor_pengetahuan,skor_makan,created_at)
            VALUES (1,2,11.5,20,15,10,12,'2026-07-20')");
        $pdo->exec("INSERT INTO hasil_deteksi
            (id,user_id,probabilitas_risiko,kategori_risiko,tanggal,created_at)
            VALUES (1,2,0.8,'tinggi','2026-07-20','2026-07-20')");
        $pdo->exec("INSERT INTO kadar_hb VALUES
            (1,2,11.5,'ringan','2026-07-20',NULL,NULL,NULL,NULL,NULL,NULL)");
        $pdo->exec("INSERT INTO konsumsi_ttd VALUES
            (1,2,'2026-07-20','sudah','2026-07-20',NULL,NULL,NULL,NULL,NULL,NULL)");
        $pdo->exec("INSERT INTO riwayat_haid VALUES
            (1,2,'2026-07-20',NULL,NULL,NULL,NULL,NULL,NULL,NULL)");
        return $pdo;
    }
}
