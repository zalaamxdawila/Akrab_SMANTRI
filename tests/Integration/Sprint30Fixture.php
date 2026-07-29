<?php

declare(strict_types=1);

final class Sprint30Fixture
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
        $meta = 'corrected_at TEXT, corrected_by INTEGER, correction_reason TEXT,
            archived_at TEXT, archived_by INTEGER, archive_reason TEXT';
        $pdo->exec("CREATE TABLE konsultasi (
            id INTEGER PRIMARY KEY, siswa_id INTEGER, ahli_id INTEGER,
            pertanyaan TEXT, status TEXT, tanggal_kirim TEXT, {$meta}
        )");
        $pdo->exec("CREATE TABLE balasan_konsultasi (
            id INTEGER PRIMARY KEY, konsultasi_id INTEGER, isi_balasan TEXT,
            tanggal_balas TEXT, {$meta}
        )");
        $pdo->exec("CREATE TABLE artikel_edukasi (
            id INTEGER PRIMARY KEY, uks_id INTEGER, judul TEXT, konten TEXT,
            tanggal_publikasi TEXT, {$meta}
        )");
        $pdo->exec("CREATE TABLE saran_edukasi (
            id INTEGER PRIMARY KEY, kategori_anemia TEXT, judul_saran TEXT,
            isi_saran TEXT, rekomendasi_makanan TEXT, kapan_rujuk_ke_ahli TEXT,
            {$meta}
        )");
        $pdo->exec("CREATE TABLE jadwal_notifikasi (
            id INTEGER PRIMARY KEY, siswa_id INTEGER, jam_pengingat TEXT,
            hari TEXT, aktif INTEGER, {$meta}
        )");
        $pdo->exec("CREATE TABLE log_notifikasi (
            id INTEGER PRIMARY KEY, siswa_id INTEGER, tanggal_kirim TEXT,
            status_terkirim TEXT, sudah_dikonfirmasi INTEGER, {$meta}
        )");
        $pdo->exec("INSERT INTO users VALUES
            (1,'Master','master','superadmin','active'),
            (2,'Siswa','siswa1','siswa','active'),
            (3,'UKS A','uksa','uks','active')");
        $pdo->exec("INSERT INTO konsultasi VALUES
            (1,2,3,'Pertanyaan awal','dijawab','2026-07-20',NULL,NULL,NULL,NULL,NULL,NULL)");
        $pdo->exec("INSERT INTO balasan_konsultasi VALUES
            (1,1,'Jawaban awal','2026-07-21',NULL,NULL,NULL,NULL,NULL,NULL)");
        $pdo->exec("INSERT INTO artikel_edukasi VALUES
            (1,3,'Judul','Konten aman','2026-07-20',NULL,NULL,NULL,NULL,NULL,NULL)");
        $pdo->exec("INSERT INTO saran_edukasi VALUES
            (1,'ringan','Saran','Isi','Makanan','Rujuk',NULL,NULL,NULL,NULL,NULL,NULL)");
        $pdo->exec("INSERT INTO jadwal_notifikasi VALUES
            (1,2,'07:00:00','harian',1,NULL,NULL,NULL,NULL,NULL,NULL)");
        $pdo->exec("INSERT INTO log_notifikasi VALUES
            (1,2,'2026-07-20','sukses',0,NULL,NULL,NULL,NULL,NULL,NULL)");
        return $pdo;
    }
}
