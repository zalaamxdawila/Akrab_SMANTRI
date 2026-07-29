<?php

declare(strict_types=1);

final class Sprint28Fixture
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
            id INTEGER PRIMARY KEY AUTOINCREMENT, nama TEXT, role TEXT,
            username TEXT UNIQUE, password_hash TEXT, status TEXT, kelas TEXT,
            status_changed_at TEXT, status_changed_by INTEGER, status_reason TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )');
        $pdo->exec('CREATE TABLE parent_student_links (
            id INTEGER PRIMARY KEY, parent_id INTEGER, student_id INTEGER,
            requested_student_username TEXT, status TEXT, reviewed_by INTEGER,
            requested_at TEXT DEFAULT CURRENT_TIMESTAMP, reviewed_at TEXT,
            archived_at TEXT, archived_by INTEGER, archive_reason TEXT
        )');
        $pdo->exec('CREATE TABLE audit_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT, actor_id INTEGER,
            authenticated_actor_id INTEGER, effective_actor_id INTEGER,
            impersonation_session_id INTEGER, request_id TEXT, action TEXT,
            target_type TEXT, target_id INTEGER, metadata_json TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )');
        $pdo->exec("INSERT INTO users
            (id,nama,role,username,password_hash,status,kelas) VALUES
            (1,'Master','superadmin','master','x','active',NULL),
            (2,'Siswa','siswa','siswa1','x','active','VIII A'),
            (3,'Wali','orangtua','parent1','x','active',NULL)");
        $pdo->exec("INSERT INTO parent_student_links
            (id,parent_id,student_id,requested_student_username,status)
            VALUES (1,3,NULL,'siswa1','pending')");
        return $pdo;
    }
}
